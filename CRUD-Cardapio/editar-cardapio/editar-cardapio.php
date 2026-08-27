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

// ── Busca os itens atuais do cardápio ─────────────────────────────────────
$stmtItens = $conexao->prepare(
    'SELECT nome, preco FROM itens_cardapio WHERE cardapio_id = ? ORDER BY id'
);
$stmtItens->bind_param('i', $idCardapio);
$stmtItens->execute();
$itensAtuais = $stmtItens->get_result()->fetch_all(MYSQLI_ASSOC);
$stmtItens->close();

$mensagensErro = [
    'campos'   => 'Preencha o nome do restaurante e adicione pelo menos um item.',
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
            <form class="cardapio-form" action="atualizarCardapio.php" method="POST" onsubmit="return prepararEnvio()">
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

                        <button type="button" class="btn-add" onclick="adicionarItem()">
                            Adicionar
                        </button>
                    </div>

                    <div id="listaItens"></div>

                </div>

                <button type="submit">
                    Salvar Alterações
                </button>

                <input type="hidden" id="itensJson" name="itens">
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
        // Pré-carrega os itens já cadastrados desse cardápio
        let itens = <?php echo json_encode($itensAtuais, JSON_UNESCAPED_UNICODE); ?>;

        function adicionarItem() {

            const nome = document.getElementById("nomeItem");
            const preco = document.getElementById("precoItem");

            if (!nome.value || !preco.value) return;

            itens.push({
                nome: nome.value,
                preco: preco.value
            });

            atualizarLista();

            nome.value = "";
            preco.value = "";
        }


        function atualizarLista() {

            const lista = document.getElementById("listaItens");

            if (!lista) return;

            lista.innerHTML = "";

            itens.forEach((item, index) => {

                lista.innerHTML += `
            <div class="item-linha">

                <span>${item.nome}</span>

                <span>
                    R$ ${parseFloat(item.preco).toFixed(2)}
                </span>

                <button type="button" onclick="remover(${index})">
                    ✕
                </button>

            </div>
        `;
            });
        }

        function prepararEnvio() {

            if (itens.length === 0) {
                alert("Adicione pelo menos um item ao cardápio.");
                return false;
            }

            document.getElementById("itensJson").value =
                JSON.stringify(itens);

            return true;
        }

        function remover(index) {

            itens.splice(index, 1);

            atualizarLista();

        }

        // Mostra os itens já cadastrados assim que a página carrega
        atualizarLista();
    </script>

</body>

</html>
