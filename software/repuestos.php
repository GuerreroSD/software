<?php
session_start();
require 'config/conexion.php';
// require 'funciones/funciones.php'; // 

if (!isset($_SESSION['user_id'])) header("Location: index.php");

$msg = "";

// ==========================================
// 1. PROCESAR SOLICITUD DE REPUESTO (RF-7)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['solicitar_repuesto'])) {
    try {
        $sql = "INSERT INTO SOLICITUDES_REPUESTOS (ID_VEHICULO, NUMERO_PARTE, DESCRIPCION, CANTIDAD, PROVEEDOR, ESTADO, FECHA_SOLICITUD) 
                VALUES (:id, :parte, :descri, :cant, :prov, 'SOLICITADO', SYSDATE)";
        
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':id' => $_POST['id_vehiculo'],
            ':parte' => $_POST['numero_parte'],
            ':descri' => $_POST['descripcion'],
            ':cant' => $_POST['cantidad'],
            ':prov' => $_POST['proveedor']
        ]);
        $msg = "<div class='alert alert-success alert-dismissible fade show'>Repuesto solicitado correctamente. <button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
    } catch (PDOException $e) {
        $msg = "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
    }
}

// ==========================================
// 2. ACTUALIZAR ESTADO (RF-8 - Gestión)
// ==========================================
if (isset($_GET['accion']) && isset($_GET['id_solicitud'])) {
    $nuevo_estado = $_GET['accion']; 
    $id_sol = $_GET['id_solicitud'];
    
    // Validación básica de seguridad para estados permitidos
    if(in_array($nuevo_estado, ['EN TRANSITO', 'RECIBIDO'])){
        $stmt = $conn->prepare("UPDATE SOLICITUDES_REPUESTOS SET ESTADO = :est WHERE ID_SOLICITUD = :id");
        $stmt->execute([':est' => $nuevo_estado, ':id' => $id_sol]);
    }
    
    // Redirección limpia para quitar parámetros GET de la URL
    header("Location: repuestos.php"); 
    exit;
}



// A) Obtener vehículos para el formulario
$vehiculos = $conn->query("SELECT ID_VEHICULO, PATENTE, MARCA_MODELO FROM VEHICULOS ORDER BY PATENTE ASC")->fetchAll(PDO::FETCH_ASSOC);

// B) Obtener lista de repuestos CON FILTRO (RF-8 - Visualización)
$sql_repuestos = "SELECT S.*, V.PATENTE, V.MARCA_MODELO 
                  FROM SOLICITUDES_REPUESTOS S 
                  JOIN VEHICULOS V ON S.ID_VEHICULO = V.ID_VEHICULO";

// Lógica de Filtrado Dinámico
$filtro_params = [];
if (isset($_GET['estado']) && !empty($_GET['estado'])) {
    $sql_repuestos .= " WHERE S.ESTADO = :p_estado"; 
    $filtro_params[':p_estado'] = $_GET['estado'];
}

$sql_repuestos .= " ORDER BY S.FECHA_SOLICITUD DESC"; // Ordenar por más reciente

