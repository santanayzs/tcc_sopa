<?php

declare(strict_types=1);

session_start();

// ── Exige login ────────────────────────────────────────────────────────────
if (!isset($_SESSION['id'])) {
    header('Location: ../auth/index.php');
    exit;
}

include '../criar-cardapio/conexao.php';

$usuarioId   = (int) $_SESSION['id'];
$nomeUsuario = $_SESSION['nome'] ?? 'Usuário';
$mostrarAviso = isset($_GET['salvo']);

// ── Busca os cardápios do usuário logado ──────────────────────────────────
$cardapios = [];

$stmt = $conexao->prepare(
    'SELECT id, nome_restaurante, categoria, data_criacao
     FROM cardapios
     WHERE usuario_id = ?
     ORDER BY data_criacao DESC'
);
$stmt->bind_param('i', $usuarioId);
$stmt->execute();
$resultado = $stmt->get_result();

while ($cardapio = $resultado->fetch_assoc()) {
    // ── Busca os itens de cada cardápio ───────────────────────────────────────
    $stmtItens = $conexao->prepare(
        'SELECT nome, preco, disponivel
         FROM itens_cardapio
         WHERE cardapio_id = ?
         ORDER BY id'
    );
    $stmtItens->bind_param('i', $cardapio['id']);
    $stmtItens->execute();
    $cardapio['itens'] = $stmtItens->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmtItens->close();

    $cardapios[] = $cardapio;
}
$stmt->close();
?>
<!doctype html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Meus Cardápios — S.O.P.A.</title>

    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700;800&family=Inter:wght@400;500;600&family=Cormorant+Garamond:wght@500;600&display=swap"
        rel="stylesheet" />

    <link rel="stylesheet" href="../CSS/style.css" />

    <style>
        .cardapios-lista {
            display: flex;
            flex-direction: column;
            gap: 24px;
            margin-top: 32px;
        }

        .cardapio-bloco {
            background: var(--card-bg);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: var(--radius-md);
            padding: 26px 28px;
        }

        .cardapio-bloco-topo {
            display: flex;
            flex-wrap: wrap;
            align-items: baseline;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 4px;
        }

        .cardapio-bloco h2 {
            margin: 0;
            font-family: var(--font-display);
            font-size: 1.3rem;
            color: var(--cream-1);
        }

        .cardapio-categoria {
            font-size: 0.82rem;
            font-weight: 600;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            color: var(--sage);
        }

        .cardapio-data {
            font-size: 0.8rem;
            color: var(--cream-2);
            margin: 4px 0 18px;
        }

        .cardapio-itens {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .cardapio-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            padding: 10px 14px;
            background: rgb(255, 252, 252);
            border-radius: 10px;
        }

        .cardapio-item-nome {
            font-weight: 500;
        }

        .cardapio-item-preco {
            font-family: var(--font-display);
            font-weight: 700;
            color: var(--cream-1);
            white-space: nowrap;
            color: #243935;
        }

        .cardapio-item.indisponivel {
            opacity: 0.5;
        }

        .cardapio-item-badge {
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #ff9b9b;
            margin-left: 8px;
        }

        .cardapio-vazio {
            font-size: 0.9rem;
            color: var(--cream-2);
            font-style: italic;
        }

        .aviso-sucesso {
            background: #e7f6ee;
            color: #1f6d45;
            padding: 12px 16px;
            border-radius: 10px;
            font-weight: 600;
            margin-bottom: 6px;
        }

        .estado-vazio {
            text-align: center;
            padding: 40px 20px;
        }

        .estado-vazio p {
            color: var(--green-800);
            margin-bottom: 20px;
        }
    </style>
</head>

<body class="dashboard-page">
    <header class="site-header">
        <a href="../index.html" class="logo">
            <span class="logo-badge">S</span>
            <span class="logo-word">S.O.P.A.</span>
        </a>

        <nav class="main-nav">
            <a href="../index.html">Início</a>
            <a href="../dashboard/index.php">Painel</a>
            <a href="../criar-cardapio/criar-cardapio.php">Criar Cardápio</a>
            <a href="../auth/logout.php">Sair</a>
        </nav>
    </header>

    <main class="dashboard-shell">
        <div class="dashboard-card">
            <p class="dashboard-eyebrow">Área do Restaurante</p>
            <h1>Meus cardápios</h1>
            <p class="dashboard-text">
                Aqui estão os cardápios cadastrados por <?php echo htmlspecialchars($nomeUsuario, ENT_QUOTES, 'UTF-8'); ?>.
            </p>

            <?php if ($mostrarAviso): ?>
                <p class="aviso-sucesso">Cardápio salvo com sucesso!</p>
            <?php endif; ?>

            <?php if (empty($cardapios)): ?>
                <div class="estado-vazio">
                    <p>Você ainda não criou nenhum cardápio.</p>
                    <a class="btn-pill" href="../criar-cardapio/criar-cardapio.php">Criar meu primeiro cardápio</a>
                </div>
            <?php else: ?>
                <div class="cardapios-lista">
                    <?php foreach ($cardapios as $cardapio): ?>
                        <section class="cardapio-bloco">
                            <div class="cardapio-bloco-topo">
                                <h2><?php echo htmlspecialchars($cardapio['nome_restaurante'], ENT_QUOTES, 'UTF-8'); ?></h2>
                                <?php if (!empty($cardapio['categoria'])): ?>
                                    <span class="cardapio-categoria">
                                        <?php echo htmlspecialchars($cardapio['categoria'], ENT_QUOTES, 'UTF-8'); ?>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <p class="cardapio-data">
                                Criado em <?php echo date('d/m/Y \à\s H:i', strtotime($cardapio['data_criacao'])); ?>
                            </p>

                            <?php if (empty($cardapio['itens'])): ?>
                                <p class="cardapio-vazio">Nenhum item cadastrado neste cardápio.</p>
                            <?php else: ?>
                                <div class="cardapio-itens">
                                    <?php foreach ($cardapio['itens'] as $item): ?>
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
                        </section>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="dashboard-actions">
                <a class="btn-pill" href="../criar-cardapio/criar-cardapio.php">+ Novo cardápio</a>
                <a class="btn-pill" href="../dashboard/index.php">Voltar ao painel</a>
            </div>
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
