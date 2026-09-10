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
$itemId = isset($_POST['item_id']) ? (int) $_POST['item_id'] : 0;
$quantidade = isset($_POST['quantidade']) ? max(1, (int) $_POST['quantidade']) : 1;
$observacao = trim((string) ($_POST['observacao'] ?? ''));

if ($comandaId <= 0 || $cardapioId <= 0 || $itemId <= 0 || $quantidade <= 0) {
    header('Location: comanda-detalhe.php?id=' . $comandaId . '&aviso=erro_item');
    exit;
}

$stmtComanda = $conn->prepare(
    'SELECT c.id, c.cardapio_id, c.usuario_id, c.total
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

$stmtItem = $conn->prepare(
    'SELECT id, preco, cardapio_id, nome
     FROM itens_cardapio
     WHERE id = ? AND cardapio_id = ? AND disponivel = 1
     LIMIT 1'
);
$stmtItem->bind_param('ii', $itemId, $cardapioId);
$stmtItem->execute();
$item = $stmtItem->get_result()->fetch_assoc();
$stmtItem->close();

if (!$item) {
    header('Location: comanda-detalhe.php?id=' . $comandaId . '&aviso=erro_item');
    exit;
}

$conn->begin_transaction();

$stmtInsert = $conn->prepare(
    'INSERT INTO itens_comanda (comanda_id, item_cardapio_id, quantidade, preco_unitario, observacao)
     VALUES (?, ?, ?, ?, ?)'
);
$stmtInsert->bind_param('iiids', $comandaId, $itemId, $quantidade, $item['preco'], $observacao);

if (!$stmtInsert->execute()) {
    $stmtInsert->close();
    $conn->rollback();
    header('Location: comanda-detalhe.php?id=' . $comandaId . '&aviso=erro_item');
    exit;
}

$stmtInsert->close();

$stmtTotal = $conn->prepare(
    'SELECT COALESCE(SUM(quantidade * preco_unitario), 0) AS total
     FROM itens_comanda
     WHERE comanda_id = ?'
);
$stmtTotal->bind_param('i', $comandaId);
$stmtTotal->execute();
$total = $stmtTotal->get_result()->fetch_assoc()['total'] ?? 0;
$stmtTotal->close();

$stmtAtualiza = $conn->prepare(
    'UPDATE comandas SET total = ? WHERE id = ?'
);
$stmtAtualiza->bind_param('di', $total, $comandaId);

if (!$stmtAtualiza->execute()) {
    $stmtAtualiza->close();
    $conn->rollback();
    header('Location: comanda-detalhe.php?id=' . $comandaId . '&aviso=erro_item');
    exit;
}

$stmtAtualiza->close();
$conn->commit();

header('Location: comanda-detalhe.php?id=' . $comandaId . '&aviso=item_adicionado');
exit;
