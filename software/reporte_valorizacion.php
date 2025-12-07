<?php
session_start();
require 'funciones/funciones.php';

// Filtros
$where = " WHERE 1=1 "; // Truco para concatenar ANDs
$params = [];

if (!empty($_GET['compania'])) {
    $where .= " AND V.COMPANIA_SEGUROS = :cia ";
    $params[':cia'] = $_GET['compania'];
}
// Puedes agregar filtro de fechas aquí igual...

// Consulta RF-18: Unimos Vehiculos con Repuestos Solicitados y sumamos precios
$sql = "SELECT V.PATENTE, 
               V.MARCA_MODELO, 
               V.COMPANIA_SEGUROS,
               V.ESTADO_ACTUAL_OT,
               SUM(R.PRECIO_UNITARIO * S.CANTIDAD) as TOTAL_INVERTIDO
        FROM VEHICULOS V
        JOIN SOLICITUDES_REPUESTOS S ON V.ID_VEHICULO = S.ID_VEHICULO
        JOIN REPUESTOS R ON S.ID_REPUESTO = R.ID_REPUESTO
        $where
        GROUP BY V.PATENTE, V.MARCA_MODELO, V.COMPANIA_SEGUROS, V.ESTADO_ACTUAL_OT";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$reporte = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="p-5">
    <div class="d-flex justify-content-between mb-4">
            <h3>RF-18: Valorización de Inventario en Curso</h3>
        <a href="dashboard.php" class="btn btn-secondary">Volver</a>
    </div>
    <form method="GET" class="row g-3 mb-4 bg-light p-3 rounded">
        <div class="col-auto">
            <label>Compañía Seguros:</label>
            <input type="text" name="compania" class="form-control" placeholder="Ej: HDI">
        </div>
        <div class="col-auto align-self-end">
            <button type="submit" class="btn btn-primary">Filtrar</button>
        </div>
    </form>

    <table class="table table-bordered border-dark">
        <thead class="table-dark">
            <tr>
                <th>Patente</th>
                <th>Vehículo</th>
                <th>Cía. Seguros</th>
                <th>Estado Orden</th>
                <th>Valor Repuestos Total ($)</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($reporte as $fila): ?>
            <tr>
                <td><?php echo htmlspecialchars($fila['PATENTE']); ?></td>
                <td><?php echo htmlspecialchars($fila['MARCA_MODELO']); ?></td>
                <td><?php echo htmlspecialchars($fila['COMPANIA_SEGUROS']); ?></td>
                <td><?php echo htmlspecialchars($fila['ESTADO_ACTUAL_OT']); ?></td>
                <td class="text-end fw-bold">
                    $ <?php echo number_format($fila['TOTAL_INVERTIDO'], 0, ',', '.'); ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>