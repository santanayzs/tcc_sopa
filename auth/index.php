<?php
/**
 * index.php — Tela de login/cadastro/recuperação de senha
 * O token CSRF é gerado aqui no servidor e impresso diretamente nos formulários.
 */

declare(strict_types=1);

session_start([
    'cookie_httponly' => true,
    'cookie_secure'   => true,
    'cookie_samesite' => 'Strict',
    'use_strict_mode' => true,
]);

// Gera (ou reutiliza) o token CSRF da sessão
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

// Função auxiliar: imprime o campo hidden com o token
function csrfField(string $token): string {
    return '<input type="hidden" name="csrf_token" value="'
         . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
}
?>
<!doctype html>
<html lang="pt-br">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>S.O.P.A. - Entrar ou Cadastrar</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700;800&family=Inter:wght@400;500;600&family=Cormorant+Garamond:wght@500;600&display=swap"
      rel="stylesheet"
    />
    <link rel="stylesheet" href="../CSS/style.css" />
    <style>
      :root {
        --auth-panel:   #344e49;
        --auth-panel-2: #3f5d57;
        --auth-surface: #eef1ea;
        --auth-border:  rgba(255,255,255,0.08);
      }

      body {
        background: linear-gradient(155deg, #eef1ea 0%, #dde3d5 45%, #c9d1c4 100%);
      }

      .auth-page {
        min-height: 100vh;
        display: grid;
        place-items: center;
        padding: 48px 18px;
      }

      .auth-card {
        width: min(980px, 100%);
        display: grid;
        grid-template-columns: 0.95fr 1.05fr;
        background: var(--auth-panel);
        border-radius: var(--radius-lg, 16px);
        overflow: hidden;
        border: 1px solid var(--auth-border);
        box-shadow: 0 24px 60px -22px rgba(0,0,0,0.55);
      }

      .auth-brand {
        position: relative;
        background:
          linear-gradient(180deg, rgba(36,57,53,0.96), rgba(52,78,73,0.95)),
          radial-gradient(circle at top, rgba(207,219,212,0.08), transparent 36%);
        padding: 56px 40px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        gap: 18px;
      }

      .auth-brand h1 { margin: 0; font-family: var(--font-display, 'Cormorant Garamond', serif); font-size: 2rem; letter-spacing: 0.04em; color: #fff; }
      .auth-brand p  { margin: 0; max-width: 32ch; color: rgba(255,255,255,0.7); line-height: 1.6; }
      .auth-brand a  { display: inline-flex; align-items: center; width: fit-content; color: #fff; font-weight: 600; text-decoration: underline; text-underline-offset: 0.3em; }

      .auth-forms {
        background: var(--auth-panel-2);
        padding: 42px 36px;
        display: flex;
        flex-direction: column;
        gap: 18px;
      }

      .tabs { display: flex; gap: 10px; flex-wrap: wrap; }

      .tab {
        padding: 10px 16px;
        border: 1px solid rgba(255,255,255,0.1);
        background: rgba(255,255,255,0.06);
        border-radius: 999px;
        color: #fff;
        cursor: pointer;
        font-family: var(--font-display, 'Cormorant Garamond', serif);
        font-weight: 600;
        transition: background 0.2s ease;
        font-size: 0.95rem;
      }
      .tab.active { background: #fff; color: #344e49; }

      .form-panel { display: none; }
      .form-panel.active { display: block; }

      form { display: flex; flex-direction: column; gap: 14px; }

      form label {
        display: block;
        margin-bottom: 6px;
        font-size: 0.88rem;
        font-weight: 600;
        color: rgba(255,255,255,0.85);
      }

      form input[type="email"],
      form input[type="password"],
      form input[type="text"],
      form input[type="tel"] {
        width: 100%;
        padding: 12px 14px;
        background: var(--auth-surface);
        color: #1a2420;
        border: 1px solid rgba(22,31,29,0.12);
        border-radius: 10px;
        font-size: 1rem;
        outline: none;
        box-sizing: border-box;
        transition: border-color 0.15s, box-shadow 0.15s;
      }
      form input:focus {
        border-color: #7fa898;
        box-shadow: 0 0 0 3px rgba(127,168,152,0.2);
      }

      .senha-hint {
        font-size: 0.78rem;
        color: rgba(255,255,255,0.5);
        margin-top: 4px;
        line-height: 1.4;
      }

      form button[type="submit"] {
        margin-top: 6px;
        background: #fff;
        color: #344e49;
        border: none;
        border-radius: 10px;
        padding: 13px 16px;
        font-family: var(--font-display, 'Cormorant Garamond', serif);
        font-size: 1rem;
        font-weight: 700;
        cursor: pointer;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
      }
      form button[type="submit"]:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0,0,0,0.2); }

      .link-aux {
        font-size: 0.83rem;
        color: rgba(255,255,255,0.55);
        text-align: right;
        cursor: pointer;
        background: none;
        border: none;
        padding: 0;
        text-decoration: underline;
        text-underline-offset: 3px;
        transition: color 0.15s;
      }
      .link-aux:hover { color: rgba(255,255,255,0.9); }

      .message {
        display: none;
        padding: 10px 14px;
        border-radius: 10px;
        font-size: 0.88rem;
        font-weight: 600;
        line-height: 1.45;
      }
      .message.visible { display: block; }
      .message.error   { background: #f8e5e5; color: #9b1c1c; }
      .message.success { background: #e7f6ee; color: #1f6d45; }
      .message.info    { background: #e5eef8; color: #1c3f9b; }

      .metodo-selector { display: flex; gap: 10px; margin-bottom: 4px; }
      .metodo-selector label {
        display: flex;
        align-items: center;
        gap: 6px;
        cursor: pointer;
        font-size: 0.88rem;
        color: rgba(255,255,255,0.8);
        font-weight: 500;
        margin: 0;
      }
      .metodo-selector input[type="radio"] { accent-color: #7fa898; width: 16px; height: 16px; }

      @media (max-width: 900px) {
        .auth-card { grid-template-columns: 1fr; }
        .auth-brand, .auth-forms { padding: 36px 24px; }
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
            <button class="tab"        type="button" data-panel="cadastro">Cadastro</button>
            <button class="tab"        type="button" data-panel="recuperar">Recuperar senha</button>
          </div>

          <span id="msg-error"   class="message error"></span>
          <span id="msg-success" class="message success"></span>
          <span id="msg-info"    class="message info"></span>

          <!-- ── LOGIN ─────────────────────────────────────────── -->
          <div class="form-panel active" id="panel-login">
            <form action="login.php" method="post" novalidate>
              <?= csrfField($csrfToken) ?>

              <div>
                <label for="email">E-mail</label>
                <input type="email" id="email" name="email"
                       autocomplete="email" required />
              </div>

              <div>
                <label for="senha">Senha</label>
                <input type="password" id="senha" name="senha"
                       autocomplete="current-password" required />
              </div>

              <button type="button" class="link-aux" data-panel="recuperar">
                Esqueci minha senha
              </button>

              <button type="submit">Entrar</button>
            </form>
          </div>

          <!-- ── CADASTRO ───────────────────────────────────────── -->
          <div class="form-panel" id="panel-cadastro">
            <form action="cadastro.php" method="post" novalidate>
              <?= csrfField($csrfToken) ?>

              <div>
                <label for="nome">Nome Completo</label>
                <input type="text" id="nome" name="nome"
                       autocomplete="name" minlength="3" maxlength="100" required />
              </div>

              <div>
                <label for="emailCadastro">E-mail</label>
                <input type="email" id="emailCadastro" name="email"
                       autocomplete="email" required />
              </div>

              <div>
                <label for="telefone">Telefone (com DDD)</label>
                <input type="tel" id="telefone" name="telefone"
                       autocomplete="tel" placeholder="11999999999"
                       pattern="\d{10,11}" required />
              </div>

              <div>
                <label for="estabelecimento">Nome do estabelecimento</label>
                <input type="text" id="estabelecimento" name="estabelecimento"
                       minlength="2" maxlength="150" required />
              </div>

              <div>
                <label for="senhaCadastro">Senha</label>
                <input type="password" id="senhaCadastro" name="senha"
                       autocomplete="new-password" minlength="8" required />
                <p class="senha-hint">
                  Mínimo 8 caracteres · maiúscula · minúscula · número · símbolo
                </p>
              </div>

              <div>
                <label for="confirma">Confirmar senha</label>
                <input type="password" id="confirma" name="confirma_senha"
                       autocomplete="new-password" required />
              </div>

              <button type="submit">Cadastrar</button>
            </form>
          </div>

          <!-- ── RECUPERAR SENHA ────────────────────────────────── -->
          <div class="form-panel" id="panel-recuperar">
            <form action="recuperar-senha.php" method="post" novalidate>
              <?= csrfField($csrfToken) ?>

              <div>
                <label for="emailRecuperar">E-mail da conta</label>
                <input type="email" id="emailRecuperar" name="email"
                       autocomplete="email" required />
              </div>

              <div>
                <p style="font-size:0.88rem;color:rgba(255,255,255,0.7);margin:0 0 6px">
                  Receber o código por:
                </p>
                <div class="metodo-selector">
                  <label>
                    <input type="radio" name="metodo" value="email" checked />
                    E-mail
                  </label>
                  <label>
                    <input type="radio" name="metodo" value="sms" />
                    SMS
                  </label>
                </div>
              </div>

              <button type="submit">Enviar código</button>
            </form>
          </div>

          <!-- ── REDEFINIR — código SMS ─────────────────────────── -->
          <div class="form-panel" id="panel-codigo-sms">
            <form action="redefinir-senha.php" method="post" novalidate>
              <?= csrfField($csrfToken) ?>
              <input type="hidden" name="etapa" value="sms" />

              <div>
                <label for="codigoSms">Código de 6 dígitos (SMS)</label>
                <input type="text" id="codigoSms" name="codigo"
                       inputmode="numeric" pattern="\d{6}"
                       maxlength="6" placeholder="000000" required />
              </div>

              <div>
                <label for="novaSenhaSms">Nova senha</label>
                <input type="password" id="novaSenhaSms" name="nova_senha"
                       autocomplete="new-password" minlength="8" required />
                <p class="senha-hint">
                  Mínimo 8 caracteres · maiúscula · minúscula · número · símbolo
                </p>
              </div>

              <div>
                <label for="confirmaSenhaSms">Confirmar nova senha</label>
                <input type="password" id="confirmaSenhaSms" name="confirma_senha"
                       autocomplete="new-password" required />
              </div>

              <button type="submit">Redefinir senha</button>
            </form>
          </div>

          <!-- ── REDEFINIR — link de e-mail ────────────────────── -->
          <div class="form-panel" id="panel-nova-senha">
            <form action="redefinir-senha.php" method="post" novalidate>
              <?= csrfField($csrfToken) ?>
              <input type="hidden" name="etapa" value="email" />

              <div>
                <label for="novaSenhaEmail">Nova senha</label>
                <input type="password" id="novaSenhaEmail" name="nova_senha"
                       autocomplete="new-password" minlength="8" required />
                <p class="senha-hint">
                  Mínimo 8 caracteres · maiúscula · minúscula · número · símbolo
                </p>
              </div>

              <div>
                <label for="confirmaSenhaEmail">Confirmar nova senha</label>
                <input type="password" id="confirmaSenhaEmail" name="confirma_senha"
                       autocomplete="new-password" required />
              </div>

              <button type="submit">Redefinir senha</button>
            </form>
          </div>

        </div><!-- /.auth-forms -->
      </section>
    </main>

    <script>
    (() => {
      'use strict';

      // ── Tabs ─────────────────────────────────────────────────────────────────
      function ativarPainel(nomePanel) {
        document.querySelectorAll('.tab').forEach(t =>
          t.classList.toggle('active', t.dataset.panel === nomePanel)
        );
        document.querySelectorAll('.form-panel').forEach(p =>
          p.classList.toggle('active', p.id === 'panel-' + nomePanel)
        );
        limparMensagens();
      }

      document.querySelectorAll('[data-panel]').forEach(el => {
        el.addEventListener('click', () => ativarPainel(el.dataset.panel));
      });

      // ── Mensagens ────────────────────────────────────────────────────────────
      function limparMensagens() {
        document.querySelectorAll('.message').forEach(m => {
          m.textContent = '';
          m.classList.remove('visible');
        });
      }

      function mostrarMensagem(tipo, texto) {
        limparMensagens();
        const el = document.getElementById('msg-' + tipo);
        if (!el) return;
        el.textContent = texto;
        el.classList.add('visible');
        el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
      }

      // ── Mapa de parâmetros → mensagens ────────────────────────────────────────
      const mensagensErro = {
        credenciais:     'E-mail ou senha incorretos. Verifique e tente novamente.',
        bloqueado:       'Muitas tentativas. Aguarde 15 minutos antes de tentar novamente.',
        campos:          'Preencha todos os campos corretamente.',
        csrf:            'Sessão expirada. Recarregue a página e tente novamente.',
        email_existente: 'Este e-mail já está cadastrado. Faça login ou recupere sua senha.',
        senha_fraca:     'A senha precisa ter ao menos 8 caracteres, maiúscula, minúscula, número e símbolo.',
        senha_diverge:   'As senhas não coincidem.',
        token_invalido:  'O link de redefinição é inválido ou expirou. Solicite um novo.',
        codigo_invalido: 'Código incorreto ou expirado. Solicite um novo.',
        sessao_expirada: 'Sessão expirada. Solicite uma nova recuperação de senha.',
        servidor:        'Erro no servidor. Tente novamente em alguns instantes.',
      };

      // ── Processa parâmetros da URL ao carregar ────────────────────────────────
      function processarUrl() {
        const params = new URLSearchParams(location.search);

        if (params.get('cadastro') === 'ok') {
          mostrarMensagem('success', 'Cadastro realizado! Agora faça login.');
          ativarPainel('login');
        }

        if (params.get('saiu') === '1') {
          mostrarMensagem('info', 'Você saiu da sua conta com sucesso.');
          ativarPainel('login');
        }

        if (params.get('senha_redefinida') === '1') {
          mostrarMensagem('success', 'Senha redefinida com sucesso! Faça login.');
          ativarPainel('login');
        }

        const erro = params.get('erro');
        if (erro) {
          const texto = mensagensErro[erro] ?? 'Ocorreu um erro. Tente novamente.';
          mostrarMensagem('error', texto);

          if (['token_invalido', 'codigo_invalido', 'sessao_expirada'].includes(erro)) {
            ativarPainel('recuperar');
          } else if (['email_existente', 'senha_fraca', 'senha_diverge'].includes(erro)) {
            ativarPainel('cadastro');
          }
        }

        if (params.get('enviado') === '1') {
          mostrarMensagem('info',
            'Se este e-mail estiver cadastrado, você receberá as instruções em breve.'
          );
          ativarPainel('login');
        }

        // Etapas de redefinição (vindas de redefinir-senha.php via GET)
        const etapa = params.get('etapa');
        if (etapa === 'sms' || etapa === 'nova_senha') {
          document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
          document.querySelectorAll('.form-panel').forEach(p => p.classList.remove('active'));
          const alvo = etapa === 'sms' ? 'panel-codigo-sms' : 'panel-nova-senha';
          document.getElementById(alvo).classList.add('active');
        }

        if (params.toString()) {
          history.replaceState(null, '', location.pathname);
        }
      }

      processarUrl();

      // ── Validação client-side ─────────────────────────────────────────────────
      function validarSenha(v) {
        return /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/.test(v);
      }

      document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', e => {
          // Campos obrigatórios
          for (const input of form.querySelectorAll('input[required]')) {
            if (!input.checkValidity()) {
              input.focus();
              e.preventDefault();
              return;
            }
          }

          // Força da senha (cadastro)
          const senhaCad = form.querySelector('#senhaCadastro');
          if (senhaCad && !validarSenha(senhaCad.value)) {
            mostrarMensagem('error', mensagensErro['senha_fraca']);
            senhaCad.focus();
            e.preventDefault();
            return;
          }

          // Confirmação (cadastro)
          const confirma = form.querySelector('#confirma');
          if (senhaCad && confirma && senhaCad.value !== confirma.value) {
            mostrarMensagem('error', mensagensErro['senha_diverge']);
            confirma.focus();
            e.preventDefault();
            return;
          }

          // Força + confirmação (redefinição)
          const novaSenha  = form.querySelector('[name="nova_senha"]');
          const confirmaNova = form.querySelector('[name="confirma_senha"]');
          if (novaSenha) {
            if (!validarSenha(novaSenha.value)) {
              mostrarMensagem('error', mensagensErro['senha_fraca']);
              novaSenha.focus();
              e.preventDefault();
              return;
            }
            if (confirmaNova && novaSenha.value !== confirmaNova.value) {
              mostrarMensagem('error', mensagensErro['senha_diverge']);
              confirmaNova.focus();
              e.preventDefault();
              return;
            }
          }
        });
      });

    })();
    </script>
  </body>
</html>
