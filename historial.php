<?php
// ============================================================
// PANTALLA 5 — Historial de Reservas  |  historial.php
// ============================================================
session_start();
$pageTitle  = 'Mis Reservas';
$activePage = 'history';

require_once 'config/firestore.php';
require_once 'config/auth.php';

$usuario = FirebaseAuth::getSession();
// Cancelar reserva
if (isset($_GET['cancelar'])) {

    $idReserva = $_GET['cancelar'];

    $respuesta = Firestore::get('reservas', $idReserva);

    if ($respuesta['success']) {

        $reserva = $respuesta['data'];

        // Cambiar estado de la reserva
        Firestore::update(
            'reservas',
            $idReserva,
            [
                'estado' => 'cancelada'
            ]
        );

        // Liberar la habitación
        Firestore::update(
            'habitaciones',
            $reserva['habitacion_id'],
            [
                'estatus' => 'disponible'
            ]
        );

    }

    header("Location: historial.php");
    exit;

}
$respuesta = Firestore::getAll('reservas');

$reservas = [];

if ($respuesta['success']) {

    foreach ($respuesta['data'] as $reserva) {

        // Solo mostrar las reservas del usuario actual
        if (($reserva['uid'] ?? '') == ($usuario['uid'] ?? '')) {

            $reservas[] = [

                'id' => $reserva['id'],

                'tipo' => 'habitacion',

                'titulo' => ucfirst($reserva['tipo_habitacion']),

                'detalle' =>
                    'Hab. ' .
                    $reserva['habitacion_numero'] .
                    ' · Check-in ' .
                    date('d M Y', strtotime($reserva['fecha_entrada'])) .
                    ' · Check-out ' .
                    date('d M Y', strtotime($reserva['fecha_salida'])) .
                    ' · ' .
                    $reserva['huespedes'] .
                    ' huésped(es)',

                'fecha' => $reserva['fecha_creacion'],

                'total' => $reserva['total'],

                // Si en Firestore guardas "confirmada",
                // la mostramos como activa
                'estatus' =>
                    $reserva['estado'] == 'confirmada'
                        ? 'activa'
                        : $reserva['estado'],

                'noches' =>
                    (new DateTime($reserva['fecha_entrada']))
                        ->diff(new DateTime($reserva['fecha_salida']))
                        ->days,

                'icon' => '🛏'

            ];

        }

    }

}

$filtro = $_GET['filtro'] ?? 'todas';
if ($filtro !== 'todas') {
    $reservas = array_filter($reservas, fn($r) => $r['estatus'] === $filtro);
}
$totalReservas = count($reservas);

$activas = 0;
$completadas = 0;
$canceladas = 0;
$totalGastado = 0;

foreach ($reservas as $r) {

    if ($r['estatus'] == 'activa') {
        $activas++;
    }

    if ($r['estatus'] == 'completada') {
        $completadas++;
    }

    if ($r['estatus'] == 'cancelada') {
        $canceladas++;
    }

    $totalGastado += $r['total'];

}
include 'includes/header.php';
?>

<main class="page-content">
<div class="container">

  <div class="page-header animate-in">
    <p class="breadcrumb">🏠 <a href="dashboard.php">Inicio</a> / <span>Mis Reservas</span></p>
    <h1>📋 Historial de Reservas</h1>
    <p>Consulta todas tus reservas de habitaciones y traslados.</p>
  </div>

  <!-- ── SUMMARY STATS ── -->
  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:14px;margin-bottom:28px;" class="animate-in">
    <div class="stat-card">
      <div class="stat-label">Total Reservas</div>
      <div class="stat-value"><?= $totalReservas ?></div>
    </div>
    <div class="stat-card">
      <div class="stat-label">Activas</div>
      <div class="stat-value" style="color:#2ECC71;">
    <?= $activas ?>
</div>
    </div>
    <div class="stat-card">
      <div class="stat-label">Completadas</div>
      <div class="stat-value" style="color:var(--grey-mid);">
    <?= $completadas ?>
</div>
    </div>
    <div class="stat-card">
      <div class="stat-label">Canceladas</div>
      <div class="stat-value" style="color:var(--danger);">
    <?= $canceladas ?>
