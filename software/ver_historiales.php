<?php
session_start();
require 'funciones/funciones.php';

// --- LÓGICA RF-20 (FILTROS DE FECHA) ---
$filtro_sql = "";
$params = [];

// Valores por defecto (Últimos 30 días si no eligen nada)
$fecha_inicio = $_GET['f_inicio'] ?? date('Y-m-d', strtotime('-1 month'));
$fecha_fin    = $_GET['f_fin']    ?? date('Y-m-d');

if (isset($_GET['filtrar'])) {
    // Ajustamos la query para filtrar por rango
    $filtro_sql = " AND H.FECHA_MOVIMIENTO BETWEEN TO_DATE(:f_ini, 'YYYY-MM-DD') AND TO_DATE(:f_fin, 'YYYY-MM-DD') + 1 ";
    $params[':f_ini'] = $fecha_inicio;
    $params[':f_fin'] = $fecha_fin;
}

// 1. CONSULTA RF-19: HISTORIAL VEHÍCULOS (Últimos 50 cambios)
// Hacemos JOIN con VEHICULOS para mostrar la Patente y no solo el ID
$sqlVehiculos = "SELECT H.*, V.PATENTE, V.MARCA_MODELO 
                 FROM HISTORIAL_VEHICULOS H
                 LEFT JOIN VEHICULOS V ON H.ID_VEHICULO = V.ID_VEHICULO
                 ORDER BY H.FECHA DESC 
                 FETCH FIRST 50 ROWS ONLY";
$historialV = $conn->query($sqlVehiculos)->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Historiales de Auditoría</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light p-4">
    <div class="container bg-white p-4 rounded shadow-sm">
        <div class="d-flex justify-content-between mb-4">
            <h2>📜 Auditoría del Sistema</h2>
            <a href="dashboard.php" class="btn btn-secondary">Volver al Dashboard</a>
        </div>

        <ul class="nav nav-tabs mb-3" id="myTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="vehiculos-tab" data-bs-toggle="tab" data-bs-target="#vehiculos" type="button">
                    🚗 Historial Vehículos (RF-19)
                </button>
            </li>
        </ul>

        <div class="tab-content" id="myTabContent">
            
            <div class="tab-pane fade show active" id="vehiculos" role="tabpanel">
                <div class="alert alert-info py-2">
                    Mostrando los últimos 50 cambios de estado (Solo lectura).
                </div>
                <table class="table table-sm table-hover border">
                    <thead class="table-dark">
                        <tr>
                            <th>Fecha/Hora</th>
                            <th>Vehículo</th>
                            <th>Estado Anterior</th>
                            <th>Nuevo Estado</th>
                            <th>ID Usuario</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($historialV as $row): ?>
                        <tr>
                            <td><?php echo $row['FECHA']; ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($row['PATENTE'] ?? 'N/A'); ?></strong><br>
                                <small><?php echo htmlspecialchars($row['MARCA_MODELO'] ?? ''); ?></small>
                            </td>
                            <td class="text-danger"><?php echo htmlspecialchars($row['ESTADO_ANTERIOR']); ?></td>
                            <td class="text-success fw-bold"><?php echo htmlspecialchars($row['ESTADO_NUEVO']); ?></td>
                            <td>User #<?php echo $row['ID_USUARIO']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="tab-pane fade" id="repuestos" role="tabpanel">
                <form method="GET" class="row g-3 mb-3 p-3 bg-light border rounded">
                    <div class="col-auto d-flex align-items-center">
                        <span class="fw-bold me-2">Filtrar por fecha:</span>
                    </div>
                    <div class="col-auto">
                        <input type="date" name="f_inicio" class="form-control" value="<?php echo $fecha_inicio; ?>" required>
                    </div>
                    <div class="col-auto">
                        <span class="fw-bold">hasta</span>
                    </div>
                    <div class="col-auto">
                        <input type="date" name="f_fin" class="form-control" value="<?php echo $fecha_fin; ?>" required>
                    </div>
                    <div class="col-auto">
                        <button type="submit" name="filtrar" value="1" class="btn btn-primary">🔍 Buscar</button>
                    </div>
                    <script>
                        if(window.location.search.includes('filtrar')) {
                            document.addEventListener("DOMContentLoaded", function() {
                                var triggerEl = document.querySelector('#repuestos-tab');
                                var tab = new bootstrap.Tab(triggerEl);
                                tab.show();
                            });
                        }
                    </script>
                </form>

                <table class="table table-sm table-bordered">
                    <thead class="table-secondary">
                        <tr>
                            <th>Fecha Movimiento</th>
                            <th>Repuesto Afectado</th>
                            <th>Acción Realizada</th>
                            <th>Responsable (ID)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($historialR as $row): ?>
                        <tr>
                            <td><?php echo $row['FECHA_MOVIMIENTO']; ?></td>
                            <td>
                                <?php echo htmlspecialchars($row['NOMBRE_REPUESTO'] ?? 'ID: '.$row['ID_REPUESTO']); ?>
                            </td>
                            <td>
                                <?php 
                                    $accion = strtoupper($row['ACCION']);
                                    $clase = 'text-dark';
                                    if(strpos($accion, 'CASTIGADO') !== false) $clase = 'badge bg-danger text-white';
                                    elseif(strpos($accion, 'CREADO') !== false) $clase = 'badge bg-success text-white';
                                    else $clase = 'badge bg-secondary text-white';
                                ?>
                                <span class="<?php echo $clase; ?>"><?php echo htmlspecialchars($row['ACCION']); ?></span>
                            </td>
                            <td>User #<?php echo $row['ID_USUARIO']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($historialR)) echo "<tr><td colspan='4' class='text-center'>No hay movimientos en este rango de fechas.</td></tr>"; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
