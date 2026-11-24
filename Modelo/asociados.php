<?php

require_once __DIR__ . '/../Config/conexion.php';

class Asociados
{
    private $conexion;

    public function __construct()
    {
        $conexiondb = new Conexion();
        $this->conexion = $conexiondb->conectar();
    }

    // Lista de asociados sin la contraseña
    public function obtenerTodos()
    {
        $sql = "SELECT idAsociado, documento, nombres, apellidos, fechaNacimiento, genero, direccionResidencia, ciudad, telefono, email, rol
                FROM asociados ORDER BY apellidos ASC, nombres ASC";
        $consulta = $this->conexion->prepare($sql);
        $consulta->execute();
        return $consulta->fetchAll();
    }

    public function obtenerPorId($idAsociado)
    {
        $consulta = $this->conexion->prepare("SELECT idAsociado, documento, rol FROM asociados WHERE idAsociado = :idAsociado LIMIT 1");
        $consulta->execute([':idAsociado' => $idAsociado]);
        return $consulta->fetch() ?: null;
    }

    public function emailUsadoPorOtro($email, $idAsociado)
    {
        $consulta = $this->conexion->prepare("SELECT 1 FROM asociados WHERE email = :email AND idAsociado <> :idAsociado LIMIT 1");
        $consulta->execute([':email' => $email, ':idAsociado' => $idAsociado]);
        return (bool)$consulta->fetchColumn();
    }

    // El documento no se edita: es la identidad que usa la sesión y los préstamos se asocian al id
    public function editar($idAsociado, $datos)
    {
        $sql = "UPDATE asociados SET nombres = :nombres, apellidos = :apellidos, fechaNacimiento = :fechaNacimiento,
                    genero = :genero, direccionResidencia = :direccionResidencia, ciudad = :ciudad,
                    telefono = :telefono, email = :email, rol = :rol";
        $parametros = [
            ':nombres' => $datos['nombres'],
            ':apellidos' => $datos['apellidos'],
            ':fechaNacimiento' => $datos['fechaNacimiento'],
            ':genero' => $datos['genero'],
            ':direccionResidencia' => $datos['direccionResidencia'],
            ':ciudad' => $datos['ciudad'],
            ':telefono' => $datos['telefono'],
            ':email' => $datos['email'],
            ':rol' => $datos['rol'],
            ':idAsociado' => $idAsociado
        ];
        // La contraseña solo cambia si el admin escribió una nueva
        if (isset($datos['passwordHash'])) {
            $sql .= ", passwordHash = :passwordHash";
            $parametros[':passwordHash'] = $datos['passwordHash'];
        }
        $sql .= " WHERE idAsociado = :idAsociado";

        $consulta = $this->conexion->prepare($sql);
        return $consulta->execute($parametros);
    }

    // Al eliminar un asociado, la base de datos borra en cascada sus préstamos, abonos y solicitudes
    public function eliminar($idAsociado)
    {
        $consulta = $this->conexion->prepare("DELETE FROM asociados WHERE idAsociado = :idAsociado");
        $consulta->execute([':idAsociado' => $idAsociado]);
        return $consulta->rowCount() > 0;
    }
}
