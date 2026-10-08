<?php
session_start();
include __DIR__ . '/../CRUD-Cardapio/criar-cardapio/conexao.php';

$deliveryCsrf = $_SESSION['delivery_location_csrf'] ??= bin2hex(random_bytes(32));
$locationError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'confirmar_localizacao') {
  $postedToken = $_POST['csrf_token'] ?? '';
  if (!is_string($postedToken) || !hash_equals($deliveryCsrf, $postedToken)) {
    $locationError = 'Não foi possível confirmar sua localização. Atualize a página e tente novamente.';
  } elseif (($_POST['location_type'] ?? '') === 'gps') {
    $latitude = filter_var($_POST['latitude'] ?? null, FILTER_VALIDATE_FLOAT);
    $longitude = filter_var($_POST['longitude'] ?? null, FILTER_VALIDATE_FLOAT);
    if ($latitude === false || $longitude === false || $latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
      $locationError = 'Não foi possível validar o GPS. Permita o acesso ou informe seu endereço.';
    } else {
      $_SESSION['delivery_location'] = [
        'type' => 'gps',
        'latitude' => $latitude,
        'longitude' => $longitude,
      ];
    }
  } elseif (($_POST['location_type'] ?? '') === 'endereco') {
    $endereco = trim((string) ($_POST['endereco_cliente'] ?? ''));
    if ($endereco === '' || strlen($endereco) > 500) {
      $locationError = 'Informe seu endereço para continuar.';
    } else {
      $_SESSION['delivery_location'] = [
        'type' => 'endereco',
        'endereco' => $endereco,
      ];
    }
  } else {
    $locationError = 'Escolha usar o GPS ou informar seu endereço.';
  }

  if ($locationError === '') {
    header('Location: index.php#estabelecimentos');
    exit;
  }
}

$deliveryLocation = $_SESSION['delivery_location'] ?? null;
$locationConfirmed = is_array($deliveryLocation)
  && in_array($deliveryLocation['type'] ?? '', ['gps', 'endereco'], true)
  && $locationError === '';
$locationSummary = $locationConfirmed
  ? (($deliveryLocation['type'] ?? '') === 'gps' ? 'GPS confirmado' : 'Endereço informado')
  : '';

