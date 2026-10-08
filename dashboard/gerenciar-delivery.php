<?php
require_once __DIR__ . '/../configs/session_helper.php';

if (!isset($_SESSION['id'])) {
    header('Location: ../auth/index.php');
    exit;
}

include __DIR__ . '/../CRUD-Cardapio/criar-cardapio/conexao.php';

$usuarioId = (int) $_SESSION['id'];
$deliveryCsrf = $_SESSION['delivery_csrf'] ??= bin2hex(random_bytes(32));
$deliverySalvo = ($_GET['delivery'] ?? '') === 'salvo';
$deliveryErro = ($_GET['delivery'] ?? '') === 'erro';
$locationErro = ($_GET['delivery'] ?? '') === 'localizacao';

$stmtEstabelecimento = $conexao->prepare(
  'SELECT cep, logradouro, numero, bairro, cidade, estado, latitude, longitude
   FROM estabelecimentos
   WHERE usuario_id = ?
   ORDER BY id
   LIMIT 1'
);
$stmtEstabelecimento->bind_param('i', $usuarioId);
$stmtEstabelecimento->execute();
$estabelecimento = $stmtEstabelecimento->get_result()->fetch_assoc() ?: [];
$stmtEstabelecimento->close();

$localizacaoGps = isset($estabelecimento['latitude'], $estabelecimento['longitude']);
$tipoLocalizacao = $localizacaoGps ? 'gps' : 'endereco';

$stmtDelivery = $conexao->prepare(
    'SELECT id, nome_restaurante, categoria, delivery_ativo
     FROM cardapios
     WHERE usuario_id = ?
     ORDER BY nome_restaurante'
);
$stmtDelivery->bind_param('i', $usuarioId);
$stmtDelivery->execute();
$deliveryCardapios = $stmtDelivery->get_result()->fetch_all(MYSQLI_ASSOC);
$stmtDelivery->close();
?>
<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Gerenciar Delivery — S.O.P.A.</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link
    href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700;800&family=Inter:wght@400;500;600&family=Cormorant+Garamond:wght@500;600&display=swap"
    rel="stylesheet"
  />
  <link rel="stylesheet" href="../CSS/style.css" />
  <link rel="stylesheet" href="delivery.css" />
