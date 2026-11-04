<?php
// 1. Cargar el header modular (incluye el CSS con la versión dinámica)
include 'extend/header.php';

// 2. Lógica estática de inicio de sesión
$mensaje_error = '';
$usuario_logueado = false;
$nombre_usuario = '';

// Credenciales estáticas de prueba
$usuario_correcto = "admin";
$password_correcto = "12345";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user = $_POST['usuario'] ?? '';
    $pass = $_POST['password'] ?? '';

    if ($user === $usuario_correcto && $pass === $password_correcto) {
        $usuario_logueado = true;
        $nombre_usuario = "Administrador del Sistema";
    } else {
        $mensaje_error = "Usuario o contraseña incorrectos (Intente otra vez)";
    }
}
?>

<main class="login-page-bg">
  <div class="login-wrapper">
    
    <?php if ($usuario_logueado): ?>
      <!-- Tarjeta de Bienvenida centrada -->
      <div class="login-card welcome-card">
        <div class="login-header">
          <div class="user-avatar">👤</div>
          <h3>¡Bienvenido!</h3>
          <p><?php echo htmlspecialchars($nombre_usuario); ?></p>
        </div>
        <p class="status-badge">Sesión iniciada mediante datos estáticos</p>
        <a href="index.php" class="btn-login btn-logout">Cerrar Sesión</a>
      </div>

    <?php else: ?>
      <!-- Formulario de Inicio de Sesión Moderno y Centrado -->
      <div class="login-card">
        <div class="login-header">
          <div class="logo-badge">LS</div>
          <h3>LabSimulator</h3>
          <p>Ingrese sus credenciales para acceder</p>
        </div>

        <?php if (!empty($mensaje_error)): ?>
          <div class="alert-error">
            <span>⚠️</span> <?php echo $mensaje_error; ?>
          </div>
        <?php endif; ?>

        <form action="index.php" method="POST" class="login-form">
          <div class="form-group">
            <label for="usuario">Usuario / Correo</label>
            <input type="text" id="usuario" name="usuario" placeholder="Ej. admin" required autocomplete="off">
          </div>

          <div class="form-group">
            <label for="password">Contraseña</label>
            <input type="password" id="password" name="password" placeholder="••••••••" required>
          </div>

          <button type="submit" class="btn-login">Ingresar al Sistema</button>
        </form>
      </div>
    <?php endif; ?>

  </div>
</main>

<?php
// 3. Cargar el footer modular
include 'extend/footer.php';
?>