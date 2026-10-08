<?php
require_once __DIR__ . '/../configs/session_helper.php';

if (empty($_SESSION['id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: gerenciar-delivery.php');
    exit;
}

$csrfToken = $_SESSION['delivery_csrf'] ?? '';
$postedToken = $_POST['csrf_token'] ?? '';
if (!is_string($postedToken) || $csrfToken === '' || !hash_equals($csrfToken, $postedToken)) {
    header('Location: gerenciar-delivery.php?delivery=erro');
    exit;
}

$cardapioIds = $_POST['cardapio_ids'] ?? [];
if (!is_array($cardapioIds)) {
    header('Location: gerenciar-delivery.php?delivery=erro');
    exit;
}

$idsValidados = [];
foreach ($cardapioIds as $cardapioId) {
    $idValidado = filter_var($cardapioId, FILTER_VALIDATE_INT);
    if ($idValidado === false || $idValidado < 1) {
        header('Location: gerenciar-delivery.php?delivery=erro');
        exit;
    }
    $idsValidados[] = $idValidado;
}
$idsValidados = array_values(array_unique($idsValidados));

$tipoLocalizacao = $_POST['location_type'] ?? '';
if ($tipoLocalizacao === 'gps') {
    $latitude = filter_var($_POST['latitude'] ?? null, FILTER_VALIDATE_FLOAT);
    $longitude = filter_var($_POST['longitude'] ?? null, FILTER_VALIDATE_FLOAT);
    if ($latitude === false || $longitude === false || $latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
        header('Location: gerenciar-delivery.php?delivery=localizacao');
        exit;
    }
} elseif ($tipoLocalizacao === 'endereco') {
    $cep = preg_replace('/\D+/', '', trim((string) ($_POST['cep'] ?? '')));
    $logradouro = trim((string) ($_POST['logradouro'] ?? ''));
    $numero = trim((string) ($_POST['numero'] ?? ''));
    $bairro = trim((string) ($_POST['bairro'] ?? ''));
    $cidade = trim((string) ($_POST['cidade'] ?? ''));
    $estado = strtoupper(trim((string) ($_POST['estado'] ?? '')));

    if (strlen($cep) !== 8 || $logradouro === '' || $bairro === '' || $cidade === '' || !preg_match('/^[A-Z]{2}$/', $estado)) {
        header('Location: gerenciar-delivery.php?delivery=localizacao');
        exit;
    }
} else {
    header('Location: gerenciar-delivery.php?delivery=localizacao');
    exit;
}

include __DIR__ . '/../CRUD-Cardapio/criar-cardapio/conexao.php';
$usuarioId = (int) $_SESSION['id'];

try {
    $conexao->begin_transaction();

    $stmtEstabelecimento = $conexao->prepare(
        'SELECT id FROM estabelecimentos WHERE usuario_id = ? ORDER BY id LIMIT 1'
    );
    $stmtEstabelecimento->bind_param('i', $usuarioId);
    $stmtEstabelecimento->execute();
    $estabelecimentoExiste = $stmtEstabelecimento->get_result()->fetch_assoc();
    $stmtEstabelecimento->close();

    if (!$estabelecimentoExiste) {
        throw new RuntimeException('Estabelecimento não encontrado.');
    }

    if ($tipoLocalizacao === 'gps') {
        $stmtLocalizacao = $conexao->prepare(
            'UPDATE estabelecimentos
             SET cep = NULL, logradouro = NULL, numero = NULL, bairro = NULL, cidade = NULL, estado = NULL,
                 latitude = ?, longitude = ?
             WHERE usuario_id = ?'
        );
        $stmtLocalizacao->bind_param('ddi', $latitude, $longitude, $usuarioId);
    } else {
        $stmtLocalizacao = $conexao->prepare(
            'UPDATE estabelecimentos
             SET cep = ?, logradouro = ?, numero = ?, bairro = ?, cidade = ?, estado = ?, latitude = NULL, longitude = NULL
             WHERE usuario_id = ?'
        );
        $stmtLocalizacao->bind_param('ssssssi', $cep, $logradouro, $numero, $bairro, $cidade, $estado, $usuarioId);
    }
    $stmtLocalizacao->execute();
    $stmtLocalizacao->close();

    $stmtOcultar = $conexao->prepare(
        'UPDATE cardapios SET delivery_ativo = 0 WHERE usuario_id = ?'
    );
    $stmtOcultar->bind_param('i', $usuarioId);
    $stmtOcultar->execute();
    $stmtOcultar->close();

    $stmtPublicar = $conexao->prepare(
        'UPDATE cardapios SET delivery_ativo = 1 WHERE id = ? AND usuario_id = ?'
    );
    foreach ($idsValidados as $cardapioId) {
        $stmtPublicar->bind_param('ii', $cardapioId, $usuarioId);
        $stmtPublicar->execute();
    }
    $stmtPublicar->close();

    $conexao->commit();
    header('Location: gerenciar-delivery.php?delivery=salvo');
} catch (Throwable $erro) {
    $conexao->rollback();
    header('Location: gerenciar-delivery.php?delivery=erro');
}
exit;