</head>
<body class="dashboard-page">
  <header class="site-header">
    <a href="../index.html" class="logo" aria-label="S.O.P.A. — voltar ao início">
      <span class="logo-badge">S</span>
      <span class="logo-word">S.O.P.A.</span>
    </a>
    <nav class="main-nav">
      <a href="index.php">Painel</a>
      <a href="../Delivery/index.php">Ver delivery</a>
      <a href="../auth/logout.php">Sair</a>
    </nav>
  </header>

  <main class="dashboard-shell">
    <section class="dashboard-card dashboard-delivery" aria-labelledby="delivery-title">
      <p class="dashboard-eyebrow">Vitrine pública</p>
      <h1 id="delivery-title">Gerenciar delivery</h1>
      <p class="dashboard-text">
        Selecione quais dos seus cardápios já cadastrados serão exibidos para os clientes.
      </p>

      <?php if ($deliverySalvo): ?>
        <p class="delivery-message delivery-message-success" role="status">Disponibilidade do delivery atualizada.</p>
      <?php elseif ($deliveryErro): ?>
        <p class="delivery-message delivery-message-error" role="alert">Não foi possível salvar. Atualize a página e tente novamente.</p>
      <?php elseif ($locationErro): ?>
        <p class="delivery-message delivery-message-error" role="alert">Informe um endereço completo ou capture a localização GPS do estabelecimento.</p>
      <?php endif; ?>

      <?php if (empty($estabelecimento)): ?>
        <p class="delivery-message delivery-message-error" role="alert">Não foi encontrado um estabelecimento vinculado a esta conta.</p>
      <?php else: ?>
        <form class="delivery-settings-form" action="salvar-delivery.php" method="post" id="delivery-settings-form">
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($deliveryCsrf, ENT_QUOTES, 'UTF-8'); ?>">

          <section class="delivery-location-settings" aria-labelledby="location-title">
            <h2 id="location-title">Localização do estabelecimento</h2>
            <p>Cadastre o endereço da loja ou use o GPS do próprio estabelecimento.</p>

            <div class="delivery-location-methods">
              <label class="delivery-method-option">
                <input type="radio" name="location_type" value="gps" <?php echo $tipoLocalizacao === 'gps' ? 'checked' : ''; ?>>
                <span>Usar GPS</span>
              </label>
              <label class="delivery-method-option">
                <input type="radio" name="location_type" value="endereco" <?php echo $tipoLocalizacao === 'endereco' ? 'checked' : ''; ?>>
                <span>Informar endereço</span>
              </label>
            </div>

            <div class="delivery-gps-control" id="delivery-gps-control">
              <button class="btn-mini" type="button" id="capture-business-location">Capturar localização GPS</button>
              <span id="business-location-status" role="status">
                <?php echo $localizacaoGps ? 'GPS do estabelecimento já cadastrado.' : 'Permita o acesso à localização neste dispositivo para usar o GPS.'; ?>
              </span>
            </div>
            <input type="hidden" name="latitude" id="business-latitude" value="<?php echo htmlspecialchars((string) ($estabelecimento['latitude'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="longitude" id="business-longitude" value="<?php echo htmlspecialchars((string) ($estabelecimento['longitude'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">

            <fieldset class="delivery-address-fields" id="business-address-fields">
              <legend>Endereço do estabelecimento</legend>
              <label>
                CEP
                <input name="cep" type="text" inputmode="numeric" autocomplete="postal-code" maxlength="9" value="<?php echo htmlspecialchars((string) ($estabelecimento['cep'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
              </label>
              <label>
                Logradouro
                <input name="logradouro" type="text" autocomplete="street-address" maxlength="120" value="<?php echo htmlspecialchars((string) ($estabelecimento['logradouro'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
              </label>
              <label>
                Número
                <input name="numero" type="text" autocomplete="address-line2" maxlength="10" value="<?php echo htmlspecialchars((string) ($estabelecimento['numero'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
              </label>
              <label>
                Bairro
                <input name="bairro" type="text" autocomplete="address-level3" maxlength="80" value="<?php echo htmlspecialchars((string) ($estabelecimento['bairro'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
              </label>
              <label>
                Cidade
                <input name="cidade" type="text" autocomplete="address-level2" maxlength="80" value="<?php echo htmlspecialchars((string) ($estabelecimento['cidade'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
              </label>
              <label>
                Estado (UF)
                <input name="estado" type="text" autocomplete="address-level1" maxlength="2" value="<?php echo htmlspecialchars((string) ($estabelecimento['estado'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
              </label>
            </fieldset>
          </section>

          <section class="delivery-menu-settings" aria-labelledby="menu-title">
            <h2 id="menu-title">Cardápios disponíveis no delivery</h2>
            <?php if (empty($deliveryCardapios)): ?>
              <div class="estado-vazio delivery-empty">
                <p>Não há cardápios cadastrados para selecionar. Os cardápios existentes aparecerão aqui.</p>
              </div>
            <?php else: ?>
          <div class="delivery-options">
            <?php foreach ($deliveryCardapios as $cardapio): ?>
              <label class="delivery-option">
                <input
                  type="checkbox"
                  name="cardapio_ids[]"
                  value="<?php echo (int) $cardapio['id']; ?>"
                  <?php echo !empty($cardapio['delivery_ativo']) ? 'checked' : ''; ?>
                >
                <span class="delivery-option-copy">
                  <strong><?php echo htmlspecialchars($cardapio['nome_restaurante'], ENT_QUOTES, 'UTF-8'); ?></strong>
                  <span><?php echo htmlspecialchars($cardapio['categoria'] ?: 'Sem categoria', ENT_QUOTES, 'UTF-8'); ?></span>
                </span>
                <span class="delivery-option-status">
                  <?php echo !empty($cardapio['delivery_ativo']) ? 'Publicado' : 'Oculto'; ?>
                </span>
              </label>
            <?php endforeach; ?>
          </div>
            <?php endif; ?>
          </section>

          <div class="delivery-settings-actions">
            <button class="btn-pill" type="submit">Salvar localização e cardápios</button>
            <span>É necessário informar a localização do estabelecimento para publicar no delivery.</span>
          </div>
        </form>
      <?php endif; ?>

      <div class="dashboard-actions">
        <a class="btn-pill" href="index.php">Voltar ao painel</a>
        <a class="btn-pill" href="../Delivery/index.php">Ver delivery</a>
      </div>
    </section>
  </main>

  <footer class="site-footer">
    <div class="logo">
      <span class="logo-badge">S</span>
      <span class="logo-word">S.O.P.A.</span>
    </div>
    <p>Sistema Online de Pedidos e Atendimentos</p>
  </footer>
  <script src="delivery-location.js"></script>
</body>
</html>