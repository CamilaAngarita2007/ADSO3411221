<?php

header("Content-Type: application/json; charset=utf-8");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    //Saltar a catch y mostrar el mensaje
    throw new Exception("Metodo HTTP no permitido", 405);
}
session_start();
$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}
session_destroy();
echo json_encode([
    "success" => true,
    "message" => "Sesión cerrada exitosamente"
]);