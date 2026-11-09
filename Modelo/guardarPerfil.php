<?php
header("content-type: application/json; charset=UTF-8");
require_once __DIR__ . '/../Modelo/Perfil.php';
try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        //Saltar a catch y mostrar el mensaje
        throw new Exception("Metodo HTTP no permitido", 405);
    }
    //Leer el contenido JSON de la solicitud
    $json = file_get_contents('php://input');
    //Decodificar el JSON en un array asociativo
    $datos = json_decode($json, true);
    if (!is_array($datos) || json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        throw new Exception("Datos inválidos", 400);
    }

    $campos = ['documento', 'nombres', 'apellidos', 'fechaNacimiento', 'genero', 'direccionResidencia', 'ciudad', 'telefono', 'email'];
    foreach ($campos as $campo) {
        if (!isset($datos[$campo]) || empty($datos[$campo])) {
            http_response_code(400);
            throw new Exception("El campo '$campo' es obligatorio", 400);
        }
    }
    if (!filter_var($datos['email'], FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        throw new Exception("El correo electrónico no es válido", 400);
    }
    $asociado = new perfil($datos);
    $resultado = $asociado->guardar();
    if ($resultado) {
        http_response_code(201);
        echo json_encode(["success" => true, "message" => "Perfil guardado exitosamente"]);
    } else {
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "Error al guardar el perfil"]);
    }
} catch(PDOException $e) {
    if ($e->getCode()==23000) {
        http_response_code(409);
        echo json_encode(["success"=> false,"message"=> "Error: El documento ya existe en la base de datos"]);
    } else {
        http_response_code(500);
        echo json_encode(["success"=> false,"message"=> "Error de base de datos: " . $e->getMessage()]);
    }
} catch (Exception $e) {
    http_response_code($e->getCode());
    echo json_encode(["success"=> false,"message"=> $e->getMessage()]);
}