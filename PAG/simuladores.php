<?php
session_start();

if (!isset($_SESSION['boleta'])) {
    header("Location: ../index.php");
    exit();
}

include '../conexion/conexion.php';
include '../EXTEND/header.php';

$filtro = trim($_GET['buscar'] ?? '');
$filtro_parcial = intval($_GET['parcial'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['asignar_parcial'])
    && in_array($_SESSION['id_rol'], array(1, 2), true)) {
    $id_practica = intval($_POST['id_practica'] ?? 0);
    $nuevo_parcial = intval($_POST['num_parcial'] ?? 0);

    if ($id_practica > 0 && $nuevo_parcial >= 1 && $nuevo_parcial <= 3) {
        $stmt_parcial = mysqli_prepare(
            $conexion,
            "UPDATE practicas SET num_parcial = ? WHERE id_practica = ?"
        );
        mysqli_stmt_bind_param($stmt_parcial, "ii", $nuevo_parcial, $id_practica);
        mysqli_stmt_execute($stmt_parcial);
        mysqli_stmt_close($stmt_parcial);

        $parametros = array();
        if ($filtro !== '') {
            $parametros[] = 'buscar=' . urlencode($filtro);
        }
        if ($filtro_parcial >= 1 && $filtro_parcial <= 3) {
            $parametros[] = 'parcial=' . $filtro_parcial;
        }

        $parametros[] = 'mensaje=parcial_actualizado';
        header('Location: simuladores.php?' . implode('&', $parametros));
        exit();
    }
}

$where = array();

if ($filtro !== '') {
    $where[] = "(p.titulo LIKE ? OR p.descripcion LIKE ? OR a.nombre LIKE ?)";
}

if ($filtro_parcial >= 1 && $filtro_parcial <= 3) {
    $where[] = "p.num_parcial = ?";
}

$sql = "SELECT p.id_practica, p.titulo, p.descripcion, p.tipo_simulador, p.num_parcial, p.guia_pdf,
               a.id_asignatura, a.nombre AS asignatura_nombre
        FROM practicas p
        INNER JOIN asignaturas a ON p.id_asignatura = a.id_asignatura";

if (count($where) > 0) {
    $sql .= " WHERE " . implode(" AND ", $where);
}

$sql .= " ORDER BY p.num_parcial ASC, a.nombre ASC, p.id_practica ASC";

$stmt = mysqli_prepare($conexion, $sql);

if ($filtro !== '' && $filtro_parcial >= 1 && $filtro_parcial <= 3) {
    $busqueda = '%' . $filtro . '%';
    mysqli_stmt_bind_param($stmt, "sssi", $busqueda, $busqueda, $busqueda, $filtro_parcial);
} elseif ($filtro !== '') {
    $busqueda = '%' . $filtro . '%';
    mysqli_stmt_bind_param($stmt, "sss", $busqueda, $busqueda, $busqueda);
} elseif ($filtro_parcial >= 1 && $filtro_parcial <= 3) {
    mysqli_stmt_bind_param($stmt, "i", $filtro_parcial);
}

mysqli_stmt_execute($stmt);
$practicas = mysqli_stmt_get_result($stmt);
?>

