<?php

session_start();
header("Content-Type: application/json; charset=utf-8");
try{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        //Saltar a catch y mostrar el mensaje
        throw new Exception("Metodo HTTP no permitido", 405);
    }
    //Decodificar el JSON en un array asociativo
    $datos = json_decode($json, true);
    //error de decodificación
    if (!is_array($datos) || json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        throw new Exception("Datos inválidos", 400);
    }
    // ??'' -> si no nada viene vacio
    $email = $datos['email'] ?? '';
    $passwordHash = $datos['passwordHash'] ??'';
    if(!is_string($email) || !is_string($passwordHash)||$email === '' || $passwordHash === ''){
        http_response_code(400);
        throw new Exception("Email o contraseña invalidos", 400);
    }

    require_once __DIR__ . '/../Config/conexion.php';
    $conexion = (new Conexion())->conectar();

    $consulta = $conexion->prepare("SELECT * FROM asociados WHERE email = :email");
    $consulta->execute([':email' => $email]);
    $asociado = $consulta->fetch(PDO::FETCH_ASSOC);
    
    if (!$asociado || !password_verify($passwordHash, $asociado['passwordHash'] || empty($asociado['passwordHash']))) {
        http_response_code(401);
        throw new Exception("Email o contraseña inválidas", 401);
    }

    //almacenar información del usuario en la sesión
    $_SESSION["rol"] = strtolower($asociado['rol']);
    $_SESSION["documento"] = $asociado['documento'];
    
    echo json_encode([
        "success" => true,
        "rol" => $_SESSION['rol'],
        "documento" => $_SESSION['documento'],
        "nombre" => $asociado['nombres'] . " " . $asociado['apellidos'],
    ]);


}catch (PDOException){
    $errorMessage = "Error: " . $e->getMessage();
    echo json_encode([
        "success" => false,
        "mensaje" => $errorMessage
    ]);
} catch(Exception $e){
    echo json_encode([
        "success" => false,
        "mensaje" => $e->getMessage()
    ]);
}