// Preparar y Ejecutar
$stmt_list = $conn->prepare($sql_repuestos);
$stmt_list->execute($filtro_params);
$repuestos = $stmt_list->fetchAll(PDO::FETCH_ASSOC); // Guardamos todo en un array para el HTML

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Repuestos - SIGSAK</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light p-4">
    <div class="container">
        <div class="d-flex justify-content-between mb-4">
            <h3>📦 Gestión de Repuestos (RF-7 / RF-8)</h3>
            <a href="dashboard.php" class="btn btn-secondary">Volver al Dashboard</a>
        </div>

        <?php echo $msg; ?>

        <div class="row">
            <div class="col-md-4">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-primary text-white">Nueva Solicitud</div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="solicitar_repuesto" value="1">
                            
                            <div class="mb-3">
                                <label class="form-label">Vehículo *</label>
                                <select name="id_vehiculo" class="form-select" required>
                                    <option value="">Seleccione...</option>
                                    <?php foreach($vehiculos as $v): ?>
                                        <option value="<?php echo $v['ID_VEHICULO']; ?>">
                                            <?php echo $v['PATENTE'] . " - " . $v['MARCA_MODELO']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Nro. Parte *</label>
                                <input type="text" name="numero_parte" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Descripción</label>
                                <input type="text" name="descripcion" class="form-control" required>
                            </div>
                            <div class="row">
                                <div class="col-6 mb-3">
                                    <label class="form-label">Cantidad</label>
                                    <input type="number" name="cantidad" class="form-control" value="1" min="1" required>
                                </div>
                                <div class="col-6 mb-3">
                                    <label class="form-label">Proveedor</label>
                                    <input type="text" name="proveedor" class="form-control" placeholder="Ej: Bosch">
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Solicitar Repuesto</button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-8">
                <div class="card shadow-sm">
                    <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                        <span>Estado de Pedidos</span>
                        
                        <form method="GET" action="" class="d-flex" style="max-width: 250px;">
                            <select name="estado" class="form-select form-select-sm me-2" onchange="this.form.submit()">
                                <option value="">-- Todos los Estados --</option>
                                <option value="SOLICITADO" <?php if(isset($_GET['estado']) && $_GET['estado'] == 'SOLICITADO') echo 'selected'; ?>>SOLICITADO</option>
                                <option value="EN TRANSITO" <?php if(isset($_GET['estado']) && $_GET['estado'] == 'EN TRANSITO') echo 'selected'; ?>>EN TRANSITO</option>
                                <option value="RECIBIDO" <?php if(isset($_GET['estado']) && $_GET['estado'] == 'RECIBIDO') echo 'selected'; ?>>RECIBIDO</option>
                            </select>
                            <noscript><button type="submit" class="btn btn-sm btn-light">Ir</button></noscript>
                        </form>
                    </div>
                    
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover table-striped mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Pieza / Descripción</th>
                                        <th>Cant.</th>
                                        <th>Vehículo / Prov.</th>
                                        <th>Estado</th>
                                        <th>Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($repuestos) > 0): ?>
                                        <?php foreach($repuestos as $r): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($r['NUMERO_PARTE']); ?></strong><br>
                                                <small class="text-muted"><?php echo htmlspecialchars($r['DESCRIPCION']); ?></small>
                                            </td>
                                            <td class="text-center">
                                                <?php echo htmlspecialchars($r['CANTIDAD']); ?>
                                            </td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($r['PATENTE']); ?></strong><br>
                                                <small class="text-muted"><?php echo htmlspecialchars($r['PROVEEDOR']); ?></small>
                                            </td>
                                            <td>
                                                <?php 
                                                $claseBadge = 'bg-secondary';
                                                if($r['ESTADO'] == 'EN TRANSITO') $claseBadge = 'bg-warning text-dark';
                                                if($r['ESTADO'] == 'RECIBIDO') $claseBadge = 'bg-success';
                                                if($r['ESTADO'] == 'SOLICITADO') $claseBadge = 'bg-danger';
                                                ?>
                                                <span class="badge <?php echo $claseBadge; ?>"><?php echo $r['ESTADO']; ?></span>
                                            </td>
                                            <td>
                                                <?php if($r['ESTADO'] == 'SOLICITADO'): ?>
                                                    <a href="?accion=EN TRANSITO&id_solicitud=<?php echo $r['ID_SOLICITUD']; ?>" class="btn btn-sm btn-outline-warning" title="Marcar como En Tránsito">Encargar</a>
                                                <?php elseif($r['ESTADO'] == 'EN TRANSITO'): ?>
                                                    <a href="?accion=RECIBIDO&id_solicitud=<?php echo $r['ID_SOLICITUD']; ?>" class="btn btn-sm btn-outline-success" title="Marcar como Recibido">Recibir</a>
                                                <?php else: ?>
                                                    <span class="text-muted small">Completado</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="text-center py-4">
                                                No hay repuestos con el estado seleccionado.
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>

