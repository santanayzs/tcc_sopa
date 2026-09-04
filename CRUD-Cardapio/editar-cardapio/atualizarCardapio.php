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
include '../criar-cardapio/uploadImagem.php';

$usuarioId       = (int) $_SESSION['id'];
$idCardapio      = (int) ($_POST['id'] ?? 0);
$nomeRestaurante = trim($_POST['nome_restaurante'] ?? '');
$categoria       = trim($_POST['categoria'] ?? '');
$itens           = $_POST['itens'] ?? [];
$arquivosItens   = $_FILES['itens'] ?? [];

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

$pastaItens = __DIR__ . '/../../uploads/itens';

// ── Descobre quais imagens existiam antes, pra saber depois quais limpar ──
$stmtAntigas = $conexao->prepare('SELECT imagem FROM itens_cardapio WHERE cardapio_id = ? AND imagem IS NOT NULL');
$stmtAntigas->bind_param('i', $idCardapio);
$stmtAntigas->execute();
$imagensAntigas = array_column($stmtAntigas->get_result()->fetch_all(MYSQLI_ASSOC), 'imagem');
$stmtAntigas->close();

$imagensMantidas = []; // filenames antigos que continuam em uso após a edição
$imagensNovas    = []; // arquivos físicos recém-enviados agora (pra limpar se algo der errado)
$itensParaSalvar = []; // [nome, preco, imagemFinal] já resolvidos

// ── Resolve, item a item, qual vai ser a imagem final (nova / mantida / removida) ──
try {
    foreach ($itens as $idx => $item) {
        $nomeItem    = trim((string) ($item['nome'] ?? ''));
        $precoItem   = (float) ($item['preco'] ?? 0);
        $imagemAtual = trim((string) ($item['imagem_atual'] ?? ''));
        $removerFoto = !empty($item['remover_imagem']);

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

        $novaImagem = processarUploadImagem($arquivoImagem, $pastaItens, 'item_' . $idCardapio);

        if ($novaImagem) {
            // Uma foto nova foi enviada: usa ela (a antiga, se existia, sai de uso)
            $imagemFinal = $novaImagem;
            $imagensNovas[] = $pastaItens . '/' . $novaImagem;
        } elseif ($removerFoto) {
            // Marcou "remover foto" e não mandou substituta
            $imagemFinal = null;
        } else {
            // Não mexeu na foto: mantém a que já existia
            $imagemFinal = $imagemAtual !== '' ? $imagemAtual : null;
            if ($imagemFinal) {
                $imagensMantidas[] = $imagemFinal;
            }
        }

        $itensParaSalvar[] = [$nomeItem, $precoItem, $imagemFinal];
    }
} catch (InvalidArgumentException $e) {
    foreach ($imagensNovas as $caminho) {
        @unlink($caminho);
    }
    header('Location: editar-cardapio.php?id=' . $idCardapio . '&erro=formato');
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

    $sqlItem = 'INSERT INTO itens_cardapio (cardapio_id, nome, preco, imagem)
                VALUES (?, ?, ?, ?)';
    $stmtItem = $conexao->prepare($sqlItem);

    foreach ($itensParaSalvar as [$nomeItem, $precoItem, $imagemFinal]) {
        $stmtItem->bind_param('isds', $idCardapio, $nomeItem, $precoItem, $imagemFinal);
        $stmtItem->execute();
    }

    $stmtItem->close();

    $conexao->commit();
} catch (Throwable $e) {
    $conexao->rollback();
    foreach ($imagensNovas as $caminho) {
        @unlink($caminho);
    }
    error_log('Falha ao atualizar cardápio: ' . $e->getMessage());
    header('Location: editar-cardapio.php?id=' . $idCardapio . '&erro=servidor');
    exit;
}

// ── Só depois do sucesso, apaga do disco as fotos antigas que saíram de uso ──
foreach ($imagensAntigas as $antiga) {
    if (!in_array($antiga, $imagensMantidas, true)) {
        @unlink($pastaItens . '/' . $antiga);
    }
}

header('Location: ../ver-cardapio/ver-cardapio.php?atualizado=1');
exit;
