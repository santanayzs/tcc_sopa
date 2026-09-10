<?php

declare(strict_types=1);

session_start();

if (!isset($_SESSION['id'])) {
    header('Location: ../../auth/index.php');
    exit;
}

include '../../configs/conexao.php';

$usuarioId = (int) ($_SESSION['id'] ?? 0);
$comandaId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

$mensagens = [
    'item_adicionado' => 'Item adicionado com sucesso!',
    'item_removido' => 'Item removido da comanda.',
    'comanda_cancelada' => 'Comanda cancelada com sucesso.',
    'erro_item' => 'Não foi possível processar este item.',
];
$aviso = $_GET['aviso'] ?? '';

if ($comandaId <= 0) {
    header('Location: ../teste_comandas.php');
    exit;
}

$stmtComanda = $conn->prepare(
    'SELECT c.id, c.cardapio_id, c.total, c.status, c.data_abertura,
            m.numero AS numero_mesa,
            p.nome_restaurante
     FROM comandas c
     INNER JOIN mesas m ON m.id = c.mesa_id
     INNER JOIN cardapios p ON p.id = c.cardapio_id
     WHERE c.id = ? AND c.usuario_id = ? AND c.status = "ABERTA"
     LIMIT 1'
);
$stmtComanda->bind_param('ii', $comandaId, $usuarioId);
$stmtComanda->execute();
$comanda = $stmtComanda->get_result()->fetch_assoc();
$stmtComanda->close();

if (!$comanda) {
    header('Location: ../teste_comandas.php?aviso=naoencontrado');
    exit;
}

$stmtItensComanda = $conn->prepare(
    'SELECT ic.nome, ic.preco, ic.id AS item_id,
            itc.id AS linha_item_id,
            itc.quantidade,
            itc.observacao,
            (itc.quantidade * itc.preco_unitario) AS subtotal
     FROM itens_comanda itc
     INNER JOIN itens_cardapio ic ON ic.id = itc.item_cardapio_id
     WHERE itc.comanda_id = ?
     ORDER BY ic.nome'
);
$stmtItensComanda->bind_param('i', $comandaId);
$stmtItensComanda->execute();
$itensComanda = $stmtItensComanda->get_result()->fetch_all(MYSQLI_ASSOC);
$stmtItensComanda->close();

$stmtMenu = $conn->prepare(
    'SELECT id, nome, preco, descricao
     FROM itens_cardapio
     WHERE cardapio_id = ? AND disponivel = 1
     ORDER BY nome'
);
$stmtMenu->bind_param('i', $comanda['cardapio_id']);
$stmtMenu->execute();
$menu = $stmtMenu->get_result()->fetch_all(MYSQLI_ASSOC);
$stmtMenu->close();
?>
<!doctype html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Comanda — S.O.P.A.</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="../../CSS/style.css" />
    <link rel="stylesheet" href="../testecomandas.css" />
