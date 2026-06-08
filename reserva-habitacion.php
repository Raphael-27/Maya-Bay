<?php
// ============================================================
// PANTALLA 3 — Reserva de Habitación  |  reserva-habitacion.php
// ============================================================
session_start();

require_once 'config/firestore.php';
require_once 'config/auth.php';

$usuario = FirebaseAuth::getSession();
$pageTitle  = 'Reservar Habitación';
$activePage = 'rooms';

$errors  = [];
$success = false;
$resumenReserva = [];
$respuesta = Firestore::getAll('habitaciones');

$habitaciones = [];

if ($respuesta['success']) {

    foreach ($respuesta['data'] as $hab) {

        if (($hab['estatus'] ?? '') === 'disponible') {

            $habitaciones[$hab['id']] = [
                'numero' => $hab['numero'],
                'tipo' => $hab['tipo'],
                'precio' => $hab['precio'],
                'imagen' => $hab['imagen'] ?? '',
                'estatus' => $hab['estatus']
            ];

        }

    }

}
$CUPO_MAX = 40;
$CUPO_MAX = 40;

// Obtener todas las habitaciones
$habitacionesOcupadas = Firestore::getAll('habitaciones');

// Inicializar contador
$reservasActuales = 0;

// Contar habitaciones ocupadas
if ($habitacionesOcupadas['success']) {

    foreach ($habitacionesOcupadas['data'] as $hab) {

        if (($hab['estatus'] ?? '') === 'ocupada') {
            $reservasActuales++;
        }

    }

}

// Calcular porcentaje
$porcentajeOcupacion = ($reservasActuales / $CUPO_MAX) * 100;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $hab_id   = $_POST['habitacion_id'] ?? '';
    $entrada  = $_POST['fecha_entrada'] ?? '';
    $salida   = $_POST['fecha_salida']  ?? '';
    $huespedes= intval($_POST['huespedes'] ?? 1);
    $notas    = htmlspecialchars(trim($_POST['notas'] ?? ''));

    // Validaciones
    if (empty($hab_id) || !isset($habitaciones[$hab_id]))
        $errors[] = 'Selecciona una habitación válida.';
    if (empty($entrada))
        $errors[] = 'La fecha de entrada es obligatoria.';
    if (empty($salida))
        $errors[] = 'La fecha de salida es obligatoria.';
    if (!empty($entrada) && !empty($salida) && $salida <= $entrada)
        $errors[] = 'La fecha de salida debe ser posterior a la entrada.';
    if ($huespedes < 1 || $huespedes > 4)
        $errors[] = 'El número de huéspedes debe ser entre 1 y 4.';

    if ($reservasActuales >= $CUPO_MAX) {
        $errors[] = '🔒 El hotel ha alcanzado su capacidad máxima de ' . $CUPO_MAX . ' habitaciones. No hay disponibilidad para las fechas seleccionadas.';
    }

  if (empty($errors)) {

    $noches = (new DateTime($entrada))
        ->diff(new DateTime($salida))
        ->days;

    $total = $habitaciones[$hab_id]['precio'] * $noches;

    $guardarReserva = Firestore::create(
        'reservas',
        [
            'uid' => $usuario['uid'] ?? '',
            'habitacion_id' => $hab_id,
            'habitacion_numero' => $habitaciones[$hab_id]['numero'],
            'tipo_habitacion' => $habitaciones[$hab_id]['tipo'],
            'fecha_entrada' => $entrada,
            'fecha_salida' => $salida,
            'huespedes' => $huespedes,
            'notas' => $notas,
            'total' => $total,
            'estado' => 'confirmada',
            'fecha_creacion' => date('Y-m-d H:i:s')
        ]
    );
    if ($guardarReserva['success']) {

    // Cambiar la habitación a ocupada
    Firestore::update(
        'habitaciones',
        $hab_id,
        [
            'estatus' => 'ocupada'
        ]
    );

    // Indicar que la reserva fue exitosa
    $success = true;

    // Crear el resumen que se mostrará en la pantalla
    $resumenReserva = [
        'id' => $guardarReserva['id'],
        'hab' => $habitaciones[$hab_id],
        'entrada' => $entrada,
        'salida' => $salida,
        'huespedes' => $huespedes,
        'noches' => $noches,
        'total' => $total
    ];

} else {

    $errors[] = 'No fue posible guardar la reserva.';

}
}
}
include 'includes/header.php';
?>

