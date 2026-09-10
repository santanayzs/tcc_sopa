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
    header('Location: ../teste_comandas.php');
    exit;
}

$comandaId = isset($_POST['comanda_id']) ? (int) $_POST['comanda_id'] : 0;
$cardapioId = isset($_POST['cardapio_id']) ? (int) $_POST['cardapio_id'] : 0;
$formaPagamento = $_POST['forma_pagamento'] ?? '';
$valorPago = isset($_POST['valor_pago']) ? (float) $_POST['valor_pago'] : 0.0;
$observacao = trim((string) ($_POST['observacao_pagamento'] ?? ''));

if ($comandaId <= 0 || $cardapioId <= 0 || !in_array($formaPagamento, ['DINHEIRO', 'PIX', 'DEBITO', 'CREDITO', 'OUTRO'], true) || $valorPago <= 0) {
    header('Location: comanda-detalhe.php?id=' . $comandaId . '&aviso=erro_item');
    exit;
}

$stmtComanda = $conn->prepare(
    'SELECT c.id, c.cardapio_id, c.usuario_id, c.total, c.mesa_id
     FROM comandas c
     WHERE c.id = ? AND c.usuario_id = ? AND c.status = "ABERTA"
     LIMIT 1'
);
$stmtComanda->bind_param('ii', $comandaId, $usuarioId);
$stmtComanda->execute();
$comanda = $stmtComanda->get_result()->fetch_assoc();
$stmtComanda->close();

if (!$comanda || (int) $comanda['cardapio_id'] !== $cardapioId) {
    header('Location: ../teste_comandas.php?aviso=naoencontrado');
    exit;
}

$valorTotal = (float) $comanda['total'];
if ($valorPago < $valorTotal) {
    $stmtComanda = null;
    header('Location: comanda-detalhe.php?id=' . $comandaId . '&aviso=erro_item');
    exit;
}

$conn->begin_transaction();

$stmtPagamento = $conn->prepare(
    'INSERT INTO pagamentos (comanda_id, forma_pagamento, valor, observacao)
     VALUES (?, ?, ?, ?)'
);
$stmtPagamento->bind_param('isds', $comandaId, $formaPagamento, $valorPago, $observacao);

if (!$stmtPagamento->execute()) {
    $stmtPagamento->close();
    $conn->rollback();
    header('Location: comanda-detalhe.php?id=' . $comandaId . '&aviso=erro_item');
    exit;
}

$stmtPagamento->close();

$stmtAtualizaComanda = $conn->prepare(
    'UPDATE comandas SET status = "FECHADA", data_fechamento = NOW(), total = ? WHERE id = ?'
);
$stmtAtualizaComanda->bind_param('di', $valorTotal, $comandaId);

if (!$stmtAtualizaComanda->execute()) {
    $stmtAtualizaComanda->close();
    $conn->rollback();
    header('Location: comanda-detalhe.php?id=' . $comandaId . '&aviso=erro_item');
    exit;
}

$stmtAtualizaComanda->close();

$stmtMesa = $conn->prepare(
    'UPDATE mesas SET status = "LIVRE" WHERE id = ? AND ativo = 1'
);
$stmtMesa->bind_param('i', $comanda['mesa_id']);

if (!$stmtMesa->execute()) {
    $stmtMesa->close();
    $conn->rollback();
    header('Location: comanda-detalhe.php?id=' . $comandaId . '&aviso=erro_item');
    exit;
}

$stmtMesa->close();
$conn->commit();

header('Location: ../teste_comandas.php?cardapio_id=' . $cardapioId . '&aviso=comanda_fechada');
exit;
