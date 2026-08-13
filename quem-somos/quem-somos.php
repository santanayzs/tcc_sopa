<?php
/**
 * S.O.P.A. — Página "Quem Somos"
 * Página institucional pública (não requer login).
 */

$anoAtual = date("Y");

// Equipe do projeto — renderizada dinamicamente via PHP.
$equipe = [
    ['nome' => 'Dérek Robson de Santana',            'funcao' => 'Desenvolvedor'],
    ['nome' => 'Gabriel Henrique Bastos da Silva',    'funcao' => 'Desenvolvedor'],
    ['nome' => 'Miguel Cirino Leite',                 'funcao' => 'Desenvolvedor'],
    ['nome' => 'Renato Miranda Lima de Sá',           'funcao' => 'Desenvolvedor'],
    ['nome' => 'Rian Aleixo da Silva',                'funcao' => 'Desenvolvedor'],
    ['nome' => 'Wendrel Patrick Freitas Alves da Silva', 'funcao' => 'Desenvolvedor'],
];

$orientadora = ['nome' => 'Profa. Maria Regina Gonçalves', 'funcao' => 'Orientadora'];

/**
 * Gera as iniciais de um nome (até 2 letras) para o avatar.
 */
function iniciais(string $nomeCompleto): string
{
    $partes = preg_split('/\s+/', trim($nomeCompleto));
    $primeira = mb_substr($partes[0] ?? '', 0, 1);
    $ultima   = mb_substr(end($partes) ?: '', 0, 1);
    return mb_strtoupper($primeira . $ultima);
}

// Valores/pilares do projeto, usados na grade "O que nos move".
$valores = [
    [
        'titulo' => 'Praticidade',
        'texto'  => 'Menos etapas e mais agilidade na hora de pedir e atender.',
        'icone'  => '<path d="M12 3v6l4 2" stroke-linecap="round"/><circle cx="12" cy="12" r="9"/>',
    ],
    [
        'titulo' => 'Acessibilidade',
        'texto'  => 'Uma ferramenta simples, gratuita e intuitiva para pequenos empreendedores.',
        'icone'  => '<path d="M12 21c-4.5-2-7-5.5-7-9a7 7 0 0 1 14 0c0 3.5-2.5 7-7 9Z"/><circle cx="12" cy="11" r="2.4"/>',
    ],
    [
        'titulo' => 'Organização',
        'texto'  => 'Redução de erros de comanda e confusão entre atendimentos.',
        'icone'  => '<rect x="5" y="4" width="14" height="17" rx="2"/><path d="M9 9h6M9 13h6M9 17h3"/>',
    ],
    [
        'titulo' => 'Inovação',
        'texto'  => 'Acompanhando as demandas de um mercado cada vez mais digital.',
        'icone'  => '<path d="M9 18h6M10 21h4"/><path d="M12 3a6 6 0 0 0-3.6 10.8c.4.3.6.8.6 1.2h6c0-.4.2-.9.6-1.2A6 6 0 0 0 12 3Z"/>',
    ],
];

// Objetivos específicos do projeto (usados na lista de metas).
$objetivosEspecificos = [
    'Levantar os principais problemas operacionais enfrentados por estabelecimentos que ainda usam a gestão manual de pedidos.',
    'Projetar uma interface de cardápio digital intuitiva, que dê mais autonomia ao cliente no momento do pedido.',
    'Desenvolver um cadastro e uma configuração de cardápio simples, acessíveis a pequenos empreendedores.',
    'Implementar controle de comandas que previna duplicidade ou extravio de pedidos em horários de pico.',
    'Validar a eficácia do sistema como solução para reduzir prejuízos causados por falhas logísticas internas.',
];
?>
<!doctype html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Quem Somos — S.O.P.A.</title>
    <meta
        name="description"
        content="Conheça o S.O.P.A. — Sistema Online de Pedidos e Atendimento — projeto de TCC da ETEC João Gomes de Araújo, e a equipe por trás dele."
    />

    <!-- FONTES -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700;800&family=Inter:wght@400;500;600&family=Cormorant+Garamond:wght@500;600&display=swap"
        rel="stylesheet"
    />

    <!-- CSS -->
    <link rel="stylesheet" href="../CSS/style.css" />
    <link rel="stylesheet" href="quem-somos.css" />
