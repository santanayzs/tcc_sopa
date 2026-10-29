<?php

declare(strict_types=1);

session_start();
include __DIR__ . '/../criar-cardapio/conexao.php';

$modoDelivery = defined('SOPA_CARDAPIO_DELIVERY') && SOPA_CARDAPIO_DELIVERY;
$idCardapio = (int) ($_GET['id'] ?? 0);
$pedidoCsrf = $modoDelivery ? ($_SESSION['pedido_delivery_csrf'] ??= bin2hex(random_bytes(32))) : '';
$pedidoResultado = $modoDelivery ? ($_GET['pedido'] ?? '') : '';

$stmt = $conexao->prepare(
    'SELECT id, usuario_id, nome_restaurante, categoria, delivery_ativo, cor_primaria, cor_texto, cor_fundo_cardapio, cor_fundo_item, logo
     FROM cardapios
     WHERE id = ?'
);
$stmt->bind_param('i', $idCardapio);
$stmt->execute();
$cardapio = $stmt->get_result()->fetch_assoc();
$stmt->close();

$corPrimaria = $cardapio['cor_primaria'] ?? '#2f6b4f';
$corTexto    = $cardapio['cor_texto'] ?? '#1c1c1c';
$corFundoCardapio = $cardapio['cor_fundo_cardapio'] ?? '#f7f5f0';
$corFundoItem = $cardapio['cor_fundo_item'] ?? '#ffffff';

$itens = [];

if ($cardapio) {
    $stmtItens = $conexao->prepare(
        'SELECT id, nome, preco, disponivel, imagem
         FROM itens_cardapio
         WHERE cardapio_id = ?
         ORDER BY id'
    );
    $stmtItens->bind_param('i', $idCardapio);
    $stmtItens->execute();
    $itens = $stmtItens->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmtItens->close();
}
?>
<!doctype html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo $cardapio ? htmlspecialchars($cardapio['nome_restaurante'], ENT_QUOTES, 'UTF-8') . ($modoDelivery ? ' — Delivery' : ' — Cardápio') : 'Cardápio não encontrado'; ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700;800&family=Inter:wght@400;500;600&family=Cormorant+Garamond:wght@500;600&display=swap"
        rel="stylesheet" />

    <link rel="stylesheet" href="../../CSS/style.css" />
    <link rel="stylesheet" href="style-cardapio-publico.css" />

</head>

