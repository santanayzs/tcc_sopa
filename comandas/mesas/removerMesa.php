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
    'SELECT id, status, ativo FROM mesas WHERE id = ? AND usuario_id = ? AND ativo = 1 LIMIT 1'
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
    header('Location: ../comandas.php?aviso=mesa_ativa');
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
            header('Location: ../comandas.php?aviso=mesa_ativa');
            exit;
        }
        $stmtPagamentos->close();

        $stmtItens = $conn->prepare('DELETE FROM itens_comanda WHERE comanda_id = ?');
        $stmtItens->bind_param('i', $comandaId);
        if (!$stmtItens->execute()) {
            $stmtItens->close();
            $conn->rollback();
            header('Location: ../comandas.php?aviso=mesa_ativa');
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
        header('Location: ../comandas.php?aviso=mesa_ativa');
        exit;
    }
    $stmtDeleteComandas->close();
}

$stmtDelete = $conn->prepare(
    'DELETE FROM mesas WHERE id = ? AND usuario_id = ? AND ativo = 1'
);
$stmtDelete->bind_param('ii', $mesaId, $usuarioId);

if (!$stmtDelete->execute()) {
    $stmtDelete->close();
    $conn->rollback();
    header('Location: ../comandas.php?aviso=mesa_ativa');
    exit;
}

if ($stmtDelete->affected_rows <= 0) {
    $stmtDelete->close();
    $conn->rollback();
    header('Location: ../comandas.php?aviso=naoencontrado');
    exit;
}

$stmtDelete->close();
$conn->commit();

header('Location: ../comandas.php?aviso=mesa_removida');
exit;
