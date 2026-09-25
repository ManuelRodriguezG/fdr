<?php
declare(strict_types=1);

const FDR_PANEL_KEY = 'fdr-2026';
const FDR_LOG_FILE = __DIR__ . '/data/acciones-whatsapp.jsonl';
const FDR_LEADS_FILE = __DIR__ . '/data/leads-formulario.jsonl';

date_default_timezone_set('America/Mexico_City');

if (($_GET['key'] ?? '') !== FDR_PANEL_KEY) {
    http_response_code(404);
    echo 'Página no encontrada.';
    exit;
}

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function read_entries(): array
{
    if (!is_file(FDR_LOG_FILE)) {
        return [];
    }

    $entries = [];
    $lines = file(FDR_LOG_FILE, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
    foreach ($lines as $line) {
        $entry = json_decode($line, true);
        if (is_array($entry)) {
            $entries[] = $entry;
        }
    }

    return array_reverse($entries);
}

function read_jsonl(string $file): array
{
    if (!is_file($file)) {
        return [];
    }

    $entries = [];
    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
    foreach ($lines as $line) {
        $entry = json_decode($line, true);
        if (is_array($entry)) {
            $entries[] = $entry;
        }
    }

    return $entries;
}

function latest_by_id(array $entries): array
{
    $latest = [];
    foreach ($entries as $entry) {
        $id = trim((string) ($entry['id'] ?? ''));
        if ($id === '') {
            continue;
        }
        $latest[$id] = $entry;
    }

    uasort($latest, fn ($a, $b) => strcmp((string) ($b['created_at'] ?? ''), (string) ($a['created_at'] ?? '')));
    return $latest;
}

function count_by(array $entries, string $key): array
{
    $counts = [];
    foreach ($entries as $entry) {
        $value = trim((string) ($entry[$key] ?? 'Sin dato'));
        if ($value === '') {
            $value = 'Sin dato';
        }
        $counts[$value] = ($counts[$value] ?? 0) + 1;
    }
    arsort($counts);
    return $counts;
}

function digits_only(string $value): string
{
    return preg_replace('/\D/', '', $value) ?? '';
}

function customer_whatsapp_url(string $phone): string
{
    $digits = digits_only($phone);
    if (strlen($digits) === 10) {
        $digits = '52' . $digits;
    }

    if ($digits === '') {
        return '';
    }

    return 'https://wa.me/' . $digits;
}

$entries = read_entries();
$leadEvents = read_jsonl(FDR_LEADS_FILE);
$latestLeads = latest_by_id($leadEvents);
$convertedLeadIds = [];
foreach ($entries as $entry) {
    $leadId = trim((string) ($entry['lead_id'] ?? ''));
    if ($leadId !== '') {
        $convertedLeadIds[$leadId] = true;
    }
}
$pendingLeads = array_filter($latestLeads, fn ($entry, $id) => !isset($convertedLeadIds[$id]), ARRAY_FILTER_USE_BOTH);
$total = count($entries);
$totalLeads = count($latestLeads);
$pendingCount = count($pendingLeads);
$byInterest = count_by($entries, 'interes');
$bySource = count_by($entries, 'source');
$today = date('Y-m-d');
$todayCount = count(array_filter($entries, fn ($entry) => str_starts_with((string) ($entry['created_at'] ?? ''), $today)));
$todayLeads = count(array_filter($latestLeads, fn ($entry) => str_starts_with((string) ($entry['created_at'] ?? ''), $today)));
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Panel FDR</title>
  <style>
    :root { --blue:#2458d3; --dark:#0f2d5b; --ice:#ddeffd; --line:#d8e2ee; --text:#12213a; }
    * { box-sizing: border-box; }
    body { margin: 0; font-family: Montserrat, Arial, sans-serif; color: var(--text); background: #f7fbff; }
    main { width: min(1120px, calc(100% - 32px)); margin: 0 auto; padding: 34px 0; }
    h1, h2, p { margin-top: 0; }
    h1 { color: var(--dark); font-size: clamp(2rem, 5vw, 3.4rem); line-height: 1.05; }
    .grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 14px; margin: 24px 0; }
    .card, table { border: 1px solid var(--line); border-radius: 8px; background: #fff; box-shadow: 0 12px 28px rgba(15,45,91,.08); }
    .card { padding: 22px; }
    .metric { color: var(--blue); font-size: 2.4rem; font-weight: 800; line-height: 1; }
    .label { color: #546274; font-weight: 700; }
    .split { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; margin-bottom: 24px; }
    ul { margin: 0; padding: 0; list-style: none; display: grid; gap: 10px; }
    li { display: flex; justify-content: space-between; gap: 12px; border-bottom: 1px solid var(--line); padding-bottom: 8px; }
    table { width: 100%; border-collapse: collapse; overflow: hidden; }
    th, td { padding: 12px; border-bottom: 1px solid var(--line); text-align: left; vertical-align: top; font-size: .92rem; }
    th { color: var(--dark); background: var(--ice); }
    .muted { color: #546274; }
    .pill { display: inline-flex; min-height: 30px; align-items: center; border-radius: 999px; padding: 0 10px; background: #eaf4ff; color: var(--dark); font-weight: 800; font-size: .78rem; text-decoration: none; }
    .pill.good { background: #dcfce7; color: #166534; }
    .pill.warn { background: #fff7ed; color: #9a3412; }
    .lead-table { margin-bottom: 24px; }
    @media (max-width: 900px) { .grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    @media (max-width: 760px) { .grid, .split { grid-template-columns: 1fr; } table { display: block; overflow-x: auto; } }
  </style>
</head>
<body>
<main>
  <p class="muted">Panel privado por enlace</p>
  <h1>Actividad de consultas FDR</h1>
  <p class="muted">Este panel lee los registros locales guardados en el formulario y antes de enviar a WhatsApp. No es autenticación formal; es una URL privada para demostración local.</p>

  <section class="grid" aria-label="Resumen">
    <div class="card"><div class="metric"><?= $totalLeads ?></div><div class="label">leads captados</div></div>
    <div class="card"><div class="metric"><?= $pendingCount ?></div><div class="label">sin WhatsApp confirmado</div></div>
    <div class="card"><div class="metric"><?= $total ?></div><div class="label">acciones a WhatsApp</div></div>
    <div class="card"><div class="metric"><?= $todayLeads ?></div><div class="label">leads de hoy</div></div>
  </section>

  <section class="card lead-table">
    <h2>Leads para seguimiento</h2>
    <p class="muted">Personas que dejaron teléfono en el formulario. Si no aparece como enviado a WhatsApp, conviene dar seguimiento manual.</p>
    <table>
      <thead>
        <tr>
          <th>Fecha</th>
          <th>Estado</th>
          <th>Contacto</th>
          <th>Interés</th>
          <th>Detalles</th>
          <th>Acción</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach (array_slice($latestLeads, 0, 40, true) as $id => $lead): ?>
          <?php $converted = isset($convertedLeadIds[$id]); ?>
          <?php $customerUrl = customer_whatsapp_url((string) ($lead['telefono'] ?? '')); ?>
          <tr>
            <td><?= h((string) ($lead['created_at'] ?? '')) ?></td>
            <td><span class="pill <?= $converted ? 'good' : 'warn' ?>"><?= $converted ? 'WhatsApp abierto' : 'Pendiente' ?></span></td>
            <td>
              <strong><?= h((string) ($lead['nombre'] ?? 'Sin nombre')) ?></strong><br>
              <?= h((string) ($lead['telefono'] ?? '')) ?><br>
              <span class="muted"><?= h((string) ($lead['negocio'] ?? '')) ?></span>
            </td>
            <td><?= h((string) ($lead['interes'] ?? '')) ?><br><span class="muted"><?= h((string) ($lead['condicion'] ?? '')) ?></span></td>
            <td><?= h((string) ($lead['detalles'] ?? '')) ?></td>
            <td>
              <?php if ($customerUrl !== ''): ?>
                <a class="pill" href="<?= h($customerUrl) ?>" target="_blank" rel="noopener">Contactar</a>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </section>

  <section class="split">
    <div class="card">
      <h2>Intereses</h2>
      <ul>
        <?php foreach ($byInterest as $label => $count): ?>
          <li><span><?= h($label) ?></span><strong><?= $count ?></strong></li>
        <?php endforeach; ?>
      </ul>
    </div>
    <div class="card">
      <h2>Origen</h2>
      <ul>
        <?php foreach ($bySource as $label => $count): ?>
          <li><span><?= h($label) ?></span><strong><?= $count ?></strong></li>
        <?php endforeach; ?>
      </ul>
    </div>
  </section>

  <section class="card">
    <h2>Últimas acciones</h2>
    <table>
      <thead>
        <tr>
          <th>Fecha</th>
          <th>Interés</th>
          <th>Origen</th>
          <th>Nombre / negocio</th>
          <th>Teléfono</th>
          <th>Detalles</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach (array_slice($entries, 0, 30) as $entry): ?>
          <tr>
            <td><?= h((string) ($entry['created_at'] ?? '')) ?></td>
            <td><?= h((string) ($entry['interes'] ?? '')) ?></td>
            <td><?= h((string) ($entry['source'] ?? '')) ?></td>
            <td><?= h(trim((string) ($entry['nombre'] ?? '') . ' / ' . (string) ($entry['negocio'] ?? ''), ' /')) ?></td>
            <td><?= h((string) ($entry['telefono'] ?? '')) ?></td>
            <td><?= h((string) ($entry['detalles'] ?? '')) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </section>
</main>
</body>
</html>
