<?php
// Cardapio que fica publico para ser acessado por meio de um QR code (não tem como teste pelo xammp)
declare(strict_types=1);

include '../criar-cardapio/conexao.php';

$idCardapio = (int) ($_GET['id'] ?? 0);

$stmt = $conexao->prepare(
    'SELECT nome_restaurante, categoria
     FROM cardapios
     WHERE id = ?'
);
$stmt->bind_param('i', $idCardapio);
$stmt->execute();
$cardapio = $stmt->get_result()->fetch_assoc();
$stmt->close();

$itens = [];

if ($cardapio) {
    $stmtItens = $conexao->prepare(
        'SELECT nome, preco, disponivel
         FROM itens_cardapio
         WHERE cardapio_id = ?
         ORDER BY id'
    );
    $stmtItens->bind_param('i', $idCardapio);
    $stmtItens->execute();
    $itens = $stmtItens->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmtItens->close();
}
?>
<!doctype html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo $cardapio ? htmlspecialchars($cardapio['nome_restaurante'], ENT_QUOTES, 'UTF-8') . ' — Cardápio' : 'Cardápio não encontrado'; ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700;800&family=Inter:wght@400;500;600&family=Cormorant+Garamond:wght@500;600&display=swap"
        rel="stylesheet" />

    <link rel="stylesheet" href="../../CSS/style.css" />
    <link rel="stylesheet" href="style-card-public" />
</head>

<body class="dashboard-page">

    <?php if (!$cardapio): ?>
        <div class="cardapio-nao-encontrado">
            <h1>Cardápio não encontrado</h1>
            <p class="dashboard-text">O link pode estar incorreto ou o cardápio pode ter sido removido.</p>
        </div>
    <?php else: ?>
        <main class="cardapio-publico-shell">
            <div class="cardapio-publico-cabecalho">
                <h1><?php echo htmlspecialchars($cardapio['nome_restaurante'], ENT_QUOTES, 'UTF-8'); ?></h1>
                <?php if (!empty($cardapio['categoria'])): ?>
                    <span class="cardapio-publico-categoria">
                        <?php echo htmlspecialchars($cardapio['categoria'], ENT_QUOTES, 'UTF-8'); ?>
                    </span>
                <?php endif; ?>
            </div>

            <?php if (empty($itens)): ?>
                <p style="text-align:center; color: var(--cream-2);">Nenhum item cadastrado neste cardápio ainda.</p>
            <?php else: ?>
                <div class="cardapio-itens">
                    <?php foreach ($itens as $item): ?>
                        <div class="cardapio-item <?php echo $item['disponivel'] ? '' : 'indisponivel'; ?>">
                            <span class="cardapio-item-nome">
                                <?php echo htmlspecialchars($item['nome'], ENT_QUOTES, 'UTF-8'); ?>
                                <?php if (!$item['disponivel']): ?>
                                    <span class="cardapio-item-badge">Indisponível</span>
                                <?php endif; ?>
                            </span>
                            <span class="cardapio-item-preco">
                                R$ <?php echo number_format((float) $item['preco'], 2, ',', '.'); ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <p class="cardapio-publico-rodape">Cardápio via S.O.P.A.</p>
        </main>
    <?php endif; ?>

</body>

</html>
