@echo off
chcp 65001 >nul
color 0A
cls

echo ========================================
echo 📧 INSTALADOR PHPMAILER - MELODIAS
echo ========================================
echo.

:: Verifica se está na pasta correta
if not exist "email_config.php" (
    echo ❌ ERRO: Execute este script da pasta melodias!
    echo.
    echo Caminho correto: C:\xampp\htdocs\karen_site\Site\melodias
    echo.
    pause
    exit /b 1
)

:: Verifica se Composer está instalado
echo [1/3] Verificando Composer...
composer --version >nul 2>&1
if errorlevel 1 (
    echo.
    echo ❌ Composer não encontrado!
    echo.
    echo Por favor, instale o Composer primeiro:
    echo https://getcomposer.org/Composer-Setup.exe
    echo.
    echo Após instalar, reinicie este script.
    echo.
    pause
    exit /b 1
)
echo ✅ Composer encontrado!
echo.

:: Instala PHPMailer
echo [2/3] Instalando PHPMailer...
echo.
composer require phpmailer/phpmailer
if errorlevel 1 (
    echo.
    echo ❌ Erro ao instalar PHPMailer!
    echo.
    echo Tente manualmente:
    echo composer require phpmailer/phpmailer
    echo.
    pause
    exit /b 1
)
echo.
echo ✅ PHPMailer instalado com sucesso!
echo.

:: Verifica pasta vendor
echo [3/3] Verificando instalação...
if exist "vendor" (
    echo ✅ Pasta vendor criada
) else (
    echo ❌ Pasta vendor não encontrada
    pause
    exit /b 1
)

if exist "vendor\autoload.php" (
    echo ✅ Autoload configurado
) else (
    echo ❌ Autoload não encontrado
    pause
    exit /b 1
)
echo.

:: Sucesso
color 0B
cls
echo ========================================
echo ✅ INSTALAÇÃO CONCLUÍDA!
echo ========================================
echo.
echo PHPMailer foi instalado com sucesso!
echo.
echo 📋 PRÓXIMOS PASSOS:
echo.
echo 1. Configure a senha do Gmail:
echo    - Abra: email_config.php
echo    - Procure: COLOQUE_SUA_SENHA_DE_APP_AQUI
echo    - Substitua pela senha gerada no Gmail
echo.
echo 2. Teste a configuração:
echo    - Acesse: http://localhost/karen_site/Site/melodias/teste_phpmailer.php
echo    - Clique em "Enviar Email de Teste"
echo.
echo 3. Use o sistema:
echo    - Aprove usuários no painel
echo    - Emails serão enviados automaticamente
echo.
echo.
echo 📚 Documentação:
echo    - Leia: INSTALACAO_PHPMAILER.txt
echo    - Leia: README_EMAIL.txt
echo.
echo ========================================
pause
