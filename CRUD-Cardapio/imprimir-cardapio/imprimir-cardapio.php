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
    'SELECT nome, preco, disponivel
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

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #d9d9d6;
            margin: 0;
            padding: 30px 0 60px;
        }

        .barra-acoes {
            max-width: 210mm;
            margin: 0 auto 20px;
            display: flex;
            justify-content: center;
            gap: 12px;
        }

        .barra-acoes button,
        .barra-acoes a {
            font-family: 'Inter', sans-serif;
            font-weight: 600;
            font-size: 0.9rem;
            padding: 10px 22px;
            border-radius: 999px;
            border: none;
            cursor: pointer;
            text-decoration: none;
            background: #2f6b4f;
            color: #fff;
        }

        .barra-acoes a.secundario {
            background: transparent;
            border: 1px solid #555;
            color: #333;
        }

        /* ── Folha A4 ─────────────────────────────────────────────────────────── */
        .folha-a4 {
            width: 210mm;
            min-height: 297mm;
            margin: 0 auto;
            background: <?php echo htmlspecialchars($corFundoCardapio, ENT_QUOTES, 'UTF-8'); ?>;
            box-shadow: 0 0 12px rgba(0, 0, 0, 0.25);
            padding: 20mm 18mm;
            color: <?php echo htmlspecialchars($corTexto, ENT_QUOTES, 'UTF-8'); ?>;
        }

        .folha-cabecalho {
            text-align: center;
            margin-bottom: 14mm;
        }

        .folha-cabecalho img {
            width: 26mm;
            height: 26mm;
            object-fit: contain;
            margin: 0 auto 6mm;
            display: block;
        }

        .folha-cabecalho h1 {
            font-family: 'Cormorant Garamond', serif;
            font-size: 30pt;
            font-weight: 600;
            margin: 0 0 4mm;
            color: <?php echo htmlspecialchars($corPrimaria, ENT_QUOTES, 'UTF-8'); ?>;
        }

        .folha-cabecalho p {
            font-size: 10pt;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            margin: 0;
            opacity: 0.7;
        }

        .folha-item {
            display: flex;
            align-items: baseline;
            gap: 8px;
            margin-bottom: 7mm;
            page-break-inside: avoid;
            background: <?php echo htmlspecialchars($corFundoItem, ENT_QUOTES, 'UTF-8'); ?>;
            padding: 6px 10px;
            border-radius: 8px;
        }

        .folha-item-nome {
            font-size: 12pt;
            font-weight: 500;
            white-space: nowrap;
        }

        .folha-item-linha {
            flex: 1;
            border-bottom: 1px dotted #999;
            transform: translateY(-3px);
        }

        .folha-item-preco {
            font-family: 'Cormorant Garamond', serif;
            font-weight: 700;
            font-size: 13pt;
            white-space: nowrap;
            color: <?php echo htmlspecialchars($corPrimaria, ENT_QUOTES, 'UTF-8'); ?>;
        }

        .folha-item.indisponivel {
            opacity: 0.4;
        }

        .folha-item-badge {
            font-size: 8pt;
            font-weight: 700;
            text-transform: uppercase;
            color: #c0392b;
            margin-left: 4px;
        }

        .folha-rodape {
            text-align: center;
            margin-top: 16mm;
            font-size: 8pt;
            opacity: 0.5;
        }

        /* ── Impressão: some tudo, exceto a folha ──────────────────────────────── */
        @media print {
            body {
                background: #fff;
                padding: 0;
            }

            .barra-acoes {
                display: none;
            }

            .folha-a4 {
                box-shadow: none;
                margin: 0;
                width: auto;
                min-height: auto;
            }

            @page {
                size: A4;
                margin: 0;
            }
        }
    </style>
</head>

<body>

    <div class="barra-acoes">
        <button type="button" onclick="window.print()">Baixar / Imprimir PDF</button>
        <a class="secundario" href="../ver-cardapio/ver-cardapio.php">Voltar</a>
    </div>

    <div class="folha-a4">
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
                    <span class="folha-item-nome">
                        <?php echo htmlspecialchars($item['nome'], ENT_QUOTES, 'UTF-8'); ?>
                        <?php if (!$item['disponivel']): ?>
                            <span class="folha-item-badge">Indisponível</span>
                        <?php endif; ?>
                    </span>
                    <span class="folha-item-linha"></span>
                    <span class="folha-item-preco" style="color: <?php echo htmlspecialchars($corTexto, ENT_QUOTES, 'UTF-8'); ?>;">
                        R$ <?php echo number_format((float) $item['preco'], 2, ',', '.'); ?>
                    </span>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <p class="folha-rodape">Cardápio gerado via S.O.P.A.</p>
    </div>

</body>

</html>
