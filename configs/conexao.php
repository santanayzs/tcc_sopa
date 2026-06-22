<?php

$host="localhost";

$user="root";

$password="";

$db="sopa";

$conn=new mysqli($host,$user,$password,$db);

if($conn->connect_error){

die("Erro: ".$conn->connect_error);

}

$conn->set_charset("utf8");

?>