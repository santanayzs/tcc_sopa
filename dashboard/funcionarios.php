<?php
declare(strict_types=1);

require_once __DIR__ . '/../configs/session_helper.php';
require_once __DIR__ . '/../configs/perfis.php';
garantirSchemaPerfis($conn);

if (empty($_SESSION['id']) || !in_array($_SESSION['tipo_usuario'] ?? '', ['empresa', 'admin'], true)) {
    http_response_code(403);
    exit('Acesso não autorizado.');
}

$usuarioId = (int) $_SESSION['id'];
$_SESSION['csrf_funcionarios'] ??= bin2hex(random_bytes(32));
$csrfToken = $_SESSION['csrf_funcionarios'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    $funcionarioId = filter_var($_POST['funcionario_id'] ?? null, FILTER_VALIDATE_INT);
    $acao = $_POST['acao'] ?? '';

    if (!is_string($token) || !hash_equals($csrfToken, $token) || !$funcionarioId || !in_array($acao, ['aprovar', 'suspender'], true)) {
        http_response_code(400);
        exit('Solicitação inválida.');
    }

    $ativo = $acao === 'aprovar' ? 1 : 0;
    $stmtAtualizar = $conn->prepare(
        "UPDATE usuarios SET ativo = ?
         WHERE id = ? AND usuario_estabelecimento_id = ? AND tipo_usuario = 'funcionario'"
    );
    $stmtAtualizar->bind_param('iii', $ativo, $funcionarioId, $usuarioId);
    $stmtAtualizar->execute();
    header('Location: funcionarios.php?atualizado=1');
    exit;
}

$stmtFuncionarios = $conn->prepare(
    "SELECT id, nome, email, telefone, ativo, criado_em
     FROM usuarios
     WHERE usuario_estabelecimento_id = ? AND tipo_usuario = 'funcionario'
     ORDER BY ativo ASC, nome ASC"
);
$stmtFuncionarios->bind_param('i', $usuarioId);
$stmtFuncionarios->execute();
$funcionarios = $stmtFuncionarios->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Funcionários — S.O.P.A.</title>
  <link rel="stylesheet" href="../CSS/style.css" />
  <link rel="stylesheet" href="dashboard.css" />
  <style>
    .staff-shell { width: min(100% - 36px, 980px); margin: 42px auto; }
    .staff-shell h1 { color: var(--green-900); }
    .staff-note, .staff-empty { color: var(--green-800); }
    .staff-table-wrap { overflow-x: auto; margin-top: 24px; }
    .staff-table { width: 100%; border-collapse: collapse; text-align: left; background: #f7f8f4; }
    .staff-table th, .staff-table td { padding: 14px 12px; border-bottom: 1px solid #d5ddd2; }
    .staff-table th { color: var(--green-900); }
    .staff-status { font-weight: 600; }
    .staff-action { border: 0; border-radius: 6px; padding: 9px 12px; background: var(--green-800); color: #fff; cursor: pointer; }
    .staff-action.suspend { background: #8d3c32; }
    .staff-feedback { padding: 12px; background: #e3eee4; color: #244b34; }
    @media (max-width: 640px) { .staff-table { min-width: 640px; } }
  </style>
</head>
<body class="dashboard-page">
  <header class="site-header">
    <a href="index.php" class="logo" aria-label="Voltar ao painel">
      <img src="../img/logotipoBranco.svg" alt="Logotipo S.O.P.A." class="logotipo">
    </a>
    <nav class="main-nav" id="main-nav">
      <a href="index.php">Início</a>
      <a href="../comandas/comandas.php">Comandas</a>
      <a href="funcionarios.php" aria-current="page">Funcionários</a>
      <a href="../auth/logout.php" class="btn-sair">Sair</a>
    </nav>
    <button class="nav-toggle" id="nav-toggle" aria-label="Abrir menu" aria-expanded="false" aria-controls="main-nav">
      <span></span><span></span><span></span>
    </button>
  </header>

  <main class="staff-shell">
    <p class="dashboard-eyebrow">Acessos do estabelecimento</p>
    <h1>Funcionários</h1>
    <p class="staff-note">Aprove ou suspenda contas que podem operar as comandas.</p>
    <?php if (isset($_GET['atualizado'])): ?>
      <p class="staff-feedback" role="status">Acesso atualizado.</p>
    <?php endif; ?>

    <?php if (empty($funcionarios)): ?>
      <p class="staff-empty">Ainda não há cadastros de funcionários vinculados ao estabelecimento.</p>
    <?php else: ?>
      <div class="staff-table-wrap">
        <table class="staff-table">
          <thead><tr><th>Nome</th><th>E-mail</th><th>Telefone</th><th>Status</th><th>Ação</th></tr></thead>
          <tbody>
            <?php foreach ($funcionarios as $funcionario): ?>
              <tr>
                <td><?= htmlspecialchars($funcionario['nome'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($funcionario['email'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($funcionario['telefone'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                <td class="staff-status"><?= (int) $funcionario['ativo'] === 1 ? 'Ativo' : 'Pendente ou suspenso' ?></td>
                <td>
                  <form method="post">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="funcionario_id" value="<?= (int) $funcionario['id'] ?>">
                    <?php if ((int) $funcionario['ativo'] === 1): ?>
                      <button class="staff-action suspend" type="submit" name="acao" value="suspender">Suspender</button>
                    <?php else: ?>
                      <button class="staff-action" type="submit" name="acao" value="aprovar">Aprovar</button>
                    <?php endif; ?>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </main>
  <script src="../JS/main-nav.js"></script>
</body>
</html>