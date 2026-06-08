<?php
// ============================================================
// PANTALLA 4 — Reserva de Traslado  |  reserva-traslado.php
// ============================================================
session_start();
$pageTitle  = 'Reservar Traslado';
$activePage = 'transfer';

$errors  = [];
$success = false;

// ── Configuración de flota ──
$MAX_PAX_POR_VEHICULO = 12;
$NUM_VEHICULOS        = 3;
$CAPACIDAD_TOTAL      = $MAX_PAX_POR_VEHICULO * $NUM_VEHICULOS; // 36

// Traslados existentes en la ventana de 20 min (simulado — en producción: Firestore query)
$ocupadosEnVentana = [
    'camioneta_01' => 8,
    'camioneta_02' => 5,
    'camioneta_03' => 0,
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fecha    = $_POST['fecha']    ?? '';
    $hora     = $_POST['hora']     ?? '';
    $vuelo    = htmlspecialchars(trim($_POST['vuelo']     ?? ''));
    $pax      = intval($_POST['pasajeros'] ?? 1);
    $maletas  = intval($_POST['maletas']   ?? 0);
    $tipo     = $_POST['tipo_traslado'] ?? 'llegada';

    // ── Validaciones ──
    if (empty($fecha))
        $errors[] = 'La fecha del traslado es obligatoria.';
    if (empty($hora))
        $errors[] = 'El horario del vuelo es obligatorio.';
    if (empty($vuelo))
        $errors[] = 'El número de vuelo es obligatorio.';
    if ($pax < 1 || $pax > $CAPACIDAD_TOTAL)
        $errors[] = "El número de pasajeros debe ser entre 1 y {$CAPACIDAD_TOTAL}.";
    if ($maletas < 0)
        $errors[] = 'El número de maletas no puede ser negativo.';

    // ── Regla de negocio: asignación de camioneta ──
    if (empty($errors)) {
        $vehiculoAsignado  = null;
        $libresPorVehiculo = [];

        foreach ($ocupadosEnVentana as $vid => $ocupados) {
            $libres = $MAX_PAX_POR_VEHICULO - $ocupados;
            $libresPorVehiculo[$vid] = $libres;
            if ($libres >= $pax && $vehiculoAsignado === null) {
                $vehiculoAsignado = $vid;
            }
        }

        if ($vehiculoAsignado === null) {
            $errDetalle = implode(', ', array_map(fn($k,$v) => "$k: $v libres", array_keys($libresPorVehiculo), $libresPorVehiculo));
            $errors[] = "🚫 No hay capacidad suficiente para {$pax} pasajero(s) en esta ventana horaria. ({$errDetalle}). Por favor elige otro horario.";
        }
    }

    if (empty($errors)) {
        $success = true;
        $horaFin = date('H:i', strtotime($hora) + 20*60);
        $resumen = [
            'id'       => 'TRF-' . strtoupper(substr(md5(uniqid()), 0, 8)),
            'vehiculo' => $vehiculoAsignado,
            'fecha'    => $fecha,
            'hora'     => $hora,
            'hora_fin' => $horaFin,
            'vuelo'    => $vuelo,
            'pax'      => $pax,
            'maletas'  => $maletas,
            'tipo'     => $tipo,
        ];
    }
}

include 'includes/header.php';
?>

