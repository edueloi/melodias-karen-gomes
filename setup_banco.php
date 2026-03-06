<?php
// ==========================================
// SCRIPT DE INSTALAÇÃO/ATUALIZAÇÃO DO BANCO
// Rode este arquivo UMA VEZ no navegador para configurar tudo
// ==========================================

require_once 'config.php';

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup do Sistema Melodias</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f4f7f6; padding: 40px; max-width: 900px; margin: 0 auto; }
        .container { background: white; padding: 40px; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        h1 { color: #1b333d; border-bottom: 3px solid #6e2b3a; padding-bottom: 15px; }
        .step { background: #f9fafb; padding: 20px; margin: 15px 0; border-radius: 10px; border-left: 5px solid #10b981; }
        .step.error { border-left-color: #ef4444; background: #fef2f2; }
        .step.warning { border-left-color: #f59e0b; background: #fffbeb; }
        .success { color: #10b981; font-weight: bold; }
        .error { color: #ef4444; font-weight: bold; }
        .warning { color: #f59e0b; font-weight: bold; }
        code { background: #1e293b; color: #10b981; padding: 3px 8px; border-radius: 5px; font-family: monospace; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 Setup do Sistema Melodias</h1>
        <p>Este script irá configurar/atualizar todas as tabelas e criar o Super Admin.</p>

<?php

try {
    $pdo = getDB();
    
    echo "<div class='step'><h3>📊 Passo 1: Estrutura do Banco</h3>";
    
    // ==========================================
    // TABELA PROFISSIONAIS (Usuários)
    // ==========================================
    $pdo->exec("CREATE TABLE IF NOT EXISTS profissionais (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        nome TEXT NOT NULL,
        email TEXT NOT NULL UNIQUE,
        senha TEXT NOT NULL,
        especialidade TEXT,
        whatsapp TEXT,
        role TEXT DEFAULT 'user',
        status TEXT DEFAULT 'ativo',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    echo "<p class='success'>✅ Tabela 'profissionais' criada/verificada</p>";
    
    // Adicionar colunas se não existirem (para atualização de versões antigas)
    try {
        $pdo->exec("ALTER TABLE profissionais ADD COLUMN senha TEXT");
        echo "<p class='success'>✅ Coluna 'senha' adicionada</p>";
    } catch (PDOException $e) {
        echo "<p class='warning'>⚠️ Coluna 'senha' já existe</p>";
    }
    
    try {
        $pdo->exec("ALTER TABLE profissionais ADD COLUMN role TEXT DEFAULT 'user'");
        echo "<p class='success'>✅ Coluna 'role' adicionada</p>";
    } catch (PDOException $e) {
        echo "<p class='warning'>⚠️ Coluna 'role' já existe</p>";
    }
    
    try {
        $pdo->exec("ALTER TABLE profissionais ADD COLUMN status TEXT DEFAULT 'ativo'");
        echo "<p class='success'>✅ Coluna 'status' adicionada</p>";
    } catch (PDOException $e) {
        echo "<p class='warning'>⚠️ Coluna 'status' já existe</p>";
    }
    
    try {
        $pdo->exec("ALTER TABLE profissionais ADD COLUMN created_at DATETIME DEFAULT CURRENT_TIMESTAMP");
        echo "<p class='success'>✅ Coluna 'created_at' adicionada</p>";
    } catch (PDOException $e) {
        echo "<p class='warning'>⚠️ Coluna 'created_at' já existe</p>";
    }
    
    try {
        $pdo->exec("ALTER TABLE profissionais ADD COLUMN updated_at DATETIME DEFAULT CURRENT_TIMESTAMP");
        echo "<p class='success'>✅ Coluna 'updated_at' adicionada</p>";
    } catch (PDOException $e) {
        echo "<p class='warning'>⚠️ Coluna 'updated_at' já existe</p>";
    }
    
    // ==========================================
    // TABELA MATERIAIS
    // ==========================================
    $pdo->exec("CREATE TABLE IF NOT EXISTS materiais (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        titulo TEXT NOT NULL,
        descricao TEXT,
        categoria TEXT NOT NULL,
        tipo TEXT DEFAULT 'arquivo',
        caminho TEXT,
        visibilidade TEXT DEFAULT 'todos',
        created_by INTEGER,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (created_by) REFERENCES profissionais(id)
    )");
    echo "<p class='success'>✅ Tabela 'materiais' criada/verificada</p>";
    
    // Adicionar colunas em materiais se não existirem
    try {
        $pdo->exec("ALTER TABLE materiais ADD COLUMN created_by INTEGER");
        echo "<p class='success'>✅ Coluna 'created_by' adicionada em materiais</p>";
    } catch (PDOException $e) {
        echo "<p class='warning'>⚠️ Coluna 'created_by' já existe em materiais</p>";
    }
    
    try {
        $pdo->exec("ALTER TABLE materiais ADD COLUMN created_at DATETIME DEFAULT CURRENT_TIMESTAMP");
        echo "<p class='success'>✅ Coluna 'created_at' adicionada em materiais</p>";
    } catch (PDOException $e) {
        echo "<p class='warning'>⚠️ Coluna 'created_at' já existe em materiais</p>";
    }
    
    try {
        $pdo->exec("ALTER TABLE materiais ADD COLUMN updated_at DATETIME DEFAULT CURRENT_TIMESTAMP");
        echo "<p class='success'>✅ Coluna 'updated_at' adicionada em materiais</p>";
    } catch (PDOException $e) {
        echo "<p class='warning'>⚠️ Coluna 'updated_at' já existe em materiais</p>";
    }
    
    // ==========================================
    // TABELA SUGESTÕES
    // ==========================================
    $pdo->exec("CREATE TABLE IF NOT EXISTS sugestoes (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        texto TEXT NOT NULL,
        status TEXT DEFAULT 'nova',
        resposta_admin TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES profissionais(id)
    )");
    echo "<p class='success'>✅ Tabela 'sugestoes' criada/verificada</p>";
    
    // ==========================================
    // TABELAS DO FÓRUM
    // ==========================================
    $pdo->exec("CREATE TABLE IF NOT EXISTS forum_posts (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        titulo TEXT NOT NULL,
        conteudo TEXT NOT NULL,
        categoria TEXT DEFAULT 'geral',
        views INTEGER DEFAULT 0,
        status TEXT DEFAULT 'ativo',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES profissionais(id)
    )");
    echo "<p class='success'>✅ Tabela 'forum_posts' criada/verificada</p>";
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS forum_comentarios (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        post_id INTEGER NOT NULL,
        user_id INTEGER NOT NULL,
        comentario TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (post_id) REFERENCES forum_posts(id),
        FOREIGN KEY (user_id) REFERENCES profissionais(id)
    )");
    echo "<p class='success'>✅ Tabela 'forum_comentarios' criada/verificada</p>";
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS forum_curtidas (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        post_id INTEGER NOT NULL,
        user_id INTEGER NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE(post_id, user_id),
        FOREIGN KEY (post_id) REFERENCES forum_posts(id),
        FOREIGN KEY (user_id) REFERENCES profissionais(id)
    )");
    echo "<p class='success'>✅ Tabela 'forum_curtidas' criada/verificada</p>";
    
    echo "</div>";
    
    // ==========================================
    // CRIAR SUPER ADMIN
    // ==========================================
    echo "<div class='step'><h3>👑 Passo 2: Super Administrador</h3>";
    
    $stmt = $pdo->prepare("SELECT * FROM profissionais WHERE email = :email");
    $stmt->execute([':email' => SUPER_ADMIN_EMAIL]);
    $superAdmin = $stmt->fetch();
    
    if (!$superAdmin) {
        // Criar Super Admin
        $senhaHash = hashPassword(SUPER_ADMIN_PASSWORD);
        $stmt = $pdo->prepare("INSERT INTO profissionais (nome, email, senha, especialidade, whatsapp, role, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            'Karen Gomes',
            SUPER_ADMIN_EMAIL,
            $senhaHash,
            'Administrador do Sistema',
            '(15) 99999-9999',
            ROLE_SUPERADMIN,
            'ativo'
        ]);
        
        echo "<p class='success'>✅ Super Admin criado com sucesso!</p>";
        echo "<p><strong>Email:</strong> <code>" . SUPER_ADMIN_EMAIL . "</code></p>";
        echo "<p><strong>Senha:</strong> <code>" . SUPER_ADMIN_PASSWORD . "</code></p>";
    } else {
        // Atualizar senha e garantir role superadmin
        $senhaHash = hashPassword(SUPER_ADMIN_PASSWORD);
        $stmt = $pdo->prepare("UPDATE profissionais SET senha = ?, role = ?, status = 'ativo' WHERE email = ?");
        $stmt->execute([$senhaHash, ROLE_SUPERADMIN, SUPER_ADMIN_EMAIL]);
        
        echo "<p class='success'>✅ Super Admin atualizado!</p>";
        echo "<p><strong>Email:</strong> <code>" . SUPER_ADMIN_EMAIL . "</code></p>";
        echo "<p><strong>Senha:</strong> <code>" . SUPER_ADMIN_PASSWORD . "</code></p>";
    }
    
    echo "</div>";
    
    // ==========================================
    // MIGRAR USUÁRIOS ANTIGOS (Se houver)
    // ==========================================
    echo "<div class='step'><h3>🔄 Passo 3: Migração de Dados Antigos</h3>";
    
    $stmt = $pdo->query("SELECT * FROM profissionais WHERE senha IS NULL OR senha = ''");
    $usuariosSemSenha = $stmt->fetchAll();
    
    if (count($usuariosSemSenha) > 0) {
        foreach ($usuariosSemSenha as $user) {
            if ($user['email'] == SUPER_ADMIN_EMAIL) continue; // Pula o super admin
            
            // Gera senha temporária baseada no nome
            $senhaTemp = strtolower(str_replace(' ', '', explode(' ', $user['nome'])[0])) . '123';
            $senhaHash = hashPassword($senhaTemp);
            
            $stmt = $pdo->prepare("UPDATE profissionais SET senha = ?, role = 'user', status = 'ativo' WHERE id = ?");
            $stmt->execute([$senhaHash, $user['id']]);
            
            echo "<p class='warning'>⚠️ Usuário '{$user['nome']}' migrado. Senha temporária: <code>{$senhaTemp}</code></p>";
        }
    } else {
        echo "<p class='success'>✅ Todos os usuários já possuem senha configurada</p>";
    }
    
    echo "</div>";
    
    // ==========================================
    // ESTATÍSTICAS
    // ==========================================
    echo "<div class='step'><h3>📈 Estatísticas do Sistema</h3>";
    
    $totalUsuarios = $pdo->query("SELECT COUNT(*) FROM profissionais")->fetchColumn();
    $totalAdmins = $pdo->query("SELECT COUNT(*) FROM profissionais WHERE role IN ('admin', 'superadmin')")->fetchColumn();
    $totalMateriais = $pdo->query("SELECT COUNT(*) FROM materiais")->fetchColumn();
    $totalSugestoes = $pdo->query("SELECT COUNT(*) FROM sugestoes")->fetchColumn();
    
    echo "<p>👥 <strong>Total de Usuários:</strong> {$totalUsuarios}</p>";
    echo "<p>🛡️ <strong>Administradores:</strong> {$totalAdmins}</p>";
    echo "<p>📚 <strong>Materiais:</strong> {$totalMateriais}</p>";
    echo "<p>💡 <strong>Sugestões:</strong> {$totalSugestoes}</p>";
    
    echo "</div>";
    
    // ==========================================
    // FINALIZAÇÃO
    // ==========================================
    echo "<div class='step'>";
    echo "<h3 class='success'>🎉 Instalação Concluída com Sucesso!</h3>";
    echo "<p>✅ O sistema está pronto para uso!</p>";
    echo "<p>🚀 <a href='login.php' style='background: #6e2b3a; color: white; padding: 10px 20px; text-decoration: none; border-radius: 8px; display: inline-block; margin-top: 10px;'>Ir para o Login</a></p>";
    echo "<p style='margin-top: 20px; color: #ef4444;'><strong>⚠️ IMPORTANTE:</strong> Por segurança, delete este arquivo (setup_banco.php) após a instalação!</p>";
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<div class='step error'>";
    echo "<h3 class='error'>❌ Erro Fatal</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}

?>

    </div>
</body>
</html>