<?php

declare(strict_types=1);

include '../criar-cardapio/conexao.php';

$idCardapio = (int) ($_GET['id'] ?? 0);

$stmt = $conexao->prepare(
    'SELECT nome_restaurante, categoria, cor_primaria, cor_texto, logo
     FROM cardapios
     WHERE id = ?'
);
$stmt->bind_param('i', $idCardapio);
$stmt->execute();
$cardapio = $stmt->get_result()->fetch_assoc();
$stmt->close();

$corPrimaria = $cardapio['cor_primaria'] ?? '#2f6b4f';
$corTexto    = $cardapio['cor_texto'] ?? '#1c1c1c';

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

    <style>
        body.cardapio-publico-body {
            background: #f7f5f0;
            min-height: 100vh;
        }

        .cardapio-publico-shell {
            max-width: 640px;
            margin: 0 auto;
            padding: 60px 20px 80px;
        }

        .cardapio-publico-cabecalho {
            text-align: center;
            margin-bottom: 36px;
        }

        .cardapio-publico-logo {
            width: 84px;
            height: 84px;
            object-fit: contain;
            margin: 0 auto 16px;
            display: block;
        }

        .cardapio-publico-cabecalho h1 {
            font-family: var(--font-display);
            font-size: 2.1rem;
            margin: 0 0 8px;
        }

        .cardapio-publico-categoria {
            font-size: 0.85rem;
            font-weight: 600;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            opacity: 0.75;
        }

        .cardapio-itens {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .cardapio-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            padding: 16px 20px;
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
        }

        .cardapio-item-nome {
            font-weight: 500;
        }

        .cardapio-item-preco {
            font-family: var(--font-display);
            font-weight: 700;
            white-space: nowrap;
        }

        .cardapio-item.indisponivel {
            opacity: 0.45;
        }

        .cardapio-item-badge {
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #c0392b;
            margin-left: 8px;
        }

        .cardapio-publico-rodape {
            text-align: center;
            margin-top: 40px;
            font-size: 0.8rem;
            opacity: 0.6;
        }

        .cardapio-nao-encontrado {
            text-align: center;
            padding: 100px 20px;
        }
    </style>
</head>

<body class="cardapio-publico-body" style="color: <?php echo htmlspecialchars($corTexto, ENT_QUOTES, 'UTF-8'); ?>;">

    <?php if (!$cardapio): ?>
        <div class="cardapio-nao-encontrado">
            <h1>Cardápio não encontrado</h1>
            <p>O link pode estar incorreto ou o cardápio pode ter sido removido.</p>
        </div>
    <?php else: ?>
        <main class="cardapio-publico-shell">
            <div class="cardapio-publico-cabecalho">
                <?php if (!empty($cardapio['logo'])): ?>
                    <img class="cardapio-publico-logo"
                         src="../../uploads/logos/<?php echo htmlspecialchars($cardapio['logo'], ENT_QUOTES, 'UTF-8'); ?>"
                         alt="Logo">
                <?php endif; ?>
                <h1 style="color: <?php echo htmlspecialchars($corPrimaria, ENT_QUOTES, 'UTF-8'); ?>;">
                    <?php echo htmlspecialchars($cardapio['nome_restaurante'], ENT_QUOTES, 'UTF-8'); ?>
                </h1>
                <?php if (!empty($cardapio['categoria'])): ?>
                    <span class="cardapio-publico-categoria">
                        <?php echo htmlspecialchars($cardapio['categoria'], ENT_QUOTES, 'UTF-8'); ?>
                    </span>
                <?php endif; ?>
            </div>

            <?php if (empty($itens)): ?>
                <p style="text-align:center;">Nenhum item cadastrado neste cardápio ainda.</p>
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
                            <span class="cardapio-item-preco" style="color: <?php echo htmlspecialchars($corPrimaria, ENT_QUOTES, 'UTF-8'); ?>;">
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
