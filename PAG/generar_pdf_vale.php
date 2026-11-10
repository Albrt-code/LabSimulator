<?php
session_start();

// Control de seguridad
if (!isset($_SESSION['boleta'])) {
    die("Acceso denegado. Inicie sesión.");
}

include '../conexion/conexion.php';

$id_vale = isset($_GET['id_vale']) ? intval($_GET['id_vale']) : 0;

// Consulta de los datos del vale, alumno, secuencia y práctica
$sql = "SELECT v.folio, v.estado, v.fecha_solicitud, 
               u_al.boleta_num_empleado AS boleta_alumno, CONCAT(u_al.nombre, ' ', u_al.apellidos) AS alumno_nombre,
               u_doc.nombre AS docente_nombre, u_doc.apellidos AS docente_apellidos,
               s.clave_secuencia, a.nombre AS asignatura_nombre,
               p.titulo AS practica_titulo, ag.aula_laboratorio, ag.fecha_programada, ag.hora_inicio, ag.hora_fin
        FROM vales_prestamo v
        INNER JOIN usuarios u_al ON v.boleta_alumno = u_al.boleta_num_empleado
        INNER JOIN usuarios u_doc ON v.boleta_docente = u_doc.boleta_num_empleado
        INNER JOIN agenda_practicas ag ON v.id_agenda = ag.id_agenda
        INNER JOIN practicas p ON ag.id_practica = p.id_practica
        INNER JOIN secuencias s ON ag.id_secuencia = s.id_secuencia
        INNER JOIN asignaturas a ON s.id_asignatura = a.id_asignatura
        WHERE v.id_vale = ? AND v.boleta_alumno = ?";

$stmt = mysqli_prepare($conexion, $sql);
mysqli_stmt_bind_param($stmt, "is", $id_vale, $_SESSION['boleta']);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$vale = mysqli_fetch_assoc($res);

if (!$vale) {
    die("El vale no existe o no corresponde a sus registros.");
}

// Consulta de materiales asociados al vale
$sql_detalles = "SELECT r.nombre, r.nic, d.cantidad 
                 FROM detalle_vale_recursos d
                 INNER JOIN recursos_materiales r ON d.id_recurso = r.id_recurso
                 WHERE d.id_vale = ?";
$stmt_det = mysqli_prepare($conexion, $sql_detalles);
mysqli_stmt_bind_param($stmt_det, "i", $id_vale);
mysqli_stmt_execute($stmt_det);
$materiales = mysqli_stmt_get_result($stmt_det);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Vale de Almacén - Folio <?php echo htmlspecialchars($vale['folio']); ?></title>
  <style>
    body { font-family: Arial, sans-serif; margin: 30px; color: #333; }
    .header-table { width: 100%; border-bottom: 3px solid #6c1d45; padding-bottom: 10px; margin-bottom: 20px; }
    .title-head { text-align: center; font-weight: bold; color: #6c1d45; }
    .info-box { width: 100%; border: 1px solid #ccc; padding: 10px; margin-bottom: 20px; border-radius: 5px; background: #fafafa; }
    .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; font-size: 0.9rem; }
    table.data-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    table.data-table th, table.data-table td { border: 1px solid #b38e5d; padding: 8px; text-align: left; font-size: 0.85rem; }
    table.data-table th { background-color: #6c1d45; color: white; }
    .signatures { margin-top: 50px; display: flex; justify-content: space-around; text-align: center; font-size: 0.85rem; }
    .signature-line { border-top: 1px solid #333; width: 200px; margin-top: 40px; padding-top: 5px; }
    @media print { .no-print { display: none; } }
  </style>
</head>
<body>

  <div class="no-print" style="margin-bottom: 20px;">
    <button onclick="window.print();" style="background: #6c1d45; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer;">🖨️ Imprimir / Guardar en PDF</button>
  </div>

  <table class="header-table">
    <tr>
      <td style="width: 20%; text-align: center;"><strong>IPN / UPIICSA</strong></td>
      <td class="title-head">
        INSTITUTO POLITÉCNICO NACIONAL<br>
        LABORATORIO DE FÍSICA EXPERIMENTAL<br>
        <span style="font-size: 0.9rem; color: #555;">VALE DIGITAL DE ALMACÉN</span>
      </td>
      <td style="width: 25%; text-align: right; font-size: 0.9rem; color: #6c1d45;">
        <strong>FOLIO: <?php echo htmlspecialchars($vale['folio']); ?></strong><br>
        Estado: <strong><?php echo htmlspecialchars($vale['estado']); ?></strong>
      </td>
    </tr>
  </table>

  <div class="info-box">
    <div class="info-grid">
      <div><strong>Alumno:</strong> <?php echo htmlspecialchars($vale['alumno_nombre']); ?></div>
      <div><strong>Boleta:</strong> <?php echo htmlspecialchars($vale['boleta_alumno']); ?></div>
      <div><strong>Asignatura:</strong> <?php echo htmlspecialchars($vale['asignatura_nombre']); ?></div>
      <div><strong>Secuencia / Grupo:</strong> <?php echo htmlspecialchars($vale['clave_secuencia']); ?></div>
      <div><strong>Docente Titular:</strong> <?php echo htmlspecialchars($vale['docente_nombre'] . ' ' . $vale['docente_apellidos']); ?></div>
      <div><strong>Aula / Lab:</strong> <?php echo htmlspecialchars($vale['aula_laboratorio']); ?></div>
      <div><strong>Práctica:</strong> <?php echo htmlspecialchars($vale['practica_titulo']); ?></div>
      <div><strong>Fecha de la Clase:</strong> <?php echo htmlspecialchars($vale['fecha_programada']); ?> (<?php echo htmlspecialchars($vale['hora_inicio'] . ' - ' . $vale['hora_fin']); ?>)</div>
    </div>
  </div>

  <h4>Materiales y Equipos Asignados</h4>
  <table class="data-table">
    <thead>
      <tr>
        <th>Cant.</th>
        <th>Descripción del Recurso</th>
        <th>NIC / No. Inventario IPN</th>
      </tr>
    </thead>
    <tbody>
      <?php while ($m = mysqli_fetch_assoc($materiales)): ?>
        <tr>
          <td><?php echo $m['cantidad']; ?></td>
          <td><?php echo htmlspecialchars($m['nombre']); ?></td>
          <td><?php echo htmlspecialchars($m['nic'] ?? 'N/A'); ?></td>
        </tr>
      <?php endwhile; ?>
    </tbody>
  </table>

  <div class="signatures">
    <div>
      <div class="signature-line">Firma del Alumno</div>
      <small><?php echo htmlspecialchars($vale['alumno_nombre']); ?></small>
    </div>
    <div>
      <div class="signature-line">Firma y Sello Docente</div>
      <small>Validado Digitalmente</small>
    </div>
    <div>
      <div class="signature-line">Surtido Almacén</div>
      <small>Firma / Sello Despacho</small>
    </div>
  </div>

</body>
</html>