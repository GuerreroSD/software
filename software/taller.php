<?php
session_start();
require 'funciones/funciones.php';


// Procesar Cambio de Estado
if(isset($_POST['nuevo_estado'])) {
    cambiarEstadoVehiculo($_POST['id'], $_POST['nuevo_estado'], $_SESSION['user_id']);
    registrarHistorialVehiculo($_POST['id'], '', $_POST['nuevo_estado'], $_SESSION['user_id']);
}
if(isset($_POST['ubicacion'])) {
    cambiarUbicacionVehiculo($_POST['id'], $_POST['ubicacion'], $_SESSION['user_id']);
}
if (isset($_POST['id_mecanico']) && !empty($_POST['id_mecanico'])) {
    
    $id_vehiculo = $_POST['id'];
    $id_mecanico = $_POST['id_mecanico'];

    try {
        // 1. Preparamos la consulta para Oracle
        
        $sqlAsignacion = "INSERT INTO ASIGNACIONES (ID_VEHICULO, ID_MECANICO, FECHA_ASIGNACION) 
                          VALUES (:vehiculo, :mecanico, SYSDATE)";
        
        $stmt = $conn->prepare($sqlAsignacion);
        
        // 2. Vinculamos los parámetros 
        $stmt->bindParam(':vehiculo', $id_vehiculo);
        $stmt->bindParam(':mecanico', $id_mecanico);
        
        // 3. Ejecutamos
        if ($stmt->execute()) {
            echo "<script>
                    alert('Mecánico asignado correctamente en Oracle.');
                    // Opcional: Redirigir para limpiar el POST
                    // window.location.href = window.location.href; 
                  </script>";
        } else {
            echo "<script>alert('Error al guardar en Oracle.');</script>";
        }

    } catch (PDOException $e) {
        echo "<script>alert('Error Crítico Oracle: " . $e->getMessage() . "');</script>";
    }
}
// Procesar Subida de Foto (RF-16)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['foto'])) {
    $id_vehiculo = $_POST['id'];
    $directorio = __DIR__ . '/uploads/';
    
    // Crear carpeta si no existe
    if (!file_exists($directorio)) {
        mkdir($directorio, 0777, true);
    }

    $nombre_archivo = time() . "_" . basename($_FILES['foto']['name']);
    $ruta_completa = $directorio . $nombre_archivo;
    $ruta_db = 'uploads/' . $nombre_archivo; // Ruta relativa para guardar en BD

    // Validaciones básicas
    if ($_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
        echo "<script>alert('Error PHP al subir: " . $_FILES['foto']['error'] . "');</script>";
    } else {
        if (move_uploaded_file($_FILES['foto']['tmp_name'], $ruta_completa)) {
            // Guardar en Oracle
            $sql = "INSERT INTO DOCUMENTOS (ID_VEHICULO, RUTA_ARCHIVO, SUBIDO_POR, FECHA_SUBIDA) VALUES (:id, :ruta, :usr, SYSDATE)";
            $stmt = $conn->prepare($sql);
            if ($stmt->execute([':id' => $id_vehiculo, ':ruta' => $ruta_db, ':usr' => $_SESSION['user_id']])) {
                echo "<script>alert('Archivo subido y registrado correctamente [cite: 378]');</script>";
            } else {
                echo "<script>alert('Archivo subido pero error al guardar en BD Oracle');</script>";
            }
        } else {
            echo "<script>alert('Error: No se pudo mover el archivo a la carpeta uploads. Verifique permisos.');</script>";
        }
    }
}


// Modificamos la query para traer también la FOTO (RF-16)
$sql = "SELECT V.*, 
        -- Subconsulta para el Mecánico 
        (SELECT ID_MECANICO FROM ASIGNACIONES A 
         WHERE A.ID_VEHICULO = V.ID_VEHICULO 
         ORDER BY FECHA_ASIGNACION DESC 
         FETCH FIRST 1 ROWS ONLY) AS MECANICO_ID,
         
        -- NUEVA Subconsulta para la Foto del Auto
        (SELECT RUTA_ARCHIVO FROM DOCUMENTOS D 
         WHERE D.ID_VEHICULO = V.ID_VEHICULO 
         ORDER BY FECHA_SUBIDA DESC 
         FETCH FIRST 1 ROWS ONLY) AS FOTO_VEHICULO
         
        FROM VEHICULOS V";