<main class="page-content">
<div class="container">

  <div class="page-header animate-in">
    <p class="breadcrumb">🏠 <a href="dashboard.php">Inicio</a> / <span>Reservar Traslado</span></p>
    <h1>🚐 Reserva de Traslado</h1>
    <p>Transporte privado aeropuerto ↔ Hotel Maya Bay.</p>
  </div>

  <?php if ($success): ?>
  <!-- ── SUCCESS ── -->
  <div class="card" style="border-color:rgba(46,204,113,0.4);background:rgba(46,204,113,0.06);text-align:center;padding:44px;margin-bottom:32px;">
    <div style="font-size:3rem;margin-bottom:12px;">🚐✅</div>
    <h2 style="color:#2ECC71;margin-bottom:6px;">¡Traslado Confirmado!</h2>
    <p style="color:var(--grey-mid);margin-bottom:20px;">ID: <strong style="color:var(--gold)"><?= $resumen['id'] ?></strong></p>

    <div style="display:inline-grid;grid-template-columns:1fr 1fr;gap:12px 32px;text-align:left;margin-bottom:28px;">
      <div><span style="color:var(--grey-mid);font-size:0.8rem;">Vehículo</span><br><strong style="text-transform:capitalize"><?= str_replace('_',' ', $resumen['vehiculo']) ?></strong></div>
      <div><span style="color:var(--grey-mid);font-size:0.8rem;">Tipo</span><br><strong><?= ucfirst($resumen['tipo']) ?></strong></div>
      <div><span style="color:var(--grey-mid);font-size:0.8rem;">Fecha</span><br><strong><?= date('d M Y', strtotime($resumen['fecha'])) ?></strong></div>
      <div><span style="color:var(--grey-mid);font-size:0.8rem;">Ventana horaria</span><br><strong><?= $resumen['hora'] ?> – <?= $resumen['hora_fin'] ?></strong></div>
      <div><span style="color:var(--grey-mid);font-size:0.8rem;">Vuelo</span><br><strong><?= $resumen['vuelo'] ?></strong></div>
      <div><span style="color:var(--grey-mid);font-size:0.8rem;">Pasajeros / Maletas</span><br><strong><?= $resumen['pax'] ?> pax · <?= $resumen['maletas'] ?> maletas</strong></div>
    </div>

    <div style="display:flex;gap:12px;justify-content:center;">
      <a href="historial.php" class="btn btn-gold">Ver Mis Reservas</a>
      <a href="dashboard.php" class="btn btn-ghost">Volver al Inicio</a>
    </div>
  </div>

  <?php else: ?>
  <?php if (!empty($errors)): ?>
    <div class="alert alert-danger animate-in">
      <?php foreach ($errors as $e): ?><div>⚠️ <?= $e ?></div><?php endforeach; ?>
    </div>
  <?php endif; ?>

  <div style="display:grid;grid-template-columns:1fr 340px;gap:28px;align-items:start;">

    <!-- ── FORM ── -->
    <div class="card animate-in">
      <h3 style="font-family:var(--font-title);margin-bottom:6px;">Datos del Traslado</h3>
      <div class="gold-divider"></div>

      <form method="POST" action="reserva-traslado.php">

        <!-- Tipo -->
        <div class="form-group">
          <label class="form-label">Tipo de Traslado</label>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
            <label style="cursor:pointer;">
              <input type="radio" name="tipo_traslado" value="llegada"
                     <?= ($_POST['tipo_traslado']??'llegada')==='llegada'?'checked':'' ?>
                     style="display:none;" id="radio_llegada"
                     onchange="document.querySelectorAll('.tipo-card').forEach(c=>c.classList.remove('selected'));this.closest('.tipo-card').classList.add('selected')">
              <div class="vehicle-card tipo-card <?= ($_POST['tipo_traslado']??'llegada')==='llegada'?'selected':'' ?>"
                   onclick="document.getElementById('radio_llegada').click()">
                <div class="vehicle-icon">✈️→🏨</div>
                <div>
                  <div style="font-family:var(--font-title);font-size:0.9rem;color:var(--bone);">Llegada</div>
                  <div style="font-size:0.78rem;color:var(--grey-mid);">Aeropuerto → Hotel</div>
                </div>
              </div>
            </label>
            <label style="cursor:pointer;">
              <input type="radio" name="tipo_traslado" value="salida"
                     <?= ($_POST['tipo_traslado']??'')==='salida'?'checked':'' ?>
                     style="display:none;" id="radio_salida">
              <div class="vehicle-card tipo-card <?= ($_POST['tipo_traslado']??'')==='salida'?'selected':'' ?>"
                   onclick="document.getElementById('radio_salida').click();this.closest('.tipo-card').classList.add('selected')">
                <div class="vehicle-icon">🏨→✈️</div>
                <div>
                  <div style="font-family:var(--font-title);font-size:0.9rem;color:var(--bone);">Salida</div>
                  <div style="font-size:0.78rem;color:var(--grey-mid);">Hotel → Aeropuerto</div>
                </div>
              </div>
            </label>
          </div>
        </div>

        <!-- Fecha y hora -->
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">📅 Fecha</label>
            <input type="date" name="fecha" class="form-control"
                   min="<?= date('Y-m-d') ?>"
                   value="<?= htmlspecialchars($_POST['fecha'] ?? date('Y-m-d', strtotime('+1 day'))) ?>"
                   required>
          </div>
          <div class="form-group">
            <label class="form-label">🕐 Horario de Vuelo</label>
            <input type="time" name="hora" class="form-control"
                   value="<?= htmlspecialchars($_POST['hora'] ?? '14:00') ?>"
                   required>
            <div class="form-hint">Ventana de 20 min. a partir de este horario.</div>
          </div>
        </div>

        <!-- Número de vuelo -->
        <div class="form-group">
          <label class="form-label">✈️ Número de Vuelo</label>
          <input type="text" name="vuelo" class="form-control"
                 placeholder="Ej. TG-8812 / AA-1234"
                 value="<?= htmlspecialchars($_POST['vuelo'] ?? '') ?>"
                 required>
        </div>

        <!-- Pasajeros y maletas -->
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">👥 Pasajeros</label>
            <input type="number" name="pasajeros" class="form-control"
                   min="1" max="<?= $CAPACIDAD_TOTAL ?>"
                   value="<?= intval($_POST['pasajeros'] ?? 2) ?>"
                   required oninput="calcPax(this.value)">
            <div class="form-hint">Máx. <?= $MAX_PAX_POR_VEHICULO ?> por camioneta · <?= $NUM_VEHICULOS ?> vehículos disponibles.</div>
          </div>
          <div class="form-group">
            <label class="form-label">🧳 Maletas</label>
            <input type="number" name="maletas" class="form-control"
                   min="0" max="30"
                   value="<?= intval($_POST['maletas'] ?? 2) ?>">
          </div>
        </div>

        <button type="submit" class="btn btn-gold btn-lg btn-block">
          Confirmar Traslado →
        </button>

      </form>
    </div>

    <!-- ── SIDEBAR ── -->
    <div>
      <!-- Fleet status -->
      <div class="card card-gold animate-in" style="margin-bottom:16px;">
        <div style="font-size:0.78rem;color:var(--grey-mid);font-family:var(--font-title);letter-spacing:1px;text-transform:uppercase;margin-bottom:14px;">Estado de la Flota</div>

        <?php foreach ($ocupadosEnVentana as $vid => $ocp):
              $libres = $MAX_PAX_POR_VEHICULO - $ocp;
              $pct    = round($ocp / $MAX_PAX_POR_VEHICULO * 100);
              $clase  = $pct >= 100 ? 'danger' : ($pct >= 70 ? 'warning' : '');
        ?>
        <div style="margin-bottom:14px;">
          <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px;">
            <span style="font-family:var(--font-title);font-size:0.85rem;color:var(--bone);text-transform:capitalize;">
              🚐 <?= str_replace('_',' ', $vid) ?>
            </span>
            <span class="badge <?= $pct>=100?'badge-danger':($pct>=70?'badge-gold':'badge-success') ?>">
              <?= $libres ?> libres
            </span>
          </div>
          <div class="capacity-bar">
            <div class="capacity-bar-fill <?= $clase ?>" style="width:<?= $pct ?>%"></div>
          </div>
          <div style="font-size:0.72rem;color:var(--grey-mid);margin-top:3px;"><?= $ocp ?> / <?= $MAX_PAX_POR_VEHICULO ?> pasajeros</div>
        </div>
        <?php endforeach; ?>

        <div class="alert alert-warning" style="margin-top:12px;padding:10px 14px;font-size:0.8rem;">
          ⏱ La ventana operativa es de <strong>20 minutos fijos</strong> por trayecto.
        </div>
      </div>

      <!-- Pax indicator -->
      <div class="card animate-in" id="paxCard">
        <div style="font-size:0.78rem;color:var(--grey-mid);font-family:var(--font-title);letter-spacing:1px;text-transform:uppercase;margin-bottom:8px;">Tu Solicitud</div>
        <div id="paxMsg" style="font-size:0.875rem;color:var(--grey-mid);">Introduce el número de pasajeros.</div>
      </div>
    </div>

  </div>
  <?php endif; ?>

</div>
</main>

<script>
function calcPax(val) {
  const n = parseInt(val) || 0;
  const max = <?= $MAX_PAX_POR_VEHICULO ?>;
  const msg = document.getElementById('paxMsg');

  if (n <= 0) { msg.textContent = 'Introduce el número de pasajeros.'; return; }

  const camionetas = Math.ceil(n / max);
  const color = camionetas > <?= $NUM_VEHICULOS ?> ? 'var(--danger)' : 'var(--gold)';
  msg.innerHTML = `<span style="color:${color};font-family:var(--font-title);font-size:1.1rem;">${n} pasajero${n>1?'s':''}</span><br>
    <span style="color:var(--grey-mid);">Requiere ${camionetas} camioneta${camionetas>1?'s':''}</span>`;
}
calcPax(<?= intval($_POST['pasajeros'] ?? 2) ?>);
</script>

<?php include 'includes/footer.php'; ?>
