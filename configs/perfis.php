<?php
declare(strict_types=1);

function garantirSchemaPerfis(mysqli $conn): void
{
    $colunaTipo = $conn->query("SHOW COLUMNS FROM usuarios LIKE 'tipo_usuario'");
    if (!$colunaTipo || $colunaTipo->num_rows === 0) {
        $conn->query(
            "ALTER TABLE usuarios ADD COLUMN tipo_usuario ENUM('admin','empresa','funcionario','cliente') NOT NULL DEFAULT 'empresa' AFTER senha"
        );
    } else {
        $conn->query("UPDATE usuarios SET tipo_usuario = 'empresa' WHERE tipo_usuario IS NULL");
        $conn->query(
            "ALTER TABLE usuarios MODIFY COLUMN tipo_usuario ENUM('admin','empresa','funcionario','cliente') NOT NULL DEFAULT 'empresa'"
        );
    }

    $colunaEstabelecimento = $conn->query("SHOW COLUMNS FROM usuarios LIKE 'usuario_estabelecimento_id'");
    if (!$colunaEstabelecimento || $colunaEstabelecimento->num_rows === 0) {
        $conn->query(
            'ALTER TABLE usuarios ADD COLUMN usuario_estabelecimento_id INT(11) NULL DEFAULT NULL AFTER tipo_usuario, ADD INDEX idx_usuarios_estabelecimento (usuario_estabelecimento_id), ADD CONSTRAINT fk_usuario_estabelecimento FOREIGN KEY (usuario_estabelecimento_id) REFERENCES usuarios (id) ON DELETE CASCADE'
        );
    }
}

function usuarioDonoId(mysqli $conn): int
{
    if (($_SESSION['tipo_usuario'] ?? '') === 'funcionario') {
        $usuarioId = (int) ($_SESSION['id'] ?? 0);
        $estabelecimentoId = (int) ($_SESSION['usuario_estabelecimento_id'] ?? 0);
        $stmt = $conn->prepare(
            "SELECT id FROM usuarios
             WHERE id = ? AND usuario_estabelecimento_id = ? AND tipo_usuario = 'funcionario' AND ativo = 1
             LIMIT 1"
        );
        $stmt->bind_param('ii', $usuarioId, $estabelecimentoId);
        $stmt->execute();
        if (!$stmt->get_result()->fetch_assoc()) {
            http_response_code(403);
            exit('Acesso às comandas não autorizado.');
        }

        return $estabelecimentoId;
    }

    return (int) ($_SESSION['id'] ?? 0);
}

function exigirAcessoEstabelecimento(): void
{
    if (($_SESSION['tipo_usuario'] ?? '') === 'funcionario') {
        http_response_code(403);
        exit('Acesso não autorizado para este perfil.');
    }
}

function iniciarSessaoUsuario(array $usuario): void
{
    $_SESSION['id'] = (int) $usuario['id'];
    $_SESSION['nome'] = (string) $usuario['nome'];
    $_SESSION['email'] = (string) $usuario['email'];
    $_SESSION['telefone'] = (string) ($usuario['telefone'] ?? '');
    $_SESSION['tipo_usuario'] = (string) ($usuario['tipo_usuario'] ?? 'empresa');
    $_SESSION['usuario_estabelecimento_id'] = (int) ($usuario['usuario_estabelecimento_id'] ?? $usuario['id']);
    $_SESSION['criado_em'] = time();
    $_SESSION['ip'] = $_SERVER['REMOTE_ADDR'] ?? '';
    $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
}