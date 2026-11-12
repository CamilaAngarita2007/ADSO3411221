<?php

class Actividad {

    private $conexion;

    public function __construct($conexion) {
        $conexionDB = new conexion();
        $this->conexion = $conexionDB->conectar();
    }
    public function obtenerActividad() {
        $sql = "SELECT * FROM actividades order by fechaevento asc";
        $consulta = $this->conexion->prepare($sql);
        $consulta->execute();
        return $consulta->fetchAll();
    }

    public function guardar(){
        $sql ="INSERT INTO actividades (nombreActividad, fechaEvento, municipio) VALUES (:nombreActividad, :fechaEvento, :municipio)";
        $consulta = $this->conexion->prepare($sql);
        return $consulta->execute([
            ":nombreActividad" => $_POST['nombreActividad'],
            ":fechaEvento" => $_POST['fechaEvento'],   
            ":municipio" => $_POST['municipio'] 
        ]);
    }

    public function eliminar($idActividad) {
        $sql = "DELETE FROM actividades WHERE idActividad = :idActividad";
        $consulta = $this->conexion->prepare($sql);
        $consulta->execute([':idActividad' => $idActividad]);
        return $consulta->rowCount() > 0; // Devuelve true si se eliminó al menos una fila, false en caso contrario
    }
    public function editar($datos){
        $sql="UPDATE actividades SET nombreActividad = :nombreActividad, fechaEvento = :fechaEvento, municipio = :municipio, totalRecaudo = :totalRecaudo WHERE idActividad = :idActividad";
        $consulta = $this->conexion->prepare($sql);
        return $consulta->execute([
            ":nombreActividad" => $datos['nombreActividad'],
            ":fechaEvento" => $datos['fechaEvento'],
            ":municipio" => $datos['municipio'],
            ":totalRecaudo" => $datos['totalRecaudo'],
            ":idActividad" => $datos['idActividad']
        ]);

        if($consulta->rowCount() > 0){
            return true; // Se actualizó al menos una fila
        }

        $verificar = $this->conexion->prepare("SELECT 1 FROM actividades WHERE idActividad = :idActividad");
        $verificar->execute([':idActividad' => $datos['idActividad']]);
        return(bool)$verificar->fetchColumn(); // Devuelve true si el registro existe, false en caso contrario
        
    }
}