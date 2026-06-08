<?php
// ============================================================
// PANTALLA 6 — Perfil y Ajustes  |  perfil.php
// ============================================================
session_start();
require_once 'config/firestore.php';
require_once 'config/auth.php';

$usuarioAuth = FirebaseAuth::getSession();

$respuesta = Firestore::get('usuarios', $usuarioAuth['uid']);

if ($respuesta['success']) {

    $usuario = $respuesta['data'];

} else {

    // Valores por defecto
    $usuario = [
        'nombre' => '',
        'apellido' => '',
        'email' => $usuarioAuth['email'] ?? '',
        'telefono' => '',
        'pais' => 'México',
        'idioma' => 'Español',
        'membresia' => 'Gold Member',
        'desde' => date('Y-m-d')
    ];

}
$pageTitle  = 'Mi Perfil';
$activePage = 'profile';

$saved  = false;
$errors = [];


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_perfil'])) {

    $nombre   = htmlspecialchars(trim($_POST['nombre'] ?? ''));
    $apellido = htmlspecialchars(trim($_POST['apellido'] ?? ''));
    $telefono = htmlspecialchars(trim($_POST['telefono'] ?? ''));
    $pais     = htmlspecialchars(trim($_POST['pais'] ?? ''));
    $idioma   = htmlspecialchars(trim($_POST['idioma'] ?? ''));

    if (empty($nombre)) {
        $errors[] = 'El nombre es obligatorio.';
    }

    if (empty($apellido)) {
        $errors[] = 'El apellido es obligatorio.';
    }

    if (empty($errors)) {

        $actualizar = Firestore::update(
            'usuarios',
            $usuario['uid'],
            [
                'nombre'   => $nombre,
                'apellido' => $apellido,
                'telefono' => $telefono,
                'pais'     => $pais,
                'idioma'   => $idioma
            ]
        );

        if ($actualizar['success']) {

            $usuario['nombre']   = $nombre;
            $usuario['apellido'] = $apellido;
            $usuario['telefono'] = $telefono;
            $usuario['pais']     = $pais;
            $usuario['idioma']   = $idioma;

            $saved = true;

        } else {

            $errors[] = 'No se pudo actualizar el perfil.';

        }
    }
}


$iniciales = strtoupper(
    substr($usuario['nombre'] ?? '', 0, 1) .
    substr($usuario['apellido'] ?? '', 0, 1)
);

if ($iniciales == '') {
    $iniciales = '';
}
include 'includes/header.php';
?>

