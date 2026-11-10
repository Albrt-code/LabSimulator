<?php
// 1. Iniciar o reanudar la sesión existente
session_start();

// 2. Control de seguridad: Si no hay sesión iniciada, redirigir al Login
if (!isset($_SESSION['boleta'])) {
    header("Location: ../index.php");
    exit();
}

// 3. Control de roles: Si ingresa un Alumno (3) o Docente (2), enviarlo a su panel usr.php
if ($_SESSION['id_rol'] != 1 && $_SESSION['id_rol'] != 4) {
    header("Location: usr.php");
    exit();
}

// 4. Cargar la conexión SQL y la cabecera visual
include '../conexion/conexion.php';
include '../EXTEND/header.php';

$boleta_despachador = $_SESSION['boleta'];
$prefijo_despachador = $_SESSION['prefijo'] ?? 'D';

// Procesar Despacho de Vale (Cambio de estado a "Entregado")
if (isset($_GET['accion']) && $_GET['accion'] == 'despachar' && isset($_GET['id_vale'])) {
    $id_vale_despachar = intval($_GET['id_vale']);
    
    // Actualizar estado a Entregado y registrar al despachador que entrega físicamente
    $sql_despacho = "UPDATE vales_prestamo 
                     SET estado = 'Entregado', boleta_despachador = ?, fecha_entrega = NOW() 
                     WHERE id_vale = ? AND estado = 'Validado_Docente'";
    $stmt_d = mysqli_prepare($conexion, $sql_despacho);
    mysqli_stmt_bind_param($stmt_d, "si", $boleta_despachador, $id_vale_despachar);
    
    if (mysqli_stmt_execute($stmt_d) && mysqli_stmt_affected_rows($stmt_d) > 0) {
        // Registrar en logs_sistema
        $log_det = "Vale ID: $id_vale_despachar despachado físicamente por despachador $boleta_despachador (Prefijo: $prefijo_despachador)";
        $sql_l = "INSERT INTO logs_sistema (boleta_usuario, accion, detalles) VALUES (?, 'Despacho de Vale', ?)";
        $stmt_l = mysqli_prepare($conexion, $sql_l);
        mysqli_stmt_bind_param($stmt_l, "ss", $boleta_despachador, $log_det);
        mysqli_stmt_execute($stmt_l);
        
        $msg_exito = "El vale ha sido despachado correctamente y el stock fue actualizado.";
    }
}

// Consulta 1: Vales listos para despachar (Estado: Validado_Docente o Solicitado)
$sql_vales = "SELECT v.id_vale, v.folio, v.estado, v.fecha_solicitud,
                     u_al.boleta_num_empleado AS boleta_alumno, CONCAT(u_al.nombre, ' ', u_al.apellidos) AS alumno_nombre,
                     u_doc.nombre AS docente_nombre,
                     s.clave_secuencia, p.titulo AS practica_titulo, ag.aula_laboratorio
              FROM vales_prestamo v
              INNER JOIN usuarios u_al ON v.boleta_alumno = u_al.boleta_num_empleado
              INNER JOIN usuarios u_doc ON v.boleta_docente = u_doc.boleta_num_empleado
              INNER JOIN agenda_practicas ag ON v.id_agenda = ag.id_agenda
              INNER JOIN practicas p ON ag.id_practica = p.id_practica
              INNER JOIN secuencias s ON ag.id_secuencia = s.id_secuencia
              WHERE v.estado IN ('Solicitado', 'Validado_Docente', 'Entregado')
              ORDER BY v.fecha_solicitud DESC";
$res_vales = mysqli_query($conexion, $sql_vales);

// Consulta 2: Resumen del Inventario de Materiales
$sql_inventario = "SELECT id_recurso, nombre, nic, stock_total, stock_disponible, especificaciones 
                   FROM recursos_materiales 
                   ORDER BY nombre ASC";
$res_inventario = mysqli_query($conexion, $sql_inventario);
?>

