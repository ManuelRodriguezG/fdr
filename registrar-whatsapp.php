<?php
declare(strict_types=1);

const FDR_WHATSAPP = '523321922061';
const FDR_DATA_DIR = __DIR__ . '/data';

date_default_timezone_set('America/Mexico_City');

function field(string $key, string $default = ''): string
{
    $value = $_POST[$key] ?? $_GET[$key] ?? $default;
    if (is_array($value)) {
        return $default;
    }
    $value = trim((string) $value);
    return mb_substr($value, 0, 600);
}

function ensure_storage(): void
{
    $actionsDir = FDR_DATA_DIR . '/acciones';
    if (!is_dir($actionsDir)) {
        mkdir($actionsDir, 0775, true);
    }
}

function client_ip(): string
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '';
}

function build_message(array $entry): string
{
    $lines = [
        'Hola FDR, me gustaría recibir información.',
        '',
        'Interés: ' . ($entry['interes'] ?: 'Cotización general'),
        'Tipo: ' . ($entry['tipo'] ?: 'consulta'),
    ];

    if ($entry['condicion'] !== '') {
        $lines[] = 'Condición buscada: ' . $entry['condicion'];
    } else {
        $lines[] = 'Condición buscada: nuevo o reacondicionado';
    }

    if ($entry['nombre'] !== '') {
        $lines[] = 'Nombre: ' . $entry['nombre'];
    }

    if ($entry['negocio'] !== '') {
        $lines[] = 'Negocio: ' . $entry['negocio'];
    }

    if ($entry['detalles'] !== '') {
        $lines[] = 'Detalles: ' . $entry['detalles'];
    }

    $lines[] = '';
    $lines[] = 'Vi esta opción en la página de FDR.';

    return implode("\n", $lines);
}

ensure_storage();

$entry = [
    'id' => date('Ymd-His') . '-' . bin2hex(random_bytes(4)),
    'created_at' => date('c'),
    'lead_id' => field('lead_id'),
    'evento' => 'whatsapp_redirect',
    'source' => field('source', 'desconocido'),
    'tipo' => field('tipo', 'consulta'),
    'interes' => field('interes', field('need', 'Cotización general')),
    'condicion' => field('condicion'),
    'nombre' => field('nombre'),
    'telefono' => field('telefono'),
    'negocio' => field('negocio'),
    'detalles' => field('detalles'),
    'user_agent' => mb_substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 500),
    'ip' => client_ip(),
    'referer' => mb_substr((string) ($_SERVER['HTTP_REFERER'] ?? ''), 0, 500),
];

$entry['whatsapp_message'] = build_message($entry);

$json = json_encode($entry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
if ($json === false) {
    http_response_code(500);
    echo 'No se pudo preparar el registro.';
    exit;
}

file_put_contents(FDR_DATA_DIR . '/acciones-whatsapp.jsonl', $json . PHP_EOL, FILE_APPEND | LOCK_EX);
file_put_contents(FDR_DATA_DIR . '/acciones/' . $entry['id'] . '.json', json_encode($entry, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

$url = 'https://wa.me/' . FDR_WHATSAPP . '?text=' . rawurlencode($entry['whatsapp_message']);
header('Location: ' . $url, true, 302);
exit;
