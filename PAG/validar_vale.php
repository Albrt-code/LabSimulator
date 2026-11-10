<?php
session_start();

// Control de Seguridad: Permitido para Docentes (Rol 2) y Administradores (Rol 1)
if (!isset($_SESSION['boleta']) || ($_SESSION['id_rol'] != 2 && $_SESSION['id_rol'] != 1)) {
    header("Location: ../index.php");
    exit();
}

include '../conexion/conexion.php';

$boleta_docente = $_SESSION['boleta'];
$id_vale = isset($_GET['id_vale']) ? intval($_GET['id_vale']) : 0;
$accion = isset($_GET['accion']) ? trim($_GET['accion']) : '';

if ($id_vale <= 0 || !in_array($accion, ['aprobar', 'rechazar'])) {
    header("Location: docente.php?msg=Parametros_invalidos");
    exit();
}

// Determinar el nuevo estado
$nuevo_estado = ($accion === 'aprobar') ? 'Validado_Docente' : 'Rechazado';

// Actualizar el estado del vale verificando que la solicitud corresponda a la boleta del docente
$sql_update = "UPDATE vales_prestamo 
               SET estado = ?, boleta_docente = ? 
               WHERE id_vale = ? AND (boleta_docente = ? OR ? = 1)";

$stmt = mysqli_prepare($conexion, $sql_update);
$es_admin = ($_SESSION['id_rol'] == 1) ? 1 : 0;
mysqli_stmt_bind_param($stmt, "ssisi", $nuevo_estado, $boleta_docente, $id_vale, $boleta_docente, $es_admin);

if (mysqli_stmt_execute($stmt) && mysqli_stmt_affected_rows($stmt) > 0) {
    
    // Registrar la acción en la tabla logs_sistema para auditoría
    $accion_log = ($accion === 'aprobar') ? 'Firma Digital Aprobada' : 'Solicitud Rechazada';
    $detalles_log = "Vale ID: $id_vale procesado por el docente/admin con Boleta: $boleta_docente";
    
    $sql_log = "INSERT INTO logs_sistema (boleta_usuario, accion, detalles) VALUES (?, ?, ?)";
    $stmt_log = mysqli_prepare($conexion, $sql_log);
    mysqli_stmt_bind_param($stmt_log, "sss", $boleta_docente, $accion_log, $detalles_log);
    mysqli_stmt_execute($stmt_log);
    mysqli_stmt_close($stmt_log);

    header("Location: docente.php?msg=Vale_actualizado_exitosamente");
} else {
    header("Location: docente.php?msg=Error_al_procesar_el_vale");
}

mysqli_stmt_close($stmt);
mysqli_close($conexion);
exit();
?>