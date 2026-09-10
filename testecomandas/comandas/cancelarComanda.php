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

if ($comandaId <= 0 || $cardapioId <= 0) {
    header('Location: ../teste_comandas.php?cardapio_id=' . $cardapioId . '&aviso=naoencontrado');
    exit;
}

$stmtValidar = $conn->prepare(
    'SELECT c.id, c.mesa_id
     FROM comandas c
     WHERE c.id = ? AND c.usuario_id = ? AND c.status = "ABERTA"
     LIMIT 1'
);
$stmtValidar->bind_param('ii', $comandaId, $usuarioId);
$stmtValidar->execute();
$comanda = $stmtValidar->get_result()->fetch_assoc();
$stmtValidar->close();

if (!$comanda) {
    header('Location: ../teste_comandas.php?cardapio_id=' . $cardapioId . '&aviso=naoencontrado');
    exit;
}

$conn->begin_transaction();

$stmtDeleteItens = $conn->prepare('DELETE FROM itens_comanda WHERE comanda_id = ?');
$stmtDeleteItens->bind_param('i', $comandaId);
if (!$stmtDeleteItens->execute()) {
    $stmtDeleteItens->close();
    $conn->rollback();
    header('Location: comanda-detalhe.php?id=' . $comandaId . '&aviso=erro_item');
    exit;
}
$stmtDeleteItens->close();

$stmtAtualiza = $conn->prepare(
    'UPDATE comandas SET status = "CANCELADA", data_fechamento = NOW(), total = 0 WHERE id = ?'
);
$stmtAtualiza->bind_param('i', $comandaId);
if (!$stmtAtualiza->execute()) {
    $stmtAtualiza->close();
    $conn->rollback();
    header('Location: comanda-detalhe.php?id=' . $comandaId . '&aviso=erro_item');
    exit;
}
$stmtAtualiza->close();

$stmtMesa = $conn->prepare('UPDATE mesas SET status = "LIVRE" WHERE id = ? AND ativo = 1');
$stmtMesa->bind_param('i', $comanda['mesa_id']);
if (!$stmtMesa->execute()) {
    $stmtMesa->close();
    $conn->rollback();
    header('Location: comanda-detalhe.php?id=' . $comandaId . '&aviso=erro_item');
    exit;
}
$stmtMesa->close();

$conn->commit();

header('Location: ../teste_comandas.php?cardapio_id=' . $cardapioId . '&aviso=comanda_cancelada');
exit;
