<?php
// guardar_mecanico.php

require 'config/conexion.php';

// --- BLOQUE PARA GUARDAR EL MECÁNICO (RF-11) ---
if (isset($_POST['id_mecanico']) && !empty($_POST['id_mecanico'])) {
    
    $id_vehiculo = $_POST['id'];
    $id_mecanico = $_POST['id_mecanico'];

    try {
        $sqlAsignacion = "INSERT INTO ASIGNACIONES (ID_VEHICULO, ID_MECANICO, FECHA_ASIGNACION) 
                          VALUES (:vehiculo, :mecanico, SYSDATE)";
        
        $stmt = $conn->prepare($sqlAsignacion);
        
        // Vinculamos los parámetros (Evita inyección SQL y errores de tipos)
        $stmt->bindParam(':vehiculo', $id_vehiculo);
        $stmt->bindParam(':mecanico', $id_mecanico);
        
        // Ejecutamos
        if ($stmt->execute()) {
            echo "<script>
                    alert('✅ Mecánico asignado correctamente en Oracle.');
                    // Opcional: Redirigir para limpiar el POST
                    // window.location.href = window.location.href; 
                  </script>";
        } else {
            echo "<script>alert('❌ Error al guardar en Oracle.');</script>";
        }

    } catch (PDOException $e) {
        echo "<script>alert('Error Crítico Oracle: " . $e->getMessage() . "');</script>";
    }
}
?>
