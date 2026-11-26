<?php
session_start();
require_once __DIR__ . '/../../configs/perfis.php';
exigirAcessoEstabelecimento();

if (!isset($_SESSION['id'])) {
    header('Location: ../../auth/index.php');
    exit;
}

$nomeUsuario = $_SESSION['nome'] ?? 'Usuário';

$mensagensErro = [
    'campos'   => 'Preencha o nome do restaurante e adicione pelo menos um item.',
    'preco'    => 'Os preços dos itens não podem ser negativos. Revise o cardápio e tente novamente.',
    'formato'  => 'Uma das imagens enviadas não é válida (use JPG, PNG ou WEBP, até 2MB).',
    'servidor' => 'Erro ao salvar o cardápio. Tente novamente em instantes.',
];
$erro = $mensagensErro[$_GET['erro'] ?? ''] ?? null;
?>
<!doctype html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Criar Cardápio — S.O.P.A.</title>
    <link href="../../img/icon.svg" rel="icon" class="icon">
    
    <!-- FONTES -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700;800&family=Inter:wght@400;500;600&family=Cormorant+Garamond:wght@500;600&display=swap"
        rel="stylesheet" />

    <!-- CSS -->
    <link rel="stylesheet" href="../../CSS/style.css" />
    <link rel="stylesheet" href="../../dashboard/dashboard.css" />
    <link rel="stylesheet" href="style-criar.css" />

    <style>
        
    </style>
</head>

