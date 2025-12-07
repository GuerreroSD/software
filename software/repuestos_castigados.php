<?php
session_start();
require 'funciones/funciones.php'; // Asegúrate de incluir tu conexión $conn
// Procesar el "Castigo"
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['castigar_id'])) {
    $id_rep = $_POST['castigar_id'];
    
    // 1. Marcar como castigado en DB
    $sql = "UPDATE REPUESTOS SET ESTADO = 'CASTIGADO', FECHA_ULTIMO_MOVIMIENTO = SYSDATE WHERE ID_REPUESTO = :id";
    $stmt = $conn->prepare($sql);
    $stmt->execute([':id' => $id_rep]);

    // 2. Guardar en Historial (RF-20)
    registrarHistorialRepuesto($id_rep, 'CASTIGADO POR INACTIVIDAD', $_SESSION['user_id']);
    
    echo "<script>alert('Repuesto marcado como CASTIGADO (Pérdida).');</script>";
}

// Consulta RF-17: Traer repuestos activos SIN movimiento en 24 meses
$sql = "SELECT * FROM REPUESTOS 
        WHERE FECHA_ULTIMO_MOVIMIENTO < ADD_MONTHS(SYSDATE, -24) 
        AND ESTADO = 'ACTIVO'";
$castigables = $conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="p-5"> 
    <div class="d-flex justify-content-between mb-4">
    <h3>RF-17: Repuestos "Hueso" (Sin movimiento > 24 Meses)</h3> 
    <a href="dashboard.php" class="btn btn-secondary">Volver</a>
    </div>
  
    <div class="alert alert-warning">
        ⚠️ Estos repuestos generan pérdida. Confirme para enviarlos a castigo.
        
    </div>

    <table class="table table-striped"> 
        
        <thead>
            <tr>
                <th>Repuesto</th>
                <th>Último Movimiento</th>
                <th>Acción</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($castigables as $r): ?>
            <tr>
                <td><?php echo htmlspecialchars($r['NOMBRE_REPUESTO']); ?></td>
                <td><?php echo htmlspecialchars($r['FECHA_ULTIMO_MOVIMIENTO']); ?></td>
                <td>
                    <form method="POST">
                        <input type="hidden" name="castigar_id" value="<?php echo $r['ID_REPUESTO']; ?>">
                        <button type="submit" class="btn btn-danger" onclick="return confirm('¿Seguro que desea castigar este repuesto como pérdida?');">
                            🗑️ Castigar (Pérdida)
                        </button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if(empty($castigables)) echo "<tr><td colspan='3'>Todo limpio. No hay repuestos antiguos.</td></tr>"; ?>
        </tbody>
    </table>
</body>

</html>
