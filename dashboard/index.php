<?php
require_once __DIR__ . '/../configs/session_helper.php';
require_once __DIR__ . '/../configs/perfis.php';
exigirAcessoEstabelecimento();

if (!isset($_SESSION['id'])) {
  header('Location: ../auth/index.php');
  exit;
}

include '../CRUD-Cardapio/criar-cardapio/conexao.php';
garantirSchemaPerfis($conn);

$nomeUsuario = isset($_SESSION['nome']) ? $_SESSION['nome'] : 'Usuário';
$usuarioId = (int) $_SESSION['id'];

$stmtFuncionarios = $conn->prepare(
  "SELECT COUNT(*) AS total,
      COALESCE(SUM(ativo = 1), 0) AS ativos
   FROM usuarios
   WHERE usuario_estabelecimento_id = ? AND tipo_usuario = 'funcionario'"
);
$stmtFuncionarios->bind_param('i', $usuarioId);
$stmtFuncionarios->execute();
$resumoFuncionarios = $stmtFuncionarios->get_result()->fetch_assoc();
$funcionariosAtivos = (int) ($resumoFuncionarios['ativos'] ?? 0);
$funcionariosPendentes = (int) ($resumoFuncionarios['total'] ?? 0) - $funcionariosAtivos;
$stmtFuncionarios->close();

$recentCardapios = [];

