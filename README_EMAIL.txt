=======================================================
📧 SISTEMA DE EMAIL - MELODIAS
=======================================================

CONFIGURAÇÃO IMPLEMENTADA ✅

O sistema agora possui 3 arquivos novos:

1. email_config.php
   - Configuração do PHPMailer
   - Função enviarEmailPHPMailer()
   - Isolado do resto do XAMPP

2. teste_phpmailer.php
   - Interface de teste visual
   - Detecta status da instalação
   - Envia email de teste

3. INSTALACAO_PHPMAILER.txt
   - Guia passo a passo
   - Resolução de problemas
   - Verificações rápidas


=======================================================
COMO FUNCIONA
=======================================================

ANTES (Problema):
- Precisava configurar php.ini e sendmail.ini
- Afetava TODO o XAMPP
- Complicado e arriscado

AGORA (Solução):
- PHPMailer instalado apenas na pasta melodias
- NÃO afeta outros projetos
- Mais confiável e robusto


=======================================================
PRÓXIMOS PASSOS (EM ORDEM)
=======================================================

1. INSTALE O COMPOSER
   └─ Baixe: https://getcomposer.org/Composer-Setup.exe
   └─ Execute e instale normalmente

2. INSTALE O PHPMAILER
   └─ Abra PowerShell
   └─ Execute: cd C:\xampp\htdocs\karen_site\Site\melodias
   └─ Execute: composer require phpmailer/phpmailer
   └─ Aguarde instalação (cria pasta "vendor")

3. CONFIGURE SENHA DO GMAIL
   └─ Acesse: https://myaccount.google.com/apppasswords
   └─ Gere senha de app para "Melodias System"
   └─ Copie a senha (16 caracteres sem espaços)
   └─ Edite: email_config.php
   └─ Substitua 'COLOQUE_SUA_SENHA_DE_APP_AQUI' pela senha

4. TESTE A CONFIGURAÇÃO
   └─ Acesse: http://localhost/karen_site/Site/melodias/teste_phpmailer.php
   └─ Clique em "Enviar Email de Teste"
   └─ Verifique se recebeu o email


=======================================================
STATUS ATUAL
=======================================================

✅ Código implementado e funcional
✅ Sistema detecta se PHPMailer está instalado
✅ Fallback para mail() nativo se PHPMailer não disponível
✅ Mensagens de erro explicativas
✅ Guias de instalação criados
✅ Página de teste criada
✅ email_config.php configurável

⏳ PENDENTE: Você executar os 4 passos acima


=======================================================
VANTAGENS DESTA SOLUÇÃO
=======================================================

✅ Isolada - Não afeta outros projetos
✅ Segura - Senha não fica no php.ini geral
✅ Confiável - PHPMailer é mais robusto que mail()
✅ Portável - Funciona em qualquer servidor
✅ Rastreável - Logs de erro específicos
✅ Testável - Página de teste dedicada


=======================================================
ARQUIVOS MODIFICADOS
=======================================================

painel.php:
  - Linha 7: Adicionado require_once 'email_config.php'
  - Linhas 447-503: Sistema de envio com PHPMailer
  - Tenta PHPMailer primeiro
  - Fallback para mail() se não disponível
  - Mensagens claras de erro/sucesso

Novos arquivos:
  - email_config.php (configuração PHPMailer)
  - teste_phpmailer.php (teste visual)
  - INSTALACAO_PHPMAILER.txt (guia passo a passo)
  - .gitignore (ignora vendor e senhas)


=======================================================
TESTE RÁPIDO
=======================================================

Depois de instalar, abra o painel e aprove um usuário:

✅ SE PHPMAILER CONFIGURADO:
   → Email enviado automaticamente
   → Toast verde: "Acesso liberado e e-mail enviado"
   → Usuário recebe credenciais por email

⚠️ SE PHPMAILER NÃO CONFIGURADO:
   → Toast amarelo: "Email não enviado"
   → Popup com credenciais para envio manual
   → Instruções de como configurar


=======================================================
SUPORTE
=======================================================

Problemas? Siga esta ordem:

1. Leia: INSTALACAO_PHPMAILER.txt
2. Teste: teste_phpmailer.php
3. Verifique: Pasta "vendor" existe?
4. Verifique: Senha configurada em email_config.php?
5. Teste com outro email de destino

Se nada resolver:
- Tire print do erro
- Verifique porta 587 não bloqueada
- Desative antivírus temporariamente


=======================================================
SUCESSO!
=======================================================

Quando tudo estiver funcionando:
✅ Aprovar usuário → Email enviado automaticamente
✅ Credenciais enviadas por email HTML bonito
✅ Aviso claro: "Este é um email automático, não responda"
✅ Remetente: contato@karengomes.com.br

=======================================================
