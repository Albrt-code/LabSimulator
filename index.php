<?php
// 1. Iniciar sesión y conectar a la base de datos MySQL
session_start();
include 'conexion/conexion.php';

// Si el usuario ya tiene sesión activa, redirigir automáticamente según su rol
if (isset($_SESSION['boleta'])) {
    if ($_SESSION['id_rol'] == 1 || $_SESSION['id_rol'] == 4) {
        header("Location: PAG/adm.php");
        exit();
    } else {
        header("Location: PAG/usr.php");
        exit();
    }
}

// 2. Procesamiento de credenciales al enviar el formulario
$mensaje_error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $boleta_ingresada = trim($_POST['usuario'] ?? '');
    $password_ingresada = trim($_POST['password'] ?? '');

    if (!empty($boleta_ingresada) && !empty($password_ingresada)) {
        // Consulta segura mediante Prepared Statements
        $stmt = mysqli_prepare($conexion, "SELECT boleta_num_empleado, nombre, apellidos, password, id_rol, prefijo_folio, activo FROM usuarios WHERE boleta_num_empleado = ?");
        mysqli_stmt_bind_param($stmt, "s", $boleta_ingresada);
        mysqli_stmt_execute($stmt);
        $resultado = mysqli_stmt_get_result($stmt);

        if ($usuario = mysqli_fetch_assoc($resultado)) {
            if ($usuario['activo'] == 1) {
                // Verificar la contraseña encriptada (BCRYPT)
                if (password_verify($password_ingresada, $usuario['password'])) {
                    
                    // Guardar los datos esenciales en la variable $_SESSION
                    $_SESSION['boleta'] = $usuario['boleta_num_empleado'];
                    $_SESSION['nombre'] = $usuario['nombre'] . ' ' . $usuario['apellidos'];
                    $_SESSION['id_rol'] = $usuario['id_rol'];
                    $_SESSION['prefijo'] = $usuario['prefijo_folio'];

                    // Redirección condicionada por Rol de Usuario
                    if ($usuario['id_rol'] == 1 || $usuario['id_rol'] == 4) {
                        header("Location: PAG/adm.php");
                    } else {
                        // Alumno (Rol 3) o Docente (Rol 2) entran al Espacio del Alumno/Docente
                        header("Location: PAG/usr.php");
                    }
                    exit();
                } else {
                    $mensaje_error = "La contraseña es incorrecta.";
                }
            } else {
                $mensaje_error = "Esta cuenta se encuentra deshabilitada.";
            }
        } else {
            $mensaje_error = "La Boleta o No. de Empleado no existe en el sistema.";
        }
        mysqli_stmt_close($stmt);
    } else {
        $mensaje_error = "Por favor, complete todos los campos.";
    }
}

// 3. Cargar la cabecera visual (EXTEND/header.php)
include 'EXTEND/header.php';
?>

<main class="login-page-bg">
  <div class="login-wrapper">
    
    <!-- Tarjeta de Inicio de Sesión Moderno -->
    <div class="login-card">
      <div class="login-header">
        <div class="logo-badge">LS</div>
        <h3>LabSimulator</h3>
        <p>Ingrese sus credenciales para acceder</p>
      </div>

      <?php if (!empty($mensaje_error)): ?>
        <div class="alert-error">
          <span>⚠️</span> <?php echo htmlspecialchars($mensaje_error); ?>
        </div>
      <?php endif; ?>

      <form action="index.php" method="POST" class="login-form">
        <div class="form-group">
          <label for="usuario">Boleta / No. Empleado</label>
          <input type="text" id="usuario" name="usuario" placeholder="Ej. 2026601855 o DOC202601" required autocomplete="off">
        </div>

        <div class="form-group">
          <label for="password">Contraseña</label>
          <input type="password" id="password" name="password" placeholder="••••••••" required>
        </div>

        <button type="submit" class="btn-login">Ingresar al Sistema</button>
      </form>
    </div>

  </div>
</main>

<?php
// 4. Cargar el pie de página visual (EXTEND/footer.php)
include 'EXTEND/footer.php';
?>