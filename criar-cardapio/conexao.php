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


if($conexao->connect_error){

    die("Erro: ".$conexao->connect_error);

}

$conexao->set_charset("utf8");


?>