<?php

declare(strict_types=1);

session_start();

// ── Exige login ────────────────────────────────────────────────────────────
if (!isset($_SESSION['id'])) {
    header('Location: ../../auth/index.php');
    exit;
}

include '../criar-cardapio/conexao.php';

$usuarioId   = (int) $_SESSION['id'];
$nomeUsuario = $_SESSION['nome'] ?? 'Usuário';
$mostrarAviso = isset($_GET['salvo']);
$mostrarAtualizado = isset($_GET['atualizado']);
$mostrarExcluido = isset($_GET['excluido']);
$mostrarPersonalizado = isset($_GET['personalizado']);
$mostrarErroNaoEncontrado = ($_GET['erro'] ?? '') === 'naoencontrado';

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
        'SELECT nome, preco, disponivel, imagem
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

    <link rel="stylesheet" href="../../CSS/style.css" />
    <link rel="stylesheet" href="style-ver-cardapio.css" />

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
            <a href="../criar-cardapio/criar-cardapio.php">Criar Cardápio</a>
            <a href="../../auth/logout.php">Sair</a>
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

            <?php if ($mostrarAtualizado): ?>
                <p class="aviso-sucesso">Cardápio atualizado com sucesso!</p>
            <?php endif; ?>

            <?php if ($mostrarExcluido): ?>
                <p class="aviso-sucesso">Cardápio excluído com sucesso!</p>
            <?php endif; ?>

            <?php if ($mostrarPersonalizado): ?>
                <p class="aviso-sucesso">Personalização salva com sucesso!</p>
            <?php endif; ?>

            <?php if ($mostrarErroNaoEncontrado): ?>
                <p style="background:#f8e5e5;color:#9b1c1c;padding:10px 14px;border-radius:10px;font-weight:600;">
                    Cardápio não encontrado (ou você não tem permissão para acessá-lo).
                </p>
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
                                            <div class="cardapio-item-esquerda">
                                                <?php if (!empty($item['imagem'])): ?>
                                                    <img class="cardapio-item-thumb"
                                                         src="../../uploads/itens/<?php echo htmlspecialchars($item['imagem'], ENT_QUOTES, 'UTF-8'); ?>"
                                                         alt="">
                                                <?php endif; ?>
                                                <span class="cardapio-item-nome">
                                                    <?php echo htmlspecialchars($item['nome'], ENT_QUOTES, 'UTF-8'); ?>
                                                    <?php if (!$item['disponivel']): ?>
                                                        <span class="cardapio-item-badge">Indisponível</span>
                                                    <?php endif; ?>
                                                </span>
                                            </div>
                                            <span class="cardapio-item-preco">
                                                R$ <?php echo number_format((float) $item['preco'], 2, ',', '.'); ?>
                                            </span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <div class="cardapio-acoes">
                                <a class="btn-mini"
                                   href="../editar-cardapio/editar-cardapio.php?id=<?php echo (int) $cardapio['id']; ?>">
                                    Editar
                                </a>
                                <a class="btn-mini"
                                   href="qrcode-cardapio.php?id=<?php echo (int) $cardapio['id']; ?>">
                                    QR Code
                                </a>
                                <a class="btn-mini"
                                   href="../personalizar-cardapio/personalizar-cardapio.php?id=<?php echo (int) $cardapio['id']; ?>">
                                    Personalizar
                                </a>
                                <a class="btn-mini"
                                   href="../imprimir-cardapio/imprimir-cardapio.php?id=<?php echo (int) $cardapio['id']; ?>"
                                   target="_blank">
                                    Exportar PDF
                                </a>
                                <form action="../deletar-cardapio/deletarCardapio.php" method="POST"
                                      onsubmit="return confirm('Tem certeza que deseja excluir o cardápio &quot;<?php echo htmlspecialchars(addslashes($cardapio['nome_restaurante']), ENT_QUOTES, 'UTF-8'); ?>&quot;? Essa ação não pode ser desfeita.');">
                                    <input type="hidden" name="id" value="<?php echo (int) $cardapio['id']; ?>">
                                    <button type="submit" class="btn-mini excluir">Excluir</button>
                                </form>
                            </div>
                        </section>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="dashboard-actions">
                <a class="btn-pill" href="../criar-cardapio/criar-cardapio.php">+ Novo cardápio</a>
                <a class="btn-pill" href="../../dashboard/index.php">Voltar ao painel</a>
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
