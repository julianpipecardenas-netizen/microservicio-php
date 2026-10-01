<?php

if (php_sapi_name() === 'cli-server') {
    $ruta_solicitada = realpath(__DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
    if ($ruta_solicitada && is_file($ruta_solicitada) && strpos($ruta_solicitada, __DIR__) === 0) {
        return false; // deja que el servidor sirva el archivo tal cual (HTML, JSON, etc.)
    }
}

header("Content-Type: application/json");

$supabaseUrl = getenv("SUPABASE_URL");
$supabaseKey = getenv("SUPABASE_KEY");

function llamarSupabase($metodo, $ruta, $datos = null) {
    global $supabaseUrl, $supabaseKey;
    $ch = curl_init($supabaseUrl . "/rest/v1/comentarios" . $ruta);
    $headers = [
        "apikey: $supabaseKey",
        "Authorization: Bearer $supabaseKey",
        "Content-Type: application/json",
        "Prefer: return=representation",
    ];
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $metodo);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    if ($datos !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($datos));
    }
    $respuesta = curl_exec($ch);
    curl_close($ch);
    return $respuesta;
}

$metodo = $_SERVER["REQUEST_METHOD"];
$uri = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
$partes = explode("/", trim($uri, "/"));

$datos = json_decode(file_get_contents("php://input"), true);

if ($metodo === "POST" && $partes[0] === "comentarios") {
    if (empty($datos["nombre_cliente"]) || empty($datos["comentario"])) {
        http_response_code(400);
        echo json_encode(["error" => "Faltan datos obligatorios"]);
        exit;
    }
    http_response_code(201);
    echo llamarSupabase("POST", "", $datos);
    exit;
}

if ($metodo === "PUT" && $partes[0] === "comentarios" && isset($partes[1])) {
    echo llamarSupabase("PATCH", "?id=eq." . $partes[1], $datos);
    exit;
}

if ($metodo === "DELETE" && $partes[0] === "comentarios" && isset($partes[1])) {
    llamarSupabase("DELETE", "?id=eq." . $partes[1]);
    http_response_code(204);
    exit;
}

http_response_code(404);
echo json_encode(["error" => "Ruta no encontrada"]);
