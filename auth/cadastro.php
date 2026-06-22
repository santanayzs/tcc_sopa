<?php

session_start();

include("../configs/conexao.php");

$nome=$_POST['nome'];

$email=$_POST['email'];

$telefone=$_POST['telefone'];

$senha=password_hash($_POST['senha'],PASSWORD_DEFAULT);

$estabelecimento=$_POST['estabelecimento'];

$stmt=$conn->prepare("INSERT INTO usuarios(nome,email,telefone,senha) VALUES(?,?,?,?)");

$stmt->bind_param("ssss",$nome,$email,$telefone,$senha);

$stmt->execute();

$idUsuario=$conn->insert_id;

$stmt2=$conn->prepare("INSERT INTO estabelecimentos(usuario_id,nome_estabelecimento) VALUES(?,?)");

$stmt2->bind_param("is",$idUsuario,$estabelecimento);

$stmt2->execute();

header("Location: index.html?cadastro=ok");

?>