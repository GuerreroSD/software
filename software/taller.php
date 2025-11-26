<?php
session_start();
require 'funciones/funciones.php';

// Procesar Cambio de Estado
if(isset($_POST['nuevo_estado'])) {
    cambiarEstadoVehiculo($_POST['id'], $_POST['nuevo_estado'], $_SESSION['user_id']);
}

// Procesar Subida de Foto (RF-16)
if(isset($_FILES['foto'])) {
    $ruta = 'uploads/' . time() . "_" . $_FILES['foto']['name'];
    move_uploaded_file($_FILES['foto']['tmp_name'], $ruta);
    $conn->prepare("INSERT INTO DOCUMENTOS (ID_VEHICULO, RUTA_ARCHIVO, SUBIDO_POR) VALUES (?, ?, ?)")
         ->execute([$_POST['id'], $ruta, $_SESSION['user_id']]);
}

$vehiculos = $conn->query("SELECT * FROM VEHICULOS")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="p-4">
    <div class="container">
        <div class="d-flex justify-content-between mb-4">
            <h3>Gestión de Taller (Kanban)</h3>
            <a href="dashboard.php" class="btn btn-secondary">Volver</a>
        </div>

        <table class="table table-bordered">
            <thead class="table-dark">
                <tr><th>Vehículo</th><th>Estado (RF-9)</th><th>Acciones</th></tr>
            </thead>
            <tbody>
                <?php foreach($vehiculos as $v): ?>
                <tr>
                    <td>
                        <strong><?php echo $v['MARCA_MODELO']; ?></strong><br>
                        <small><?php echo $v['PATENTE']; ?></small>
                    </td>
                    <td><span class="badge bg-info"><?php echo $v['ESTADO_ACTUAL']; ?></span></td>
                    <td>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="id" value="<?php echo $v['ID_VEHICULO']; ?>">
                            <select name="nuevo_estado" class="form-select form-select-sm d-inline w-auto" onchange="this.form.submit()">
                                <option>Cambiar a...</option>
                                <option value="DESARME">Desarme</option>
                                <option value="PINTURA">Pintura</option>
                                <option value="ARMADO">Armado</option>
                                <option value="LISTO">Listo</option>
                            </select>
                        </form>
                        
                        <form method="POST" enctype="multipart/form-data" class="d-inline mt-2">
                            <input type="hidden" name="id" value="<?php echo $v['ID_VEHICULO']; ?>">
                            <label class="btn btn-sm btn-outline-warning">
                                📷 <input type="file" name="foto" hidden onchange="this.form.submit()">
                            </label>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>