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

function garantirMesasCompartilhadas(mysqli $conn): void
{
	$colunaUsuario = $conn->query("SHOW COLUMNS FROM mesas LIKE 'usuario_id'");
	if ($colunaUsuario && $colunaUsuario->num_rows === 0) {
		$conn->query('ALTER TABLE mesas ADD COLUMN usuario_id INT NULL AFTER id');
	}

	$conn->query(
		'UPDATE mesas m
		 INNER JOIN cardapios c ON c.id = m.cardapio_id
		 SET m.usuario_id = c.usuario_id
		 WHERE m.usuario_id IS NULL'
	);

	$colunaCardapio = $conn->query("SHOW COLUMNS FROM mesas LIKE 'cardapio_id'");
	if ($colunaCardapio && ($coluna = $colunaCardapio->fetch_assoc()) && $coluna['Null'] === 'NO') {
		$conn->query('ALTER TABLE mesas MODIFY cardapio_id INT NULL DEFAULT NULL');
	}

	$conn->query('UPDATE mesas SET cardapio_id = NULL WHERE cardapio_id IS NOT NULL');

	$colunaUsuario = $conn->query("SHOW COLUMNS FROM mesas LIKE 'usuario_id'");
	if ($colunaUsuario && ($coluna = $colunaUsuario->fetch_assoc()) && $coluna['Null'] === 'YES') {
		$conn->query('ALTER TABLE mesas MODIFY usuario_id INT NOT NULL');
	}

	$indiceUsuario = $conn->query("SHOW INDEX FROM mesas WHERE Key_name = 'idx_mesas_usuario'");
	if ($indiceUsuario && $indiceUsuario->num_rows === 0) {
		$conn->query('ALTER TABLE mesas ADD INDEX idx_mesas_usuario (usuario_id)');
	}

	$colunaCardapioComanda = $conn->query("SHOW COLUMNS FROM comandas LIKE 'cardapio_id'");
	if ($colunaCardapioComanda && ($coluna = $colunaCardapioComanda->fetch_assoc()) && $coluna['Null'] === 'NO') {
		$conn->query('ALTER TABLE comandas MODIFY cardapio_id INT NULL DEFAULT NULL');
	}
}

?>