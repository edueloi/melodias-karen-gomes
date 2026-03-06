<?php
/**
 * CONFIGURAÇÃO DE EMAIL COM PHPMAILER
 * 
 * Este arquivo contém a configuração de envio de emails APENAS para o sistema Melodias.
 * Não afeta outros projetos no XAMPP.
 * 
 * IMPORTANTE: Antes de usar, você precisa:
 * 1. Instalar o Composer: https://getcomposer.org/download/
 * 2. No terminal, executar: cd C:\xampp\htdocs\karen_site\Site\melodias
 * 3. Executar: composer require phpmailer/phpmailer
 * 4. Configurar a senha de app do Gmail abaixo
 */

// Tenta carregar o autoload do Composer
$autoload_path = __DIR__ . '/vendor/autoload.php';
if (file_exists($autoload_path)) {
    require $autoload_path;
    define('PHPMAILER_DISPONIVEL', true);
} else {
    define('PHPMAILER_DISPONIVEL', false);
}

/**
 * Envia email usando PHPMailer
 * 
 * @param string $destinatario Email do destinatário
 * @param string $nome_dest Nome do destinatário
 * @param string $assunto Assunto do email
 * @param string $mensagem_html Conteúdo HTML do email
 * @return bool True se enviou com sucesso, False se falhou
 */
function enviarEmailPHPMailer($destinatario, $nome_dest, $assunto, $mensagem_html) {
    if (!PHPMAILER_DISPONIVEL) {
        error_log("PHPMailer não está instalado. Execute: composer require phpmailer/phpmailer");
        return false;
    }

    // Verifica se as classes PHPMailer existem
    if (!class_exists('PHPMailer\PHPMailer\PHPMailer') || !class_exists('PHPMailer\PHPMailer\Exception')) {
        error_log("Classes PHPMailer não encontradas. Reinstale: composer require phpmailer/phpmailer");
        return false;
    }

    try {
        // Cria instância do PHPMailer
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        
        // ===================================
        // CONFIGURAÇÕES DO SERVIDOR SMTP
        // ===================================
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'contato@karengomes.com.br';
        
        // ⚠️ ATENÇÃO: CONFIGURE SUA SENHA DE APP DO GMAIL AQUI
        // Como obter:
        // 1. Acesse: https://myaccount.google.com/apppasswords
        // 2. Faça login no Gmail (contato@karengomes.com.br)
        // 3. Clique em "Criar senha de app"
        // 4. Escolha "Outro" e digite "Melodias System"
        // 5. Use a senha gerada abaixo (16 caracteres sem espaços)
        $mail->Password   = 'COLOQUE_SUA_SENHA_DE_APP_AQUI';
        
        $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';
        
        // Desabilita verificação SSL em ambiente local (remova em produção)
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );
        
        // ===================================
        // REMETENTE E DESTINATÁRIO
        // ===================================
        $mail->setFrom('contato@karengomes.com.br', 'Melodias - Rede de Saúde Mental');
        $mail->addAddress($destinatario, $nome_dest);
        $mail->addReplyTo('contato@karengomes.com.br', 'Karen Gomes');
        
        // ===================================
        // CONTEÚDO DO EMAIL
        // ===================================
        $mail->isHTML(true);
        $mail->Subject = $assunto;
        $mail->Body    = $mensagem_html;
        
        // Versão texto alternativa (caso o cliente de email não suporte HTML)
        $mail->AltBody = strip_tags($mensagem_html);
        
        // ===================================
        // ENVIA O EMAIL
        // ===================================
        $mail->send();
        return true;
        
    } catch (\Exception $e) {
        // Log do erro para debug
        error_log("Erro ao enviar email via PHPMailer: " . (isset($mail) ? $mail->ErrorInfo : $e->getMessage()));
        return false;
    }
}

/**
 * Função auxiliar que verifica se o PHPMailer está configurado
 * 
 * @return array Array com 'instalado' (bool) e 'configurado' (bool)
 */
function verificarPHPMailer() {
    $status = [
        'instalado' => PHPMAILER_DISPONIVEL,
        'configurado' => false,
        'mensagem' => ''
    ];
    
    if (!PHPMAILER_DISPONIVEL) {
        $status['mensagem'] = 'PHPMailer não está instalado. Execute: composer require phpmailer/phpmailer';
        return $status;
    }
    
    // Verifica se a senha foi configurada
    $arquivo = @file_get_contents(__FILE__);
    if ($arquivo && strpos($arquivo, 'COLOQUE_SUA_SENHA_DE_APP_AQUI') !== false) {
        $status['mensagem'] = 'PHPMailer instalado, mas senha de app do Gmail não foi configurada no email_config.php';
        return $status;
    }
    
    $status['configurado'] = true;
    $status['mensagem'] = 'PHPMailer instalado e configurado corretamente!';
    return $status;
}
?>
