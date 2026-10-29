<?php

declare(strict_types=1);

session_start();

if (!isset($_SESSION['id'])) {
    header('Location: ../../auth/index.php');
    exit;
}

include '../../configs/conexao.php';
garantirMesasCompartilhadas($conn);

$usuarioId = (int) ($_SESSION['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../comandas.php');
    exit;
}

$numero = isset($_POST['numero']) ? (int) $_POST['numero'] : 0;
$capacidade = isset($_POST['capacidade']) ? (int) $_POST['capacidade'] : 0;

if ($numero <= 0 || $capacidade <= 0) {
    header('Location: ../comandas.php?aviso=campos');
    exit;
}

$stmtExiste = $conn->prepare(
    'SELECT id, ativo, status FROM mesas WHERE usuario_id = ? AND numero = ? LIMIT 1'
);
$stmtExiste->bind_param('ii', $usuarioId, $numero);
$stmtExiste->execute();
$mesaExistente = $stmtExiste->get_result()->fetch_assoc();
$stmtExiste->close();

if ($mesaExistente) {
    if ((int) $mesaExistente['ativo'] === 1) {
        header('Location: ../comandas.php?aviso=numero_duplicado');
        exit;
    }

    $mesaIdExistente = (int) $mesaExistente['id'];
    $capacidadeExistente = (int) $capacidade;

    $stmtReativar = $conn->prepare(
        'UPDATE mesas SET capacidade = ?, status = "LIVRE", ativo = 1, atualizado_em = NOW() WHERE id = ? AND usuario_id = ?'
    );
    $stmtReativar->bind_param('iii', $capacidadeExistente, $mesaIdExistente, $usuarioId);

    if ($stmtReativar->execute()) {
        $stmtReativar->close();
        header('Location: ../comandas.php?aviso=mesa_criada');
        exit;
    }

    $stmtReativar->close();
    header('Location: ../comandas.php?aviso=campos');
    exit;
}

$stmt = $conn->prepare(
    'INSERT INTO mesas (usuario_id, cardapio_id, numero, capacidade, status, ativo) VALUES (?, NULL, ?, ?, "LIVRE", 1)'
);
$stmt->bind_param('iii', $usuarioId, $numero, $capacidade);

if ($stmt->execute()) {
    $stmt->close();
    header('Location: ../comandas.php?aviso=mesa_criada');
    exit;
}

if ($conn->errno === 1062) {
    $stmt->close();
    header('Location: ../comandas.php?aviso=numero_duplicado');
    exit;
}

$stmt->close();
header('Location: ../comandas.php?aviso=campos');
exit;
