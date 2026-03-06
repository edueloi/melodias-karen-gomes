<?php
// ==========================================
// 1. BACK-END: BANCO DE DADOS E FORMULÁRIO
// ==========================================
$db_file = 'banco_melodias.sqlite';
$mensagem = ''; 

try {
    $pdo = new PDO("sqlite:$db_file");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Cria a tabela se não existir
    $query_tabela = "CREATE TABLE IF NOT EXISTS profissionais (
                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                        nome TEXT NOT NULL,
                        especialidade TEXT NOT NULL,
                        email TEXT NOT NULL,
                        whatsapp TEXT NOT NULL,
                        senha TEXT,
                        genero TEXT DEFAULT 'Não declarado',
                        role INTEGER DEFAULT 1,
                        status TEXT DEFAULT 'ativo',
                        data_cadastro DATETIME DEFAULT CURRENT_TIMESTAMP,
                        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
                    )";
    $pdo->exec($query_tabela);
    
    // Adiciona coluna genero se não existir
    try { $pdo->exec("ALTER TABLE profissionais ADD COLUMN genero TEXT DEFAULT 'Não declarado'"); } catch(Exception $e){}

    // Processa o cadastro
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $nome = htmlspecialchars($_POST['nome']);
        $especialidade = htmlspecialchars($_POST['especialidade']);
        $genero = htmlspecialchars($_POST['genero'] ?? 'Não declarado');
        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        $whatsapp = htmlspecialchars($_POST['whatsapp']);

        // Verifica se email já existe
        $check = $pdo->prepare("SELECT id FROM profissionais WHERE email = :email");
        $check->execute([':email' => $email]);
        
        if ($check->fetch()) {
            $mensagem = "<div class='alerta erro'>⚠️ Este e-mail já foi cadastrado. Se já é membro, <a href='login' style='color: #6e2b3a; font-weight: bold;'>faça login aqui</a>.</div>";
        } else {
            // Cria usuário com status PENDENTE aguardando aprovação
            $stmt = $pdo->prepare("INSERT INTO profissionais (nome, especialidade, genero, email, whatsapp, status, role) VALUES (:nome, :especialidade, :genero, :email, :whatsapp, 'pendente', 1)");
            $stmt->bindParam(':nome', $nome);
            $stmt->bindParam(':especialidade', $especialidade);
            $stmt->bindParam(':genero', $genero);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':whatsapp', $whatsapp);

            if ($stmt->execute()) {
                $mensagem = "<div class='alerta sucesso'>✨ Solicitação enviada com sucesso!<br><br>Sua solicitação será analisada em breve. Você receberá um e-mail quando seu acesso for liberado.</div>";
            } else {
                $mensagem = "<div class='alerta erro'>❌ Erro ao enviar solicitação. Tente novamente.</div>";
            }
        }
    }
} catch (PDOException $e) {
    $mensagem = "<div class='alerta erro'>Erro no sistema: " . $e->getMessage() . "</div>";
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Melodias - Rede de Profissionais em Saúde Mental de Tatuí</title>
    
    <!-- SEO & Social Sharing (Open Graph) -->
    <meta name="description" content="A maior rede de profissionais em saúde mental de Tatuí e região. Conecte-se com psicólogos, médicos e outros especialistas.">
    <meta name="keywords" content="saúde mental, tatuí, psicólogos, psiquiatras, profissionais de saúde, melodias">
    <meta property="og:title" content="Melodias - Rede de Profissionais em Saúde Mental">
    <meta property="og:description" content="Conecte-se com os melhores profissionais de saúde mental da região de Tatuí.">
    <meta property="og:image" content="https://melodias.karengomes.com.br/images/share-banner.png">
    <meta property="og:url" content="https://melodias.karengomes.com.br/">
    <meta property="og:type" content="website">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="theme-color" content="#6e2b3a">

    <link rel="icon" type="image/png" href="images/favicon.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Playfair+Display:wght@600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: #6e2b3a;
            --primary-dark: #4a1d27;
            --primary-light: #8d3a4d;
            --secondary: #1e293b;
            --secondary-dark: #0f172a;
            --accent: #d4a574;
            --bg-light: #f8fafc;
            --bg-white: #ffffff;
            --text-dark: #2c2c2c;
            --text-muted: #666666;
            --border: #e5e7eb;
            --success: #10b981;
            --error: #ef4444;
        }

        html { 
            scroll-behavior: smooth; 
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background-color: var(--bg-light);
            color: var(--text-dark);
            line-height: 1.6;
            overflow-x: hidden;
        }

        /* === NAVBAR === */
        .navbar {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(10px);
            padding: 20px 5%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            position: sticky;
            top: 0;
            z-index: 1000;
            transition: all 0.3s ease;
        }

        .navbar.scrolled {
            padding: 15px 5%;
            box-shadow: 0 6px 25px rgba(0,0,0,0.12);
        }

        .logo-container {
            display: flex;
            align-items: center;
            gap: 15px;
            text-decoration: none;
        }

        .logo-img {
            height: 55px;
            width: auto;
            transition: transform 0.3s ease;
        }

        .logo-img:hover {
            transform: scale(1.05);
        }

        .logo-text {
            font-family: 'Playfair Display', Georgia, serif;
            font-size: 1.8em;
            font-weight: 700;
            color: var(--primary);
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 35px;
        }

        .nav-links a {
            text-decoration: none;
            color: var(--text-dark);
            font-weight: 500;
            font-size: 0.95em;
            position: relative;
            transition: color 0.3s ease;
        }

        .nav-links a:not(.btn-login):hover {
            color: var(--primary);
        }

        .nav-links a:not(.btn-login)::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 0;
            height: 2px;
            background: var(--primary);
            transition: width 0.3s ease;
        }

        .nav-links a:not(.btn-login):hover::after {
            width: 100%;
        }

        .btn-login {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white !important;
            padding: 12px 28px;
            border-radius: 50px;
            font-weight: 600;
            box-shadow: 0 4px 15px rgba(110, 43, 58, 0.3);
            transition: all 0.3s ease;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(110, 43, 58, 0.4);
        }

        .btn-login::after {
            display: none;
        }

        /* === HERO SECTION === */
        .hero {
            background: linear-gradient(135deg, var(--secondary-dark) 0%, var(--secondary) 50%, var(--primary-dark) 100%);
            color: white;
            text-align: center;
            padding: 120px 5% 100px;
            position: relative;
            overflow: hidden;
        }

        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,') no-repeat bottom;
            background-size: cover;
            opacity: 0.5;
        }

        .hero-content {
            position: relative;
            z-index: 1;
            max-width: 900px;
            margin: 0 auto;
            animation: fadeInUp 0.8s ease;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .hero-badge {
            display: inline-block;
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            padding: 10px 24px;
            border-radius: 50px;
            font-size: 0.9em;
            font-weight: 500;
            margin-bottom: 25px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .hero h1 {
            font-family: 'Playfair Display', Georgia, serif;
            font-size: 4.5em;
            font-weight: 800;
            margin: 20px 0;
            letter-spacing: -2px;
            line-height: 1.1;
        }

        .hero-subtitle {
            font-size: 1.3em;
            opacity: 0.95;
            max-width: 700px;
            margin: 0 auto 40px;
            font-weight: 300;
            line-height: 1.7;
        }

        .hero-btn {
            display: inline-block;
            background: white;
            color: var(--primary);
            padding: 18px 45px;
            border-radius: 50px;
            font-size: 1.1em;
            font-weight: 700;
            text-decoration: none;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
            margin-top: 10px;
        }

        .hero-btn:hover {
            transform: translateY(-4px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.3);
        }

        /* === ALERT MESSAGES === */
        .alert-container {
            padding: 40px 5%;
            animation: fadeIn 0.5s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }

        .alerta {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px 30px;
            border-radius: 15px;
            text-align: center;
            font-size: 1.05em;
            animation: slideDown 0.5s ease;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .sucesso {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            color: #155724;
            border: 2px solid #b1dfbb;
        }

        .erro {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            color: #721c24;
            border: 2px solid #f1b0b7;
        }

        /* === SECTIONS === */
        .secao {
            padding: 100px 5%;
            text-align: center;
        }

        .secao-titulo {
            font-family: 'Playfair Display', Georgia, serif;
            font-size: 3em;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 15px;
            position: relative;
            display: inline-block;
        }

        .secao-titulo::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 4px;
            background: linear-gradient(90deg, var(--primary) 0%, var(--accent) 100%);
            border-radius: 2px;
        }

        .secao-descricao {
            max-width: 800px;
            margin: 30px auto 60px;
            font-size: 1.15em;
            color: var(--text-muted);
            line-height: 1.8;
        }

        /* === CARDS === */
        .grid-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 35px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .card {
            background: white;
            padding: 40px 30px;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
            overflow: hidden;
        }

        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, var(--primary) 0%, var(--accent) 100%);
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }

        .card:hover::before {
            transform: scaleX(1);
        }

        .card:hover {
            transform: translateY(-15px);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
        }

        .card h3 {
            font-size: 1.5em;
            color: var(--secondary);
            margin: 20px 0 15px;
            font-weight: 700;
        }

        .card p {
            color: var(--text-muted);
            line-height: 1.7;
            font-size: 0.98em;
        }

        /* === SPLIT SECTION === */
        .split-layout {
            display: flex;
            align-items: center;
            gap: 50px;
            max-width: 1200px;
            margin: 0 auto;
            text-align: left;
        }
        .split-layout.reverse {
            flex-direction: row-reverse;
        }
        .split-content {
            flex: 1;
        }
        .split-content h3 {
            font-size: 2.2em;
            color: var(--secondary);
            margin-bottom: 20px;
            font-weight: 700;
            line-height: 1.2;
        }
        .split-content p {
            color: var(--text-muted);
            line-height: 1.8;
            font-size: 1.1em;
            margin-bottom: 20px;
        }
        .split-image {
            flex: 1;
            position: relative;
        }
        .split-image img {
            width: 100%;
            border-radius: 20px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.15);
            transition: transform 0.3s ease;
            object-fit: cover;
            max-height: 400px;
        }
        .split-image img:hover {
            transform: scale(1.02);
        }
        @media (max-width: 900px) {
            .split-layout, .split-layout.reverse {
                flex-direction: column;
                text-align: center;
                gap: 30px;
            }
        }

        /* === FORM SECTION === */
        .form-container {
            max-width: 650px;
            margin: 0 auto;
            background: white;
            padding: 50px 45px;
            border-radius: 25px;
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.1);
        }

        .formulario-grupo {
            display: flex;
            flex-direction: column;
            gap: 25px;
        }

        .formulario-grupo label {
            font-weight: 600;
            color: var(--secondary);
            margin-bottom: 8px;
            display: block;
            font-size: 0.95em;
        }

        .formulario-grupo input {
            padding: 16px 20px;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            font-size: 1em;
            font-family: 'Inter', sans-serif;
            transition: all 0.3s ease;
            width: 100%;
        }

        .formulario-grupo input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(110, 43, 58, 0.1);
            transform: translateY(-2px);
        }

        .btn-enviar {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 18px 40px;
            border: none;
            border-radius: 50px;
            font-size: 1.1em;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 15px;
            box-shadow: 0 8px 25px rgba(110, 43, 58, 0.3);
        }

        .btn-enviar:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 35px rgba(110, 43, 58, 0.4);
        }

        .btn-enviar:active {
            transform: translateY(-1px);
        }

        /* === FOOTER === */
        .rodape {
            background: linear-gradient(135deg, var(--secondary-dark) 0%, var(--primary-dark) 100%);
            color: white;
            text-align: center;
            padding: 60px 5%;
        }

        .rodape h3 {
            font-family: 'Playfair Display', Georgia, serif;
            font-size: 2em;
            margin-bottom: 15px;
            font-weight: 600;
        }

        .rodape p {
            opacity: 0.9;
            line-height: 1.8;
        }

        .rodape a {
            color: var(--accent);
            text-decoration: none;
            border-bottom: 1px solid var(--accent);
            transition: opacity 0.3s ease;
        }

        .rodape a:hover {
            opacity: 0.8;
        }

        /* === RESPONSIVE === */
        @media (max-width: 992px) {
            .hero h1 {
                font-size: 3.5em;
            }

            .nav-links {
                gap: 20px;
            }

            .secao-titulo {
                font-size: 2.5em;
            }

            .grid-cards {
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                gap: 25px;
            }
        }

        .menu-toggle {
            display: none;
            background: none;
            border: none;
            font-size: 1.8em;
            color: var(--primary);
            cursor: pointer;
        }
        .d-mobile-only {
            display: none;
        }

        @media (max-width: 768px) {
            .navbar {
                flex-direction: row;
                justify-content: space-between;
                padding: 15px 5%;
            }

            .menu-toggle {
                display: block;
                position: relative;
                z-index: 10001; /* Ficar acima de nav-links e flutuantes */
            }

            .nav-links {
                display: flex;
                flex-direction: column;
                justify-content: center;
                align-items: center;
                gap: 25px; /* Reduzido levemente */
                width: 100vw;
                height: 100vh;
                position: fixed;
                top: 0;
                left: 0;
                background: rgba(255, 255, 255, 0.98);
                backdrop-filter: blur(20px);
                z-index: 10000; /* Cobrir widgets (9999) */
                opacity: 0;
                visibility: hidden;
                transform: scale(0.95);
                transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            }

            .nav-links.active {
                opacity: 1;
                visibility: visible;
                transform: scale(1);
            }

            .nav-links a {
                width: auto;
                text-align: center;
                font-size: 1.4em;
                padding: 0;
            }

            .nav-links a.btn-login {
                width: auto;
                max-width: none;
                font-size: 1.1em;
                padding: 14px 35px;
                border-radius: 50px;
                margin-top: 5px;
            }
            
            .nav-links a.btn-instagram {
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 8px;
                width: auto;
                font-size: 1.1em;
                color: #E1306C; /* Cor do IG */
                background: rgba(225, 48, 108, 0.1);
                padding: 12px 25px;
                border-radius: 50px;
                font-weight: 600;
                transition: all 0.3s;
            }

            .nav-links a.btn-instagram.d-mobile-only {
                display: flex !important;
            }

            .hero {
                padding: 80px 5% 60px;
            }

            .hero h1 {
                font-size: 2.5em;
            }

            .hero-subtitle {
                font-size: 1.1em;
            }

            .secao {
                padding: 60px 5%;
            }

            .secao-titulo {
                font-size: 2em;
            }

            .form-container {
                padding: 35px 25px;
            }

            .logo-img {
                height: 40px;
            }

            .logo-text {
                font-size: 1.4em;
            }

            .split-content {
                padding: 30px 20px !important;
            }
            .btn-enviar {
                font-size: 1.05em !important;
                padding: 16px !important;
                white-space: normal;
            }
            
            .floating-widgets {
                bottom: 20px;
                right: 20px;
                gap: 10px;
            }
            .float-btn {
                width: 50px;
                height: 50px;
                font-size: 22px;
            }
        }

        @media (max-width: 576px) {
            .hero h1 {
                font-size: 2em;
            }

            .grid-cards {
                grid-template-columns: 1fr;
            }

            .card {
                padding: 30px 20px;
            }
        }
        /* === FLOATING WIDGETS === */
        .floating-widgets {
            position: fixed;
            bottom: 30px;
            right: 30px;
            display: flex;
            flex-direction: column;
            gap: 15px;
            z-index: 9999;
        }

        .float-btn {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            color: white;
            font-size: 28px;
            text-decoration: none;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
        }
        
        .float-btn:hover {
            transform: scale(1.1);
        }

        .float-whatsapp {
            background-color: #25D366;
        }

        .float-bot {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
        }

        /* === CHAT WINDOW === */
        .chat-window {
            position: fixed;
            bottom: 100px;
            right: 30px;
            width: 350px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            display: none;
            flex-direction: column;
            overflow: hidden;
            z-index: 9998;
            transform: translateY(20px);
            opacity: 0;
            transition: all 0.3s ease;
            font-family: 'Inter', sans-serif;
        }

        .chat-window.active {
            display: flex;
            transform: translateY(0);
            opacity: 1;
        }

        .chat-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .chat-header h4 {
            margin: 0;
            font-size: 1.1em;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .chat-close {
            background: none;
            border: none;
            color: white;
            font-size: 1.2em;
            cursor: pointer;
        }

        .chat-body {
            padding: 20px;
            height: 350px;
            overflow-y: auto;
            background: #f9fafb;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .chat-msg {
            max-width: 85%;
            padding: 12px 16px;
            border-radius: 15px;
            font-size: 0.9em;
            line-height: 1.5;
            animation: fadeInChat 0.3s ease;
        }

        @keyframes fadeInChat {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .msg-bot {
            background: white;
            border: 1px solid #eeeff1;
            align-self: flex-start;
            border-bottom-left-radius: 0;
            box-shadow: 0 2px 5px rgba(0,0,0,0.02);
            color: #333;
        }

        .msg-user {
            background: var(--primary);
            color: white;
            align-self: flex-end;
            border-bottom-right-radius: 0;
            box-shadow: 0 2px 5px rgba(110, 43, 58, 0.2);
        }

        .chat-options {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-top: 5px;
        }

        .chat-option-btn {
            background: white;
            border: 1px solid var(--primary);
            color: var(--primary);
            padding: 10px 15px;
            border-radius: 20px;
            font-size: 0.85em;
            cursor: pointer;
            transition: all 0.2s;
            text-align: left;
            font-weight: 500;
        }

        .chat-option-btn:hover {
            background: var(--primary);
            color: white;
        }
        
        @media (max-width: 400px) {
            .chat-window {
                width: calc(100% - 40px);
                right: 20px;
                bottom: 100px;
            }
        }
    </style>
</head>
<body>

    <nav class="navbar" id="navbar">
        <a href="#" class="logo-container">
            <img src="images/logo-melodias.png" alt="Melodias Logo" class="logo-img">
        </a>
        <button class="menu-toggle" id="menuToggle" aria-label="Abrir Menu">
            <i class="fas fa-bars"></i>
        </button>
        <div class="nav-links" id="navLinks">
            <a href="#sobre"><i class="fas fa-info-circle"></i> Sobre</a>
            <a href="#recursos"><i class="fas fa-star"></i> Recursos</a>
            <a href="https://instagram.com/psi.karengomes" target="_blank" class="btn-instagram d-mobile-only"><i class="fab fa-instagram"></i> Siga-nos</a>
            <a href="#cadastro"><i class="fas fa-user-plus"></i> Participar</a>
            <a href="login" class="btn-login">
                <i class="fas fa-sign-in-alt"></i> Área de Membros
            </a>
        </div>
    </nav>

    <header class="hero">
        <div class="hero-content">
            <div class="hero-badge">
                <i class="fas fa-map-marker-alt"></i> Profissionais de Tatuí
            </div>
            <h1>Melodias</h1>
            <p class="hero-subtitle">
                Conexões em Saúde Mental. Um espaço seguro para crescer, trocar ideias e fortalecer nossa profissão juntos.
            </p>
            <a href="#cadastro" class="hero-btn">
                <i class="fas fa-hands-helping"></i> Quero Fazer Parte
            </a>
        </div>
    </header>

    <?php if (!empty($mensagem)): ?>
    <div class="alert-container">
        <?php echo $mensagem; ?>
    </div>
    <?php endif; ?>

    <section id="sobre" class="secao">
        <h2 class="secao-titulo">Nosso Objetivo</h2>
        <p class="secao-descricao">
            Criar uma rede de apoio sólida entre psicólogos, psicanalistas e terapeutas. 
            Queremos facilitar encaminhamentos éticos e criar um ambiente colaborativo para discussão de casos e estudos.
        </p>

        <div class="grid-cards">
            <div class="card">
                <div style="font-size: 3em; margin-bottom: 15px;">💡</div>
                <h3>Troca de Ideias</h3>
                <p>Compartilhe experiências de consultório, tire dúvidas e debata abordagens terapêuticas com colegas de confiança.</p>
            </div>
            <div class="card">
                <div style="font-size: 3em; margin-bottom: 15px;">🤝</div>
                <h3>Grupos de Estudo</h3>
                <p>Encontros online e presenciais para aprofundamento teórico em Psicanálise, TCC, e outras abordagens.</p>
            </div>
            <div class="card">
                <div style="font-size: 3em; margin-bottom: 15px;">🔄</div>
                <h3>Encaminhamentos</h3>
                <p>Uma rede de confiança para indicar pacientes quando a demanda fugir da sua especialidade ou disponibilidade.</p>
            </div>
        </div>
    </section>

    <section id="comunidade" class="secao" style="background: white;">
        <div class="split-layout">
            <div class="split-image">
                <img src="images/conversa_psi.png" alt="Psicólogos conversando em grupo">
            </div>
            <div class="split-content">
                <h3>Rodas de Conversa e Apoio Mútuo</h3>
                <p>Nossa área principal de debates proporciona um ambiente acolhedor, onde psicólogos encontram a liberdade e a segurança necessárias para expor as complexidades de atendimentos, burocracias, medos e vitórias da prática clínica.</p>
                <p>O foco não está apenas nos referenciais teóricos, mas na rede humana. Compartilhamos vivências, dicas de gestão de consultório, e construímos uma comunidade baseada na escuta empática entre colegas de profissão.</p>
                <a href="#cadastro" class="btn-login" style="display: inline-block; margin-top: 15px; text-decoration: none;">Venha Conversar</a>
            </div>
        </div>
    </section>

    <section id="biblioteca-rede" class="secao" style="background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);">
        <div class="split-layout reverse">
            <div class="split-image">
                <img src="images/estudos_psi.png" alt="Materiais e espaço de estudo clínico">
            </div>
            <div class="split-content">
                <h3>Material Clínico e Grupos de Estudo</h3>
                <p>Nosso projeto Melodias é desenhado com a filosofia de que o aprendizado do terapeuta é contínuo. Formamos grupos focados no estudo de diferentes abordagens, auxiliando em manejos difíceis de casos.</p>
                <p>Sinta-se à vontade para consumir, explorar e também contribuir com nossa própria <strong>Biblioteca Digital</strong>: um espaço onde armazenamos acervos, livros e palestras voltadas à capacitação em Saúde Mental.</p>
                <a href="#cadastro" class="btn-login" style="display: inline-block; margin-top: 15px; text-decoration: none;">Explorar Biblioteca</a>
            </div>
        </div>
    </section>

    <section id="recursos" class="secao" style="background: var(--bg-white);">
        <h2 class="secao-titulo">Material e Conteúdo</h2>
        <p class="secao-descricao">Ao fazer parte do grupo e acessar a Área de Membros, você terá acesso a:</p>
        <div class="grid-cards">
            <div class="card">
                <div style="font-size: 3em; margin-bottom: 15px;">📚</div>
                <h3>E-Books Exclusivos</h3>
                <p>Materiais de apoio em formato digital criados por especialistas do nosso grupo para consulta rápida.</p>
            </div>
            <div class="card">
                <div style="font-size: 3em; margin-bottom: 15px;">📅</div>
                <h3>Eventos e Palestras</h3>
                <p>Calendário completo de workshops, supervisões em grupo e encontros presenciais em Tatuí.</p>
            </div>
            <div class="card">
                <div style="font-size: 3em; margin-bottom: 15px;">💬</div>
                <h3>Fórum de Discussões</h3>
                <p>Espaço online para tirar dúvidas, compartilhar casos (com sigilo) e trocar experiências clínicas.</p>
            </div>
        </div>
    </section>

    <section id="cadastro" class="secao" style="background: white; padding-top: 80px; padding-bottom: 150px;">
        <div class="split-layout">
            <div class="split-image" style="display: flex; flex-direction: column; gap: 30px;">
                <img src="images/junte_se.png" alt="Profissional dando boas vindas">
                <div style="background: var(--bg-light); padding: 35px; border-radius: 20px; text-align: left;">
                    <h4 style="font-size: 1.4em; color: var(--secondary); margin-bottom: 20px;">Por que participar?</h4>
                    <ul style="list-style: none; padding: 0; display: flex; flex-direction: column; gap: 15px; color: var(--text-muted);">
                        <li style="display: flex; align-items: flex-start; gap: 12px;">
                            <i class="fas fa-check-circle" style="color: var(--success); margin-top: 4px; font-size: 1.1em;"></i> 
                            <span>Amplie sua rede de encaminhamentos éticos da nossa região.</span>
                        </li>
                        <li style="display: flex; align-items: flex-start; gap: 12px;">
                            <i class="fas fa-check-circle" style="color: var(--success); margin-top: 4px; font-size: 1.1em;"></i> 
                            <span>Acesse conteúdos exclusivos em nossa biblioteca digital restrita.</span>
                        </li>
                        <li style="display: flex; align-items: flex-start; gap: 12px;">
                            <i class="fas fa-check-circle" style="color: var(--success); margin-top: 4px; font-size: 1.1em;"></i> 
                            <span>Tire dúvidas e debata em fóruns sigilosos, blindados a leigos.</span>
                        </li>
                        <li style="display: flex; align-items: flex-start; gap: 12px;">
                            <i class="fas fa-check-circle" style="color: var(--success); margin-top: 4px; font-size: 1.1em;"></i> 
                            <span>Mantenha-se fortalecido junto da sua classe profissional.</span>
                        </li>
                    </ul>
                </div>
            </div>
            
            <div class="split-content" style="background: var(--bg-white); padding: 45px 40px; border-radius: 25px; box-shadow: 0 15px 50px rgba(0, 0, 0, 0.06); border: 1px solid var(--border);">
                <h2 style="font-family: 'Playfair Display', serif; font-size: 1.9em; color: var(--primary); margin-bottom: 15px; text-align: center;">Faça Parte da Rede</h2>
                <p style="color: var(--text-muted); margin-bottom: 35px; font-size: 1.05em; line-height: 1.7; text-align: center;">
                    Preencha os dados abaixo e entraremos em contato com você. <br>Após aprovação pela coordenação, liberamos seu acesso.
                </p>
                
                <form action="index#cadastro" method="POST" class="formulario-grupo">
                    <div>
                        <label for="nome">
                            <i class="fas fa-user" style="opacity: 0.7; margin-right: 5px;"></i> Nome Completo
                        </label>
                        <input type="text" id="nome" name="nome" required placeholder="Ex: Maria Silva"
                               value="<?php echo isset($_POST['nome']) ? htmlspecialchars($_POST['nome']) : ''; ?>"
                               style="background: #f9fafb;">
                    </div>
                    <div>
                        <label for="especialidade">
                            <i class="fas fa-briefcase" style="opacity: 0.7; margin-right: 5px;"></i> Sua Profissão
                        </label>
                        <input type="text" id="especialidade" name="especialidade" required placeholder="Ex: Psicólogo, Médico, etc."
                               value="<?php echo isset($_POST['especialidade']) ? htmlspecialchars($_POST['especialidade']) : ''; ?>"
                               style="background: #f9fafb;">
                    </div>
                    <div>
                        <label for="genero">
                            <i class="fas fa-venus-mars" style="opacity: 0.7; margin-right: 5px;"></i> Gênero
                        </label>
                        <select id="genero" name="genero" required style="background: #f9fafb; width: 100%; padding: 16px 20px; border: 2px solid #e0e0e0; border-radius: 12px; font-size: 1em; font-family: 'Inter', sans-serif;">
                            <option value="Masculino">Masculino</option>
                            <option value="Feminino">Feminino</option>
                            <option value="Não declarado" selected>Não declarado</option>
                        </select>
                    </div>
                    <div>
                        <label for="email">
                            <i class="fas fa-envelope" style="opacity: 0.7; margin-right: 5px;"></i> Seu E-mail
                        </label>
                        <input type="email" id="email" name="email" required placeholder="voce@email.com"
                               style="background: #f9fafb;">
                    </div>
                    <div>
                        <label for="whatsapp">
                            <i class="fab fa-whatsapp" style="opacity: 0.7; margin-right: 5px;"></i> WhatsApp
                        </label>
                        <input type="tel" id="whatsapp" name="whatsapp" required placeholder="(15) 99999-9999"
                               value="<?php echo isset($_POST['whatsapp']) ? htmlspecialchars($_POST['whatsapp']) : ''; ?>"
                               style="background: #f9fafb;">
                    </div>
                    <button type="submit" class="btn-enviar" style="width: 100%; margin-top: 10px; font-size: 1.15em; display: flex; justify-content: center; gap: 10px; align-items: center;">
                        <i class="fas fa-paper-plane"></i> Enviar Solicitação
                    </button>
                </form>
            </div>
        </div>
    </section>

    <footer class="rodape">
        <h3>Precisa de Ajuda?</h3>
        <p style="max-width: 600px; margin: 15px auto;">
            Se tiver dúvidas sobre o grupo ou dificuldades no acesso, entre em contato conosco.
        </p>
        <p style="margin-top: 40px; opacity: 0.8;">
            &copy; <?php echo date('Y'); ?> <strong>Melodias</strong> - Rede de Saúde Mental de Tatuí<br>
            <span style="font-size: 0.9em; margin-top: 10px; display: inline-block;">
                Iniciativa de <a href="https://instagram.com/psi.karengomes" target="_blank" style="color: var(--accent); white-space: nowrap;"><i class="fab fa-instagram"></i> Karen Gomes - Psicologia</a>
            </span>
        </p>
    </footer>

    <!-- FLOATING WIDGETS -->
    <div class="floating-widgets">
        <button class="float-btn float-bot" onclick="toggleChat()" title="Assistente Virtual Melodias">
            <i class="fas fa-robot"></i>
        </button>
        <a href="https://wa.me/5515991345333" target="_blank" class="float-btn float-whatsapp" title="Falar no WhatsApp">
            <i class="fab fa-whatsapp"></i>
        </a >
    </div>

    <!-- CHATBOT WINDOW -->
    <div class="chat-window" id="chatWindow">
        <div class="chat-header">
            <h4><img src="images/favicon.png" style="width: 24px; height: 24px;"> Assistente Melodias</h4>
            <button class="chat-close" onclick="toggleChat()"><i class="fas fa-times"></i></button>
        </div>
        <div class="chat-body" id="chatBody">
            <div class="chat-msg msg-bot">
                Olá! 👋 Sou a assistente virtual da rede Melodias. Como posso te orientar hoje?
            </div>
            <div class="chat-options" id="chatOptions">
                <button class="chat-option-btn" onclick="sendMsg('Como o projeto funciona?', 1)">Como o projeto funciona?</button>
                <button class="chat-option-btn" onclick="sendMsg('Quem pode participar?', 2)">Quem pode participar?</button>
                <button class="chat-option-btn" onclick="sendMsg('Quero informações sobre os grupos de estudo', 4)">Quero informações sobre os grupos de estudo</button>
                <button class="chat-option-btn" onclick="sendMsg('Tirar dúvidas específicas', 3)"><i class="fab fa-whatsapp"></i> Falar com a Psicóloga Karen</button>
            </div>
        </div>
    </div>

    <script>
        // Lógica do Menu Hamburguer
        const menuToggle = document.getElementById('menuToggle');
        const navLinks = document.getElementById('navLinks');
        
        menuToggle.addEventListener('click', () => {
            navLinks.classList.toggle('active');
            
            // Travar/Destravar rolagem da tela
            if(navLinks.classList.contains('active')) {
                document.body.style.overflow = 'hidden';
                menuToggle.querySelector('i').classList.replace('fa-bars', 'fa-times');
            } else {
                document.body.style.overflow = '';
                menuToggle.querySelector('i').classList.replace('fa-times', 'fa-bars');
            }
        });

        // Fechar menu ao clicar em um link no mobile
        document.querySelectorAll('.nav-links a').forEach(link => {
            link.addEventListener('click', () => {
                if(window.innerWidth <= 768) {
                    navLinks.classList.remove('active');
                    document.body.style.overflow = '';
                    menuToggle.querySelector('i').classList.replace('fa-times', 'fa-bars');
                }
            });
        });

        // Lógica do Chatbot
        function toggleChat() {
            const chat = document.getElementById('chatWindow');
            if (chat.classList.contains('active')) {
                chat.classList.remove('active');
                setTimeout(() => chat.style.display = 'none', 300);
            } else {
                chat.style.display = 'flex';
                setTimeout(() => chat.classList.add('active'), 10);
            }
        }

        function sendMsg(text, type) {
            const chatBody = document.getElementById('chatBody');
            const options = document.getElementById('chatOptions');
            
            // Oculta opções
            options.style.display = 'none';

            // Adiciona mensagem do usuário
            chatBody.insertAdjacentHTML('beforeend', `<div class="chat-msg msg-user">${text}</div>`);
            chatBody.scrollTop = chatBody.scrollHeight;

            // Simula "digitando..."
            const typingId = 'typing-' + Date.now();
            setTimeout(() => {
                chatBody.insertAdjacentHTML('beforeend', `<div id="${typingId}" class="chat-msg msg-bot" style="color: #aaa; font-style: italic;">Digitando...</div>`);
                chatBody.scrollTop = chatBody.scrollHeight;
            }, 300);

            // Resposta do bot
            setTimeout(() => {
                document.getElementById(typingId).remove(); // remove digitando...
                
                let response = '';
                if (type === 1) {
                    response = 'O <strong>Melodias</strong> é uma rede de apoio presencial e digital para profissionais de Saúde Mental de Tatuí.<br><br>Aqui trocamos experiências clínicas supervisionadas em ambiente seguro, orientamos inícios de carreira, compartilhamos ferramentas de trabalho (documentos, planilhas) e cultivamos bons vínculos de encaminhamento!';
                } else if (type === 2) {
                    response = 'Nosso foco são psicólogos já formados (de diversas abordagens), psicanalistas e estudantes dos <strong>últimos anos</strong> matriculados ativamente em clínicas-escola atuando em Tatuí/SP e região.';
                } else if (type === 4) {
                    response = 'Realizamos encontros periodicamente focados em aprofundar temas psicanalíticos, manejo de conflitos familiares e elaboração de documentos na TCC. Os encontros costumam ser online via reuniões fechadas!';
                } else if (type === 3) {
                    response = 'Excelente! Vou te encaminhar direto para o WhatsApp da <strong>Psicóloga Karen Gomes</strong>, idealizadora da rede. Ela poderá te esclarecer todos os detalhes.';
                    setTimeout(() => {
                        window.open('https://wa.me/5515991345333?text=Olá Karen! Estava no site da rede Melodias e o assistente me enviou pra cá. Gostaria de tirar umas dúvidas com você.', '_blank');
                    }, 2500);
                }

                chatBody.insertAdjacentHTML('beforeend', `<div class="chat-msg msg-bot">${response}</div>`);
                chatBody.scrollTop = chatBody.scrollHeight;

                if(type !== 3) {
                     setTimeout(() => {
                        options.style.display = 'flex';
                        chatBody.appendChild(options); // move pro final
                        chatBody.scrollTop = chatBody.scrollHeight;
                     }, 1500);
                }
            }, 1200); // tempo de espera simulado
        }

        // --- Código Antigo Mantido: Scroll effect para navbar ---
        window.addEventListener('scroll', function() {
            const navbar = document.getElementById('navbar');
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });

        // Smooth scroll para links internos
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Animação de entrada para cards
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver(function(entries) {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);

        document.querySelectorAll('.card').forEach(card => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(30px)';
            card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            observer.observe(card);
        });
    </script>
</body>
</html>
