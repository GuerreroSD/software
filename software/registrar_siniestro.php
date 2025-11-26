<?php
session_start();
require 'config/conexion.php';
$msg = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        $sql = "INSERT INTO VEHICULOS (RUT_CLIENTE, NOMBRE_CLIENTE, TIPO_CLIENTE, VIN, PATENTE, MARCA_MODELO) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            $_POST['rut'], $_POST['nombre'], $_POST['tipo'], 
            $_POST['vin'], $_POST['patente'], $_POST['marca']
        ]);
        $msg = "<div class='alert alert-success'>Vehículo registrado.</div>";
    } catch (PDOException $e) {
        $msg = "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
    }
}
?>
<!DOCTYPE html>
<html>
<head><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="p-4">
    <div class="container">
        <a href="dashboard.php" class="btn btn-outline-secondary mb-3">&larr; Volver</a>
        <h3>Registrar Vehículo (RF-2)</h3>
        <?php echo $msg; ?>
        <form method="POST" class="row g-3">
            <div class="col-md-6"><label>RUT</label><input type="text" name="rut" class="form-control" required></div>
            <div class="col-md-6"><label>Nombre</label><input type="text" name="nombre" class="form-control" required></div>
            <div class="col-md-6"><label>VIN</label><input type="text" name="vin" class="form-control" required></div>
            <div class="col-md-6"><label>Patente</label><input type="text" name="patente" class="form-control" required></div>
            <div class="col-md-6"><label>Marca/Modelo</label><input type="text" name="marca" class="form-control"></div>
            <div class="col-md-6"><label>Tipo</label>
                <select name="tipo" class="form-select">
                    <option value="SEGURO">Compañía de Seguros</option>
                    <option value="PARTICULAR">Particular</option>
                </select>
            </div>
            <button class="btn btn-primary mt-3">Guardar</button>
        </form>
    </div>
</body>
</html>