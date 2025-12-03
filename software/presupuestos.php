<?php
session_start();
require 'config/conexion.php';
if (!isset($_SESSION['user_id'])) header("Location: index.php");

$id_vehiculo = $_GET['id'] ?? 0;
$msg = "";

// Agregar ítem al presupuesto (RF-4)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['agregar_item'])) {
    $sql = "INSERT INTO DETALLE_PRESUPUESTO (ID_VEHICULO, TIPO, DESCRIPCION, COSTO, CANTIDAD) VALUES (:id, :tipo, :descripcion, :costo, :cant)";
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':id' => $id_vehiculo,
        ':tipo' => $_POST['tipo'],
        ':descripcion' => $_POST['descripcion'],
        ':costo' => $_POST['costo'],
        ':cant' => $_POST['cantidad']
    ]);
}

// Obtener vehículo e ítems
$vehiculo = $conn->query("SELECT * FROM VEHICULOS WHERE ID_VEHICULO = $id_vehiculo")->fetch(PDO::FETCH_ASSOC);
$items = $conn->query("SELECT * FROM DETALLE_PRESUPUESTO WHERE ID_VEHICULO = $id_vehiculo ORDER BY TIPO")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <title>Presupuesto - SIGSAK</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light p-4">
    <div class="container bg-white p-4 rounded shadow">
        <div class="d-flex justify-content-between">
            <h3>Presupuesto: <?php echo $vehiculo['MARCA_MODELO'] ?? 'Vehículo no encontrado'; ?></h3>
            <a href="taller.php" class="btn btn-secondary">Volver</a>
        </div>
        <p>Patente: <strong><?php echo $vehiculo['PATENTE']; ?></strong></p>

        <div class="card mb-4">
            <div class="card-header bg-dark text-white">Agregar Ítem (Mano de Obra / Repuesto)</div>
            <div class="card-body">
                <form method="POST" class="row g-3">
                    <input type="hidden" name="agregar_item" value="1">
                    <div class="col-md-3">
                        <select name="tipo" class="form-select">
                            <option value="MANO_OBRA">Mano de Obra</option>
                            <option value="REPUESTO">Repuesto</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <input type="text" name="descripcion" class="form-control" placeholder="Descripción (Ej: Desabollado puerta)" required>
                    </div>
                    <div class="col-md-2">
                        <input type="number" name="cantidad" class="form-control" value="1" placeholder="Cant">
                    </div>
                    <div class="col-md-2">
                        <input type="number" name="costo" class="form-control" placeholder="Costo Unitario" required>
                    </div>
                    <div class="col-md-1">
                        <button type="submit" class="btn btn-success">+</button>
                    </div>
                </form>
            </div>
        </div>

        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Tipo</th>
                    <th>Descripción</th>
                    <th>Cant.</th>
                    <th>Costo Unit.</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $grand_total = 0;
                foreach($items as $i): 
                    $total = $i['COSTO'] * $i['CANTIDAD'];
                    $grand_total += $total;
                ?>
                <tr>
                    <td><?php echo $i['TIPO']; ?></td>
                    <td><?php echo $i['DESCRIPCION']; ?></td>
                    <td><?php echo $i['CANTIDAD']; ?></td>
                    <td>$<?php echo number_format($i['COSTO']); ?></td>
                    <td>$<?php echo number_format($total); ?></td>
                </tr>
                <?php endforeach; ?>
                <tr class="table-dark">
                    <td colspan="4" class="text-end"><strong>TOTAL PRESUPUESTO</strong></td>
                    <td><strong>$<?php echo number_format($grand_total); ?></strong></td>
                </tr>
            </tbody>
        </table>

        <?php if($grand_total > 0): ?>
            <div class="alert alert-warning mt-3">
                <form method="POST" action="generar_ot.php">
                    <input type="hidden" name="id_vehiculo" value="<?php echo $id_vehiculo; ?>">
                    <label><strong>Aprobar Presupuesto (RF-6):</strong> Se generará la Orden de Trabajo.</label><br>
                    <button type="button" class="btn btn-primary mt-2" onclick="alert('Funcionalidad RF-5: PDF Generado (Simulación)')">Imprimir PDF (RF-5)</button>
                    <button type="submit" class="btn btn-danger mt-2">Aprobar y Crear OT</button>
                </form>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>