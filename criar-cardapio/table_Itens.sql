CREATE TABLE itens_cardapio(

    id INT AUTO_INCREMENT PRIMARY KEY,

    cardapio_id INT NOT NULL,

    nome VARCHAR(150) NOT NULL,

    descricao TEXT,

    preco DECIMAL(10,2) NOT NULL,

    disponivel BOOLEAN DEFAULT TRUE,


    FOREIGN KEY(cardapio_id)
    REFERENCES cardapios(id)

    ON DELETE CASCADE

);