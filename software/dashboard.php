<?php
session_start();
// Si no hay sesión, mandar al login (descomenta si ya tienes login)
// if (!isset($_SESSION['user_id'])) header("Location: index.php");

require 'funciones/funciones.php';

// KPI: Conteo de Estados
// Aseguramos que $conn existe desde funciones.php
if (isset($conn)) {
    $sqlKpi = "SELECT ESTADO_ACTUAL, COUNT(*) as C FROM VEHICULOS GROUP BY ESTADO_ACTUAL";
    $kpis = $conn->query($sqlKpi)->fetchAll(PDO::FETCH_KEY_PAIR);
    
    // Si tienes la función de alertas, la usamos; si no, array vacío para que no falle
    $alertas = function_exists('obtenerAlertas') ? obtenerAlertas() : [];
} else {
    $kpis = [];
    $alertas = [];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <title>Dashboard SIGSAK</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .card-shortcut { transition: transform 0.2s; cursor: pointer; }
        .card-shortcut:hover { transform: scale(1.03); }
    </style>
</head>
<body class="bg-light">
    
    <nav class="navbar navbar-dark bg-dark mb-4 px-3">
        <a class="navbar-brand" href="#">🏁 SIGSAK - Gestión Kovacs</a>
        <div class="text-white">
            Hola, <?php echo $_SESSION['user_name'] ?? 'Usuario'; ?> | <a href="logout.php" class="text-white">Salir</a>
        </div>
    </nav>

    <div class="container-fluid px-4">
        <div class="row">
            
            <div class="col-md-3 col-lg-2">
                <div class="list-group shadow-sm mb-4">
                    <a href="dashboard.php" class="list-group-item list-group-item-action active fw-bold"> Dashboard</a>
                    <a href="registrar_siniestro.php" class="list-group-item list-group-item-action"> Nuevo Siniestro</a>
                    <a href="taller.php" class="list-group-item list-group-item-action"> Gestión Taller (Kanban)</a>
                    <a href="ver_historiales.php" class="list-group-item list-group-item-action">Historial Vehiculos</a>
                    <a href="repuestos.php" class="list-group-item list-group-item-action">Repuestos</a>
                </div>
            </div>

            <div class="col-md-9 col-lg-10">
                
                <?php if(!empty($alertas)): ?>
                    <div class="alert alert-warning shadow-sm border-warning">
                        <h5 class="alert-heading">⚠️ Alertas de Retraso</h5>
                        <ul class="mb-0"><?php foreach($alertas as $a) echo "<li>$a</li>"; ?></ul>
                    </div>
                <?php endif; ?>

                <div class="card mb-4 shadow-sm">
                    <div class="card-header bg-white fw-bold">Estado del Taller (RF-14)</div>
                    <div class="card-body">
                        <canvas id="miGrafico" style="max-height: 250px;"></canvas>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="card card-shortcut border-danger h-100" onclick="window.location='repuestos_castigados.php'">
                            <div class="card-body d-flex align-items-center">
                                <div class="display-4 me-3">🗑️</div>
                                <div>
                                    <h5 class="card-title text-danger">Repuestos Castigados</h5>
                                    <p class="card-text small text-muted">Gestionar bajas por inactividad (+24 meses).</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card card-shortcut border-success h-100" onclick="window.location='reporte_valorizacion.php'">
                            <div class="card-body d-flex align-items-center">
                                <div class="display-4 me-3">💰</div>
                                <div>
                                    <h5 class="card-title text-success">Valorización Inventario</h5>
                                    <p class="card-text small text-muted">Ver reporte financiero por órdenes de trabajo.</p>
                                </div>
                            </div>
                        </div>
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
                    label: '# Vehículos en esta etapa',
                    data: <?php echo json_encode(array_values($kpis)); ?>,
                    backgroundColor: [
                        'rgba(255, 99, 132, 0.6)',
                        'rgba(54, 162, 235, 0.6)',
                        'rgba(255, 206, 86, 0.6)',
                        'rgba(75, 192, 192, 0.6)',
                        'rgba(153, 102, 255, 0.6)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: { beginAtZero: true, stepSize: 1 }
                }
            }
        });
    </script>
</body>
</html>