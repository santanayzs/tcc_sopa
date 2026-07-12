<?php
/**
 * recuperar-senha.php — Inicia o fluxo de recuperação de senha
 * Suporta: token por e-mail  OU  código SMS
 */

declare(strict_types=1);

session_start([
    'cookie_httponly' => true,
    'cookie_secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
    'cookie_samesite' => 'Strict',
    'use_strict_mode' => true,
]);

include('../configs/conexao.php');
include('../configs/mailer.php');   // Retorna $mailer (PHPMailer ou similar)
include('../configs/sms.php');      // Retorna $smsClient (Twilio ou similar)

// ── Constantes ────────────────────────────────────────────────────────────────
define('TOKEN_EXPIRA_MIN', 30);     // Token/código válido por 30 minutos
define('MAX_REENVIOS',      3);     // Máximo de pedidos por hora
define('TOKEN_BYTES',       32);    // Bytes de entropia para o token de e-mail
define('CODIGO_DIGITOS',    6);     // Dígitos do código SMS

// ── Método ────────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Location: index.php');
    exit;
}

// ── CSRF ──────────────────────────────────────────────────────────────────────
$csrfToken = $_POST['csrf_token'] ?? '';
if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrfToken)) {
    header('Location: index.php?erro=csrf');
    exit;
}

$metodo = $_POST['metodo'] ?? '';   // 'email' ou 'sms'
$email  = trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL) ?? '');

if (!in_array($metodo, ['email', 'sms'], true) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: index.php?erro=campos');
    exit;
}

// ── Busca usuário — resposta idêntica se não existir (anti-enumeração) ─────────
$stmt = $conn->prepare(
    'SELECT id, nome, email, telefone FROM usuarios WHERE email = ? AND ativo = 1 LIMIT 1'
);
$stmt->bind_param('s', $email);
$stmt->execute();
$usuario = $stmt->get_result()->fetch_assoc();

// Rate limiting: máximo de MAX_REENVIOS pedidos por hora por e-mail
$limite = date('Y-m-d H:i:s', time() - 3600);
$rateCheck = $conn->prepare(
    'SELECT COUNT(*) AS total FROM tokens_recuperacao
     WHERE email = ? AND criado_em >= ?'
);
$rateCheck->bind_param('ss', $email, $limite);
$rateCheck->execute();
$rateRow = $rateCheck->get_result()->fetch_assoc();

if ((int) $rateRow['total'] >= MAX_REENVIOS) {
    // Resposta genérica — não revela que o limite foi atingido para este e-mail
    header('Location: index.php?enviado=1');
    exit;
}

// Invalida tokens anteriores deste e-mail
$inv = $conn->prepare('UPDATE tokens_recuperacao SET usado = 1 WHERE email = ? AND usado = 0');
$inv->bind_param('s', $email);
$inv->execute();

if ($usuario) {
    $expira = date('Y-m-d H:i:s', time() + TOKEN_EXPIRA_MIN * 60);
    $ip     = filter_var($_SERVER['REMOTE_ADDR'] ?? '', FILTER_VALIDATE_IP) ?: '';

    if ($metodo === 'email') {
        // ── Token de e-mail ───────────────────────────────────────────────────
        $token    = bin2hex(random_bytes(TOKEN_BYTES));       // 64 chars hex
        $tokenHash = hash('sha256', $token);                  // Guarda hash, não o token

        $ins = $conn->prepare(
            'INSERT INTO tokens_recuperacao
               (usuario_id, email, token_hash, metodo, expira_em, criado_em, ip_solicitante)
             VALUES (?, ?, ?, "email", ?, NOW(), ?)'
        );
        $ins->bind_param('issss', $usuario['id'], $email, $tokenHash, $expira, $ip);
        $ins->execute();

          $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
          $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
          $link = $protocol . '://' . $_SERVER['HTTP_HOST']
            . $basePath . '/redefinir-senha.php?token=' . urlencode($token);

        // ── Envia e-mail ──────────────────────────────────────────────────────
        $mailer->addAddress($usuario['email'], $usuario['nome']);
        $mailer->Subject = 'Redefinição de senha — S.O.P.A.';
        $mailer->isHTML(true);
        $mailer->Body = renderEmailRecuperacao($usuario['nome'], $link);
        $mailer->AltBody = renderEmailRecuperacaoTexto($usuario['nome'], $link);
        $mailer->send();

    } else {
        // ── Código SMS ────────────────────────────────────────────────────────
        if (empty($usuario['telefone'])) {
            // Sem telefone cadastrado — falha silenciosa
            header('Location: index.php?enviado=1');
            exit;
        }

        $codigo     = str_pad((string) random_int(0, 10 ** CODIGO_DIGITOS - 1), CODIGO_DIGITOS, '0', STR_PAD_LEFT);
        $codigoHash = password_hash($codigo, PASSWORD_DEFAULT);

        $ins = $conn->prepare(
            'INSERT INTO tokens_recuperacao
               (usuario_id, email, token_hash, metodo, expira_em, criado_em, ip_solicitante)
             VALUES (?, ?, ?, "sms", ?, NOW(), ?)'
        );
        $ins->bind_param('issss', $usuario['id'], $email, $codigoHash, $expira, $ip);
        $ins->execute();

        $foneFormatado = '+55' . preg_replace('/\D/', '', $usuario['telefone']);
        $mensagem = "S.O.P.A. - Seu código de redefinição é: {$codigo}\n"
                  . "Válido por " . TOKEN_EXPIRA_MIN . " minutos. "
                  . "Se não foi você, ignore esta mensagem.";

        $smsClient->messages->create($foneFormatado, [
            'from' => getenv('TWILIO_FROM'),
            'body' => $mensagem,
        ]);

        // Salva e-mail na sessão para a tela de confirmação de código
        $_SESSION['recuperacao_email']  = $email;
        $_SESSION['recuperacao_metodo'] = 'sms';
    }
}

