<?php
session_start();

require_once 'config/firebase_config.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirmPassword = trim($_POST['confirm_password'] ?? '');

    if (empty($email) || empty($password) || empty($confirmPassword)) {

        $error = 'Completa todos los campos.';

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = 'Correo electrónico inválido.';

    } elseif ($password !== $confirmPassword) {

        $error = 'Las contraseñas no coinciden.';

    } elseif (strlen($password) < 6) {

        $error = 'La contraseña debe tener al menos 6 caracteres.';

    } else {

        $url = FIREBASE_AUTH_URL . ':signUp?key=' . FIREBASE_API_KEY;

        $data = [
            'email' => $email,
            'password' => $password,
            'returnSecureToken' => true
        ];

        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json'
            ],
            CURLOPT_POSTFIELDS => json_encode($data)
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        $result = json_decode($response, true);

        if (isset($result['localId'])) {

            $_SESSION['user'] = [
                'uid' => $result['localId'],
                'email' => $result['email'],
                'token' => $result['idToken']
            ];

            header('Location: dashboard.php');
            exit;

        } else {

            $firebaseError = $result['error']['message'] ?? '';

            switch ($firebaseError) {

                case 'EMAIL_EXISTS':
                    $error = 'Este correo ya está registrado.';
                    break;

                default:
    $error = 'Firebase: ' . $firebaseError;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Registro — Hotel Maya Bay</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <style>
    body { background: var(--midnight); display: flex; align-items: stretch; min-height: 100vh; }

    /* ── Two-column layout ── */
    .login-wrap {
      display: flex;
      width: 100%;
      min-height: 100vh;
    }

    /* Left: hero panel */
    .login-hero {
      flex: 1;
      position: relative;
      overflow: hidden;
      display: flex;
      flex-direction: column;
      justify-content: flex-end;
      padding: 48px;
      background: linear-gradient(160deg, #080F14 0%, #0F1A24 40%, #162534 100%);
    }

    .hero-pattern {
      position: absolute;
      inset: 0;
      opacity: 0.07;
      background-image:
        repeating-linear-gradient(45deg, var(--gold) 0, var(--gold) 1px, transparent 0, transparent 50%);
      background-size: 28px 28px;
    }

    .hero-badge {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      background: rgba(212,175,55,0.12);
      border: 1px solid rgba(212,175,55,0.3);
      color: var(--gold);
      font-family: var(--font-title);
      font-size: 0.72rem;
      font-weight: 600;
      letter-spacing: 2px;
      text-transform: uppercase;
      padding: 6px 14px;
      border-radius: 20px;
      margin-bottom: 20px;
      width: fit-content;
    }

    .hero-text h1 {
      font-size: 3.5rem;
      font-weight: 900;
      color: var(--bone);
      line-height: 1;
      margin-bottom: 12px;
    }

    .hero-text h1 em {
      font-style: normal;
      color: var(--gold);
      display: block;
    }

    .hero-text p {
      color: var(--grey-mid);
      font-size: 1rem;
      max-width: 380px;
      line-height: 1.7;
    }

    .hero-features {
      display: flex;
      gap: 24px;
      margin-top: 32px;
    }

    .hero-feat {
      display: flex;
      align-items: center;
      gap: 8px;
      font-size: 0.8rem;
      color: var(--grey-light);
      font-family: var(--font-title);
    }

    .hero-feat .dot {
      width: 8px; height: 8px;
      border-radius: 50%;
      background: var(--gold);
      flex-shrink: 0;
    }

    /* Right: form panel */
    .login-form-panel {
      width: 440px;
      flex-shrink: 0;
      background: var(--midnight-light);
      border-left: 1px solid rgba(212,175,55,0.12);
      display: flex;
      flex-direction: column;
      justify-content: center;
      padding: 52px 44px;
      overflow-y: auto;
    }

    .login-brand {
      display: flex;
      align-items: center;
      gap: 10px;
      margin-bottom: 36px;
    }

    .login-brand .logo {
      width: 44px; height: 44px;
      background: linear-gradient(135deg, var(--gold-dark), var(--gold));
      border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      font-size: 22px;
    }

    .login-brand-text { font-family: var(--font-title); }
    .login-brand-text .name { font-weight: 700; font-size: 1.05rem; color: var(--bone); }
    .login-brand-text .sub  { font-size: 0.65rem; color: var(--gold); letter-spacing: 2px; text-transform: uppercase; }

    .login-heading h2 {
      font-size: 1.6rem;
      color: var(--bone);
      margin-bottom: 6px;
    }

    .login-heading p {
      font-size: 0.85rem;
      color: var(--grey-mid);
      margin-bottom: 28px;
    }

    .or-divider {
      display: flex;
      align-items: center;
      gap: 12px;
      margin: 20px 0;
      font-size: 0.75rem;
      color: var(--grey-mid);
    }

    .or-divider::before,
    .or-divider::after {
      content: '';
      flex: 1;
      height: 1px;
      background: rgba(255,255,255,0.1);
    }

    .socials-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 8px;
      margin-bottom: 20px;
    }

    .socials-grid .social-btn:last-child:nth-child(odd) {
      grid-column: 1 / -1;
    }

    .forgot-link {
      text-align: right;
      margin-top: -12px;
      margin-bottom: 20px;
    }

    .forgot-link a { font-size: 0.8rem; color: var(--grey-mid); }
    .forgot-link a:hover { color: var(--gold); }

    .register-link {
      text-align: center;
      margin-top: 20px;
      font-size: 0.85rem;
      color: var(--grey-mid);
    }

    @media (max-width: 900px) {
      .login-hero { display: none; }
      .login-form-panel { width: 100%; padding: 40px 28px; }
    }
  </style>
</head>
<body>

<div class="login-wrap">

  <!-- ── LEFT: HERO ── -->
  <div class="login-hero">
    <div class="hero-pattern"></div>

    <div class="hero-text animate-in">
      <div class="hero-badge">✦ 4 Star Luxury Resort</div>
      <h1>Maya Bay<<em>Hotel</em></h1>
      <p>Koh Phi Phi Leh, Mar de Andamán — Tailandia.<br>
         Reserva tu estadía y traslados desde cualquier parte del mundo.</p>

      <div class="hero-features">
        <div class="hero-feat"><div class="dot"></div>40 Habitaciones de Lujo</div>
        <div class="hero-feat"><div class="dot"></div>3 Vehículos Privados</div>
        <div class="hero-feat"><div class="dot"></div>Reservas en Tiempo Real</div>
      </div>
    </div>
  </div>

  <!-- ── RIGHT: FORM ── -->
  <div class="login-form-panel">

    <div class="login-brand">
      <div class="logo">🏨</div>
      <div class="login-brand-text">
        <div class="name">Hotel Maya Bay</div>
        <div class="sub">★★★★ Resort</div>
      </div>
    </div>

    <div class="login-heading">
      <h2>Crear cuenta</h2>
      <p>Regístrate para gestionar tus reservas</p>
    </div>

    <?php if ($error): ?>
      <div class="alert alert-danger">⚠️ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="" novalidate>

      <div class="form-group">
        <label class="form-label" for="nombre">Nombre Completo</label>
        <input
          type="text" id="nombre" name="nombre" class="form-control"
          placeholder="Juan Pérez"
          value="<?= htmlspecialchars($_POST['nombre'] ?? '') ?>"
          required>
      </div>

      <div class="form-group">
        <label class="form-label" for="email">Correo Electrónico</label>
        <input
          type="email" id="email" name="email" class="form-control"
          placeholder="tu@correo.com"
          value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
          required>
      </div>

      <div class="form-group">
        <label class="form-label" for="password">Contraseña</label>
        <input type="password" id="password" name="password" class="form-control"
               placeholder="Mínimo 8 caracteres" required>
      </div>

      <div class="form-group">
        <label class="form-label" for="confirm_password">Confirmar Contraseña</label>
        <input type="password" id="confirm_password" name="confirm_password" class="form-control"
               placeholder="Repite tu contraseña" required>
      </div>

      <button type="submit" class="btn btn-gold btn-lg btn-block">
        Crear Cuenta →
      </button>

    </form>

    <p class="register-link">
      ¿Ya tienes cuenta? <a href="index.php">Iniciar sesión</a>
    </p>

  </div><!-- /.login-form-panel -->
</div><!-- /.login-wrap -->

</body>
</html>