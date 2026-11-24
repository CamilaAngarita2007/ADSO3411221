<?php

require_once __DIR__ . '/../Config/conexion.php';

class PerfilAsociado
{
    private $conexion;

    public function __construct()
    {
        $conexiondb = new Conexion();
        $this->conexion = $conexiondb->conectar();
    }

    // Datos del perfil sin la contraseña
    public function obtenerPorDocumento($documento)
    {
        $sql = "SELECT documento, nombres, apellidos, fechaNacimiento, genero, direccionResidencia, ciudad, telefono, email, rol
                FROM asociados WHERE documento = :documento LIMIT 1";
        $consulta = $this->conexion->prepare($sql);
        $consulta->execute([':documento' => $documento]);
        return $consulta->fetch() ?: null;
    }

    public function emailUsadoPorOtro($email, $documento)
    {
        $consulta = $this->conexion->prepare("SELECT 1 FROM asociados WHERE email = :email AND documento <> :documento LIMIT 1");
        $consulta->execute([':email' => $email, ':documento' => $documento]);
        return (bool)$consulta->fetchColumn();
    }

    // El documento y el rol no se editan desde el perfil
    public function editar($documento, $datos)
    {
        $sql = "UPDATE asociados SET nombres = :nombres, apellidos = :apellidos, fechaNacimiento = :fechaNacimiento,
                    genero = :genero, direccionResidencia = :direccionResidencia, ciudad = :ciudad,
                    telefono = :telefono, email = :email";
        $parametros = [
            ':nombres' => $datos['nombres'],
            ':apellidos' => $datos['apellidos'],
            ':fechaNacimiento' => $datos['fechaNacimiento'],
            ':genero' => $datos['genero'],
            ':direccionResidencia' => $datos['direccionResidencia'],
            ':ciudad' => $datos['ciudad'],
            ':telefono' => $datos['telefono'],
            ':email' => $datos['email'],
            ':documento' => $documento
        ];
        // La contraseña solo cambia si el usuario escribió una nueva
        if (isset($datos['passwordHash'])) {
            $sql .= ", passwordHash = :passwordHash";
            $parametros[':passwordHash'] = $datos['passwordHash'];
        }
        $sql .= " WHERE documento = :documento";

        $consulta = $this->conexion->prepare($sql);
        return $consulta->execute($parametros);
    }
}
