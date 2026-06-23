<?php
session_start();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>S.O.P.A. - Entrar ou Cadastrar</title>
  <style>
    * { box-sizing: border-box; }
    body {
      margin: 0;
      font-family: 'Inter', sans-serif;
      background: linear-gradient(135deg, #f7f3ea 0%, #fff 100%);
      color: #1f2937;
    }
    .auth-page {
      min-height: 100vh;
      display: grid;
      place-items: center;
      padding: 32px 16px;
    }
    .auth-card {
      width: min(1000px, 980px);
      display: grid;
      grid-template-columns: 1fr 1.1fr;
      background: #fff;
      border-radius: 18px;
      overflow: hidden;
      box-shadow: 0 14px 50px rgba(41, 52, 72, 0.12);
    }
    .auth-brand {
      background: #1b1b1b;
      color: #fff;
      padding: 48px 32px;
      display: flex;
      flex-direction: column;
      justify-content: center;
      gap: 18px;
    }
    .auth-brand h1 {
      margin: 0;
      font-size: 2rem;
      line-height: 1.1;
    }
    .auth-brand p {
      margin: 0;
      color: #d7d7d7;
      line-height: 1.6;
    }
    .auth-brand a {
      color: #f7c948;
      text-decoration: none;
      font-weight: 600;
    }
    .auth-forms {
      padding: 40px 32px;
      display: flex;
      flex-direction: column;
      gap: 22px;
    }
    .tabs {
      display: flex;
      gap: 10px;
    }
    .tab {
      padding: 10px 16px;
      border: 1px solid #ddd;
      background: #f8f9fb;
      border-radius: 999px;
      cursor: pointer;
      font-weight: 600;
    }
    .tab.active {
      background: #1b1b1b;
      color: #fff;
      border-color: #1b1b1b;
    }
    .form-panel {
      display: none;
    }
    .form-panel.active {
      display: block;
    }
    form {
      display: flex;
      flex-direction: column;
      gap: 14px;
    }
    label {
      font-size: 0.92rem;
      font-weight: 600;
    }
    input {
      width: 100%;
      padding: 12px 14px;
      border: 1px solid #d8dde5;
      border-radius: 10px;
      font-size: 1rem;
      outline: none;
    }
    input:focus {
      border-color: #f7c948;
      box-shadow: 0 0 0 3px rgba(247, 201, 72, 0.18);
    }
    button {
      background: #1b1b1b;
      color: #fff;
      border: none;
      border-radius: 10px;
      padding: 12px 16px;
      font-size: 1rem;
      font-weight: 700;
      cursor: pointer;
    }
    button:hover {
      background: #2e2e2e;
    }
    .message {
      padding: 10px 12px;
      display: block;
      border-radius: 8px;
      font-size: 0.95rem;
      font-weight: 600;
    }
    .message.error {
      background: #fff3f3;
      color: #a80000;
    }
    .message.success {
      background: #eefbf3;
      color: #167a39;
    }
    @media (max-width: 900px) {
      .auth-card { grid-template-columns: 1fr; }
      .auth-brand { padding: 36px 24px; }
      .auth-forms { padding: 30px 22px; }
    }
  </style>
</head>
<body>
  <main class="auth-page">
    <section class="auth-card">
      <div class="auth-brand">
        <h1>S.O.P.A.</h1>
        <p>Crie seu cardápio digital e organize pedidos com mais praticidade.</p>
        <a href="../index.html">← Voltar para a página inicial</a>
      </div>

      <div class="auth-forms">
        <div class="tabs">
          <button class="tab active" type="button" data-panel="login">Login</button>
          <button class="tab" type="button" data-panel="cadastro">Cadastro</button>
        </div>

        <?php if (isset($_GET['erro']) && $_GET['erro'] == 1): ?>
          <span class="message error">E-mail ou senha inválidos.</span>
        <?php endif; ?>

        <?php if (isset($_GET['cadastro']) && $_GET['cadastro'] == 'ok'): ?>
          <span class="message success">Cadastro realizado com sucesso! Agora faça login.</span>
        <?php endif; ?>

        <div class="form-panel active" id="panel-login">
          <form action="login.php" method="post">
            <div>
              <label for="email">E-mail</label>
              <input type="email" id="email" name="email" required />
            </div>
            <div>
              <label for="senha">Senha</label>
              <input type="password" id="senha" name="senha" required />
            </div>
            <button type="submit">Entrar</button>
          </form>
        </div>

        <div class="form-panel" id="panel-cadastro">
          <form action="cadastro.php" method="post">
            <div>
              <label for="nome">Nome</label>
              <input type="text" id="nome" name="nome" required />
            </div>
            <div>
              <label for="emailCadastro">E-mail</label>
              <input type="email" id="emailCadastro" name="email" required />
            </div>
            <div>
              <label for="telefone">Telefone</label>
              <input type="text" id="telefone" name="telefone" required />
            </div>
            <div>
              <label for="estabelecimento">Nome do estabelecimento</label>
              <input type="text" id="estabelecimento" name="estabelecimento" required />
            </div>
            <div>
              <label for="senhaCadastro">Senha</label>
              <input type="password" id="senhaCadastro" name="senha" required />
            </div>
            <button type="submit">Cadastrar</button>
          </form>
        </div>
      </div>
    </section>
  </main>

  <script src="../JS/auth-tabs.js"></script>
</body>
</html>
