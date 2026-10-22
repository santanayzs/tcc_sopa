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
    'SELECT nome_restaurante FROM cardapios WHERE id = ? AND usuario_id = ?'
);
$stmt->bind_param('ii', $idCardapio, $usuarioId);
$stmt->execute();
$cardapio = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$cardapio) {
    header('Location: ver-cardapio.php?erro=naoencontrado');
    exit;
}

// ── Monta a URL pública (funciona em qualquer host/pasta onde o projeto esteja) ──
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host   = $_SERVER['HTTP_HOST'];
$pasta  = rtrim(str_replace('\\', '/', dirname($_SERVER['PHP_SELF'])), '/');
$urlPublica = $scheme . '://' . $host . $pasta . '/cardapio-publico.php?id=' . $idCardapio;
?>
<!doctype html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>QR Code do Cardápio — S.O.P.A.</title>
    <link href="../../img/icon.svg" rel="icon" class="icon">

    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700;800&family=Inter:wght@400;500;600&family=Cormorant+Garamond:wght@500;600&display=swap"
        rel="stylesheet" />

    <link rel="stylesheet" href="../../CSS/style.css" />
    <link rel="stylesheet" href="../../dashboard/dashboard.css" />
    <link rel="stylesheet" href="style-qrcode.css" />

    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

</head>

<body class="dashboard-page">
    <header class="site-header">
        <a href="../../dashboard/index.php" class="logo" aria-label="S.O.P.A. — voltar ao Painel">
            <img src="../../img/logotipoBranco.svg" alt="logotipo" class="logotipo" >    
        </a>

        <nav class="main-nav" id="main-nav">
            <a href="../../dashboard/index.php">Início</a>
            <a href="../../criar-cardapio/criar-cardapio.php">Criar Cardápio</a>
            <a href="../../auth/logout.php">Sair</a>
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

    <main class="dashboard-shell">
        <div class="dashboard-card qr-shell">
            <p class="dashboard-eyebrow">QR Code</p>
            <h1><?php echo htmlspecialchars($cardapio['nome_restaurante'], ENT_QUOTES, 'UTF-8'); ?></h1>
            <p class="dashboard-text">
                Escaneie para abrir o cardápio no celular, ou imprima este código pra colocar nas mesas.
            </p>

            <div id="qrcode"></div>

            <div class="qr-link-box">
                <input type="text" id="linkPublico" value="<?php echo htmlspecialchars($urlPublica, ENT_QUOTES, 'UTF-8'); ?>" readonly>
                <button type="button" class="btn-pill" onclick="copiarLink()">Copiar link</button>
            </div>
            <p class="qr-copiado" id="avisoCopiado"></p>

            <div class="dashboard-actions">
                <button type="button" class="btn-pill" onclick="baixarQrCode()">Baixar QR Code (PNG)</button>
                <a class="btn-pill" href="<?php echo htmlspecialchars($urlPublica, ENT_QUOTES, 'UTF-8'); ?>" target="_blank">
                    Ver cardápio público
                </a>
                <a class="btn-pill" href="ver-cardapio.php">Voltar</a>
            </div>
        </div>
    </main>

    <footer class="site-footer">
            <img src="../../img/logotipoBranco.svg" alt="logotipoFooter" class="logotipoFooter" >
        <p>Sistema Online de Pedidos e Atendimentos</p>
        <p class="footer-fine">
        &copy; 2026 S.O.P.A. — Projeto de TCC, ETEC João Gomes de Araújo.
      </p>
    </footer>
    
    <script>
        const urlPublica = <?php echo json_encode($urlPublica, JSON_UNESCAPED_SLASHES); ?>;
        const nomeArquivo = <?php echo json_encode('qrcode-' . preg_replace('/[^a-z0-9]+/i', '-', strtolower($cardapio['nome_restaurante']))); ?>;

        new QRCode(document.getElementById("qrcode"), {
            text: urlPublica,
            width: 240,
            height: 240,
            colorDark: "#000000",
            colorLight: "#ffffff",
            correctLevel: QRCode.CorrectLevel.H
        });

        function copiarLink() {
            const campo = document.getElementById("linkPublico");
            campo.select();
            campo.setSelectionRange(0, 99999);

            navigator.clipboard.writeText(urlPublica).then(() => {
                const aviso = document.getElementById("avisoCopiado");
                aviso.textContent = "Link copiado!";
                setTimeout(() => { aviso.textContent = ""; }, 2500);
            });
        }

        function baixarQrCode() {
            // A biblioteca desenha o QR Code num <canvas> dentro de #qrcode
            const canvas = document.querySelector("#qrcode canvas");
            if (!canvas) return;

            const link = document.createElement("a");
            link.download = nomeArquivo + ".png";
            link.href = canvas.toDataURL("image/png");
            link.click();
        }
    </script>
    
    <script src="../../JS/main-nav.js"></script>

</body>

</html>