</div>
    </div>
    <div class="stat-card">
      <div class="stat-label">Total Gastado</div>
      <div class="stat-value" style="font-size:1.4rem;">
    $<?= number_format($totalGastado) ?>
</div>
      <div class="stat-sub">MXN</div>
    </div>
  </div>

  <!-- ── FILTER TABS ── -->
  <div class="tabs animate-in" style="max-width:480px;">
    <a href="historial.php?filtro=todas"      class="tab-btn <?= $filtro==='todas'?'active':'' ?>">Todas</a>
    <a href="historial.php?filtro=activa"     class="tab-btn <?= $filtro==='activa'?'active':'' ?>">Activas</a>
    <a href="historial.php?filtro=completada" class="tab-btn <?= $filtro==='completada'?'active':'' ?>">Completadas</a>
    <a href="historial.php?filtro=cancelada"  class="tab-btn <?= $filtro==='cancelada'?'active':'' ?>">Canceladas</a>
  </div>

  <!-- ── RESERVATION LIST ── -->
  <?php if (empty($reservas)): ?>
    <div class="card" style="text-align:center;padding:48px;">
      <div style="font-size:3rem;margin-bottom:12px;">🗂️</div>
      <h3 style="color:var(--grey-mid);">Sin reservas en esta categoría</h3>
      <div style="margin-top:16px;">
        <a href="reserva-habitacion.php" class="btn btn-gold">Hacer una Reserva</a>
      </div>
    </div>
  <?php else: ?>

    <?php foreach ($reservas as $r):
          $badgeClass = match($r['estatus']) {
              'activa'     => 'badge-success',
              'completada' => 'badge-grey',
              'cancelada'  => 'badge-danger',
              default      => 'badge-grey',
          };
          $estatusLabel = match($r['estatus']) {
              'activa'     => '✓ Activa',
              'completada' => '✔ Completada',
              'cancelada'  => '✕ Cancelada',
              default      => $r['estatus'],
          };
    ?>
    <div class="res-item animate-in">
      <div class="res-icon <?= $r['tipo'] ?>">
        <?= $r['icon'] ?>
      </div>

      <div class="res-info">
        <h4><?= htmlspecialchars($r['titulo']) ?></h4>
        <p><?= htmlspecialchars($r['detalle']) ?></p>
        <div style="margin-top:6px;display:flex;align-items:center;gap:8px;">
          <span class="badge <?= $badgeClass ?>"><?= $estatusLabel ?></span>
          <span style="font-size:0.75rem;color:var(--grey-mid);">ID: <?= $r['id'] ?></span>
        </div>
      </div>

      <div class="res-meta">
        <div class="amount">$<?= number_format($r['total']) ?> MXN</div>
        <div class="date">Creada <?= date('d M Y', strtotime($r['fecha'])) ?></div>

        <?php if ($r['estatus'] === 'activa'): ?>
          <div style="margin-top:8px;display:flex;gap:6px;justify-content:flex-end;">
            <a href="#" class="btn btn-outline btn-sm">Ver</a>
           <a href="historial.php?cancelar=<?= $r['id'] ?>"
   class="btn btn-danger btn-sm"
   onclick="return confirm('¿Seguro que deseas cancelar esta reserva?');">
    Cancelar
</a>
          </div>
        <?php elseif ($r['estatus'] === 'completada'): ?>
          <div style="margin-top:8px;">
            <a href="#" class="btn btn-ghost btn-sm">Volver a reservar</a>
          </div>
        <?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>

  <?php endif; ?>

  <!-- ── NEW RESERVATION CTA ── -->
  <div class="card card-gold animate-in" style="margin-top:32px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:16px;">
    <div>
      <h3 style="font-size:1rem;margin-bottom:4px;">¿Listo para tu próxima escapada?</h3>
      <p style="font-size:0.85rem;color:var(--grey-mid);">Explora habitaciones disponibles o reserva tu traslado.</p>
    </div>
    <div style="display:flex;gap:10px;">
      <a href="reserva-habitacion.php" class="btn btn-gold">🛏 Nueva Reserva</a>
      <a href="reserva-traslado.php"   class="btn btn-outline">🚐 Traslado</a>
    </div>
  </div>

</div>
</main>

<?php include 'includes/footer.php'; ?>
