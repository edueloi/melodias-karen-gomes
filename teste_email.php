<?php
/**
 * TESTE DE ENVIO DE EMAIL
 * 
 * Use este arquivo para testar se a configuração de email está funcionando.
 * Acesse: http://localhost/karen_site/Site/melodias/teste_email.php
 */

// ALTERE AQUI PARA SEU EMAIL DE TESTE
$destinatario = "karen.l.s.gomes@gmail.com";

$assunto = "🧪 Teste de Email - Melodias";

$mensagem = "
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
        .info { background: #d1ecf1; padding: 15px; border-radius: 5px; border-left: 4px solid #0c5460; margin: 20px 0; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h1>🎵 Melodias</h1>
            <p>Sistema de Email Funcionando!</p>
        </div>
        <div class='content'>
            <div class='success'>
                <strong>✅ Sucesso!</strong><br>
                Se você está lendo esta mensagem, significa que o sistema de envio de e-mails está configurado corretamente!
            </div>
            
            <h2>📋 Informações do Teste</h2>
            <ul>
                <li><strong>Data/Hora:</strong> " . date('d/m/Y H:i:s') . "</li>
                <li><strong>Servidor SMTP:</strong> " . ini_get('SMTP') . "</li>
                <li><strong>Porta SMTP:</strong> " . ini_get('smtp_port') . "</li>
                <li><strong>Remetente:</strong> contato@karengomes.com.br</li>
            </ul>
            
            <div class='info'>
                <strong>ℹ️ Próximos Passos:</strong><br>
                Agora você pode aprovar usuários no painel e os emails serão enviados automaticamente!
            </div>
            
            <hr style='margin: 30px 0; border: none; border-top: 1px solid #ddd;'>
            
            <p style='text-align: center; color: #666; font-size: 14px;'>
                <strong>⚠️ Este é um e-mail automático. Por favor, NÃO responda este e-mail.</strong>
            </p>
        </div>
    </div>
</body>
</html>
";

$headers = "MIME-Version: 1.0\r\n";
$headers .= "Content-type: text/html; charset=UTF-8\r\n";
$headers .= "From: Melodias <contato@karengomes.com.br>\r\n";
$headers .= "Reply-To: contato@karengomes.com.br\r\n";

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Teste de Email - Melodias</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            background: #f5f5f5; 
            padding: 50px;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }
        .container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            max-width: 600px;
            width: 100%;
        }
        h1 { color: #6e2b3a; margin-bottom: 20px; }
        .status { padding: 20px; border-radius: 8px; margin: 20px 0; }
        .success { background: #d4edda; border-left: 4px solid #28a745; color: #155724; }
        .error { background: #f8d7da; border-left: 4px solid #dc3545; color: #721c24; }
        .info { background: #d1ecf1; border-left: 4px solid #0c5460; color: #0c5460; margin-top: 20px; padding: 15px; border-radius: 5px; font-size: 14px; }
        .btn { 
            display: inline-block;
            background: #6e2b3a; 
            color: white; 
            padding: 12px 30px; 
            text-decoration: none; 
            border-radius: 5px; 
            margin-top: 15px;
            transition: background 0.3s;
        }
        .btn:hover { background: #8d3a4d; }
        .config-info { 
            background: #f8f9fa; 
            padding: 15px; 
            border-radius: 5px; 
            margin: 15px 0;
            font-family: monospace;
            font-size: 13px;
        }
    </style>
</head>
<body>
    <div class='container'>
        <h1>📧 Teste de Envio de E-mail</h1>";

// Verifica configuração SMTP
$smtp_host = ini_get('SMTP');
$smtp_port = ini_get('smtp_port');

echo "<div class='config-info'>
        <strong>📋 Configuração Atual:</strong><br>
        SMTP: " . ($smtp_host ?: 'não configurado') . "<br>
        Porta: " . ($smtp_port ?: 'não configurada') . "<br>
        Remetente: contato@karengomes.com.br
      </div>";

if (empty($smtp_host) || $smtp_host === 'localhost') {
    echo "<div class='status error'>
            <strong>❌ SMTP Não Configurado</strong><br><br>
            Antes de enviar emails, você precisa configurar o SMTP no php.ini.<br><br>
            <strong>Passos:</strong>
            <ol style='margin: 10px 0; padding-left: 20px;'>
                <li>Abra: <code>C:\\xampp\\php\\php.ini</code></li>
                <li>Procure por <code>[mail function]</code></li>
                <li>Configure <code>SMTP=smtp.gmail.com</code></li>
                <li>Configure <code>smtp_port=587</code></li>
                <li>Reinicie o Apache</li>
            </ol>
            <a href='GUIA_CONFIGURAR_EMAIL.txt' class='btn' target='_blank'>📖 Ver Guia Completo</a>
          </div>";
} else {
    echo "<p>Tentando enviar email de teste para: <strong>$destinatario</strong></p>";
    
    // Tenta enviar
    if (mail($destinatario, $assunto, $mensagem, $headers)) {
        echo "<div class='status success'>
                <strong>✅ E-mail enviado com sucesso!</strong><br><br>
                Verifique sua caixa de entrada (e pasta de SPAM) em:<br>
                <strong>$destinatario</strong>
              </div>";
        
        echo "<div class='info'>
                <strong>✨ Configuração OK!</strong><br>
                Agora você pode aprovar usuários no painel e os emails serão enviados automaticamente.
              </div>";
    } else {
        echo "<div class='status error'>
                <strong>❌ Falha ao enviar e-mail</strong><br><br>
                Possíveis causas:
                <ul style='margin: 10px 0; padding-left: 20px;'>
                    <li>Senha de app do Gmail incorreta</li>
                    <li>Configuração do sendmail.ini incorreta</li>
                    <li>Porta bloqueada pelo firewall</li>
                    <li>Apache precisa ser reiniciado</li>
                </ul>
                <a href='GUIA_CONFIGURAR_EMAIL.txt' class='btn' target='_blank'>📖 Ver Guia de Solução</a>
              </div>";
    }
}

echo "  <div style='text-align: center; margin-top: 30px;'>
            <a href='painel.php' class='btn'>← Voltar ao Painel</a>
            <a href='teste_email.php' class='btn' style='background: #1b333d;'>🔄 Testar Novamente</a>
        </div>
    </div>
</body>
</html>";
?>
