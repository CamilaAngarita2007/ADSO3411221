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

function validarValorCuota($valor)
{
    if (!is_numeric($valor) || $valor <= 0 || $valor > 9999999999.99) {
        http_response_code(400);
        throw new Exception("Ingresa un valor de abono válido");
    }
    return round((float)$valor, 2);
}

function validarId($id, $mensaje)
{
    $identificador = filter_var($id, FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]);
    if ($identificador === false) {
        http_response_code(400);
        throw new Exception($mensaje);
    }
    return $identificador;
}

function validarFecha($fecha)
{
    $fechaObjeto = is_string($fecha) ? DateTime::createFromFormat('Y-m-d', $fecha) : false;
    if ($fechaObjeto === false || $fechaObjeto->format('Y-m-d') !== $fecha) {
        http_response_code(400);
        throw new Exception("Ingresa una fecha válida");
    }
    return $fecha;
}

function dinero($valor)
{
    return "$" . number_format((float)$valor, 2, ',', '.');
}

try {
    if (!isset($_SESSION['documento'], $_SESSION['rol'])) {
        http_response_code(401);
        throw new Exception("iniciar sesion para acceder a pagos");
    }
    $metodo = $_SERVER['REQUEST_METHOD'];
    if (!in_array($metodo, ['GET', 'POST', 'PUT', 'DELETE'], true)) {
        http_response_code(405);
        throw new Exception("Método HTTP no permitido");
    }
    $rol = $_SESSION['rol'];

    // Solo los asociados abonan a sus préstamos
    if ($metodo === 'POST' && $rol !== "asociado") {
        http_response_code(403);
        throw new Exception("Solo los asociados pueden registrar abonos");
    }
    // Solo el administrador edita o elimina abonos
    if (in_array($metodo, ['PUT', 'DELETE'], true) && $rol !== "admin") {
        http_response_code(403);
        throw new Exception("No tienes permisos para realizar esta acción");
    }

    require_once __DIR__ . '/pagos.php';
    $pagos = new Pagos();
    switch ($metodo) {
        case 'GET':
            if ($rol === "admin") {
                $resultado = [
                    "success" => true,
                    "rol" => $rol,
                    "pagos" => $pagos->obtenerTodos()
                ];
            } else {
                // El asociado solo ve sus préstamos que todavía tienen deuda
                $idAsociado = $pagos->obtenerIdAsociado($_SESSION['documento']);
                $lista = $idAsociado === null ? [] : $pagos->obtenerPrestamosConDeuda($idAsociado);
                $resultado = [
                    "success" => true,
                    "rol" => $rol,
                    "prestamos" => $lista
                ];
            }
            http_response_code(200);
            break;
        case 'POST':
            $datos = leerDatos();
            $idPrestamo = validarId($datos["idPrestamo"] ?? null, "El identificador del préstamo no es válido");
            $valorCuota = validarValorCuota($datos["valorCuota"] ?? null);

            $idAsociado = $pagos->obtenerIdAsociado($_SESSION['documento']);
            $estado = $idAsociado === null ? ['estado' => 'no_existe'] : $pagos->abonar($idPrestamo, $idAsociado, $valorCuota);
            switch ($estado['estado']) {
                case 'no_existe':
                    http_response_code(404);
                    throw new Exception("No se encontró el préstamo");
                case 'sin_deuda':
                    http_response_code(400);
                    throw new Exception("Este préstamo ya no tiene deuda pendiente");
                case 'excede':
                    http_response_code(400);
                    throw new Exception("El abono no puede superar la deuda actual de " . dinero($estado['deuda']));
            }
            $resultado = [
                "success" => true,
                "message" => "Abono registrado correctamente"
            ];
            http_response_code(201);
            break;
        case 'PUT':
            $datos = leerDatos();
            $idPago = validarId($datos["idPago"] ?? null, "El identificador del pago no es válido");
            $valorCuota = validarValorCuota($datos["valorCuota"] ?? null);
            $fechaPago = validarFecha($datos["fechaPago"] ?? null);

            $estado = $pagos->editarAbono($idPago, $valorCuota, $fechaPago);
            switch ($estado['estado']) {
                case 'no_existe':
                    http_response_code(404);
                    throw new Exception("No se encontró el pago");
                case 'excede':
                    http_response_code(400);
                    throw new Exception("El abono no puede superar la deuda disponible de " . dinero($estado['deuda']));
            }
            $resultado = [
                "success" => true,
                "message" => "Pago actualizado correctamente"
            ];
            http_response_code(200);
            break;
        case 'DELETE':
            $datos = leerDatos();
            $idPago = validarId($datos["idPago"] ?? null, "El identificador del pago no es válido");
            if (!$pagos->eliminarAbono($idPago)) {
                http_response_code(404);
                throw new Exception("No se encontró el pago");
            }
            $resultado = [
                "success" => true,
                "message" => "Pago eliminado correctamente"
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
