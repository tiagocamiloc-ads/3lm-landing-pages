<?php
header('Content-Type: application/json; charset=utf-8');

define('TO_EMAIL', 'tiagocamiloc@gmail.com');

require_once __DIR__ . '/config.php';

function clean($v) {
    return htmlspecialchars(strip_tags(trim($v ?? '')), ENT_QUOTES, 'UTF-8');
}

$nome         = clean($_POST['nome']          ?? '');
$email        = clean($_POST['email']         ?? '');
$telefone     = clean($_POST['telefone']      ?? '');
$empresa      = clean($_POST['empresa']       ?? '');
$tamanho      = clean($_POST['tamanho']       ?? '');
$tecido       = clean($_POST['tecido']        ?? '');
$qtd          = clean($_POST['qtd_tendas']    ?? '');
$faces        = clean($_POST['faces']         ?? '');
$paredes      = clean($_POST['paredes']       ?? '');
$meias        = clean($_POST['meias_paredes'] ?? '');
$janela       = clean($_POST['janela']        ?? '');
$porta        = clean($_POST['porta']         ?? '');
$bases        = clean($_POST['bases']         ?? '');
$notas        = clean($_POST['notas']         ?? '');
$utm_source   = clean($_POST['utm_source']    ?? '');
$utm_medium   = clean($_POST['utm_medium']    ?? '');
$utm_campaign = clean($_POST['utm_campaign']  ?? '');
$utm_content  = clean($_POST['utm_content']   ?? '');
$utm_term     = clean($_POST['utm_term']      ?? '');

if (!$nome || !$telefone || !$empresa) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Campos obrigatórios em falta.']);
    exit;
}

if (!filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Email inválido.']);
    exit;
}

$subject = 'Nova Lead | Tendas Para Eventos | ' . ($empresa ?: $nome);

$sep  = str_repeat('-', 48);
$body = "NOVA LEAD - TENDAS PARA EVENTOS\n$sep\n\n";

$body .= "CONTACTO\n";
$body .= "Nome:     $nome\n";
$body .= "Email:    $email\n";
$body .= "Telefone: $telefone\n";
$body .= "Empresa:  $empresa\n\n";

$body .= "TENDA\n";
$body .= "Tamanho:    " . ($tamanho ?: '-') . "\n";
$body .= "Tecido:     " . ($tecido  ?: '-') . "\n";
$body .= "Quantidade: " . ($qtd     ?: '-') . "\n";
$body .= "Faces topo: " . ($faces   ?: '-') . "\n\n";

$body .= "ACESSORIOS\n";
$body .= "Paredes laterais: " . ($paredes ?: '-') . "\n";
$body .= "Meias-paredes:    " . ($meias   ?: '-') . "\n";
$body .= "Janela:           " . ($janela  ?: '-') . "\n";
$body .= "Porta:            " . ($porta   ?: '-') . "\n";
$body .= "Bases de carga:   " . ($bases   ?: '-') . "\n\n";

$body .= "NOTAS / ARTE\n";
$body .= ($notas ?: '-') . "\n\n";

$body .= "ORIGEM DA LEAD\n";
$body .= "Fonte:     " . ($utm_source   ?: '-') . "\n";
$body .= "Canal:     " . ($utm_medium   ?: '-') . "\n";
$body .= "Campanha:  " . ($utm_campaign ?: '-') . "\n";
$body .= "Conteudo:  " . ($utm_content  ?: '-') . "\n";
$body .= "Termo:     " . ($utm_term     ?: '-') . "\n\n";

$body .= "$sep\n";
$body .= "Recebido em " . date('d/m/Y H:i') . " UTC\n";
$body .= "Produto: Tendas Para Eventos - produtos.3lm.pt\n";

$payload = json_encode([
    'from'       => 'Leads 3LM <onboarding@resend.dev>',
    'to'         => [TO_EMAIL],
    'reply_to'   => $email,
    'subject'    => $subject,
    'text'       => $body,
]);

$ch = curl_init('https://api.resend.com/emails');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_HTTPHEADER     => [
        'Authorization: Bearer ' . RESEND_API_KEY,
        'Content-Type: application/json',
    ],
    CURLOPT_POSTFIELDS     => $payload,
    CURLOPT_TIMEOUT        => 10,
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$result = json_decode($response, true);

if ($httpCode === 200 && isset($result['id'])) {
    // Save lead to Supabase (fire-and-forget — email already sent)
    $leadData = json_encode([
        'produto'       => 'tendas-para-eventos',
        'nome'          => $nome,
        'email'         => $email,
        'telefone'      => $telefone,
        'empresa'       => $empresa,
        'tamanho'       => $tamanho   ?: null,
        'tecido'        => $tecido    ?: null,
        'quantidade'    => $qtd       ?: null,
        'faces'         => $faces     ?: null,
        'paredes'       => $paredes   ?: null,
        'meias_paredes' => $meias     ?: null,
        'janela'        => $janela    ?: null,
        'porta'         => $porta     ?: null,
        'bases'         => $bases     ?: null,
        'notas'         => $notas     ?: null,
        'utm_source'    => $utm_source    ?: null,
        'utm_medium'    => $utm_medium    ?: null,
        'utm_campaign'  => $utm_campaign  ?: null,
        'utm_content'   => $utm_content   ?: null,
        'utm_term'      => $utm_term      ?: null,
        'status'        => 'por_contatar',
    ]);

    $sbCh = curl_init(SUPABASE_URL . '/rest/v1/leads_3lm');
    curl_setopt_array($sbCh, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => [
            'apikey: '        . SUPABASE_SERVICE_KEY,
            'Authorization: Bearer ' . SUPABASE_SERVICE_KEY,
            'Content-Type: application/json',
            'Prefer: return=minimal',
        ],
        CURLOPT_POSTFIELDS     => $leadData,
        CURLOPT_TIMEOUT        => 8,
    ]);
    curl_exec($sbCh);
    curl_close($sbCh);

    echo json_encode(['ok' => true]);
} else {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Erro ao enviar email. Tente novamente.']);
}
