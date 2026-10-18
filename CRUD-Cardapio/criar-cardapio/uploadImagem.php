<?php

declare(strict_types=1);

/**
 * Valida e salva uma imagem enviada por upload.
 *
 * @param array  $arquivo      Um item de $_FILES (com as chaves name, type, tmp_name, error, size)
 * @param string $pastaDestino Caminho absoluto da pasta onde salvar (sem barra no final)
 * @param string $prefixo      Prefixo do nome do arquivo salvo (ex: "item_12")
 *
 * @return string|null Nome do arquivo salvo, ou null se nenhuma imagem foi enviada
 *
 * @throws InvalidArgumentException Se um arquivo foi enviado mas o formato/tamanho é inválido
 * @throws RuntimeException         Se houve falha ao mover o arquivo pro destino final
 */
function processarUploadImagem(array $arquivo, string $pastaDestino, string $prefixo): ?string
{
    // Nenhum arquivo selecionado nesse campo — não é um erro, apenas não há imagem
    if (empty($arquivo['name']) || ($arquivo['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ($arquivo['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Erro no upload da imagem (código ' . $arquivo['error'] . ').');
    }

    $tipoPermitido = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];

    $tamanhoMaximo = 2 * 1024 * 1024; // 2MB

    // getimagesize() confirma que o arquivo é REALMENTE uma imagem,
    // não apenas um arquivo com extensão de imagem (evita scripts disfarçados)
    $infoImagem = @getimagesize($arquivo['tmp_name']);

    if (
        $infoImagem === false
        || !isset($tipoPermitido[$infoImagem['mime']])
        || $arquivo['size'] > $tamanhoMaximo
    ) {
        throw new InvalidArgumentException('Formato ou tamanho de imagem inválido.');
    }

    $extensao    = $tipoPermitido[$infoImagem['mime']];
    $nomeArquivo = $prefixo . '_' . bin2hex(random_bytes(6)) . '.' . $extensao;

    if (!move_uploaded_file($arquivo['tmp_name'], rtrim($pastaDestino, '/') . '/' . $nomeArquivo)) {
        throw new RuntimeException('Falha ao mover o arquivo enviado.');
    }

    return $nomeArquivo;
}
