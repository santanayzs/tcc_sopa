<?php

declare(strict_types=1);

session_start();

// ── Exige login ────────────────────────────────────────────────────────────
if (!isset($_SESSION['id'])) {
    header('Location: ../../auth/index.php');
    exit;
}

include '../criar-cardapio/conexao.php';

$usuarioId  = (int) $_SESSION['id'];
$idCardapio = (int) ($_GET['id'] ?? 0);

// ── Confirma que o cardápio existe e pertence ao usuário logado ──────────
$stmt = $conexao->prepare(
    'SELECT id, nome_restaurante, cor_primaria, cor_texto, cor_fundo_cardapio, cor_fundo_item, logo
     FROM cardapios
     WHERE id = ? AND usuario_id = ?'
);
$stmt->bind_param('ii', $idCardapio, $usuarioId);
$stmt->execute();
$cardapio = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$cardapio) {
    header('Location: ../ver-cardapio/ver-cardapio.php?erro=naoencontrado');
    exit;
}

$corPrimaria = $cardapio['cor_primaria'] ?: '#2f6b4f';
$corTexto    = $cardapio['cor_texto'] ?: '#1c1c1c';
$corFundoCardapio = $cardapio['cor_fundo_cardapio'] ?: '#f7f5f0';
$corFundoItem = $cardapio['cor_fundo_item'] ?: '#ffffff';

$mensagensErro = [
    'formato'  => 'A imagem precisa ser JPG, PNG ou WEBP (até 2MB).',
    'servidor' => 'Erro ao salvar a personalização. Tente novamente.',
];
$erro = $mensagensErro[$_GET['erro'] ?? ''] ?? null;
?>
<!doctype html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Personalizar Cardápio — S.O.P.A.</title>

    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700;800&family=Inter:wght@400;500;600&family=Cormorant+Garamond:wght@500;600&display=swap"
        rel="stylesheet" />

    <link rel="stylesheet" href="../../CSS/style.css" />

    <style>
        .personalizar-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 32px;
            margin-top: 28px;
            align-items: start;
        }

        @media (max-width: 760px) {
            .personalizar-grid {
                grid-template-columns: 1fr;
            }
        }

        .campo-cor {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 20px;
        }

        .campo-cor label {
            flex: 1;
            font-weight: 500;
        }

        .campo-cor input[type="color"] {
            width: 52px;
            height: 40px;
            border: none;
            border-radius: 10px;
            background: none;
            cursor: pointer;
            padding: 0;
        }

        .logo-atual {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 16px;
        }

        .logo-atual img {
            width: 64px;
            height: 64px;
            object-fit: contain;
            background: #fff;
            border-radius: 10px;
            padding: 6px;
        }

        .preview-box {
            background: #ffffff;
            border-radius: var(--radius-md);
            padding: 28px 24px;
            text-align: center;
            transition: background-color 0.2s ease;
        }

        #previewLogo {
            max-width: 90px;
            max-height: 90px;
            object-fit: contain;
            margin: 0 auto 14px;
            display: block;
        }

        #previewNome {
            font-family: var(--font-display);
            font-size: 1.5rem;
            font-weight: 700;
            margin: 0 0 18px;
        }

        .preview-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 14px;
            border-radius: 10px;
            background: #f4f4f2;
            margin-bottom: 8px;
            font-family: var(--font-body);
        }

        .preview-item span:last-child {
            font-weight: 700;
        }
    </style>
</head>

