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

$cardapioId = isset($_POST['cardapio_id']) ? (int) $_POST['cardapio_id'] : 0;
$numero = isset($_POST['numero']) ? (int) $_POST['numero'] : 0;
$capacidade = isset($_POST['capacidade']) ? (int) $_POST['capacidade'] : 0;

if ($cardapioId <= 0 || $numero <= 0 || $capacidade <= 0) {
    header('Location: ../teste_comandas.php?cardapio_id=' . $cardapioId . '&aviso=campos');
    exit;
}

$stmtValidar = $conn->prepare(
    'SELECT id FROM cardapios WHERE id = ? AND usuario_id = ? LIMIT 1'
);
$stmtValidar->bind_param('ii', $cardapioId, $usuarioId);
$stmtValidar->execute();
$result = $stmtValidar->get_result();

if ($result->num_rows === 0) {
    $stmtValidar->close();
    header('Location: ../teste_comandas.php?aviso=naoencontrado');
    exit;
}

$stmtValidar->close();

$stmtExiste = $conn->prepare(
    'SELECT id, ativo, status FROM mesas WHERE cardapio_id = ? AND numero = ? LIMIT 1'
);
$stmtExiste->bind_param('ii', $cardapioId, $numero);
$stmtExiste->execute();
$mesaExistente = $stmtExiste->get_result()->fetch_assoc();
$stmtExiste->close();

if ($mesaExistente) {
    if ((int) $mesaExistente['ativo'] === 1) {
        header('Location: ../teste_comandas.php?cardapio_id=' . $cardapioId . '&aviso=numero_duplicado');
        exit;
    }

    $mesaIdExistente = (int) $mesaExistente['id'];
    $capacidadeExistente = (int) $capacidade;

    $stmtReativar = $conn->prepare(
        'UPDATE mesas SET capacidade = ?, status = "LIVRE", ativo = 1, atualizado_em = NOW() WHERE id = ? AND cardapio_id = ?'
    );
    $stmtReativar->bind_param('iii', $capacidadeExistente, $mesaIdExistente, $cardapioId);

    if ($stmtReativar->execute()) {
        $stmtReativar->close();
        header('Location: ../teste_comandas.php?cardapio_id=' . $cardapioId . '&aviso=mesa_criada');
        exit;
    }

    $stmtReativar->close();
    header('Location: ../teste_comandas.php?cardapio_id=' . $cardapioId . '&aviso=campos');
    exit;
}

$stmt = $conn->prepare(
    'INSERT INTO mesas (cardapio_id, numero, capacidade, status, ativo) VALUES (?, ?, ?, "LIVRE", 1)'
);
$stmt->bind_param('iii', $cardapioId, $numero, $capacidade);

if ($stmt->execute()) {
    $stmt->close();
    header('Location: ../teste_comandas.php?cardapio_id=' . $cardapioId . '&aviso=mesa_criada');
    exit;
}

if ($conn->errno === 1062) {
    $stmt->close();
    header('Location: ../teste_comandas.php?cardapio_id=' . $cardapioId . '&aviso=numero_duplicado');
    exit;
}

$stmt->close();
header('Location: ../teste_comandas.php?cardapio_id=' . $cardapioId . '&aviso=campos');
exit;
