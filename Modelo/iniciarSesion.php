<?php
session_start();
header("content-type: application/json; charset=UTF-8");
require_once __DIR__ . '/../Config/conexion.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Metodo HTTP no permitido", 405);
    }
    $datos = json_decode(file_get_contents('php://input'), true);
    if (!is_array($datos) || empty($datos['email']) || empty($datos['password'])) {
        throw new Exception("Correo y contraseña son obligatorios", 400);
    }

    $db = (new conexion())->conectar();
    $consulta = $db->prepare("SELECT documento, nombres, apellidos, passwordHash, rol FROM asociados WHERE email = :email LIMIT 1");
    $consulta->execute([':email' => $datos['email']]);
    $usuario = $consulta->fetch();

    if (!$usuario || !password_verify($datos['password'], $usuario['passwordHash'])) {
        throw new Exception("Usuario o contraseña incorrectas", 401);
    }

    $_SESSION["rol"] = strtolower($usuario['rol']);
    $_SESSION["documento"] = $usuario['documento'];

    echo json_encode([
        "success" => true,
        "nombre" => $usuario['nombres'] . " " . $usuario['apellidos'],
        "rol" => $_SESSION["rol"],
        "documento" => $usuario['documento']
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Error de base de datos: " . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code($e->getCode() ?: 500);
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
