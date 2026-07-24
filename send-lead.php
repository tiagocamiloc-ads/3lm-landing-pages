<?php
header('Content-Type: application/json; charset=utf-8');

function clean($v) {
    return htmlspecialchars(strip_tags(trim($v ?? '')), ENT_QUOTES, 'UTF-8');
}

$nome     = clean($_POST['nome']          ?? '');
$email    = clean($_POST['email']         ?? '');
$telefone = clean($_POST['telefone']      ?? '');
$empresa  = clean($_POST['empresa']       ?? '');
$tamanho  = clean($_POST['tamanho']       ?? '');
$tecido   = clean($_POST['tecido']        ?? '');
$qtd      = clean($_POST['qtd_tendas']    ?? '');
$faces    = clean($_POST['faces']         ?? '');
$paredes  = clean($_POST['paredes']       ?? '');
$meias    = clean($_POST['meias_paredes'] ?? '');
$janela   = clean($_POST['janela']        ?? '');
$porta    = clean($_POST['porta']         ?? '');
$bases    = clean($_POST['bases']         ?? '');
$notas    = clean($_POST['notas']         ?? '');

if (!$nome || !$telefone || !$empresa) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Campos obrigatórios em falta.']);
    exit;
}

$rawEmail = $_POST['email'] ?? '';
if (!filter_var($rawEmail, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Email inválido.']);
    exit;
}

// Subject — references the product clearly
$subject = 'Nova Lead | Tendas Para Eventos | ' . ($empresa ?: $nome);

// Plain-text body with all lead details
$sep  = str_repeat('─', 48);
$body = "NOVA LEAD — TENDAS PARA EVENTOS\n$sep\n\n";

$body .= "CONTACTO\n";
$body .= "Nome:     $nome\n";
$body .= "Email:    $email\n";
$body .= "Telefone: $telefone\n";
$body .= "Empresa:  $empresa\n\n";

$body .= "TENDA\n";
$body .= "Tamanho:    " . ($tamanho ?: '—') . "\n";
$body .= "Tecido:     " . ($tecido  ?: '—') . "\n";
$body .= "Quantidade: " . ($qtd     ?: '—') . "\n";
$body .= "Faces topo: " . ($faces   ?: '—') . "\n\n";

$body .= "ACESSÓRIOS\n";
$body .= "Paredes laterais: " . ($paredes ?: '—') . "\n";
$body .= "Meias-paredes:    " . ($meias   ?: '—') . "\n";
$body .= "Janela:           " . ($janela  ?: '—') . "\n";
$body .= "Porta:            " . ($porta   ?: '—') . "\n";
$body .= "Bases de carga:   " . ($bases   ?: '—') . "\n\n";

$body .= "NOTAS / ARTE\n";
$body .= ($notas ?: '—') . "\n\n";

$body .= "$sep\n";
$body .= "Recebido em " . date('d/m/Y H:i') . " UTC\n";
$body .= "Produto: Tendas Para Eventos — produtos.3lm.pt\n";

$to      = '3lm@3lm.pt';
$headers  = "From: leads@produtos.3lm.pt\r\n";
$headers .= "Reply-To: $email\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
$headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";

$sent = mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $body, $headers);

if ($sent) {
    echo json_encode(['ok' => true]);
} else {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Erro ao enviar email. Tente novamente.']);
}