</head>
<body>
    <!-- HEADER -->
    <header class="site-header">
        <a href="../index.html" class="logo" aria-label="S.O.P.A. — voltar ao início">
            <span class="logo-badge">S</span>
            <span class="logo-word">S.O.P.A.</span>
        </a>

        <nav class="main-nav" id="main-nav">
            <a href="../index.html" aria-current="page" class="nav-link">Voltar</a>
            <a href="../auth/index.php" class="nav-cta">Criar cardápio</a>
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

    <main id="topo">
        <!-- INTRODUÇÃO -->
        <section class="section section-dark about-hero">
            <div class="zigzag" aria-hidden="true"></div>
            <div class="section-inner">
                <span class="about-eyebrow">Quem somos</span>
                <h1>O S.O.P.A. nasceu para simplificar o pedido</h1>
                <p class="lead">
                    Somos a equipe por trás do S.O.P.A. — Sistema Online de Pedidos
                    e Atendimento —, um projeto de Trabalho de Conclusão de Curso
                    (TCC) desenvolvido por estudantes do Curso Técnico em
                    Desenvolvimento de Sistemas da ETEC João Gomes de Araújo, em
                    Pindamonhangaba.
                </p>
            </div>
        </section>

        <!-- NOSSA HISTÓRIA -->
        <section class="section section-dark" id="historia">
            <div class="zigzag" aria-hidden="true"></div>
            <div class="section-inner">
                <h2 class="boxed-title">Nossa história</h2>
                <div class="about-block lead bordered-text">
                    <p>
                        Durante pesquisas sobre sistemas de pedidos online, percebemos
                        que muitos estabelecimentos alimentícios ainda dependem de
                        comandas de papel e controles manuais. Isso gera trocas de
                        pedidos, confusão entre atendimentos e prejuízo tanto para o
                        cliente quanto para o próprio negócio, principalmente nos
                        horários de maior movimento.
                    </p>
                    <p>
                        A partir dessa observação, decidimos propor uma solução
                        tecnológica acessível: um sistema capaz de modernizar o
                        atendimento, reduzir erros operacionais e trazer mais
                        agilidade tanto para quem pede quanto para quem serve.
                    </p>
                </div>
            </div>
        </section>

        <!-- MISSÃO / OBJETIVO -->
        <section class="section section-dark" id="missao">
            <div class="zigzag" aria-hidden="true"></div>
            <div class="section-inner">
                <h2 class="boxed-title">Nossa missão</h2>
                <p class="about-dosomething lead">
                    Desenvolver e disponibilizar o S.O.P.A., um sistema de
                    gerenciamento de pedidos e cardápio online voltado a
                    estabelecimentos alimentícios, com o intuito de mitigar erros
                    operacionais, otimizar a gestão do atendimento e oferecer uma
                    solução acessível, gratuita e intuitiva a quem utilizá-lo.
                </p>

                <ul class="goals-list">
                    <?php foreach ($objetivosEspecificos as $objetivo): ?>
                        <li><?php echo htmlspecialchars($objetivo, ENT_QUOTES, 'UTF-8'); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </section>

        <!-- O QUE NOS MOVE (VALORES) -->
        <section class="section section-dark" id="valores">
            <div class="zigzag" aria-hidden="true"></div>
            <div class="section-inner">
                <h2 class="title-plain">O que nos move</h2>
                <div class="values-grid">
                    <?php foreach ($valores as $valor): ?>
                        <article class="value-card">
                            <span class="value-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                                    <?php echo $valor['icone']; ?>
                                </svg>
                            </span>
                            <h3><?php echo htmlspecialchars($valor['titulo'], ENT_QUOTES, 'UTF-8'); ?></h3>
                            <p><?php echo htmlspecialchars($valor['texto'], ENT_QUOTES, 'UTF-8'); ?></p>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <!-- EQUIPE -->
        <section class="section section-dark" id="equipe">
            <div class="zigzag" aria-hidden="true"></div>
            <div class="section-inner">
                <h2 class="title-plain">Nossa equipe</h2>

                <div class="team-grid">
                    <article class="team-card is-advisor">
                        <span class="team-avatar"><?php echo iniciais($orientadora['nome']); ?></span>
                        <h3><?php echo htmlspecialchars($orientadora['nome'], ENT_QUOTES, 'UTF-8'); ?></h3>
                        <span class="team-role"><?php echo htmlspecialchars($orientadora['funcao'], ENT_QUOTES, 'UTF-8'); ?></span>
                    </article>

                    <?php foreach ($equipe as $integrante): ?>
                        <article class="team-card">
                            <span class="team-avatar"><?php echo iniciais($integrante['nome']); ?></span>
                            <h3><?php echo htmlspecialchars($integrante['nome'], ENT_QUOTES, 'UTF-8'); ?></h3>
                            <span class="team-role"><?php echo htmlspecialchars($integrante['funcao'], ENT_QUOTES, 'UTF-8'); ?></span>
                        </article>
                    <?php endforeach; ?>
                </div>

                <p class="institution-note">
                    Projeto desenvolvido como <strong>Trabalho de Conclusão de Curso</strong>
                    do Curso Técnico em Desenvolvimento de Sistemas da
                    <strong>ETEC João Gomes de Araújo</strong>, em Pindamonhangaba/SP.
                </p>
            </div>
        </section>

        <!-- CTA -->
        <section class="section section-dark" id="cta-quem-somos">
            <div class="zigzag" aria-hidden="true"></div>
            <div class="section-inner" style="text-align:center;">
                <h2 class="boxed-title">Quer conhecer o sistema na prática?</h2>
                <p class="lead" style="margin:0 auto 24px;">
                    Veja o que o S.O.P.A. pode oferecer para o seu estabelecimento.
                </p>
                <a href="../auth/index.php" class="btn-pill">Criar meu cardápio</a>
            </div>
        </section>
    </main>

    <!-- FOOTER -->
    <footer class="site-footer">
        <div class="logo">
            <span class="logo-badge">S</span>
            <span class="logo-word">S.O.P.A.</span>
        </div>
        <p>Sistema Online de Pedidos e Atendimentos</p>
        <p class="footer-fine">
            &copy; <?php echo htmlspecialchars($anoAtual, ENT_QUOTES, 'UTF-8'); ?> S.O.P.A. — Projeto de TCC, ETEC João Gomes de Araújo.
        </p>
    </footer>

    <script src="../JS/main-nav.js"></script>
    <script src="quem-somos.js"></script>
</body>
</html>
