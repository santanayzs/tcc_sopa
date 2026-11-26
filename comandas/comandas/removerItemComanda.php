<?php

declare(strict_types=1);

session_start();
require_once __DIR__ . '/../../configs/perfis.php';

if (!isset($_SESSION['id'])) {
    header('Location: ../../auth/index.php');
    exit;
}

include '../../configs/conexao.php';

$usuarioId = usuarioDonoId($conn);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../comandas.php');
    exit;
}

$comandaId = isset($_POST['comanda_id']) ? (int) $_POST['comanda_id'] : 0;
$linhaItemId = isset($_POST['linha_item_id']) ? (int) $_POST['linha_item_id'] : 0;
$cardapioId = isset($_POST['cardapio_id']) ? (int) $_POST['cardapio_id'] : 0;

$urlDetalhe = 'comanda-detalhe.php?id=' . $comandaId . ($cardapioId > 0 ? '&cardapio_id=' . $cardapioId : '');

if ($comandaId <= 0 || $linhaItemId <= 0) {
    header('Location: ' . $urlDetalhe . '&aviso=erro_item');
    exit;
}

$stmtValidar = $conn->prepare(
    'SELECT ic.id
     FROM itens_comanda ic
     INNER JOIN comandas c ON c.id = ic.comanda_id
     WHERE ic.id = ? AND c.id = ? AND c.usuario_id = ? AND c.status = "ABERTA"
     LIMIT 1'
);
$stmtValidar->bind_param('iii', $linhaItemId, $comandaId, $usuarioId);
$stmtValidar->execute();
$result = $stmtValidar->get_result();

if ($result->num_rows === 0) {
    $stmtValidar->close();
    header('Location: ' . $urlDetalhe . '&aviso=erro_item');
    exit;
}

$stmtValidar->close();

$conn->begin_transaction();

$stmtDelete = $conn->prepare('DELETE FROM itens_comanda WHERE id = ?');
$stmtDelete->bind_param('i', $linhaItemId);

if (!$stmtDelete->execute()) {
    $stmtDelete->close();
    $conn->rollback();
    header('Location: ' . $urlDetalhe . '&aviso=erro_item');
    exit;
}

$stmtDelete->close();

$stmtTotal = $conn->prepare(
    'SELECT COALESCE(SUM(quantidade * preco_unitario), 0) AS total
     FROM itens_comanda
     WHERE comanda_id = ?'
);
$stmtTotal->bind_param('i', $comandaId);
$stmtTotal->execute();
$total = $stmtTotal->get_result()->fetch_assoc()['total'] ?? 0;
$stmtTotal->close();

$stmtAtualiza = $conn->prepare('UPDATE comandas SET total = ? WHERE id = ?');
$stmtAtualiza->bind_param('di', $total, $comandaId);

if (!$stmtAtualiza->execute()) {
    $stmtAtualiza->close();
    $conn->rollback();
    header('Location: ' . $urlDetalhe . '&aviso=erro_item');
    exit;
}

$stmtAtualiza->close();
$conn->commit();

header('Location: ' . $urlDetalhe . '&aviso=item_removido');
exit;
