<?php

include 'conexao.php';

echo "Conectado com sucesso!";

$nomeRestaurante = $_POST['nome_restaurante'];
$categoria = $_POST['categoria'];
$itens = json_decode($_POST['itens'], true);



$sql = "INSERT INTO cardapios(

            nome_restaurante,
            categoria

        )

        VALUES (?,?)";


$stmt = $conexao->prepare($sql);

$stmt->bind_param(

        "ss",

        $nomeRestaurante,
        $categoria

);


$stmt->execute();


$idCardapio = $stmt->insert_id;




$stmt->close();



?>