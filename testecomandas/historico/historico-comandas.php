<?php

declare(strict_types=1);

session_start();

if (!isset($_SESSION['id'])) {
    header('Location: ../../auth/index.php');
    exit;
}

include '../../configs/conexao.php';

$usuarioId = (int) ($_SESSION['id'] ?? 0);

$stmtCardapios = $conn->prepare(
    'SELECT id, nome_restaurante FROM cardapios WHERE usuario_id = ? ORDER BY nome_restaurante'
);
$stmtCardapios->bind_param('i', $usuarioId);
$stmtCardapios->execute();
$cardapios = $stmtCardapios->get_result()->fetch_all(MYSQLI_ASSOC);
$stmtCardapios->close();

$cardapioId = isset($_GET['cardapio_id']) ? (int) $_GET['cardapio_id'] : 0;
if ($cardapioId <= 0 && !empty($cardapios)) {
    $cardapioId = (int) $cardapios[0]['id'];
}

$dataInicio = isset($_GET['data_inicio']) && $_GET['data_inicio'] !== '' ? $_GET['data_inicio'] : null;
$dataFim = isset($_GET['data_fim']) && $_GET['data_fim'] !== '' ? $_GET['data_fim'] : null;

$condicaoCardapio = '';
$condicaoData = '';
$params = [$usuarioId];
$types = 'i';

if ($cardapioId > 0) {
    $condicaoCardapio = ' AND c.cardapio_id = ?';
    $params[] = $cardapioId;
    $types .= 'i';
}

if ($dataInicio) {
    $condicaoData .= ' AND DATE(c.data_fechamento) >= ?';
    $params[] = $dataInicio;
    $types .= 's';
}

if ($dataFim) {
    $condicaoData .= ' AND DATE(c.data_fechamento) <= ?';
    $params[] = $dataFim;
    $types .= 's';
}

$stmtHistorico = $conn->prepare(
    'SELECT c.id, c.status, c.total, c.data_abertura, c.data_fechamento,
            m.numero AS numero_mesa,
            cp.nome_restaurante,
            GROUP_CONCAT(DISTINCT CASE
                WHEN p.observacao IS NOT NULL AND TRIM(p.observacao) <> "" THEN CONCAT(p.forma_pagamento, " - ", p.observacao)
                ELSE p.forma_pagamento
            END ORDER BY p.forma_pagamento SEPARATOR ", ") AS formas_pagamento
     FROM comandas c
     INNER JOIN mesas m ON m.id = c.mesa_id
     INNER JOIN cardapios cp ON cp.id = c.cardapio_id
     LEFT JOIN pagamentos p ON p.comanda_id = c.id
     WHERE c.usuario_id = ?' . $condicaoCardapio . $condicaoData . '
     GROUP BY c.id, c.status, c.total, c.data_abertura, c.data_fechamento, m.numero, cp.nome_restaurante
     ORDER BY c.data_fechamento DESC, c.data_abertura DESC, c.id DESC'
);

$stmtHistorico->bind_param($types, ...$params);
$stmtHistorico->execute();
$historico = $stmtHistorico->get_result()->fetch_all(MYSQLI_ASSOC);
$stmtHistorico->close();

$valorTotalFechado = 0.0;
foreach ($historico as $item) {
    if ((string) $item['status'] === 'FECHADA') {
        $valorTotalFechado += (float) $item['total'];
    }
}

