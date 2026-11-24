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

function textoRequerido($valor, $maximo, $mensaje)
{
    if (!is_string($valor) || trim($valor) === "" || strlen($valor) > $maximo) {
        http_response_code(400);
        throw new Exception($mensaje);
    }
    return trim($valor);
}

function validarAsociado($datos)
{
    $fechaNacimiento = $datos["fechaNacimiento"] ?? null;
    $fechaObjeto = is_string($fechaNacimiento) ? DateTime::createFromFormat('Y-m-d', $fechaNacimiento) : false;
    if ($fechaObjeto === false || $fechaObjeto->format('Y-m-d') !== $fechaNacimiento) {
        http_response_code(400);
        throw new Exception("Ingresa una fecha de nacimiento válida");
    }

    $genero = $datos["genero"] ?? null;
    if (!in_array($genero, ["Masculino", "Femenino", "Otro"], true)) {
        http_response_code(400);
        throw new Exception("Selecciona un género válido");
    }

    $rol = $datos["rol"] ?? null;
    if (!in_array($rol, ["Admin", "Asociado"], true)) {
        http_response_code(400);
        throw new Exception("Selecciona un rol válido");
    }

    $email = $datos["email"] ?? null;
    if (!is_string($email) || strlen($email) > 100 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        throw new Exception("Ingresa un correo electrónico válido");
    }

    $validado = [
        "nombres" => textoRequerido($datos["nombres"] ?? null, 50, "Ingresa los nombres (máximo 50 caracteres)"),
        "apellidos" => textoRequerido($datos["apellidos"] ?? null, 50, "Ingresa los apellidos (máximo 50 caracteres)"),
        "fechaNacimiento" => $fechaNacimiento,
        "genero" => $genero,
        "direccionResidencia" => textoRequerido($datos["direccionResidencia"] ?? null, 100, "Ingresa la dirección (máximo 100 caracteres)"),
        "ciudad" => textoRequerido($datos["ciudad"] ?? null, 50, "Ingresa la ciudad (máximo 50 caracteres)"),
        "telefono" => textoRequerido($datos["telefono"] ?? null, 20, "Ingresa un teléfono válido (máximo 20 caracteres)"),
        "email" => trim($email),
        "rol" => $rol,
    ];

    // La contraseña es opcional: solo se cambia si se escribe una nueva
    $nuevaPassword = $datos["nuevaPassword"] ?? "";
    if ($nuevaPassword !== "") {
        if (!is_string($nuevaPassword) || strlen($nuevaPassword) < 8) {
            http_response_code(400);
            throw new Exception("La nueva contraseña debe tener al menos 8 caracteres");
        }
        $validado["passwordHash"] = password_hash($nuevaPassword, PASSWORD_DEFAULT);
    }
    return $validado;
}

function validarId($id)
{
    $idAsociado = filter_var($id, FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]);
    if ($idAsociado === false) {
        http_response_code(400);
        throw new Exception("El identificador del asociado no es válido");
    }
    return $idAsociado;
}

try {
    if (!isset($_SESSION['documento'], $_SESSION['rol'])) {
        http_response_code(401);
        throw new Exception("iniciar sesion para acceder a asociados");
    }
    // Solo el administrador gestiona los asociados
    if ($_SESSION['rol'] !== "admin") {
        http_response_code(403);
        throw new Exception("No tienes permisos para realizar esta acción");
    }
    $metodo = $_SERVER['REQUEST_METHOD'];
    if (!in_array($metodo, ['GET', 'PUT', 'DELETE'], true)) {
        http_response_code(405);
        throw new Exception("Método HTTP no permitido");
    }

    require_once __DIR__ . '/asociados.php';
    $asociados = new Asociados();
    switch ($metodo) {
        case 'GET':
            $resultado = [
                "success" => true,
                "asociados" => $asociados->obtenerTodos()
            ];
            http_response_code(200);
            break;
        case 'PUT':
            $datos = leerDatos();
            $idAsociado = validarId($datos["idAsociado"] ?? null);
            if ($asociados->obtenerPorId($idAsociado) === null) {
                http_response_code(404);
                throw new Exception("No se encontró el asociado");
            }
            $datos = validarAsociado($datos);
            if ($asociados->emailUsadoPorOtro($datos["email"], $idAsociado)) {
                http_response_code(400);
                throw new Exception("Ese correo ya está registrado por otro asociado");
            }
            // Un admin no puede quitarse a sí mismo el rol de administrador
            $propio = $asociados->obtenerPorId($idAsociado);
            if ((string)$propio['documento'] === (string)$_SESSION['documento'] && $datos["rol"] !== "Admin") {
                http_response_code(400);
                throw new Exception("No puedes quitarte a ti mismo el rol de administrador");
            }
            $asociados->editar($idAsociado, $datos);
            $resultado = [
                "success" => true,
                "message" => "Asociado actualizado correctamente"
            ];
            http_response_code(200);
            break;
        case 'DELETE':
            $datos = leerDatos();
            $idAsociado = validarId($datos["idAsociado"] ?? null);
            $propio = $asociados->obtenerPorId($idAsociado);
            if ($propio !== null && (string)$propio['documento'] === (string)$_SESSION['documento']) {
                http_response_code(400);
                throw new Exception("No puedes eliminar tu propia cuenta");
            }
            if (!$asociados->eliminar($idAsociado)) {
                http_response_code(404);
                throw new Exception("No se encontró el asociado");
            }
            $resultado = [
                "success" => true,
                "message" => "Asociado eliminado correctamente"
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
