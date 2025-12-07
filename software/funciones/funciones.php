<?php
require __DIR__ . '/../config/conexion.php';

// RF-19: Cambiar estado y guardar historial
function cambiarEstadoVehiculo($id_vehiculo, $nuevo_estado, $id_usuario) {
    global $conn;
    
    // Obtener estado anterior
    $stmt = $conn->prepare("SELECT ESTADO_ACTUAL FROM VEHICULOS WHERE ID_VEHICULO = :id");
    $stmt->execute([':id' => $id_vehiculo]);
    $ant = $stmt->fetchColumn();

    if($ant != $nuevo_estado) {
        // Actualizar vehículo
        $sql = "UPDATE VEHICULOS SET ESTADO_ACTUAL = :nuevo, FECHA_ULTIMO_CAMBIO = SYSTIMESTAMP WHERE ID_VEHICULO = :id";
        $conn->prepare($sql)->execute([':nuevo' => $nuevo_estado, ':id' => $id_vehiculo]);

        // Insertar Historial
        $sqlH = "INSERT INTO HISTORIAL_VEHICULOS (ID_VEHICULO, ESTADO_ANTERIOR, ESTADO_NUEVO, ID_USUARIO) VALUES (:id, :ant, :nue, :usr)";
        $conn->prepare($sqlH)->execute([':id' => $id_vehiculo, ':ant' => $ant, ':nue' => $nuevo_estado, ':usr' => $id_usuario]);
    }
}
function cambiarUbicacionVehiculo($id_vehiculo, $nuevo_estado) {
    global $conn;
    
        $sql = "UPDATE VEHICULOS SET UBICACION = :nuevo WHERE ID_VEHICULO = :id";
        $conn->prepare($sql)->execute([':nuevo' => $nuevo_estado, ':id' => $id_vehiculo]);

}


// RF-13: Alertas de tiempo
function obtenerAlertas() {
    global $conn;
    $alertas = [];
    // Ejemplo: Alerta si lleva más de 24 horas en el mismo estado
    $sql = "SELECT PATENTE, ESTADO_ACTUAL, 
            EXTRACT(DAY FROM (SYSTIMESTAMP - FECHA_ULTIMO_CAMBIO))*24 + EXTRACT(HOUR FROM (SYSTIMESTAMP - FECHA_ULTIMO_CAMBIO)) as HORAS 
            FROM VEHICULOS WHERE ESTADO_ACTUAL NOT IN ('ENTREGADO')";
    
    $stmt = $conn->query($sql);
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if($row['HORAS'] > 48) { // Umbral de 48 horas
            $alertas[] = "Vehículo {$row['PATENTE']} estancado en {$row['ESTADO_ACTUAL']} por {$row['HORAS']} horas.";
        }
    }
    return $alertas;
}
// RF-19: Registrar movimientos de repuestos
function registrarHistorialVehiculo($id_vehiculo, $estado_ant, $estado_nue, $id_usuario) {
    global $conn;
    try {
        $sql = "INSERT INTO HISTORIAL_VEHICULOS (ID_VEHICULO, ESTADO_ANTERIOR, ESTADO_NUEVO, ID_USUARIO, FECHA_CAMBIO) 
                VALUES (:id, :ant, :nue, :usr, SYSDATE)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([':id'=>$id_vehiculo, ':ant'=>$estado_ant, ':nue'=>$estado_nue, ':usr'=>$id_usuario]);
    } catch (PDOException $e) {
        // Silencioso o log de error
    }
}

// RF-20: Registrar movimientos de repuestos
function registrarHistorialRepuesto($id_repuesto, $accion, $id_usuario) {
    global $conn;
    try {
        $sql = "INSERT INTO HISTORIAL_REPUESTOS (ID_REPUESTO, ACCION, ID_USUARIO, FECHA_MOVIMIENTO) 
                VALUES (:id, :acc, :usr, SYSDATE)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([':id'=>$id_repuesto, ':acc'=>$accion, ':usr'=>$id_usuario]);
    } catch (PDOException $e) 
}


?>