$stmt = $conexao->prepare(
    'SELECT id, nome_restaurante, categoria, data_criacao, atualizado_em, cor_primaria, cor_texto, cor_fundo_cardapio, cor_fundo_item, logo, delivery_ativo
     FROM cardapios
     WHERE usuario_id = ?
     ORDER BY GREATEST(data_criacao, COALESCE(atualizado_em, data_criacao)) DESC
     LIMIT 4'
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

$stmtComandas = $conexao->prepare(
    'SELECT c.id, c.total, c.data_abertura, m.numero AS numero_mesa,
            cp.nome_restaurante
     FROM comandas c
     INNER JOIN mesas m ON m.id = c.mesa_id
     LEFT JOIN cardapios cp ON cp.id = c.cardapio_id
     WHERE c.usuario_id = ? AND c.status = "ABERTA"
     ORDER BY c.data_abertura DESC, c.id DESC
     LIMIT 4'
);
$stmtComandas->bind_param('i', $usuarioId);
$stmtComandas->execute();
$comandasAbertas = $stmtComandas->get_result()->fetch_all(MYSQLI_ASSOC);
$stmtComandas->close();

garantirTabelasPedidosDelivery($conexao);
$stmtPedidos = $conexao->prepare(
    'SELECT id, nome_estabelecimento, nome_cliente, status, total, criado_em
     FROM pedidos_delivery
     WHERE usuario_id = ?
     ORDER BY criado_em DESC, id DESC
     LIMIT 4'
);
$stmtPedidos->bind_param('i', $usuarioId);
$stmtPedidos->execute();
$pedidosRecentes = $stmtPedidos->get_result()->fetch_all(MYSQLI_ASSOC);
$stmtPedidos->close();

$statusPedidos = [
    'RECEBIDO' => 'Recebido',
    'CONFIRMADO' => 'Confirmado',
    'EM_PREPARO' => 'Em preparo',
    'SAIU_PARA_ENTREGA' => 'Saiu para entrega',
    'ENTREGUE' => 'Entregue',
    'CANCELADO' => 'Cancelado',
];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>S.O.P.A. - Painel</title>
  <link href="../img/icon.svg" rel="icon" class="icon">

  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700;800&family=Inter:wght@400;500;600&family=Cormorant+Garamond:wght@500;600&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="../CSS/style.css" />
  <link rel="stylesheet" href="../dashboard/dashboard.css">
  <link rel="stylesheet" href="../CRUD-Cardapio/ver-cardapio/style-ver-cardapio.css" />
</head>
<body class="dashboard-page">
  <header class="site-header">
    <a href="../dashboard/index.php" class="logo" aria-label="S.O.P.A. — voltar ao Painel">
            <img src="../img/logotipoBranco.svg" alt="logotipo" class="logotipo" >    
        </a>

        <nav class="main-nav" id="main-nav">
          <a href="index.php">Início</a>
          <a href="../CRUD-Cardapio/criar-cardapio/criar-cardapio.php">Criar Cardápio</a>
          <a href="../CRUD-Cardapio/ver-cardapio/ver-cardapio.php">Ver Cardápio</a>
          <a href="../comandas/comandas.php">Comandas</a>
          <a href="funcionarios.php">Funcionários</a>
          <a href="gerenciar-delivery.php">Gerenciar Delivery</a>
          <a href="pedidos-delivery.php">Pedidos Delivery</a>
          <a href="../auth/logout.php" class="btn-sair">Sair</a>
      </nav>
      
         <button
        class="nav-toggle"
        id="nav-toggle"
        aria-label="Abrir menu"
        aria-expanded="false"
        aria-controls="main-nav"
      >
        <span></span><span></span><span></span>
      </button>
    </header>

  <main class="dashboard-shell">
    <section class="dashboard-card">
      <p class="dashboard-eyebrow">Painel do estabelecimento</p>
      <h1>Bem-vindo(a), <?php echo htmlspecialchars($nomeUsuario); ?>!</h1>
      <p class="dashboard-text">
        Aqui você pode organizar seu cardápio, acompanhar pedidos e manter tudo em ordem.
      </p>

      <div class="dashboard-recentes" style="margin-top: 36px;">
         <div class="dashboard-layout">
            <!-- Coluna fixa à esquerda -->
            <a class="feature-card" href="../CRUD-Cardapio/criar-cardapio/criar-cardapio.php" style="text-decoration:none; color:inherit;">

              <svg xmlns="http://www.w3.org/2000/svg"
                  viewBox="0 0 24 24"
                  fill="none"
                  stroke="currentColor"
                  stroke-width="1.6"
                  stroke-linecap="round"
                  stroke-linejoin="round">
                <path d="M14 3v4a1 1 0 0 0 1 1h4"></path>
                <path d="M17 21H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h7l5 5v11a2 2 0 0 1-2 2Z"></path>
                <path d="M9 13h6"></path>
                <path d="M9 17h6"></path>
              </svg>

              <h3>Criar Cardápio</h3>
            </a>
          
           <!-- Coluna direita -->
            <div class="dashboard-area-cardapios">

              <div class="dashboard-cardapios-topo">
                <h2>Meus Cardápios</h2>

                <a class="link-ver-cardapio"
                  href="../CRUD-Cardapio/ver-cardapio/ver-cardapio.php">
                  Ver Cardápio
                </a>
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
        </div>

        <section class="dashboard-modulo" aria-labelledby="funcionarios-title">
          <div class="dashboard-layout">
            <a class="feature-card" href="funcionarios.php" aria-label="Gerenciar funcionários">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <circle cx="9" cy="8" r="3"></circle>
                <path d="M3 20v-1a6 6 0 0 1 12 0v1"></path>
                <path d="M16 5.5a3 3 0 0 1 0 5.8M18 14a5 5 0 0 1 3 4.6V20"></path>
              </svg>
              <h3>Funcionários</h3>
            </a>

            <div class="dashboard-area-cardapios dashboard-resumo">
              <div class="dashboard-cardapios-topo">
                <h2 id="funcionarios-title">Acessos da equipe</h2>
                <a class="link-ver-cardapio" href="funcionarios.php">Gerenciar funcionários</a>
              </div>
              <p class="dashboard-resumo-vazio">
                <?php echo $funcionariosAtivos; ?> ativos · <?php echo $funcionariosPendentes; ?> aguardando aprovação.
              </p>
            </div>
          </div>
        </section>

        <section class="dashboard-modulo" aria-labelledby="garcom-title">
          <div class="dashboard-layout">
            <a class="feature-card" href="../comandas/comandas.php" aria-label="Acessar área do garçom">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M4 4h16v16H4z"></path>
                <path d="M8 8h8M8 12h8M8 16h4"></path>
                <path d="m16 16 1.5 1.5L20 15"></path>
              </svg>
              <h3>Área do Garçom</h3>
            </a>

            <div class="dashboard-area-cardapios dashboard-resumo">
              <div class="dashboard-cardapios-topo">
                <h2 id="garcom-title">Comandas abertas</h2>
                <a class="link-ver-cardapio" href="../comandas/comandas.php">Gerenciar comandas</a>
              </div>

              <?php if (empty($comandasAbertas)): ?>
                <p class="dashboard-resumo-vazio">Você não tem comandas abertas no momento.</p>
              <?php else: ?>
                <div class="dashboard-resumo-lista">
                  <?php foreach ($comandasAbertas as $comanda): ?>
                    <a class="dashboard-resumo-item" href="../comandas/comandas/comanda-detalhe.php?id=<?php echo (int) $comanda['id']; ?>">
                      <span>
                        <strong>Mesa <?php echo (int) $comanda['numero_mesa']; ?></strong>
                        <small><?php echo htmlspecialchars($comanda['nome_restaurante'] ?: 'Cardápio não selecionado', ENT_QUOTES, 'UTF-8'); ?> · aberta às <?php echo date('H:i', strtotime($comanda['data_abertura'])); ?></small>
                      </span>
                      <strong>R$ <?php echo number_format((float) $comanda['total'], 2, ',', '.'); ?></strong>
                    </a>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </section>

        <section class="dashboard-modulo" aria-labelledby="delivery-title">
          <div class="dashboard-layout">
            <a class="feature-card" href="gerenciar-delivery.php" aria-label="Gerenciar delivery">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M3 8h12v10H3z"></path>
                <path d="M15 11h4l3 3v4h-7z"></path>
                <circle cx="7.5" cy="19" r="1.5"></circle>
                <circle cx="18.5" cy="19" r="1.5"></circle>
                <path d="M6 11h6M6 14h4"></path>
              </svg>
              <h3>Gerenciar Delivery</h3>
            </a>

            <div class="dashboard-area-cardapios dashboard-resumo">
              <div class="dashboard-cardapios-topo">
                <h2 id="delivery-title">Cardápios disponíveis</h2>
                <a class="link-ver-cardapio" href="gerenciar-delivery.php">Configurar delivery</a>
              </div>

              <?php if (empty($recentCardapios)): ?>
                <p class="dashboard-resumo-vazio">Cadastre um cardápio para configurar a disponibilidade no delivery.</p>
              <?php else: ?>
                <div class="dashboard-resumo-lista">
                  <?php foreach ($recentCardapios as $cardapio): ?>
                    <a class="dashboard-resumo-item" href="gerenciar-delivery.php">
                      <span>
                        <strong><?php echo htmlspecialchars($cardapio['nome_restaurante'], ENT_QUOTES, 'UTF-8'); ?></strong>
                        <small><?php echo htmlspecialchars($cardapio['categoria'] ?: 'Sem categoria', ENT_QUOTES, 'UTF-8'); ?></small>
                      </span>
                      <span class="dashboard-resumo-status <?php echo !empty($cardapio['delivery_ativo']) ? 'ativo' : ''; ?>">
                        <?php echo !empty($cardapio['delivery_ativo']) ? 'Publicado' : 'Oculto'; ?>
                      </span>
                    </a>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </section>

        <section class="dashboard-modulo" aria-labelledby="pedidos-title">
          <div class="dashboard-layout">
            <a class="feature-card" href="pedidos-delivery.php" aria-label="Acessar pedidos de delivery">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M5 3h14v18H5z"></path>
                <path d="M8 7h8M8 11h8M8 15h5"></path>
                <path d="m14 18 2 2 4-4"></path>
              </svg>
              <h3>Pedidos Delivery</h3>
            </a>

            <div class="dashboard-area-cardapios dashboard-resumo">
              <div class="dashboard-cardapios-topo">
                <h2 id="pedidos-title">Pedidos recentes</h2>
                <a class="link-ver-cardapio" href="pedidos-delivery.php">Ver pedidos</a>
              </div>

              <?php if (empty($pedidosRecentes)): ?>
                <p class="dashboard-resumo-vazio">Os pedidos recebidos pelos seus cardápios aparecerão aqui.</p>
              <?php else: ?>
                <div class="dashboard-resumo-lista">
                  <?php foreach ($pedidosRecentes as $pedido): ?>
                    <a class="dashboard-resumo-item" href="pedidos-delivery.php?status=<?php echo rawurlencode((string) $pedido['status']); ?>">
                      <span>
                        <strong>#<?php echo (int) $pedido['id']; ?> · <?php echo htmlspecialchars($pedido['nome_cliente'], ENT_QUOTES, 'UTF-8'); ?></strong>
                        <small><?php echo htmlspecialchars($pedido['nome_estabelecimento'], ENT_QUOTES, 'UTF-8'); ?> · <?php echo date('d/m/Y H:i', strtotime($pedido['criado_em'])); ?></small>
                      </span>
                      <span class="dashboard-resumo-pedido">
                        <strong>R$ <?php echo number_format((float) $pedido['total'], 2, ',', '.'); ?></strong>
                        <small><?php echo htmlspecialchars($statusPedidos[$pedido['status']] ?? $pedido['status'], ENT_QUOTES, 'UTF-8'); ?></small>
                      </span>
                    </a>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </section>

      </div>

    </section>
  </main>

  <footer class="site-footer">
        <img src="../img/logotipoBranco.svg" alt="logotipoFooter" class="logotipoFooter" >
        <p>Sistema Online de Pedidos e Atendimentos</p>
        <p class="footer-fine">
        &copy; 2026 S.O.P.A. — Projeto de TCC, ETEC João Gomes de Araújo.
      </p>
    </footer>

  <script src="../JS/main-nav.js"></script>

</body>
</html>
