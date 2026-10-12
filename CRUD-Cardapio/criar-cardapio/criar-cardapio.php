<?php
session_start();

if (!isset($_SESSION['id'])) {
    header('Location: ../../auth/index.php');
    exit;
}

$nomeUsuario = $_SESSION['nome'] ?? 'Usuário';

$mensagensErro = [
    'campos'   => 'Preencha o nome do restaurante e adicione pelo menos um item.',
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

    <!-- FONTES -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700;800&family=Inter:wght@400;500;600&family=Cormorant+Garamond:wght@500;600&display=swap"
        rel="stylesheet" />

    <!-- CSS -->
    <link rel="stylesheet" href="../../CSS/style.css" />
    <link rel="stylesheet" href="style-criar.css" />

    <style>
        
    </style>
</head>

<body class="dashboard-page">
    <!-- HEADER -->
    <header class="site-header">
        <a href="../../index.html" class="logo">
            <span class="logo-badge">S</span>
            <span class="logo-word">S.O.P.A.</span>
        </a>

        <nav class="main-nav">
            <a href="../../index.html">Início</a>
            <a href="../../dashboard/index.php">Painel</a>
            <a href="../ver-cardapio/ver-cardapio.php">Ver Cardápio</a>
            <a href="../../auth/logout.php">Sair</a>
        </nav>
    </header>

    <!-- CONTEÚDO -->
    <main class="dashboard-shell">
        <div class="dashboard-card">
            <p class="dashboard-eyebrow">Área do Restaurante</p>
            <h1>Criar novo cardápio</h1>

            <p class="dashboard-text">
                Preencha as informações abaixo para montar o seu cardápio digital.
                Você poderá adicionar itens, preços, e uma foto para cada prato.
            </p>

            <?php if ($erro): ?>
                <p style="background:#f8e5e5;color:#9b1c1c;padding:10px 14px;border-radius:10px;font-weight:600;">
                    <?php echo htmlspecialchars($erro, ENT_QUOTES, 'UTF-8'); ?>
                </p>
            <?php endif; ?>

            <!-- FORMULÁRIO -->
            <form class="cardapio-form" action="salvarCardapio.php" method="POST" enctype="multipart/form-data" onsubmit="return prepararEnvio()">
                <!-- Nome do restaurante -->
                <div class="form-group">
                    <label>Nome do Restaurante</label>
                    <input type="text" name="nome_restaurante" placeholder="Ex: Restaurante do João" required>
                </div>

                <!-- Categoria -->
                <div class="form-group">
                    <label>Categoria</label>
                    <input type="text" name="categoria" placeholder="Ex: Lanches, Bebidas...">
                </div>

                <div class="itens-box">

                    <!-- Itens -->
                    <h3>Adicionar Item</h3>

                    <div class="item-inputs">
                        <input type="text" id="nomeItem" placeholder="Nome do item">

                        <input type="number" id="precoItem" placeholder="Preço" step="0.01">

                        <input type="file" id="imagemItem" accept="image/png, image/jpeg, image/webp" title="Foto do item (opcional)">

                        <button type="button" class="btn-add" onclick="adicionarItem()">
                            Adicionar
                        </button>
                    </div>

                    <p style="font-size:0.78rem; color:var(--cream-2); margin: 4px 0 14px;">
                        A foto é opcional, até 2MB (JPG, PNG ou WEBP).
                    </p>

                    <div id="listaItens"></div>

                </div>

                <button type="submit">
                    Salvar Cardápio
                </button>
            </form>
        </div>
    </main>

    <!-- FOOTER -->
    <footer class="site-footer">
        <div class="logo">
            <span class="logo-badge">S</span>
            <span class="logo-word">S.O.P.A.</span>
        </div>
        <p>Sistema Online de Pedidos e Atendimentos</p>
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

            if (!nome.value || !preco.value) return;

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
                linha.appendChild(imagemInput);

                // Cria um novo input de arquivo "limpo" no lugar, pro próximo item
                const container = document.querySelector('.item-inputs');
                const novoInput = document.createElement('input');
                novoInput.type = 'file';
                novoInput.id = 'imagemItem';
                novoInput.accept = 'image/png, image/jpeg, image/webp';
                novoInput.title = 'Foto do item (opcional)';
                container.insertBefore(novoInput, container.querySelector('.btn-add'));
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

</body>

</html>
