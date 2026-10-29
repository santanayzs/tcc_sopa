<?php

declare(strict_types=1);

session_start();
include '../criar-cardapio/conexao.php';

function retornarAoCardapio(int $cardapioId, string $resultado): never
{
    header('Location: cardapio-delivery.php?' . http_build_query([
        'id' => $cardapioId,
        'pedido' => $resultado,
    ]));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../Delivery/index.php');
    exit;
}

garantirTabelasPedidosDelivery($conexao);

$cardapioId = (int) ($_POST['cardapio_id'] ?? 0);
$tokenEnviado = $_POST['csrf_token'] ?? '';
$tokenSessao = $_SESSION['pedido_delivery_csrf'] ?? '';
$nomeCliente = trim((string) ($_POST['nome_cliente'] ?? ''));
$telefoneCliente = trim((string) ($_POST['telefone_cliente'] ?? ''));
$enderecoEntrega = trim((string) ($_POST['endereco_entrega'] ?? ''));
$observacao = trim((string) ($_POST['observacao'] ?? ''));
$quantidadesEnviadas = $_POST['quantidades'] ?? [];

if (
    $cardapioId <= 0
    || !is_string($tokenEnviado)
    || !is_string($tokenSessao)
    || $tokenSessao === ''
    || !hash_equals($tokenSessao, $tokenEnviado)
    || !is_array($quantidadesEnviadas)
    || $nomeCliente === ''
    || strlen($nomeCliente) > 150
    || $telefoneCliente === ''
    || strlen($telefoneCliente) > 30
    || $enderecoEntrega === ''
    || strlen($enderecoEntrega) > 500
    || strlen($observacao) > 1000
) {
    retornarAoCardapio($cardapioId, 'erro');
}

$stmtCardapio = $conexao->prepare(
    'SELECT c.usuario_id, c.nome_restaurante, c.categoria
     FROM cardapios c
     WHERE c.id = ? AND c.delivery_ativo = 1
     LIMIT 1'
);
$stmtCardapio->bind_param('i', $cardapioId);
$stmtCardapio->execute();
$cardapio = $stmtCardapio->get_result()->fetch_assoc();
$stmtCardapio->close();

if (!$cardapio) {
    retornarAoCardapio($cardapioId, 'erro');
}

$quantidades = [];
foreach ($quantidadesEnviadas as $itemIdEnviado => $quantidadeEnviada) {
    $itemId = filter_var($itemIdEnviado, FILTER_VALIDATE_INT);
    $quantidade = filter_var($quantidadeEnviada, FILTER_VALIDATE_INT);
    if ($itemId === false || $itemId <= 0 || $quantidade === false || $quantidade < 0 || $quantidade > 30) {
        retornarAoCardapio($cardapioId, 'erro');
    }
    if ($quantidade > 0) {
        $quantidades[$itemId] = $quantidade;
    }
}

if (empty($quantidades)) {
    retornarAoCardapio($cardapioId, 'erro');
}

$itemIds = array_keys($quantidades);
$placeholders = implode(',', array_fill(0, count($itemIds), '?'));
$tipos = 'i' . str_repeat('i', count($itemIds));
$parametros = array_merge([$cardapioId], $itemIds);
$stmtItens = $conexao->prepare(
    'SELECT id, nome, preco
     FROM itens_cardapio
     WHERE cardapio_id = ? AND disponivel = 1 AND id IN (' . $placeholders . ')'
);
$stmtItens->bind_param($tipos, ...$parametros);
$stmtItens->execute();
$itensDisponiveis = $stmtItens->get_result()->fetch_all(MYSQLI_ASSOC);
$stmtItens->close();

if (count($itensDisponiveis) !== count($quantidades)) {
    retornarAoCardapio($cardapioId, 'erro');
}

$total = 0.0;
foreach ($itensDisponiveis as $item) {
    $total += (float) $item['preco'] * $quantidades[(int) $item['id']];
}

$conexao->begin_transaction();

try {
    $usuarioId = (int) $cardapio['usuario_id'];
    $nomeEstabelecimento = (string) $cardapio['nome_restaurante'];
    $categoria = $cardapio['categoria'] !== null ? (string) $cardapio['categoria'] : null;
    $stmtPedido = $conexao->prepare(
        'INSERT INTO pedidos_delivery
            (usuario_id, cardapio_id, nome_estabelecimento, categoria, nome_cliente, telefone_cliente, endereco_entrega, observacao, total)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $stmtPedido->bind_param(
        'iissssssd',
        $usuarioId,
        $cardapioId,
        $nomeEstabelecimento,
        $categoria,
        $nomeCliente,
        $telefoneCliente,
        $enderecoEntrega,
        $observacao,
        $total
    );
    $stmtPedido->execute();
    $pedidoId = (int) $stmtPedido->insert_id;
    $stmtPedido->close();

    $stmtItem = $conexao->prepare(
        'INSERT INTO itens_pedido_delivery (pedido_id, item_cardapio_id, nome_item, quantidade, preco_unitario)
         VALUES (?, ?, ?, ?, ?)'
    );
    foreach ($itensDisponiveis as $item) {
        $itemCardapioId = (int) $item['id'];
        $nomeItem = (string) $item['nome'];
        $quantidadeItem = $quantidades[$itemCardapioId];
        $precoUnitario = (float) $item['preco'];
        $stmtItem->bind_param('iisid', $pedidoId, $itemCardapioId, $nomeItem, $quantidadeItem, $precoUnitario);
        $stmtItem->execute();
    }
    $stmtItem->close();

    $conexao->commit();
} catch (Throwable $erro) {
    $conexao->rollback();
    error_log('Falha ao registrar pedido delivery: ' . $erro->getMessage());
    retornarAoCardapio($cardapioId, 'erro');
}

unset($_SESSION['pedido_delivery_csrf']);
retornarAoCardapio($cardapioId, 'ok');