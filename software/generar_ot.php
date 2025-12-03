<?php
session_start();
require 'config/conexion.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id_vehiculo'])) {
    $id_vehiculo = $_POST['id_vehiculo'];
    
    try {
        // 1. Calcular el total del presupuesto actual
        $stmt = $conn->prepare("SELECT SUM(COSTO * CANTIDAD) FROM DETALLE_PRESUPUESTO WHERE ID_VEHICULO = :id");
        $stmt->execute([':id' => $id_vehiculo]);
        $total = $stmt->fetchColumn();

        // 2. Crear la Orden de Trabajo (RF-6)
        $sql = "INSERT INTO ORDENES_TRABAJO (ID_VEHICULO, TOTAL_APROBADO) VALUES (:id, :total)";
        $conn->prepare($sql)->execute([':id' => $id_vehiculo, ':total' => $total]);

        // 3. Cambiar estado del vehículo a "EN REPARACION" o similar
        $sqlUpd = "UPDATE VEHICULOS SET ESTADO_ACTUAL_OT = 'APROBADA' WHERE ID_VEHICULO = :id";
        $conn->prepare($sqlUpd)->execute([':id' => $id_vehiculo]);

        // Redirigir con éxito
        echo "<script>
                alert('¡Orden de Trabajo Generada (RF-6)! El vehículo ahora puede iniciar reparaciones.');
                window.location.href = 'taller.php';
              </script>";
    } catch (PDOException $e) {
        die("Error al generar OT: " . $e->getMessage());
    }
} else {
    header("Location: taller.php");
}
?>