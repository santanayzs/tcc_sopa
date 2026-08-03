<?php

declare(strict_types=1);

session_start();

// ── Exige login ────────────────────────────────────────────────────────────
if (!isset($_SESSION['id'])) {
    header('Location: ../../auth/index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../ver-cardapio/ver-cardapio.php');
    exit;
}

include '../criar-cardapio/conexao.php';

$usuarioId       = (int) $_SESSION['id'];
$idCardapio      = (int) ($_POST['id'] ?? 0);
$nomeRestaurante = trim($_POST['nome_restaurante'] ?? '');
$categoria       = trim($_POST['categoria'] ?? '');
$itens           = json_decode($_POST['itens'] ?? '[]', true);

// ── Validação básica ───────────────────────────────────────────────────────
if ($idCardapio <= 0 || $nomeRestaurante === '' || !is_array($itens) || count($itens) === 0) {
    header('Location: editar-cardapio.php?id=' . $idCardapio . '&erro=campos');
    exit;
}

// ── Confirma que o cardápio existe e pertence ao usuário logado ──────────
$stmtDono = $conexao->prepare('SELECT id FROM cardapios WHERE id = ? AND usuario_id = ?');
$stmtDono->bind_param('ii', $idCardapio, $usuarioId);
$stmtDono->execute();
$existe = $stmtDono->get_result()->fetch_assoc();
$stmtDono->close();

if (!$existe) {
    header('Location: ../ver-cardapio/ver-cardapio.php?erro=naoencontrado');
    exit;
}

$conexao->begin_transaction();

try {
    // ── Atualiza os dados do cardápio ──────────────────────────────────────────
    $sql = 'UPDATE cardapios
            SET nome_restaurante = ?, categoria = ?
            WHERE id = ? AND usuario_id = ?';
    $stmt = $conexao->prepare($sql);
    $stmt->bind_param('ssii', $nomeRestaurante, $categoria, $idCardapio, $usuarioId);
    $stmt->execute();
    $stmt->close();

    // ── Substitui a lista de itens (remove os antigos e insere os novos) ──────
    $stmtDel = $conexao->prepare('DELETE FROM itens_cardapio WHERE cardapio_id = ?');
    $stmtDel->bind_param('i', $idCardapio);
    $stmtDel->execute();
    $stmtDel->close();

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
    error_log('Falha ao atualizar cardápio: ' . $e->getMessage());
    header('Location: editar-cardapio.php?id=' . $idCardapio . '&erro=servidor');
    exit;
}

header('Location: ../ver-cardapio/ver-cardapio.php?atualizado=1');
exit;