<main class="page-content">
<div class="container">

  <div class="page-header animate-in">
    <h1>Perfil y Ajustes</h1>
    <p>Gestiona tu información personal y preferencias de cuenta.</p>
  </div>

  <?php if ($saved): ?>
    <div class="alert alert-success animate-in">✅ Perfil actualizado correctamente.</div>
  <?php endif; ?>
  <?php if (!empty($errors)): ?>
    <div class="alert alert-danger animate-in">
      <?php foreach ($errors as $e): ?><div>⚠️ <?= $e ?></div><?php endforeach; ?>
    </div>
  <?php endif; ?>

  <div style="display:grid;grid-template-columns:280px 1fr;gap:28px;align-items:start;">

    <!-- ── LEFT SIDEBAR ── -->
    <div>
      <!-- Avatar card -->
      <div class="card card-gold animate-in" style="text-align:center;padding:32px 20px;">
        <div class="profile-avatar" style="margin:0 auto 14px;"><?= $iniciales ?></div>
        <div style="font-family:var(--font-title);font-size:1.1rem;color:var(--bone);">
          <?= $usuario['nombre'] ?> <?= $usuario['apellido'] ?>
        </div>
        <div style="font-size:0.8rem;color:var(--grey-mid);margin-bottom:12px;">
          <?= $usuario['email'] ?>
        </div>
        <span class="badge badge-gold" style="margin:0 auto;">✦ <?= $usuario['membresia'] ?></span>
        <div style="margin-top:14px;font-size:0.75rem;color:var(--grey-mid);">
          Miembro desde <?= date('M Y', strtotime($usuario['desde'])) ?>
        </div>
      </div>

      <!-- Stats mini -->
      <div class="card animate-in" style="margin-top:14px;padding:18px;">
        <div style="font-size:0.72rem;color:var(--grey-mid);font-family:var(--font-title);letter-spacing:1px;text-transform:uppercase;margin-bottom:14px;">Resumen</div>
        <div style="display:flex;flex-direction:column;gap:12px;font-size:0.875rem;">
          <div style="display:flex;justify-content:space-between;align-items:center;">
            <span style="color:var(--grey-mid);">🛏 Estancias</span><strong style="color:var(--bone);">3</strong>
          </div>
          <div style="display:flex;justify-content:space-between;align-items:center;">
            <span style="color:var(--grey-mid);">🚐 Traslados</span><strong style="color:var(--bone);">2</strong>
          </div>
          <div style="display:flex;justify-content:space-between;align-items:center;">
            <span style="color:var(--grey-mid);">💰 Total gastado</span><strong style="color:var(--gold);">$25,150</strong>
          </div>
          <div style="display:flex;justify-content:space-between;align-items:center;">
            <span style="color:var(--grey-mid);">⭐ Noches totales</span><strong style="color:var(--bone);">10</strong>
          </div>
        </div>
      </div>

      <!-- Contact / Support links -->
      <div class="card animate-in" style="margin-top:14px;padding:18px;">
        <div style="font-size:0.72rem;color:var(--grey-mid);font-family:var(--font-title);letter-spacing:1px;text-transform:uppercase;margin-bottom:14px;">Soporte · Redes</div>
        <div style="display:flex;flex-direction:column;gap:8px;">
          <?php
          $redes = [
            ['label'=>'Facebook',  'icon'=>'f',  'color'=>'#1877F2', 'url'=>'https://facebook.com'],
            ['label'=>'Instagram', 'icon'=>'◉',  'color'=>'#E1306C', 'url'=>'https://instagram.com'],
            ['label'=>'X (Twitter)','icon'=>'𝕏', 'color'=>'#aaa',   'url'=>'https://twitter.com'],
            ['label'=>'TikTok',    'icon'=>'♪',  'color'=>'#69C9D0', 'url'=>'https://tiktok.com'],
            ['label'=>'Threads',   'icon'=>'@',  'color'=>'#bbb',    'url'=>'https://threads.net'],
          ];
          foreach ($redes as $red): ?>
          <a href="<?= $red['url'] ?>" target="_blank" rel="noopener"
             style="display:flex;align-items:center;gap:10px;padding:9px 12px;border-radius:6px;background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.08);color:var(--grey-light);font-size:0.85rem;transition:var(--transition);"
             onmouseover="this.style.background='rgba(255,255,255,0.08)'"
             onmouseout="this.style.background='rgba(255,255,255,0.03)'">
            <span style="width:22px;text-align:center;color:<?= $red['color'] ?>;font-weight:700;"><?= $red['icon'] ?></span>
            <?= $red['label'] ?>
          </a>
          <?php endforeach; ?>
        </div>
      </div>

    </div><!-- /.sidebar -->

    <!-- ── RIGHT: EDIT FORM ── -->
    <div>

      <!-- Tabs -->
      <div class="tabs animate-in">
        <button class="tab-btn active" onclick="switchTab('personal',this)">Datos Personales</button>
        <button class="tab-btn"        onclick="switchTab('seguridad',this)">Seguridad</button>
        <button class="tab-btn"        onclick="switchTab('notificaciones',this)">Notificaciones</button>
      </div>

      <!-- ── Tab: Personal ── -->
      <div id="tab-personal" class="card animate-in">
        <h3 style="font-family:var(--font-title);font-size:1rem;margin-bottom:6px;">Información Personal</h3>
        <div class="gold-divider"></div>

        <form method="POST" action="perfil.php">
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Nombre</label>
              <input type="text" name="nombre" class="form-control"
                     value="<?= htmlspecialchars($usuario['nombre']) ?>" required>
            </div>
            <div class="form-group">
              <label class="form-label">Apellido</label>
              <input type="text" name="apellido" class="form-control"
                     value="<?= htmlspecialchars($usuario['apellido']) ?>" required>
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">Correo Electrónico</label>
            <input type="email" class="form-control"
                   value="<?= htmlspecialchars($usuarioAuth['email']) ?>"
                   disabled style="opacity:0.5;cursor:not-allowed;">
            <div class="form-hint">El correo se gestiona desde Firebase Auth.</div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Teléfono</label>
              <input type="tel" name="telefono" class="form-control"
                     placeholder="+52 55 0000 0000"
                     value="<?= htmlspecialchars($usuario['telefono']) ?>">
            </div>
            <div class="form-group">
              <label class="form-label">País</label>
              <select name="pais" class="form-control">
                <?php
                $paises = ['México','Argentina','Colombia','España','Estados Unidos','Tailandia','Otro'];
                foreach ($paises as $p):
                  $sel = $p === $usuario['pais'] ? 'selected' : '';
                ?>
                  <option <?= $sel ?>><?= $p ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">Idioma Preferido</label>
            <select name="idioma" class="form-control">
              <?php foreach (['Español','English','Français','日本語','中文'] as $lang):
                    $sel = $lang === $usuario['idioma'] ? 'selected' : ''; ?>
                <option <?= $sel ?>><?= $lang ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div style="display:flex;gap:12px;margin-top:4px;">
            <button type="submit" name="guardar_perfil" class="btn btn-gold">Guardar Cambios</button>
            <a href="dashboard.php" class="btn btn-ghost">Cancelar</a>
          </div>
        </form>
      </div>

      <!-- ── Tab: Seguridad ── -->
      <div id="tab-seguridad" class="card animate-in" style="display:none;">
        <h3 style="font-family:var(--font-title);font-size:1rem;margin-bottom:6px;">Seguridad de la Cuenta</h3>
        <div class="gold-divider"></div>

        <div class="form-group">
          <label class="form-label">Contraseña Actual</label>
          <input type="password" class="form-control" placeholder="••••••••">
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Nueva Contraseña</label>
            <input type="password" class="form-control" placeholder="••••••••">
          </div>
          <div class="form-group">
            <label class="form-label">Confirmar Contraseña</label>
            <input type="password" class="form-control" placeholder="••••••••">
          </div>
        </div>
        <button class="btn btn-gold" onclick="alert('Cambio de contraseña — integrar Firebase Auth')">Actualizar Contraseña</button>

        <div style="margin-top:28px;padding-top:20px;border-top:1px solid rgba(255,255,255,0.07);">
          <h4 style="font-family:var(--font-title);font-size:0.9rem;margin-bottom:12px;color:var(--danger);">Zona de Peligro</h4>
          <button class="btn btn-danger btn-sm" onclick="return confirm('¿Cerrar sesión?') && (window.location='index.php')">
            Cerrar Sesión
          </button>
        </div>
      </div>

      <!-- ── Tab: Notificaciones ── -->
      <div id="tab-notificaciones" class="card animate-in" style="display:none;">
        <h3 style="font-family:var(--font-title);font-size:1rem;margin-bottom:6px;">Preferencias de Notificación</h3>
        <div class="gold-divider"></div>

        <?php
        $prefs = [
          ['id'=>'notif_reserva',   'label'=>'Confirmación de reserva',       'on'=>true],
          ['id'=>'notif_checkin',   'label'=>'Recordatorio de check-in',       'on'=>true],
          ['id'=>'notif_traslado',  'label'=>'Recordatorio de traslado',       'on'=>true],
          ['id'=>'notif_promo',     'label'=>'Ofertas y promociones',          'on'=>false],
          ['id'=>'notif_newsletter','label'=>'Newsletter mensual',             'on'=>false],
        ];
        ?>
        <div style="display:flex;flex-direction:column;gap:14px;">
          <?php foreach ($prefs as $p): ?>
          <label style="display:flex;align-items:center;justify-content:space-between;cursor:pointer;padding:12px 16px;background:rgba(255,255,255,0.03);border-radius:var(--radius-sm);border:1px solid rgba(255,255,255,0.06);">
            <span style="font-size:0.875rem;color:var(--bone);"><?= $p['label'] ?></span>
            <input type="checkbox" <?= $p['on']?'checked':'' ?>
                   style="width:18px;height:18px;accent-color:var(--gold);cursor:pointer;">
          </label>
          <?php endforeach; ?>
        </div>

        <button class="btn btn-gold" style="margin-top:20px;"
                onclick="alert('Preferencias guardadas (integrar Firebase)')">Guardar Preferencias</button>
      </div>

    </div><!-- /.right -->
  </div><!-- /.grid -->

</div>
</main>

<script>
function switchTab(name, btn) {
  document.querySelectorAll('[id^="tab-"]').forEach(t => t.style.display = 'none');
  document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
  document.getElementById('tab-' + name).style.display = 'block';
  btn.classList.add('active');
}
</script>

<?php include 'includes/footer.php'; ?>