<body class="dashboard-page">
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

    <main class="dashboard-shell">
        <div class="dashboard-card">
            <p class="dashboard-eyebrow">Identidade Visual</p>
            <h1>Personalizar "<?php echo htmlspecialchars($cardapio['nome_restaurante'], ENT_QUOTES, 'UTF-8'); ?>"</h1>
            <p class="dashboard-text">
                Escolha as cores e a logo que vão aparecer no cardápio público e na versão para impressão.
            </p>

            <?php if ($erro): ?>
                <p style="background:#f8e5e5;color:#9b1c1c;padding:10px 14px;border-radius:10px;font-weight:600;">
                    <?php echo htmlspecialchars($erro, ENT_QUOTES, 'UTF-8'); ?>
                </p>
            <?php endif; ?>

            <div class="personalizar-grid">
                <form action="salvarPersonalizacao.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="id" value="<?php echo (int) $cardapio['id']; ?>">

                    <div class="campo-cor">
                        <label for="corPrimaria">Cor principal (títulos e destaques)</label>
                        <input type="color" id="corPrimaria" name="cor_primaria" value="<?php echo htmlspecialchars($corPrimaria, ENT_QUOTES, 'UTF-8'); ?>">
                    </div>

                    <div class="campo-cor">
                        <label for="corTexto">Cor do texto e do preço</label>
                        <input type="color" id="corTexto" name="cor_texto" value="<?php echo htmlspecialchars($corTexto, ENT_QUOTES, 'UTF-8'); ?>">
                    </div>

                    <div class="campo-cor">
                        <label for="corFundoCardapio">Fundo do cardápio</label>
                        <input type="color" id="corFundoCardapio" name="cor_fundo_cardapio" value="<?php echo htmlspecialchars($corFundoCardapio, ENT_QUOTES, 'UTF-8'); ?>">
                    </div>

                    <div class="campo-cor">
                        <label for="corFundoItem">Fundo do item do cardápio</label>
                        <input type="color" id="corFundoItem" name="cor_fundo_item" value="<?php echo htmlspecialchars($corFundoItem, ENT_QUOTES, 'UTF-8'); ?>">
                    </div>

                    <div class="form-group">
                        <label>Logo do estabelecimento</label>

                        <?php if (!empty($cardapio['logo'])): ?>
                            <div class="logo-atual">
                                <img src="../uploads/logos/<?php echo htmlspecialchars($cardapio['logo'], ENT_QUOTES, 'UTF-8'); ?>" alt="Logo atual">
                                <label style="font-weight:400; font-size:0.85rem;">
                                    <input type="checkbox" name="remover_logo" value="1"> Remover logo atual
                                </label>
                            </div>
                        <?php endif; ?>

                        <input type="file" id="logoInput" name="logo" accept="image/png, image/jpeg, image/webp">
                        <p style="font-size:0.78rem; color:var(--cream-2); margin-top:6px;">JPG, PNG ou WEBP, até 2MB.</p>
                    </div>

                    <button type="submit">Salvar Personalização</button>
                </form>

                <div class="preview-box" id="previewBox" style="background: <?php echo htmlspecialchars($corFundoCardapio, ENT_QUOTES, 'UTF-8'); ?>;">
                    <p style="font-size:0.75rem; text-transform:uppercase; letter-spacing:0.05em; color:#8a8a86; margin-bottom:14px;">
                        Pré-visualização
                    </p>
                    <img id="previewLogo"
                         src="<?php echo !empty($cardapio['logo']) ? htmlspecialchars('../uploads/logos/' . $cardapio['logo'], ENT_QUOTES, 'UTF-8') : ''; ?>"
                         style="<?php echo empty($cardapio['logo']) ? 'display:none;' : ''; ?>">
                    <h2 id="previewNome" style="color: <?php echo htmlspecialchars($corPrimaria, ENT_QUOTES, 'UTF-8'); ?>;">
                        <?php echo htmlspecialchars($cardapio['nome_restaurante'], ENT_QUOTES, 'UTF-8'); ?>
                    </h2>
                    <div class="preview-item" id="previewItem" style="color: <?php echo htmlspecialchars($corTexto, ENT_QUOTES, 'UTF-8'); ?>; background: <?php echo htmlspecialchars($corFundoItem, ENT_QUOTES, 'UTF-8'); ?>;">
                        <span>Prato exemplo</span>
                        <span id="previewPreco" style="color: <?php echo htmlspecialchars($corTexto, ENT_QUOTES, 'UTF-8'); ?>;">R$ 29,90</span>
                    </div>
                </div>
            </div>

            <div class="dashboard-actions">
                <a class="btn-pill" href="../ver-cardapio/ver-cardapio.php">Voltar</a>
            </div>
        </div>
    </main>

    <footer class="site-footer">
        <div class="logo">
            <span class="logo-badge">S</span>
            <span class="logo-word">S.O.P.A.</span>
        </div>
        <p>Sistema Online de Pedidos e Atendimentos</p>
    </footer>

    <script>
        const corPrimaria = document.getElementById('corPrimaria');
        const corTexto = document.getElementById('corTexto');
        const corFundoCardapio = document.getElementById('corFundoCardapio');
        const corFundoItem = document.getElementById('corFundoItem');
        const previewNome = document.getElementById('previewNome');
        const previewPreco = document.getElementById('previewPreco');
        const previewItem = document.getElementById('previewItem');
        const previewBox = document.getElementById('previewBox');
        const logoInput = document.getElementById('logoInput');
        const previewLogo = document.getElementById('previewLogo');

        corPrimaria.addEventListener('input', () => {
            previewNome.style.color = corPrimaria.value;
        });

        corTexto.addEventListener('input', () => {
            previewItem.style.color = corTexto.value;
            previewPreco.style.color = corTexto.value;
        });

        corFundoCardapio.addEventListener('input', () => {
            previewBox.style.background = corFundoCardapio.value;
        });

        corFundoItem.addEventListener('input', () => {
            previewItem.style.background = corFundoItem.value;
        });

        logoInput.addEventListener('change', () => {
            const arquivo = logoInput.files[0];
            if (!arquivo) return;

            const leitor = new FileReader();
            leitor.onload = (e) => {
                previewLogo.src = e.target.result;
                previewLogo.style.display = 'block';
            };
            leitor.readAsDataURL(arquivo);
        });
    </script>
</body>

</html>
