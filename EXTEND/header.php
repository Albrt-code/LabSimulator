<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>LabSimulator - IPN UPIICSA</title>

  <!-- Carga del CSS con versión dinámica para evitar caché -->
  <link rel="stylesheet" href="/LabSimulator/CSS/style.css?v=<?php echo time(); ?>">
</head>
<body>

<!-- 1. BARRA SUPERIOR VERDE DE GOBIERNO -->
<div class="top-gob-bar">
  <div class="top-gob-container">
    <div class="top-gob-links">
      <a href="https://www.gob.mx/tramites" target="_blank">Trámites</a>
      <a href="https://www.gob.mx/gobierno" target="_blank">Gobierno</a>
      <a href="https://www.ipn.mx/directorio-telefonico.html" target="_blank">Directorio</a>
      <a href="https://www.ipn.mx/correo-electronico.html" target="_blank">Correo</a>
    </div>
    <div class="accessibility-controls">
      <button type="button" class="accessibility-btn" aria-label="Aumentar texto">A+</button>
      <button type="button" class="accessibility-btn" aria-label="Reducir texto">A-</button>
      <button type="button" class="accessibility-btn" aria-label="Texto normal">A</button>
    </div>
  </div>
</div>

<!-- 2. ENCABEZADO DE MARCA IPN / UPIICSA -->
<header class="ipn-header">
  <div class="header-container" style="display: flex; justify-content: space-between; align-items: center; padding: 12px 20px;">
    
    <!-- Logo y Nombre de la Plataforma -->
    <div class="brand" style="display: flex; align-items: center;">
      <a href="/LabSimulator/index.php" style="display: flex; align-items: center; text-decoration: none;">
        <img src="/LabSimulator/ASSETS/img/upiicsa-logo.png" alt="UPIICSA IPN" style="height: 48px; margin-right: 12px; object-fit: contain;">
        <div class="brand-title">
          <h1 style="margin: 0; font-size: 1.5rem; color: #6c1d45; font-weight: 700; line-height: 1.1;">LabSimulator</h1>
          <span style="font-size: 0.82rem; color: #666; display: block;">Laboratorio de Física Experimental - UPIICSA</span>
        </div>
      </a>
    </div>

    <!-- Menú de Usuario (Si ya inició sesión) -->
    <?php if (isset($_SESSION['boleta'])): ?>
      <div class="user-session-info" style="text-align: right; font-size: 0.9rem;">
        <span style="color: #333; font-weight: 600;">👤 <?php echo htmlspecialchars($_SESSION['nombre']); ?></span> 
        <small style="display: block; color: #666; font-size: 0.78rem;">
          Boleta: <strong><?php echo htmlspecialchars($_SESSION['boleta']); ?></strong>
        </small>
        <a href="/LabSimulator/logout.php" style="color: #991b1b; font-size: 0.8rem; font-weight: bold; text-decoration: underline;">Cerrar Sesión</a>
      </div>
    <?php endif; ?>

  </div>
</header>