<?php
session_start();

if (!isset($_SESSION['user_id'])) header("Location: index.php");

require 'funciones/funciones.php';

if (isset($conn)) {
    $sqlKpi = "SELECT ESTADO_ACTUAL, COUNT(*) as C FROM VEHICULOS GROUP BY ESTADO_ACTUAL";
    $kpis = $conn->query($sqlKpi)->fetchAll(PDO::FETCH_KEY_PAIR);
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
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .card-shortcut { transition: transform 0.2s; cursor: pointer; }
        .card-shortcut:hover { transform: scale(1.03); }
        /* Ajuste menú móvil */
        @media (max-width: 767.98px) { .sidebar-offcanvas { width: 75%; } }
    </style>
</head>
<body class="bg-light">
    
    <nav class="navbar navbar-dark bg-dark sticky-top px-3 shadow">
        <div class="d-flex align-items-center">
            <button class="navbar-toggler d-md-none me-2" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarMenu">
                <span class="navbar-toggler-icon"></span>
            </button>
            <a class="navbar-brand me-0" href="#">🏁 SIGSAK</a>
        </div>
        <div class="text-white d-none d-md-block">
            Hola, <?php echo $_SESSION['user_name'] ?? 'Usuario'; ?> | <a href="logout.php" class="text-white text-decoration-none">Salir</a>
        </div>
        <a href="logout.php" class="text-white d-md-none text-decoration-none">🚪</a>
    </nav>

    <div class="container-fluid">
        <div class="row">
            
            <div class="sidebar border border-right col-md-3 col-lg-2 p-0 bg-body-tertiary">
                <div class="offcanvas-md offcanvas-start bg-body-tertiary" tabindex="-1" id="sidebarMenu">
                    <div class="offcanvas-header"><h5 class="offcanvas-title">Menú</h5><button type="button" class="btn-close" data-bs-dismiss="offcanvas" data-bs-target="#sidebarMenu"></button></div>
                    <div class="offcanvas-body d-md-flex flex-column p-0 pt-lg-3 overflow-y-auto">
                        <div class="list-group list-group-flush w-100">
                            <a href="dashboard.php" class="list-group-item list-group-item-action active fw-bold"> Dashboard</a>
                            <a href="registrar_siniestro.php" class="list-group-item list-group-item-action"> Nuevo Siniestro</a>
                            <a href="taller.php" class="list-group-item list-group-item-action"> Gestión Taller (Kanban)</a>
                            <a href="ver_historiales.php" class="list-group-item list-group-item-action">Historial Vehiculos</a>
                            <a href="repuestos.php" class="list-group-item list-group-item-action">Repuestos</a>
                        </div>
                    </div>
                </div>
            </div>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                
                <?php if(!empty($alertas)): ?>
                    <div class="alert alert-warning shadow-sm border-warning">
                        <h5 class="alert-heading h6">⚠️ Alertas de Retraso</h5>
                        <ul class="mb-0 small"><?php foreach($alertas as $a) echo "<li>$a</li>"; ?></ul>
                    </div>
                <?php endif; ?>

                <div class="card mb-4 shadow-sm">
                    <div class="card-header bg-white fw-bold">Estado del Taller (RF-14)</div>
                    <div class="card-body">
                        <div style="position: relative; height:30vh; min-height: 250px;">
                            <canvas id="miGrafico"></canvas>
                        </div>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <div class="card card-shortcut border-danger h-100" onclick="window.location='repuestos_castigados.php'">
                            <div class="card-body d-flex align-items-center p-3">
                                <div class="display-4 me-3">🗑️</div>
                                <div>
                                    <h5 class="card-title text-danger mb-1">Repuestos Castigados</h5>
                                    <p class="card-text small text-muted">Gestionar bajas (+24 meses).</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="card card-shortcut border-success h-100" onclick="window.location='reporte_valorizacion.php'">
                            <div class="card-body d-flex align-items-center p-3">
                                <div class="display-4 me-3">💰</div>
                                <div>
                                    <h5 class="card-title text-success mb-1">Valorización Inventario</h5>
                                    <p class="card-text small text-muted">Ver reporte financiero.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        const ctx = document.getElementById('miGrafico');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_keys($kpis)); ?>,
                datasets: [{
                    label: '# Vehículos',
                    data: <?php echo json_encode(array_values($kpis)); ?>,
                    backgroundColor: ['rgba(255, 99, 132, 0.6)','rgba(54, 162, 235, 0.6)','rgba(255, 206, 86, 0.6)','rgba(75, 192, 192, 0.6)','rgba(153, 102, 255, 0.6)'],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false, // Importante para responsive
                scales: { y: { beginAtZero: true, stepSize: 1 } }
            }
        });
    </script>
</body>
</html>