<main class="main-content" style="padding: 30px 20px; background-color: #f8f9fa; min-height: calc(100vh - 350px);">
  <div class="container" style="max-width: 1200px; margin: 0 auto;">
    
    <!-- ENCABEZADO Y MENSAJE DE BIENVENIDA -->
    <div style="background: white; border-left: 6px solid #6c1d45; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.06); margin-bottom: 25px;">
      <h2 style="color: #6c1d45; margin: 0 0 5px 0;">Panel de Administración y Almacén</h2>
      <p style="margin: 0; color: #4b5563;">
        Bienvenido(a), <strong><?php echo htmlspecialchars($_SESSION['nombre']); ?></strong> 
        (No. Empleado: <strong><?php echo htmlspecialchars($_SESSION['boleta']); ?></strong>) | 
        Rol: <strong><?php echo ($_SESSION['id_rol'] == 1) ? 'Administrador' : 'Almacenista'; ?></strong>
      </p>

      <!-- Badge indicador del prefijo de folio asignado -->
      <div style="margin-top: 12px; display: inline-block; background-color: #f3f4f6; padding: 6px 14px; border-radius: 6px; border-left: 4px solid #b38e5d; font-size: 0.88rem;">
        <span>Prefijo Oficial de Despacho: <strong style="color: #6c1d45; font-size: 1rem;"><?php echo htmlspecialchars($prefijo_despachador); ?></strong></span>
      </div>
    </div>

    <?php if (isset($msg_exito)): ?>
      <div style="background: #dcfce7; color: #15803d; padding: 12px; border-radius: 6px; margin-bottom: 20px; font-weight: bold;">
        ✅ <?php echo $msg_exito; ?>
      </div>
    <?php endif; ?>

    <!-- TARJETAS DE MÓDULOS DE CONTROL -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; margin-bottom: 30px;">
      <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); border-top: 4px solid #6c1d45;">
        <h3 style="margin: 0 0 8px 0; color: #1f2937;">📋 Vales y Préstamos</h3>
        <p style="margin: 0; font-size: 0.88rem; color: #6b7280;">Revisar, autorizar la entrega física y procesar devoluciones.</p>
      </div>

      <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); border-top: 4px solid #b38e5d;">
        <h3 style="margin: 0 0 8px 0; color: #1f2937;">📦 Inventario de Equipos</h3>
        <p style="margin: 0; font-size: 0.88rem; color: #6b7280;">Existencias en tiempo real y especificaciones técnicas en JSON.</p>
      </div>

      <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); border-top: 4px solid #0c231e;">
        <h3 style="margin: 0 0 8px 0; color: #1f2937;">⚙️ Auditoría de Logs</h3>
        <p style="margin: 0; font-size: 0.88rem; color: #6b7280;">Supervisión de operaciones realizadas en el laboratorio.</p>
      </div>
    </div>

    <!-- SECCIÓN 1: SOLICITUDES DE VALES DE ALMACÉN EN ESPERA -->
    <section style="background: white; padding: 22px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.06); margin-bottom: 30px;">
      <h3 style="margin: 0 0 15px 0; color: #1f2937; font-size: 1.2rem;">📋 Solicitudes de Vales de Almacén</h3>
      
      <div style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse; font-size: 0.88rem; text-align: left;">
          <thead>
            <tr style="background-color: #6c1d45; color: white;">
              <th style="padding: 10px 12px;">Folio</th>
              <th style="padding: 10px 12px;">Alumno / Boleta</th>
              <th style="padding: 10px 12px;">Práctica y Secuencia</th>
              <th style="padding: 10px 12px;">Docente Autoriza</th>
              <th style="padding: 10px 12px;">Estatus</th>
              <th style="padding: 10px 12px; text-align: center;">Acción de Almacén</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($res_vales && mysqli_num_rows($res_vales) > 0): ?>
              <?php while ($v = mysqli_fetch_assoc($res_vales)): ?>
                <tr style="border-bottom: 1px solid #e5e7eb;">
                  <td style="padding: 10px 12px;"><strong><?php echo htmlspecialchars($v['folio']); ?></strong></td>
                  <td style="padding: 10px 12px;">
                    <?php echo htmlspecialchars($v['alumno_nombre']); ?><br>
                    <small style="color: #6b7280;">Boleta: <?php echo htmlspecialchars($v['boleta_alumno']); ?></small>
                  </td>
                  <td style="padding: 10px 12px;">
                    <strong><?php echo htmlspecialchars($v['practica_titulo']); ?></strong><br>
                    <small style="color: #6b7280;">Secuencia: <?php echo htmlspecialchars($v['clave_secuencia']); ?> (<?php echo htmlspecialchars($v['aula_laboratorio']); ?>)</small>
                  </td>
                  <td style="padding: 10px 12px;"><?php echo htmlspecialchars($v['docente_nombre']); ?></td>
                  <td style="padding: 10px 12px;">
                    <?php 
                      $e = $v['estado'];
                      $bg = '#fef3c7'; $tx = '#b45309'; // Solicitado (Amarillo)
                      if ($e == 'Validado_Docente') { $bg = '#dbeafe'; $tx = '#1d4ed8'; } // Azul
                      elseif ($e == 'Entregado') { $bg = '#dcfce7'; $tx = '#15803d'; }   // Verde
                    ?>
                    <span style="background: <?php echo $bg; ?>; color: <?php echo $tx; ?>; padding: 4px 8px; border-radius: 10px; font-weight: bold; font-size: 0.78rem;">
                      <?php echo htmlspecialchars($e); ?>
                    </span>
                  </td>
                  <td style="padding: 10px 12px; text-align: center;">
                    <?php if ($v['estado'] == 'Validado_Docente'): ?>
                      <a href="adm.php?accion=despachar&id_vale=<?php echo $v['id_vale']; ?>" style="background: #15803d; color: white; padding: 5px 10px; border-radius: 4px; text-decoration: none; font-size: 0.8rem; font-weight: bold;">
                        📦 Despachar Material
                      </a>
                    <?php elseif ($v['estado'] == 'Entregado'): ?>
                      <span style="color: #6b7280; font-size: 0.8rem;">Entregado en Almacén</span>
                    <?php else: ?>
                      <span style="color: #b45309; font-size: 0.8rem;">Pendiente Firma Docente</span>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endwhile; ?>
            <?php else: ?>
              <tr>
                <td colspan="6" style="padding: 15px; text-align: center; color: #6b7280;">No hay solicitudes registradas por el momento.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </section>

    <!-- SECCIÓN 2: CONTROL DE INVENTARIO Y DISPONIBILIDAD -->
    <section style="background: white; padding: 22px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.06);">
      <h3 style="margin: 0 0 15px 0; color: #1f2937; font-size: 1.2rem;">📦 Existencias de Inventario de Laboratorio</h3>
      
      <div style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse; font-size: 0.88rem; text-align: left;">
          <thead>
            <tr style="background-color: #b38e5d; color: white;">
              <th style="padding: 10px 12px;">ID / NIC</th>
              <th style="padding: 10px 12px;">Nombre del Aparato / Equipo</th>
              <th style="padding: 10px 12px;">Especificaciones (JSON)</th>
              <th style="padding: 10px 12px;">Stock Total</th>
              <th style="padding: 10px 12px;">Stock Disponible</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($res_inventario && mysqli_num_rows($res_inventario) > 0): ?>
              <?php while ($item = mysqli_fetch_assoc($res_inventario)): ?>
                <tr style="border-bottom: 1px solid #e5e7eb;">
                  <td style="padding: 10px 12px;">
                    <strong><?php echo $item['id_recurso']; ?></strong>
                    <br><small style="color: #6b7280;"><?php echo htmlspecialchars($item['nic'] ?? 'N/A'); ?></small>
                  </td>
                  <td style="padding: 10px 12px;"><strong><?php echo htmlspecialchars($item['nombre']); ?></strong></td>
                  <td style="padding: 10px 12px; font-size: 0.82rem; color: #4b5563;">
                    <?php 
                      $json = json_decode($item['especificaciones'], true);
                      if (is_array($json)) {
                          foreach ($json as $k => $val) {
                              echo "<div><strong>".htmlspecialchars($k).":</strong> ".htmlspecialchars($val)."</div>";
                          }
                      } else {
                          echo "Sin datos técnicos";
                      }
                    ?>
                  </td>
                  <td style="padding: 10px 12px;"><?php echo $item['stock_total']; ?> pzs</td>
                  <td style="padding: 10px 12px;">
                    <strong style="color: <?php echo ($item['stock_disponible'] > 0) ? '#15803d' : '#b91c1c'; ?>;">
                      <?php echo $item['stock_disponible']; ?> pzs
                    </strong>
                  </td>
                </tr>
              <?php endwhile; ?>
            <?php else: ?>
              <tr>
                <td colspan="5" style="padding: 15px; text-align: center; color: #6b7280;">No hay materiales registrados en el inventario.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </section>

  </div>
</main>

<?php
// 5. Cargar el pie de página modular
include '../EXTEND/footer.php';
?>