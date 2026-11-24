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

// Tasa de interés mensual fija para todos los préstamos (en %)
const TASA_INTERES_FIJA = 1.50;

function validarPrestamo($datos)
{
    $valor = $datos["valorPrestamo"] ?? null;
    $cuotas = $datos["numeroCuotas"] ?? null;
    $fecha = $datos["fechaPrestamo"] ?? null;

    if (!is_numeric($valor) || $valor <= 0 || $valor > 9999999999.99) {
        http_response_code(400);
        throw new Exception("Ingresa un valor de préstamo válido");
    }
    if (filter_var($cuotas, FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]) === false) {
        http_response_code(400);
        throw new Exception("Ingresa un número de cuotas válido");
    }
    $fechaObjeto = is_string($fecha) ? DateTime::createFromFormat('Y-m-d', $fecha) : false;
    if ($fechaObjeto === false || $fechaObjeto->format('Y-m-d') !== $fecha) {
        http_response_code(400);
        throw new Exception("Ingresa una fecha válida");
    }
    return [
        "valorPrestamo" => (float)$valor,
        "tasaInteres" => TASA_INTERES_FIJA,
        "numeroCuotas" => (int)$cuotas,
        "fechaPrestamo" => $fecha,
    ];
}

function validarId($id)
{
    $idPrestamo = filter_var($id, FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]);
    if ($idPrestamo === false) {
        http_response_code(400);
        throw new Exception("El identificador del préstamo no es válido");
    }
    return $idPrestamo;
}

try {
    if (!isset($_SESSION['documento'], $_SESSION['rol'])) {
        http_response_code(401);
        throw new Exception("iniciar sesion para acceder a prestamos");
    }
    $metodo = $_SERVER['REQUEST_METHOD'];
    if (!in_array($metodo, ['GET', 'POST', 'PUT', 'DELETE'], true)) {
        http_response_code(405);
        throw new Exception("Método HTTP no permitido");
    }
    $rol = $_SESSION['rol'];

    // Solo los asociados pueden solicitar préstamos
    if ($metodo === 'POST' && $rol !== "asociado") {
        http_response_code(403);
        throw new Exception("Solo los asociados pueden solicitar préstamos");
    }
    // Solo el administrador puede editar o eliminar préstamos
    if (in_array($metodo, ['PUT', 'DELETE'], true) && $rol !== "admin") {
        http_response_code(403);
        throw new Exception("No tienes permisos para realizar esta acción");
    }

    require_once __DIR__ . '/prestamos.php';
    $prestamos = new Prestamos();
    switch ($metodo) {
        case 'GET':
            if ($rol === "admin") {
                $lista = $prestamos->obtenerTodos();
            } else {
                // El asociado solo ve sus propios préstamos
                $idAsociado = $prestamos->obtenerIdAsociado($_SESSION['documento']);
                $lista = $idAsociado === null ? [] : $prestamos->obtenerPorAsociado($idAsociado);
            }
            $resultado = [
                "success" => true,
                "rol" => $rol,
                "prestamos" => $lista
            ];
            http_response_code(200);
            break;
        case 'POST':
            // El asociado se toma de la sesión, nunca del cliente
            $idAsociado = $prestamos->obtenerIdAsociado($_SESSION['documento']);
            if ($idAsociado === null) {
                http_response_code(404);
                throw new Exception("No se encontró el asociado en sesión");
            }
            $datos = validarPrestamo(leerDatos());
            $prestamos->solicitar($idAsociado, $datos);
            $resultado = [
                "success" => true,
                "message" => "Solicitud enviada. Queda pendiente de aprobación del administrador"
            ];
            http_response_code(201);
            break;
        case 'PUT':
            $datos = leerDatos();
            $datos["idPrestamo"] = validarId($datos["idPrestamo"] ?? null);
            $datos = array_merge($datos, validarPrestamo($datos));
            if (!$prestamos->editar($datos["idPrestamo"], $datos)) {
                http_response_code(404);
                throw new Exception("No se encontró el préstamo");
            }
            $resultado = [
                "success" => true,
                "message" => "Préstamo actualizado correctamente"
            ];
            http_response_code(200);
            break;
        case 'DELETE':
            $datos = leerDatos();
            $idPrestamo = validarId($datos["idPrestamo"] ?? null);
            if (!$prestamos->eliminar($idPrestamo)) {
                http_response_code(404);
                throw new Exception("No se encontró el préstamo");
            }
            $resultado = [
                "success" => true,
                "message" => "Préstamo eliminado correctamente"
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
