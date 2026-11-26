<?php

declare(strict_types=1);

session_start();
require_once __DIR__ . '/../../configs/perfis.php';

if (!isset($_SESSION['id'])) {
    header('Location: ../../auth/index.php');
    exit;
}

include '../../configs/conexao.php';
garantirMesasCompartilhadas($conn);

$usuarioId = usuarioDonoId($conn);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../comandas.php');
    exit;
}

$mesaId = isset($_POST['mesa_id']) ? (int) $_POST['mesa_id'] : 0;

if ($mesaId <= 0) {
    header('Location: ../comandas.php?aviso=campos');
    exit;
}

$stmtMesa = $conn->prepare(
    'SELECT id, status FROM mesas WHERE id = ? AND usuario_id = ? AND ativo = 1 LIMIT 1'
);
$stmtMesa->bind_param('ii', $mesaId, $usuarioId);
$stmtMesa->execute();
$mesa = $stmtMesa->get_result()->fetch_assoc();
$stmtMesa->close();

if (!$mesa) {
    header('Location: ../comandas.php?aviso=naoencontrado');
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
    header('Location: ../comandas.php?aviso=mesa_ocupada');
    exit;
}

$stmtComandaAberta->close();

$conn->begin_transaction();

$stmtInsert = $conn->prepare(
    'INSERT INTO comandas (cardapio_id, mesa_id, usuario_id, status, total, data_abertura) VALUES (NULL, ?, ?, "ABERTA", 0.00, NOW())'
);
$stmtInsert->bind_param('ii', $mesaId, $usuarioId);

if (!$stmtInsert->execute()) {
    $stmtInsert->close();
    $conn->rollback();
    header('Location: ../comandas.php?aviso=campos');
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
    header('Location: ../comandas.php?aviso=campos');
    exit;
}

$stmtUpdateMesa->close();

$conn->commit();

header('Location: ../comandas.php');
exit;
