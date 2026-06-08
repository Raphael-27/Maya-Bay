<?php
session_start();
if (isset($_SESSION['firebase_user'])) { header('Location: dashboard.php'); exit; }
require_once 'config/auth.php';
require_once 'config/firestore.php';

$error = $success = '';
$tab   = 'login';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action   = $_POST['action']   ?? 'login';
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');
    $nombre   = trim($_POST['nombre']   ?? '');
    $tab      = $action;

    if (empty($email) || empty($password))
        $error = 'Por favor completa todos los campos.';
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL))
        $error = 'Introduce un correo electrónico válido.';
    elseif ($action === 'register' && empty($nombre))
        $error = 'El nombre es obligatorio para registrarse.';
    else {
        if ($action === 'register') {
            $result = FirebaseAuth::register($email, $password, $nombre);
            if (!$result['success']) { $error = $result['error']; }
            else {
                Firestore::create('usuarios', [
                    'uid'=>$result['uid'],'nombre'=>$nombre,'email'=>$email,
                    'membresia'=>'Estándar','desde'=>date('Y-m-d'),
                ], $result['idToken']);
                FirebaseAuth::saveSession($result);
                header('Location: dashboard.php'); exit;
            }
        } else {
            $result = FirebaseAuth::login($email, $password);
            if (!$result['success']) { $error = $result['error']; }
            else { FirebaseAuth::saveSession($result); header('Location: dashboard.php'); exit; }
        }
    }
}

if (isset($_POST['action']) && $_POST['action'] === 'reset') {
    $email  = trim($_POST['email'] ?? '');
    $result = FirebaseAuth::sendPasswordReset($email);
    $success = $result['success'] ? 'Se envió el enlace a '.htmlspecialchars($email) : $result['error'];
    $tab = 'reset';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Hotel Maya Bay — Bienvenido</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Pacifico&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}

:root{
  --sky:#E0F7FF;
  --ocean:#0096C7;
  --deep:#023E8A;
  --wave:#90E0EF;
  --sand:#FFF3CD;
  --coral:#FF6B6B;
  --coral-dark:#E63946;
  --sun:#FFD166;
  --foam:#FAFEFF;
  --text:#1A3A4A;
  --text-muted:#5A8A9A;
  --white:#FFFFFF;
  --radius:16px;
  --shadow:0 8px 32px rgba(0,150,199,0.15);
}

html,body{min-height:100vh;font-family:'Nunito',sans-serif;color:var(--text);}

/* ── BACKGROUND ── */
body{
  background: url('https://upload.wikimedia.org/wikipedia/commons/3/34/Maya_Bay%2C_Thailand_by_Mike_Clegg_Photography.jpg') center center / cover no-repeat fixed;
  min-height:100vh;
  overflow-x:hidden;
}
body::before{
  content:'';
  position:fixed;inset:0;
  background:linear-gradient(160deg, rgba(0,30,60,0.45) 0%, rgba(0,80,130,0.35) 50%, rgba(0,40,80,0.55) 100%);
  z-index:0;
}

