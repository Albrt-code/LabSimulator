<?php
session_start();

// Control de seguridad: solo profesores (rol 2) o administradores (rol 1)
if (!isset($_SESSION['boleta']) || ($_SESSION['id_rol'] != 2 && $_SESSION['id_rol'] != 1)) {
    header("Location: ../index.php");
    exit();
}

include '../conexion/conexion.php';
include '../EXTEND/header.php';

// Procesar alta de práctica programada (Fecha, Hora, Lab)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['agendar_practica'])) {
    $id_secuencia = intval($_POST['id_secuencia']);
    $id_practica = intval($_POST['id_practica']);
    $aula = trim($_POST['aula_laboratorio']);
    $fecha = $_POST['fecha_programada'];
    $hora_ini = $_POST['hora_inicio'];
    $hora_fin = $_POST['hora_fin'];

    $sql_agenda = "INSERT INTO agenda_practicas (id_secuencia, id_practica, aula_laboratorio, fecha_programada, hora_inicio, hora_fin, estado) 
                   VALUES (?, ?, ?, ?, ?, ?, 'Programada')";
    $stmt = mysqli_prepare($conexion, $sql_agenda);
    mysqli_stmt_bind_param($stmt, "iissss", $id_secuencia, $id_practica, $aula, $fecha, $hora_ini, $hora_fin);
    mysqli_stmt_execute($stmt);
    $mensaje = "Práctica agendada correctamente para la secuencia.";
}

// Procesar subida de PDF por Parcial
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['subir_material'])) {
    $num_parcial = intval($_POST['num_parcial']);
    $id_asignatura = intval($_POST['id_asignatura']);
    $titulo_material = trim($_POST['titulo_material']);
    
    // Gestión de subida de archivo PDF
    if (isset($_FILES['archivo_pdf']) && $_FILES['archivo_pdf']['error'] == 0) {
        $dir_destino = "../guias/";
        if (!file_exists($dir_destino)) { mkdir($dir_destino, 0777, true); }
        
        $nombre_archivo = time() . "_" . basename($_FILES['archivo_pdf']['name']);
        $ruta_final = $dir_destino . $nombre_archivo;

        if (move_uploaded_file($_FILES['archivo_pdf']['tmp_name'], $ruta_final)) {
            $sql_mat = "INSERT INTO materiales_parcial (id_asignatura, num_parcial, titulo_material, archivo_pdf, boleta_docente) 
                        VALUES (?, ?, ?, ?, ?)";
            $stmt_m = mysqli_prepare($conexion, $sql_mat);
            mysqli_stmt_bind_param($stmt_m, "iisss", $id_asignatura, $num_parcial, $titulo_material, $ruta_final, $_SESSION['boleta']);
            mysqli_stmt_execute($stmt_m);
            $mensaje_pdf = "Material didáctico asignado al Parcial $num_parcial con éxito.";
        }
    }
}
?>