<main class="main-content" style="padding:30px 20px; background:#f8f9fa; min-height:calc(100vh - 350px);">
  <div class="container" style="max-width:1200px; margin:0 auto;">
    <div style="background:white; border-left:6px solid #6c1d45; border-radius:8px; padding:20px; box-shadow:0 2px 4px rgba(0,0,0,0.06); margin-bottom:25px;">
      <h2 style="color:#6c1d45; margin:0 0 6px 0;">Catálogo de Simulaciones</h2>
      <p style="margin:0; color:#4b5563;">
        Experimentos disponibles para el semestre. Selecciona una práctica para revisar sus detalles o abrir su guía.
      </p>
    </div>

    <?php if (isset($_GET['mensaje']) && $_GET['mensaje'] === 'parcial_actualizado'): ?>
      <div style="background:#dcfce7; color:#15803d; padding:12px; border-radius:6px; margin-bottom:20px; font-weight:bold;">
        ✅ La práctica fue asignada al parcial correspondiente.
      </div>
    <?php endif; ?>

    <section style="background:white; border-radius:8px; padding:18px; margin-bottom:25px; box-shadow:0 2px 4px rgba(0,0,0,0.06);">
      <form action="simuladores.php" method="GET" style="display:grid; grid-template-columns:minmax(220px, 1fr) 220px auto; gap:12px; align-items:end;">
        <div>
          <label for="buscar" style="display:block; font-size:0.85rem; font-weight:bold; margin-bottom:5px;">Buscar práctica</label>
          <input id="buscar" type="search" name="buscar" value="<?php echo htmlspecialchars($filtro); ?>" placeholder="Ej. Vibraciones, ondas..." style="width:100%; padding:9px; border:1px solid #ccc; border-radius:4px;">
        </div>
        <div>
          <label for="parcial" style="display:block; font-size:0.85rem; font-weight:bold; margin-bottom:5px;">Parcial</label>
          <select id="parcial" name="parcial" style="width:100%; padding:9px; border:1px solid #ccc; border-radius:4px;">
            <option value="">Todos los parciales</option>
            <option value="1" <?php echo $filtro_parcial === 1 ? 'selected' : ''; ?>>Primer parcial</option>
            <option value="2" <?php echo $filtro_parcial === 2 ? 'selected' : ''; ?>>Segundo parcial</option>
            <option value="3" <?php echo $filtro_parcial === 3 ? 'selected' : ''; ?>>Tercer parcial</option>
          </select>
        </div>
        <div style="display:flex; gap:8px;">
          <button type="submit" style="background:#6c1d45; color:white; border:none; border-radius:4px; padding:10px 16px; font-weight:bold; cursor:pointer;">
            🔍 Buscar
          </button>
          <a href="simuladores.php" style="background:#e5e7eb; color:#374151; border-radius:4px; padding:10px 16px; font-weight:bold;">Limpiar</a>
        </div>
      </form>
    </section>

    <?php if ($practicas && mysqli_num_rows($practicas) > 0): ?>
      <section style="display:grid; grid-template-columns:repeat(auto-fit, minmax(280px, 1fr)); gap:20px;">
        <?php while ($practica = mysqli_fetch_assoc($practicas)): ?>
          <?php
          $titulos_parcial = array(1 => 'Primer parcial', 2 => 'Segundo parcial', 3 => 'Tercer parcial');
          $num_parcial = intval($practica['num_parcial']);
          $parcial_legible = isset($titulos_parcial[$num_parcial]) ? $titulos_parcial[$num_parcial] : 'Parcial no asignado';
          $ruta_guia = trim($practica['guia_pdf'] ?? '');
          $guia_disponible = false;

          if ($ruta_guia !== '' && strpos($ruta_guia, '../') !== 0 && strpos($ruta_guia, '/') !== 0) {
              $ruta_guia = '../' . $ruta_guia;
          }

          if ($ruta_guia !== '') {
              $ruta_relativa = ltrim(str_replace('../', '', $ruta_guia), '/');
              $ruta_fisica = realpath(__DIR__ . '/../' . $ruta_relativa);
              $guia_disponible = $ruta_fisica !== false && is_file($ruta_fisica);
          }
          ?>
          <article style="background:white; border:1px solid #e5e7eb; border-top:5px solid #6c1d45; border-radius:10px; padding:20px; box-shadow:0 3px 8px rgba(0,0,0,0.06); display:flex; flex-direction:column;">
            <div style="display:flex; justify-content:space-between; gap:10px; align-items:flex-start;">
              <h3 style="margin:0; color:#1f2937; font-size:1.05rem;">
                <?php echo htmlspecialchars($practica['titulo']); ?>
              </h3>
              <span style="background:#f3e8ef; color:#6c1d45; border-radius:12px; padding:4px 8px; font-size:0.72rem; font-weight:bold; white-space:nowrap;">
                <?php echo htmlspecialchars($parcial_legible); ?>
              </span>
            </div>

            <p style="color:#6b7280; font-size:0.86rem; margin:10px 0 14px 0; flex:1;">
              <?php echo htmlspecialchars($practica['descripcion'] ?: 'Práctica disponible en el laboratorio virtual.'); ?>
            </p>

            <div style="border-top:1px solid #eee; padding-top:12px;">
              <small style="display:block; color:#6b7280; margin-bottom:10px;">
                <strong>Asignatura:</strong> <?php echo htmlspecialchars($practica['asignatura_nombre']); ?>
              </small>
              <?php if (in_array($_SESSION['id_rol'], array(1, 2), true)): ?>
                <form action="simuladores.php<?php echo $filtro_parcial >= 1 && $filtro_parcial <= 3 ? '?parcial=' . $filtro_parcial : ''; ?>" method="POST" style="display:flex; gap:8px; align-items:center; margin-bottom:10px;">
                  <input type="hidden" name="asignar_parcial" value="1">
                  <input type="hidden" name="id_practica" value="<?php echo intval($practica['id_practica']); ?>">
                  <select name="num_parcial" aria-label="Asignar parcial" style="flex:1; min-width:120px; padding:7px; border:1px solid #ccc; border-radius:4px;">
                    <option value="1" <?php echo $num_parcial === 1 ? 'selected' : ''; ?>>Primer parcial</option>
                    <option value="2" <?php echo $num_parcial === 2 ? 'selected' : ''; ?>>Segundo parcial</option>
                    <option value="3" <?php echo $num_parcial === 3 ? 'selected' : ''; ?>>Tercer parcial</option>
                  </select>
                  <button type="submit" style="background:#0c231e; color:white; border:none; border-radius:4px; padding:8px 10px; font-weight:bold; cursor:pointer;">
                    Guardar
                  </button>
                </form>
              <?php endif; ?>
              <?php if ($guia_disponible): ?>
                <a href="<?php echo htmlspecialchars($ruta_guia); ?>" target="_blank" style="display:inline-block; background:#6c1d45; color:white; padding:8px 12px; border-radius:5px; font-size:0.84rem; font-weight:bold;">
                  📄 Abrir guía de práctica
                </a>
              <?php else: ?>
                <span style="display:inline-block; background:#f3f4f6; color:#6b7280; padding:8px 12px; border-radius:5px; font-size:0.84rem;">
                  Guía pendiente
                </span>
              <?php endif; ?>
            </div>
          </article>
        <?php endwhile; ?>
      </section>
    <?php else: ?>
      <section style="background:white; border-radius:8px; padding:35px 20px; text-align:center; color:#6b7280; box-shadow:0 2px 4px rgba(0,0,0,0.06);">
        <h3 style="margin:0 0 8px 0; color:#374151;">No se encontraron simulaciones</h3>
        <p style="margin:0;">Modifica los filtros o vuelve a consultar el catálogo completo.</p>
      </section>
    <?php endif; ?>

    <div style="margin-top:25px;">
      <a href="usr.php" style="color:#6c1d45; font-weight:bold;">← Volver al panel</a>
    </div>
  </div>
</main>

<?php
mysqli_stmt_close($stmt);
mysqli_close($conexion);
include '../EXTEND/footer.php';
?>
