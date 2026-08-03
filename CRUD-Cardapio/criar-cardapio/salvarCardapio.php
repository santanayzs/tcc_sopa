<?php

declare(strict_types=1);

session_start();

// ── Exige login ────────────────────────────────────────────────────────────
if (!isset($_SESSION['id'])) {
    header('Location: ../../auth/index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: criar-cardapio.php');
    exit;
}

include 'conexao.php';

$usuarioId       = (int) $_SESSION['id'];
$nomeRestaurante = trim($_POST['nome_restaurante'] ?? '');
$categoria       = trim($_POST['categoria'] ?? '');
$itens           = json_decode($_POST['itens'] ?? '[]', true);

// ── Validação básica ─────────────────────────────────────────────────────────
if ($nomeRestaurante === '' || !is_array($itens) || count($itens) === 0) {
    header('Location: criar-cardapio.php?erro=campos');
    exit;
}

$conexao->begin_transaction();

try {
    // ── Cria o cardápio, já vinculado ao usuário logado ───────────────────────
    $sql = 'INSERT INTO cardapios (usuario_id, nome_restaurante, categoria)
            VALUES (?, ?, ?)';
    $stmt = $conexao->prepare($sql);
    $stmt->bind_param('iss', $usuarioId, $nomeRestaurante, $categoria);
    $stmt->execute();

    $idCardapio = (int) $stmt->insert_id;
    $stmt->close();

    // ── Salva cada item do cardápio ────────────────────────────────────────────
    $sqlItem = 'INSERT INTO itens_cardapio (cardapio_id, nome, preco)
                VALUES (?, ?, ?)';
    $stmtItem = $conexao->prepare($sqlItem);

    foreach ($itens as $item) {
        $nomeItem  = trim((string) ($item['nome'] ?? ''));
        $precoItem = (float) ($item['preco'] ?? 0);

        if ($nomeItem === '' || $precoItem < 0) {
            continue; // ignora itens inválidos em vez de derrubar o cardápio inteiro
        }

        $stmtItem->bind_param('isd', $idCardapio, $nomeItem, $precoItem);
        $stmtItem->execute();
    }

    $stmtItem->close();

    $conexao->commit();
} catch (Throwable $e) {
    $conexao->rollback();
    error_log('Falha ao salvar cardápio: ' . $e->getMessage());
    header('Location: criar-cardapio.php?erro=servidor');
    exit;
}

header('Location: ../ver-cardapio/ver-cardapio.php?salvo=1');
exit;
