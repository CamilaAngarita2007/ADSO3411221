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

function validarId($id)
{
    $idSolicitud = filter_var($id, FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]);
    if ($idSolicitud === false) {
        http_response_code(400);
        throw new Exception("El identificador de la solicitud no es válido");
    }
    return $idSolicitud;
}

try {
    if (!isset($_SESSION['documento'], $_SESSION['rol'])) {
        http_response_code(401);
        throw new Exception("iniciar sesion para acceder a las solicitudes");
    }
    // Solo el administrador gestiona las solicitudes
    if ($_SESSION['rol'] !== "admin") {
        http_response_code(403);
        throw new Exception("No tienes permisos para realizar esta acción");
    }
    $metodo = $_SERVER['REQUEST_METHOD'];
    if (!in_array($metodo, ['GET', 'POST', 'DELETE'], true)) {
        http_response_code(405);
        throw new Exception("Método HTTP no permitido");
    }

    require_once __DIR__ . '/prestamos.php';
    $prestamos = new Prestamos();
    switch ($metodo) {
        case 'GET':
            $resultado = [
                "success" => true,
                "solicitudes" => $prestamos->obtenerSolicitudes()
            ];
            http_response_code(200);
            break;
        case 'POST':
            // Aceptar: el préstamo se guarda en prestamos
            $idSolicitud = validarId(leerDatos()["idSolicitud"] ?? null);
            if (!$prestamos->aceptarSolicitud($idSolicitud)) {
                http_response_code(404);
                throw new Exception("No se encontró la solicitud");
            }
            $resultado = [
                "success" => true,
                "message" => "Solicitud aceptada. El préstamo quedó registrado"
            ];
            http_response_code(200);
            break;
        case 'DELETE':
            // Rechazar: la solicitud se elimina sin guardarse en prestamos
            $idSolicitud = validarId(leerDatos()["idSolicitud"] ?? null);
            if (!$prestamos->rechazarSolicitud($idSolicitud)) {
                http_response_code(404);
                throw new Exception("No se encontró la solicitud");
            }
            $resultado = [
                "success" => true,
                "message" => "Solicitud rechazada y eliminada"
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
