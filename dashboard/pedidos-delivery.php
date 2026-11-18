<?php

declare(strict_types=1);

require_once __DIR__ . '/../configs/session_helper.php';

if (!isset($_SESSION['id'])) {
    header('Location: ../auth/index.php');
    exit;
}

include __DIR__ . '/../CRUD-Cardapio/criar-cardapio/conexao.php';
garantirTabelasPedidosDelivery($conexao);

$usuarioId = (int) $_SESSION['id'];
$csrfToken = $_SESSION['delivery_order_csrf'] ??= bin2hex(random_bytes(32));
$statusFiltro = $_GET['status'] ?? '';
$statusPermitidos = ['RECEBIDO', 'CONFIRMADO', 'EM_PREPARO', 'SAIU_PARA_ENTREGA', 'ENTREGUE', 'CANCELADO'];
if (!is_string($statusFiltro) || !in_array($statusFiltro, $statusPermitidos, true)) {
    $statusFiltro = '';
}

$rotulosStatus = [
    'RECEBIDO' => 'Recebido',
    'CONFIRMADO' => 'Confirmado',
    'EM_PREPARO' => 'Em preparo',
    'SAIU_PARA_ENTREGA' => 'Saiu para entrega',
    'ENTREGUE' => 'Entregue',
    'CANCELADO' => 'Cancelado',
];

$sqlPedidos =
    'SELECT p.id, p.cardapio_id, p.nome_estabelecimento, p.categoria,
            p.nome_cliente, p.telefone_cliente, p.endereco_entrega,
            p.observacao, p.status, p.total, p.criado_em,
            i.nome_item, i.quantidade, i.preco_unitario
     FROM pedidos_delivery p
     LEFT JOIN itens_pedido_delivery i ON i.pedido_id = p.id
     WHERE p.usuario_id = ?';
$tipos = 'i';
$parametros = [$usuarioId];
if ($statusFiltro !== '') {
    $sqlPedidos .= ' AND p.status = ?';
    $tipos .= 's';
    $parametros[] = $statusFiltro;
}
$sqlPedidos .= ' ORDER BY p.criado_em DESC, p.id DESC, i.id';

$stmtPedidos = $conexao->prepare($sqlPedidos);
$stmtPedidos->bind_param($tipos, ...$parametros);
$stmtPedidos->execute();
$linhasPedidos = $stmtPedidos->get_result()->fetch_all(MYSQLI_ASSOC);
$stmtPedidos->close();

$pedidos = [];
foreach ($linhasPedidos as $linha) {
    $pedidoId = (int) $linha['id'];
    if (!isset($pedidos[$pedidoId])) {
        $pedidos[$pedidoId] = [
            'id' => $pedidoId,
            'nome_estabelecimento' => $linha['nome_estabelecimento'],
            'categoria' => $linha['categoria'],
            'nome_cliente' => $linha['nome_cliente'],
            'telefone_cliente' => $linha['telefone_cliente'],
            'endereco_entrega' => $linha['endereco_entrega'],
            'observacao' => $linha['observacao'],
            'status' => $linha['status'],
            'total' => $linha['total'],
            'criado_em' => $linha['criado_em'],
            'itens' => [],
        ];
    }
    if ($linha['nome_item'] !== null) {
        $pedidos[$pedidoId]['itens'][] = [
            'nome' => $linha['nome_item'],
            'quantidade' => (int) $linha['quantidade'],
            'preco' => (float) $linha['preco_unitario'],
        ];
    }
}

$pedidoAtualizado = ($_GET['resultado'] ?? '') === 'atualizado';
$pedidoErro = ($_GET['resultado'] ?? '') === 'erro';
?>
<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Pedidos de Delivery — S.O.P.A.</title>
  <link href="../img/icon.svg" rel="icon" class="icon">
  
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700;800&family=Inter:wght@400;500;600&family=Cormorant+Garamond:wght@500;600&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="../CSS/style.css" />
  <link rel="stylesheet" href="delivery.css" />
  <link rel="stylesheet" href="pedidos-delivery.css" />
