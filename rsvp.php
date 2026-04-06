<?php
require_once 'config.php';

$pdo = getDB();
$id_evento = (int)($_GET['id'] ?? 0);
$success = false;
$error   = '';
$nome    = '';

if ($id_evento <= 0) {
    http_response_code(404);
    die("Evento não encontrado.");
}

$stmt = $pdo->prepare("SELECT * FROM eventos WHERE id = ?");
$stmt->execute([$id_evento]);
$evento = $stmt->fetch();

if (!$evento) {
    http_response_code(404);
    die("Evento não encontrado.");
}

// Buscar contribuições já registradas
$contribuicoes_existentes = [];
if (!empty($evento['colaborativo_ativo'])) {
    try {
        $stmt_c = $pdo->prepare("
            SELECT item_nome, p.nome as pessoa FROM eventos_contribuicoes ec JOIN profissionais p ON ec.user_id = p.id WHERE ec.evento_id = ?
            UNION ALL
            SELECT contribuicao_item as item_nome, nome as pessoa FROM eventos_presenca_externa WHERE evento_id = ? AND status = 'confirmado' AND contribuicao_item IS NOT NULL AND contribuicao_item != ''
        ");
        $stmt_c->execute([$id_evento, $id_evento]);
        while($row = $stmt_c->fetch()) {
            $p_nome = explode(' ', $row['pessoa'])[0];
            $contribuicoes_existentes[$row['item_nome']][] = $p_nome;
        }
    } catch (Exception $e) {}
}

// Processar RSVP
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome         = strip_tags(trim($_POST['nome'] ?? ''));
    $whatsapp     = strip_tags(trim($_POST['whatsapp'] ?? ''));
    $acompanhantes = max(0, (int)($_POST['acompanhantes'] ?? 0));
    $contribuicao = strip_tags(trim($_POST['contribuicao'] ?? ''));
    $obs          = strip_tags(trim($_POST['contribuicao_obs'] ?? ''));

    if (empty($nome)) {
        $error = "Por favor, informe seu nome.";
    } else {
        try {
            $pdo->prepare("INSERT INTO eventos_presenca_externa (evento_id, nome, whatsapp, acompanhantes, contribuicao_item, contribuicao_obs, status) VALUES (?, ?, ?, ?, ?, ?, 'confirmado')")
                ->execute([$id_evento, $nome, $whatsapp, $acompanhantes, $contribuicao, $obs]);
            $success = true;
        } catch (Exception $e) {
            $error = "Não foi possível salvar sua confirmação. Tente novamente.";
        }
    }
}

