<?php
 
declare(strict_types=1);
 
session_start();
 
// ── Exige login ────────────────────────────────────────────────────────────
if (!isset($_SESSION['id'])) {
    header('Location: ../auth/index.php');
    exit;
}
 
include '../configs/conexao.php';
 
$usuarioId   = (int) $_SESSION['id'];
$nomeUsuario = $_SESSION['nome'] ?? 'Usuário';
 
// ── Cardápios do usuário (uma comanda sempre pertence a um cardápio) ──────
$stmtCardapios = $conn->prepare(
    'SELECT id, nome_restaurante FROM cardapios WHERE usuario_id = ? ORDER BY nome_restaurante'
);
$stmtCardapios->bind_param('i', $usuarioId);
$stmtCardapios->execute();
$cardapios = $stmtCardapios->get_result()->fetch_all(MYSQLI_ASSOC);
$stmtCardapios->close();
 
$cardapioId = (int) ($_GET['cardapio_id'] ?? ($cardapios[0]['id'] ?? 0));
 
$cardapioAtual = null;
foreach ($cardapios as $c) {
    if ((int) $c['id'] === $cardapioId) {
        $cardapioAtual = $c;
        break;
    }
}
 
// ── Mesas do cardápio selecionado, com a comanda aberta (se houver) ───────
$mesas = [];
if ($cardapioAtual) {
    $stmtMesas = $conn->prepare(
        'SELECT m.id, m.numero, m.capacidade, m.status,
                c.id AS comanda_id, c.total, c.data_abertura
         FROM mesas m
         LEFT JOIN comandas c ON c.mesa_id = m.id AND c.status = "ABERTA"
         WHERE m.cardapio_id = ? AND m.ativo = 1
         ORDER BY m.numero'
    );
    $stmtMesas->bind_param('i', $cardapioId);
    $stmtMesas->execute();
    $mesas = $stmtMesas->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmtMesas->close();
}
 