$cardapios = [];
if ($locationConfirmed) {
  $resultado = $conexao->query(
    "SELECT c.id, c.nome_restaurante, c.categoria, c.logo,
        e.bairro, e.cidade, e.estado, e.latitude, e.longitude,
        (SELECT COUNT(*)
         FROM itens_cardapio AS i
         WHERE i.cardapio_id = c.id AND i.disponivel = 1) AS itens_disponiveis
     FROM cardapios AS c
     INNER JOIN estabelecimentos AS e ON e.usuario_id = c.usuario_id
     WHERE c.delivery_ativo = 1
       AND (
         (e.latitude IS NOT NULL AND e.longitude IS NOT NULL)
         OR (e.cep IS NOT NULL AND e.logradouro IS NOT NULL AND e.bairro IS NOT NULL
           AND e.cidade IS NOT NULL AND e.estado IS NOT NULL)
       )
       AND EXISTS (
         SELECT 1
         FROM itens_cardapio AS disponiveis
         WHERE disponiveis.cardapio_id = c.id AND disponiveis.disponivel = 1
       )
     ORDER BY c.nome_restaurante"
  );

  if ($resultado) {
    $cardapios = $resultado->fetch_all(MYSQLI_ASSOC);
  }
}
?>
<!doctype html>
<html lang="pt-BR">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="Encontre cardápios disponíveis para delivery no S.O.P.A." />
    <title>Delivery | S.O.P.A.</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700&family=Inter:wght@400;500;600&display=swap"
      rel="stylesheet"
    />
    <link rel="stylesheet" href="style-delivery.css" />
    <link rel="stylesheet" href="lista-delivery.css" />
  </head>
  <body>
    <header class="site-header">
      <a class="brand" href="../index.html" aria-label="S.O.P.A., início">
        <span class="brand-mark">S</span>
        <span>S.O.P.A.</span>
      </a>
      <nav class="main-nav" aria-label="Navegação principal">
        <a href="#estabelecimentos">Cardápios</a>
      </nav>
      <a class="account-link" href="../auth/index.php">Área do estabelecimento</a>
    </header>

    <main id="inicio">
      <section class="delivery-intro" aria-labelledby="intro-title">
        <div class="intro-copy">
          <p class="eyebrow">Delivery S.O.P.A.</p>
          <h1 id="intro-title">Escolha seu próximo lugar favorito.</h1>
          <p class="intro-description">
            Informe onde receber seu pedido para ver os cardápios disponíveis.
          </p>
          <a class="delivery-cta" href="<?php echo $locationConfirmed ? '#estabelecimentos' : '#localizacao'; ?>">
            <?php echo $locationConfirmed ? 'Ver cardápios disponíveis' : 'Informar localização'; ?>
          </a>
        </div>
        <div class="delivery-location-panel" id="localizacao">
          <div class="delivery-location-confirmation" <?php echo $locationConfirmed ? '' : 'hidden'; ?>>
            <span class="location-check" aria-hidden="true">✓</span>
            <div>
              <strong>Localização confirmada</strong>
              <span><?php echo htmlspecialchars($locationSummary, ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
            <button class="location-change-button" type="button" id="change-delivery-location">Alterar</button>
          </div>

          <form
            class="address-form delivery-location-form"
            id="delivery-location-form"
            method="post"
            action="index.php"
            <?php echo $locationConfirmed ? 'hidden' : ''; ?>
          >
            <input type="hidden" name="action" value="confirmar_localizacao">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($deliveryCsrf, ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="location_type" id="customer-location-type" value="endereco">
            <input type="hidden" name="latitude" id="customer-latitude">
            <input type="hidden" name="longitude" id="customer-longitude">
            <p class="location-form-title">Onde vamos entregar?</p>
            <button class="location-gps-button" type="button" id="use-customer-gps">Usar minha localização</button>
            <p class="location-feedback" id="customer-location-status" role="status"></p>
            <p class="location-divider"><span>ou informe seu endereço</span></p>
            <label for="customer-address">Endereço de entrega</label>
            <textarea
              id="customer-address"
              name="endereco_cliente"
              rows="3"
              maxlength="500"
              placeholder="Rua, número, bairro, cidade e estado"
              autocomplete="street-address"
              required
            ></textarea>
            <?php if ($locationError !== ''): ?>
              <p class="location-error" role="alert"><?php echo htmlspecialchars($locationError, ENT_QUOTES, 'UTF-8'); ?></p>
            <?php endif; ?>
            <button class="location-submit-button" type="submit">Continuar</button>
          </form>
        </div>
      </section>

      <section class="content-section" id="estabelecimentos" aria-labelledby="estabelecimentos-title" <?php echo $locationConfirmed ? '' : 'hidden'; ?>>
        <div class="section-heading">
          <div>
            <p class="eyebrow">Disponíveis agora</p>
            <h2 id="estabelecimentos-title">Cardápios para pedir</h2>
          </div>
          <span class="section-note"><?php echo count($cardapios); ?> estabelecimento<?php echo count($cardapios) === 1 ? '' : 's'; ?></span>
        </div>

        <?php if (empty($cardapios)): ?>
          <div class="delivery-empty-state">
            <span class="empty-mark" aria-hidden="true">S</span>
            <h3>Nenhum cardápio disponível por enquanto</h3>
            <p>Quando um estabelecimento ativar o delivery, o cardápio aparecerá aqui.</p>
          </div>
        <?php else: ?>
          <div class="estabelecimento-grid">
            <?php foreach ($cardapios as $cardapio): ?>
              <a
                class="estabelecimento-card"
                href="../CRUD-Cardapio/ver-cardapio/cardapio-publico.php?id=<?php echo (int) $cardapio['id']; ?>"
              >
                <div class="estabelecimento-identidade">
                  <?php if (!empty($cardapio['logo'])): ?>
                    <img
                      class="estabelecimento-logo"
                      src="../uploads/logos/<?php echo htmlspecialchars($cardapio['logo'], ENT_QUOTES, 'UTF-8'); ?>"
                      alt="Logo de <?php echo htmlspecialchars($cardapio['nome_restaurante'], ENT_QUOTES, 'UTF-8'); ?>"
                      loading="lazy"
                    />
                  <?php else: ?>
                    <span class="estabelecimento-logo estabelecimento-logo-placeholder" aria-hidden="true">S</span>
                  <?php endif; ?>
                  <div class="estabelecimento-titulo">
                    <span class="estabelecimento-sobretitulo">Estabelecimento</span>
                    <h3><?php echo htmlspecialchars($cardapio['nome_restaurante'], ENT_QUOTES, 'UTF-8'); ?></h3>
                  </div>
                </div>
                <div class="estabelecimento-detalhes">
                  <span class="estabelecimento-categoria">
                    <?php echo htmlspecialchars($cardapio['categoria'] ?: 'Cardápio da casa', ENT_QUOTES, 'UTF-8'); ?>
                  </span>
                  <span><?php echo (int) $cardapio['itens_disponiveis']; ?> itens disponíveis</span>
                </div>
                <span class="estabelecimento-localizacao">
                  <?php
                    $localizacaoEstabelecimento = array_filter([
                        $cardapio['bairro'],
                        trim(($cardapio['cidade'] ?? '') . (!empty($cardapio['estado']) ? ' - ' . $cardapio['estado'] : '')),
                    ]);
                    echo htmlspecialchars(
                        $localizacaoEstabelecimento ? implode(', ', $localizacaoEstabelecimento) : 'Localização GPS cadastrada',
                        ENT_QUOTES,
                        'UTF-8'
                    );
                  ?>
                </span>
                <span class="estabelecimento-link">Abrir cardápio <span aria-hidden="true">&rarr;</span></span>
              </a>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </section>
    </main>

    <footer class="site-footer">
      <a class="brand" href="../index.html">
        <span class="brand-mark">S</span>
        <span>S.O.P.A.</span>
      </a>
      <p>Comida que aproxima.</p>
    </footer>
    <script src="location.js"></script>
  </body>
</html>