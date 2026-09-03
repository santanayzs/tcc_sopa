<?php
require_once __DIR__ . '/../configs/session_helper.php';

if (!isset($_SESSION['id'])) {
  header('Location: ../auth/index.php');
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

      <div class="card-grid" style="margin-top: 32px;">
        <a class="feature-card" href="../CRUD-Cardapio/criar-cardapio/criar-cardapio.php" style="text-decoration:none; color:inherit;">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
            <path d="M14 3v4a1 1 0 0 0 1 1h4"></path>
            <path d="M17 21H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h7l5 5v11a2 2 0 0 1-2 2Z"></path>
            <path d="M9 13h6"></path>
            <path d="M9 17h6"></path>
          </svg>
          <h3>Criar Cardápio</h3>
        </a>

        <a class="feature-card" href="../CRUD-Cardapio/ver-cardapio/ver-cardapio.php" style="text-decoration:none; color:inherit;">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
            <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7Z"></path>
            <circle cx="12" cy="12" r="3"></circle>
          </svg>
          <h3>Ver Cardápio</h3>
        </a>

        <!-- Próximas funcionalidades entram aqui, seguindo o mesmo padrão de .feature-card -->
      </div>

      <div class="dashboard-actions">
        <a class="btn-pill" href="../index.html">Voltar para a home</a>
        <a class="btn-pill" href="../auth/logout.php">Sair</a>
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
