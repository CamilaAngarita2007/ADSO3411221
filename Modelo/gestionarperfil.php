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

function validarPerfil($datos)
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

try {
    if (!isset($_SESSION['documento'], $_SESSION['rol'])) {
        http_response_code(401);
        throw new Exception("iniciar sesion para acceder al perfil");
    }
    $metodo = $_SERVER['REQUEST_METHOD'];
    if (!in_array($metodo, ['GET', 'PUT'], true)) {
        http_response_code(405);
        throw new Exception("Método HTTP no permitido");
    }

    require_once __DIR__ . '/perfilasociado.php';
    $perfil = new PerfilAsociado();
    // Cada usuario solo consulta y edita su propio perfil, identificado por la sesión
    $documento = $_SESSION['documento'];

    switch ($metodo) {
        case 'GET':
            $datos = $perfil->obtenerPorDocumento($documento);
            if ($datos === null) {
                http_response_code(404);
                throw new Exception("No se encontró el perfil");
            }
            $resultado = [
                "success" => true,
                "perfil" => $datos
            ];
            http_response_code(200);
            break;
        case 'PUT':
            if ($perfil->obtenerPorDocumento($documento) === null) {
                http_response_code(404);
                throw new Exception("No se encontró el perfil");
            }
            $datos = validarPerfil(leerDatos());
            if ($perfil->emailUsadoPorOtro($datos["email"], $documento)) {
                http_response_code(400);
                throw new Exception("Ese correo ya está registrado por otro asociado");
            }
            $perfil->editar($documento, $datos);
            $resultado = [
                "success" => true,
                "message" => "Perfil actualizado correctamente"
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
