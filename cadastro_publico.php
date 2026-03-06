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
                        data_cadastro DATETIME DEFAULT CURRENT_TIMESTAMP
                    )";
    $pdo->exec($query_tabela);

    // Processa o cadastro
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $nome = htmlspecialchars($_POST['nome']);
        $especialidade = htmlspecialchars($_POST['especialidade']);
        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        $whatsapp = htmlspecialchars($_POST['whatsapp']);

        $stmt = $pdo->prepare("INSERT INTO profissionais (nome, especialidade, email, whatsapp) VALUES (:nome, :especialidade, :email, :whatsapp)");
        $stmt->bindParam(':nome', $nome);
        $stmt->bindParam(':especialidade', $especialidade);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':whatsapp', $whatsapp);

        if ($stmt->execute()) {
            $mensagem = "<div class='alerta sucesso'>✨ Cadastro realizado com sucesso! Bem-vindo(a) ao grupo.</div>";
        } else {
            $mensagem = "<div class='alerta erro'>❌ Erro ao realizar cadastro. Tente novamente.</div>";
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
    <title>Grupo Melodias - Conexões em Saúde Mental</title>
    <style>
        /* ==========================================
           2. FRONT-END: ESTILOS CSS E EFEITOS
        ========================================== */
        :root {
            --azul-escuro: #1b333d;
            --creme: #f5eedf;
            --vinho: #6e2b3a;
            --vinho-hover: #551f2b;
            --texto: #333;
            --branco: #ffffff;
        }

        /* Efeito de rolagem suave na página inteira */
        html { scroll-behavior: smooth; }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background-color: var(--creme);
            color: var(--texto);
        }

        /* --- NAVEGAÇÃO SUPERIOR --- */
        .navbar {
            background-color: var(--branco);
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: sticky; /* Fica fixo no topo ao rolar */
            top: 0;
            z-index: 1000;
        }

        .navbar .logo { font-family: Georgia, serif; font-size: 1.5em; color: var(--azul-escuro); font-weight: bold; text-decoration: none;}
        
        .nav-links a {
            margin-left: 20px;
            text-decoration: none;
            color: var(--texto);
            font-weight: 600;
            transition: color 0.3s;
        }
        .nav-links a:hover { color: var(--vinho); }
        
        .btn-login {
            background-color: var(--azul-escuro);
            color: var(--branco) !important;
            padding: 8px 20px;
            border-radius: 20px;
            transition: background 0.3s, transform 0.2s;
        }
        .btn-login:hover { background-color: #122229; transform: scale(1.05); }

        /* --- HERO SECTION (Apresentação) --- */
        .hero {
            background-color: var(--azul-escuro);
            color: var(--creme);
            text-align: center;
            padding: 80px 20px;
            background-image: linear-gradient(to bottom, #1b333d, #122229);
        }
        .hero h1 { font-family: Georgia, serif; font-size: 3em; margin-bottom: 10px; }
        .hero p { font-size: 1.2em; max-width: 600px; margin: 0 auto 30px; line-height: 1.6; }
        .hero-btn {
            background-color: var(--vinho);
            color: var(--branco);
            text-decoration: none;
            padding: 15px 30px;
            border-radius: 30px;
            font-size: 1.2em;
            font-weight: bold;
            transition: background 0.3s, transform 0.2s;
            display: inline-block;
        }
        .hero-btn:hover { background-color: var(--vinho-hover); transform: translateY(-3px); }

        /* --- SEÇÕES GERAIS --- */
        .secao { padding: 60px 20px; text-align: center; }
        .secao-titulo { color: var(--vinho); font-family: Georgia, serif; font-size: 2.2em; margin-bottom: 20px; }
        
        /* --- CARDS (Como Funciona / Recursos) --- */
        .grid-cards {
            display: flex;
            justify-content: center;
            gap: 20px;
            flex-wrap: wrap;
            max-width: 1000px;
            margin: 0 auto;
        }
        .card {
            background: var(--branco);
            padding: 30px;
            border-radius: 10px;
            width: 250px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            transition: transform 0.3s, box-shadow 0.3s;
        }
        .card:hover {
            transform: translateY(-10px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }
        .card h3 { color: var(--azul-escuro); }

        /* --- FORMULÁRIO DE CADASTRO --- */
        .form-container {
            max-width: 600px;
            margin: 0 auto;
            background: var(--branco);
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            text-align: left;
        }
        .formulario-grupo { display: flex; flex-direction: column; gap: 15px; }
        .formulario-grupo label { font-weight: bold; color: var(--azul-escuro); }
        .formulario-grupo input {
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 1em;
            width: 100%;
            box-sizing: border-box;
            transition: border-color 0.3s;
        }
        .formulario-grupo input:focus { border-color: var(--vinho); outline: none; }
        
        .btn-enviar {
            background-color: var(--vinho);
            color: var(--branco);
            padding: 15px;
            border: none;
            border-radius: 30px;
            font-size: 1.1em;
            cursor: pointer;
            font-weight: bold;
            transition: background 0.3s, transform 0.2s;
            margin-top: 10px;
        }
        .btn-enviar:hover { background-color: var(--vinho-hover); transform: translateY(-2px); }

        /* Mensagens */
        .alerta { padding: 15px; border-radius: 5px; margin-bottom: 20px; text-align: center; font-weight: bold; }
        .sucesso { background-color: #d4edda; color: #155724; }
        .erro { background-color: #f8d7da; color: #721c24; }

        /* --- RODAPÉ --- */
        .rodape { background-color: var(--azul-escuro); color: var(--creme); text-align: center; padding: 40px 20px; margin-top: 40px;}
        .rodape a { color: var(--creme); text-decoration: underline; }
    </style>
</head>
<body>

    <nav class="navbar">
        <a href="#" class="logo">Ψ Melodias</a>
        <div class="nav-links">
            <a href="#sobre">Objetivo</a>
            <a href="#recursos">Recursos</a>
            <a href="#cadastro">Fazer Parte</a>
            <a href="login.php" class="btn-login">Área de Membros &rarr;</a>
        </div>
    </nav>

    <header class="hero">
        <p>Convite aos profissionais da saúde mental de Tatuí</p>
        <h1>Melodias</h1>
        <p>Conexões em Saúde Mental. Um espaço seguro para crescer, trocar ideias e fortalecer nossa profissão.</p>
        <a href="#cadastro" class="hero-btn">Quero Participar</a>
    </header>

    <?php if (!empty($mensagem)) echo "<div class='secao'>$mensagem</div>"; ?>

    <section id="sobre" class="secao">
        <h2 class="secao-titulo">Nosso Objetivo</h2>
        <p style="max-width: 800px; margin: 0 auto 40px; line-height: 1.6; font-size: 1.1em;">
            Criar uma rede de apoio sólida entre psicólogos, psicanalistas e terapeutas. 
            Queremos facilitar encaminhamentos éticos e criar um ambiente colaborativo para discussão de casos e estudos.
        </p>

        <div class="grid-cards">
            <div class="card">
                <h3>💡 Troca de Ideias</h3>
                <p>Compartilhe experiências de consultório, tire dúvidas e debata abordagens terapêuticas com colegas.</p>
            </div>
            <div class="card">
                <h3>🤝 Grupos de Estudo</h3>
                <p>Encontros online e presenciais para aprofundamento teórico em Psicanálise, TCC, e outras áreas.</p>
            </div>
            <div class="card">
                <h3>🔄 Encaminhamentos</h3>
                <p>Uma rede de confiança para indicar pacientes quando a demanda fugir da sua especialidade.</p>
            </div>
        </div>
    </section>

    <section id="recursos" class="secao" style="background-color: #e8dfc8;">
        <h2 class="secao-titulo">Material e Conteúdo</h2>
        <p style="margin-bottom: 30px;">Ao fazer parte do grupo e acessar a Área de Membros, você terá acesso a:</p>
        <div class="grid-cards">
            <div class="card">
                <h3>📚 E-Books Exclusivos</h3>
                <p>Materiais de apoio em formato digital criados por especialistas do nosso grupo.</p>
            </div>
            <div class="card">
                <h3>📅 Eventos e Palestras</h3>
                <p>Calendário completo de workshops, supervisões em grupo e encontros presenciais em Tatuí.</p>
            </div>
        </div>
    </section>

    <section id="cadastro" class="secao">
        <h2 class="secao-titulo">Faça Parte da Rede</h2>
        <p style="margin-bottom: 30px;">Preencha seus dados abaixo. Após análise, liberaremos seu acesso à Área de Membros.</p>
        
        <div class="form-container">
            <form action="index.php#cadastro" method="POST" class="formulario-grupo">
                <div>
                    <label for="nome">Seu Nome Completo:</label>
                    <input type="text" id="nome" name="nome" required placeholder="Ex: Maria Silva">
                </div>
                <div>
                    <label for="especialidade">Sua Profissão/Abordagem:</label>
                    <input type="text" id="especialidade" name="especialidade" required placeholder="Ex: Psicóloga Clínica">
                </div>
                <div>
                    <label for="email">Seu E-mail:</label>
                    <input type="email" id="email" name="email" required placeholder="Ex: maria@email.com">
                </div>
                <div>
                    <label for="whatsapp">Seu WhatsApp:</label>
                    <input type="tel" id="whatsapp" name="whatsapp" required placeholder="(15) 99999-9999">
                </div>
                <button type="submit" class="btn-enviar">Enviar Solicitação</button>
            </form>
        </div>
    </section>

    <footer class="rodape">
        <h3 style="color: var(--creme); margin-bottom: 10px;">Precisa de Ajuda?</h3>
        <p>Se tiver dúvidas sobre o grupo ou dificuldades no acesso, envie uma mensagem.</p>
        <p style="margin-top: 30px; font-size: 0.9em;">
            &copy; <?php echo date('Y'); ?> Grupo Melodias. Uma iniciativa <a href="https://karengomes.com.br/" target="_blank">Karen Gomes - Psicologia</a>.
        </p>
    </footer>

</body>
</html>