</head>
<body class="dashboard-page">
    <header class="site-header">
        <a href="../../index.html" class="logo">
            <span class="logo-badge">S</span>
            <span class="logo-word">S.O.P.A.</span>
        </a>

        <nav class="main-nav">
            <a href="../../index.html">Início</a>
            <a href="../../dashboard/index.php">Painel</a>
            <a href="../teste_comandas.php?cardapio_id=<?php echo (int) $comanda['cardapio_id']; ?>">Voltar</a>
            <a href="../../auth/logout.php">Sair</a>
        </nav>
    </header>

    <main class="dashboard-shell">
        <div class="dashboard-card comandas-shell">
            <p class="dashboard-eyebrow">Comanda em andamento</p>
            <h1>Comanda da Mesa <?php echo (int) $comanda['numero_mesa']; ?></h1>
            <p class="dashboard-text">
                Cardápio: <strong><?php echo htmlspecialchars($comanda['nome_restaurante'], ENT_QUOTES, 'UTF-8'); ?></strong>
            </p>

            <?php if ($aviso && isset($mensagens[$aviso])): ?>
                <p class="comanda-aviso comanda-aviso-ok"><?php echo htmlspecialchars($mensagens[$aviso], ENT_QUOTES, 'UTF-8'); ?></p>
            <?php endif; ?>

            <div class="comanda-topo">
                <div>
                    <p><strong>Aberta em:</strong> <?php echo date('d/m/Y H:i', strtotime($comanda['data_abertura'])); ?></p>
                    <p><strong>Total atual:</strong> R$ <?php echo number_format((float) $comanda['total'], 2, ',', '.'); ?></p>
                </div>
            </div>

            <section class="comanda-secao">
                <h2>Itens já adicionados</h2>

                <?php if (empty($itensComanda)): ?>
                    <p class="cardapio-vazio">Ainda não há itens nesta comanda.</p>
                <?php else: ?>
                    <div class="lista-itens-comanda">
                        <?php foreach ($itensComanda as $item): ?>
                            <div class="item-comanda-row">
                                <div>
                                    <strong><?php echo htmlspecialchars($item['nome'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                    <span><?php echo (int) $item['quantidade']; ?>× R$ <?php echo number_format((float) $item['preco'], 2, ',', '.'); ?></span>
                                    <?php if (!empty($item['observacao'])): ?>
                                        <small>Obs: <?php echo htmlspecialchars($item['observacao'], ENT_QUOTES, 'UTF-8'); ?></small>
                                    <?php endif; ?>
                                </div>
                                <div style="display:flex; align-items:center; gap:10px;">
                                    <span>R$ <?php echo number_format((float) $item['subtotal'], 2, ',', '.'); ?></span>
                                    <form action="removerItemComanda.php" method="POST" style="margin:0;">
                                        <input type="hidden" name="comanda_id" value="<?php echo (int) $comanda['id']; ?>">
                                        <input type="hidden" name="linha_item_id" value="<?php echo (int) $item['linha_item_id']; ?>">
                                        <button type="submit" class="btn-mini" style="background:#c0392b;">Remover</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <section class="comanda-secao">
                <h2>Adicionar item</h2>

                <?php if (empty($menu)): ?>
                    <p class="cardapio-vazio">Este cardápio ainda não tem itens cadastrados.</p>
                <?php else: ?>
                    <form action="adicionarItemComanda.php" method="POST" class="form-nova-mesa">
                        <input type="hidden" name="comanda_id" value="<?php echo (int) $comanda['id']; ?>">
                        <input type="hidden" name="cardapio_id" value="<?php echo (int) $comanda['cardapio_id']; ?>">

                        <div class="form-group-inline" style="align-items: end;">
                            <div>
                                <label for="item_id">Item</label>
                                <select name="item_id" id="item_id" required>
                                    <?php foreach ($menu as $item): ?>
                                        <option value="<?php echo (int) $item['id']; ?>">
                                            <?php echo htmlspecialchars($item['nome'], ENT_QUOTES, 'UTF-8'); ?>
                                            — R$ <?php echo number_format((float) $item['preco'], 2, ',', '.'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div>
                                <label for="quantidade">Quantidade</label>
                                <input type="number" name="quantidade" id="quantidade" min="1" value="1" required>
                            </div>
                        </div>

                        <div style="margin-top: 1rem;">
                            <label for="observacao">Observação</label>
                            <textarea name="observacao" id="observacao" rows="3" placeholder="Ex.: sem cebola, bem passado..."></textarea>
                        </div>

                        <div class="dashboard-actions" style="justify-content: flex-start; margin-top: 1.25rem;">
                            <button type="submit" class="btn-mini abrir">Adicionar item</button>
                        </div>
                    </form>
                <?php endif; ?>
            </section>

            <section class="comanda-secao">
                <h2>Fechar comanda</h2>
                <form action="fecharComanda.php" method="POST" class="form-nova-mesa">
                    <input type="hidden" name="comanda_id" value="<?php echo (int) $comanda['id']; ?>">
                    <input type="hidden" name="cardapio_id" value="<?php echo (int) $comanda['cardapio_id']; ?>">

                    <div class="form-group-inline" style="align-items: end;">
                        <div>
                            <label for="forma_pagamento">Forma de pagamento</label>
                            <select name="forma_pagamento" id="forma_pagamento" required>
                                <option value="DINHEIRO">Dinheiro</option>
                                <option value="PIX">Pix</option>
                                <option value="DEBITO">Débito</option>
                                <option value="CREDITO">Crédito</option>
                                <option value="OUTRO">Outro</option>
                            </select>
                        </div>

                        <div>
                            <label for="valor_pago">Valor pago</label>
                            <input type="number" name="valor_pago" id="valor_pago" min="0.01" step="0.01" value="<?php echo number_format((float) $comanda['total'], 2, '.', ''); ?>" required>
                        </div>
                    </div>

                    <div style="margin-top: 1rem;">
                        <label for="observacao_pagamento">Observação do pagamento</label>
                        <textarea name="observacao_pagamento" id="observacao_pagamento" rows="3" placeholder="Ex.: troco para R$ 50,00"></textarea>
                    </div>

                    <div class="dashboard-actions" style="justify-content: flex-start; margin-top: 1.25rem;">
                        <button type="submit" class="btn-mini">Fechar comanda</button>
                        <a class="btn-pill" href="../teste_comandas.php?cardapio_id=<?php echo (int) $comanda['cardapio_id']; ?>">Voltar</a>
                    </div>
                </form>
            </section>

            <section class="comanda-secao">
                <h2>Cancelar comanda</h2>
                <form action="cancelarComanda.php" method="POST">
                    <input type="hidden" name="comanda_id" value="<?php echo (int) $comanda['id']; ?>">
                    <input type="hidden" name="cardapio_id" value="<?php echo (int) $comanda['cardapio_id']; ?>">
                    <button type="submit" class="btn-mini" style="background:#8e44ad;">Cancelar comanda</button>
                </form>
            </section>
        </div>
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
