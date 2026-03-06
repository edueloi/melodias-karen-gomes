<?php
/**
 * TESTE DE EMAIL COM PHPMAILER
 * 
 * Use este arquivo para testar se o PHPMailer está configurado corretamente.
 * Acesse: http://localhost/karen_site/Site/melodias/teste_phpmailer.php
 */

require_once 'email_config.php';

// CONFIGURAÇÃO DO TESTE
$destinatario_teste = "karen.l.s.gomes@gmail.com"; // ⚠️ Altere para seu email de teste

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste PHPMailer - Melodias</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 700px;
            width: 100%;
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #1b333d 0%, #6e2b3a 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 2em;
            margin-bottom: 10px;
        }
        
        .header p {
            opacity: 0.9;
            font-size: 1.1em;
        }
        
        .content {
            padding: 40px;
        }
        
        .status-box {
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
            border-left: 5px solid;
        }
        
        .status-success {
            background: #d4edda;
            border-color: #28a745;
            color: #155724;
        }
        
        .status-warning {
            background: #fff3cd;
            border-color: #ffc107;
            color: #856404;
        }
        
        .status-error {
            background: #f8d7da;
            border-color: #dc3545;
            color: #721c24;
        }
        
        .status-info {
            background: #d1ecf1;
            border-color: #17a2b8;
            color: #0c5460;
        }
        
        .status-box h3 {
            margin-bottom: 10px;
            font-size: 1.3em;
        }
        
        .status-box ul {
            margin: 15px 0 15px 20px;
        }
        
        .status-box li {
            margin: 8px 0;
            line-height: 1.6;
        }
        
        .btn {
            display: inline-block;
            background: #6e2b3a;
            color: white;
            padding: 12px 30px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            margin: 10px 5px;
            border: none;
            cursor: pointer;
            font-size: 1em;
        }
        
        .btn:hover {
            background: #8d3a4d;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        
        .btn-secondary {
            background: #6c757d;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        .code-box {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            margin: 15px 0;
            font-family: 'Courier New', monospace;
            font-size: 0.9em;
            overflow-x: auto;
        }
        
        .steps {
            counter-reset: step-counter;
            list-style: none;
            padding: 0;
        }
        
        .steps li {
            counter-increment: step-counter;
            margin: 15px 0;
            padding-left: 40px;
            position: relative;
        }
        
        .steps li::before {
            content: counter(step-counter);
            position: absolute;
            left: 0;
            top: 0;
            background: #6e2b3a;
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
        
        .footer {
            text-align: center;
            padding: 20px;
            background: #f8f9fa;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎵 Teste PHPMailer</h1>
            <p>Sistema Melodias - Verificação de Envio de Email</p>
        </div>
        
        <div class="content">
            <?php
            // Verifica status do PHPMailer
            $status = verificarPHPMailer();
            
            if (!$status['instalado']) {
                // PHPMailer não está instalado
                ?>
                <div class="status-box status-error">
                    <h3>❌ PHPMailer Não Instalado</h3>
                    <p><?php echo $status['mensagem']; ?></p>
                </div>
                
                <div class="status-box status-info">
                    <h3>📦 Como Instalar o PHPMailer</h3>
                    <ol class="steps">
                        <li>
                            <strong>Instale o Composer</strong><br>
                            Baixe e instale: <a href="https://getcomposer.org/download/" target="_blank">https://getcomposer.org/download/</a>
                        </li>
                        <li>
                            <strong>Abra o PowerShell ou CMD</strong><br>
                            Pressione <kbd>Win + R</kbd>, digite <code>powershell</code> e pressione Enter
                        </li>
                        <li>
                            <strong>Navegue até a pasta do projeto</strong><br>
                            <div class="code-box">cd C:\xampp\htdocs\karen_site\Site\melodias</div>
                        </li>
                        <li>
                            <strong>Instale o PHPMailer</strong><br>
                            <div class="code-box">composer require phpmailer/phpmailer</div>
                        </li>
                        <li>
                            <strong>Configure a senha de app do Gmail</strong><br>
                            Edite o arquivo <code>email_config.php</code> e coloque sua senha de app
                        </li>
                        <li>
                            <strong>Recarregue esta página</strong><br>
                            Pressione F5 para testar novamente
                        </li>
                    </ol>
                </div>
                <?php
            } elseif (!$status['configurado']) {
                // PHPMailer instalado mas não configurado
                ?>
                <div class="status-box status-warning">
                    <h3>⚠️ PHPMailer Instalado - Falta Configurar</h3>
                    <p><?php echo $status['mensagem']; ?></p>
                </div>
                
                <div class="status-box status-info">
                    <h3>🔑 Como Obter a Senha de App do Gmail</h3>
                    <ol class="steps">
                        <li>
                            <strong>Acesse sua conta Google</strong><br>
                            <a href="https://myaccount.google.com/apppasswords" target="_blank">https://myaccount.google.com/apppasswords</a>
                        </li>
                        <li>
                            <strong>Faça login</strong><br>
                            Use a conta: <code>contato@karengomes.com.br</code>
                        </li>
                        <li>
                            <strong>Clique em "Criar senha de app"</strong><br>
                            Se não aparecer, ative a verificação em 2 etapas primeiro
                        </li>
                        <li>
                            <strong>Escolha "Outro"</strong><br>
                            Digite: <code>Melodias System</code>
                        </li>
                        <li>
                            <strong>Copie a senha gerada</strong><br>
                            Serão 16 caracteres (ex: <code>abcd efgh ijkl mnop</code>)
                        </li>
                        <li>
                            <strong>Cole no arquivo email_config.php</strong><br>
                            Procure a linha com <code>COLOQUE_SUA_SENHA_DE_APP_AQUI</code><br>
                            Cole a senha SEM ESPAÇOS: <code>abcdefghijklmnop</code>
                        </li>
                    </ol>
                </div>
                <?php
            } else {
                // PHPMailer configurado - pode testar
                ?>
                <div class="status-box status-success">
                    <h3>✅ PHPMailer Instalado e Configurado!</h3>
                    <p><?php echo $status['mensagem']; ?></p>
                </div>
                
                <?php
                // Se foi enviado o formulário de teste
                if (isset($_POST['testar_email'])) {
                    echo '<div class="status-box status-info">';
                    echo '<h3>📧 Enviando email de teste...</h3>';
                    echo '</div>';
                    
                    // Monta email de teste
                    $assunto_teste = "🧪 Teste PHPMailer - Sistema Melodias";
                    $mensagem_teste = "
                    <!DOCTYPE html>
                    <html>
                    <head>
                        <meta charset='UTF-8'>
                        <style>
                            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                            .header { background: #6e2b3a; color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                            .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; border: 2px solid #6e2b3a; }
                            .success { background: #d4edda; padding: 15px; border-radius: 5px; border-left: 4px solid #28a745; margin: 20px 0; }
                        </style>
                    </head>
                    <body>
                        <div class='container'>
                            <div class='header'>
                                <h1>🎵 Melodias</h1>
                                <p>Teste de Email - PHPMailer</p>
                            </div>
                            <div class='content'>
                                <div class='success'>
                                    <strong>✅ Parabéns!</strong><br>
                                    Se você está lendo esta mensagem, significa que o PHPMailer está funcionando perfeitamente!
                                </div>
                                
                                <h2>📋 Informações do Teste</h2>
                                <ul>
                                    <li><strong>Data/Hora:</strong> " . date('d/m/Y H:i:s') . "</li>
                                    <li><strong>Sistema:</strong> Melodias v2.0</li>
                                    <li><strong>Método:</strong> PHPMailer via SMTP Gmail</li>
                                    <li><strong>Remetente:</strong> contato@karengomes.com.br</li>
                                </ul>
                                
                                <hr style='margin: 30px 0; border: none; border-top: 1px solid #ddd;'>
                                
                                <p style='text-align: center; color: #666; font-size: 14px;'>
                                    <strong>⚠️ Este é um e-mail automático de teste. Por favor, NÃO responda este e-mail.</strong>
                                </p>
                            </div>
                        </div>
                    </body>
                    </html>
                    ";
                    
                    $resultado = enviarEmailPHPMailer($destinatario_teste, 'Teste', $assunto_teste, $mensagem_teste);
                    
                    if ($resultado) {
                        echo '<div class="status-box status-success">';
                        echo '<h3>✅ Email Enviado com Sucesso!</h3>';
                        echo '<p>Verifique a caixa de entrada (e spam) de: <strong>' . htmlspecialchars($destinatario_teste) . '</strong></p>';
                        echo '<p style="margin-top: 15px;">🎉 <strong>O sistema de email está funcionando perfeitamente!</strong></p>';
                        echo '<p>Agora você pode aprovar usuários e os emails serão enviados automaticamente.</p>';
                        echo '</div>';
                    } else {
                        echo '<div class="status-box status-error">';
                        echo '<h3>❌ Falha ao Enviar Email</h3>';
                        echo '<p>Possíveis causas:</p>';
                        echo '<ul>';
                        echo '<li>Senha de app do Gmail incorreta (verifique em email_config.php)</li>';
                        echo '<li>Conta Gmail sem verificação em 2 etapas ativada</li>';
                        echo '<li>Firewall ou antivírus bloqueando conexão SMTP na porta 587</li>';
                        echo '<li>Limite de envio do Gmail atingido (tente novamente em 1 hora)</li>';
                        echo '</ul>';
                        echo '</div>';
                    }
                }
                ?>
                
                <form method="POST" style="text-align: center; margin-top: 30px;">
                    <p style="margin-bottom: 20px;">
                        <strong>Email de teste será enviado para:</strong><br>
                        <code style="background: #f8f9fa; padding: 5px 10px; border-radius: 5px;"><?php echo htmlspecialchars($destinatario_teste); ?></code>
                    </p>
                    <button type="submit" name="testar_email" class="btn">
                        📧 Enviar Email de Teste
                    </button>
                    <p style="margin-top: 15px; font-size: 0.9em; color: #6c757d;">
                        Altere o email em <code>teste_phpmailer.php</code> se necessário
                    </p>
                </form>
                <?php
            }
            ?>
            
            <div style="text-align: center; margin-top: 40px; padding-top: 30px; border-top: 2px solid #e9ecef;">
                <a href="painel.php" class="btn btn-secondary">← Voltar ao Painel</a>
                <a href="teste_phpmailer.php" class="btn">🔄 Recarregar Página</a>
            </div>
        </div>
        
        <div class="footer">
            <p><strong>Melodias</strong> - Sistema de Gestão de Saúde Mental</p>
            <p style="margin-top: 10px; font-size: 0.9em;">Desenvolvido para Karen Gomes</p>
        </div>
    </div>
</body>
</html>