$vehiculos = $conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);

// Pon esto antes de tu tabla HTML
$mapa_fotos = [
    '1' => 'mecanicos/mecanico1.jpeg',
    '2' => 'mecanicos/mecanico2.jpeg',
    '3' => 'mecanicos/mecanico3.jpeg',
    '4' => 'mecanicos/mecanico4.jpeg'
];


?>
<!DOCTYPE html>
<html>
<head><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
        /* Un poco de estilo para que se vea decente */
        .contenedor-seleccion {
            display: flex;
            align-items: center;
            gap: 20px;
            background: #f0f0f0;
            padding: 20px;
            border-radius: 10px;
            width: fit-content;
        }

        .foto-circular {
            width: 100px;
            height: 100px;
            border-radius: 50%; /* Esto hace la foto redonda como en tu diseño */
            object-fit: cover;
            border: 3px solid #333;
            background-color: #ddd;
        }

        select {
            padding: 10px;
            font-size: 16px;
        }
    </style>

</head>
<body class="p-4">
    <div class="container">
        <div class="d-flex justify-content-between mb-4">
            <h3>Gestión de Taller (Kanban)</h3>
            <a href="dashboard.php" class="btn btn-secondary">Volver</a>
        </div>

        <table class="table table-bordered">
            <thead class="table-dark">
                <tr><th>Vehículo</th><th>Ubicacion</th><th>Estado (RF-9)</th><th>Estado Orden Trabajo</th><th>Acciones</th><th>Mecanico</th></tr>
            </thead>
            <tbody>
                <?php foreach($vehiculos as $v): ?>
                <tr>
                    <td>
                        <strong><?php echo htmlspecialchars($v['MARCA_MODELO']); ?></strong> 
                            <form method="POST" class="d-inline">
                            <input type="hidden" name="id" value="<?php echo $v['ID_VEHICULO']; ?>">
                            <select name="ubicacion" class="form-select form-select-sm d-inline w-auto mt-1" onchange="this.form.submit()">
                                <option selected disabled>Lugar</option>
                                <option value="EXTERIOR">EXTERIOR</option>
                                <option value="TALLER">TALLER</option>
                            </select>
                        </form><br>
                        <small>Patente: <?php echo htmlspecialchars($v['PATENTE']); ?></small><br>
                        <small>VIN: <?php echo htmlspecialchars($v['VIN']); ?></small><br>
                        <small>Seguro: <?php if (!empty($v['COMPANIA_SEGUROS'])) { echo htmlspecialchars($v['COMPANIA_SEGUROS']); } else {echo "Cliente Particular"; }; ?></small><br>
                        <small>Abono Pagado: <?php echo ($v['ABONO_PAGADO']) ? 'Sí' : 'No'; ?></small>
                    </td>
                    <td>
                        <span class="badge bg-info"><?php echo htmlspecialchars($v['UBICACION']); ?></span>
                    </td>
                    <td>
                        <span class="badge bg-info"><?php echo htmlspecialchars($v['ESTADO_ACTUAL']); ?></span>
                    </td>
                    <td>
                        <span class="badge bg-info"><?php echo htmlspecialchars($v['ESTADO_ACTUAL_OT']); ?></span>
                        <!--small>Total Deducible: <?//php if (($v['ESTADO_ACTUAL_OT']) === 'APROBADA') { echo number_format($v['TOTAL_APROBADO'], 0); } else {echo "Pendiente"; } ?></small-->
                    </td>
                    <td>
                        <a href="presupuestos.php?id=<?php echo $v['ID_VEHICULO']; ?>" class="btn btn-sm btn-success mb-1">
                            Presupuesto (RF-4)
                        </a>
                        
                        <br> <form method="POST" class="d-inline">
                            <input type="hidden" name="id" value="<?php echo $v['ID_VEHICULO']; ?>">
                            <select name="nuevo_estado" class="form-select form-select-sm d-inline w-auto mt-1" onchange="this.form.submit()">
                                <option selected disabled>Cambiar Estado...</option>
                                <option value="DESARME">Desarme</option>
                                <option value="PINTURA">Pintura</option>
                                <option value="ARMADO">Armado</option>
                                <option value="LISTO">Listo Entrega</option>
                            </select>
                        </form>
                        
                    <div style="text-align: center;">
                        <?php if (!empty($v['FOTO_VEHICULO'])): ?>
                            <div style="margin-bottom: 5px;">
                                <img src="<?php echo htmlspecialchars($v['FOTO_VEHICULO']); ?>" 
                                    alt="Foto Auto" 
                                    style="width: 80px; height: 60px; object-fit: cover; border-radius: 4px; border: 1px solid #999; cursor: pointer;"
                                    onclick="window.open(this.src, '_blank');">
                                
                                <br>
                                <small style="font-size: 10px;"><a href="<?php echo htmlspecialchars($v['FOTO_VEHICULO']); ?>" target="_blank">Ver Grande</a></small>
                            </div>
                        <?php endif; ?>

                        <form method="POST" enctype="multipart/form-data" class="d-inline">
                            <input type="hidden" name="id" value="<?php echo $v['ID_VEHICULO']; ?>">
                            <label class="btn btn-sm btn-outline-warning" title="Subir/Cambiar Foto (RF-16)" style="font-size: 12px;">
                                <?php echo (!empty($v['FOTO_VEHICULO'])) ? '🔄 Cambiar' : '📷 Subir Foto'; ?>
                                <input type="file" name="foto" hidden onchange="this.form.submit()">
                            </label>
                        </form>
                    </div>
                    </td>
                    <td>
                        <form method="POST" class="contenedor-seleccion" style="padding: 5px; gap: 5px; flex-direction: column;">
                            <input type="hidden" name="id" value="<?php echo $v['ID_VEHICULO']; ?>">
                            
                            <?php 
                                $mecanico_actual = $v['MECANICO_ID'] ?? 0; // Si es null, pone 0
                                
                                // 2. CALCULAR LA FOTO INICIAL EN PHP
                                $foto_inicial = isset($mapa_fotos[$mecanico_actual]) 
                                                ? $mapa_fotos[$mecanico_actual] 
                                                : 'mecanicos/sin_foto.png';
                            ?>

                            <div style="width: 100%;">
                                <label style="font-size:12px; font-weight:bold;">Asignar Mecánico:</label>
                                
                                <select name="id_mecanico" id="selector_<?php echo $v['ID_VEHICULO']; ?>" 
                                        onchange="cambiarFoto(<?php echo $v['ID_VEHICULO']; ?>)"
                                        class="form-select form-select-sm mb-2">
                                    
                                    <option value="">-- Seleccionar --</option>
                                    
                                    <option value="4" <?php echo ($mecanico_actual == 4) ? 'selected' : ''; ?>>Juan Pérez</option>
                                    <option value="2" <?php echo ($mecanico_actual == 2) ? 'selected' : ''; ?>>Maria Gonzalez</option>
                                    <option value="3" <?php echo ($mecanico_actual == 3) ? 'selected' : ''; ?>>Carlos "El Tuercas"</option>
                                    <option value="1" <?php echo ($mecanico_actual == 1) ? 'selected' : ''; ?>>Leku "El Tuercas 2004"</option>
                                </select>
                            </div>

                            <div style="display: flex; align-items: center; gap: 10px;">
                                <img id="foto_<?php echo $v['ID_VEHICULO']; ?>" 
                                    src="<?php echo $foto_inicial; ?>" 
                                    alt="Foto" class="foto-circular" style="width:50px; height:50px;">
                                
                                <button type="submit" class="btn btn-primary btn-sm">💾 Guardar</button>
                            </div>
                        </form>
                    </td>
                        <script>
                        function cambiarFoto() {
                                    // Obtener el valor seleccionado (1, 2 o 3)
                                    var idSeleccionado = document.getElementById("selectorMecanico").value;
                                    var imagen = document.getElementById("fotoMecanico");

                                    var baseDeDatosFotos = {
                                        '1': 'mecanicos/mecanico1.jpeg',     
                                        '2': 'mecanicos/mecanico2.jpeg',         
                                        '3': 'mecanicos/mecanico3.jpeg',        
                                        '4': 'mecanicos/mecanico4.jpeg'        
                                    };

                                    // Cambiar la fuente (src) de la imagen según lo que elegiste
                                    if (baseDeDatosFotos[idSeleccionado]) {
                                        imagen.src = baseDeDatosFotos[idSeleccionado];
                                    } else {
                                        imagen.src = 'mecanicos/sin_foto.png';
                                    }
                                }
                            </script>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>


