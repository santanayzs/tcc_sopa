<?php
session_start();

if (!isset($_SESSION['id'])) {
    header('Location: ../auth/index.php');
    exit;
}

include '../CRUD-Cardapio/criar-cardapio/conexao.php';

$nomeUsuario = isset($_SESSION['nome']) ? $_SESSION['nome'] : 'Usuário';
$usuarioId = (int) $_SESSION['id'];

$recentCardapios = [];

$stmt = $conexao->prepare(
    'SELECT id, nome_restaurante, categoria, data_criacao, atualizado_em, cor_primaria, cor_texto, cor_fundo_cardapio, cor_fundo_item, logo
     FROM cardapios
     WHERE usuario_id = ?
     ORDER BY GREATEST(data_criacao, COALESCE(atualizado_em, data_criacao)) DESC
     LIMIT 3'
);
$stmt->bind_param('i', $usuarioId);
$stmt->execute();
$resultado = $stmt->get_result();

while ($cardapio = $resultado->fetch_assoc()) {
    $stmtItens = $conexao->prepare(
        'SELECT nome, preco, disponivel, imagem
         FROM itens_cardapio
         WHERE cardapio_id = ?
         ORDER BY id'
    );
    $stmtItens->bind_param('i', $cardapio['id']);
    $stmtItens->execute();
    $cardapio['itens'] = $stmtItens->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmtItens->close();

    $recentCardapios[] = $cardapio;
}
$stmt->close();
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
  <link rel="stylesheet" href="../CRUD-Cardapio/ver-cardapio/style-ver-cardapio.css" />
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

      <div class="dashboard-recentes" style="margin-top: 36px;">
        <div class="dashboard-recentes-header">
          <p class="dashboard-eyebrow" style="margin-bottom: 0;">Cardápios recentes</p>
          <a class="btn-mini dashboard-link-ver-todos" href="../CRUD-Cardapio/ver-cardapio/ver-cardapio.php">Ver todos</a>
        </div>

        <?php if (empty($recentCardapios)): ?>
          <div class="estado-vazio" style="margin-top: 18px;">
            <p>Você ainda não criou nenhum cardápio.</p>
          </div>
        <?php else: ?>
          <div class="cardapios-lista dashboard-cardapios-lista">
            <?php foreach ($recentCardapios as $cardapio): ?>
              <?php
                $corPrimariaPreview = $cardapio['cor_primaria'] ?: '#2f6b4f';
                $corTextoPreview = $cardapio['cor_texto'] ?: '#1c1c1c';
                $corFundoCardapioPreview = $cardapio['cor_fundo_cardapio'] ?: '#f7f5f0';
                $corFundoItemPreview = $cardapio['cor_fundo_item'] ?: '#ffffff';
              ?>
              <article class="cardapio-bloco">
                <a class="cardapio-preview-link" href="../CRUD-Cardapio/ver-cardapio/cardapio-publico.php?id=<?php echo (int) $cardapio['id']; ?>" aria-label="Abrir cardápio público de <?php echo htmlspecialchars($cardapio['nome_restaurante'], ENT_QUOTES, 'UTF-8'); ?>">
                  <div class="cardapio-preview">
                    <div class="cardapio-preview-personalizado" style="background: <?php echo htmlspecialchars($corFundoCardapioPreview, ENT_QUOTES, 'UTF-8'); ?>; color: <?php echo htmlspecialchars($corTextoPreview, ENT_QUOTES, 'UTF-8'); ?>;">
                      <div class="cardapio-preview-cabecalho">
                        <?php if (!empty($cardapio['logo'])): ?>
                          <img src="../uploads/logos/<?php echo htmlspecialchars($cardapio['logo'], ENT_QUOTES, 'UTF-8'); ?>" alt="Logo do restaurante">
                        <?php endif; ?>

                        <div class="cardapio-preview-titulo">
                          <h3 style="color: <?php echo htmlspecialchars($corPrimariaPreview, ENT_QUOTES, 'UTF-8'); ?>;">
                            <?php echo htmlspecialchars($cardapio['nome_restaurante'], ENT_QUOTES, 'UTF-8'); ?>
                          </h3>
                          <?php if (!empty($cardapio['categoria'])): ?>
                            <span style="color: <?php echo htmlspecialchars($corPrimariaPreview, ENT_QUOTES, 'UTF-8'); ?>;">
                              <?php echo htmlspecialchars($cardapio['categoria'], ENT_QUOTES, 'UTF-8'); ?>
                            </span>
                          <?php endif; ?>
                        </div>
                      </div>

                      <?php if (empty($cardapio['itens'])): ?>
                        <div class="cardapio-preview-vazio-personalizado">Sem itens cadastrados</div>
                      <?php else: ?>
                        <?php foreach (array_slice($cardapio['itens'], 0, 2) as $item): ?>
                          <div class="cardapio-preview-item-personalizado" style="background: <?php echo htmlspecialchars($corFundoItemPreview, ENT_QUOTES, 'UTF-8'); ?>; color: <?php echo htmlspecialchars($corTextoPreview, ENT_QUOTES, 'UTF-8'); ?>;">
                            <span><?php echo htmlspecialchars($item['nome'], ENT_QUOTES, 'UTF-8'); ?></span>
                            <span>R$ <?php echo number_format((float) $item['preco'], 2, ',', '.'); ?></span>
                          </div>
                        <?php endforeach; ?>
                      <?php endif; ?>
                    </div>
                  </div>
                </a>

                <div class="cardapio-info">
                  <div class="cardapio-bloco-topo">
                    <h2><?php echo htmlspecialchars($cardapio['nome_restaurante'], ENT_QUOTES, 'UTF-8'); ?></h2>
                  </div>

                  <?php if (!empty($cardapio['categoria'])): ?>
                    <span class="cardapio-categoria">
                      <?php echo htmlspecialchars($cardapio['categoria'], ENT_QUOTES, 'UTF-8'); ?>
                    </span>
                  <?php endif; ?>

                  <p class="cardapio-data">
                    Criado em <?php echo date('d/m/Y \à\s H:i', strtotime($cardapio['data_criacao'])); ?>
                  </p>
                </div>
              </article>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
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