// SEO helpers
$titulo     = htmlspecialchars($evento['titulo']);
$descricao  = !empty($evento['descricao']) ? htmlspecialchars(mb_strimwidth($evento['descricao'], 0, 160, '...')) : "Confirme sua presença no evento $titulo da Rede Melodias.";
$local      = htmlspecialchars($evento['local'] ?? '');
$data_fmt   = date('d/m/Y \à\s H:i', strtotime($evento['data_evento']));
$data_iso   = date('c', strtotime($evento['data_evento']));
$has_capa   = !empty($evento['capa']);
$capa_url   = $has_capa ? htmlspecialchars($evento['capa']) : '';
$site_url   = 'https://melodias.karengomes.com.br';
$og_image   = $has_capa ? $capa_url : "$site_url/images/share-banner.png";
$itens_colab = [];
if (!empty($evento['colaborativo_ativo']) && !empty($evento['itens_colaborativos'])) {
    $itens_colab = array_values(array_filter(array_map('trim', explode(',', $evento['itens_colaborativos']))));
}
?>
<!DOCTYPE html>
<html lang="pt-BR" prefix="og: https://ogp.me/ns#">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- SEO Primary -->
    <title>Confirmar Presença — <?= $titulo ?> | Rede Melodias</title>
    <meta name="description" content="<?= $descricao ?>">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="<?= $site_url ?>/rsvp.php?id=<?= $id_evento ?>">

    <!-- Open Graph / WhatsApp / Facebook -->
    <meta property="og:type"        content="event">
    <meta property="og:url"         content="<?= $site_url ?>/rsvp.php?id=<?= $id_evento ?>">
    <meta property="og:title"       content="<?= $titulo ?> — Confirmar Presença | Rede Melodias">
    <meta property="og:description" content="<?= $descricao ?>">
    <meta property="og:image"       content="<?= htmlspecialchars($og_image) ?>">
    <meta property="og:locale"      content="pt_BR">
    <meta property="og:site_name"   content="Rede Melodias">
    <meta property="event:start_time" content="<?= $data_iso ?>">

    <!-- Twitter Card -->
    <meta name="twitter:card"        content="summary_large_image">
    <meta name="twitter:title"       content="<?= $titulo ?> | Rede Melodias">
    <meta name="twitter:description" content="<?= $descricao ?>">
    <meta name="twitter:image"       content="<?= htmlspecialchars($og_image) ?>">

    <!-- JSON-LD Structured Data -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "Event",
      "name": "<?= addslashes($titulo) ?>",
      "description": "<?= addslashes($descricao) ?>",
      "startDate": "<?= $data_iso ?>",
      "location": { "@type": "Place", "name": "<?= addslashes($local) ?>" },
      "organizer": { "@type": "Organization", "name": "Rede Melodias", "url": "<?= $site_url ?>" },
      "eventStatus": "https://schema.org/EventScheduled",
      "eventAttendanceMode": "https://schema.org/OfflineEventAttendanceMode"
    }
    </script>

    <link rel="icon" href="images/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
        --primary:    #6e2b3a;
        --primary-dk: #521f2b;
        --primary-lt: #9d405a;
        --accent:     #d4a0ab;
        --success:    #10b981;
        --danger:     #ef4444;
        --warning:    #f59e0b;
        --bg:         #f8f4f5;
        --card:       #ffffff;
        --text:       #1a0d10;
        --muted:      #6b5057;
        --border:     #e8dde0;
        --shadow:     0 25px 60px rgba(110,43,58,.15);
    }

    body {
        font-family: 'Outfit', sans-serif;
        background: var(--bg);
        color: var(--text);
        min-height: 100vh;
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: 0 0 60px;
        opacity: 0;
        animation: pageFadeIn 0.8s ease forwards;
    }

    @keyframes pageFadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* ── HERO ── */
    .hero {
        width: 100%;
        position: relative;
        min-height: 400px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding-bottom: 100px;
        padding-top: 40px;
        overflow: hidden;
        background: var(--bg);
    }
    .hero-bg {
        position: absolute; inset: 0;
        background-size: cover;
        background-position: center;
        filter: brightness(0.4) saturate(1.2) blur(8px);
        transform: scale(1.1);
        transition: transform 12s ease-out;
    }
    .hero:hover .hero-bg { transform: scale(1.2); }
    
    .hero-overlay {
        position: absolute; inset: 0;
        background: linear-gradient(to bottom, 
            rgba(82, 31, 43, 0.4) 0%, 
            rgba(82, 31, 43, 0.7) 50%, 
            var(--bg) 100%);
    }
    .hero-content {
        position: relative;
        text-align: center;
        color: white;
        padding: 40px 24px;
        width: calc(100% - 40px);
        max-width: 600px;
        background: rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        border: 1px solid rgba(255, 255, 255, 0.2);
        border-radius: 30px;
        box-shadow: 0 20px 50px rgba(0,0,0,0.2);
        animation: heroPopup 1s cubic-bezier(0.2, 0.8, 0.2, 1) forwards;
    }
    @keyframes heroPopup {
        from { opacity: 0; transform: translateY(30px) scale(0.95); }
        to { opacity: 1; transform: translateY(0) scale(1); }
    }
    .hero-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: var(--primary);
        color: white;
        font-size: 0.75em;
        font-weight: 800;
        letter-spacing: 2px;
        text-transform: uppercase;
        padding: 8px 20px;
        border-radius: 30px;
        margin-bottom: 20px;
        box-shadow: 0 4px 15px rgba(110,43,58,0.3);
    }
    .hero h1 {
        font-size: clamp(1.8rem, 6vw, 2.8rem);
        font-weight: 900;
        line-height: 1.1;
        margin-bottom: 20px;
        text-shadow: 0 2px 15px rgba(0,0,0,0.4);
    }
    .hero-meta {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 12px 24px;
        font-size: 0.95em;
        font-weight: 500;
    }
    .hero-meta span {
        display: flex;
        align-items: center;
        gap: 8px;
        background: rgba(0,0,0,0.2);
        padding: 6px 14px;
        border-radius: 12px;
    }
    .hero-meta i { color: var(--accent); }

    /* ── LOGO ── */
    .hero-logo {
        position: relative;
        height: 100px;
        margin-bottom: 25px;
        filter: brightness(0) invert(1) drop-shadow(0 6px 15px rgba(0,0,0,0.4));
        transition: transform 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        opacity: 1;
        display: block;
        margin-left: auto;
        margin-right: auto;
    }
    .hero-logo:hover { transform: scale(1.1); }

    /* ── CARD ── */
    .card-wrap {
        width: 100%;
        max-width: 520px;
        margin-top: -56px;
        padding: 0 16px;
        position: relative;
        z-index: 10;
    }
    .card {
        background: var(--card);
        border-radius: 24px;
        box-shadow: var(--shadow);
        overflow: hidden;
        border: 1px solid var(--border);
    }

    /* ── DESCRIPTION banner ── */
    .desc-banner {
        background: linear-gradient(135deg, #fdf2f4, #fff8f9);
        border-left: 4px solid var(--primary);
        padding: 16px 20px;
        font-size: 0.88em;
        color: var(--muted);
        line-height: 1.6;
        font-style: italic;
        border-top: 1px solid var(--border);
        border-bottom: 1px solid var(--border);
    }

    /* ── FORM BODY ── */
    .card-body { padding: 30px 28px; }

    .section-title {
        font-size: 1.1em;
        font-weight: 800;
        color: var(--primary);
        margin-bottom: 22px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .section-title::after {
        content: '';
        flex: 1;
        height: 2px;
        background: linear-gradient(to right, var(--border), transparent);
        border-radius: 2px;
    }

    .form-group { margin-bottom: 18px; }
    .form-group label {
        display: block;
        margin-bottom: 7px;
        font-weight: 700;
        font-size: 0.88em;
        color: var(--text);
    }
    .form-group label .req { color: var(--primary); margin-left: 2px; }
    .form-control {
        width: 100%;
        padding: 13px 16px;
        border-radius: 12px;
        border: 2px solid var(--border);
        font-family: 'Outfit', sans-serif;
        font-size: 0.95em;
        color: var(--text);
        background: #fdfdfd;
        transition: border-color 0.2s, box-shadow 0.2s;
    }
    .form-control:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 4px rgba(110,43,58,0.1);
        background: #fff;
    }
    select.form-control { appearance: none; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236e2b3a' d='M6 8L1 3h10z'/%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 14px center; padding-right: 38px; }

    /* Companions stepper */
    .stepper {
        display: flex;
        align-items: center;
        gap: 0;
        border: 2px solid var(--border);
        border-radius: 12px;
        overflow: hidden;
    }
    .stepper button {
        background: #f8f4f5;
        border: none;
        width: 46px;
        height: 48px;
        font-size: 1.2em;
        font-weight: 700;
        cursor: pointer;
        color: var(--primary);
        transition: background 0.2s;
        flex-shrink: 0;
    }
    .stepper button:hover { background: #efdfdf; }
    .stepper input {
        flex: 1;
        border: none;
        border-left: 2px solid var(--border);
        border-right: 2px solid var(--border);
        text-align: center;
        font-size: 1em;
        font-weight: 700;
        font-family: 'Outfit', sans-serif;
        color: var(--text);
        padding: 10px 0;
    }
    .stepper input:focus { outline: none; background: #fff; }

    /* Contribution pills */
    .contrib-pills {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-top: 4px;
    }
    .contrib-pill {
        display: none;
    }
    .contrib-pill + label {
        padding: 10px 18px;
        border-radius: 14px;
        border: 2px solid var(--border);
        font-size: 0.88em;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        color: var(--muted);
        background: #fdfdfd;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    .contrib-pill + label:hover {
        border-color: var(--accent);
        background: #fffafa;
        transform: translateY(-2px);
    }
    .contrib-pill:checked + label {
        background: var(--primary);
        color: white;
        border-color: var(--primary);
        box-shadow: 0 4px 12px rgba(110,43,58,0.25);
        transform: translateY(-2px);
    }

    /* Submit button */
    .btn-submit {
        width: 100%;
        padding: 17px;
        border-radius: 16px;
        border: none;
        background: linear-gradient(135deg, var(--primary), var(--primary-lt));
        color: white;
        font-weight: 800;
        font-size: 1.05em;
        font-family: 'Outfit', sans-serif;
        cursor: pointer;
        transition: transform 0.2s, box-shadow 0.2s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        margin-top: 8px;
        box-shadow: 0 6px 20px rgba(110,43,58,.25);
    }
    .btn-submit:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 28px rgba(110,43,58,.35);
    }
    .btn-submit:active { transform: translateY(0); }
    .btn-submit i { font-size: 1em; }

    /* Error */
    .alert-error {
        background: #fee2e2;
        color: #991b1b;
        padding: 14px 18px;
        border-radius: 12px;
        font-size: 0.9em;
        font-weight: 600;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
        border-left: 4px solid var(--danger);
    }

    /* Privacy note */
    .privacy {
        text-align: center;
        margin-top: 20px;
        font-size: 0.75em;
        color: var(--muted);
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
    }

    /* ── SUCCESS STATE ── */
    .success-wrap {
        padding: 50px 30px;
        text-align: center;
    }
    .success-icon {
        width: 90px;
        height: 90px;
        border-radius: 50%;
        background: linear-gradient(135deg, #d1fae5, #a7f3d0);
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 24px;
        font-size: 2.5em;
        color: var(--success);
        animation: popIn 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }
    @keyframes popIn {
        from { transform: scale(0); opacity: 0; }
        to   { transform: scale(1); opacity: 1; }
    }
    .success-wrap h2 {
        font-size: 1.6em;
        font-weight: 800;
        color: var(--text);
        margin-bottom: 10px;
    }
    .success-wrap .name-hi {
        color: var(--primary);
    }
    .success-wrap p {
        color: var(--muted);
        font-size: 0.95em;
        line-height: 1.6;
        max-width: 340px;
        margin: 0 auto 28px;
    }
    .success-details {
        background: #f8f4f5;
        border-radius: 14px;
        padding: 18px 20px;
        text-align: left;
        margin-bottom: 28px;
        display: flex;
        flex-direction: column;
        gap: 10px;
    }
    .success-details .det {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 0.9em;
        color: var(--muted);
    }
    .success-details .det i { color: var(--primary); width: 16px; }
    .btn-new {
        background: var(--bg);
        color: var(--primary);
        border: 2px solid var(--border);
        border-radius: 14px;
        padding: 13px 28px;
        font-weight: 700;
        font-size: 0.95em;
        font-family: 'Outfit', sans-serif;
        cursor: pointer;
        transition: all 0.2s;
    }
    .btn-new:hover { background: #efdfdf; border-color: var(--primary); }

    /* ── FOOTER ── */
    .rsvp-footer {
        margin-top: 28px;
        text-align: center;
        font-size: 0.78em;
        color: var(--muted);
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }
    .rsvp-footer img { height: 22px; opacity: 0.6; }
    .rsvp-footer a { color: var(--primary); text-decoration: none; font-weight: 600; }

    /* Mobile tweaks */
    @media (max-width: 480px) {
        .hero { min-height: 240px; padding-bottom: 70px; }
        .card-body { padding: 24px 20px; }
    }
    .contrib-pill + label.taken {
        opacity: 0.7;
        position: relative;
        padding-bottom: 22px;
        color: var(--muted);
        text-decoration: line-through;
        border-style: dotted;
    }
    .pill-owner {
        position: absolute;
        bottom: 4px;
        left: 50%;
        transform: translateX(-50%);
        font-size: 0.65em;
        font-weight: 800;
        text-transform: uppercase;
        color: var(--primary);
        white-space: nowrap;
        text-decoration: none !important;
        display: block;
    }
    .contrib-pill:checked + label.taken { text-decoration: none; border-style: solid; opacity: 1; }
    </style>
</head>
<body>

    <!-- HERO -->
    <div class="hero" role="banner" aria-label="Cabeçalho do evento">
        <?php if ($has_capa): ?>
        <div class="hero-bg" style="background-image: url('<?= $capa_url ?>')" role="img" aria-label="Imagem de capa do evento <?= $titulo ?>"></div>
        <?php endif; ?>
        <div class="hero-overlay"></div>
        
        <div class="hero-content">
            <img src="images/logo-melodias.png" alt="Rede Melodias" class="hero-logo" loading="lazy">
            <span class="hero-badge"><i class="fa-regular fa-calendar-check"></i> Confirme Sua Presença</span>
            <h1><?= $titulo ?></h1>
            <div class="hero-meta">
                <span><i class="fa-regular fa-calendar-days" aria-hidden="true"></i> <?= $data_fmt ?></span>
                <?php if (!empty($local)): ?>
                <span><i class="fa-solid fa-location-dot" aria-hidden="true"></i> <?= $local ?></span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- CARD -->
    <div class="card-wrap" role="main">
        <div class="card">

            <?php if ($success): ?>
            <!-- ── SUCCESS ── -->
            <div class="success-wrap">
                <div class="success-icon" aria-hidden="true"><i class="fa-solid fa-check"></i></div>
                <h2>Você está confirmado<span class="name-hi"><?= $nome ? ', ' . htmlspecialchars($nome) . '!' : '!' ?></span></h2>
                <p>Sua presença foi registrada com sucesso. Estamos animados em te receber neste encontro! 🎉</p>

                <div class="success-details" role="region" aria-label="Detalhes do evento">
                    <div class="det"><i class="fa-solid fa-calendar-check" aria-hidden="true"></i> <?= $data_fmt ?></div>
                    <?php if (!empty($local)): ?>
                    <div class="det"><i class="fa-solid fa-location-dot" aria-hidden="true"></i> <?= $local ?></div>
                    <?php endif; ?>
                    <?php if (!empty($evento['mapa_link'])): ?>
                    <div class="det"><i class="fa-solid fa-map" aria-hidden="true"></i> <a href="<?= htmlspecialchars($evento['mapa_link']) ?>" target="_blank" rel="noopener" style="color: var(--primary); font-weight:600;">Ver no mapa / abrir link</a></div>
                    <?php endif; ?>
                </div>

                <button onclick="window.location.reload()" class="btn-new" aria-label="Cadastrar outro participante">
                    <i class="fa-solid fa-user-plus"></i> Confirmar outro participante
                </button>
            </div>

            <?php else: ?>

            <!-- ── IMAGE if exists ── -->
            <?php if ($has_capa): ?>
            <div style="width: 100%; min-height: 200px; max-height: 450px; overflow: hidden; position: relative; background: #000; display: flex; align-items: center; justify-content: center; border-bottom: 1px solid var(--border);">
                <img src="<?= $capa_url ?>" alt="Imagem do evento <?= $titulo ?>" style="width: 100%; height: 100%; object-fit: contain; position: relative; z-index: 2;" loading="lazy">
                <div style="position: absolute; inset: 0; background-image: url('<?= $capa_url ?>'); background-size: cover; background-position: center; filter: blur(25px) brightness(0.4); opacity: 0.8; transform: scale(1.3);"></div>
            </div>
            <?php endif; ?>

            <!-- ── DESCRIPTION ── -->
            <?php if (!empty($evento['descricao'])): ?>
            <div class="desc-banner" role="note">
                <i class="fa-solid fa-quote-left" style="color:var(--primary); margin-right:6px; opacity:0.5" aria-hidden="true"></i>
                <?= nl2br(htmlspecialchars($evento['descricao'])) ?>
            </div>
            <?php endif; ?>

            <!-- ── FORM ── -->
            <div class="card-body">
                <p class="section-title"><i class="fa-solid fa-pen-to-square" aria-hidden="true"></i> Seus Dados</p>

                <?php if ($error): ?>
                <div class="alert-error" role="alert" aria-live="polite">
                    <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i> <?= htmlspecialchars($error) ?>
                </div>
                <?php endif; ?>

                <form method="POST" novalidate aria-label="Formulário de confirmação de presença">

                    <div class="form-group">
                        <label for="rsvp_nome">Nome Completo <span class="req" aria-hidden="true">*</span></label>
                        <input
                            type="text"
                            id="rsvp_nome"
                            name="nome"
                            class="form-control"
                            placeholder="Como devemos te chamar?"
                            value="<?= htmlspecialchars($_POST['nome'] ?? '') ?>"
                            required
                            autocomplete="name"
                            aria-required="true"
                        >
                    </div>

                    <div class="form-group">
                        <label for="rsvp_whatsapp">WhatsApp / Telefone <span style="font-weight:400; color:var(--muted)">(opcional)</span></label>
                        <input
                            type="tel"
                            id="rsvp_whatsapp"
                            name="whatsapp"
                            class="form-control"
                            placeholder="(00) 00000-0000"
                            value="<?= htmlspecialchars($_POST['whatsapp'] ?? '') ?>"
                            autocomplete="tel"
                        >
                    </div>

                    <?php if (!empty($evento['permite_acompanhantes'])): ?>
                    <div class="form-group">
                        <label for="rsvp_acomp">Vai levar acompanhantes?</label>
                        <div class="stepper" role="group" aria-label="Número de acompanhantes">
                            <button type="button" onclick="stepAcomp(-1)" aria-label="Diminuir">−</button>
                            <input type="number" id="rsvp_acomp" name="acompanhantes" value="0" min="0" max="20" readonly aria-label="Quantidade de acompanhantes">
                            <button type="button" onclick="stepAcomp(1)" aria-label="Aumentar">+</button>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($itens_colab)): ?>
                    <div class="form-group">
                        <label>Deseja contribuir com algum item? <span style="font-weight:400; color:var(--muted)">(opcional)</span></label>
                        <div class="contrib-pills" role="group" aria-label="Escolha um item para contribuição">
                            <input type="radio" class="contrib-pill" name="contribuicao" id="contrib_nenhum" value="" checked>
                            <label for="contrib_nenhum">🚫 Nenhum</label>
                            <?php foreach ($itens_colab as $i => $item): 
                                $quem_leva = $contribuicoes_existentes[$item] ?? [];
                                $ja_tem = !empty($quem_leva);
                            ?>
                            <input type="radio" class="contrib-pill" name="contribuicao" id="contrib_<?= $i ?>" value="<?= htmlspecialchars($item) ?>">
                            <label for="contrib_<?= $i ?>" class="<?= $ja_tem ? 'taken' : '' ?>">
                                <?= htmlspecialchars($item) ?>
                                <?php if($ja_tem): ?>
                                    <span class="pill-owner"><?= implode(', ', $quem_leva) ?></span>
                                <?php endif; ?>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="form-group">
                        <label for="rsvp_obs">Deseja trazer algo diferente ou deixar uma observação?</label>
                        <textarea
                            id="rsvp_obs"
                            name="contribuicao_obs"
                            class="form-control"
                            rows="2"
                            placeholder="Ex: Vou levar também um suco natural..."
                        ><?= htmlspecialchars($_POST['contribuicao_obs'] ?? '') ?></textarea>
                    </div>

                    <button type="submit" class="btn-submit" id="btn_confirmar" aria-label="Confirmar presença no evento">
                        <i class="fa-solid fa-circle-check" aria-hidden="true"></i> Confirmar Presença
                    </button>

                    <p class="privacy" role="note">
                        <i class="fa-solid fa-shield-halved" aria-hidden="true"></i>
                        Seus dados são usados apenas para gestão deste evento.
                    </p>
                </form>
            </div>
            <?php endif; ?>

        </div><!-- .card -->

        <!-- Footer -->
        <div class="rsvp-footer" role="contentinfo">
            <img src="images/logo-melodias.png" alt="Rede Melodias" loading="lazy">
            Realização: <a href="<?= $site_url ?>" target="_blank" rel="noopener">Rede Melodias</a>
        </div>

    </div><!-- .card-wrap -->

    <script>
    function stepAcomp(delta) {
        const input = document.getElementById('rsvp_acomp');
        if (!input) return;
        const val = parseInt(input.value) + delta;
        input.value = Math.max(0, Math.min(20, val));
    }

    // Simple form protection against double-submit
    const form = document.querySelector('form');
    const btn  = document.getElementById('btn_confirmar');
    if (form && btn) {
        form.addEventListener('submit', function() {
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Enviando...';
        });
    }
    </script>
</body>
</html>
