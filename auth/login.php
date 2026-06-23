<?php

session_start();

include("../configs/conexao.php");

$email=$_POST['email'];

$senha=$_POST['senha'];

$stmt=$conn->prepare("SELECT * FROM usuarios WHERE email=?");

$stmt->bind_param("s",$email);

$stmt->execute();

$result=$stmt->get_result();

if($result->num_rows>0){

$usuario=$result->fetch_assoc();

if(password_verify($senha,$usuario['senha'])){

$_SESSION['id']=$usuario['id'];

$_SESSION['nome']=$usuario['nome'];

header("Location: ../home.php");

exit;

}

}

header("Location: index.html?erro=1");

?>