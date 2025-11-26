<?php
$host = 'localhost'; // IP de tu servidor Oracle
$port = '1521';
$sid = 'xe'; // Tu Service Name (xe, orcl, etc)
$username = 'user_developer'; // TU USUARIO ORACLE
$password = '123456';    // TU CLAVE ORACLE

$tns = "(DESCRIPTION = (ADDRESS = (PROTOCOL = TCP)(HOST = $host)(PORT = $port)) (CONNECT_DATA = (SERVICE_NAME = $sid) (SID = $sid)))";

try {
    $conn = new PDO("oci:dbname=" . $tns . ";charset=utf8", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Error de conexión a Oracle: " . $e->getMessage());
}
?>