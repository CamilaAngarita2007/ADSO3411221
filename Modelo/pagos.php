<?php

require_once __DIR__ . '/../Config/conexion.php';

// El valor del préstamo es la deuda actual: cada abono se resta de valorPrestamo.
// pagos_cuotas guarda el historial de abonos para que el admin pueda editarlos o eliminarlos.
class Pagos
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

    // Préstamos del asociado que todavía tienen deuda (valorPrestamo mayor a cero)
    public function obtenerPrestamosConDeuda($idAsociado)
    {
        $sql = "SELECT idPrestamo, valorPrestamo, tasaInteres, numeroCuotas, fechaPrestamo
                FROM prestamos
                WHERE idAsociado = :idAsociado AND valorPrestamo > 0
                ORDER BY fechaPrestamo DESC";
        $consulta = $this->conexion->prepare($sql);
        $consulta->execute([':idAsociado' => $idAsociado]);
        return $consulta->fetchAll();
    }

    // Lista de todos los abonos (vista del administrador)
    public function obtenerTodos()
    {
        $sql = "SELECT pc.idPago, pc.idPrestamo, pc.valorCuota, pc.fechaPago,
                       CONCAT(a.nombres, ' ', a.apellidos) AS nombreAsociado
                FROM pagos_cuotas pc
                INNER JOIN prestamos p ON p.idPrestamo = pc.idPrestamo
                INNER JOIN asociados a ON a.idAsociado = p.idAsociado
                ORDER BY pc.fechaPago DESC, pc.idPago DESC";
        $consulta = $this->conexion->prepare($sql);
        $consulta->execute();
        return $consulta->fetchAll();
    }

    // Registra un abono y resta el valor de la deuda. Devuelve un código de estado.
    public function abonar($idPrestamo, $idAsociado, $valor)
    {
        $this->conexion->beginTransaction();
        try {
            $seleccion = $this->conexion->prepare("SELECT valorPrestamo FROM prestamos WHERE idPrestamo = :idPrestamo AND idAsociado = :idAsociado FOR UPDATE");
            $seleccion->execute([':idPrestamo' => $idPrestamo, ':idAsociado' => $idAsociado]);
            $fila = $seleccion->fetch();
            if (!$fila) {
                $this->conexion->rollBack();
                return ['estado' => 'no_existe'];
            }
            $deuda = round((float)$fila['valorPrestamo'], 2);
            if ($deuda <= 0) {
                $this->conexion->rollBack();
                return ['estado' => 'sin_deuda'];
            }
            if ($valor > $deuda) {
                $this->conexion->rollBack();
                return ['estado' => 'excede', 'deuda' => $deuda];
            }

            $insercion = $this->conexion->prepare("INSERT INTO pagos_cuotas (valorCuota, fechaPago, idPrestamo) VALUES (:valorCuota, CURDATE(), :idPrestamo)");
            $insercion->execute([':valorCuota' => $valor, ':idPrestamo' => $idPrestamo]);

            $actualizacion = $this->conexion->prepare("UPDATE prestamos SET valorPrestamo = valorPrestamo - :valor WHERE idPrestamo = :idPrestamo");
            $actualizacion->execute([':valor' => $valor, ':idPrestamo' => $idPrestamo]);

            $this->conexion->commit();
            return ['estado' => 'ok'];
        } catch (Throwable $e) {
            if ($this->conexion->inTransaction()) {
                $this->conexion->rollBack();
            }
            throw $e;
        }
    }

    // Corrige un abono: ajusta la deuda por la diferencia entre el valor anterior y el nuevo
    public function editarAbono($idPago, $nuevoValor, $fechaPago)
    {
        $this->conexion->beginTransaction();
        try {
            $seleccion = $this->conexion->prepare("SELECT pc.valorCuota, pc.idPrestamo, p.valorPrestamo
                FROM pagos_cuotas pc
                INNER JOIN prestamos p ON p.idPrestamo = pc.idPrestamo
                WHERE pc.idPago = :idPago FOR UPDATE");
            $seleccion->execute([':idPago' => $idPago]);
            $fila = $seleccion->fetch();
            if (!$fila) {
                $this->conexion->rollBack();
                return ['estado' => 'no_existe'];
            }
            $valorAnterior = round((float)$fila['valorCuota'], 2);
            // La deuda disponible es la actual más lo que este abono ya había descontado
            $disponible = round((float)$fila['valorPrestamo'] + $valorAnterior, 2);
            if ($nuevoValor > $disponible) {
                $this->conexion->rollBack();
                return ['estado' => 'excede', 'deuda' => $disponible];
            }

            $actualizacionPrestamo = $this->conexion->prepare("UPDATE prestamos SET valorPrestamo = valorPrestamo + :anterior - :nuevo WHERE idPrestamo = :idPrestamo");
            $actualizacionPrestamo->execute([
                ':anterior' => $valorAnterior,
                ':nuevo' => $nuevoValor,
                ':idPrestamo' => $fila['idPrestamo']
            ]);

            $actualizacionPago = $this->conexion->prepare("UPDATE pagos_cuotas SET valorCuota = :valorCuota, fechaPago = :fechaPago WHERE idPago = :idPago");
            $actualizacionPago->execute([':valorCuota' => $nuevoValor, ':fechaPago' => $fechaPago, ':idPago' => $idPago]);

            $this->conexion->commit();
            return ['estado' => 'ok'];
        } catch (Throwable $e) {
            if ($this->conexion->inTransaction()) {
                $this->conexion->rollBack();
            }
            throw $e;
        }
    }

    // Elimina un abono y devuelve su valor a la deuda del préstamo
    public function eliminarAbono($idPago)
    {
        $this->conexion->beginTransaction();
        try {
            $seleccion = $this->conexion->prepare("SELECT pc.valorCuota, pc.idPrestamo
                FROM pagos_cuotas pc
                INNER JOIN prestamos p ON p.idPrestamo = pc.idPrestamo
                WHERE pc.idPago = :idPago FOR UPDATE");
            $seleccion->execute([':idPago' => $idPago]);
            $fila = $seleccion->fetch();
            if (!$fila) {
                $this->conexion->rollBack();
                return false;
            }

            $actualizacion = $this->conexion->prepare("UPDATE prestamos SET valorPrestamo = valorPrestamo + :valor WHERE idPrestamo = :idPrestamo");
            $actualizacion->execute([':valor' => $fila['valorCuota'], ':idPrestamo' => $fila['idPrestamo']]);

            $borrado = $this->conexion->prepare("DELETE FROM pagos_cuotas WHERE idPago = :idPago");
            $borrado->execute([':idPago' => $idPago]);

            $this->conexion->commit();
            return true;
        } catch (Throwable $e) {
            if ($this->conexion->inTransaction()) {
                $this->conexion->rollBack();
            }
            throw $e;
        }
    }
}