<body class="dashboard-page">
    <!-- HEADER -->
    <header class="site-header">
        <a href="../../dashboard/index.php" class="logo" aria-label="S.O.P.A. — voltar ao Painel">
            <img src="../../img/logotipoBranco.svg" alt="S.O.P.A." class="logotipo">
        </a>

        <nav class="main-nav" id="main-nav">
            <a href="../../dashboard/index.php">Início</a>
            <a href="criar-cardapio.php">Criar Cardápio</a>
            <a href="../ver-cardapio/ver-cardapio.php">Ver Cardápio</a>
            <a href="../../comandas/comandas.php">Comandas</a>
            <a href="../../dashboard/gerenciar-delivery.php">Gerenciar Delivery</a>
            <a href="../../dashboard/pedidos-delivery.php">Pedidos Delivery</a>
            <a href="../../auth/logout.php" class="btn-sair">Sair</a>
        </nav>

         <button
        class="nav-toggle"
        id="nav-toggle"
        aria-label="Abrir menu"
        aria-expanded="false"
        aria-controls="main-nav"
      >
        <span></span><span></span><span></span>
      </button>
    </header>

    <!-- CONTEÚDO -->
    <main class="dashboard-shell">
        <div class="dashboard-card">
            <a class="btn-voltar" href="../../dashboard/index.php">Voltar</a>

            <div class="menu-page-heading">
                <h1>Criar novo cardápio</h1>
                <p>Preencha as informações abaixo para montar o seu cardápio digital</p>
            </div>

            <?php if ($erro): ?>
                <p style="background:#f8e5e5;color:#9b1c1c;padding:10px 14px;border-radius:10px;font-weight:600;">
                    <?php echo htmlspecialchars($erro, ENT_QUOTES, 'UTF-8'); ?>
                </p>
            <?php endif; ?>

            <!-- FORMULÁRIO -->
            <form class="cardapio-form" action="salvarCardapio.php" method="POST" enctype="multipart/form-data" onsubmit="return prepararEnvio()">
                <section class="form-group-area" aria-labelledby="informacoes-cardapio">
                    <h2 id="informacoes-cardapio">Informações do cardápio</h2>
                    <div class="form-group">
                        <label for="nomeRestaurante">Nome do Restaurante</label>
                        <input id="nomeRestaurante" type="text" name="nome_restaurante" placeholder="Nome do Restaurante" required>
                    </div>

                    <div class="form-group">
                        <label for="categoria">Categoria</label>
                        <input id="categoria" type="text" name="categoria" placeholder="Categoria">
                    </div>
                </section>

                <div class="itens-box">
                    <h2>Adicionar item</h2>

                    <div class="item-inputs">
                        <input type="text" id="nomeItem" placeholder="Nome do item" aria-label="Nome do item">
                        <input type="number" id="precoItem" placeholder="Preço" step="0.01" min="0" aria-label="Preço">
                        <input class="file-input-hidden" type="file" id="imagemItem" accept="image/png, image/jpeg, image/webp" title="Foto do item (opcional)">
                        <label class="btn-upload" for="imagemItem">Anexar imagem</label>

                        <button type="button" class="btn-add" onclick="adicionarItem()">
                            Adicionar item
                        </button>
                        <button type="submit" class="btn-save">Salvar Cardápio</button>
                    </div>

                    <p class="upload-help">
                        Foto opcional, até 2MB (JPG, PNG ou WEBP).
                    </p>
                </div>

                <section class="lista-itens-box" aria-labelledby="titulo-lista-itens">
                    <h2 id="titulo-lista-itens">Itens</h2>
                    <div id="listaItens"></div>
                </section>
            </form>
        </div>
    </main>

    <!-- FOOTER -->
    <footer class="site-footer">
        <img src="../../img/logotipoBranco.svg" alt="S.O.P.A." class="logotipoFooter">
        <p>Sistema Online de Pedidos e Atendimentos</p>
        <p class="footer-fine">
        &copy; 2026 S.O.P.A. — Projeto de TCC, ETEC João Gomes de Araújo.
      </p>
    </footer>

    <script>
        let contadorItem = 0;

        function criarCampoHidden(name, value) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = name;
            input.value = value;
            return input;
        }

        // Monta o "esqueleto" comum de uma linha de item (nome, preço, miniatura, botão de remover)
        function montarLinhaBase(idx, nome, preco) {
            const linha = document.createElement('div');
            linha.className = 'item-linha';

            linha.appendChild(criarCampoHidden(`itens[${idx}][nome]`, nome));
            linha.appendChild(criarCampoHidden(`itens[${idx}][preco]`, preco));

            const thumb = document.createElement('img');
            thumb.className = 'item-thumb';
            thumb.style.display = 'none';
            linha.appendChild(thumb);

            const info = document.createElement('div');
            info.className = 'item-linha-info';

            const spanNome = document.createElement('span');
            spanNome.className = 'item-linha-nome';
            spanNome.textContent = nome;

            const spanPreco = document.createElement('span');
            spanPreco.className = 'item-linha-preco';
            spanPreco.textContent = 'R$ ' + parseFloat(preco).toFixed(2);

            info.appendChild(spanNome);
            info.appendChild(spanPreco);
            linha.appendChild(info);

            const btnRemover = document.createElement('button');
            btnRemover.type = 'button';
            btnRemover.className = 'btn-remover-item';
            btnRemover.textContent = '✕';
            btnRemover.onclick = () => linha.remove();
            linha.appendChild(btnRemover);

            return { linha, thumb };
        }

        function adicionarItem() {
            const nome = document.getElementById("nomeItem");
            const preco = document.getElementById("precoItem");
            const imagemInput = document.getElementById("imagemItem");

            if (!nome.value.trim() || !preco.value) return;

            if (!Number.isFinite(Number(preco.value)) || Number(preco.value) < 0) {
                alert("O preço não pode ser negativo.");
                preco.focus();
                return;
            }

            const idx = contadorItem++;
            const { linha, thumb } = montarLinhaBase(idx, nome.value, preco.value);

            if (imagemInput.files && imagemInput.files.length > 0) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    thumb.src = e.target.result;
                    thumb.style.display = 'block';
                };
                reader.readAsDataURL(imagemInput.files[0]);

                // Move o próprio input (com o arquivo já selecionado) pra dentro da linha do item
                imagemInput.name = `itens[${idx}][imagem]`;
                imagemInput.style.display = 'none';
                imagemInput.removeAttribute('id');
                linha.appendChild(imagemInput);

                // Cria um novo input de arquivo "limpo" no lugar, pro próximo item
                const container = document.querySelector('.item-inputs');
                const novoInput = document.createElement('input');
                novoInput.type = 'file';
                novoInput.id = 'imagemItem';
                novoInput.className = 'file-input-hidden';
                novoInput.accept = 'image/png, image/jpeg, image/webp';
                novoInput.title = 'Foto do item (opcional)';
                container.insertBefore(novoInput, container.querySelector('.btn-upload'));
            }

            document.getElementById('listaItens').appendChild(linha);

            nome.value = "";
            preco.value = "";
        }

        function prepararEnvio() {
            const lista = document.getElementById('listaItens');
            if (lista.children.length === 0) {
                alert("Adicione pelo menos um item ao cardápio.");
                return false;
            }
            return true;
        }
    </script>

<script src="../../JS/main-nav.js"></script>
</body>

</html>
