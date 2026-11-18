<?php

require_once __DIR__ . '/../Config/conexion.php';

class Actividades
{
    private $conexion;

    public function __construct()
    {
        $conexiondb = new Conexion();
        $this->conexion = $conexiondb->conectar();
    }

    public function obtenerActividades()
    {
        $sql = "SELECT * FROM actividades ORDER BY fechaEvento ASC";
        $consulta = $this->conexion->prepare($sql);
        $consulta->execute();
        return $consulta->fetchAll();
    }

    public function guardar($datos)
    {
        $sql = "INSERT INTO actividades (nombreActividad, fechaEvento, municipio)
                VALUES (:nombreActividad, :fechaEvento, :municipio)";
        $consulta = $this->conexion->prepare($sql);
        return $consulta->execute([
            ':nombreActividad' => $datos['nombreActividad'],
            ':fechaEvento' => $datos['fechaEvento'],
            ':municipio' => $datos['municipio']
        ]);
    }

    public function eliminar($idActividad)
    {
        $sql = "DELETE FROM actividades WHERE idActividad = :idActividad";
        $consulta = $this->conexion->prepare($sql);
        $consulta->execute([':idActividad' => $idActividad]);
        return $consulta->rowCount() > 0;
    }
    public function editar($idActividad, $datos)
    {
        $sql = "UPDATE actividades SET nombreActividad = :nombreActividad, fechaEvento = :fechaEvento, municipio = :municipio, totalRecaudado = :totalRecaudado WHERE idActividad = :idActividad";
        $consulta = $this->conexion->prepare($sql);
        $consulta->execute([
            ':nombreActividad' => $datos['nombreActividad'],
            ':fechaEvento' => $datos['fechaEvento'],
            ':municipio' => $datos['municipio'],
            ':totalRecaudado' => $datos['totalRecaudado'],
            ':idActividad' => $idActividad
        ]);
        if ($consulta->rowCount() > 0) {
            return true; // Actualización exitosa
        }

        // Si no cambió ninguna fila, verificar si el registro existe
        $verificar = $this->conexion->prepare("SELECT 1 FROM actividades WHERE idActividad = :idActividad");
        $verificar->execute([':idActividad' => $idActividad]);

        return (bool)$verificar->fetchColumn(); // true si existe, false si no
    }
}