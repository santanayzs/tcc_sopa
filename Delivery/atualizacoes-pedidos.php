<?php

declare(strict_types=1);

session_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$clienteToken = $_SESSION['delivery_customer_token'] ?? '';
if (!is_string($clienteToken) || $clienteToken === '') {
    echo json_encode(['pedidos' => []]);
    exit;
}

try {
    include __DIR__ . '/../CRUD-Cardapio/criar-cardapio/conexao.php';
    $clienteTokenHash = hash('sha256', $clienteToken);
    $stmtPedidos = $conexao->prepare(
        'SELECT id, nome_estabelecimento, status
         FROM pedidos_delivery
         WHERE cliente_token_hash = ?
         ORDER BY criado_em DESC, id DESC'
    );
    $stmtPedidos->bind_param('s', $clienteTokenHash);
    $stmtPedidos->execute();
    $pedidos = $stmtPedidos->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmtPedidos->close();

    echo json_encode(
        ['pedidos' => $pedidos],
        JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
    );
} catch (Throwable $erro) {
    error_log('Falha ao consultar atualizações de pedidos delivery: ' . $erro->getMessage());
    http_response_code(500);
    echo json_encode(['erro' => 'Não foi possível consultar as atualizações dos pedidos.']);
}
