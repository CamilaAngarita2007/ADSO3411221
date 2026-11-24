<?php

require_once __DIR__ . '/../Config/conexion.php';

class Prestamos
{
    private $conexion;

    public function __construct()
    {
        $conexiondb = new Conexion();
        $this->conexion = $conexiondb->conectar();
    }

    public function obtenerIdAsociado($documento)
    {
        $consulta = $this->conexion->prepare("SELECT idAsociado FROM asociados WHERE documento = :documento LIMIT 1");
        $consulta->execute([':documento' => $documento]);
        $idAsociado = $consulta->fetchColumn();
        return $idAsociado === false ? null : (int)$idAsociado;
    }

    public function obtenerTodos()
    {
        $sql = "SELECT p.idPrestamo, p.idAsociado, p.valorPrestamo, p.tasaInteres, p.numeroCuotas, p.fechaPrestamo,
                       CONCAT(a.nombres, ' ', a.apellidos) AS nombreAsociado
                FROM prestamos p
                INNER JOIN asociados a ON a.idAsociado = p.idAsociado
                ORDER BY p.fechaPrestamo DESC";
        $consulta = $this->conexion->prepare($sql);
        $consulta->execute();
        return $consulta->fetchAll();
    }

    public function obtenerPorAsociado($idAsociado)
    {
        $sql = "SELECT p.idPrestamo, p.idAsociado, p.valorPrestamo, p.tasaInteres, p.numeroCuotas, p.fechaPrestamo,
                       CONCAT(a.nombres, ' ', a.apellidos) AS nombreAsociado
                FROM prestamos p
                INNER JOIN asociados a ON a.idAsociado = p.idAsociado
                WHERE p.idAsociado = :idAsociado
                ORDER BY p.fechaPrestamo DESC";
        $consulta = $this->conexion->prepare($sql);
        $consulta->execute([':idAsociado' => $idAsociado]);
        return $consulta->fetchAll();
    }

    public function guardar($idAsociado, $datos)
    {
        $sql = "INSERT INTO prestamos (valorPrestamo, tasaInteres, numeroCuotas, fechaPrestamo, idAsociado)
                VALUES (:valorPrestamo, :tasaInteres, :numeroCuotas, :fechaPrestamo, :idAsociado)";
        $consulta = $this->conexion->prepare($sql);
        return $consulta->execute([
            ':valorPrestamo' => $datos['valorPrestamo'],
            ':tasaInteres' => $datos['tasaInteres'],
            ':numeroCuotas' => $datos['numeroCuotas'],
            ':fechaPrestamo' => $datos['fechaPrestamo'],
            ':idAsociado' => $idAsociado
        ]);
    }

    public function eliminar($idPrestamo)
    {
        $sql = "DELETE FROM prestamos WHERE idPrestamo = :idPrestamo";
        $consulta = $this->conexion->prepare($sql);
        $consulta->execute([':idPrestamo' => $idPrestamo]);
        return $consulta->rowCount() > 0;
    }

    // Solicitudes pendientes (no están en prestamos hasta que el admin las acepte)
    public function solicitar($idAsociado, $datos)
    {
        $sql = "INSERT INTO solicitudes_prestamo (valorPrestamo, tasaInteres, numeroCuotas, fechaPrestamo, idAsociado)
                VALUES (:valorPrestamo, :tasaInteres, :numeroCuotas, :fechaPrestamo, :idAsociado)";
        $consulta = $this->conexion->prepare($sql);
        return $consulta->execute([
            ':valorPrestamo' => $datos['valorPrestamo'],
            ':tasaInteres' => $datos['tasaInteres'],
            ':numeroCuotas' => $datos['numeroCuotas'],
            ':fechaPrestamo' => $datos['fechaPrestamo'],
            ':idAsociado' => $idAsociado
        ]);
    }

    public function obtenerSolicitudes()
    {
        $sql = "SELECT s.idSolicitud, s.idAsociado, s.valorPrestamo, s.tasaInteres, s.numeroCuotas, s.fechaPrestamo,
                       CONCAT(a.nombres, ' ', a.apellidos) AS nombreAsociado
                FROM solicitudes_prestamo s
                INNER JOIN asociados a ON a.idAsociado = s.idAsociado
                ORDER BY s.idSolicitud ASC";
        $consulta = $this->conexion->prepare($sql);
        $consulta->execute();
        return $consulta->fetchAll();
    }

    // Acepta la solicitud: la copia a prestamos y la borra de solicitudes, todo o nada
    public function aceptarSolicitud($idSolicitud)
    {
        $this->conexion->beginTransaction();
        try {
            $seleccion = $this->conexion->prepare("SELECT valorPrestamo, tasaInteres, numeroCuotas, fechaPrestamo, idAsociado FROM solicitudes_prestamo WHERE idSolicitud = :idSolicitud FOR UPDATE");
            $seleccion->execute([':idSolicitud' => $idSolicitud]);
            $solicitud = $seleccion->fetch();
            if (!$solicitud) {
                $this->conexion->rollBack();
                return false;
            }

            $insercion = $this->conexion->prepare("INSERT INTO prestamos (valorPrestamo, tasaInteres, numeroCuotas, fechaPrestamo, idAsociado)
                VALUES (:valorPrestamo, :tasaInteres, :numeroCuotas, :fechaPrestamo, :idAsociado)");
            $insercion->execute([
                ':valorPrestamo' => $solicitud['valorPrestamo'],
                ':tasaInteres' => $solicitud['tasaInteres'],
                ':numeroCuotas' => $solicitud['numeroCuotas'],
                ':fechaPrestamo' => $solicitud['fechaPrestamo'],
                ':idAsociado' => $solicitud['idAsociado']
            ]);

            $borrado = $this->conexion->prepare("DELETE FROM solicitudes_prestamo WHERE idSolicitud = :idSolicitud");
            $borrado->execute([':idSolicitud' => $idSolicitud]);

            $this->conexion->commit();
            return true;
        } catch (Throwable $e) {
            if ($this->conexion->inTransaction()) {
                $this->conexion->rollBack();
            }
            throw $e;
        }
    }

    // Rechaza la solicitud: se elimina sin guardarla en prestamos
    public function rechazarSolicitud($idSolicitud)
    {
        $consulta = $this->conexion->prepare("DELETE FROM solicitudes_prestamo WHERE idSolicitud = :idSolicitud");
        $consulta->execute([':idSolicitud' => $idSolicitud]);
        return $consulta->rowCount() > 0;
    }

    public function editar($idPrestamo, $datos)
    {
        $sql = "UPDATE prestamos SET valorPrestamo = :valorPrestamo, tasaInteres = :tasaInteres, numeroCuotas = :numeroCuotas, fechaPrestamo = :fechaPrestamo WHERE idPrestamo = :idPrestamo";
        $consulta = $this->conexion->prepare($sql);
        $consulta->execute([
            ':valorPrestamo' => $datos['valorPrestamo'],
            ':tasaInteres' => $datos['tasaInteres'],
            ':numeroCuotas' => $datos['numeroCuotas'],
            ':fechaPrestamo' => $datos['fechaPrestamo'],
            ':idPrestamo' => $idPrestamo
        ]);
        if ($consulta->rowCount() > 0) {
            return true; // Actualización exitosa
        }

        // Si no cambió ninguna fila, verificar si el registro existe
        $verificar = $this->conexion->prepare("SELECT 1 FROM prestamos WHERE idPrestamo = :idPrestamo");
        $verificar->execute([':idPrestamo' => $idPrestamo]);

        return (bool)$verificar->fetchColumn();
    }
}
