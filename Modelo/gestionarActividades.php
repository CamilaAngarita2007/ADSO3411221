<?php
session_start();

header('Content-Type: application/json; charset=UTF-8');

function leerDatos()
{
    $datos = json_decode(file_get_contents('php://input'), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        throw new Exception("Error al decodificar JSON: " . json_last_error_msg());
    }
    return $datos;
}

function validarActividad($datos)
{
    $nombre = $datos["nombreActividad"] ?? null;
    $fecha = $datos["fechaEvento"] ?? null;
    $municipio = $datos["municipio"] ?? null;
    if (!is_string($nombre) || trim($nombre) === "" || strlen($nombre) > 100 || !is_string($municipio) || trim($municipio) === "" || strlen($municipio) > 50) {
        http_response_code(400);
        throw new Exception("Ingresa un nombre y municipio validos");
    }
    return [
        "nombreActividad" => trim($nombre),
        "fechaEvento" => $fecha,
        "municipio" => trim($municipio),
        "totalRecaudado" => $datos["totalRecaudado"] ?? null,
    ];
}

function validarId($id)
{
    $idActividad = filter_var($id, FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]);
    if ($idActividad === false) {
        http_response_code(400);
        throw new Exception("El identificador de la actividad no es válido");
    }
    return $idActividad;
}

try {
    if (!isset($_SESSION['documento'], $_SESSION['rol'])) {
        http_response_code(401);
        throw new Exception("iniciar sesion para acceder a actividades");
    }
    $metodo = $_SERVER['REQUEST_METHOD'];
    if (!in_array($metodo, ['GET', 'POST', 'PUT', 'DELETE'], true)) {
        http_response_code(405);
        throw new Exception("Método HTTP no permitido");
    }
    if ($metodo !== 'GET' && $_SESSION['rol'] !== "admin") {
        http_response_code(403);
        throw new Exception("No tienes permisos para realizar esta acción");
    }
    require_once __DIR__ . '/actividades.php';
    $actividades = new Actividades();
    switch ($metodo) {
        case 'GET':
            $resultado = [
                "success" => true,
                "rol" => $_SESSION['rol'],
                "actividades" => $actividades->obtenerActividades()
            ];
            http_response_code(200);
            break;
        case 'POST':
            $datos = validarActividad(leerDatos());
            $actividades->guardar($datos);
            $resultado = [
                "success" => true,
                "message" => "Actividad creada correctamente"
            ];
            http_response_code(201);
            break;
        case 'PUT':
            $datos = leerDatos();
            $datos["idActividad"] = validarId($datos["idActividad"] ?? null);
            $datos = array_merge($datos, validarActividad($datos));
            if (!$actividades->editar($datos["idActividad"], $datos)) {
                http_response_code(404);
                throw new Exception("No se encontró la actividad");
            }
            $resultado = [
                "success" => true,
                "message" => "Actividad actualizada correctamente"
            ];
            http_response_code(200);
            break;
        case 'DELETE':
            $datos = leerDatos();
            $idActividad = validarId($datos["idActividad"] ?? null);
            if (!$actividades->eliminar($idActividad)) {
                http_response_code(404);
                throw new Exception("No se encontró la actividad");
            }
            $resultado = [
                "success" => true,
                "message" => "Actividad eliminada correctamente"
            ];
            http_response_code(200);
            break;
    }
    echo json_encode($resultado);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "error en la base de datos: " . $e->getMessage()
    ]);

} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}
