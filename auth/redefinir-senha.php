<?php
/**
 * redefinir-senha.php — valida o token de recuperação e atualiza a senha
 */

declare(strict_types=1);

session_start([
    'cookie_httponly' => true,
    'cookie_secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
    'cookie_samesite' => 'Strict',
    'use_strict_mode' => true,
]);

include('../configs/conexao.php');

function htmlEscape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function csrfField(string $token): string
{
    return '<input type="hidden" name="csrf_token" value="' . htmlEscape($token) . '">';
}

function falha(string $erro): never
{
    header("Location: index.php?erro={$erro}");
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$metodo = $_SERVER['REQUEST_METHOD'];

if ($metodo === 'GET') {
    $token = trim($_GET['token'] ?? '');
    if (strlen($token) !== 64 || !ctype_xdigit($token)) {
        falha('token_invalido');
    }

    $tokenHash = hash('sha256', $token);
    $agora = date('Y-m-d H:i:s');

    $stmt = $conn->prepare(
        'SELECT id FROM tokens_recuperacao
         WHERE token_hash = ? AND metodo = "email" AND usado = 0 AND expira_em > ?
         LIMIT 1'
    );
    $stmt->bind_param('ss', $tokenHash, $agora);
    $stmt->execute();
    $registro = $stmt->get_result()->fetch_assoc();

    if (!$registro) {
        falha('token_invalido');
    }

    $tokenEsc = htmlEscape($token);
    $csrfField = csrfField($_SESSION['csrf_token']);

    echo <<<HTML
<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Redefinir senha — S.O.P.A.</title>
  <link rel="stylesheet" href="../CSS/style.css">
  <style>
    body{background:#eef1ea;padding:40px;font-family:Inter,sans-serif}
    .reset-card{max-width:520px;margin:0 auto;background:#fff;padding:30px;border-radius:16px;box-shadow:0 28px 60px rgba(0,0,0,.12)}
    h1{margin-top:0;color:#344e49}
    label{display:block;margin:.85rem 0 .35rem;color:#344e49;font-weight:600}

    /* Wrapper precisa ser o único elemento de referência para o botão absoluto */
    .password-field{
      position:relative;
      display:block;
      width:100%;
    }
    .password-field input{
      width:100%;
      padding:12px 44px 12px 14px; /* espaço à direita pro olhinho não ficar em cima do texto */
      border:1px solid #ccd7d1;
      border-radius:10px;
      font-size:1rem;
      box-sizing:border-box;
    }

    /* Botão do olhinho: círculo fixo, ancorado dentro do campo, sem depender da cascata externa */
    .password-field .password-toggle{
      all:unset;
      position:absolute !important;
      top:50% !important;
      right:8px !important;
      transform:translateY(-50%) !important;
      width:28px;
      height:28px;
      display:flex;
      align-items:center;
      justify-content:center;
      border-radius:50%;
      background:#eef1ea;
      color:#344e49;
      line-height:1;
      cursor:pointer;
      box-sizing:border-box;
      z-index:2;
    }
    .password-field .password-toggle svg{
      width:18px;
      height:18px;
      display:block;
      pointer-events:none;
    }
    .password-field .password-toggle:hover{
      background:#dfe6df;
    }
    .password-field .password-toggle:focus-visible{
      outline:2px solid #344e49;
      outline-offset:2px;
    }

    .senha-feedback{min-height:1.1rem;margin:6px 0 0;color:#b42318;font-size:.9rem}
    button[type="submit"]{margin-top:22px;width:100%;padding:14px;background:#344e49;color:#fff;border:none;border-radius:10px;font-size:1rem;cursor:pointer}
  </style>
</head>
<body>
  <div class="reset-card">
    <h1>Redefinir senha</h1>
    <p>Digite uma nova senha para a conta vinculada a este link.</p>
    <form action="redefinir-senha.php" method="post" novalidate>
      {$csrfField}
      <input type="hidden" name="token" value="{$tokenEsc}">
      <label for="novaSenha">Nova senha</label>
      <div class="password-field">
        <input type="password" id="novaSenha" name="nova_senha" autocomplete="new-password" minlength="8" required>
        <button class="password-toggle" type="button" data-target="novaSenha" aria-label="Mostrar senha">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7Z"></path>
            <circle cx="12" cy="12" r="3"></circle>
          </svg>
        </button>
      </div>
      <label for="confirmaSenha">Confirmar nova senha</label>
      <div class="password-field">
        <input type="password" id="confirmaSenha" name="confirma_senha" autocomplete="new-password" required>
        <button class="password-toggle" type="button" data-target="confirmaSenha" aria-label="Mostrar senha">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7Z"></path>
            <circle cx="12" cy="12" r="3"></circle>
          </svg>
        </button>
      </div>
      <p class="senha-feedback" id="senhaFeedback" aria-live="polite"></p>
      <button type="submit">Redefinir senha</button>
    </form>
  </div>
  <script>
    (() => {
      const senha = document.getElementById('novaSenha');
      const confirma = document.getElementById('confirmaSenha');
      const feedback = document.getElementById('senhaFeedback');

      function validarConfirmacaoSenha() {
        if (!senha || !confirma || !feedback) return true;
        const ok = senha.value === confirma.value;
        feedback.textContent = ok ? '' : 'As senhas não coincidem.';
        return ok;
      }

      const iconeOlhoAberto = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7Z"></path><circle cx="12" cy="12" r="3"></circle></svg>';
      const iconeOlhoRiscado = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.94 10.94 0 0 1 12 19c-7 0-11-7-11-7a20.4 20.4 0 0 1 4.22-5.19"></path><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 7 11 7a20.5 20.5 0 0 1-2.16 3.19"></path><path d="M14.12 14.12a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>';

      document.querySelectorAll('.password-toggle').forEach(btn => {
        btn.addEventListener('click', () => {
          const input = document.getElementById(btn.dataset.target);
          if (!input) return;
          const mostrar = input.type === 'password';
          input.type = mostrar ? 'text' : 'password';
          btn.innerHTML = mostrar ? iconeOlhoRiscado : iconeOlhoAberto;
          btn.setAttribute('aria-label', mostrar ? 'Ocultar senha' : 'Mostrar senha');
        });
      });

      [senha, confirma].forEach(input => input?.addEventListener('input', validarConfirmacaoSenha));

      document.querySelector('form')?.addEventListener('submit', (event) => {
        if (!validarConfirmacaoSenha()) {
          event.preventDefault();
          confirma?.focus();
        }
      });
    })();
  </script>
</body>
</html>
HTML;
    exit;
}

if ($metodo !== 'POST') {
    http_response_code(405);
    header('Location: index.php');
    exit;
}

$csrfToken = $_POST['csrf_token'] ?? '';
if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrfToken)) {
    falha('csrf');
}

$token       = trim($_POST['token'] ?? '');
$novaSenha   = $_POST['nova_senha'] ?? '';
$confirmacao = $_POST['confirma_senha'] ?? '';

if ($token === '' || strlen($token) !== 64 || !ctype_xdigit($token)) {
    falha('token_invalido');
}

if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/', $novaSenha)) {
    falha('senha_fraca');
}

if ($novaSenha !== $confirmacao) {
    falha('senha_diverge');
}

$tokenHash = hash('sha256', $token);
$agora     = date('Y-m-d H:i:s');

$stmt = $conn->prepare(
    'SELECT id, usuario_id FROM tokens_recuperacao
     WHERE token_hash = ? AND metodo = "email" AND usado = 0 AND expira_em > ?
     LIMIT 1'
);
$stmt->bind_param('ss', $tokenHash, $agora);
$stmt->execute();
$registro = $stmt->get_result()->fetch_assoc();

if (!$registro) {
    falha('token_invalido');
}

$usuarioId = (int) $registro['usuario_id'];
$tokenDbId = (int) $registro['id'];
$novoHash = password_hash($novaSenha, PASSWORD_DEFAULT);

$conn->begin_transaction();
try {
    $upd = $conn->prepare('UPDATE usuarios SET senha = ? WHERE id = ?');
    $upd->bind_param('si', $novoHash, $usuarioId);
    $upd->execute();

    $mark = $conn->prepare('UPDATE tokens_recuperacao SET usado = 1 WHERE id = ?');
    $mark->bind_param('i', $tokenDbId);
    $mark->execute();

    $conn->commit();
} catch (Throwable $e) {
    $conn->rollback();
    error_log('Redefinição de senha falhou: ' . $e->getMessage());
    falha('servidor');
}

unset($_SESSION['csrf_token']);

header('Location: index.php?senha_redefinida=1');
exit;