<body class="cardapio-publico-body" style="color: <?php echo htmlspecialchars($corTexto, ENT_QUOTES, 'UTF-8'); ?>; background: <?php echo htmlspecialchars($corFundoCardapio, ENT_QUOTES, 'UTF-8'); ?>; --cardapio-item-bg: <?php echo htmlspecialchars($corFundoItem, ENT_QUOTES, 'UTF-8'); ?>;">

    <?php if (!$cardapio): ?>
        <div class="cardapio-nao-encontrado">
            <h1>Cardápio não encontrado</h1>
            <p>O link pode estar incorreto ou o cardápio pode ter sido removido.</p>
        </div>
    <?php else: ?>
        <main class="cardapio-publico-shell">
            <div class="cardapio-publico-cabecalho">
                <?php if (!empty($cardapio['logo'])): ?>
                    <img class="cardapio-publico-logo"
                         src="../../uploads/logos/<?php echo htmlspecialchars($cardapio['logo'], ENT_QUOTES, 'UTF-8'); ?>"
                         alt="Logo do restaurante">
                <?php endif; ?>
                <h1 style="color: <?php echo htmlspecialchars($corPrimaria, ENT_QUOTES, 'UTF-8'); ?>;">
                    <?php echo htmlspecialchars($cardapio['nome_restaurante'], ENT_QUOTES, 'UTF-8'); ?>
                </h1>
                <?php if (!empty($cardapio['categoria'])): ?>
                    <span class="cardapio-publico-categoria" style="color: <?php echo htmlspecialchars($corPrimaria, ENT_QUOTES, 'UTF-8'); ?>; opacity: 1;">
                        <?php echo htmlspecialchars($cardapio['categoria'], ENT_QUOTES, 'UTF-8'); ?>
                    </span>
                <?php endif; ?>
                <?php if ($modoDelivery): ?>
                    <p class="delivery-cardapio-indicador">Delivery e pedidos online</p>
                <?php endif; ?>
            </div>

            <?php if ($modoDelivery && (int) $cardapio['delivery_ativo'] !== 1): ?>
                <p class="pedido-feedback pedido-feedback-erro" role="status">Este cardápio não está recebendo pedidos de delivery no momento.</p>
            <?php endif; ?>

            <?php if (empty($itens)): ?>
                <p style="text-align:center;">Nenhum item cadastrado neste cardápio ainda.</p>
            <?php else: ?>
                <?php if ($modoDelivery && $pedidoResultado === 'ok'): ?>
                    <p class="pedido-feedback pedido-feedback-ok" role="status">Pedido enviado. O estabelecimento já pode recebê-lo.</p>
                <?php elseif ($modoDelivery && $pedidoResultado === 'erro'): ?>
                    <p class="pedido-feedback pedido-feedback-erro" role="alert">Não foi possível enviar. Confira os dados e tente novamente.</p>
                <?php endif; ?>

                <?php if ($modoDelivery && (int) $cardapio['delivery_ativo'] === 1): ?>
                    <form class="pedido-delivery-form" action="registrarPedidoDelivery.php" method="post" id="pedido-delivery-form">
                        <input type="hidden" name="cardapio_id" value="<?php echo (int) $cardapio['id']; ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($pedidoCsrf, ENT_QUOTES, 'UTF-8'); ?>">
                <?php endif; ?>

                <div class="cardapio-itens">
                    <?php foreach ($itens as $item): ?>
                        <div class="cardapio-item <?php echo $item['disponivel'] ? '' : 'indisponivel'; ?>" style="background: <?php echo htmlspecialchars($corFundoItem, ENT_QUOTES, 'UTF-8'); ?>;">
                            <div class="cardapio-item-esquerda">
                                <?php if (!empty($item['imagem'])): ?>
                                    <button type="button" class="cardapio-imagem-trigger cardapio-item-thumb-trigger" data-full-src="../../uploads/itens/<?php echo htmlspecialchars($item['imagem'], ENT_QUOTES, 'UTF-8'); ?>" aria-label="Ampliar imagem do item <?php echo htmlspecialchars($item['nome'], ENT_QUOTES, 'UTF-8'); ?>">
                                        <img class="cardapio-item-thumb"
                                             src="../../uploads/itens/<?php echo htmlspecialchars($item['imagem'], ENT_QUOTES, 'UTF-8'); ?>"
                                             alt="Imagem do item <?php echo htmlspecialchars($item['nome'], ENT_QUOTES, 'UTF-8'); ?>">
                                    </button>
                                <?php endif; ?>
                                <span class="cardapio-item-nome">
                                    <?php echo htmlspecialchars($item['nome'], ENT_QUOTES, 'UTF-8'); ?>
                                    <?php if (!$item['disponivel']): ?>
                                        <span class="cardapio-item-badge">Indisponível</span>
                                    <?php endif; ?>
                                </span>
                            </div>
                            <span class="cardapio-item-preco" style="color: <?php echo htmlspecialchars($corTexto, ENT_QUOTES, 'UTF-8'); ?>;">
                                R$ <?php echo number_format((float) $item['preco'], 2, ',', '.'); ?>
                            </span>
                            <?php if ($modoDelivery && (int) $cardapio['delivery_ativo'] === 1 && (int) $item['disponivel'] === 1): ?>
                                <label class="pedido-quantidade">
                                    Qtd.
                                    <input type="number" name="quantidades[<?php echo (int) $item['id']; ?>]" min="0" max="30" value="0" data-preco="<?php echo htmlspecialchars((string) $item['preco'], ENT_QUOTES, 'UTF-8'); ?>" inputmode="numeric" aria-label="Quantidade de <?php echo htmlspecialchars($item['nome'], ENT_QUOTES, 'UTF-8'); ?>">
                                </label>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if ($modoDelivery && (int) $cardapio['delivery_ativo'] === 1): ?>
                    <section class="pedido-checkout" aria-labelledby="pedido-checkout-title">
                        <div class="pedido-checkout-heading">
                            <p class="cardapio-publico-categoria">Delivery</p>
                            <h2 id="pedido-checkout-title">Finalizar pedido</h2>
                        </div>
                        <div class="pedido-checkout-fields">
                            <label>Seu nome
                                <input name="nome_cliente" type="text" maxlength="150" autocomplete="name" required>
                            </label>
                            <label>Telefone
                                <input name="telefone_cliente" type="tel" maxlength="30" autocomplete="tel" required>
                            </label>
                            <label class="pedido-endereco">Endereço de entrega
                                <textarea name="endereco_entrega" rows="3" maxlength="500" autocomplete="street-address" required></textarea>
                            </label>
                            <label class="pedido-observacao">Observação
                                <textarea name="observacao" rows="2" maxlength="1000" placeholder="Complemento, referência ou instruções"></textarea>
                            </label>
                        </div>
                        <div class="pedido-checkout-footer">
                            <strong>Total estimado: <span id="pedido-total">R$ 0,00</span></strong>
                            <button type="submit" class="pedido-enviar" disabled>Enviar pedido</button>
                        </div>
                    </section>
                    </form>
                <?php endif; ?>
            <?php endif; ?>

            <p class="cardapio-publico-rodape" style="color: <?php echo htmlspecialchars($corPrimaria, ENT_QUOTES, 'UTF-8'); ?>; opacity: 1;">Cardápio via S.O.P.A.</p>
        </main>
    <?php endif; ?>

    <div class="cardapio-preview" id="cardapioPreview" aria-live="polite" aria-atomic="true" aria-hidden="true">
        <div class="cardapio-preview-backdrop" data-close-preview="true"></div>
        <div class="cardapio-preview-card" role="dialog" aria-modal="true" aria-label="Imagem do item em destaque">
            <button type="button" class="cardapio-preview-close" aria-label="Fechar imagem em destaque">×</button>
            <img id="cardapioPreviewImage" src="" alt="Imagem em destaque" />
        </div>
    </div>

    <script>
        const pedidoForm = document.getElementById('pedido-delivery-form');
        if (pedidoForm) {
            const pedidoTotal = document.getElementById('pedido-total');
            const enviarPedido = pedidoForm.querySelector('.pedido-enviar');
            const formatarMoeda = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' });

            pedidoForm.querySelectorAll('.pedido-quantidade input').forEach((input) => {
                input.addEventListener('input', () => {
                    let total = 0;
                    let temItens = false;
                    pedidoForm.querySelectorAll('.pedido-quantidade input').forEach((quantidade) => {
                        const unidades = Math.max(0, Math.min(30, Number(quantidade.value) || 0));
                        total += unidades * Number(quantidade.dataset.preco || 0);
                        temItens ||= unidades > 0;
                    });
                    pedidoTotal.textContent = formatarMoeda.format(total);
                    enviarPedido.disabled = !temItens;
                });
            });
        }

        const preview = document.getElementById('cardapioPreview');
        const previewImage = document.getElementById('cardapioPreviewImage');
        const triggers = document.querySelectorAll('.cardapio-imagem-trigger');

        function showPreview(src) {
            if (!preview || !previewImage) return;
            previewImage.src = src;
            preview.classList.add('visible');
            preview.setAttribute('aria-hidden', 'false');
        }

        function closePreview() {
            if (!preview || !previewImage) return;
            preview.classList.remove('visible');
            preview.setAttribute('aria-hidden', 'true');
            previewImage.src = '';
        }

        triggers.forEach((trigger) => {
            trigger.addEventListener('click', () => {
                const src = trigger.dataset.fullSrc;
                if (src) showPreview(src);
            });
        });

        document.querySelector('.cardapio-preview-close')?.addEventListener('click', closePreview);
        document.querySelector('[data-close-preview="true"]')?.addEventListener('click', closePreview);

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && preview && preview.classList.contains('visible')) {
                closePreview();
            }
        });
    </script>

</body>

</html>