$mensagens = [
    'mesa_criada'       => ['ok',   'Mesa criada com sucesso!'],
    'mesa_removida'     => ['ok',   'Mesa removida com sucesso.'],
    'comanda_fechada'   => ['ok',   'Comanda fechada e pagamento registrado.'],
    'comanda_cancelada' => ['ok',   'Comanda cancelada com sucesso.'],
    'item_adicionado'   => ['ok',   'Item adicionado com sucesso!'],
    'item_removido'     => ['ok',   'Item removido da comanda.'],
    'mesa_ocupada'      => ['erro', 'Essa mesa já tem uma comanda aberta.'],
    'mesa_ativa'        => ['erro', 'Não foi possível remover a mesa porque ela está ativa.'],
    'naoencontrado'     => ['erro', 'Registro não encontrado ou sem permissão.'],
    'campos'            => ['erro', 'Preencha os campos corretamente.'],
    'numero_duplicado'  => ['erro', 'Já existe uma mesa com esse número nesse cardápio.'],
];
$avisoChave = $_GET['aviso'] ?? '';
$aviso = $mensagens[$avisoChave] ?? null;
?>
<!doctype html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Gerenciar Comandas — S.O.P.A.</title>
 
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700;800&family=Inter:wght@400;500;600&family=Cormorant+Garamond:wght@500;600&display=swap"
        rel="stylesheet" />
 
    <link rel="stylesheet" href="../CSS/style.css" />
    <link rel="stylesheet" href="testecomandas.css" />
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
            <a href="../CRUD-Cardapio/ver-cardapio/ver-cardapio.php">Ver Cardápio</a>
            <a href="../auth/logout.php">Sair</a>
        </nav>
    </header>
 
    <main class="dashboard-shell">
        <div class="dashboard-card comandas-shell">
            <p class="dashboard-eyebrow">Área do Garçom · Teste</p>
            <h1>Gerenciar Comandas</h1>
            <p class="dashboard-text">
                Abra comandas por mesa, lance os itens pedidos e feche a conta na hora de pagar.
            </p>
 
            <?php if ($aviso): ?>
                <p class="comanda-aviso comanda-aviso-<?php echo $aviso[0]; ?>">
                    <?php echo htmlspecialchars($aviso[1], ENT_QUOTES, 'UTF-8'); ?>
                </p>
            <?php endif; ?>
 
            <?php if (empty($cardapios)): ?>
                <div class="estado-vazio">
                    <p>Você ainda não criou nenhum cardápio. Crie um cardápio antes de gerenciar comandas.</p>
                    <a class="btn-pill" href="../CRUD-Cardapio/criar-cardapio/criar-cardapio.php">Criar meu primeiro cardápio</a>
                </div>
            <?php else: ?>
 
                <?php if (count($cardapios) > 1): ?>
                    <form method="GET" class="seletor-cardapio" id="formCardapio">
                        <label for="cardapio_id">Cardápio</label>
                        <select name="cardapio_id" id="cardapio_id">
                            <?php foreach ($cardapios as $c): ?>
                                <option value="<?php echo (int) $c['id']; ?>" <?php echo ((int) $c['id'] === $cardapioId) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($c['nome_restaurante'], ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                <?php endif; ?>
 
                <form action="mesas/criarMesa.php" method="POST" class="form-nova-mesa">
                    <input type="hidden" name="cardapio_id" value="<?php echo (int) $cardapioId; ?>">
                    <div class="form-group-inline">
                        <input type="number" name="numero" placeholder="Número da mesa" min="1" required>
                        <input type="number" name="capacidade" placeholder="Capacidade" min="1" value="4">
                        <button type="submit" class="btn-mini">+ Nova mesa</button>
                    </div>
                </form>
 
                <?php if (empty($mesas)): ?>
                    <p class="cardapio-vazio">Nenhuma mesa cadastrada ainda para este cardápio. Crie a primeira mesa acima.</p>
                <?php else: ?>
                    <div class="mesas-grid">
                        <?php foreach ($mesas as $mesa): ?>
                            <?php $temComandaAberta = !empty($mesa['comanda_id']); ?>
                            <article class="mesa-card <?php echo $temComandaAberta ? 'ocupada' : strtolower($mesa['status']); ?>">
                                <div class="mesa-card-topo">
                                    <span class="mesa-numero">Mesa <?php echo (int) $mesa['numero']; ?></span>
                                    <span class="mesa-status-badge">
                                        <?php echo $temComandaAberta ? 'Ocupada' : ucfirst(strtolower($mesa['status'])); ?>
                                    </span>
                                </div>
                                <p class="mesa-capacidade"><?php echo (int) $mesa['capacidade']; ?> lugares</p>
 
                                <?php if ($temComandaAberta): ?>
                                    <p class="mesa-comanda-info">
                                        Aberta às <?php echo date('H:i', strtotime($mesa['data_abertura'])); ?><br>
                                        Total: <strong>R$ <?php echo number_format((float) $mesa['total'], 2, ',', '.'); ?></strong>
                                    </p>
                                    <a class="btn-mini" href="comandas/comanda-detalhe.php?id=<?php echo (int) $mesa['comanda_id']; ?>">
                                        Ver comanda
                                    </a>
                                <?php elseif (strtolower($mesa['status']) === 'livre'): ?>
                                    <form action="comandas/abrirComanda.php" method="POST">
                                        <input type="hidden" name="mesa_id" value="<?php echo (int) $mesa['id']; ?>">
                                        <input type="hidden" name="cardapio_id" value="<?php echo (int) $cardapioId; ?>">
                                        <button type="submit" class="btn-mini abrir">Abrir comanda</button>
                                    </form>

                                    <form action="mesas/removerMesa.php" method="POST" style="margin-top: 0.5rem;">
                                        <input type="hidden" name="mesa_id" value="<?php echo (int) $mesa['id']; ?>">
                                        <input type="hidden" name="cardapio_id" value="<?php echo (int) $cardapioId; ?>">
                                        <button type="submit" class="btn-mini danger" onclick="return confirm('Deseja remover esta mesa?');">Remover mesa</button>
                                    </form>
                                <?php else: ?>
                                    <p class="mesa-comanda-info">Mesa <?php echo htmlspecialchars(strtolower($mesa['status']), ENT_QUOTES, 'UTF-8'); ?>.</p>
                                <?php endif; ?>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
 
            <?php endif; ?>
 
            <div class="dashboard-actions">
                <a class="btn-pill" href="historico/historico-comandas.php">Histórico de comandas</a>
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
 
    <script src="testecomandas.js"></script>
</body>
</html>