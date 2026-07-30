CREATE TABLE cardapios(

    id INT AUTO_INCREMENT PRIMARY KEY,

    usuario_id INT NOT NULL,

    nome_restaurante VARCHAR(150) NOT NULL,

    categoria VARCHAR(100),

    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY(usuario_id)
    REFERENCES usuarios(id)

    ON DELETE CASCADE

);
