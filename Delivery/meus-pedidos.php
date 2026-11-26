<?php

declare(strict_types=1);

session_start();
include __DIR__ . '/../CRUD-Cardapio/criar-cardapio/conexao.php';
garantirTabelasPedidosDelivery($conexao);

$rotulosStatus = [
    'RECEBIDO' => 'Recebido',
    'CONFIRMADO' => 'Confirmado',
    'EM_PREPARO' => 'Em preparo',
    'SAIU_PARA_ENTREGA' => 'Saiu para entrega',
    'ENTREGUE' => 'Entregue',
    'CANCELADO' => 'Cancelado',
];
$etapas = [
    'RECEBIDO' => 'Recebido',
    'CONFIRMADO' => 'Confirmado',
    'EM_PREPARO' => 'Em preparo',
    'SAIU_PARA_ENTREGA' => 'Saiu para entrega',
    'ENTREGUE' => 'Entregue',
];
$pedidos = [];
$clienteToken = $_SESSION['delivery_customer_token'] ?? '';

if (is_string($clienteToken) && $clienteToken !== '') {
    $clienteTokenHash = hash('sha256', $clienteToken);
    $stmtPedidos = $conexao->prepare(
        'SELECT p.id, p.nome_estabelecimento, p.categoria, p.status, p.total, p.criado_em,
                i.nome_item, i.quantidade, i.preco_unitario
         FROM pedidos_delivery p
         LEFT JOIN itens_pedido_delivery i ON i.pedido_id = p.id
         WHERE p.cliente_token_hash = ?
         ORDER BY p.criado_em DESC, p.id DESC, i.id'
    );
    $stmtPedidos->bind_param('s', $clienteTokenHash);
    $stmtPedidos->execute();
    $linhasPedidos = $stmtPedidos->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmtPedidos->close();

    foreach ($linhasPedidos as $linha) {
        $pedidoId = (int) $linha['id'];
        if (!isset($pedidos[$pedidoId])) {
            $pedidos[$pedidoId] = [
                'id' => $pedidoId,
                'nome_estabelecimento' => $linha['nome_estabelecimento'],
                'categoria' => $linha['categoria'],
                'status' => $linha['status'],
                'total' => (float) $linha['total'],
                'criado_em' => $linha['criado_em'],
                'itens' => [],
            ];
        }

        if ($linha['nome_item'] !== null) {
            $pedidos[$pedidoId]['itens'][] = [
                'nome' => $linha['nome_item'],
                'quantidade' => (int) $linha['quantidade'],
                'preco_unitario' => (float) $linha['preco_unitario'],
            ];
        }
    }
}

?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="description" content="Acompanhe a situação dos seus pedidos de delivery no S.O.P.A." />
  <title>Meus pedidos | S.O.P.A.</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="style-delivery.css" />
  <link rel="stylesheet" href="meus-pedidos.css" />