<main class="page-content">
<div class="container">

  <div class="page-header animate-in">
    <p class="breadcrumb">🏠 <a href="dashboard.php">Inicio</a> / <span>Reservar Habitación</span></p>
    <h1>🛏 Reserva tu Habitación</h1>
    <p>Elige fechas, tipo de habitación y confirma tu estadía.</p>
  </div>

  <?php if ($success): ?>
  <!-- ── SUCCESS CARD ── -->
  <div class="card" style="border-color:rgba(46,204,113,0.4);background:rgba(46,204,113,0.06);text-align:center;padding:44px;margin-bottom:32px;">
    <div style="font-size:3rem;margin-bottom:12px;">✅</div>
    <h2 style="color:#2ECC71;margin-bottom:6px;">¡Reserva Confirmada!</h2>
    <p style="color:var(--grey-mid);margin-bottom:20px;">ID de reserva: <strong style="color:var(--gold)"><?= $resumenReserva['id'] ?></strong></p>

    <div style="display:inline-grid;grid-template-columns:1fr 1fr;gap:12px 32px;text-align:left;margin-bottom:28px;">
      <div><span style="color:var(--grey-mid);font-size:0.8rem;">Habitación</span><br><strong>Hab. <?= $resumenReserva['hab']['numero'] ?> — <?= $resumenReserva['hab']['tipo'] ?></strong></div>
      <div><span style="color:var(--grey-mid);font-size:0.8rem;">Noches</span><br><strong><?= $resumenReserva['noches'] ?> noches</strong></div>
      <div><span style="color:var(--grey-mid);font-size:0.8rem;">Check-in</span><br><strong><?= date('d M Y', strtotime($resumenReserva['entrada'])) ?></strong></div>
      <div><span style="color:var(--grey-mid);font-size:0.8rem;">Check-out</span><br><strong><?= date('d M Y', strtotime($resumenReserva['salida'])) ?></strong></div>
      <div><span style="color:var(--grey-mid);font-size:0.8rem;">Huéspedes</span><br><strong><?= $resumenReserva['huespedes'] ?></strong></div>
      <div><span style="color:var(--grey-mid);font-size:0.8rem;">Total</span><br><strong style="color:var(--gold);font-size:1.2rem;">$<?= number_format($resumenReserva['total']) ?> MXN</strong></div>
    </div>

    <div style="display:flex;gap:12px;justify-content:center;">
      <a href="historial.php" class="btn btn-gold">Ver Mis Reservas</a>
      <a href="dashboard.php" class="btn btn-ghost">Volver al Inicio</a>
    </div>
  </div>

  <?php else: ?>
  <!-- ── ERRORS ── -->
  <?php if (!empty($errors)): ?>
    <div class="alert alert-danger animate-in">
      <div>
        <?php foreach ($errors as $e): ?>
          <div>⚠️ <?= $e ?></div>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>

  <div style="display:grid;grid-template-columns:1fr 360px;gap:28px;align-items:start;">

    <!-- ── FORM ── -->
    <div>
      <div class="card animate-in">
        <h3 style="font-family:var(--font-title);margin-bottom:6px;">Detalles de la Reserva</h3>
        <div class="gold-divider"></div>

        <form method="POST" action="reserva-habitacion.php" id="formReserva">

          <!-- Tipo de habitación -->
          <div class="form-group">
            <label class="form-label">Tipo de Habitación</label>
            <select name="habitacion_id" id="habitacion_id" class="form-control" required
                    onchange="actualizarResumen()">
              <option value="">-- Selecciona una habitación --</option>
              <?php foreach ($habitaciones as $id => $h): ?>
              <option value="<?= $id ?>"
                      data-precio="<?= $h['precio'] ?>"
                      data-tipo="<?= $h['tipo'] ?>"
                      data-num="<?= $h['numero'] ?>"
                      <?= ($_POST['habitacion_id'] ?? '')===$id ? 'selected' : '' ?>>
                Hab. <?= $h['numero'] ?> — <?= $h['tipo'] ?> · $<?= number_format($h['precio']) ?>/noche Hab.
              </option>
              <?php endforeach; ?>
            </select>
            <div class="form-hint">Capacidad máxima del hotel: <?= $CUPO_MAX ?> habitaciones.</div>
          </div>

          <!-- Fechas -->
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">📅 Fecha de Entrada</label>
              <input type="date" name="fecha_entrada" id="fecha_entrada" class="form-control"
                     min="<?= date('Y-m-d') ?>"
                     value="<?= htmlspecialchars($_POST['fecha_entrada'] ?? date('Y-m-d', strtotime('+1 day'))) ?>"
                     required onchange="actualizarResumen()">
            </div>
            <div class="form-group">
              <label class="form-label">📅 Fecha de Salida</label>
              <input type="date" name="fecha_salida" id="fecha_salida" class="form-control"
                     min="<?= date('Y-m-d', strtotime('+2 days')) ?>"
                     value="<?= htmlspecialchars($_POST['fecha_salida'] ?? date('Y-m-d', strtotime('+6 days'))) ?>"
                     required onchange="actualizarResumen()">
            </div>
          </div>

          <!-- Huéspedes -->
          <div class="form-group">
            <label class="form-label">👥 Número de Huéspedes</label>
            <select name="huespedes" class="form-control">
              <?php for ($i=1; $i<=4; $i++): ?>
                <option value="<?= $i ?>" <?= ($_POST['huespedes'] ?? 1)==$i ? 'selected' : '' ?>><?= $i ?> huésped<?= $i>1?'es':'' ?></option>
              <?php endfor; ?>
            </select>
          </div>

          <!-- Notas -->
          <div class="form-group">
            <label class="form-label">📝 Solicitudes Especiales (Opcional)</label>
            <textarea name="notas" class="form-control" rows="3"
                      placeholder="Cama extra, alergias alimentarias, llegada tardía..."
                      style="resize:vertical;"><?= htmlspecialchars($_POST['notas'] ?? '') ?></textarea>
          </div>

          <button type="submit" class="btn btn-gold btn-lg btn-block">
            Confirmar Reserva →
          </button>

        </form>
      </div>
    </div>

    <!-- ── SUMMARY SIDEBAR ── -->
    <div>
      <div class="card card-gold animate-in" id="resumenCard">
        <h3 style="font-size:0.95rem;color:var(--grey-mid);letter-spacing:1px;text-transform:uppercase;">Resumen</h3>
        <div class="gold-divider"></div>

        <div id="resumenHab" style="font-family:var(--font-title);font-size:1rem;color:var(--bone);margin-bottom:16px;">
          Selecciona una habitación
        </div>

        <div style="display:flex;flex-direction:column;gap:10px;font-size:0.875rem;">
          <div style="display:flex;justify-content:space-between;color:var(--grey-mid);">
            <span>Tarifa por noche</span>
            <span id="resumenPrecio" style="color:var(--bone);">—</span>
          </div>
          <div style="display:flex;justify-content:space-between;color:var(--grey-mid);">
            <span>Noches</span>
            <span id="resumenNoches" style="color:var(--bone);">—</span>
          </div>
          <div style="height:1px;background:rgba(255,255,255,0.08);"></div>
          <div style="display:flex;justify-content:space-between;">
            <span style="font-family:var(--font-title);font-weight:700;">Total</span>
            <span id="resumenTotal" style="font-family:var(--font-title);font-weight:700;font-size:1.2rem;color:var(--gold);">—</span>
          </div>
        </div>

        <div style="margin-top:20px;" class="alert alert-warning" style="font-size:0.8rem;">
          ℹ️ El hotel cuenta con <strong><?= $CUPO_MAX - $reservasActuales ?> habitaciones disponibles</strong> actualmente. La reserva se valida contra disponibilidad en tiempo real.
        </div>
      </div>

      <!-- Availability indicator -->
      <div class="card animate-in" style="margin-top:16px;padding:18px 20px;">
        <div style="font-size:0.78rem;color:var(--grey-mid);font-family:var(--font-title);letter-spacing:1px;text-transform:uppercase;margin-bottom:12px;">
          Ocupación actual
        </div>
        <div class="capacity-bar-wrap">
          <div style="display:flex;justify-content:space-between;font-size:0.8rem;margin-bottom:4px;">

    <span style="color:var(--grey-mid);">
        <?= $reservasActuales ?> / <?= $CUPO_MAX ?> ocupadas
    </span>

    <span style="color:var(--gold);font-weight:600;">
        <?= round($porcentajeOcupacion) ?>%
    </span>

