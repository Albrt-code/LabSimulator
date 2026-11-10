<?php
$host = "localhost";
$user = "root";
$password = ""; // En WampServer la contraseña de root suele ser vacía por defecto
$database = "lab_simulator";

$conexion = mysqli_connect($host, $user, $password, $database);

if (!$conexion) {
    die("Error de conexión a la base de datos: " . mysqli_connect_error());
}

// Configurar codificación de caracteres para acentos y eñes
mysqli_set_charset($conexion, "utf8mb4");
?>