</head>
<body>
  <header class="site-header">
    <a class="brand" href="../index.html" aria-label="S.O.P.A., início">
      <img src="../img/logotipo.svg" alt="S.O.P.A." class="logotipo" />
    </a>
    <nav class="main-nav" aria-label="Navegação principal">
      <a href="index.php#estabelecimentos">Cardápios</a>
      <a href="meus-pedidos.php" aria-current="page">Meus pedidos</a>
    </nav>
    <a class="account-link" href="index.php">Voltar ao delivery</a>
  </header>

  <main class="orders-shell">
    <section class="orders-heading" aria-labelledby="orders-title">
      <p class="eyebrow">Delivery S.O.P.A.</p>
      <h1 id="orders-title">Acompanhe seus pedidos</h1>
      <p>Veja as atualizações do estabelecimento e acompanhe cada etapa até a entrega.</p>
      <p class="orders-refresh-note">Avisaremos nesta página quando a situação mudar. Mantenha-a aberta para receber os avisos.</p>
      <button class="orders-notification-button" id="enable-order-notifications" type="button" hidden>Ativar notificações do navegador</button>
    </section>

    <?php if (empty($pedidos)): ?>
      <section class="orders-empty">
        <img class="orders-empty-icon" src="../img/icon.svg" alt="" aria-hidden="true" />
        <h2>Você ainda não tem pedidos para acompanhar</h2>
        <p>Quando fizer um pedido neste navegador, ele aparecerá aqui com as atualizações do estabelecimento.</p>
        <a class="orders-button" href="index.php#estabelecimentos">Explorar cardápios</a>
      </section>
    <?php else: ?>
      <div class="orders-list">
        <?php foreach ($pedidos as $pedido): ?>
          <?php
            $status = (string) $pedido['status'];
            $statusRotulo = $rotulosStatus[$status] ?? $status;
            $etapaAtual = array_search($status, array_keys($etapas), true);
          ?>
          <article class="order-card" data-order-id="<?php echo (int) $pedido['id']; ?>" data-order-status="<?php echo htmlspecialchars($status, ENT_QUOTES, 'UTF-8'); ?>">
            <header class="order-header">
              <div>
                <p class="order-number">Pedido #<?php echo (int) $pedido['id']; ?></p>
                <h2><?php echo htmlspecialchars($pedido['nome_estabelecimento'], ENT_QUOTES, 'UTF-8'); ?></h2>
                <?php if (!empty($pedido['categoria'])): ?>
                  <p class="order-category"><?php echo htmlspecialchars($pedido['categoria'], ENT_QUOTES, 'UTF-8'); ?></p>
                <?php endif; ?>
                <time datetime="<?php echo htmlspecialchars(date(DATE_ATOM, strtotime($pedido['criado_em'])), ENT_QUOTES, 'UTF-8'); ?>">
                  Pedido feito em <?php echo date('d/m/Y às H:i', strtotime($pedido['criado_em'])); ?>
                </time>
              </div>
              <span class="order-status order-status-<?php echo strtolower($status); ?>">
                <?php echo htmlspecialchars($statusRotulo, ENT_QUOTES, 'UTF-8'); ?>
              </span>
            </header>

            <?php if ($status === 'CANCELADO'): ?>
              <p class="order-cancelled" role="status">Este pedido foi cancelado pelo estabelecimento.</p>
            <?php else: ?>
              <ol class="order-progress" aria-label="Etapas do pedido">
                <?php foreach ($etapas as $codigoEtapa => $rotuloEtapa): ?>
                  <?php
                    $indiceEtapa = array_search($codigoEtapa, array_keys($etapas), true);
                    $classeEtapa = $indiceEtapa < $etapaAtual ? 'is-complete' : ($indiceEtapa === $etapaAtual ? 'is-current' : '');
                  ?>
                  <li class="<?php echo $classeEtapa; ?>">
                    <span class="order-progress-dot" aria-hidden="true"></span>
                    <span><?php echo htmlspecialchars($rotuloEtapa, ENT_QUOTES, 'UTF-8'); ?></span>
                  </li>
                <?php endforeach; ?>
              </ol>
            <?php endif; ?>

            <div class="order-details">
              <section>
                <h3>Itens do pedido</h3>
                <ul>
                  <?php foreach ($pedido['itens'] as $item): ?>
                    <li>
                      <span><?php echo (int) $item['quantidade']; ?>× <?php echo htmlspecialchars($item['nome'], ENT_QUOTES, 'UTF-8'); ?></span>
                      <span>R$ <?php echo number_format($item['quantidade'] * $item['preco_unitario'], 2, ',', '.'); ?></span>
                    </li>
                  <?php endforeach; ?>
                </ul>
              </section>
              <p class="order-total">Total <strong>R$ <?php echo number_format($pedido['total'], 2, ',', '.'); ?></strong></p>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </main>

  <div class="order-alerts" id="order-alerts" role="log" aria-live="polite" aria-relevant="additions"></div>

  <footer class="site-footer">
    <a class="brand" href="../index.html" aria-label="S.O.P.A., início">
      <img src="../img/logotipo.svg" alt="S.O.P.A." class="logotipo" />
    </a>
    <p>Sistema Online de Pedidos e Atendimentos</p>
    <p class="footer-fine">&copy; 2026 S.O.P.A. — Projeto de TCC, ETEC João Gomes de Araújo.</p>
  </footer>
  <script src="meus-pedidos.js" defer></script>
</body>
</html>
