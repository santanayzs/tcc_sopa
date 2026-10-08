<?php

$host = "localhost";
$usuario = "root";
$senha = "";
$banco = "sopa";

$conexao = new mysqli(
    $host,
    $usuario,
    $senha,
    $banco
);

if ($conexao->connect_error) {
    die("Erro: " . $conexao->connect_error);
}

$conexao->set_charset("utf8");

function garantirColunasPersonalizacaoCardapio(mysqli $conexao): void
{
    $colunas = $conexao->query("SHOW COLUMNS FROM cardapios LIKE 'cor_fundo_cardapio'");
    if ($colunas && $colunas->num_rows === 0) {
        $conexao->query(
            "ALTER TABLE cardapios
             ADD COLUMN cor_fundo_cardapio VARCHAR(7) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT '#f7f5f0' AFTER cor_texto"
        );
    }

    $colunasItem = $conexao->query("SHOW COLUMNS FROM cardapios LIKE 'cor_fundo_item'");
    if ($colunasItem && $colunasItem->num_rows === 0) {
        $conexao->query(
            "ALTER TABLE cardapios
             ADD COLUMN cor_fundo_item VARCHAR(7) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT '#ffffff' AFTER cor_fundo_cardapio"
        );
    }

    $colunaAtualizacao = $conexao->query("SHOW COLUMNS FROM cardapios LIKE 'atualizado_em'");
    if ($colunaAtualizacao && $colunaAtualizacao->num_rows === 0) {
        $conexao->query(
            "ALTER TABLE cardapios
             ADD COLUMN atualizado_em DATETIME NULL DEFAULT NULL AFTER data_criacao"
        );
    }

    $colunaDelivery = $conexao->query("SHOW COLUMNS FROM cardapios LIKE 'delivery_ativo'");
    if ($colunaDelivery && $colunaDelivery->num_rows === 0) {
        $conexao->query(
            "ALTER TABLE cardapios
             ADD COLUMN delivery_ativo TINYINT(1) NOT NULL DEFAULT 0 AFTER categoria"
        );
    }
}

function garantirColunasLocalizacaoEstabelecimento(mysqli $conexao): void
{
    $colunaLatitude = $conexao->query("SHOW COLUMNS FROM estabelecimentos LIKE 'latitude'");
    if ($colunaLatitude && $colunaLatitude->num_rows === 0) {
        $conexao->query(
            'ALTER TABLE estabelecimentos ADD COLUMN latitude DECIMAL(10,7) NULL DEFAULT NULL AFTER estado'
        );
    }

    $colunaLongitude = $conexao->query("SHOW COLUMNS FROM estabelecimentos LIKE 'longitude'");
    if ($colunaLongitude && $colunaLongitude->num_rows === 0) {
        $conexao->query(
            'ALTER TABLE estabelecimentos ADD COLUMN longitude DECIMAL(10,7) NULL DEFAULT NULL AFTER latitude'
        );
    }
}

garantirColunasPersonalizacaoCardapio($conexao);
garantirColunasLocalizacaoEstabelecimento($conexao);

?>