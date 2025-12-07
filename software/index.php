<?php
session_start();
require 'config/conexion.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $pass = $_POST['password'];

    $stmt = $conn->prepare("SELECT ID_USUARIO, NOMBRE_COMPLETO, PASSWORD, ESTADO FROM USUARIOS WHERE EMAIL = :email");
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Lógica original conservada
    if ($user && $pass == $user['PASSWORD'] && $user['ESTADO'] == 'ACTIVA') {
        $_SESSION['user_id'] = $user['ID_USUARIO'];
        $_SESSION['user_name'] = $user['NOMBRE_COMPLETO'];
        header("Location: dashboard.php");
        exit;
    } else {
        $error = "Credenciales incorrectas o cuenta inactiva.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <title>Login SIGSAK</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background:#eee; display:flex; justify-content:center; align-items:center; height:100vh; padding: 15px; }
        /* Ajuste responsive: máximo 400px, pero en pantallas chicas ocupa el 100% */
        .card-login { width: 100%; max-width: 400px; }
    </style>
</head>
<body>
    <div class="card p-4 card-login shadow-sm">
        <h3 class="text-center mb-3">KOVACS SIGSAK</h3>
        <?php if(isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>
        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <button class="btn btn-dark w-100 py-2">Ingresar</button>
        </form>
    </div>
</body>
</html>