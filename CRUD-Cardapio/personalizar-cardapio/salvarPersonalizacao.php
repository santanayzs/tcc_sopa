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

// ── Confirma que o cardápio existe e pertence ao usuário logado ──────────
$stmt = $conexao->prepare('SELECT logo FROM cardapios WHERE id = ? AND usuario_id = ?');
$stmt->bind_param('ii', $idCardapio, $usuarioId);
$stmt->execute();
$cardapio = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$cardapio) {
    header('Location: ../ver-cardapio/ver-cardapio.php?erro=naoencontrado');
    exit;
}

// ── Valida as cores (formato hexadecimal, ex: #2f6b4f) ────────────────────
$corPrimariaPadrao = '#2f6b4f';
$corTextoPadrao    = '#1c1c1c';
$corFundoCardapioPadrao = '#f7f5f0';
$corFundoItemPadrao = '#ffffff';

$corPrimaria = preg_match('/^#[0-9a-fA-F]{6}$/', $_POST['cor_primaria'] ?? '')
    ? $_POST['cor_primaria']
    : $corPrimariaPadrao;

$corTexto = preg_match('/^#[0-9a-fA-F]{6}$/', $_POST['cor_texto'] ?? '')
    ? $_POST['cor_texto']
    : $corTextoPadrao;

$corFundoCardapio = preg_match('/^#[0-9a-fA-F]{6}$/', $_POST['cor_fundo_cardapio'] ?? '')
    ? $_POST['cor_fundo_cardapio']
    : $corFundoCardapioPadrao;

$corFundoItem = preg_match('/^#[0-9a-fA-F]{6}$/', $_POST['cor_fundo_item'] ?? '')
    ? $_POST['cor_fundo_item']
    : $corFundoItemPadrao;

$logoAtual = $cardapio['logo'];
$logoFinal = $logoAtual;
$pastaLogos = __DIR__ . '/../uploads/logos/';

// ── Remoção da logo atual, se marcada ──────────────────────────────────────
if (!empty($_POST['remover_logo']) && $logoAtual) {
    @unlink($pastaLogos . $logoAtual);
    $logoFinal = null;
}

// ── Upload de uma nova logo, se enviada ────────────────────────────────────
if (!empty($_FILES['logo']['name']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
    $tipoPermitido = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];

    $tamanhoMaximo = 2 * 1024 * 1024; // 2MB

    // getimagesize() confirma que o arquivo é REALMENTE uma imagem,
    // não apenas um arquivo com extensão de imagem (evita scripts disfarçados)
    $infoImagem = @getimagesize($_FILES['logo']['tmp_name']);

    if (
        $infoImagem === false
        || !isset($tipoPermitido[$infoImagem['mime']])
        || $_FILES['logo']['size'] > $tamanhoMaximo
    ) {
        header('Location: personalizar-cardapio.php?id=' . $idCardapio . '&erro=formato');
        exit;
    }

    $extensao = $tipoPermitido[$infoImagem['mime']];
    $nomeArquivo = 'logo_' . $idCardapio . '_' . bin2hex(random_bytes(6)) . '.' . $extensao;

    if (!move_uploaded_file($_FILES['logo']['tmp_name'], $pastaLogos . $nomeArquivo)) {
        header('Location: personalizar-cardapio.php?id=' . $idCardapio . '&erro=servidor');
        exit;
    }

    // Remove a logo antiga (se existia e não foi a mesma que acabamos de remover acima)
    if ($logoAtual && $logoFinal !== null) {
        @unlink($pastaLogos . $logoAtual);
    }

    $logoFinal = $nomeArquivo;
}

// ── Salva no banco ──────────────────────────────────────────────────────────
$stmtUpdate = $conexao->prepare(
    'UPDATE cardapios
     SET cor_primaria = ?, cor_texto = ?, cor_fundo_cardapio = ?, cor_fundo_item = ?, logo = ?
     WHERE id = ? AND usuario_id = ?'
);
$stmtUpdate->bind_param('sssssii', $corPrimaria, $corTexto, $corFundoCardapio, $corFundoItem, $logoFinal, $idCardapio, $usuarioId);
$stmtUpdate->execute();
$stmtUpdate->close();

header('Location: ../ver-cardapio/ver-cardapio.php?personalizado=1');
exit;