$aviso = $_GET['aviso'] ?? '';
$mensagemAviso = '';
if ($aviso === 'historico_limpo') {
    $mensagemAviso = 'Histórico limpo com sucesso.';
} elseif ($aviso === 'erro_limpeza') {
    $mensagemAviso = 'Não foi possível limpar o histórico. Tente novamente.';
} elseif ($aviso === 'cardapio_invalido') {
    $mensagemAviso = 'Cardápio inválido para esta ação.';
}
?>
<!doctype html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Histórico de Comandas — S.O.P.A.</title>
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
            <a href="../teste_comandas.php">Gerenciar comandas</a>
            <a href="../../auth/logout.php">Sair</a>
        </nav>
    </header>

    <main class="dashboard-shell">
        <div class="dashboard-card comandas-shell">
            <p class="dashboard-eyebrow">Relatório</p>
            <h1>Histórico de comandas</h1>
            <p class="dashboard-text">Acompanhe as comandas fechadas, canceladas e o valor total recebido.</p>

            <?php if (!empty($cardapios)): ?>
                <form method="GET" class="seletor-cardapio">
                    <label for="cardapio_id">Filtrar por cardápio</label>
                    <select name="cardapio_id" id="cardapio_id" onchange="this.form.submit()">
                        <option value="">Todos os cardápios</option>
                        <?php foreach ($cardapios as $c): ?>
                            <option value="<?php echo (int) $c['id']; ?>" <?php echo ((int) $c['id'] === $cardapioId) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($c['nome_restaurante'], ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            <?php endif; ?>

            <form method="GET" class="seletor-cardapio filtro-historico" style="margin-top: 0;">
                <label for="data_inicio">Período</label>
                <input type="date" name="data_inicio" id="data_inicio" value="<?php echo htmlspecialchars((string) ($dataInicio ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                <span>até</span>
                <input type="date" name="data_fim" id="data_fim" value="<?php echo htmlspecialchars((string) ($dataFim ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                <?php if ($cardapioId > 0): ?>
                    <input type="hidden" name="cardapio_id" value="<?php echo (int) $cardapioId; ?>">
                <?php endif; ?>
                <button type="submit" class="btn-mini">Aplicar</button>
                <?php if ($dataInicio || $dataFim): ?>
                    <a class="btn-mini danger" href="historico-comandas.php<?php echo $cardapioId ? '?cardapio_id=' . $cardapioId : ''; ?>">Limpar</a>
                <?php endif; ?>
            </form>

            <?php if ($mensagemAviso !== ''): ?>
                <div class="comanda-aviso comanda-aviso-sucesso"><?php echo htmlspecialchars($mensagemAviso, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>

            <div class="comanda-topo">
                <div>
                    <p><strong>Comandas fechadas:</strong> <?php echo count(array_filter($historico, fn($c) => (string) $c['status'] === 'FECHADA')); ?></p>
                    <p><strong>Valor total fechado:</strong> R$ <?php echo number_format($valorTotalFechado, 2, ',', '.'); ?></p>
                </div>
            </div>

            <form method="POST" action="limparHistoricoComandas.php" onsubmit="return confirm('Deseja realmente limpar o histórico de comandas? Esta ação remove registros fechados e cancelados.');">
                <?php if ($cardapioId > 0): ?>
                    <input type="hidden" name="cardapio_id" value="<?php echo (int) $cardapioId; ?>" />
                <?php endif; ?>
                <?php if ($dataInicio): ?>
                    <input type="hidden" name="data_inicio" value="<?php echo htmlspecialchars($dataInicio, ENT_QUOTES, 'UTF-8'); ?>" />
                <?php endif; ?>
                <?php if ($dataFim): ?>
                    <input type="hidden" name="data_fim" value="<?php echo htmlspecialchars($dataFim, ENT_QUOTES, 'UTF-8'); ?>" />
                <?php endif; ?>
                <button type="submit" class="btn-mini danger">Limpar histórico</button>
            </form>

            <?php if (empty($historico)): ?>
                <p class="cardapio-vazio">Ainda não há movimentação para este filtro.</p>
            <?php else: ?>
                <div class="lista-itens-comanda">
                    <?php foreach ($historico as $registro): ?>
                        <div class="item-comanda-row">
                            <div>
                                <strong>Comanda #<?php echo (int) $registro['id']; ?> · Mesa <?php echo (int) $registro['numero_mesa']; ?></strong>
                                <span><?php echo htmlspecialchars($registro['nome_restaurante'], ENT_QUOTES, 'UTF-8'); ?></span>
                                <small>
                                    Estado: <?php echo htmlspecialchars($registro['status'], ENT_QUOTES, 'UTF-8'); ?>
                                    • Aberta: <?php echo !empty($registro['data_abertura']) ? date('d/m/Y H:i', strtotime($registro['data_abertura'])) : '-'; ?>
                                    • Fechamento: <?php echo !empty($registro['data_fechamento']) ? date('d/m/Y H:i', strtotime($registro['data_fechamento'])) : '-'; ?>
                                    • Pagamento: <?php echo !empty($registro['formas_pagamento']) ? htmlspecialchars($registro['formas_pagamento'], ENT_QUOTES, 'UTF-8') : '—'; ?>
                                </small>
                            </div>
                            <span>R$ <?php echo number_format((float) $registro['total'], 2, ',', '.'); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="dashboard-actions">
                <a class="btn-pill" href="../teste_comandas.php<?php echo $cardapioId ? '?cardapio_id=' . $cardapioId : ''; ?>">Voltar</a>
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
