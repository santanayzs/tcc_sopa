<?php
session_start();

if (!isset($_SESSION['id'])) {
    header('Location: ../auth/index.html');
    exit;
}

$nomeUsuario = isset($_SESSION['nome']) ? $_SESSION['nome'] : 'Usuário';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>S.O.P.A. - Painel</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link
    href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700;800&family=Inter:wght@400;500;600&family=Cormorant+Garamond:wght@500;600&display=swap"
    rel="stylesheet"
  />
  <link rel="stylesheet" href="../CSS/style.css" />
</head>
<body class="dashboard-page">
  <header class="site-header">
    <a href="../index.html" class="logo" aria-label="S.O.P.A. — voltar ao início">
      <span class="logo-badge">S</span>
      <span class="logo-word">S.O.P.A.</span>
    </a>

    <nav class="main-nav">
      <a href="../index.html">Home</a>
      <a href="../auth/logout.php">Sair</a>
    </nav>
  </header>

  <main class="dashboard-shell">
    <section class="dashboard-card">
      <p class="dashboard-eyebrow">Painel do estabelecimento</p>
      <h1>Bem-vindo(a), <?php echo htmlspecialchars($nomeUsuario); ?>!</h1>
      <p class="dashboard-text">
        Aqui você pode organizar seu cardápio, acompanhar pedidos e manter tudo em ordem.
      </p>
      <div class="dashboard-actions">
        <a class="btn-pill" href="../index.html">Voltar para a home</a>
        <a class="btn-pill" href="../criar-cardapio/criar-cardapio.html">Criar cardápio</a>
        <a class="dashboard-btn secondary" href="../auth/logout.php">Sair</a>
      </div>
    </section>
  </main>

  <footer class="site-footer">
    <div class="logo">
      <span class="logo-badge">S</span>
      <span class="logo-word">S.O.P.A.</span>
    </div>
    <p>Sistema Online de Pedidos e Atendimentos</p>
  </footer>
</body>
</html>