/* ── SUN ── */
.sun{
  position:fixed;top:40px;right:80px;
  width:90px;height:90px;
  background:radial-gradient(circle, #FFE566 60%, #FFD166 100%);
  border-radius:50%;
  box-shadow:0 0 0 16px rgba(255,209,102,0.18),0 0 0 32px rgba(255,209,102,0.08);
  animation:pulse-sun 4s ease-in-out infinite;
  z-index:0;
}
@keyframes pulse-sun{0%,100%{box-shadow:0 0 0 16px rgba(255,209,102,0.18),0 0 0 32px rgba(255,209,102,0.08)}50%{box-shadow:0 0 0 22px rgba(255,209,102,0.22),0 0 0 44px rgba(255,209,102,0.10)}}

/* ── PALM TREES ── */
.palm{position:fixed;bottom:0;z-index:0;pointer-events:none;}
.palm-left{left:-10px;}
.palm-right{right:-10px;transform:scaleX(-1);}

/* ── WAVES ── */
.waves-bottom{
  position:fixed;bottom:0;left:0;width:100%;z-index:1;pointer-events:none;
  line-height:0;
}
.waves-bottom svg{display:block;width:100%;}

/* ── LAYOUT ── */
.page{
  display:flex;align-items:center;justify-content:center;
  min-height:100vh;padding:24px;position:relative;z-index:2;
}

/* ── CARD ── */
.card{
  background:rgba(255,255,255,0.88);
  backdrop-filter:blur(20px);
  -webkit-backdrop-filter:blur(20px);
  border:1.5px solid rgba(144,224,239,0.5);
  border-radius:28px;
  box-shadow:0 20px 60px rgba(0,96,150,0.18),0 4px 12px rgba(0,150,199,0.08);
  width:100%;max-width:440px;
  overflow:hidden;
  animation:float-in 0.7s cubic-bezier(.22,1,.36,1) both;
}
@keyframes float-in{from{opacity:0;transform:translateY(32px) scale(0.97)}to{opacity:1;transform:none}}

/* ── CARD HEADER ── */
.card-header{
  background:linear-gradient(135deg,#0096C7 0%,#023E8A 100%);
  padding:32px 36px 24px;
  text-align:center;
  position:relative;
  overflow:hidden;
}
.card-header::before{
  content:'';position:absolute;inset:0;
  background:repeating-linear-gradient(
    -45deg,rgba(255,255,255,0.04) 0px,rgba(255,255,255,0.04) 1px,
    transparent 1px,transparent 12px);
}
.hotel-logo{
  width:64px;height:64px;margin:0 auto 12px;
  background:rgba(255,255,255,0.15);
  border:2px solid rgba(255,255,255,0.35);
  border-radius:50%;display:flex;align-items:center;justify-content:center;
  font-size:30px;position:relative;z-index:1;
  box-shadow:0 4px 16px rgba(0,0,0,0.15);
}
.hotel-title{
  font-family:'Pacifico',cursive;
  font-size:1.6rem;color:#fff;
  letter-spacing:0.5px;line-height:1.1;
  position:relative;z-index:1;
  text-shadow:0 2px 8px rgba(0,0,0,0.2);
}
.hotel-sub{
  font-size:0.8rem;color:rgba(255,255,255,0.75);
  letter-spacing:2px;text-transform:uppercase;
  margin-top:4px;position:relative;z-index:1;
  font-weight:600;
}
.stars{color:var(--sun);font-size:0.85rem;margin-top:6px;position:relative;z-index:1;}

/* ── TABS ── */
.tabs{
  display:flex;background:#F0FAFF;
  border-bottom:1.5px solid rgba(144,224,239,0.5);
}
.tab-btn{
  flex:1;padding:13px 8px;border:none;background:transparent;cursor:pointer;
  font-family:'Nunito',sans-serif;font-weight:700;font-size:0.875rem;
  color:var(--text-muted);transition:all .25s;position:relative;
}
.tab-btn.active{color:var(--ocean);}
.tab-btn.active::after{
  content:'';position:absolute;bottom:0;left:16px;right:16px;height:2.5px;
  background:linear-gradient(90deg,var(--ocean),#48CAE4);border-radius:2px;
}

/* ── BODY ── */
.card-body{padding:28px 32px 32px;}

/* ── FORMS ── */
.form-group{margin-bottom:18px;}
.form-label{
  display:block;font-size:0.78rem;font-weight:700;
  color:var(--text-muted);letter-spacing:0.8px;text-transform:uppercase;
  margin-bottom:7px;
}
.input-wrap{position:relative;}
.input-icon{
  position:absolute;left:14px;top:50%;transform:translateY(-50%);
  font-size:1rem;pointer-events:none;
}
.form-control{
  width:100%;padding:12px 14px 12px 40px;
  border:1.5px solid rgba(144,224,239,0.6);
  border-radius:12px;
  background:rgba(240,250,255,0.7);
  color:var(--text);font-family:'Nunito',sans-serif;font-size:0.95rem;
  outline:none;transition:all .2s;
}
.form-control:focus{
  border-color:var(--ocean);
  background:#fff;
  box-shadow:0 0 0 4px rgba(0,150,199,0.1);
}
.form-control::placeholder{color:#A0C8D8;}

/* ── BUTTON ── */
.btn-primary{
  width:100%;padding:14px;border:none;cursor:pointer;
  border-radius:14px;font-family:'Nunito',sans-serif;
  font-weight:800;font-size:1rem;letter-spacing:0.3px;
  background:linear-gradient(135deg,#00B4D8,#0096C7 50%,#0077B6);
  color:#fff;
  box-shadow:0 4px 18px rgba(0,150,199,0.35);
  transition:all .25s;position:relative;overflow:hidden;
}
.btn-primary:hover{
  transform:translateY(-2px);
  box-shadow:0 8px 24px rgba(0,150,199,0.45);
}
.btn-primary:active{transform:translateY(0);}
.btn-primary::after{
  content:'';position:absolute;inset:0;
  background:linear-gradient(180deg,rgba(255,255,255,0.15) 0%,transparent 60%);
  border-radius:inherit;pointer-events:none;
}

/* ── DIVIDER ── */
.or-divider{
  display:flex;align-items:center;gap:10px;
  margin:20px 0;font-size:0.78rem;color:#A0C8D8;font-weight:600;
}
.or-divider::before,.or-divider::after{content:'';flex:1;height:1px;background:rgba(144,224,239,0.5);}

/* ── SOCIAL BUTTONS ── */
.social-grid{display:grid;grid-template-columns:1fr 1fr;gap:8px;}
.social-grid .social-btn:last-child:nth-child(odd){grid-column:1/-1;}
.social-btn{
  display:flex;align-items:center;justify-content:center;gap:7px;
  padding:10px 12px;border:1.5px solid rgba(144,224,239,0.5);
  border-radius:10px;background:rgba(240,250,255,0.5);
  color:var(--text-muted);font-family:'Nunito',sans-serif;font-weight:600;
  font-size:0.82rem;cursor:pointer;transition:all .2s;
}
.social-btn:hover{background:#E0F7FF;border-color:var(--wave);color:var(--text);}

/* ── ALERTS ── */
.alert{
  padding:12px 14px;border-radius:10px;font-size:0.85rem;
  margin-bottom:18px;display:flex;align-items:flex-start;gap:8px;font-weight:600;
}
.alert-error{background:#FFF0F0;border:1px solid #FFCDD2;color:#C62828;}
.alert-success{background:#E8FFF3;border:1px solid #A5D6A7;color:#2E7D32;}

/* ── FOOTER LINKS ── */
.card-footer-link{
  text-align:center;margin-top:20px;font-size:0.85rem;color:var(--text-muted);
}
.card-footer-link a{color:var(--ocean);font-weight:700;text-decoration:none;}
.card-footer-link a:hover{color:var(--deep);}

.forgot{text-align:right;margin:-10px 0 16px;}
.forgot a{font-size:0.8rem;color:var(--text-muted);cursor:pointer;font-weight:600;}
.forgot a:hover{color:var(--ocean);}

/* ── WAVE BADGE ── */
.location-badge{
  display:inline-flex;align-items:center;gap:6px;
  background:rgba(255,255,255,0.2);border:1px solid rgba(255,255,255,0.3);
  color:rgba(255,255,255,0.9);font-size:0.72rem;font-weight:700;
  letter-spacing:1px;text-transform:uppercase;
  padding:4px 12px;border-radius:20px;margin-top:10px;
  position:relative;z-index:1;
}

.hidden{display:none;}
</style>
</head>
<body>

<!-- ── SUN ── -->
<div class="sun"></div>

<!-- ── PALM TREES ── -->
<svg class="palm palm-left" width="140" height="340" viewBox="0 0 140 340" fill="none" xmlns="http://www.w3.org/2000/svg">
  <rect x="58" y="120" width="18" height="220" rx="9" fill="#5C8A3C" opacity=".85"/>
  <!-- trunk curve -->
  <path d="M60 130 Q40 200 65 330" stroke="#4A7A2A" stroke-width="14" stroke-linecap="round" fill="none" opacity=".7"/>
  <!-- leaves -->
  <ellipse cx="62" cy="118" rx="55" ry="18" fill="#6AAF3D" transform="rotate(-20 62 118)" opacity=".9"/>
  <ellipse cx="70" cy="110" rx="58" ry="16" fill="#7DC44A" transform="rotate(15 70 110)" opacity=".85"/>
  <ellipse cx="55" cy="105" rx="52" ry="15" fill="#6AAF3D" transform="rotate(-45 55 105)" opacity=".8"/>
  <ellipse cx="80" cy="108" rx="50" ry="14" fill="#7DC44A" transform="rotate(40 80 108)" opacity=".75"/>
  <ellipse cx="45" cy="120" rx="48" ry="13" fill="#5C9E33" transform="rotate(-65 45 120)" opacity=".7"/>
  <!-- coconuts -->
  <circle cx="66" cy="124" r="7" fill="#D4843A" opacity=".9"/>
  <circle cx="76" cy="130" r="6" fill="#C97530" opacity=".85"/>
</svg>

<svg class="palm palm-right" width="120" height="300" viewBox="0 0 120 300" fill="none" xmlns="http://www.w3.org/2000/svg">
  <path d="M55 100 Q35 180 58 295" stroke="#4A7A2A" stroke-width="13" stroke-linecap="round" fill="none" opacity=".75"/>
  <ellipse cx="58" cy="98" rx="50" ry="16" fill="#6AAF3D" transform="rotate(-18 58 98)" opacity=".9"/>
  <ellipse cx="64" cy="90" rx="54" ry="15" fill="#7DC44A" transform="rotate(18 64 90)" opacity=".85"/>
  <ellipse cx="50" cy="88" rx="48" ry="14" fill="#6AAF3D" transform="rotate(-48 50 88)" opacity=".8"/>
  <ellipse cx="75" cy="92" rx="46" ry="13" fill="#7DC44A" transform="rotate(42 75 92)" opacity=".75"/>
  <circle cx="62" cy="104" r="7" fill="#D4843A" opacity=".85"/>
</svg>

<!-- ── WAVES ── -->
<div class="waves-bottom">
  <svg viewBox="0 0 1440 120" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none">
    <path d="M0,60 C240,100 480,20 720,60 C960,100 1200,20 1440,60 L1440,120 L0,120 Z" fill="rgba(0,119,182,0.4)"/>
    <path d="M0,80 C200,50 440,110 720,80 C1000,50 1240,110 1440,80 L1440,120 L0,120 Z" fill="rgba(0,150,199,0.55)"/>
    <path d="M0,100 C300,70 600,120 900,100 C1100,85 1300,110 1440,95 L1440,120 L0,120 Z" fill="#0096C7"/>
  </svg>
</div>

<!-- ── MAIN CARD ── -->
<div class="page">
<div class="card">

  <!-- HEADER -->
  <div class="card-header">
    <div class="hotel-logo">🏖️</div>
    <div class="hotel-title">Hotel Maya Bay</div>
    <div class="hotel-sub">Koh Phi Phi Leh · Tailandia</div>
    <div class="stars">★★★★ Luxury Beach Resort</div>
    <div class="location-badge">🌊 Mar de Andamán</div>
  </div>

  <!-- TABS -->
  <div class="tabs" id="tabBar">
    <button class="tab-btn <?= $tab!=='register'?'active':'' ?>" onclick="showTab('login')">Iniciar Sesión</button>
    <button class="tab-btn <?= $tab==='register'?'active':'' ?>" onclick="showTab('register')">Crear Cuenta</button>
  </div>

  <!-- BODY -->
  <div class="card-body">

    <?php if ($error):   ?><div class="alert alert-error">⚠️ <?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success">✅ <?= htmlspecialchars($success) ?></div><?php endif; ?>

    <!-- LOGIN -->
    <div id="formLogin" class="<?= $tab==='register'?'hidden':'' ?>">
      <form method="POST" action="index.php">
        <input type="hidden" name="action" value="login">
        <div class="form-group">
          <label class="form-label">Correo Electrónico</label>
          <div class="input-wrap">
            <span class="input-icon">📧</span>
            <input type="email" name="email" class="form-control" placeholder="tu@correo.com"
                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Contraseña</label>
          <div class="input-wrap">
            <span class="input-icon">🔑</span>
            <input type="password" name="password" class="form-control" placeholder="••••••••" required>
          </div>
        </div>
        <div class="forgot"><a onclick="showTab('reset')">¿Olvidaste tu contraseña?</a></div>
        <button type="submit" class="btn-primary">🌊 Iniciar Sesión</button>
      </form>

      <div class="or-divider">o continúa con</div>
      <div class="social-grid">
        <button class="social-btn" onclick="alert('Habilitar en Firebase Console → Authentication → Sign-in method')">
          <span style="color:#1877F2;font-weight:800;">f</span> Facebook
        </button>
        <button class="social-btn" onclick="alert('Habilitar en Firebase Console → Authentication')">
          <span style="color:#E1306C;">●</span> Instagram
        </button>
        <button class="social-btn" onclick="alert('Habilitar en Firebase Console → Authentication')">
          <span style="font-weight:800;">𝕏</span> Twitter
        </button>
        <button class="social-btn" onclick="alert('Habilitar en Firebase Console → Authentication')">
          <span style="color:#69C9D0;">♪</span> TikTok
        </button>
        <button class="social-btn" onclick="alert('Habilitar en Firebase Console → Authentication')">
          <span>@</span> Threads
        </button>
      </div>
      <p class="card-footer-link">¿Primera vez aquí? <a onclick="showTab('register')">Crear cuenta gratis</a></p>
    </div>

    <!-- REGISTER -->
    <div id="formRegister" class="<?= $tab!=='register'?'hidden':'' ?>">
      <form method="POST" action="index.php">
        <input type="hidden" name="action" value="register">
        <div class="form-group">
          <label class="form-label">Nombre Completo</label>
          <div class="input-wrap">
            <span class="input-icon">🧑</span>
            <input type="text" name="nombre" class="form-control" placeholder="Tu nombre" required>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Correo Electrónico</label>
          <div class="input-wrap">
            <span class="input-icon">📧</span>
            <input type="email" name="email" class="form-control" placeholder="tu@correo.com" required>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Contraseña</label>
          <div class="input-wrap">
            <span class="input-icon">🔑</span>
            <input type="password" name="password" class="form-control" placeholder="Mínimo 6 caracteres" required>
          </div>
        </div>
        <button type="submit" class="btn-primary">🏖️ Crear mi Cuenta</button>
      </form>
      <p class="card-footer-link">¿Ya tienes cuenta? <a onclick="showTab('login')">Iniciar sesión</a></p>
    </div>

    <!-- RESET -->
    <div id="formReset" class="hidden">
      <p style="color:var(--text-muted);font-size:.875rem;margin-bottom:18px;line-height:1.6;">
        Ingresa tu correo y te enviaremos un enlace para restablecer tu contraseña. 📬
      </p>
      <form method="POST" action="index.php">
        <input type="hidden" name="action" value="reset">
        <div class="form-group">
          <label class="form-label">Correo Electrónico</label>
          <div class="input-wrap">
            <span class="input-icon">📧</span>
            <input type="email" name="email" class="form-control" placeholder="tu@correo.com" required>
          </div>
        </div>
        <div style="display:flex;gap:10px;">
          <button type="submit" class="btn-primary" style="flex:1;">Enviar Enlace</button>
          <button type="button" onclick="showTab('login')"
                  style="padding:14px 18px;border:1.5px solid rgba(144,224,239,0.6);border-radius:14px;
                  background:transparent;cursor:pointer;font-family:'Nunito',sans-serif;font-weight:700;
                  color:var(--text-muted);transition:all .2s;">Volver</button>
        </div>
      </form>
    </div>

  </div><!-- /.card-body -->
</div><!-- /.card -->
</div><!-- /.page -->

<script>
function showTab(tab) {
  ['login','register','reset'].forEach(t => {
    document.getElementById('form'+t.charAt(0).toUpperCase()+t.slice(1)).classList.add('hidden');
  });
  document.getElementById('form'+tab.charAt(0).toUpperCase()+tab.slice(1)).classList.remove('hidden');
  document.querySelectorAll('.tab-btn').forEach((b,i) => {
    b.classList.remove('active');
    if ((i===0&&tab==='login')||(i===1&&tab==='register')) b.classList.add('active');
  });
}
</script>
</body>
</html>