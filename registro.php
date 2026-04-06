<?php
require_once 'config.php';

$token = $_GET['token'] ?? '';
$error = '';
$success = false;

if (empty($token)) {
    die("<h1 style='text-align:center;margin-top:100px;'>Link Inválido</h1>");
}

$pdo = getDB();

// Verifica validade do convite
$stmt = $pdo->prepare("SELECT * FROM convites WHERE token = ?");
$stmt->execute([$token]);
$convite = $stmt->fetch();

if (!$convite) {
    die("<h1 style='text-align:center;margin-top:100px;'>Link não encontrado ou expirado.</h1>");
}

if (strtotime($convite['expira_em']) < time()) {
    $error = "Este link de convite expirou em " . date('d/m/Y H:i', strtotime($convite['expira_em']));
}

if ($convite['usos_atuais'] >= $convite['limite_usos']) {
    $error = "O limite de inscrições para este link foi atingido.";
}

// Processa Cadastro
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error) {
    $nome = sanitize($_POST['nome']);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $senha = $_POST['senha'];
    $whatsapp = sanitize($_POST['whatsapp'] ?? '');
    
    if (strlen($senha) < 6) {
        $error = "A senha deve ter pelo menos 6 caracteres.";
    } else {
        // Verifica se email já existe
        $stmt_check = $pdo->prepare("SELECT id FROM profissionais WHERE email = ?");
        $stmt_check->execute([$email]);
        if ($stmt_check->fetch()) {
            $error = "Este e-mail já está cadastrado no sistema.";
        } else {
            try {
                $pdo->beginTransaction();
                
                // Insere usuário
                $senhaHash = hashPassword($senha);
                $stmt_ins = $pdo->prepare("INSERT INTO profissionais (nome, email, senha, whatsapp, role, status) VALUES (?, ?, ?, ?, ?, 'ativo')");
                $stmt_ins->execute([$nome, $email, $senhaHash, $whatsapp, $convite['role_atribuida']]);
                
                // Atualiza contagem de usos do convite
                $pdo->prepare("UPDATE convites SET usos_atuais = usos_atuais + 1 WHERE id = ?")->execute([$convite['id']]);
                
                $pdo->commit();
                $success = true;
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = "Ocorreu um erro interno. Tente novamente mais tarde.";
            }
        }
    }
}

?>
<!DOCTYPE html>
<html lang="pt-br" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro - Melodias</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #6e2b3a;
            --primary-hover: #4a1d27;
            --bg: #f8fafc;
            --text-main: #1e293b;
            --text-muted: #64748b;
            --border: #e2e8f0;
            --radius: 16px;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Inter', sans-serif; 
            background: var(--bg); 
            color: var(--text-main);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
        }
        .reg-card {
            background: white;
            width: 100%;
            max-width: 480px;
            border-radius: 24px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.1);
            overflow: hidden;
            animation: fadeIn 0.6s ease;
        }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        
        .header {
            background: linear-gradient(135deg, var(--primary), var(--primary-hover));
            padding: 40px 30px;
            text-align: center;
            color: white;
        }
        .header h1 { font-size: 1.8em; font-weight: 800; margin-bottom: 10px; }
        .header p { opacity: 0.8; font-size: 0.95em; }
        
        .body { padding: 40px 30px; }
        
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-weight: 700; font-size: 0.85em; margin-bottom: 8px; color: var(--text-main); }
        .input-control {
            width: 100%;
            padding: 14px 18px;
            border-radius: 12px;
            border: 2px solid var(--border);
            font-family: inherit;
            font-size: 1em;
            transition: all 0.2s;
            outline: none;
        }
        .input-control:focus { border-color: var(--primary); box-shadow: 0 0 0 4px rgba(110,43,58,0.1); }
        
        .btn {
            width: 100%;
            padding: 16px;
            border-radius: 14px;
            border: none;
            background: var(--primary);
            color: white;
            font-size: 1em;
            font-weight: 800;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            box-shadow: 0 10px 20px rgba(110,43,58,0.2);
        }
        .btn:hover { background: var(--primary-hover); transform: translateY(-2px); }
        .btn:active { transform: translateY(0); }

        .alert {
            padding: 15px;
            border-radius: 12px;
            margin-bottom: 25px;
            font-size: 0.9em;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        .alert-success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        
        .success-area { text-align: center; padding: 20px 0; }
        .success-area i { font-size: 4em; color: #10b981; margin-bottom: 20px; }
    </style>
</head>
<body>

    <div class="reg-card">
        <div class="header">
            <h1>Melodias</h1>
            <p>Seja bem-vindo à nossa plataforma</p>
        </div>
        
        <div class="body">
            <?php if ($success): ?>
                <div class="success-area">
                    <i class="fa-solid fa-circle-check"></i>
                    <h2>Cadastro Realizado!</h2>
                    <p style="color: var(--text-muted); margin: 15px 0 25px;">Sua conta foi criada com sucesso. Agora você já pode acessar o sistema.</p>
                    <a href="login.php" class="btn">Fazer Login</a>
                </div>
            <?php else: ?>
                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <i class="fa-solid fa-circle-exclamation"></i>
                        <?php echo $error; ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-success">
                        <i class="fa-solid fa-link"></i>
                        Link de convite válido para: <?php echo ucfirst($convite['role_atribuida']); ?>
                    </div>
                <?php endif; ?>

                <?php if (!$error || $_SERVER['REQUEST_METHOD'] === 'POST'): ?>
                <form method="POST">
                    <div class="form-group">
                        <label>Nome Completo</label>
                        <input type="text" name="nome" class="input-control" placeholder="Seu nome completo" required value="<?php echo $_POST['nome'] ?? ''; ?>">
                    </div>
                    <div class="form-group">
                        <label>E-mail</label>
                        <input type="email" name="email" class="input-control" placeholder="seu@email.com" required value="<?php echo $_POST['email'] ?? ''; ?>">
                    </div>
                    <div class="form-group">
                        <label>WhatsApp (opcional)</label>
                        <input type="text" name="whatsapp" class="input-control" placeholder="(00) 00000-0000" value="<?php echo $_POST['whatsapp'] ?? ''; ?>">
                    </div>
                    <div class="form-group">
                        <label>Criar Senha</label>
                        <input type="password" name="senha" class="input-control" placeholder="Mínimo 6 caracteres" required minlength="6">
                    </div>
                    
                    <button type="submit" class="btn">
                        Criar minha conta <i class="fa-solid fa-arrow-right"></i>
                    </button>
                    
                    <p style="text-align: center; margin-top: 25px; font-size: 0.85em; color: var(--text-muted);">
                        Já tem uma conta? <a href="login.php" style="color: var(--primary); font-weight: 700; text-decoration: none;">Faça login aqui</a>
                    </p>
                </form>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

</body>
</html>
