<?php

declare(strict_types=1);

session_start();

// ── Exige login ────────────────────────────────────────────────────────────
if (!isset($_SESSION['id'])) {
    header('Location: ../../auth/index.php');
    exit;
}

include '../criar-cardapio/conexao.php';

$usuarioId = (int) $_SESSION['id'];
$idCardapio = (int) ($_GET['id'] ?? 0);

// ── Busca o cardápio, garantindo que pertence ao usuário logado ──────────
$stmt = $conexao->prepare(
    'SELECT id, nome_restaurante, categoria
     FROM cardapios
     WHERE id = ? AND usuario_id = ?'
);
$stmt->bind_param('ii', $idCardapio, $usuarioId);
$stmt->execute();
$cardapio = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Cardápio não existe ou não é do usuário logado → volta pra listagem
if (!$cardapio) {
    header('Location: ../ver-cardapio/ver-cardapio.php?erro=naoencontrado');
    exit;
}

// ── Busca os itens atuais do cardápio (incluindo a imagem) ────────────────
$stmtItens = $conexao->prepare(
    'SELECT nome, preco, imagem FROM itens_cardapio WHERE cardapio_id = ? ORDER BY id'
);
$stmtItens->bind_param('i', $idCardapio);
$stmtItens->execute();
$itensAtuais = $stmtItens->get_result()->fetch_all(MYSQLI_ASSOC);
$stmtItens->close();

$mensagensErro = [
    'campos'   => 'Preencha o nome do restaurante e adicione pelo menos um item.',
    'formato'  => 'Uma das imagens enviadas não é válida (use JPG, PNG ou WEBP, até 2MB).',
    'servidor' => 'Erro ao atualizar o cardápio. Tente novamente em instantes.',
];
$erro = $mensagensErro[$_GET['erro'] ?? ''] ?? null;
?>
<!doctype html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Editar Cardápio — S.O.P.A.</title>

    <!-- FONTES -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700;800&family=Inter:wght@400;500;600&family=Cormorant+Garamond:wght@500;600&display=swap"
        rel="stylesheet" />

    <!-- CSS -->
    <link rel="stylesheet" href="../../CSS/style.css" />
    <link rel="stylesheet" href="style-editar.css" />

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
            <h1>Editar cardápio</h1>

            <p class="dashboard-text">
                Altere as informações do seu cardápio abaixo. Ao salvar, a lista de itens é substituída pela nova.
            </p>

            <?php if ($erro): ?>
                <p style="background:#f8e5e5;color:#9b1c1c;padding:10px 14px;border-radius:10px;font-weight:600;">
                    <?php echo htmlspecialchars($erro, ENT_QUOTES, 'UTF-8'); ?>
                </p>
            <?php endif; ?>

            <!-- FORMULÁRIO -->
            <form class="cardapio-form" action="atualizarCardapio.php" method="POST" enctype="multipart/form-data" onsubmit="return prepararEnvio()">
                <input type="hidden" name="id" value="<?php echo (int) $cardapio['id']; ?>">

                <!-- Nome do restaurante -->
                <div class="form-group">
                    <label>Nome do Restaurante</label>
                    <input type="text" name="nome_restaurante" placeholder="Ex: Restaurante do João" required
                        value="<?php echo htmlspecialchars($cardapio['nome_restaurante'], ENT_QUOTES, 'UTF-8'); ?>">
                </div>

                <!-- Categoria -->
                <div class="form-group">
                    <label>Categoria</label>
                    <input type="text" name="categoria" placeholder="Ex: Lanches, Bebidas..."
                        value="<?php echo htmlspecialchars($cardapio['categoria'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                </div>

                <div class="itens-box">

                    <!-- Itens -->
                    <h3>Itens do cardápio</h3>

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
                    Salvar Alterações
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
        // Itens já cadastrados desse cardápio (vindos do PHP)
        const itensExistentes = <?php echo json_encode($itensAtuais, JSON_UNESCAPED_UNICODE); ?>;

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

        // Adiciona um item NOVO (a partir dos campos de "Adicionar Item")
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

                imagemInput.name = `itens[${idx}][imagem]`;
                imagemInput.style.display = 'none';
                linha.appendChild(imagemInput);

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

        // Carrega os itens JÁ EXISTENTES desse cardápio, com opção de trocar/remover a foto
        function carregarItensExistentes() {
            itensExistentes.forEach((item) => {
                const idx = contadorItem++;
                const { linha, thumb } = montarLinhaBase(idx, item.nome, item.preco);

                // Guarda o nome do arquivo atual, pra manter caso o usuário não mexa na imagem
                linha.appendChild(criarCampoHidden(`itens[${idx}][imagem_atual]`, item.imagem || ''));

                if (item.imagem) {
                    thumb.src = '../../uploads/itens/' + item.imagem;
                    thumb.style.display = 'block';

                    const labelRemover = document.createElement('label');
                    labelRemover.className = 'item-remover-imagem';

                    const chkRemover = document.createElement('input');
                    chkRemover.type = 'checkbox';
                    chkRemover.name = `itens[${idx}][remover_imagem]`;
                    chkRemover.value = '1';
                    chkRemover.onchange = () => {
                        thumb.style.display = chkRemover.checked ? 'none' : 'block';
                    };

                    labelRemover.appendChild(chkRemover);
                    labelRemover.appendChild(document.createTextNode(' Remover foto'));
                    linha.appendChild(labelRemover);
                }

                const labelTrocar = document.createElement('label');
                labelTrocar.className = 'item-trocar-imagem';

                const inputTrocar = document.createElement('input');
                inputTrocar.type = 'file';
                inputTrocar.accept = 'image/png, image/jpeg, image/webp';
                inputTrocar.name = `itens[${idx}][imagem]`;
                inputTrocar.onchange = () => {
                    if (inputTrocar.files && inputTrocar.files[0]) {
                        const reader = new FileReader();
                        reader.onload = (e) => {
                            thumb.src = e.target.result;
                            thumb.style.display = 'block';
                        };
                        reader.readAsDataURL(inputTrocar.files[0]);
                    }
                };

                labelTrocar.appendChild(inputTrocar);
                labelTrocar.appendChild(document.createTextNode(item.imagem ? ' Trocar foto' : ' Adicionar foto'));
                linha.appendChild(labelTrocar);

                document.getElementById('listaItens').appendChild(linha);
            });
        }

        function prepararEnvio() {
            const lista = document.getElementById('listaItens');
            if (lista.children.length === 0) {
                alert("Adicione pelo menos um item ao cardápio.");
                return false;
            }
            return true;
        }

        // Mostra os itens já cadastrados assim que a página carrega
        carregarItensExistentes();
    </script>

</body>

</html>
