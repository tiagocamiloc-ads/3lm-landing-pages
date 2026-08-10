<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Methods: POST, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

require_once __DIR__ . '/config.php';

function clean_click($v) {
    return htmlspecialchars(strip_tags(trim($v ?? '')), ENT_QUOTES, 'UTF-8');
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];

$tipo = clean_click($input['tipo'] ?? '');
if (!in_array($tipo, ['whatsapp', 'orcamento'], true)) {
    http_response_code(204);
    exit;
}

$clickData = json_encode([
    'produto'      => clean_click($input['produto'] ?? '') ?: 'tendas-para-eventos',
    'tipo'         => $tipo,
    'utm_source'   => clean_click($input['utm_source']   ?? '') ?: null,
    'utm_medium'   => clean_click($input['utm_medium']   ?? '') ?: null,
    'utm_campaign' => clean_click($input['utm_campaign'] ?? '') ?: null,
    'utm_content'  => clean_click($input['utm_content']  ?? '') ?: null,
    'utm_term'     => clean_click($input['utm_term']     ?? '') ?: null,
]);

$ch = curl_init(SUPABASE_URL . '/rest/v1/clicks_3lm');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_ENCODING       => '',
    CURLOPT_HTTPHEADER     => [
        'apikey: '        . SUPABASE_SERVICE_KEY,
        'Authorization: Bearer ' . SUPABASE_SERVICE_KEY,
        'Content-Type: application/json',
        'Prefer: return=minimal',
    ],
    CURLOPT_POSTFIELDS     => $clickData,
    CURLOPT_TIMEOUT        => 5,
]);
curl_exec($ch);
curl_close($ch);

http_response_code(204);
