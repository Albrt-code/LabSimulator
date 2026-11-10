<?php
// 1. Iniciar sesión y validar autenticación
session_start();

if (!isset($_SESSION['boleta'])) {
    header("Location: ../index.php");
    exit();
}

include '../conexion/conexion.php';
include '../EXTEND/header.php';

$boleta_usuario = $_SESSION['boleta'];
$es_docente = ($_SESSION['id_rol'] == 2);
?>

<main class="main-content" style="padding: 30px 20px; background-color: #f8f9fa; min-height: calc(100vh - 350px);">
  <div class="container" style="max-width: 1200px; margin: 0 auto;">
    
    <!-- ENCABEZADO DINÁMICO (Cambia el título según el Rol) -->
    <div style="background: white; border-left: 6px solid #6c1d45; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.06); margin-bottom: 25px;">
      <h2 style="color: #6c1d45; margin: 0 0 6px 0; font-size: 1.6rem;">
        <?php echo $es_docente ? 'Panel de Control del Docente' : 'Espacio del Alumno'; ?>
      </h2>
      <p style="margin: 0; color: #4b5563; font-size: 0.95rem;">
        Bienvenido(a), <strong><?php echo htmlspecialchars($_SESSION['nombre']); ?></strong> | 
        <?php echo $es_docente ? 'No. Empleado:' : 'Boleta:'; ?> 
        <strong><?php echo htmlspecialchars($_SESSION['boleta']); ?></strong>
      </p>
    </div>

    <?php if ($es_docente): ?>
      <!-- ========================================== -->
      <!-- VISTA EXCLUSIVA PARA EL DOCENTE (ROL 2)    -->
      <!-- ========================================== -->

      <!-- MÓDULO 1: PUBLICAR/AGENDAR PRÁCTICA -->
      <section style="background: white; padding: 22px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.06); margin-bottom: 25px;">
        <h3 style="margin: 0 0 15px 0; color: #1f2937; font-size: 1.2rem;">📅 Programar Nueva Práctica Presencial</h3>
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
      <section style="background: white; padding: 22px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.06); margin-bottom: 25px;">
        <h3 style="margin: 0 0 15px 0; color: #1f2937; font-size: 1.2rem;">📚 Subir Material Didáctico por Parcial</h3>
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
      <section style="background: white; padding: 22px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.06);">
        <h3 style="margin: 0 0 15px 0; color: #1f2937; font-size: 1.2rem;">✅ Vales Pendientes por Autorizar (Firma Digital)</h3>
        <table style="width: 100%; border-collapse: collapse; font-size: 0.9rem;">
          <thead>
            <tr style="background-color: #6c1d45; color: white;">
              <th style="padding: 10px;">Folio</th>
              <th style="padding: 10px;">Alumno / Boleta</th>
              <th style="padding: 10px;">Práctica Agendada</th>
              <th style="padding: 10px;">Estatus</th>
              <th style="padding: 10px; text-align: center;">Acción Docente</th>
            </tr>
          </thead>
          <tbody>
            <tr style="border-bottom: 1px solid #eee;">
              <td style="padding: 10px;"><strong>C010663</strong></td>
              <td style="padding: 10px;">Luis Antonio González (2026601855)</td>
              <td style="padding: 10px;">Práctica 1: Vibraciones en una Cuerda</td>
              <td style="padding: 10px;"><span style="background:#fef3c7; color:#b45309; padding:3px 8px; border-radius:10px; font-weight:bold; font-size:0.8rem;">Solicitado</span></td>
              <td style="padding: 10px; text-align: center;">
                <a href="validar_vale.php?id_vale=1&accion=aprobar" style="background:#15803d; color:white; padding:5px 10px; text-decoration:none; border-radius:4px; font-size:0.8rem; font-weight:bold;">✍️ Firmar y Aprobar</a>
                <a href="validar_vale.php?id_vale=1&accion=rechazar" style="background:#b91c1c; color:white; padding:5px 10px; text-decoration:none; border-radius:4px; font-size:0.8rem; font-weight:bold; margin-left:5px;">❌ Rechazar</a>
              </td>
            </tr>
          </tbody>
        </table>
      </section>

    <?php else: ?>
      <!-- ========================================== -->
      <!-- VISTA EXCLUSIVA PARA EL ALUMNO (ROL 3)     -->
      <!-- ========================================== -->

      <!-- MÓDULO 1: ACCESO A SIMULADORES -->
      <section style="background: white; padding: 22px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.06); margin-bottom: 25px;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
          <div>
            <h3 style="margin: 0 0 6px 0; color: #1f2937; font-size: 1.2rem;">💻 Simuladores Virtuales de Física</h3>
            <p style="margin: 0; color: #6b7280; font-size: 0.9rem;">
              Espacio de experimentación interactiva libre para realizar pruebas teóricas y simular prácticas.
            </p>
          </div>
          <a href="simuladores.php" style="background: #6c1d45; color: white; padding: 10px 22px; text-decoration: none; border-radius: 6px; font-weight: bold; font-size: 0.9rem;">
            🚀 Ir al Catálogo de Simulaciones
          </a>
        </div>
      </section>

      <!-- MÓDULO 2: MATERIAL DIDÁCTICO PDF (PARCIALES) -->
      <section style="background: white; padding: 22px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.06); margin-bottom: 25px;">
        <h3 style="margin: 0 0 15px 0; color: #1f2937; font-size: 1.2rem;">📚 Material Didáctico y Guías Didácticas (por Parcial)</h3>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 18px;">
          <div style="border: 1px solid #e5e7eb; border-top: 4px solid #6c1d45; border-radius: 8px; padding: 16px; background: #fafafa;">
            <h4 style="margin: 0 0 12px 0; color: #6c1d45;">Primer Parcial</h4>
            <ul style="list-style: none; padding: 0; margin: 0;">
              <li style="margin-bottom: 8px; font-size: 0.88rem;">📄 <a href="../guias/manual_p1.pdf" target="_blank" style="color: #2563eb; text-decoration: none;">Manual_Practicas_P1.pdf</a></li>
              <li style="font-size: 0.88rem;">📄 <a href="../guias/formulario_mecanica.pdf" target="_blank" style="color: #2563eb; text-decoration: none;">Formulario_Mecanica.pdf</a></li>
            </ul>
          </div>

          <div style="border: 1px solid #e5e7eb; border-top: 4px solid #b38e5d; border-radius: 8px; padding: 16px; background: #fafafa;">
            <h4 style="margin: 0 0 12px 0; color: #b38e5d;">Segundo Parcial</h4>
            <ul style="list-style: none; padding: 0; margin: 0;">
              <li style="font-size: 0.88rem;">📄 <a href="../guias/guia_cuerdas.pdf" target="_blank" style="color: #2563eb; text-decoration: none;">Guia_Ondas_y_Cuerdas.pdf</a></li>
            </ul>
          </div>

          <div style="border: 1px solid #e5e7eb; border-top: 4px solid #6b7280; border-radius: 8px; padding: 16px; background: #fafafa;">
            <h4 style="margin: 0 0 12px 0; color: #374151;">Tercer Parcial</h4>
            <p style="font-size: 0.85rem; color: #9ca3af; margin: 0;">Sin archivos subidos aún por el docente.</p>
          </div>
        </div>
      </section>

      <!-- MÓDULO 3: AGENDA Y VALES ALUMNO -->
      <section style="background: white; padding: 22px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.06);">
        <h3 style="margin: 0 0 15px 0; color: #1f2937; font-size: 1.2rem;">📅 Agenda de Prácticas y Vale Digital de Almacén</h3>
        <table style="width: 100%; border-collapse: collapse; font-size: 0.9rem; text-align: left;">
          <thead>
            <tr style="background-color: #6c1d45; color: white;">
              <th style="padding: 12px;">Práctica</th>
              <th style="padding: 12px;">Aula / Lab</th>
              <th style="padding: 12px;">Fecha y Horario</th>
              <th style="padding: 12px;">Estatus</th>
              <th style="padding: 12px;">Vale Digital / Comprobante</th>
            </tr>
          </thead>
          <tbody>
            <tr style="border-bottom: 1px solid #e5e7eb;">
              <td style="padding: 12px;"><strong>Práctica 1: Vibraciones en una Cuerda</strong></td>
              <td style="padding: 12px;">Lab. 4009</td>
              <td style="padding: 12px;">28/10/2026 (19:00 - 21:00)</td>
              <td style="padding: 12px;">
                <span style="background: #dcfce7; color: #15803d; padding: 4px 10px; border-radius: 12px; font-weight: bold; font-size: 0.8rem;">
                  Validado Docente
                </span>
              </td>
              <td style="padding: 12px;">
                <a href="generar_pdf_vale.php?id_vale=1" target="_blank" style="background: #0c231e; color: white; padding: 6px 12px; text-decoration: none; border-radius: 4px; font-size: 0.82rem; font-weight: bold;">
                  📄 Descargar Vale PDF
                </a>
              </td>
            </tr>
          </tbody>
        </table>
      </section>
    <?php endif; ?>

  </div>
</main>

<?php
include '../EXTEND/footer.php';
?>