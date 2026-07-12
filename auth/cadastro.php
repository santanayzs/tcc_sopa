<?php
/**
 * cadastro.php — Registro seguro de novo usuário
 */

declare(strict_types=1);

session_start([
    'cookie_httponly' => true,
    'cookie_secure'   => true,
    'cookie_samesite' => 'Strict',
    'use_strict_mode' => true,
]);

include('../configs/conexao.php');

// ── Método ────────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Location: index.php');
    exit;
}

// ── CSRF ──────────────────────────────────────────────────────────────────────
$csrfToken = $_POST['csrf_token'] ?? '';
if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrfToken)) {
    header('Location: index.php?erro=csrf');
    exit;
}

// ── Coleta e sanitiza entradas ────────────────────────────────────────────────
$nome           = trim(htmlspecialchars($_POST['nome']           ?? '', ENT_QUOTES, 'UTF-8'));
$email          = trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL) ?? '');
$telefone       = preg_replace('/\D/', '', $_POST['telefone'] ?? '');   // Apenas dígitos
$estabelecimento= trim(htmlspecialchars($_POST['estabelecimento'] ?? '', ENT_QUOTES, 'UTF-8'));
$senha          = $_POST['senha'] ?? '';
$confirma       = $_POST['confirma_senha'] ?? '';

// ── Validações ────────────────────────────────────────────────────────────────
$erros = [];

if (mb_strlen($nome) < 3 || mb_strlen($nome) > 100) {
    $erros[] = 'nome';
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 255) {
    $erros[] = 'email';
}

// Telefone: 10 ou 11 dígitos (com DDD)
if (!preg_match('/^\d{10,11}$/', $telefone)) {
    $erros[] = 'telefone';
}

if (mb_strlen($estabelecimento) < 2 || mb_strlen($estabelecimento) > 150) {
    $erros[] = 'estabelecimento';
}

// Senha: mínimo 8 chars, ao menos 1 letra maiúscula, 1 minúscula, 1 número, 1 especial
if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/', $senha)) {
    $erros[] = 'senha_fraca';
}

if ($senha !== $confirma) {
    $erros[] = 'senha_diverge';
}

if (!empty($erros)) {
    header('Location: index.php?erro=' . implode(',', $erros));
    exit;
}

// ── Verifica duplicidade de e-mail ────────────────────────────────────────────
$check = $conn->prepare('SELECT id FROM usuarios WHERE email = ? LIMIT 1');
$check->bind_param('s', $email);
$check->execute();
if ($check->get_result()->num_rows > 0) {
    header('Location: index.php?erro=email_existente');
    exit;
}

// ── Hash da senha (bcrypt, custo padrão atual do PHP) ─────────────────────────
$senhaHash = password_hash($senha, PASSWORD_DEFAULT);

// ── Insere usuário e estabelecimento em transação ─────────────────────────────
$conn->begin_transaction();

try {
    $stmt1 = $conn->prepare(
        'INSERT INTO usuarios (nome, email, telefone, senha, ativo, criado_em)
         VALUES (?, ?, ?, ?, 1, NOW())'
    );
    $stmt1->bind_param('ssss', $nome, $email, $telefone, $senhaHash);
    $stmt1->execute();

    $idUsuario = (int) $conn->insert_id;

    $stmt2 = $conn->prepare(
        'INSERT INTO estabelecimentos (usuario_id, nome_estabelecimento, criado_em)
         VALUES (?, ?, NOW())'
    );
    $stmt2->bind_param('is', $idUsuario, $estabelecimento);
    $stmt2->execute();

    $conn->commit();
} catch (Throwable $e) {
    $conn->rollback();
    error_log('Cadastro falhou: ' . $e->getMessage());
    header('Location: index.php?erro=servidor');
    exit;
}

header('Location: index.php?cadastro=ok');
exit;
