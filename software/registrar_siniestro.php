<?php
session_start();
require 'config/conexion.php'; 

if (!isset($_SESSION['user_id'])) { header("Location: index.php"); exit; }

$msg = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        $rut = $_POST['rut']; $nombre = $_POST['nombre']; $vin = $_POST['vin']; $patente = $_POST['patente']; $marca = $_POST['marca'];
        $tipo_cliente = $_POST['tipo_cliente'];
        $compania = ($tipo_cliente == 'SEGURO') ? $_POST['compania'] : null;
        $nro_siniestro = ($tipo_cliente == 'SEGURO') ? $_POST['nro_siniestro'] : null;
        $abono = (isset($_POST['abono_pagado'])) ? '1' : '0';

        $check = $conn->prepare("SELECT COUNT(*) FROM VEHICULOS WHERE PATENTE = :pat OR VIN = :vin");
        $check->execute([':pat' => $patente, ':vin' => $vin]);
        
        if ($check->fetchColumn() > 0) {
            $msg = "<div class='alert alert-danger'>Error: Patente o VIN ya existen.</div>";
        } else {
            $sql = "INSERT INTO VEHICULOS (RUT_CLIENTE, NOMBRE_CLIENTE, TIPO_CLIENTE, VIN, PATENTE, MARCA_MODELO, COMPANIA_SEGUROS, NUMERO_SINIESTRO, ABONO_PAGADO) VALUES (:rut, :nom, :tipo, :vin, :pat, :marca, :comp, :sini, :abono)";
            $stmt = $conn->prepare($sql);
            $exito = $stmt->execute([':rut' => $rut, ':nom' => $nombre, ':tipo' => $tipo_cliente, ':vin' => $vin, ':pat' => $patente, ':marca' => $marca, ':comp' => $compania, ':sini' => $nro_siniestro, ':abono' => $abono]);
            if ($exito) $msg = "<div class='alert alert-success'>¡Vehículo registrado!</div>";
            else $msg = "<div class='alert alert-danger'>Error al guardar.</div>";
        }
    } catch (PDOException $e) {
        $msg = "<div class='alert alert-danger'>Error BD: " . $e->getMessage() . "</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registrar Siniestro</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

    <nav class="navbar navbar-dark bg-dark sticky-top px-3 shadow">
        <div class="d-flex align-items-center">
            <button class="navbar-toggler d-md-none me-2" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarMenu"><span class="navbar-toggler-icon"></span></button>
            <a class="navbar-brand me-0" href="#">SIGSAK</a>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <div class="sidebar border border-right col-md-3 col-lg-2 p-0 bg-body-tertiary">
                <div class="offcanvas-md offcanvas-start bg-body-tertiary" tabindex="-1" id="sidebarMenu">
                    <div class="offcanvas-header"><h5 class="offcanvas-title">Menú</h5><button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button></div>
                    <div class="offcanvas-body d-md-flex flex-column p-0 pt-lg-3 overflow-y-auto">
                        <div class="list-group list-group-flush w-100">
                            <a href="dashboard.php" class="list-group-item list-group-item-action"> Dashboard</a>
                            <a href="registrar_siniestro.php" class="list-group-item list-group-item-action active fw-bold"> Nuevo Siniestro</a>
                            <a href="taller.php" class="list-group-item list-group-item-action"> Gestión Taller</a>
                            <a href="ver_historiales.php" class="list-group-item list-group-item-action"> Historial</a>
                            <a href="repuestos.php" class="list-group-item list-group-item-action"> Repuestos</a>
                        </div>
                    </div>
                </div>
            </div>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <div class="container bg-white p-4 rounded shadow-sm" style="max-width: 900px;">
                    <h3 class="mb-4">Registrar Nuevo Siniestro</h3>
                    <?php echo $msg; ?>

                    <form method="POST" action="registrar_siniestro.php">
                        <div class="row g-3">
                            <div class="col-12 col-md-6"><label class="form-label">RUT Cliente *</label><input type="text" name="rut" class="form-control" required></div>
                            <div class="col-12 col-md-6"><label class="form-label">Nombre Cliente *</label><input type="text" name="nombre" class="form-control" required></div>
                            <div class="col-12 col-md-4"><label class="form-label">VIN *</label><input type="text" name="vin" class="form-control" required></div>
                            <div class="col-12 col-md-4"><label class="form-label">Patente *</label><input type="text" name="patente" class="form-control" required style="text-transform: uppercase;"></div>
                            <div class="col-12 col-md-4"><label class="form-label">Marca</label><input type="text" name="marca" class="form-control"></div>

                            <hr class="my-4">

                            <div class="col-12 col-md-6">
                                <label class="form-label">Tipo Cliente *</label>
                                <select name="tipo_cliente" id="tipoCliente" class="form-select" onchange="toggleRF3()" required>
                                    <option value="">-- Seleccionar --</option>
                                    <option value="SEGURO">Compañía de Seguros</option>
                                    <option value="PARTICULAR">Particular</option>
                                </select>
                            </div>

                            <div id="bloqueSeguro" class="col-12 row mt-3 p-3 border rounded bg-light ms-1" style="display:none;">
                                <div class="col-12 col-md-6 mb-2"><label>Compañía *</label><input type="text" name="compania" class="form-control"></div>
                                <div class="col-12 col-md-6"><label>N° Siniestro *</label><input type="text" name="nro_siniestro" class="form-control"></div>
                            </div>

                            <div id="bloqueParticular" class="col-12 mt-3 p-3 border rounded bg-light ms-1" style="display:none;">
                                <div class="alert alert-warning mb-2 small">Abono del 60% requerido.</div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="abono_pagado" value="1" id="abonoCheck">
                                    <label class="form-check-label" for="abonoCheck">¿Abono Pagado?</label>
                                </div>
                            </div>

                            <div class="col-12 mt-4 text-end">
                                <button type="submit" class="btn btn-primary btn-lg w-100 w-md-auto">Registrar</button>
                            </div>
                        </div>
                    </form>
                </div>
            </main>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function toggleRF3() {
        var tipo = document.getElementById("tipoCliente").value;
        var divSeguro = document.getElementById("bloqueSeguro");
        var divPart = document.getElementById("bloqueParticular");
        var inputCia = document.querySelector('[name="compania"]');
        var inputSin = document.querySelector('[name="nro_siniestro"]');

        if (tipo === "SEGURO") {
            divSeguro.style.display = "flex"; divPart.style.display = "none";
            inputCia.required = true; inputSin.required = true;
        } else if (tipo === "PARTICULAR") {
            divSeguro.style.display = "none"; divPart.style.display = "block";
            inputCia.required = false; inputSin.required = false;
        } else {
            divSeguro.style.display = "none"; divPart.style.display = "none";
            inputCia.required = false; inputSin.required = false;
        }
    }
    </script>
</body>

</html>
