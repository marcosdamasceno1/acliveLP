<?php
/**
 * ACLIVE — backend do formulário de contato
 *
 * Recebe os dados do formulário da landing page e envia por e-mail.
 * Requer hospedagem com PHP (Hostinger, HostGator, Locaweb etc.).
 *
 * EDITAR: coloque abaixo o e-mail que deve receber os leads.
 */
$destinatario = 'contato@digitalaclive.com.br';

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
