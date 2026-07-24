<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Authorization, Content-Type');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit(0); }

require_once __DIR__ . '/config.php';

// Auth
$auth = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
$token = str_replace('Bearer ', '', $auth);
if ($token !== CRM_PASSWORD) {
    http_response_code(401);
    echo json_encode(['error' => 'Não autorizado']);
    exit;
}

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

function sb_request(string $path, string $httpMethod = 'GET', ?string $body = null): array {
    $ch = curl_init(SUPABASE_URL . '/rest/v1/' . $path);
    $headers = [
        'apikey: '        . SUPABASE_SERVICE_KEY,
        'Authorization: Bearer ' . SUPABASE_SERVICE_KEY,
    ];
    if ($httpMethod !== 'GET') {
        $headers[] = 'Content-Type: application/json';
        $headers[] = 'Prefer: return=minimal';
    }
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST  => $httpMethod,
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_ENCODING       => '',
    ]);
    if ($body !== null) curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    $resp     = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $httpCode, 'body' => $resp];
}

// ── DEBUG ─────────────────────────────────────────────────────────────
if ($method === 'GET' && $action === 'debug') {
    $r1 = sb_request('leads_3lm?select=id,produto,status&order=created_at.desc&limit=1000');
    $r2 = sb_request('leads_3lm?select=id,produto,status&order=created_at.desc&limit=1000&produto=eq.tendas-para-eventos');
    echo json_encode([
        'sem_filtro'       => ['code' => $r1['code'], 'data' => json_decode($r1['body'])],
        'com_filtro_prod'  => ['code' => $r2['code'], 'data' => json_decode($r2['body'])],
    ]);
    exit;
}

// ── GET list ──────────────────────────────────────────────────────────
if ($method === 'GET' && $action === 'list') {
    $qs = 'leads_3lm?select=*&order=created_at.desc&limit=1000';
    if (!empty($_GET['status']))  $qs .= '&status=eq.' . urlencode($_GET['status']);
    if (!empty($_GET['produto'])) $qs .= '&produto=eq.' . urlencode($_GET['produto']);
    $r = sb_request($qs);
    echo $r['body'];
    exit;
}

// ── GET funnel ────────────────────────────────────────────────────────
if ($method === 'GET' && $action === 'funnel') {
    $qs = 'leads_3lm?select=status';
    if (!empty($_GET['produto'])) $qs .= '&produto=eq.' . urlencode($_GET['produto']);
    $r    = sb_request($qs);
    $rows = json_decode($r['body'], true) ?? [];
    $counts = [];
    foreach ($rows as $row) {
        $s = $row['status'];
        $counts[$s] = ($counts[$s] ?? 0) + 1;
    }
    $order = ['por_contatar','contatada','orcamento_enviado','confirmada','perdida','nao_disponivel'];
    $funnel = [];
    foreach ($order as $s) {
        $funnel[] = ['status' => $s, 'count' => $counts[$s] ?? 0];
    }
    echo json_encode($funnel);
    exit;
}

// ── GET single lead ───────────────────────────────────────────────────
if ($method === 'GET' && $action === 'get') {
    $id = $_GET['id'] ?? '';
    if (!$id) { http_response_code(400); echo json_encode(['error' => 'id obrigatório']); exit; }
    $r    = sb_request('leads_3lm?id=eq.' . urlencode($id));
    $rows = json_decode($r['body'], true) ?? [];
    echo json_encode($rows[0] ?? null);
    exit;
}

// ── POST update ───────────────────────────────────────────────────────
if ($method === 'POST' && $action === 'update') {
    $id   = $_GET['id'] ?? '';
    if (!$id) { http_response_code(400); echo json_encode(['error' => 'id obrigatório']); exit; }
    $body = file_get_contents('php://input');
    $data = json_decode($body, true) ?? [];
    $allowed = ['status', 'notas_internas'];
    $patch = [];
    foreach ($allowed as $k) { if (array_key_exists($k, $data)) $patch[$k] = $data[$k]; }
    if (empty($patch)) { echo json_encode(['ok' => true]); exit; }
    $r = sb_request('leads_3lm?id=eq.' . urlencode($id), 'PATCH', json_encode($patch));
    if ($r['code'] >= 400) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => 'Supabase '.$r['code'].': '.$r['body']]);
        exit;
    }
    echo json_encode(['ok' => true]);
    exit;
}

http_response_code(400);
echo json_encode(['error' => 'Acção desconhecida']);
