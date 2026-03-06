<?php
// ==========================================
// SCRIPT DE MIGRAÇÃO - CORREÇÃO DE USUÁRIOS
// Execute UMA VEZ e depois DELETE este arquivo!
// ==========================================

require_once 'config.php';

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Migração de Usuários - Melodias</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8fafc;
            padding: 40px;
            max-width: 800px;
            margin: 0 auto;
        }
        .container {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        h1 {
            color: #1b333d;
            border-bottom: 3px solid #6e2b3a;
            padding-bottom: 15px;
        }
        .step {
            background: #f9fafb;
            padding: 20px;
            margin: 15px 0;
            border-radius: 10px;
            border-left: 5px solid #10b981;
        }
        .step.error {
            border-left-color: #ef4444;
            background: #fef2f2;
        }
        .success {
            color: #10b981;
            font-weight: bold;
        }
        .error {
            color: #ef4444;
            font-weight: bold;
        }
        .warning {
            color: #f59e0b;
            font-weight: bold;
        }
        code {
            background: #1e293b;
            color: #10b981;
            padding: 3px 8px;
            border-radius: 5px;
            font-family: monospace;
        }
        .btn {
            background: #6e2b3a;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            display: inline-block;
            margin-top: 20px;
            cursor: pointer;
            font-weight: bold;
        }
        .btn:hover {
            background: #4a1d27;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Migração de Usuários</h1>
        <p>Este script vai corrigir usuários sem a coluna <code>role</code> ou <code>status</code> definida.</p>

<?php

try {
    $pdo = getDB();
    
    echo "<div class='step'><h3>📊 Verificando Usuários...</h3>";
    
    // Lista todos os usuários
    $stmt = $pdo->query("SELECT id, nome, email, role, status FROM profissionais");
    $usuarios = $stmt->fetchAll();
    
    echo "<p>Total de usuários encontrados: <strong>" . count($usuarios) . "</strong></p>";
    echo "</div>";
    
    echo "<div class='step'><h3>🔄 Corrigindo Usuários...</h3>";
    
    $corrigidos = 0;
    $jaCorretos = 0;
    
    foreach ($usuarios as $user) {
        $precisaAtualizacao = false;
        $updates = [];
        $values = [];
        
        // Verifica role
        if (!isset($user['role']) || empty($user['role']) || $user['role'] === null) {
            $updates[] = "role = ?";
            $values[] = 'user';
            $precisaAtualizacao = true;
        }
        
        // Verifica status
        if (!isset($user['status']) || empty($user['status']) || $user['status'] === null) {
            $updates[] = "status = ?";
            $values[] = 'ativo';
            $precisaAtualizacao = true;
        }
        
        if ($precisaAtualizacao) {
            $values[] = $user['id'];
            $sql = "UPDATE profissionais SET " . implode(", ", $updates) . " WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($values);
            
            echo "<p class='success'>✅ Usuário corrigido: <strong>{$user['nome']}</strong> (ID: {$user['id']})</p>";
            $corrigidos++;
        } else {
            $jaCorretos++;
        }
    }
    
    echo "<p style='margin-top: 20px;'><strong>Resumo:</strong></p>";
    echo "<p>✅ Usuários corrigidos: <strong class='success'>{$corrigidos}</strong></p>";
    echo "<p>✔️ Já estavam corretos: <strong>{$jaCorretos}</strong></p>";
    
    echo "</div>";
    
    // Garante que o super admin está correto
    echo "<div class='step'><h3>👑 Verificando Super Admin...</h3>";
    
    $stmt = $pdo->prepare("SELECT * FROM profissionais WHERE email = ?");
    $stmt->execute([SUPER_ADMIN_EMAIL]);
    $superAdmin = $stmt->fetch();
    
    if ($superAdmin) {
        // Atualiza super admin
        $stmt = $pdo->prepare("UPDATE profissionais SET role = ?, status = 'ativo' WHERE email = ?");
        $stmt->execute([ROLE_SUPERADMIN, SUPER_ADMIN_EMAIL]);
        echo "<p class='success'>✅ Super Admin verificado e atualizado!</p>";
        echo "<p><strong>Email:</strong> <code>" . SUPER_ADMIN_EMAIL . "</code></p>";
    } else {
        echo "<p class='warning'>⚠️ Super Admin não encontrado no banco de dados.</p>";
        echo "<p>Execute o <code>setup_banco.php</code> primeiro!</p>";
    }
    
    echo "</div>";
    
    // Lista status final de todos os usuários
    echo "<div class='step'><h3>📋 Status Final dos Usuários</h3>";
    echo "<table style='width: 100%; border-collapse: collapse; margin-top: 15px;'>";
    echo "<thead><tr style='background: #f3f4f6;'><th style='padding: 10px; text-align: left;'>Nome</th><th style='padding: 10px; text-align: left;'>Email</th><th style='padding: 10px; text-align: center;'>Permissão</th><th style='padding: 10px; text-align: center;'>Status</th></tr></thead>";
    echo "<tbody>";
    
    $stmt = $pdo->query("SELECT nome, email, role, status FROM profissionais ORDER BY id");
    $usuarios = $stmt->fetchAll();
    
    foreach ($usuarios as $u) {
        $roleBadge = $u['role'] === 'superadmin' ? '👑 Super Admin' : ($u['role'] === 'admin' ? '🛡️ Admin' : '👤 User');
        $statusBadge = $u['status'] === 'ativo' ? '<span style="color: #10b981;">✅ Ativo</span>' : '<span style="color: #ef4444;">❌ Inativo</span>';
        
        echo "<tr style='border-bottom: 1px solid #e5e7eb;'>";
        echo "<td style='padding: 10px;'>{$u['nome']}</td>";
        echo "<td style='padding: 10px; font-size: 0.9em;'>{$u['email']}</td>";
        echo "<td style='padding: 10px; text-align: center;'><strong>{$roleBadge}</strong></td>";
        echo "<td style='padding: 10px; text-align: center;'>{$statusBadge}</td>";
        echo "</tr>";
    }
    
    echo "</tbody></table>";
    echo "</div>";
    
    echo "<div class='step'>";
    echo "<h3 class='success'>🎉 Migração Concluída com Sucesso!</h3>";
    echo "<p>✅ Todos os usuários foram verificados e corrigidos</p>";
    echo "<p>✅ O sistema agora está pronto para uso</p>";
    echo "<p style='margin-top: 20px; color: #ef4444;'><strong>⚠️ IMPORTANTE:</strong> Delete este arquivo (fix_users.php) por segurança!</p>";
    echo "<a href='painel.php' class='btn'>🚀 Ir para o Painel</a>";
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<div class='step error'>";
    echo "<h3 class='error'>❌ Erro na Migração</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "<p style='margin-top: 15px;'>Verifique se o arquivo <code>banco_melodias.sqlite</code> existe e rode o <code>setup_banco.php</code> primeiro!</p>";
    echo "</div>";
}

?>

    </div>
</body>
</html>
