<?php

declare(strict_types=1);

session_start();

// ── Exige login ────────────────────────────────────────────────────────────
if (!isset($_SESSION['id'])) {
    header('Location: ../../auth/index.php');
    exit;
}

include '../criar-cardapio/conexao.php';

$usuarioId  = (int) $_SESSION['id'];
$idCardapio = (int) ($_GET['id'] ?? 0);

// ── Confirma que o cardápio existe e pertence ao usuário logado ──────────
$stmt = $conexao->prepare(
    'SELECT nome_restaurante, categoria, cor_primaria, cor_texto, cor_fundo_cardapio, cor_fundo_item, logo
     FROM cardapios
     WHERE id = ? AND usuario_id = ?'
);
$stmt->bind_param('ii', $idCardapio, $usuarioId);
$stmt->execute();
$cardapio = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$cardapio) {
    header('Location: ../ver-cardapio/ver-cardapio.php?erro=naoencontrado');
    exit;
}

$corPrimaria = $cardapio['cor_primaria'] ?: '#2f6b4f';
$corTexto    = $cardapio['cor_texto'] ?: '#1c1c1c';
$corFundoCardapio = $cardapio['cor_fundo_cardapio'] ?: '#f7f5f0';
$corFundoItem = $cardapio['cor_fundo_item'] ?: '#ffffff';

$stmtItens = $conexao->prepare(
    'SELECT nome, preco, disponivel, imagem
     FROM itens_cardapio
     WHERE cardapio_id = ?
     ORDER BY id'
);
$stmtItens->bind_param('i', $idCardapio);
$stmtItens->execute();
$itens = $stmtItens->get_result()->fetch_all(MYSQLI_ASSOC);
$stmtItens->close();
?>
<!doctype html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Imprimir Cardápio — <?php echo htmlspecialchars($cardapio['nome_restaurante'], ENT_QUOTES, 'UTF-8'); ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700;800&family=Inter:wght@400;500;600&family=Cormorant+Garamond:wght@500;600&display=swap"
        rel="stylesheet" />

    <link rel="stylesheet" href="style-imprimir.css" />

</head>

<body>

    <div class="barra-acoes">
        <button type="button" onclick="window.print()">Baixar / Imprimir PDF</button>
        <a class="secundario" href="../ver-cardapio/ver-cardapio.php">Voltar</a>
    </div>

    <div class="folha-a4"
        style="
            --cor-primaria: <?php echo htmlspecialchars($corPrimaria, ENT_QUOTES, 'UTF-8'); ?>;
            --cor-texto: <?php echo htmlspecialchars($corTexto, ENT_QUOTES, 'UTF-8'); ?>;
            --cor-fundo-cardapio: <?php echo htmlspecialchars($corFundoCardapio, ENT_QUOTES, 'UTF-8'); ?>;
            --cor-fundo-item: <?php echo htmlspecialchars($corFundoItem, ENT_QUOTES, 'UTF-8'); ?>;
        "
    >
        <div class="folha-cabecalho">
            <?php if (!empty($cardapio['logo'])): ?>
                <img src="../../uploads/logos/<?php echo htmlspecialchars($cardapio['logo'], ENT_QUOTES, 'UTF-8'); ?>" alt="Logo">
            <?php endif; ?>
            <h1><?php echo htmlspecialchars($cardapio['nome_restaurante'], ENT_QUOTES, 'UTF-8'); ?></h1>
            <?php if (!empty($cardapio['categoria'])): ?>
                <p><?php echo htmlspecialchars($cardapio['categoria'], ENT_QUOTES, 'UTF-8'); ?></p>
            <?php endif; ?>
        </div>

        <?php if (empty($itens)): ?>
            <p style="text-align:center;">Nenhum item cadastrado neste cardápio.</p>
        <?php else: ?>
            <?php foreach ($itens as $item): ?>
                <div class="folha-item <?php echo $item['disponivel'] ? '' : 'indisponivel'; ?>">
                    <?php if (!empty($item['imagem'])): ?>
                        <img class="folha-item-thumb"
                             src="../../uploads/itens/<?php echo htmlspecialchars($item['imagem'], ENT_QUOTES, 'UTF-8'); ?>"
                             alt="">
                    <?php endif; ?>
                    <span class="folha-item-nome">
                        <?php echo htmlspecialchars($item['nome'], ENT_QUOTES, 'UTF-8'); ?>
                        <?php if (!$item['disponivel']): ?>
                            <span class="folha-item-badge">Indisponível</span>
                        <?php endif; ?>
                    </span>
                    <span class="folha-item-linha"></span>
                    <span class="folha-item-preco">
                        R$ <?php echo number_format((float) $item['preco'], 2, ',', '.'); ?>
                    </span>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <p class="folha-rodape">Cardápio gerado via S.O.P.A.</p>
    </div>

</body>

</html>
