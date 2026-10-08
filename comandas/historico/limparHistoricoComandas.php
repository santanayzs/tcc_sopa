<?php

declare(strict_types=1);

session_start();

if (!isset($_SESSION['id'])) {
    header('Location: ../../auth/index.php');
    exit;
}

include '../../configs/conexao.php';

$usuarioId = (int) ($_SESSION['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: historico-comandas.php');
    exit;
}

$cardapioId = isset($_POST['cardapio_id']) ? (int) $_POST['cardapio_id'] : 0;
$dataInicio = isset($_POST['data_inicio']) && $_POST['data_inicio'] !== '' ? trim((string) $_POST['data_inicio']) : null;
$dataFim = isset($_POST['data_fim']) && $_POST['data_fim'] !== '' ? trim((string) $_POST['data_fim']) : null;

if ($cardapioId > 0) {
    $stmtCardapio = $conn->prepare(
        'SELECT id FROM cardapios WHERE id = ? AND usuario_id = ? LIMIT 1'
    );
    $stmtCardapio->bind_param('ii', $cardapioId, $usuarioId);
    $stmtCardapio->execute();
    $cardapio = $stmtCardapio->get_result()->fetch_assoc();
    $stmtCardapio->close();

    if (!$cardapio) {
        header('Location: historico-comandas.php?aviso=cardapio_invalido');
        exit;
    }
}

$conn->begin_transaction();

$query = 'SELECT id, mesa_id, cardapio_id, status, total, data_abertura, data_fechamento FROM comandas WHERE usuario_id = ? AND status IN ("FECHADA", "CANCELADA")';
$params = [$usuarioId];
$types = 'i';

if ($cardapioId > 0) {
    $query .= ' AND cardapio_id = ?';
    $params[] = $cardapioId;
    $types .= 'i';
}

if ($dataInicio) {
    $query .= ' AND DATE(data_fechamento) >= ?';
    $params[] = $dataInicio;
    $types .= 's';
}

if ($dataFim) {
    $query .= ' AND DATE(data_fechamento) <= ?';
    $params[] = $dataFim;
    $types .= 's';
}

$stmtComandas = $conn->prepare($query);
$stmtComandas->bind_param($types, ...$params);
$stmtComandas->execute();
$comandas = $stmtComandas->get_result()->fetch_all(MYSQLI_ASSOC);
$stmtComandas->close();

$stmtLog = $conn->prepare(
    'CREATE TABLE IF NOT EXISTS historico_apagado (
        id INT NOT NULL AUTO_INCREMENT,
        usuario_id INT NOT NULL,
        cardapio_id INT NULL,
        comanda_id INT NOT NULL,
        mesa_id INT NULL,
        status ENUM("ABERTA","FECHADA","CANCELADA") NOT NULL,
        valor_total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        data_abertura DATETIME NOT NULL,
        data_fechamento DATETIME NULL,
        filtro_data_inicio DATE NULL,
        filtro_data_fim DATE NULL,
        apagado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        motivo VARCHAR(120) NOT NULL DEFAULT "limpeza_historico",
        PRIMARY KEY (id),
        KEY idx_historico_apagado_usuario (usuario_id),
        KEY idx_historico_apagado_cardapio (cardapio_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci'
);
$stmtLog->execute();
$stmtLog->close();

foreach ($comandas as $comanda) {
    $comandaId = (int) $comanda['id'];
    $cardapioComanda = isset($comanda['cardapio_id']) ? (int) $comanda['cardapio_id'] : 0;
    $mesaId = isset($comanda['mesa_id']) ? (int) $comanda['mesa_id'] : 0;
    $status = (string) $comanda['status'];
    $valorTotal = (float) $comanda['total'];
    $dataAbertura = $comanda['data_abertura'] ?? null;
    $dataFechamento = $comanda['data_fechamento'] ?? null;

    $stmtLogInsert = $conn->prepare(
        'INSERT INTO historico_apagado (usuario_id, cardapio_id, comanda_id, mesa_id, status, valor_total, data_abertura, data_fechamento, filtro_data_inicio, filtro_data_fim, apagado_em, motivo)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), "limpeza_historico")'
    );
    $stmtLogInsert->bind_param('iiiisdssss', $usuarioId, $cardapioComanda, $comandaId, $mesaId, $status, $valorTotal, $dataAbertura, $dataFechamento, $dataInicio, $dataFim);
    if (!$stmtLogInsert->execute()) {
        $stmtLogInsert->close();
        $conn->rollback();
        header('Location: historico-comandas.php?aviso=erro_limpeza');
        exit;
    }
    $stmtLogInsert->close();

    $stmtPagamentos = $conn->prepare('DELETE FROM pagamentos WHERE comanda_id = ?');
    $stmtPagamentos->bind_param('i', $comandaId);
    if (!$stmtPagamentos->execute()) {
        $stmtPagamentos->close();
        $conn->rollback();
        header('Location: historico-comandas.php?aviso=erro_limpeza');
        exit;
    }
    $stmtPagamentos->close();

    $stmtItens = $conn->prepare('DELETE FROM itens_comanda WHERE comanda_id = ?');
    $stmtItens->bind_param('i', $comandaId);
    if (!$stmtItens->execute()) {
        $stmtItens->close();
        $conn->rollback();
        header('Location: historico-comandas.php?aviso=erro_limpeza');
        exit;
    }
    $stmtItens->close();

    $stmtDelete = $conn->prepare(
        'DELETE FROM comandas WHERE id = ? AND usuario_id = ? AND status IN ("FECHADA", "CANCELADA")'
    );
    $stmtDelete->bind_param('ii', $comandaId, $usuarioId);
    if (!$stmtDelete->execute()) {
        $stmtDelete->close();
        $conn->rollback();
        header('Location: historico-comandas.php?aviso=erro_limpeza');
        exit;
    }
    $stmtDelete->close();
}

$conn->commit();

$contador = $conn->prepare('SELECT COUNT(*) as total FROM comandas WHERE usuario_id = ?' . ($cardapioId > 0 ? ' AND cardapio_id = ?' : ''));
if ($cardapioId > 0) {
    $contador->bind_param('ii', $usuarioId, $cardapioId);
} else {
    $contador->bind_param('i', $usuarioId);
}
$contador->execute();
$totalRestante = $contador->get_result()->fetch_assoc()['total'] ?? 0;
$contador->close();

if ((int) $totalRestante === 0) {
    $conn->query('ALTER TABLE comandas AUTO_INCREMENT = 1');
}

$redirect = 'historico-comandas.php';
$queryString = [];
if ($cardapioId > 0) {
    $queryString[] = 'cardapio_id=' . $cardapioId;
}
if ($dataInicio) {
    $queryString[] = 'data_inicio=' . urlencode($dataInicio);
}
if ($dataFim) {
    $queryString[] = 'data_fim=' . urlencode($dataFim);
}
if (!empty($queryString)) {
    $redirect .= '?' . implode('&', $queryString);
}

header('Location: ' . $redirect . (str_contains($redirect, '?') ? '&' : '?') . 'aviso=historico_limpo');
exit;
