<?php
/**
 * login.php — Autenticação segura com proteção contra brute-force
 */

declare(strict_types=1);

session_start([
    'cookie_httponly' => true,
    'cookie_secure'   => true,        // Apenas HTTPS
    'cookie_samesite' => 'Strict',
    'use_strict_mode' => true,
]);

include('../configs/conexao.php');
include('../configs/remember.php');

// ── Constantes de segurança ───────────────────────────────────────────────────
define('MAX_TENTATIVAS',   5);          // Tentativas antes de bloquear
define('JANELA_SEGUNDOS',  300);        // 5 minutos
define('BLOQUEIO_SEGUNDOS', 900);       // 15 minutos de bloqueio

// ── Helpers ───────────────────────────────────────────────────────────────────

/**
 * Registra uma tentativa de login no banco.
 * Tabela esperada:
 *   tentativas_login(ip VARCHAR(45), email VARCHAR(255),
 *                    criado_em DATETIME, bem_sucedido TINYINT(1))
 */
function registrarTentativa(mysqli $conn, string $ip, string $email, bool $sucesso): void
{
    $stmt = $conn->prepare(
        'INSERT INTO tentativas_login (ip, email, criado_em, bem_sucedido)
         VALUES (?, ?, NOW(), ?)'
    );
    $s = (int) $sucesso;
    $stmt->bind_param('ssi', $ip, $email, $s);
    $stmt->execute();
}

/**
 * Verifica se o IP ou e-mail está bloqueado por excesso de tentativas.
 */
function estaBloqueado(mysqli $conn, string $ip, string $email): bool
{
    $limite = date('Y-m-d H:i:s', time() - JANELA_SEGUNDOS);
    $stmt   = $conn->prepare(
        'SELECT COUNT(*) AS total
         FROM tentativas_login
         WHERE (ip = ? OR email = ?)
           AND bem_sucedido = 0
           AND criado_em >= ?'
    );
    $stmt->bind_param('sss', $ip, $email, $limite);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return (int) $row['total'] >= MAX_TENTATIVAS;
}

/**
 * Limpa tentativas antigas (manutenção leve a cada login bem-sucedido).
 */
function limparTentativasAntigas(mysqli $conn): void
{
    $limite = date('Y-m-d H:i:s', time() - BLOQUEIO_SEGUNDOS);
    $conn->query("DELETE FROM tentativas_login WHERE criado_em < '$limite'");
}

/**
 * Regenera o ID da sessão e fixa o fingerprint do navegador.
 */
function iniciarSessaoSegura(array $usuario): void
{
    session_regenerate_id(true);

    $_SESSION['id']          = $usuario['id'];
    $_SESSION['nome']        = $usuario['nome'];
    $_SESSION['email']       = $usuario['email'];
    $_SESSION['criado_em']   = time();
    $_SESSION['ip']          = $_SERVER['REMOTE_ADDR'] ?? '';
    $_SESSION['user_agent']  = $_SERVER['HTTP_USER_AGENT'] ?? '';
}

// ── Validação de método ───────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Location: index.php');
    exit;
}

$ip    = filter_var($_SERVER['REMOTE_ADDR'] ?? '', FILTER_VALIDATE_IP) ?: 'desconhecido';
$email = trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL) ?? '');
$senha = $_POST['senha'] ?? '';

// ── Validação básica ──────────────────────────────────────────────────────────
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $senha === '') {
    header('Location: index.php?erro=campos');
    exit;
}

// ── Verificação CSRF (token no formulário) ────────────────────────────────────
$csrfToken = $_POST['csrf_token'] ?? '';
if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrfToken)) {
    header('Location: index.php?erro=csrf');
    exit;
}

// ── Rate limiting / bloqueio por IP + e-mail ─────────────────────────────────
if (estaBloqueado($conn, $ip, $email)) {
    header('Location: index.php?erro=bloqueado');
    exit;
}

// ── Consulta ao banco ─────────────────────────────────────────────────────────
$stmt = $conn->prepare(
    'SELECT id, nome, email, senha, ativo
     FROM usuarios
     WHERE email = ?
     LIMIT 1'
);
$stmt->bind_param('s', $email);
$stmt->execute();
$usuario = $stmt->get_result()->fetch_assoc();

// ── Verificação de credenciais (timing-safe) ──────────────────────────────────
$hashFalso  = '$2y$12$invalido.hash.para.evitar.timing.attack.XXXXXXXXXXXXXXXXXX';
$hashVerify = $usuario['senha'] ?? $hashFalso;
$senhaValida = password_verify($senha, $hashVerify);

if (!$usuario || !$senhaValida || !(bool) $usuario['ativo']) {
    registrarTentativa($conn, $ip, $email, false);
    // Mesmo delay para usuário inexistente (evita user enumeration)
    header('Location: index.php?erro=credenciais');
    exit;
}

// ── Rehash automático se o custo de bcrypt mudou ──────────────────────────────
if (password_needs_rehash($usuario['senha'], PASSWORD_DEFAULT)) {
    $novoHash = password_hash($senha, PASSWORD_DEFAULT);
    $upd      = $conn->prepare('UPDATE usuarios SET senha = ? WHERE id = ?');
    $upd->bind_param('si', $novoHash, $usuario['id']);
    $upd->execute();
}

// ── Login bem-sucedido ────────────────────────────────────────────────────────
registrarTentativa($conn, $ip, $email, true);
limparTentativasAntigas($conn);
iniciarSessaoSegura($usuario);

// Se o usuário marcou "Lembrar-me", cria token persistente (30 dias)
if (!empty($_POST['remember'])) {
    $payload = create_remember_token($conn, (int)$usuario['id'], 30);
    set_remember_cookie($payload, 30);
}

header('Location: ../dashboard/index.php');
exit;