</div>

<div class="capacity-bar">

    <div class="capacity-bar-fill warning"
         style="width:<?= round($porcentajeOcupacion) ?>%">
           </div>

         </div>
        </div>
      </div>
    </div>

  </div><!-- /.grid -->
  <?php endif; ?>

</div>
</main>

<script>
function actualizarResumen() {
  const sel    = document.getElementById('habitacion_id');
  const opt    = sel.options[sel.selectedIndex];
  const entrada = document.getElementById('fecha_entrada').value;
  const salida  = document.getElementById('fecha_salida').value;

  if (!sel.value) {
    document.getElementById('resumenHab').textContent = 'Selecciona una habitación';
    ['resumenPrecio','resumenNoches','resumenTotal'].forEach(id => document.getElementById(id).textContent = '—');
    return;
  }

  const precio = parseInt(opt.dataset.precio);
  document.getElementById('resumenHab').textContent = 'Hab. ' + opt.dataset.num + ' — ' + opt.dataset.tipo;
  document.getElementById('resumenPrecio').textContent = '$' + precio.toLocaleString() + ' MXN';

  if (entrada && salida && salida > entrada) {
    const noches = Math.round((new Date(salida) - new Date(entrada)) / 86400000);
    const total  = precio * noches;
    document.getElementById('resumenNoches').textContent = noches + (noches===1?' noche':' noches');
    document.getElementById('resumenTotal').textContent = '$' + total.toLocaleString() + ' MXN';
  }
}

// Run on load if values are prefilled
actualizarResumen();
</script>

<?php include 'includes/footer.php'; ?>
