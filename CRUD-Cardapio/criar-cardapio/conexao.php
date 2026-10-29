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

function garantirTabelasPedidosDelivery(mysqli $conexao): void
{
    $conexao->query(
        'CREATE TABLE IF NOT EXISTS pedidos_delivery (
            id INT NOT NULL AUTO_INCREMENT,
            usuario_id INT NOT NULL,
            cardapio_id INT NOT NULL,
            nome_estabelecimento VARCHAR(150) NOT NULL,
            categoria VARCHAR(100) NULL,
            nome_cliente VARCHAR(150) NOT NULL,
            telefone_cliente VARCHAR(30) NOT NULL,
            endereco_entrega VARCHAR(500) NOT NULL,
            observacao TEXT NULL,
            status ENUM("RECEBIDO", "CONFIRMADO", "EM_PREPARO", "SAIU_PARA_ENTREGA", "ENTREGUE", "CANCELADO") NOT NULL DEFAULT "RECEBIDO",
            total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_pedidos_delivery_usuario_status (usuario_id, status, criado_em),
            KEY idx_pedidos_delivery_cardapio (cardapio_id),
            CONSTRAINT fk_pedidos_delivery_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci'
    );

    $conexao->query(
        'CREATE TABLE IF NOT EXISTS itens_pedido_delivery (
            id INT NOT NULL AUTO_INCREMENT,
            pedido_id INT NOT NULL,
            item_cardapio_id INT NULL,
            nome_item VARCHAR(150) NOT NULL,
            quantidade INT NOT NULL,
            preco_unitario DECIMAL(10,2) NOT NULL,
            PRIMARY KEY (id),
            KEY idx_itens_pedido_delivery_pedido (pedido_id),
            CONSTRAINT fk_itens_pedido_delivery_pedido FOREIGN KEY (pedido_id) REFERENCES pedidos_delivery (id) ON DELETE CASCADE,
            CONSTRAINT fk_itens_pedido_delivery_item FOREIGN KEY (item_cardapio_id) REFERENCES itens_cardapio (id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci'
    );
}

garantirColunasPersonalizacaoCardapio($conexao);
garantirColunasLocalizacaoEstabelecimento($conexao);

?>