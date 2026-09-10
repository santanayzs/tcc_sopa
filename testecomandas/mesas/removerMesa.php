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
    'SELECT m.id, m.status, m.ativo
     FROM mesas m
     INNER JOIN cardapios c ON c.id = m.cardapio_id
     WHERE m.id = ? AND m.cardapio_id = ? AND c.usuario_id = ? AND m.ativo = 1
     LIMIT 1'
);
$stmtMesa->bind_param('iii', $mesaId, $cardapioId, $usuarioId);
$stmtMesa->execute();
$mesa = $stmtMesa->get_result()->fetch_assoc();
$stmtMesa->close();

if (!$mesa) {
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
    header('Location: ../teste_comandas.php?cardapio_id=' . $cardapioId . '&aviso=mesa_ativa');
    exit;
}

$stmtComandaAberta->close();

$conn->begin_transaction();

$stmtHistorico = $conn->prepare(
    'SELECT id FROM comandas WHERE mesa_id = ? AND status IN ("FECHADA", "CANCELADA")'
);
$stmtHistorico->bind_param('i', $mesaId);
$stmtHistorico->execute();
$historico = $stmtHistorico->get_result()->fetch_all(MYSQLI_ASSOC);
$stmtHistorico->close();

if (!empty($historico)) {
    foreach ($historico as $comanda) {
        $comandaId = (int) $comanda['id'];

        $stmtPagamentos = $conn->prepare('DELETE FROM pagamentos WHERE comanda_id = ?');
        $stmtPagamentos->bind_param('i', $comandaId);
        if (!$stmtPagamentos->execute()) {
            $stmtPagamentos->close();
            $conn->rollback();
            header('Location: ../teste_comandas.php?cardapio_id=' . $cardapioId . '&aviso=mesa_ativa');
            exit;
        }
        $stmtPagamentos->close();

        $stmtItens = $conn->prepare('DELETE FROM itens_comanda WHERE comanda_id = ?');
        $stmtItens->bind_param('i', $comandaId);
        if (!$stmtItens->execute()) {
            $stmtItens->close();
            $conn->rollback();
            header('Location: ../teste_comandas.php?cardapio_id=' . $cardapioId . '&aviso=mesa_ativa');
            exit;
        }
        $stmtItens->close();
    }

    $stmtDeleteComandas = $conn->prepare(
        'DELETE FROM comandas WHERE mesa_id = ? AND status IN ("FECHADA", "CANCELADA")'
    );
    $stmtDeleteComandas->bind_param('i', $mesaId);
    if (!$stmtDeleteComandas->execute()) {
        $stmtDeleteComandas->close();
        $conn->rollback();
        header('Location: ../teste_comandas.php?cardapio_id=' . $cardapioId . '&aviso=mesa_ativa');
        exit;
    }
    $stmtDeleteComandas->close();
}

$stmtDelete = $conn->prepare(
    'DELETE FROM mesas WHERE id = ? AND cardapio_id = ? AND ativo = 1'
);
$stmtDelete->bind_param('ii', $mesaId, $cardapioId);

if (!$stmtDelete->execute()) {
    $stmtDelete->close();
    $conn->rollback();
    header('Location: ../teste_comandas.php?cardapio_id=' . $cardapioId . '&aviso=mesa_ativa');
    exit;
}

if ($stmtDelete->affected_rows <= 0) {
    $stmtDelete->close();
    $conn->rollback();
    header('Location: ../teste_comandas.php?cardapio_id=' . $cardapioId . '&aviso=naoencontrado');
    exit;
}

$stmtDelete->close();
$conn->commit();

header('Location: ../teste_comandas.php?cardapio_id=' . $cardapioId . '&aviso=mesa_removida');
exit;
