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
$mensaje_exito = '';

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Registro público de perfiles de alumno
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['registrar_alumno'])) {
    $boleta = trim($_POST['boleta'] ?? '');
    $nombre = trim($_POST['nombre'] ?? '');
    $apellidos = trim($_POST['apellidos'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password_nuevo'] ?? '';
    $password_confirmacion = $_POST['password_confirmacion'] ?? '';
    $csrf = $_POST['csrf_token'] ?? '';

    if (!hash_equals($_SESSION['csrf_token'], $csrf)) {
        $mensaje_error = "La solicitud no pudo validarse. Recargue la página e inténtelo nuevamente.";
    } elseif ($boleta === '' || $nombre === '' || $apellidos === '' || $email === '' || $password === '') {
        $mensaje_error = "Complete todos los datos de registro.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $mensaje_error = "Ingrese un correo electrónico válido.";
    } elseif (strlen($password) < 8) {
        $mensaje_error = "La contraseña debe tener al menos 8 caracteres.";
    } elseif ($password !== $password_confirmacion) {
        $mensaje_error = "Las contraseñas no coinciden.";
    } else {
        $stmt_existente = mysqli_prepare(
            $conexion,
            "SELECT boleta_num_empleado FROM usuarios
             WHERE boleta_num_empleado = ? OR email = ?
             LIMIT 1"
        );
        mysqli_stmt_bind_param($stmt_existente, "ss", $boleta, $email);
        mysqli_stmt_execute($stmt_existente);
        $usuario_existente = mysqli_stmt_get_result($stmt_existente);

        if (mysqli_num_rows($usuario_existente) > 0) {
            $mensaje_error = "La boleta o el correo electrónico ya están registrados.";
            mysqli_stmt_close($stmt_existente);
        } else {
            mysqli_stmt_close($stmt_existente);

            $password_hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt_registro = mysqli_prepare(
                $conexion,
                "INSERT INTO usuarios
                 (boleta_num_empleado, nombre, apellidos, email, password, id_rol, prefijo_folio, activo)
                 VALUES (?, ?, ?, ?, ?, 3, NULL, 1)"
            );
            mysqli_stmt_bind_param(
                $stmt_registro,
                "sssss",
                $boleta,
                $nombre,
                $apellidos,
                $email,
                $password_hash
            );

            if (mysqli_stmt_execute($stmt_registro)) {
                $mensaje_exito = "Registro completado. Ya puede iniciar sesión con su boleta.";
            } else {
                $mensaje_error = "No fue posible crear el perfil. Inténtelo nuevamente.";
            }
            mysqli_stmt_close($stmt_registro);
        }
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['iniciar_sesion'])) {
    $boleta_ingresada = trim($_POST['usuario'] ?? '');
    $password_ingresada = trim($_POST['password'] ?? '');
    $csrf = $_POST['csrf_token'] ?? '';

    if (!hash_equals($_SESSION['csrf_token'], $csrf)) {
        $mensaje_error = "La solicitud no pudo validarse. Recargue la página e inténtelo nuevamente.";
    } else {

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
}

// 3. Cargar la cabecera visual (EXTEND/header.php)
include 'EXTEND/header.php';
?>

<main class="login-page-bg">
  <div class="login-wrapper" style="max-width:500px;">
    
    <!-- Tarjeta de Inicio de Sesión Moderno -->
    <div class="login-card" id="inicio-sesion">
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

      <?php if (!empty($mensaje_exito)): ?>
        <div class="alert-success" style="background:#dcfce7; color:#15803d; padding:12px; border-radius:6px; margin-bottom:16px; font-weight:bold;">
          ✅ <?php echo htmlspecialchars($mensaje_exito); ?>
        </div>
      <?php endif; ?>

      <form action="index.php" method="POST" class="login-form">
        <input type="hidden" name="iniciar_sesion" value="1">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
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
      <button type="button" id="mostrar-registro" style="width:100%; margin-top:12px; background:white; color:#6c1d45; border:1px solid #6c1d45; border-radius:6px; padding:11px; font-weight:bold; cursor:pointer;">
        Crear una cuenta de alumno
      </button>
    </div>

    <!-- Registro público de alumno -->
    <div class="login-card" id="registro-alumno" style="display:none;">
      <div class="login-header">
        <div class="logo-badge">LS</div>
        <h3>Registro de alumno</h3>
        <p>Cree su perfil para acceder a LabSimulator</p>
      </div>

      <?php if (!empty($mensaje_error)): ?>
        <div class="alert-error">
          <span>⚠️</span> <?php echo htmlspecialchars($mensaje_error); ?>
        </div>
      <?php endif; ?>

      <form action="index.php" method="POST" class="login-form">
        <input type="hidden" name="registrar_alumno" value="1">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

        <div class="form-group">
          <label for="boleta">Boleta</label>
          <input type="text" id="boleta" name="boleta" maxlength="20" required autocomplete="off" value="<?php echo htmlspecialchars(trim($_POST['boleta'] ?? '')); ?>">
        </div>

        <div class="form-group">
          <label for="nombre">Nombre</label>
          <input type="text" id="nombre" name="nombre" maxlength="100" required autocomplete="given-name" value="<?php echo htmlspecialchars(trim($_POST['nombre'] ?? '')); ?>">
        </div>

        <div class="form-group">
          <label for="apellidos">Apellidos</label>
          <input type="text" id="apellidos" name="apellidos" maxlength="100" required autocomplete="family-name" value="<?php echo htmlspecialchars(trim($_POST['apellidos'] ?? '')); ?>">
        </div>

        <div class="form-group">
          <label for="email">Correo electrónico</label>
          <input type="email" id="email" name="email" maxlength="120" required autocomplete="email" value="<?php echo htmlspecialchars(trim($_POST['email'] ?? '')); ?>">
        </div>

        <div class="form-group">
          <label for="password_nuevo">Contraseña</label>
          <input type="password" id="password_nuevo" name="password_nuevo" minlength="8" required autocomplete="new-password">
        </div>

        <div class="form-group">
          <label for="password_confirmacion">Confirmar contraseña</label>
          <input type="password" id="password_confirmacion" name="password_confirmacion" minlength="8" required autocomplete="new-password">
        </div>

        <button type="submit" class="btn-login">Registrarme como alumno</button>
      </form>

      <button type="button" id="mostrar-inicio" style="width:100%; margin-top:12px; background:white; color:#6c1d45; border:1px solid #6c1d45; border-radius:6px; padding:11px; font-weight:bold; cursor:pointer;">
        Ya tengo una cuenta
      </button>
    </div>
  </div>
</main>

<script>
  const inicioSesion = document.getElementById('inicio-sesion');
  const registroAlumno = document.getElementById('registro-alumno');
  const mostrarRegistro = document.getElementById('mostrar-registro');
  const mostrarInicio = document.getElementById('mostrar-inicio');

  <?php if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['registrar_alumno']) && !empty($mensaje_error)): ?>
  inicioSesion.style.display = 'none';
  registroAlumno.style.display = 'block';
  <?php endif; ?>

  mostrarRegistro.addEventListener('click', function () {
    inicioSesion.style.display = 'none';
    registroAlumno.style.display = 'block';
  });

  mostrarInicio.addEventListener('click', function () {
    registroAlumno.style.display = 'none';
    inicioSesion.style.display = 'block';
  });
</script>

<?php
// 4. Cargar el pie de página visual (EXTEND/footer.php)
include 'EXTEND/footer.php';
?>