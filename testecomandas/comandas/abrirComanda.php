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

$mesaId = isset($_POST['mesa_id']) ? (int) $_POST['mesa_id'] : 0;
$cardapioId = isset($_POST['cardapio_id']) ? (int) $_POST['cardapio_id'] : 0;

if ($mesaId <= 0 || $cardapioId <= 0) {
    header('Location: ../teste_comandas.php?cardapio_id=' . $cardapioId . '&aviso=campos');
    exit;
}

$stmtMesa = $conn->prepare(
    'SELECT m.id, m.cardapio_id, m.status
     FROM mesas m
     INNER JOIN cardapios c ON c.id = m.cardapio_id
     WHERE m.id = ? AND c.usuario_id = ? AND m.ativo = 1 LIMIT 1'
);
$stmtMesa->bind_param('ii', $mesaId, $usuarioId);
$stmtMesa->execute();
$mesa = $stmtMesa->get_result()->fetch_assoc();
$stmtMesa->close();

if (!$mesa) {
    header('Location: ../teste_comandas.php?cardapio_id=' . $cardapioId . '&aviso=naoencontrado');
    exit;
}

if ((int) $mesa['cardapio_id'] !== $cardapioId) {
    header('Location: ../teste_comandas.php?cardapio_id=' . $cardapioId . '&aviso=naoencontrado');
    exit;
}

$stmtComandaAberta = $conn->prepare(
    'SELECT id FROM comandas WHERE mesa_id = ? AND status = "ABERTA" LIMIT 1'
);
$stmtComandaAberta->bind_param('i', $mesaId);
$stmtComandaAberta->execute();
$stmtComandaAberta->store_result();

if ($stmtComandaAberta->num_rows > 0) {
    $stmtComandaAberta->close();
    header('Location: ../teste_comandas.php?cardapio_id=' . $cardapioId . '&aviso=mesa_ocupada');
    exit;
}

$stmtComandaAberta->close();

$conn->begin_transaction();

$stmtInsert = $conn->prepare(
    'INSERT INTO comandas (cardapio_id, mesa_id, usuario_id, status, total, data_abertura) VALUES (?, ?, ?, "ABERTA", 0.00, NOW())'
);
$stmtInsert->bind_param('iii', $cardapioId, $mesaId, $usuarioId);

if (!$stmtInsert->execute()) {
    $stmtInsert->close();
    $conn->rollback();
    header('Location: ../teste_comandas.php?cardapio_id=' . $cardapioId . '&aviso=campos');
    exit;
}

$stmtInsert->close();

$stmtUpdateMesa = $conn->prepare(
    'UPDATE mesas SET status = "OCUPADA" WHERE id = ? AND ativo = 1'
);
$stmtUpdateMesa->bind_param('i', $mesaId);

if (!$stmtUpdateMesa->execute()) {
    $stmtUpdateMesa->close();
    $conn->rollback();
    header('Location: ../teste_comandas.php?cardapio_id=' . $cardapioId . '&aviso=campos');
    exit;
}

$stmtUpdateMesa->close();

$conn->commit();

header('Location: ../teste_comandas.php?cardapio_id=' . $cardapioId);
exit;
