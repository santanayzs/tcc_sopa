<?php
declare(strict_types=1);

// Incluir este arquivo no topo de páginas que requerem autenticação.
// Ele inicia a sessão com parâmetros seguros e tenta autenticar via remember cookie.

session_start([
    'cookie_httponly' => true,
    'cookie_secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
    'cookie_samesite' => 'Strict',
    'use_strict_mode' => true,
]);

include_once __DIR__ . '/conexao.php';
include_once __DIR__ . '/remember.php';

// Se não houver sessão ativa, tenta login via cookie "remember"
if (empty($_SESSION['id'])) {
    try_remember_login($conn);
}

?>
