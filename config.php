<?php
// ==========================================
// CONFIGURAÇÕES CENTRALIZADAS DO SISTEMA
// ==========================================

// Configurações de Segurança
define('SUPER_ADMIN_EMAIL', 'karen.l.s.gomes@gmail.com');
define('SUPER_ADMIN_PASSWORD', 'Bibia.0110');

// Níveis de Permissão
define('ROLE_SUPERADMIN', 'superadmin');
define('ROLE_ADMIN', 'admin');
define('ROLE_EDITOR', 'editor');
define('ROLE_USER', 'user');

// ==========================================
// DETECÇÃO DE AMBIENTE
// localhost  → SQLite
// produção   → MySQL (HostGator)
// ==========================================
$_is_producao = (
    isset($_SERVER['HTTP_HOST']) &&
    !in_array($_SERVER['HTTP_HOST'], ['localhost', '127.0.0.1', '::1']) &&
    strpos($_SERVER['HTTP_HOST'], '.local') === false
);

define('IS_PRODUCAO', $_is_producao);

// Banco SQLite (desenvolvimento local)
define('DB_FILE', __DIR__ . '/banco_melodias.sqlite');

// Banco MySQL (produção — HostGator)
define('DB_MYSQL_HOST', 'localhost');
define('DB_MYSQL_NAME', 'edua6062_melodias');
define('DB_MYSQL_USER', 'edua6062_karengomes');
define('DB_MYSQL_PASS', 'Bibia.0110');
define('DB_MYSQL_CHARSET', 'utf8mb4');

// Configurações de Sessão
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
if (IS_PRODUCAO) {
    ini_set('session.cookie_secure', 1); // HTTPS em produção
}
session_start();

// ==========================================
// FUNÇÕES AUXILIARES
// ==========================================

/**
 * Conecta ao banco de dados (SQLite local / MySQL produção)
 */
function getDB() {
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    try {
        if (IS_PRODUCAO) {
            $dsn = sprintf(
                'mysql:host=%s;dbname=%s;charset=%s',
                DB_MYSQL_HOST, DB_MYSQL_NAME, DB_MYSQL_CHARSET
            );
            $pdo = new PDO($dsn, DB_MYSQL_USER, DB_MYSQL_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
            ]);
        } else {
            $pdo = new PDO('sqlite:' . DB_FILE);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $pdo->exec("PRAGMA journal_mode=WAL");
            $pdo->exec("PRAGMA foreign_keys=ON");
        }
        return $pdo;
    } catch (PDOException $e) {
        die("Erro ao conectar no banco: " . $e->getMessage());
    }
}

/**
 * Retorna o driver do banco ativo ('sqlite' | 'mysql')
 */
function dbDriver() {
    return IS_PRODUCAO ? 'mysql' : 'sqlite';
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
        ROLE_USER      => 1,
        ROLE_EDITOR    => 1.5,
        ROLE_ADMIN     => 2,
        ROLE_SUPERADMIN => 3
    ];

    $nivelUsuario    = $hierarquia[$_SESSION['role']] ?? 0;
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
 * Formata o nome da profissão de acordo com o gênero
 */
function formatarProfissao($profissao, $genero) {
    if (empty($profissao)) return "Profissional";
    if (empty($genero) || $genero === 'Não declarado') return $profissao;

    $mapaFeminino = [
        'Psicólogo'            => 'Psicóloga',
        'Médico'               => 'Médica',
        'Psiquiatra'           => 'Psiquiatra',
        'Enfermeiro'           => 'Enfermeira',
        'Fisioterapeuta'       => 'Fisioterapeuta',
        'Psicopedagogo'        => 'Psicopedagoga',
        'Neuropsicólogo'       => 'Neuropsicóloga',
        'Terapeuta Ocupacional'=> 'Terapeuta Ocupacional',
        'Assistente Social'    => 'Assistente Social',
        'Fonoaudiólogo'        => 'Fonoaudióloga',
    ];

    if ($genero === 'Feminino' && isset($mapaFeminino[$profissao])) {
        return $mapaFeminino[$profissao];
    }
    return $profissao;
}

/**
 * Retorna dados do usuário logado
 */
function getUsuarioLogado() {
    if (!isset($_SESSION['id_usuario'])) return null;

    $pdo = getDB();

    // SQLite: garante colunas (ALTER TABLE é seguro no SQLite, falha silenciosa no MySQL)
    if (dbDriver() === 'sqlite') {
        try { $pdo->exec("ALTER TABLE profissionais ADD COLUMN role TEXT DEFAULT 'user'"); } catch(PDOException $e){}
        try { $pdo->exec("ALTER TABLE profissionais ADD COLUMN status TEXT DEFAULT 'ativo'"); } catch(PDOException $e){}
        try { $pdo->exec("ALTER TABLE profissionais ADD COLUMN senha TEXT"); } catch(PDOException $e){}
    }

    $stmt = $pdo->prepare("SELECT * FROM profissionais WHERE id = :id");
    $stmt->execute([':id' => $_SESSION['id_usuario']]);
    $user = $stmt->fetch();

    if ($user) {
        if (empty($user['role'])) {
            $user['role'] = 'user';
            try { $pdo->prepare("UPDATE profissionais SET role='user' WHERE id=:id")->execute([':id' => $user['id']]); } catch(PDOException $e){}
        }
        if (empty($user['status'])) {
            $user['status'] = 'ativo';
            try { $pdo->prepare("UPDATE profissionais SET status='ativo' WHERE id=:id")->execute([':id' => $user['id']]); } catch(PDOException $e){}
        }
    }

    return $user;
}

/**
 * Formata data brasileira
 */
function formatarData($data) {
    if (empty($data)) return 'Data não disponível';
    $timestamp = strtotime($data);
    if (!$timestamp || $timestamp <= 0) return 'Data inválida';
    return date('d/m/Y H:i', $timestamp);
}

/**
 * Gera resposta JSON
 */
function jsonResponse($success, $message, $data = null) {
    header('Content-Type: application/json');
    echo json_encode(['success' => $success, 'message' => $message, 'data' => $data]);
    exit;
}
