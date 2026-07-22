-- Rode este script SOMENTE SE a tabela `cardapios` já existir no seu banco
-- (criada antes da coluna usuario_id existir). Se você for criar o banco do
-- zero, use apenas o tableCardapios.sql — ele já vem com usuario_id.
--
-- ATENÇÃO: como a coluna é NOT NULL, se já existirem cardápios cadastrados
-- (linhas na tabela), o ALTER abaixo vai falhar. Nesse caso, apague os
-- cardápios de teste antes (TRUNCATE TABLE itens_cardapio; TRUNCATE TABLE
-- cardapios;) e cadastre-os de novo depois de aplicar essa migração.

ALTER TABLE cardapios
    ADD COLUMN usuario_id INT NOT NULL AFTER id;

ALTER TABLE cardapios
    ADD CONSTRAINT fk_cardapio_usuario
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
    ON DELETE CASCADE;
