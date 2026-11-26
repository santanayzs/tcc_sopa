<?php

declare(strict_types=1);

require_once __DIR__ . '/../configs/session_helper.php';
require_once __DIR__ . '/../configs/perfis.php';
exigirAcessoEstabelecimento();

if (!isset($_SESSION['id'])) {
    header('Location: ../auth/index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: pedidos-delivery.php');
    exit;
}

$usuarioId = (int) $_SESSION['id'];
$pedidoId = (int) ($_POST['pedido_id'] ?? 0);
$status = $_POST['status'] ?? '';
$tokenEnviado = $_POST['csrf_token'] ?? '';
$tokenSessao = $_SESSION['delivery_order_csrf'] ?? '';
$statusPermitidos = ['RECEBIDO', 'CONFIRMADO', 'EM_PREPARO', 'SAIU_PARA_ENTREGA', 'ENTREGUE', 'CANCELADO'];

if (
    $pedidoId <= 0
    || !is_string($status)
    || !in_array($status, $statusPermitidos, true)
    || !is_string($tokenEnviado)
    || !is_string($tokenSessao)
    || $tokenSessao === ''
    || !hash_equals($tokenSessao, $tokenEnviado)
) {
    header('Location: pedidos-delivery.php');
    exit;
}

include __DIR__ . '/../CRUD-Cardapio/criar-cardapio/conexao.php';
garantirTabelasPedidosDelivery($conexao);

$stmtExiste = $conexao->prepare(
    'SELECT id FROM pedidos_delivery WHERE id = ? AND usuario_id = ? LIMIT 1'
);
$stmtExiste->bind_param('ii', $pedidoId, $usuarioId);
$stmtExiste->execute();
$pedidoExiste = $stmtExiste->get_result()->num_rows === 1;
$stmtExiste->close();

if (!$pedidoExiste) {
    header('Location: pedidos-delivery.php?resultado=erro');
    exit;
}

$stmtAtualizar = $conexao->prepare(
    'UPDATE pedidos_delivery SET status = ? WHERE id = ? AND usuario_id = ?'
);
$stmtAtualizar->bind_param('sii', $status, $pedidoId, $usuarioId);
$atualizado = $stmtAtualizar->execute();
$stmtAtualizar->close();

header('Location: pedidos-delivery.php' . ($atualizado ? '?resultado=atualizado' : '?resultado=erro'));
exit;