<?php
declare(strict_types=1);

const FDR_DATA_DIR = __DIR__ . '/data';

date_default_timezone_set('America/Mexico_City');

function lead_field(string $key, string $default = ''): string
{
    $value = $_POST[$key] ?? $default;
    if (is_array($value)) {
        return $default;
    }
    return mb_substr(trim((string) $value), 0, 600);
}

function ensure_lead_storage(): void
{
    $leadDir = FDR_DATA_DIR . '/leads';
    if (!is_dir($leadDir)) {
        mkdir($leadDir, 0775, true);
    }
}

function lead_client_ip(): string
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '';
}

function normalize_lead_id(string $leadId): string
{
    $leadId = preg_replace('/[^a-zA-Z0-9._-]/', '', $leadId) ?? '';
    return mb_substr($leadId, 0, 120);
}

ensure_lead_storage();

$leadId = normalize_lead_id(lead_field('lead_id'));
if ($leadId === '') {
    $leadId = date('Ymd-His') . '-' . bin2hex(random_bytes(4));
}

$entry = [
    'id' => $leadId,
    'created_at' => date('c'),
    'evento' => lead_field('evento', 'formulario_parcial'),
    'source' => lead_field('source', 'formulario'),
    'tipo' => lead_field('tipo', 'formulario'),
    'interes' => lead_field('interes'),
    'condicion' => lead_field('condicion'),
    'nombre' => lead_field('nombre'),
    'telefono' => lead_field('telefono'),
    'negocio' => lead_field('negocio'),
    'detalles' => lead_field('detalles'),
    'user_agent' => mb_substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 500),
    'ip' => lead_client_ip(),
    'referer' => mb_substr((string) ($_SERVER['HTTP_REFERER'] ?? ''), 0, 500),
];

if (preg_replace('/\D/', '', $entry['telefono']) === '') {
    http_response_code(204);
    exit;
}

$json = json_encode($entry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
if ($json === false) {
    http_response_code(500);
    echo 'No se pudo preparar el registro.';
    exit;
}

file_put_contents(FDR_DATA_DIR . '/leads-formulario.jsonl', $json . PHP_EOL, FILE_APPEND | LOCK_EX);
file_put_contents(FDR_DATA_DIR . '/leads/' . $entry['id'] . '.json', json_encode($entry, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

header('Content-Type: application/json; charset=utf-8');
echo json_encode(['ok' => true, 'id' => $entry['id']], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