// ── Resposta genérica (não revela se o e-mail existe ou não) ──────────────────
header('Location: index.php?enviado=1');
exit;

// ── Templates de e-mail ───────────────────────────────────────────────────────

function renderEmailRecuperacao(string $nome, string $link): string
{
    $nomeEsc  = htmlspecialchars($nome, ENT_QUOTES, 'UTF-8');
    $linkEsc  = htmlspecialchars($link, ENT_QUOTES, 'UTF-8');
    $expira   = TOKEN_EXPIRA_MIN;

    return <<<HTML
    <!DOCTYPE html>
    <html lang="pt-br">
    <head><meta charset="UTF-8"><title>Redefinição de Senha</title></head>
    <body style="font-family:sans-serif;background:#f4f4f4;margin:0;padding:24px">
      <table width="100%" cellpadding="0" cellspacing="0">
        <tr><td align="center">
          <table width="560" style="background:#ffffff;border-radius:8px;padding:40px;border:1px solid #e0e0e0">
            <tr><td>
              <h2 style="color:#344e49;margin-top:0">Redefinição de Senha — S.O.P.A.</h2>
              <p>Olá, <strong>{$nomeEsc}</strong>.</p>
              <p>Recebemos uma solicitação para redefinir a senha da sua conta. Clique no botão abaixo para criar uma nova senha:</p>
              <p style="text-align:center;margin:32px 0">
                <a href="{$linkEsc}"
                   style="background:#344e49;color:#ffffff;text-decoration:none;padding:14px 28px;border-radius:8px;font-weight:bold;display:inline-block">
                  Redefinir minha senha
                </a>
              </p>
              <p style="font-size:0.85rem;color:#555">
                Este link expira em <strong>{$expira} minutos</strong>.<br>
                Se você não solicitou a redefinição de senha, <strong>ignore este e-mail</strong> — sua senha permanece a mesma e nenhuma alteração foi feita na sua conta.
              </p>
              <hr style="border:none;border-top:1px solid #e0e0e0;margin:24px 0">
              <p style="font-size:0.8rem;color:#888;margin:0">S.O.P.A. · Não responda a este e-mail.</p>
            </td></tr>
          </table>
        </td></tr>
      </table>
    </body>
    </html>
    HTML;
}

function renderEmailRecuperacaoTexto(string $nome, string $link): string
{
    $expira = TOKEN_EXPIRA_MIN;
    return <<<TEXT
    Olá, {$nome}.

    Recebemos uma solicitação para redefinir a senha da sua conta no S.O.P.A.

    Acesse o link abaixo para criar uma nova senha (válido por {$expira} minutos):
    {$link}

    Se você não solicitou a redefinição de senha, IGNORE este e-mail.
    Sua senha permanece a mesma e nenhuma alteração foi feita na sua conta.

    — S.O.P.A.
    TEXT;
}
