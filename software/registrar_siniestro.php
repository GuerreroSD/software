<?php
session_start();
require 'config/conexion.php'; // Asegúrate que esta ruta sea correcta según tus carpetas

// Verificación de sesión (RF-1)
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$msg = "";

// PROCESAR FORMULARIO
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Datos básicos (RF-2)
        $rut = $_POST['rut'];
        $nombre = $_POST['nombre'];
        $vin = $_POST['vin'];
        $patente = $_POST['patente'];
        $marca = $_POST['marca'];
        
        // Datos lógicos (RF-3)
        $tipo_cliente = $_POST['tipo_cliente']; // 'SEGURO' o 'PARTICULAR'
        
        // Asignar null si no aplica
        $compania = ($tipo_cliente == 'SEGURO') ? $_POST['compania'] : null;
        $nro_siniestro = ($tipo_cliente == 'SEGURO') ? $_POST['nro_siniestro'] : null;
        
        // Checkbox de abono (Si no está marcado, es '0')
        $abono = (isset($_POST['abono_pagado'])) ? '1' : '0';

        // Validar duplicados (Restricción RF-2)
        $check = $conn->prepare("SELECT COUNT(*) FROM VEHICULOS WHERE PATENTE = :pat OR VIN = :vin");
        $check->execute([':pat' => $patente, ':vin' => $vin]);
        
        if ($check->fetchColumn() > 0) {
            $msg = "<div class='alert alert-danger'>Error: La patente o VIN ya existen en el sistema.</div>";
        } else {
            // INSERT COMPLETO
            $sql = "INSERT INTO VEHICULOS 
                    (RUT_CLIENTE, NOMBRE_CLIENTE, TIPO_CLIENTE, VIN, PATENTE, MARCA_MODELO, COMPANIA_SEGUROS, NUMERO_SINIESTRO, ABONO_PAGADO) 
                    VALUES (:rut, :nom, :tipo, :vin, :pat, :marca, :comp, :sini, :abono)";
            
            $stmt = $conn->prepare($sql);
            $exito = $stmt->execute([
                ':rut' => $rut,
                ':nom' => $nombre,
                ':tipo' => $tipo_cliente,
                ':vin' => $vin,
                ':pat' => $patente,
                ':marca' => $marca,
                ':comp' => $compania,
                ':sini' => $nro_siniestro,
                ':abono' => $abono
            ]);

            if ($exito) {
                $msg = "<div class='alert alert-success'>¡Vehículo registrado correctamente! (RF-2 y RF-3 completados)</div>";
            } else {
                $msg = "<div class='alert alert-danger'>Error al guardar en Oracle.</div>";
            }
        }

    } catch (PDOException $e) {
        $msg = "<div class='alert alert-danger'>Error de Base de Datos: " . $e->getMessage() . "</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registrar Siniestro - SIGSAK</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

    <nav class="navbar navbar-dark bg-dark mb-4">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">SIGSAK</a>
            <a href="dashboard.php" class="btn btn-outline-secondary text-white btn-sm">Volver al Dashboard</a>
        </div>
    </nav>

    <div class="container bg-white p-4 rounded shadow" style="max-width: 800px;">
        <h3 class="mb-4">Registrar Nuevo Siniestro (RF-2 / RF-3)</h3>
        
        <?php echo $msg; ?>

        <form method="POST" action="registrar_siniestro.php">
            <div class="row g-3">
                
                <div class="col-md-6">
                    <label class="form-label">RUT Cliente *</label>
                    <input type="text" name="rut" class="form-control" required placeholder="12345678-9">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Nombre Cliente *</label>
                    <input type="text" name="nombre" class="form-control" required>
                </div>
                
                <div class="col-md-4">
                    <label class="form-label">VIN (Chasis) *</label>
                    <input type="text" name="vin" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Patente *</label>
                    <input type="text" name="patente" class="form-control" required style="text-transform: uppercase;">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Marca y Modelo</label>
                    <input type="text" name="marca" class="form-control" placeholder="Ej: Chevrolet Sail">
                </div>

                <hr class="my-4">

                <div class="col-12">
                    <h5 class="text-secondary">Tipo de Siniestro</h5>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Seleccione Tipo *</label>
                    <select name="tipo_cliente" id="tipoCliente" class="form-select" onchange="toggleRF3()" required>
                        <option value="">-- Seleccionar --</option>
                        <option value="SEGURO">Compañía de Seguros</option>
                        <option value="PARTICULAR">Particular</option>
                    </select>
                </div>

                <div id="bloqueSeguro" class="col-12 row mt-3 p-3 border rounded bg-light ms-1" style="display:none;">
                    <div class="col-md-6">
                        <label class="form-label">Compañía de Seguros *</label>
                        <input type="text" name="compania" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Número de Siniestro *</label>
                        <input type="text" name="nro_siniestro" class="form-control">
                    </div>
                </div>

                <div id="bloqueParticular" class="col-12 mt-3 p-3 border rounded bg-light ms-1" style="display:none;">
                    <div class="alert alert-warning mb-2">
                        <strong>Requisito (RF-3):</strong> Clientes particulares deben abonar el 60% del presupuesto estimado para ingresar.
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="abono_pagado" value="1" id="abonoCheck">
                        <label class="form-check-label fw-bold" for="abonoCheck">¿Cliente pagó el abono del 60%?</label>
                    </div>
                </div>

                <div class="col-12 mt-4 text-end">
                    <button type="submit" class="btn btn-primary btn-lg">Registrar Vehículo</button>
                </div>
            </div>
        </form>
    </div>

    <script>
    function toggleRF3() {
        var tipo = document.getElementById("tipoCliente").value;
        var divSeguro = document.getElementById("bloqueSeguro");
        var divPart = document.getElementById("bloqueParticular");
        
        // Inputs de seguro
        var inputCia = document.querySelector('[name="compania"]');
        var inputSin = document.querySelector('[name="nro_siniestro"]');

        if (tipo === "SEGURO") {
            divSeguro.style.display = "flex";
            divPart.style.display = "none";
            // Hacer obligatorios los campos de seguro
            inputCia.required = true;
            inputSin.required = true;
        } else if (tipo === "PARTICULAR") {
            divSeguro.style.display = "none";
            divPart.style.display = "block";
            // Quitar obligatorio a seguro
            inputCia.required = false;
            inputSin.required = false;
        } else {
            divSeguro.style.display = "none";
            divPart.style.display = "none";
            inputCia.required = false;
            inputSin.required = false;
        }
    }
    </script>

</body>
</html>