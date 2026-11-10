<?php
session_start();

// Control de seguridad: Docentes y Administradores
if (!isset($_SESSION['boleta']) || ($_SESSION['id_rol'] != 2 && $_SESSION['id_rol'] != 1)) {
    header("Location: ../index.php");
    exit();
}

include '../conexion/conexion.php';
include '../EXTEND/header.php';

$id_agenda = isset($_GET['id_agenda']) ? intval($_GET['id_agenda']) : (isset($_POST['id_agenda']) ? intval($_POST['id_agenda']) : 0);
$mensaje = "";

// 1. PROCESAR ACCIONES (AGREGAR O ELIMINAR MATERIAL DE LA PLANTILLA)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Agregar o Actualizar Ítem en la plantilla
    if (isset($_POST['agregar_recurso'])) {
        $id_recurso = intval($_POST['id_recurso']);
        $cantidad = intval($_POST['cantidad']);

        if ($id_agenda > 0 && $id_recurso > 0 && $cantidad > 0) {
            // Verificar si el recurso ya existe en la plantilla de esta clase
            $sql_check = "SELECT id_plantilla FROM plantilla_materiales_agenda WHERE id_agenda = ? AND id_recurso = ?";
            $stmt_c = mysqli_prepare($conexion, $sql_check);
            mysqli_stmt_bind_param($stmt_c, "ii", $id_agenda, $id_recurso);
            mysqli_stmt_execute($stmt_c);
            $res_c = mysqli_stmt_get_result($stmt_c);

            if (mysqli_num_rows($res_c) > 0) {
                // Actualizar cantidad
                $sql_u = "UPDATE plantilla_materiales_agenda SET cantidad_sugerida = ? WHERE id_agenda = ? AND id_recurso = ?";
                $stmt_u = mysqli_prepare($conexion, $sql_u);
                mysqli_stmt_bind_param($stmt_u, "iii", $cantidad, $id_agenda, $id_recurso);
                mysqli_stmt_execute($stmt_u);
            } else {
                // Insertar nuevo ítem
                $sql_i = "INSERT INTO plantilla_materiales_agenda (id_agenda, id_recurso, cantidad_sugerida) VALUES (?, ?, ?)";
                $stmt_i = mysqli_prepare($conexion, $sql_i);
                mysqli_stmt_bind_param($stmt_i, "iii", $id_agenda, $id_recurso, $cantidad);
                mysqli_stmt_execute($stmt_i);
            }
            $mensaje = "<div style='background:#dcfce7; color:#15803d; padding:10px; border-radius:5px; margin-bottom:15px;'>Material guardado en la lista de la práctica.</div>";
        }
    }

    // Eliminar Ítem de la plantilla
    if (isset($_POST['eliminar_plantilla_id'])) {
        $id_plantilla = intval($_POST['eliminar_plantilla_id']);
        $sql_del = "DELETE FROM plantilla_materiales_agenda WHERE id_plantilla = ?";
        $stmt_d = mysqli_prepare($conexion, $sql_del);
        mysqli_stmt_bind_param($stmt_d, "i", $id_plantilla);
        mysqli_stmt_execute($stmt_d);
        $mensaje = "<div style='background:#fee2e2; color:#b91c1c; padding:10px; border-radius:5px; margin-bottom:15px;'>Ítem removido de la lista.</div>";
    }
}

// 2. CONSULTAR DATOS DE LA PRÁCTICA PROGRAMADA
$sql_agenda = "SELECT ag.id_agenda, ag.fecha_programada, ag.aula_laboratorio, p.titulo AS practica_titulo, s.clave_secuencia, a.nombre AS asignatura
               FROM agenda_practicas ag
               INNER JOIN practicas p ON ag.id_practica = p.id_practica
               INNER JOIN secuencias s ON ag.id_secuencia = s.id_secuencia
               INNER JOIN asignaturas a ON s.id_asignatura = a.id_asignatura
               WHERE ag.id_agenda = ?";
$stmt_ag = mysqli_prepare($conexion, $sql_agenda);
mysqli_stmt_bind_param($stmt_ag, "i", $id_agenda);
mysqli_stmt_execute($stmt_ag);
$info_practica = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_ag));

if (!$info_practica) {
    echo "<main style='padding:40px;'><p>Práctica no encontrada.</p></main>";
    include '../EXTEND/footer.php';
    exit();
}

// 3. CONSULTAR LA LISTA DE MATERIALES ASIGNADOS ACTUALMENTE
$sql_lista = "SELECT pma.id_plantilla, pma.cantidad_sugerida, rm.id_recurso, rm.nombre, rm.nic, rm.especificaciones 
              FROM plantilla_materiales_agenda pma
              INNER JOIN recursos_materiales rm ON pma.id_recurso = rm.id_recurso
              WHERE pma.id_agenda = ?";
$stmt_l = mysqli_prepare($conexion, $sql_lista);
mysqli_stmt_bind_param($stmt_l, "i", $id_agenda);
mysqli_stmt_execute($stmt_l);
$materiales_asignados = mysqli_stmt_get_result($stmt_l);

// 4. CONSULTAR TODO EL INVENTARIO PARA EL MENÚ DESPLEGABLE
$sql_inv = "SELECT id_recurso, nombre, nic, stock_disponible, especificaciones FROM recursos_materiales ORDER BY nombre ASC";
$res_inventario = mysqli_query($conexion, $sql_inv);
?>

