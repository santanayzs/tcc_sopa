CREATE TABLE cardapios(

    id INT AUTO_INCREMENT PRIMARY KEY,

    nome_restaurante VARCHAR(150) NOT NULL,

    categoria VARCHAR(100),

    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP

);