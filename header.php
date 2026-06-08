<?php
$pageTitle  = $pageTitle  ?? 'Hotel Maya Bay';
$activePage = $activePage ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle) ?> — Hotel Maya Bay</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Pacifico&family=Nunito:wght@400;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="app-wrapper">

  <nav class="navbar">
    <div class="navbar-inner">

      <a href="dashboard.php" class="navbar-brand">
        <div class="logo-icon">🏖️</div>
        <div>
          <span class="brand-name">Maya Bay</span>
          <span class="brand-sub">★★★★ Beach Resort</span>
        </div>
      </a>

      <ul class="nav-links">
        <li><a href="dashboard.php"          class="<?= $activePage==='home'    ?'active':'' ?>">🏠 Inicio</a></li>
        <li><a href="reserva-habitacion.php" class="<?= $activePage==='rooms'   ?'active':'' ?>">🛏 Habitaciones</a></li>
        <li><a href="reserva-traslado.php"   class="<?= $activePage==='transfer'?'active':'' ?>">🚐 Traslados</a></li>
        <li><a href="historial.php"          class="<?= $activePage==='history' ?'active':'' ?>">📋 Mis Reservas</a></li>
        <li><a href="perfil.php"             class="<?= $activePage==='profile' ?'active':'' ?>">👤 Perfil</a></li>
      </ul>

      <div class="nav-user">
        <div class="nav-avatar" onclick="location.href='perfil.php'" title="Mi Perfil">
          <?php
          $name = $_SESSION['firebase_user']['displayName'] ?? ($_SESSION['user']['email'] ?? 'U');
          echo strtoupper(substr($name, 0, 2));
          ?>
        </div>
        <a href="auth/logout.php" style="color:rgba(255,255,255,0.7);font-size:0.8rem;font-weight:700;" title="Cerrar sesión">Salir</a>
      </div>

    </div>
  </nav>