<main class="main-content" style="padding: 30px 20px; background-color: #f8f9fa; min-height: calc(100vh - 350px);">
  <div class="container" style="max-width: 1100px; margin: 0 auto;">
    
    <!-- ENCABEZADO -->
    <div style="background: white; border-left: 5px solid #6c1d45; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); margin-bottom: 25px;">
      <h2 style="color: #6c1d45; margin: 0 0 5px 0;">Personalización de Lista de Materiales</h2>
      <p style="margin: 0; color: #4b5563;">
        Secuencia: <strong><?php echo htmlspecialchars($info_practica['clave_secuencia']); ?></strong> | 
        Práctica: <strong><?php echo htmlspecialchars($info_practica['practica_titulo']); ?></strong> | 
        Aula: <strong><?php echo htmlspecialchars($info_practica['aula_laboratorio']); ?></strong>
      </p>
    </div>

    <?php echo $mensaje; ?>

    <!-- FORMULARIO PARA AGREGAR MATERIAL DEL INVENTARIO GENERAL -->
    <section style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); margin-bottom: 25px;">
      <h3 style="margin: 0 0 15px 0; color: #1f2937;">➕ Agregar Material a la Lista de la Práctica</h3>
      <form action="editar_materiales_practica.php" method="POST" style="display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 15px; align-items: end;">
        <input type="hidden" name="id_agenda" value="<?php echo $id_agenda; ?>">
        <input type="hidden" name="agregar_recurso" value="1">

        <div>
          <label style="display:block; font-size:0.85rem; font-weight:bold; margin-bottom:5px;">Seleccionar del Inventario:</label>
          <select name="id_recurso" required style="width:100%; padding:9px; border:1px solid #ccc; border-radius:4px;">
            <option value="">-- Seleccionar Recurso --</option>
            <?php while ($inv = mysqli_fetch_assoc($res_inventario)): ?>
              <?php 
                $spec = json_decode($inv['especificaciones'], true);
                $det = is_array($spec) ? implode(', ', array_map(fn($k, $v) => "$k: $v", array_keys($spec), $spec)) : '';
              ?>
              <option value="<?php echo $inv['id_recurso']; ?>">
                <?php echo htmlspecialchars($inv['nombre']); ?> 
                <?php echo $inv['nic'] ? "(".htmlspecialchars($inv['nic']).")" : ""; ?>
                <?php echo $det ? " - [$det]" : ""; ?>
                (Disp: <?php echo $inv['stock_disponible']; ?>)
              </option>
            <?php endwhile; ?>
          </select>
        </div>

        <div>
          <label style="display:block; font-size:0.85rem; font-weight:bold; margin-bottom:5px;">Cantidad Sugerida:</label>
          <input type="number" name="cantidad" value="1" min="1" required style="width:100%; padding:8px; border:1px solid #ccc; border-radius:4px;">
        </div>

        <div>
          <button type="submit" style="background:#6c1d45; color:white; padding:10px 15px; border:none; border-radius:4px; font-weight:bold; cursor:pointer; width:100%;">
            ➕ Añadir a la Lista
          </button>
        </div>
      </form>
    </section>

    <!-- TABLA DE MATERIALES REQUERIDOS PUBLICADOS PARA EL ALUMNO -->
    <section style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
      <h3 style="margin: 0 0 15px 0; color: #1f2937;">📋 Lista de Materiales Configurada para esta Sesión</h3>
      <table style="width: 100%; border-collapse: collapse; font-size: 0.9rem;">
        <thead>
          <tr style="background-color: #6c1d45; color: white;">
            <th style="padding: 10px; text-align: left;">Cant.</th>
            <th style="padding: 10px; text-align: left;">Nombre del Material</th>
            <th style="padding: 10px; text-align: left;">NIC / Inventario</th>
            <th style="padding: 10px; text-align: left;">Especificaciones (JSON)</th>
            <th style="padding: 10px; text-align: center;">Acción</th>
          </tr>
        </thead>
        <tbody>
          <?php if (mysqli_num_rows($materiales_asignados) > 0): ?>
            <?php while ($mat = mysqli_fetch_assoc($materiales_asignados)): ?>
              <tr style="border-bottom: 1px solid #eee;">
                <td style="padding: 10px;"><strong><?php echo $mat['cantidad_sugerida']; ?></strong></td>
                <td style="padding: 10px;"><?php echo htmlspecialchars($mat['nombre']); ?></td>
                <td style="padding: 10px;"><?php echo htmlspecialchars($mat['nic'] ?? 'N/A'); ?></td>
                <td style="padding: 10px; font-size: 0.85rem; color: #555;">
                  <?php 
                    $json_data = json_decode($mat['especificaciones'], true);
                    if (is_array($json_data)) {
                        foreach ($json_data as $key => $val) {
                            echo "<div><strong>".htmlspecialchars($key).":</strong> ".htmlspecialchars($val)."</div>";
                        }
                    } else {
                        echo "N/A";
                    }
                  ?>
                </td>
                <td style="padding: 10px; text-align: center;">
                  <form action="editar_materiales_practica.php" method="POST" style="margin:0;">
                    <input type="hidden" name="id_agenda" value="<?php echo $id_agenda; ?>">
                    <input type="hidden" name="eliminar_plantilla_id" value="<?php echo $mat['id_plantilla']; ?>">
                    <button type="submit" onclick="return confirm('¿Quitar este material de la lista?');" style="background:#b91c1c; color:white; border:none; padding:5px 10px; border-radius:4px; font-size:0.8rem; cursor:pointer;">
                      🗑️ Quitar
                    </button>
                  </form>
                </td>
              </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr>
              <td colspan="5" style="padding: 15px; text-align: center; color: #6b7280;">No hay materiales personalizados asignados aún para esta clase.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>

      <div style="margin-top: 20px;">
        <a href="docente.php" style="background: #0c231e; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; font-size: 0.9rem; font-weight: bold; display: inline-block;">
          ⬅️ Volver al Panel Docente
        </a>
      </div>
    </section>

  </div>
</main>

<?php
include '../EXTEND/footer.php';
?>