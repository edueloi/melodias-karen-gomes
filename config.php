<?php
// ==========================================
// CONFIGURAÇÕES CENTRALIZADAS DO SISTEMA
// ==========================================

// Configurações do Banco de Dados
define('DB_FILE', 'banco_melodias.sqlite');

// Configurações de Segurança
define('SUPER_ADMIN_EMAIL', 'karen.l.s.gomes@gmail.com');
define('SUPER_ADMIN_PASSWORD', 'Bibia.0110'); // Será hashada no setup

// Níveis de Permissão
define('ROLE_SUPERADMIN', 'superadmin');
define('ROLE_ADMIN', 'admin');
define('ROLE_USER', 'user');

// Configurações de Sessão
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
session_start();

// ==========================================
// FUNÇÕES AUXILIARES
// ==========================================

/**
 * Conecta ao banco de dados SQLite
 */
function getDB() {
    try {
        $pdo = new PDO("sqlite:" . DB_FILE);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return $pdo;
    } catch (PDOException $e) {
        die("Erro ao conectar no banco: " . $e->getMessage());
    }
}

/**
 * Verifica se o usuário está logado
 */
function verificarLogin() {
    if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
        header("Location: login");
        exit;
    }
}

/**
 * Verifica se o usuário tem permissão específica
 */
function verificarPermissao($permissaoNecessaria) {
    if (!isset($_SESSION['role'])) {
        header("Location: login");
        exit;
    }
    
    $hierarquia = [
        ROLE_USER => 1,
        ROLE_ADMIN => 2,
        ROLE_SUPERADMIN => 3
    ];
    
    $nivelUsuario = $hierarquia[$_SESSION['role']] ?? 0;
    $nivelNecessario = $hierarquia[$permissaoNecessaria] ?? 0;
    
    if ($nivelUsuario < $nivelNecessario) {
        die("<h1 style='text-align:center;margin-top:100px;color:#ef4444;'>🚫 Acesso Negado</h1><p style='text-align:center;'>Você não tem permissão para acessar esta área.</p>");
    }
}

/**
 * Sanitiza entrada de dados
 */
function sanitize($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Gera hash seguro de senha
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

/**
 * Verifica senha com hash
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Logout do sistema
 */
function logout() {
    session_unset();
    session_destroy();
    header("Location: ./login");
    exit;
}

/**
 * Retorna dados do usuário logado
 */
function getUsuarioLogado() {
    if (!isset($_SESSION['id_usuario'])) {
        return null;
    }
    
    $pdo = getDB();
    
    // Verifica se as colunas role e status existem, se não, cria
    try {
        // Tenta adicionar coluna role se não existir
        try {
            $pdo->exec("ALTER TABLE profissionais ADD COLUMN role TEXT DEFAULT 'user'");
        } catch (PDOException $e) {
            // Coluna já existe, ignora erro
        }
        
        // Tenta adicionar coluna status se não existir
        try {
            $pdo->exec("ALTER TABLE profissionais ADD COLUMN status TEXT DEFAULT 'ativo'");
        } catch (PDOException $e) {
            // Coluna já existe, ignora erro
        }
        
        // Tenta adicionar coluna senha se não existir
        try {
            $pdo->exec("ALTER TABLE profissionais ADD COLUMN senha TEXT");
        } catch (PDOException $e) {
            // Coluna já existe, ignora erro
        }
    } catch (Exception $e) {
        // Ignora erros de estrutura
    }
    
    $stmt = $pdo->prepare("SELECT * FROM profissionais WHERE id = :id");
    $stmt->execute([':id' => $_SESSION['id_usuario']]);
    $user = $stmt->fetch();
    
    // Garante valores padrão
    if ($user) {
        if (!isset($user['role']) || empty($user['role'])) {
            $user['role'] = 'user';
            try {
                $stmt = $pdo->prepare("UPDATE profissionais SET role = 'user' WHERE id = :id");
                $stmt->execute([':id' => $user['id']]);
            } catch (PDOException $e) {
                // Ignora erro se coluna não existe
            }
        }
        
        if (!isset($user['status']) || empty($user['status'])) {
            $user['status'] = 'ativo';
            try {
                $stmt = $pdo->prepare("UPDATE profissionais SET status = 'ativo' WHERE id = :id");
                $stmt->execute([':id' => $user['id']]);
            } catch (PDOException $e) {
                // Ignora erro se coluna não existe
            }
        }
    }
    
    return $user;
}

/**
 * Formata data brasileira
 */
function formatarData($data) {
    if (empty($data) || $data === null) {
        return 'Data não disponível';
    }
    $timestamp = strtotime($data);
    if ($timestamp === false || $timestamp <= 0) {
        return 'Data inválida';
    }
    return date('d/m/Y H:i', $timestamp);
}

/**
 * Gera resposta JSON
 */
function jsonResponse($success, $message, $data = null) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}