</head>
<body class="dashboard-page delivery-settings-page">
  <header class="site-header">
    <a href="index.php" class="logo" aria-label="S.O.P.A. — voltar ao Painel">
      <img src="../img/logotipoBranco.svg" alt="Logotipo S.O.P.A." class="logotipo">
    </a>
    <nav class="main-nav" id="main-nav">
      <a href="index.php">Início</a>
      <a href="../CRUD-Cardapio/criar-cardapio/criar-cardapio.php">Criar Cardápio</a>
      <a href="../CRUD-Cardapio/ver-cardapio/ver-cardapio.php">Ver Cardápio</a>
      <a href="../comandas/comandas.php">Comandas</a>
      <a href="gerenciar-delivery.php">Gerenciar Delivery</a>
      <a href="pedidos-delivery.php">Pedidos Delivery</a>
      <a href="../auth/logout.php" class="btn-sair">Sair</a>
  </nav>
    <button
      class="nav-toggle"
      id="nav-toggle"
      aria-label="Abrir menu"
      aria-expanded="false"
      aria-controls="main-nav">
      <span></span><span></span><span></span>
    </button>
  </header>

  <main class="dashboard-shell">
    <section class="dashboard-card pedidos-delivery-shell" aria-labelledby="pedidos-title">
      <p class="dashboard-eyebrow">Atendimento online</p>
      <div class="pedidos-heading">
        <div>
          <h1 id="pedidos-title">Pedidos de delivery</h1>
          <p class="dashboard-text">Acompanhe os pedidos enviados pelos clientes e atualize cada etapa.</p>
        </div>
      </div>

      <?php if ($pedidoAtualizado): ?>
        <p class="delivery-message delivery-message-success" role="status">Status do pedido atualizado.</p>
      <?php elseif ($pedidoErro): ?>
        <p class="delivery-message delivery-message-error" role="alert">Não foi possível atualizar o pedido selecionado.</p>
      <?php endif; ?>

      <form class="pedidos-filtro" method="get">
        <label for="status">Situação</label>
        <select name="status" id="status" onchange="this.form.submit()">
          <option value="">Todos</option>
          <?php foreach ($statusPermitidos as $status): ?>
            <option value="<?php echo htmlspecialchars($status, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $statusFiltro === $status ? 'selected' : ''; ?>>
              <?php echo htmlspecialchars($rotulosStatus[$status], ENT_QUOTES, 'UTF-8'); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </form>

      <?php if (empty($pedidos)): ?>
        <div class="pedidos-empty">
          <h2>Nenhum pedido nesta lista</h2>
          <p>Quando um cliente enviar um pedido por um cardápio com delivery ativo, ele aparecerá aqui.</p>
        </div>
      <?php else: ?>
        <div class="pedidos-lista">
          <?php foreach ($pedidos as $pedido): ?>
            <?php $status = (string) $pedido['status']; ?>
            <article class="pedido-admin">
              <header class="pedido-admin-header">
                <div>
                  <p class="pedido-admin-numero">Pedido #<?php echo (int) $pedido['id']; ?></p>
                  <h2><?php echo htmlspecialchars($pedido['nome_estabelecimento'] . (!empty($pedido['categoria']) ? ' · ' . $pedido['categoria'] : ''), ENT_QUOTES, 'UTF-8'); ?></h2>
                  <time datetime="<?php echo htmlspecialchars(date(DATE_ATOM, strtotime($pedido['criado_em'])), ENT_QUOTES, 'UTF-8'); ?>">
                    <?php echo date('d/m/Y H:i', strtotime($pedido['criado_em'])); ?>
                  </time>
                </div>
                <span class="pedido-status pedido-status-<?php echo strtolower($status); ?>">
                  <?php echo htmlspecialchars($rotulosStatus[$status] ?? $status, ENT_QUOTES, 'UTF-8'); ?>
                </span>
              </header>

              <div class="pedido-admin-content">
                <section>
                  <h3>Cliente</h3>
                  <p><strong>Nome:</strong> <?php echo htmlspecialchars($pedido['nome_cliente'], ENT_QUOTES, 'UTF-8'); ?></p>
                  <p><strong>Tel:</strong> <a href="tel:<?php echo htmlspecialchars(preg_replace('/[^0-9+]/', '', $pedido['telefone_cliente']), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($pedido['telefone_cliente'], ENT_QUOTES, 'UTF-8'); ?></a></p>
                  <p><strong>Endereço:</strong> <?php echo nl2br(htmlspecialchars($pedido['endereco_entrega'], ENT_QUOTES, 'UTF-8')); ?></p>
                  <?php if (!empty($pedido['observacao'])): ?>
                    <p class="pedido-observacao-admin"><strong>Observação:</strong> <?php echo nl2br(htmlspecialchars($pedido['observacao'], ENT_QUOTES, 'UTF-8')); ?></p>
                  <?php endif; ?>
                </section>

                <section>
                  <h3>Itens</h3>
                  <ul class="pedido-itens-admin">
                    <?php foreach ($pedido['itens'] as $item): ?>
                      <li>
                        <span><?php echo (int) $item['quantidade']; ?>× <?php echo htmlspecialchars($item['nome'], ENT_QUOTES, 'UTF-8'); ?></span>
                        <span>R$ <?php echo number_format($item['quantidade'] * $item['preco'], 2, ',', '.'); ?></span>
                      </li>
                    <?php endforeach; ?>
                  </ul>
                  <p class="pedido-total-admin">Total <strong>R$ <?php echo number_format((float) $pedido['total'], 2, ',', '.'); ?></strong></p>
                </section>
              </div>

              <form class="pedido-status-form" action="atualizar-status-pedido.php" method="post">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="pedido_id" value="<?php echo (int) $pedido['id']; ?>">
                <label for="status-<?php echo (int) $pedido['id']; ?>">Atualizar situação</label>
                <select name="status" id="status-<?php echo (int) $pedido['id']; ?>" required>
                  <?php foreach ($statusPermitidos as $opcaoStatus): ?>
                    <option value="<?php echo htmlspecialchars($opcaoStatus, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $status === $opcaoStatus ? 'selected' : ''; ?>>
                      <?php echo htmlspecialchars($rotulosStatus[$opcaoStatus], ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
                <button type="submit" class="btn-mini">Salvar situação</button>
              </form>
            </article>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>
  </main>

  <footer class="site-footer">
    <img src="../img/logotipoBranco.svg" alt="Logotipo S.O.P.A." class="logotipoFooter">
    <p>Sistema Online de Pedidos e Atendimentos</p>
    <p class="footer-fine">
      &copy; 2026 S.O.P.A. — Projeto de TCC, ETEC João Gomes de Araújo.
    </p>
  </footer>
  <script src="../JS/main-nav.js"></script>
</body>
</html>