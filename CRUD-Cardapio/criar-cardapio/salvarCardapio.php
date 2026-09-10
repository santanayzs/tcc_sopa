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
include 'uploadImagem.php';

$usuarioId       = (int) $_SESSION['id'];
$nomeRestaurante = trim($_POST['nome_restaurante'] ?? '');
$categoria       = trim($_POST['categoria'] ?? '');
$itens           = $_POST['itens'] ?? [];
$arquivosItens   = $_FILES['itens'] ?? [];

// ── Validação básica ─────────────────────────────────────────────────────────
if ($nomeRestaurante === '' || !is_array($itens) || count($itens) === 0) {
    header('Location: criar-cardapio.php?erro=campos');
    exit;
}

$pastaItens    = __DIR__ . '/../../uploads/itens';
$imagensSalvas = []; // rastreia arquivos físicos criados, pra limpar se algo der errado

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

    // ── Salva cada item do cardápio (com foto opcional) ────────────────────────
    $sqlItem = 'INSERT INTO itens_cardapio (cardapio_id, nome, preco, imagem)
                VALUES (?, ?, ?, ?)';
    $stmtItem = $conexao->prepare($sqlItem);

    foreach ($itens as $idx => $item) {
        $nomeItem  = trim((string) ($item['nome'] ?? ''));
        $precoItem = (float) ($item['preco'] ?? 0);

        if ($nomeItem === '' || $precoItem < 0) {
            continue; // ignora itens inválidos em vez de derrubar o cardápio inteiro
        }

        $arquivoImagem = [
            'name'     => $arquivosItens['name'][$idx]['imagem'] ?? '',
            'type'     => $arquivosItens['type'][$idx]['imagem'] ?? '',
            'tmp_name' => $arquivosItens['tmp_name'][$idx]['imagem'] ?? '',
            'error'    => $arquivosItens['error'][$idx]['imagem'] ?? UPLOAD_ERR_NO_FILE,
            'size'     => $arquivosItens['size'][$idx]['imagem'] ?? 0,
        ];

        $imagemItem = processarUploadImagem($arquivoImagem, $pastaItens, 'item_' . $idCardapio);

        if ($imagemItem) {
            $imagensSalvas[] = $pastaItens . '/' . $imagemItem;
        }

        $stmtItem->bind_param('isds', $idCardapio, $nomeItem, $precoItem, $imagemItem);
        $stmtItem->execute();
    }

    $stmtItem->close();

    $conexao->commit();
} catch (InvalidArgumentException $e) {
    $conexao->rollback();
    foreach ($imagensSalvas as $caminho) {
        @unlink($caminho);
    }
    header('Location: criar-cardapio.php?erro=formato');
    exit;
} catch (Throwable $e) {
    $conexao->rollback();
    foreach ($imagensSalvas as $caminho) {
        @unlink($caminho);
    }
    error_log('Falha ao salvar cardápio: ' . $e->getMessage());
    header('Location: criar-cardapio.php?erro=servidor');
    exit;
}

header('Location: ../ver-cardapio/ver-cardapio.php?salvo=1');
exit;
