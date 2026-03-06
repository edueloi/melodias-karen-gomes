<?php
require_once 'config.php';

// ==========================================
// AUTO-SETUP: Garante colunas necessárias
// ==========================================
try {
    $pdo = getDB();
    try { $pdo->exec("ALTER TABLE profissionais ADD COLUMN role TEXT DEFAULT 'user'"); } catch(Exception $e){}
    try { $pdo->exec("ALTER TABLE profissionais ADD COLUMN status TEXT DEFAULT 'ativo'"); } catch(Exception $e){}
    try { $pdo->exec("ALTER TABLE profissionais ADD COLUMN senha TEXT"); } catch(Exception $e){}
} catch(Exception $e) {}

$erro = '';

// Redireciona se já estiver logado
if (isset($_SESSION['logado']) && $_SESSION['logado'] === true) {
    header("Location: painel.php");
    exit;
}

// Processa o login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $senha = $_POST['senha'];

    if (empty($email) || empty($senha)) {
        $erro = "Preencha todos os campos!";
    } else {
        try {
            $pdo = getDB();
            
            // Busca usuário por email
            $stmt = $pdo->prepare("SELECT * FROM profissionais WHERE email = :email");
            $stmt->execute([':email' => $email]);
            $usuario = $stmt->fetch();

            if ($usuario) {
                // Verifica se usuário tem senha definida
                if (!isset($usuario['senha']) || empty($usuario['senha']) || $usuario['senha'] === null) {
                    $erro = "Sua conta precisa ser migrada. Execute o arquivo setup_banco.php ou entre em contato com o administrador.";
                }
                // Verifica status da conta
                elseif (isset($usuario['status']) && $usuario['status'] === 'inativo') {
                    $erro = "Sua conta está suspensa. Entre em contato com o administrador.";
                } 
                // Verifica senha
                elseif (verifyPassword($senha, $usuario['senha'])) {
                    // Login bem-sucedido
                    $_SESSION['logado'] = true;
                    $_SESSION['id_usuario'] = $usuario['id'];
                    $_SESSION['nome_usuario'] = $usuario['nome'];
                    $_SESSION['email_usuario'] = $usuario['email'];
                    $_SESSION['role'] = isset($usuario['role']) && !empty($usuario['role']) ? $usuario['role'] : 'user';
                    
                    // Atualiza último acesso
                    try {
                        $pdo->prepare("UPDATE profissionais SET updated_at = CURRENT_TIMESTAMP WHERE id = ?")->execute([$usuario['id']]);
                    } catch(Exception $e) {}
                    
                    header("Location: painel");
                    exit;
                } else {
                    $erro = "Senha incorreta. Tente novamente.";
                }
            } else {
                // Se for o email do super admin, instrui a rodar o setup
                if ($email === 'karen.l.s.gomes@gmail.com') {
                    $erro = "Sistema ainda não configurado. <br><br><strong>Acesse primeiro:</strong><br><a href='setup_banco.php' style='color: #6e2b3a; font-weight: bold; text-decoration: underline;'>setup_banco.php</a><br><br>para criar o banco de dados e sua conta de administrador.";
                } else {
                    $erro = "E-mail não encontrado no sistema.";
                }
            }
        } catch (PDOException $e) {
            $erro = "Erro no sistema: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#6e2b3a">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="Melodias">
    <title>Login — Melodias | Sistema de Gestão</title>
    
    <!-- SEO & Social Sharing -->
    <meta name="description" content="Acesse o Sistema de Gestão Melodias. Plataforma exclusiva para membros da rede de saúde mental.">
    <meta property="og:title" content="Login — Melodias">
    <meta property="og:description" content="Plataforma de gestão Melodias.">
    <meta property="og:image" content="https://melodias.karengomes.com.br/images/share-banner.jpg">
    <meta name="robots" content="noindex, nofollow">
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="images/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="images/favicon.png">
    <link rel="apple-touch-icon" href="images/favicon.png">
    <link rel="shortcut icon" href="images/favicon.ico">
    <!-- Fonts & Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
            --secondary: #1b333d;
            --bg-gradient: linear-gradient(135deg, #1b333d 0%, #6e2b3a 100%);
            --shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            --shadow-hover: 0 25px 70px rgba(0, 0, 0, 0.4);
        }

        body {
            font-family: 'Inter', 'Segoe UI', sans-serif;
            background: var(--bg-gradient);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
            position: relative;
            overflow: hidden;
        }

        /* Animated background circles */
        body::before,
        body::after {
            content: '';
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.05);
            animation: float 20s infinite ease-in-out;
        }

        body::before {
            width: 400px;
            height: 400px;
            top: -200px;
            left: -200px;
        }

        body::after {
            width: 300px;
            height: 300px;
            bottom: -150px;
            right: -150px;
            animation-delay: -10s;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0) rotate(0deg); }
            50% { transform: translateY(-50px) rotate(180deg); }
        }

        .login-wrapper {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 440px;
        }

        .login-container {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(10px);
            border-radius: 24px;
            box-shadow: var(--shadow);
            padding: 50px 40px;
            text-align: center;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            animation: slideUp 0.6s ease;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .login-container:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-hover);
        }

        .logo {
            width: 80px;
            height: 80px;
            background: var(--bg-gradient);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 25px;
            font-size: 2.5em;
            box-shadow: 0 10px 30px rgba(110, 43, 58, 0.3);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        .login-container h2 {
            color: var(--primary);
            font-size: 2em;
            font-weight: 700;
            margin-bottom: 8px;
            letter-spacing: -0.5px;
        }

        .login-container p {
            color: #666;
            margin-bottom: 35px;
            font-size: 0.95em;
            font-weight: 400;
        }

        .erro {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%);
            color: white;
            padding: 16px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            font-size: 0.9em;
            box-shadow: 0 8px 20px rgba(238, 90, 111, 0.3);
            animation: shake 0.5s ease;
            display: flex;
            align-items: center;
            gap: 12px;
            text-align: left;
        }

        .erro i {
            font-size: 1.3em;
            flex-shrink: 0;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-10px); }
            75% { transform: translateX(10px); }
        }

        .form-login {
            display: flex;
            flex-direction: column;
            gap: 20px;
            text-align: left;
        }

        .input-group {
            position: relative;
        }

        .input-group label {
            display: block;
            font-weight: 600;
            color: var(--secondary);
            font-size: 0.9em;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .input-group label i {
            color: var(--primary);
            font-size: 1.1em;
        }

        .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-group input {
            padding: 14px 18px;
            padding-right: 45px;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            width: 100%;
            font-size: 1em;
            transition: all 0.3s ease;
            font-family: 'Inter', sans-serif;
            background: white;
        }

        .input-group input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(110, 43, 58, 0.1);
            transform: translateY(-2px);
        }

        .toggle-password {
            position: absolute;
            right: 15px;
            background: none;
            border: none;
            color: #999;
            cursor: pointer;
            font-size: 1.1em;
            padding: 5px;
            transition: color 0.2s ease;
        }

        .toggle-password:hover {
            color: var(--primary);
        }

        .btn-entrar {
            background: var(--bg-gradient);
            color: white;
            border: none;
            padding: 16px 32px;
            border-radius: 12px;
            font-size: 1.05em;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
            box-shadow: 0 10px 25px rgba(110, 43, 58, 0.3);
            position: relative;
            overflow: hidden;
        }

        .btn-entrar::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s ease;
        }

        .btn-entrar:hover::before {
            left: 100%;
        }

        .btn-entrar:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 35px rgba(110, 43, 58, 0.4);
        }

        .btn-entrar:active {
            transform: translateY(-1px);
        }

        .btn-entrar:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .links {
            margin-top: 30px;
            padding-top: 25px;
            border-top: 1px solid #e0e0e0;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .link-item {
            color: var(--primary);
            text-decoration: none;
            font-size: 0.95em;
            font-weight: 500;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.3s ease;
            padding: 8px;
            border-radius: 8px;
        }

        .link-item:hover {
            background: rgba(110, 43, 58, 0.05);
            transform: translateX(5px);
        }

        .link-item i {
            font-size: 0.9em;
        }

        /* Responsive */
        @media (max-width: 576px) {
            .login-container {
                padding: 40px 30px;
            }

            .login-container h2 {
                font-size: 1.6em;
            }

            .logo {
                width: 70px;
                height: 70px;
                font-size: 2em;
            }
        }

        /* Loading Overlay Premium */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: var(--bg-gradient);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            flex-direction: column;
            gap: 25px;
        }

        .loading-overlay.active {
            display: flex;
            animation: fadeInOverlay 0.4s ease forwards;
        }

        @keyframes fadeInOverlay {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .loading-logo-box {
            position: relative;
            width: 140px;
            height: 140px;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .loading-logo-box img {
            width: 100px;
            height: auto;
            z-index: 2;
            filter: brightness(0) invert(1);
            animation: pulse-logo 2s infinite ease-in-out;
        }

        .loading-spinner-rings {
            position: absolute;
            width: 100%;
            height: 100%;
            border: 4px solid rgba(255,255,255,0.1);
            border-top: 4px solid white;
            border-radius: 50%;
            animation: spin-rings 1s linear infinite;
        }

        .loading-text {
            color: white;
            font-size: 1.1em;
            font-weight: 500;
            letter-spacing: 1px;
            text-transform: uppercase;
            opacity: 0.8;
            animation: blink-text 1.5s infinite;
        }

        @keyframes spin-rings {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        @keyframes pulse-logo {
            0%, 100% { transform: scale(1); opacity: 0.8; }
            50% { transform: scale(1.1); opacity: 1; }
        }

        @keyframes blink-text {
            0%, 100% { opacity: 0.4; }
            50% { opacity: 0.9; }
        }
    </style>
</head>
<body>
 
    <!-- Loading Overlay -->
    <div class="loading-overlay" id="preloader">
        <div class="loading-logo-box">
            <div class="loading-spinner-rings"></div>
            <img src="images/logo-melodias.png" alt="Melodias">
        </div>
        <div class="loading-text">Conectando...</div>
    </div>

    <div class="login-wrapper">
        <div class="login-container">
            <!-- Logo real da Melodias -->
            <div class="logo" style="background:none;box-shadow:none;">
                <img src="images/logo-melodias.png" alt="Melodias" style="width:100px;height:auto;object-fit:contain;">
            </div>

            <?php if (!empty($erro)): ?>
                <div class="erro">
                    <i class="fas fa-exclamation-triangle"></i>
                    <div><?php echo $erro; ?></div>
                </div>
            <?php endif; ?>

            <form action="login" method="POST" class="form-login" id="loginForm">
                <div class="input-group">
                    <label for="email">
                        <i class="fas fa-envelope"></i>
                        E-mail
                    </label>
                    <div class="input-wrapper">
                        <input type="email" 
                               id="email" 
                               name="email" 
                               required 
                               placeholder="seu@email.com" 
                               autocomplete="email"
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                    </div>
                </div>

                <div class="input-group">
                    <label for="senha">
                        <i class="fas fa-lock"></i>
                        Senha
                    </label>
                    <div class="input-wrapper">
                        <input type="password" 
                               id="senha" 
                               name="senha" 
                               required 
                               placeholder="Digite sua senha" 
                               autocomplete="current-password">
                        <button type="button" class="toggle-password" onclick="togglePassword()">
                            <i class="fas fa-eye" id="toggleIcon"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn-entrar" id="btnEntrar">
                    <span>Entrar no Sistema</span>
                </button>
            </form>

            <div class="links">
                <a href="index.php" class="link-item">
                    <i class="fas fa-arrow-left"></i>
                    Voltar para início
                </a>
            </div>
        </div>
    </div>

    <script>
        // Toggle mostrar/ocultar senha
        function togglePassword() {
            const senhaInput = document.getElementById('senha');
            const toggleIcon = document.getElementById('toggleIcon');
            
            if (senhaInput.type === 'password') {
                senhaInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                senhaInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }

        // Loading state no login com delay de 3 segundos
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            e.preventDefault(); // Previne o envio imediato para mostrar a animação
            
            const btn = document.getElementById('btnEntrar');
            const preloader = document.getElementById('preloader');
            const form = this;
            
            btn.classList.add('loading');
            btn.innerHTML = '<span>Verificando...</span>';
            btn.style.opacity = '0.7';
            btn.style.pointerEvents = 'none';
            
            // Ativa o preloader full-screen imediatamente
            preloader.classList.add('active');
            
            // Aguarda 3 segundos (3000ms) para que a animação seja vista, depois envia
            setTimeout(() => {
                form.submit();
            }, 3000);
        });

        // Auto-focus no email
        document.addEventListener('DOMContentLoaded', function() {
            const emailInput = document.getElementById('email');
            if (!emailInput.value) {
                emailInput.focus();
            }
        });
    </script>

</body>
</html>