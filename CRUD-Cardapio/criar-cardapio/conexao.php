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
}

garantirColunasPersonalizacaoCardapio($conexao);

?>