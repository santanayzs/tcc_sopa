<?php
declare(strict_types=1);

// Helpers para funcionalidade "Lembrar-me" (remember me)
// Usa uma tabela simples `remember_tokens` (será criada se inexistente).

function db_ensure_remember_table(mysqli $conn): void
{
    $sql = "CREATE TABLE IF NOT EXISTS remember_tokens (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        selector VARCHAR(24) NOT NULL,
        token_hash CHAR(64) NOT NULL,
        expires DATETIME NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX(selector),
        INDEX(user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    $conn->query($sql);
}

function generate_selector(int $len = 12): string
{
    return bin2hex(random_bytes($len));
}

function generate_validator(int $len = 32): string
{
    return bin2hex(random_bytes($len));
}

function create_remember_token(mysqli $conn, int $userId, int $days = 30): string
{
    db_ensure_remember_table($conn);

    $selector = generate_selector(8); // 16 hex chars
    $validator = generate_validator(16); // 32 hex chars
    $token_hash = hash('sha256', $validator);
    $expires_ts = time() + ($days * 24 * 60 * 60);
    $expires = date('Y-m-d H:i:s', $expires_ts);

    // Insere token
    $stmt = $conn->prepare(
        'INSERT INTO remember_tokens (user_id, selector, token_hash, expires)
         VALUES (?, ?, ?, ?)'
    );
    $stmt->bind_param('isss', $userId, $selector, $token_hash, $expires);
    $stmt->execute();

    // Cookie payload: selector:validator
    $payload = $selector . ':' . $validator;
    return $payload;
}

function set_remember_cookie(string $payload, int $days = 30): void
{
    $expire = time() + ($days * 24 * 60 * 60);
    setcookie('remember', $payload, [
        'expires' => $expire,
        'path'    => '/',
        'domain'  => '',
        'secure'  => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
        'httponly'=> true,
        'samesite'=> 'Strict',
    ]);
}

function clear_remember_cookie(): void
{
    setcookie('remember', '', [
        'expires' => time() - 3600,
        'path'    => '/',
        'domain'  => '',
        'secure'  => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
        'httponly'=> true,
        'samesite'=> 'Strict',
    ]);
}

function delete_remember_token_by_selector(mysqli $conn, string $selector): void
{
    $stmt = $conn->prepare('DELETE FROM remember_tokens WHERE selector = ?');
    $stmt->bind_param('s', $selector);
    $stmt->execute();
}

function delete_remember_tokens_by_user(mysqli $conn, int $userId): void
{
    $stmt = $conn->prepare('DELETE FROM remember_tokens WHERE user_id = ?');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
}

/**
 * Tenta autenticar a partir do cookie 'remember'.
 * Se bem-sucedido, cria a sessão do usuário e rotaciona o token.
 * Retorna true se autenticou, false caso contrário.
 */
function try_remember_login(mysqli $conn): bool
{
    if (!isset($_COOKIE['remember'])) return false;

    $parts = explode(':', $_COOKIE['remember']);
    if (count($parts) !== 2) return false;
    [$selector, $validator] = $parts;

    db_ensure_remember_table($conn);

    $stmt = $conn->prepare(
        'SELECT id, user_id, token_hash, expires FROM remember_tokens WHERE selector = ? LIMIT 1'
    );
    $stmt->bind_param('s', $selector);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if (!$row) return false;

    // Verifica expiracao
    if (strtotime($row['expires']) < time()) {
        delete_remember_token_by_selector($conn, $selector);
        clear_remember_cookie();
        return false;
    }

    $token_hash = $row['token_hash'];
    if (!hash_equals($token_hash, hash('sha256', $validator))) {
        // Token inválido — remover para segurança
        delete_remember_token_by_selector($conn, $selector);
        clear_remember_cookie();
        return false;
    }

    // Token válido: cria sessão para o usuário
    $userId = (int) $row['user_id'];
    $uStmt = $conn->prepare('SELECT id, nome, email, ativo FROM usuarios WHERE id = ? LIMIT 1');
    $uStmt->bind_param('i', $userId);
    $uStmt->execute();
    $usuario = $uStmt->get_result()->fetch_assoc();
    if (!$usuario || !(bool)$usuario['ativo']) {
        delete_remember_token_by_selector($conn, $selector);
        clear_remember_cookie();
        return false;
    }

    // Inicia sessão segura
    session_regenerate_id(true);
    $_SESSION['id'] = $usuario['id'];
    $_SESSION['nome'] = $usuario['nome'];
    $_SESSION['email'] = $usuario['email'];
    $_SESSION['criado_em'] = time();
    $_SESSION['ip'] = $_SERVER['REMOTE_ADDR'] ?? '';
    $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';

    // Rotaciona validator: gera novo validator e atualiza db + cookie
    $newValidator = generate_validator(16);
    $newHash = hash('sha256', $newValidator);
    $newExpires = date('Y-m-d H:i:s', time() + (30 * 24 * 60 * 60));
    $upd = $conn->prepare('UPDATE remember_tokens SET token_hash = ?, expires = ? WHERE id = ?');
    $upd->bind_param('ssi', $newHash, $newExpires, $row['id']);
    $upd->execute();

    $newPayload = $selector . ':' . $newValidator;
    set_remember_cookie($newPayload, 30);

    return true;
}

