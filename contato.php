<?php
/**
 * ACLIVE — backend do formulário de contato
 *
 * Recebe os dados do formulário da landing page, envia por e-mail e
 * (opcional) manda o lead para o seu CRM via webhook (Zapier, Make, n8n,
 * RD Station, etc.).
 * Requer hospedagem com PHP (Hostinger, HostGator, Locaweb etc.).
 */

/* ============================================================
   CONFIGURAÇÃO — edite as duas linhas abaixo
   ============================================================ */

// E-mail que recebe os leads
$destinatario = 'digitalaclive@gmail.com';

// URL do webhook do seu CRM (Zapier / Make / n8n / etc.)
// Cole aqui o link "Catch Hook" que a ferramenta te dá.
// Deixe '' (vazio) para desligar o envio ao CRM.
$webhook_url = '';

/* ============================================================ */

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'erro' => 'Método não permitido']);
    exit;
}

// Honeypot anti-spam: o campo "site" é invisível — se veio preenchido, é robô.
if (!empty($_POST['site'])) {
    echo json_encode(['ok' => true]); // finge sucesso para não avisar o robô
    exit;
}

$nome     = trim(strip_tags($_POST['nome'] ?? ''));
$telefone = trim(strip_tags($_POST['telefone'] ?? ''));
$email    = trim($_POST['email'] ?? '');
$mensagem = trim(strip_tags($_POST['mensagem'] ?? ''));

if ($nome === '' || $telefone === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'erro' => 'Preencha nome, WhatsApp e um e-mail válido.']);
    exit;
}

// Limita tamanhos para evitar abuso
$nome     = mb_substr($nome, 0, 120);
$telefone = mb_substr($telefone, 0, 30);
$mensagem = mb_substr($mensagem, 0, 2000);

/* ---------- 1) Envia o lead para o CRM (webhook) ---------- */
if ($webhook_url !== '') {
    $payload = [
        'nome'     => $nome,
        'telefone' => $telefone,
        'email'    => $email,
        'mensagem' => $mensagem,
        'origem'   => 'Landing page Aclive',
        'pagina'   => $_SERVER['HTTP_REFERER'] ?? '',
        'data'     => date('c'), // formato ISO 8601
    ];

    // Usa cURL se disponível; senão, cai para file_get_contents.
    if (function_exists('curl_init')) {
        $ch = curl_init($webhook_url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 8,
        ]);
        curl_exec($ch); // se falhar, não interrompe o fluxo do formulário
        curl_close($ch);
    } else {
        $ctx = stream_context_create([
            'http' => [
                'method'  => 'POST',
                'header'  => "Content-Type: application/json\r\n",
                'content' => json_encode($payload),
                'timeout' => 8,
            ],
        ]);
        @file_get_contents($webhook_url, false, $ctx);
    }
}

/* ---------- 2) Envia o lead por e-mail ---------- */
$assunto = "Novo lead do site Aclive: {$nome}";

$corpo = "Novo contato recebido pela landing page:\n\n"
       . "Nome: {$nome}\n"
       . "WhatsApp: {$telefone}\n"
       . "E-mail: {$email}\n\n"
       . "Mensagem:\n{$mensagem}\n\n"
       . 'Enviado em: ' . date('d/m/Y H:i:s');

// Remetente fixo do próprio domínio evita cair em spam;
// o Reply-To permite responder direto para o lead.
$headers = "From: Site Aclive <no-reply@digitalaclive.com.br>\r\n"
         . "Reply-To: {$nome} <{$email}>\r\n"
         . "Content-Type: text/plain; charset=utf-8\r\n";

$enviado = mail($destinatario, $assunto, $corpo, $headers);

if ($enviado) {
    echo json_encode(['ok' => true]);
} else {
    http_response_code(500);
    echo json_encode(['ok' => false, 'erro' => 'Falha ao enviar o e-mail.']);
}
