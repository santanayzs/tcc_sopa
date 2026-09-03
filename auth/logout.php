<?php
/**
 * logout.php — Encerra sessão de forma segura
 */

declare(strict_types=1);

session_start([
    'cookie_httponly' => true,
    'cookie_secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
    'cookie_samesite' => 'Strict',
]);

include('../configs/conexao.php');
include('../configs/remember.php');

// Limpa todos os dados da sessão
$_SESSION = [];

// Remove o cookie de sessão do navegador
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

session_destroy();

// Remove cookie "remember" e token no banco, se existir
if (!empty($_COOKIE['remember'])) {
    $parts = explode(':', $_COOKIE['remember']);
    if (count($parts) === 2) {
        $selector = $parts[0];
        delete_remember_token_by_selector($conn, $selector);
    }
    clear_remember_cookie();
}

header('Location: index.php?saiu=1');
exit;