<main class="main-content" style="padding: 30px 20px; background-color: #f8f9fa; min-height: calc(100vh - 350px);">
  <div class="container" style="max-width: 1200px; margin: 0 auto;">
    
    <div style="background: white; border-left: 5px solid #6c1d45; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); margin-bottom: 25px;">
      <h2 style="color: #6c1d45; margin: 0 0 5px 0;">Panel Docente: Gestión de Prácticas y Vales</h2>
      <p style="margin: 0; color: #4b5563;">Profesor: <strong><?php echo htmlspecialchars($_SESSION['nombre']); ?></strong> | No. Empleado: <strong><?php echo htmlspecialchars($_SESSION['boleta']); ?></strong></p>
    </div>

    <!-- MÓDULO 1: PUBLICAR/AGENDAR FECHA Y HORA DE PRÁCTICAS -->
    <section style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); margin-bottom: 25px;">
      <h3 style="margin: 0 0 15px 0; color: #1f2937;">📅 Programar Nueva Práctica Presencial</h3>
      <form action="docente.php" method="POST" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
        <input type="hidden" name="agendar_practica" value="1">
        
        <div>
          <label style="display:block; font-size:0.85rem; font-weight:bold; margin-bottom:5px;">Secuencia / Grupo:</label>
          <select name="id_secuencia" required style="width:100%; padding:8px; border:1px solid #ccc; border-radius:4px;">
            <option value="1">2IV21 - Mecánica Clásica</option>
          </select>
        </div>

        <div>
          <label style="display:block; font-size:0.85rem; font-weight:bold; margin-bottom:5px;">Práctica:</label>
          <select name="id_practica" required style="width:100%; padding:8px; border:1px solid #ccc; border-radius:4px;">
            <option value="1">Práctica 1: Vibraciones en una Cuerda</option>
            <option value="2">Práctica 2: Ley de Hooke</option>
          </select>
        </div>

        <div>
          <label style="display:block; font-size:0.85rem; font-weight:bold; margin-bottom:5px;">Aula / Laboratorio:</label>
          <input type="text" name="aula_laboratorio" value="Laboratorio 4009" required style="width:100%; padding:8px; border:1px solid #ccc; border-radius:4px;">
        </div>

        <div>
          <label style="display:block; font-size:0.85rem; font-weight:bold; margin-bottom:5px;">Fecha Programada:</label>
          <input type="date" name="fecha_programada" required style="width:100%; padding:8px; border:1px solid #ccc; border-radius:4px;">
        </div>

        <div>
          <label style="display:block; font-size:0.85rem; font-weight:bold; margin-bottom:5px;">Hora Inicio:</label>
          <input type="time" name="hora_inicio" required style="width:100%; padding:8px; border:1px solid #ccc; border-radius:4px;">
        </div>

        <div>
          <label style="display:block; font-size:0.85rem; font-weight:bold; margin-bottom:5px;">Hora Fin:</label>
          <input type="time" name="hora_fin" required style="width:100%; padding:8px; border:1px solid #ccc; border-radius:4px;">
        </div>

        <div style="grid-column: 1 / -1;">
          <button type="submit" style="background: #6c1d45; color: white; padding: 10px 20px; border: none; border-radius: 4px; font-weight: bold; cursor: pointer;">
            📢 Publicar Práctica en la Agenda del Alumno
          </button>
        </div>
      </form>
    </section>

    <!-- MÓDULO 2: SUBIR MATERIAL DIDÁCTICO PDF POR PARCIAL -->
    <section style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); margin-bottom: 25px;">
      <h3 style="margin: 0 0 15px 0; color: #1f2937;">📚 Subir Material Didáctico por Parcial</h3>
      <form action="docente.php" method="POST" enctype="multipart/form-data" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 15px;">
        <input type="hidden" name="subir_material" value="1">
        <input type="hidden" name="id_asignatura" value="1">

        <div>
          <label style="display:block; font-size:0.85rem; font-weight:bold; margin-bottom:5px;">Seleccionar Parcial:</label>
          <select name="num_parcial" required style="width:100%; padding:8px; border:1px solid #ccc; border-radius:4px;">
            <option value="1">Primer Parcial</option>
            <option value="2">Segundo Parcial</option>
            <option value="3">Tercer Parcial</option>
          </select>
        </div>

        <div>
          <label style="display:block; font-size:0.85rem; font-weight:bold; margin-bottom:5px;">Título del Documento:</label>
          <input type="text" name="titulo_material" placeholder="Ej. Guía Práctica de Ondas" required style="width:100%; padding:8px; border:1px solid #ccc; border-radius:4px;">
        </div>

        <div>
          <label style="display:block; font-size:0.85rem; font-weight:bold; margin-bottom:5px;">Archivo PDF:</label>
          <input type="file" name="archivo_pdf" accept=".pdf" required style="width:100%; padding:6px; border:1px solid #ccc; border-radius:4px;">
        </div>

        <div style="grid-column: 1 / -1;">
          <button type="submit" style="background: #b38e5d; color: white; padding: 10px 20px; border: none; border-radius: 4px; font-weight: bold; cursor: pointer;">
            📤 Subir Archivo al Parcial
          </button>
        </div>
      </form>
    </section>

    <!-- MÓDULO 3: VALIDACIÓN DIGITAL DE VALES DE ALUMNOS -->
    <section style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
      <h3 style="margin: 0 0 15px 0; color: #1f2937;">✅ Vales Pendientes por Autorizar (Firma Digital)</h3>
      <table style="width: 100%; border-collapse: collapse; font-size: 0.9rem;">
        <thead>
          <tr style="background-color: #6c1d45; color: white;">
            <th style="padding: 10px;">Folio</th>
            <th style="padding: 10px;">Alumno / Boleta</th>
            <th style="padding: 10px;">Práctica Agendada</th>
            <th style="padding: 10px;">Estatus</th>
            <th style="padding: 10px;">Acción Docente</th>
          </tr>
        </thead>
        <tbody>
          <tr style="border-bottom: 1px solid #eee;">
            <td style="padding: 10px;"><strong>C010663</strong></td>
            <td style="padding: 10px;">Luis Antonio González (2026601855)</td>
            <td style="padding: 10px;">Práctica 1: Vibraciones en una Cuerda</td>
            <td style="padding: 10px;"><span style="background:#fef3c7; color:#b45309; padding:3px 8px; border-radius:10px; font-weight:bold; font-size:0.8rem;">Solicitado</span></td>
            <td style="padding: 10px;">
              <a href="validar_vale.php?id=105&accion=aprobar" style="background:#15803d; color:white; padding:5px 10px; text-decoration:none; border-radius:4px; font-size:0.8rem; font-weight:bold;">✍️ Firmar y Aprobar</a>
              <a href="validar_vale.php?id=105&accion=rechazar" style="background:#b91c1c; color:white; padding:5px 10px; text-decoration:none; border-radius:4px; font-size:0.8rem; font-weight:bold; margin-left:5px;">❌ Rechazar</a>
            </td>
          </tr>
        </tbody>
      </table>
    </section>

  </div>
</main>

<?php
include '../EXTEND/footer.php';
?>