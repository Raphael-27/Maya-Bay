<?php
// ============================================================
// PANTALLA 2 — Dashboard / Home  |  dashboard.php
// ============================================================
session_start();
$pageTitle  = 'Dashboard';
$activePage = 'home';
include 'includes/header.php';
require_once 'config/auth.php';

$usuario = FirebaseAuth::getSession();
require_once 'config/firestore.php';

// Obtener todas las habitaciones
$respuesta = Firestore::getAll('habitaciones');

$habitaciones = [];

if ($respuesta['success']) {
    $habitaciones = $respuesta['data'];
}

// Calcular estadísticas
$habitacionesTotales = count($habitaciones);

$habitacionesLibres = 0;

foreach ($habitaciones as $habitacion) {
    if (
        isset($habitacion['estatus']) &&
        strtolower($habitacion['estatus']) === 'disponible'
    ) {
        $habitacionesLibres++;
    }
}

$habitacionesOcupadas = $habitacionesTotales - $habitacionesLibres;

$pctOcupacion = $habitacionesTotales > 0
    ? round(($habitacionesOcupadas / $habitacionesTotales) * 100)
    : 0;
?>

<main class="page-content">
  <div class="container">

    <!-- ── PAGE HEADER ── -->
    <div class="page-header animate-in">
      <h1>
    Bienvenido,
    <?= htmlspecialchars($usuario['displayName'] ?? $usuario['email']) ?>
     </h1>
      <p>Gestiona tus reservas y traslados desde aquí.</p>
    </div>

    <!-- ── KPI STATS ── -->
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:16px;margin-bottom:36px;">

      <div class="stat-card animate-in">
        <div class="stat-label">Habitaciones Libres</div>
        <div class="stat-value"><?= $habitacionesLibres ?></div>
        <div class="stat-sub">de <?= $habitacionesTotales ?> totales</div>
        <div class="capacity-bar-wrap" style="margin-top:10px;">
          <div class="capacity-bar">
            <div class="capacity-bar-fill <?= $pctOcupacion>80?'danger':($pctOcupacion>60?'warning':'') ?>"
                 style="width:<?= $pctOcupacion ?>%"></div>
          </div>
          <div style="font-size:0.72rem;color:var(--grey-mid);margin-top:3px;"><?= $pctOcupacion ?>% ocupación</div>
        </div>
      </div>

      <div class="stat-card animate-in">
        <div class="stat-label">Vehículos Activos</div>
        <div class="stat-value">3</div>
        <div class="stat-sub">12 pax máx. por trayecto</div>
      </div>

      <div class="stat-card animate-in">
        <div class="stat-label">Mis Reservas</div>
        <div class="stat-value">2</div>
        <div class="stat-sub">activas este mes</div>
      </div>

      <div class="stat-card animate-in">
        <div class="stat-label">Próximo Check-in</div>
        <div class="stat-value" style="font-size:1.4rem;">Jun 15</div>
        <div class="stat-sub">Hab. 301 — Suite Premium</div>
      </div>

    </div>

    <!-- ── QUICK ACTIONS ── -->
    <div style="display:flex;gap:12px;flex-wrap:wrap;margin-bottom:36px;" class="animate-in">
      <a href="reserva-habitacion.php" class="btn btn-gold btn-lg">Reservar Habitación</a>
      <a href="reserva-traslado.php"   class="btn btn-outline btn-lg">Reservar Traslado</a>
      <a href="historial.php"          class="btn btn-ghost btn-lg">Ver Mis Reservas</a>
    </div>

    <!-- ── SEARCH BAR ── -->
    <div class="card animate-in" style="margin-bottom:32px;padding:20px 28px;">
      <h3 style="font-size:0.9rem;color:var(--grey-mid);text-transform:uppercase;letter-spacing:1px;margin-bottom:14px;">
        🔍 Búsqueda Rápida de Habitaciones
      </h3>
      <form method="GET" action="reserva-habitacion.php">
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr auto;gap:12px;align-items:end;">
          <div class="form-group" style="margin:0;">
            <label class="form-label">Check-in</label>
            <input type="date" name="checkin" class="form-control"
                   min="<?= date('Y-m-d') ?>" value="<?= date('Y-m-d', strtotime('+1 day')) ?>">
          </div>
          <div class="form-group" style="margin:0;">
            <label class="form-label">Check-out</label>
            <input type="date" name="checkout" class="form-control"
                   min="<?= date('Y-m-d') ?>" value="<?= date('Y-m-d', strtotime('+6 days')) ?>">
          </div>
          <div class="form-group" style="margin:0;">
            <label class="form-label">Huéspedes</label>
            <select name="guests" class="form-control">
              <option>1 huésped</option><option>2 huéspedes</option>
              <option>3 huéspedes</option><option>4+ huéspedes</option>
            </select>
          </div>
          <button type="submit" class="btn btn-gold" style="height:46px;">Buscar</button>
        </div>
      </form>
    </div>

    <!-- ── AVAILABLE ROOMS GRID ── -->
    <div style="margin-bottom:12px;display:flex;justify-content:space-between;align-items:center;" class="animate-in">
      <h2 style="font-size:1.2rem;">🛏 Habitaciones Disponibles</h2>
      <a href="reserva-habitacion.php" style="font-size:0.85rem;color:var(--gold);">Ver todas →</a>
    </div>

    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:20px;margin-bottom:40px;">
      <?php foreach ($habitaciones as $h): ?>
      <div class="room-card animate-in">
        <div class="room-card-img">
         <img src="<?= $h['imagen'] ?>"
         alt="<?= htmlspecialchars($h['tipo']) ?>"
         style="width:100%; height:100%; object-fit:cover;">
        </div>
        <div class="room-card-body">
          <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:6px;">
            <h3>Hab. <?= $h['numero'] ?> — <?= $h['tipo'] ?></h3>
            <?php if (($h['estatus'] ?? '') === 'disponible'): ?>
    <span class="badge badge-success">✓ Disponible</span>
<?php else: ?>
    <span class="badge badge-warning">Ocupada</span>
<?php endif; ?>
          </div>
          <div class="room-price">$<?= number_format($h['precio']) ?> <span>MXN/noche</span></div>
          <div style="margin-top:12px;">
            <a href="reserva-habitacion.php?hab=<?= $h['id'] ?>" class="btn btn-gold btn-sm btn-block">
              Reservar
            </a>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- ── TRANSFER SECTION ── -->
    <div class="card card-gold animate-in" style="margin-bottom:32px;">
      <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:16px;">
        <div>
          <h2 style="font-size:1.15rem;margin-bottom:4px;">🚐 Servicio de Traslados</h2>
          <div class="gold-divider"></div>
          <p style="color:var(--grey-mid);font-size:0.875rem;max-width:460px;">
            3 camionetas disponibles · Máx. 12 pasajeros por trayecto · Ventana de 20 min.
            Reserva tu traslado del aeropuerto directo a Maya Bay.
          </p>
        </div>
        <a href="reserva-traslado.php" class="btn btn-gold btn-lg">
          🚐 Reservar Traslado
        </a>
      </div>
    </div>

  </div><!-- /.container -->
</main>

<?php include 'includes/footer.php'; ?>
