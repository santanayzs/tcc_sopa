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

$usuarioId  = (int) $_SESSION['id'];
$idCardapio = (int) ($_POST['id'] ?? 0);

if ($idCardapio <= 0) {
    header('Location: ../ver-cardapio/ver-cardapio.php');
    exit;
}

// ── Só apaga se o cardápio pertencer ao usuário logado ────────────────────
// (os itens em itens_cardapio saem junto por causa do ON DELETE CASCADE)
$stmt = $conexao->prepare('DELETE FROM cardapios WHERE id = ? AND usuario_id = ?');
$stmt->bind_param('ii', $idCardapio, $usuarioId);
$stmt->execute();
$apagou = $stmt->affected_rows > 0;
$stmt->close();

header('Location: ../ver-cardapio/ver-cardapio.php?' . ($apagou ? 'excluido=1' : 'erro=naoencontrado'));
exit;
