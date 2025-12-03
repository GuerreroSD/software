<?php
session_start();
if (!isset($_SESSION['user_id'])) header("Location: index.php");
require 'funciones/funciones.php';

// KPI: Conteo de Estados
$sqlKpi = "SELECT ESTADO_ACTUAL, COUNT(*) as C FROM VEHICULOS GROUP BY ESTADO_ACTUAL";
$kpis = $conn->query($sqlKpi)->fetchAll(PDO::FETCH_KEY_PAIR);
$alertas = obtenerAlertas();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <title>Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <nav class="navbar navbar-dark bg-dark mb-4 px-3">
        <a class="navbar-brand" href="#">SIGSAK</a>
        <div class="text-white">
            Hola, <?php echo $_SESSION['user_name']; ?> | <a href="logout.php" class="text-white">Salir</a>
        </div>
    </nav>
    <div class="container">
        <div class="row">
            <div class="col-md-3">
                <div class="list-group">
                    <a href="dashboard.php" class="list-group-item active">Dashboard</a>
                    <a href="registrar_siniestro.php" class="list-group-item">Nuevo Siniestro</a>
                    <a href="taller.php" class="list-group-item">Gestión Taller</a>
                    <a href="repuestos.php" class="list-group-item">Gestión de Repuestos</a>
                </div>
            </div>
            <div class="col-md-9">
                <?php if(!empty($alertas)): ?>
                    <div class="alert alert-warning">
                        <h5>Alertas de Retraso</h5>
                        <ul><?php foreach($alertas as $a) echo "<li>$a</li>"; ?></ul>
                    </div>
                <?php endif; ?>

                <div class="card mb-4">
                    <div class="card-header">Estado del Taller (RF-14)</div>
                    <div class="card-body">
                        <canvas id="miGrafico" style="max-height: 300px;"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        const ctx = document.getElementById('miGrafico');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_keys($kpis)); ?>,
                datasets: [{
                    label: '# Vehículos',
                    data: <?php echo json_encode(array_values($kpis)); ?>,
                    backgroundColor: 'rgba(54, 162, 235, 0.6)'
                }]
            }
        });
    </script>
</body>
</html>