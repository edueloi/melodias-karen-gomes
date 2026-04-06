<?php
// ==========================================
// SISTEMA MELODIAS V2.0 - PAINEL COMPLETO
// Sistema de Gestão com Permissões Avançadas
// ==========================================
require_once 'config.php';
require_once 'email_config.php';

// ==========================================
// AUTO-SETUP: Garante estrutura do banco
// (Apenas no ambiente local/SQLite.
//  Em produção as tabelas são criadas via migrate_mysql.sql)
// ==========================================
try {
    $pdo = getDB();

    if (dbDriver() === 'sqlite') {
        $pdo->exec("CREATE TABLE IF NOT EXISTS materiais (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            titulo TEXT NOT NULL, descricao TEXT, categoria TEXT NOT NULL,
            tipo TEXT DEFAULT 'arquivo', caminho TEXT, capa TEXT,
            visibilidade TEXT DEFAULT 'todos', created_by INTEGER,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
        $pdo->exec("CREATE TABLE IF NOT EXISTS sugestoes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL, texto TEXT NOT NULL,
            status TEXT DEFAULT 'nova', resposta_admin TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
        $pdo->exec("CREATE TABLE IF NOT EXISTS forum_posts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL, titulo TEXT NOT NULL, conteudo TEXT NOT NULL,
            categoria TEXT DEFAULT 'geral', views INTEGER DEFAULT 0,
            status TEXT DEFAULT 'ativo',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
        $pdo->exec("CREATE TABLE IF NOT EXISTS forum_comentarios (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            post_id INTEGER NOT NULL, user_id INTEGER NOT NULL, comentario TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
        $pdo->exec("CREATE TABLE IF NOT EXISTS forum_curtidas (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            post_id INTEGER NOT NULL, user_id INTEGER NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE(post_id, user_id)
        )");
        $pdo->exec("CREATE TABLE IF NOT EXISTS eventos (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            titulo TEXT NOT NULL, descricao TEXT, data_evento DATETIME,
            local TEXT, mapa_link TEXT, created_by INTEGER,
            rsvp_ativo INTEGER DEFAULT 1,
            permite_acompanhantes INTEGER DEFAULT 0,
            colaborativo_ativo INTEGER DEFAULT 0,
            itens_colaborativos TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
        $pdo->exec("CREATE TABLE IF NOT EXISTS eventos_presenca (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            evento_id INTEGER NOT NULL, user_id INTEGER NOT NULL,
            status TEXT DEFAULT 'confirmado',
            acompanhantes INTEGER DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE(evento_id, user_id)
        )");
        $pdo->exec("CREATE TABLE IF NOT EXISTS eventos_contribuicoes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            evento_id INTEGER NOT NULL, user_id INTEGER NOT NULL,
            item_nome TEXT NOT NULL,
            quantidade TEXT, 
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
        $pdo->exec("CREATE TABLE IF NOT EXISTS configuracoes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            chave TEXT UNIQUE NOT NULL, valor TEXT,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        // Colunas adicionadas em versões posteriores
        $alter = [
            "ALTER TABLE profissionais ADD COLUMN role TEXT DEFAULT 'user'",
            "ALTER TABLE profissionais ADD COLUMN status TEXT DEFAULT 'ativo'",
            "ALTER TABLE profissionais ADD COLUMN senha TEXT",
            "ALTER TABLE profissionais ADD COLUMN foto TEXT",
            "ALTER TABLE profissionais ADD COLUMN bio TEXT",
            "ALTER TABLE profissionais ADD COLUMN registro_tipo TEXT",
            "ALTER TABLE profissionais ADD COLUMN registro_numero TEXT",
            "ALTER TABLE profissionais ADD COLUMN area_atuacao TEXT",
            "ALTER TABLE profissionais ADD COLUMN formacao_superior TEXT",
            "ALTER TABLE profissionais ADD COLUMN formacao_pos TEXT",
            "ALTER TABLE profissionais ADD COLUMN instagram TEXT",
            "ALTER TABLE profissionais ADD COLUMN website TEXT",
            "ALTER TABLE profissionais ADD COLUMN genero TEXT DEFAULT 'Não declarado'",
            "ALTER TABLE profissionais ADD COLUMN endereco TEXT",
            "ALTER TABLE profissionais ADD COLUMN descricao_trabalho TEXT",
            "ALTER TABLE profissionais ADD COLUMN aceita_parcerias TEXT DEFAULT 'Não'",
            "ALTER TABLE profissionais ADD COLUMN preco_social TEXT DEFAULT 'Não'",
            "ALTER TABLE profissionais ADD COLUMN created_at DATETIME DEFAULT CURRENT_TIMESTAMP",
            "ALTER TABLE profissionais ADD COLUMN updated_at DATETIME DEFAULT CURRENT_TIMESTAMP",
            "ALTER TABLE materiais ADD COLUMN descricao TEXT",
            "ALTER TABLE materiais ADD COLUMN tipo TEXT DEFAULT 'material'",
            "ALTER TABLE materiais ADD COLUMN autor TEXT",
            "ALTER TABLE materiais ADD COLUMN url_externa TEXT",
            "ALTER TABLE materiais ADD COLUMN capa TEXT",
            "ALTER TABLE materiais ADD COLUMN created_by INTEGER",
            "ALTER TABLE materiais ADD COLUMN created_at DATETIME DEFAULT CURRENT_TIMESTAMP",
            "ALTER TABLE materiais ADD COLUMN updated_at DATETIME DEFAULT CURRENT_TIMESTAMP",
            "ALTER TABLE eventos ADD COLUMN rsvp_ativo INTEGER DEFAULT 1",
            "ALTER TABLE eventos ADD COLUMN permite_acompanhantes INTEGER DEFAULT 0",
            "ALTER TABLE eventos ADD COLUMN colaborativo_ativo INTEGER DEFAULT 0",
            "ALTER TABLE eventos ADD COLUMN itens_colaborativos TEXT",
            "ALTER TABLE eventos_presenca ADD COLUMN acompanhantes INTEGER DEFAULT 0",
            "ALTER TABLE eventos ADD COLUMN capa TEXT",
            "CREATE TABLE IF NOT EXISTS eventos_presenca_externa (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                evento_id INTEGER NOT NULL,
                nome TEXT NOT NULL,
                whatsapp TEXT,
                status TEXT DEFAULT 'confirmado',
                acompanhantes INTEGER DEFAULT 0,
                contribuicao_item TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )",
        ];
        foreach ($alter as $sql) { try { $pdo->exec($sql); } catch(Exception $e){} }
    }

    // Configurações padrão (compatível SQLite e MySQL)
    $configs_padrao = [
        ['whatsapp_auto_abrir', '1'],
        ['whatsapp_mensagem_template', "🎉 *Bem-vindo(a) ao Melodias!*\n\nOlá {NOME}, sua solicitação foi *aprovada*!\n\n📋 *Seus dados de acesso:*\n\n🔗 *Link:*\n{LINK}\n\n📧 *Email/Login:*\n{EMAIL}\n\n🔑 *Senha Temporária:*\n{SENHA}\n\n⚠️ _Recomendamos trocar sua senha após o primeiro acesso._\n\n✨ Agora você faz parte da nossa rede de profissionais em saúde mental!"]
    ];
    $insert_ignore = dbDriver() === 'mysql'
        ? "INSERT IGNORE INTO configuracoes (chave, valor) VALUES (?, ?)"
        : "INSERT OR IGNORE INTO configuracoes (chave, valor) VALUES (?, ?)";
    foreach ($configs_padrao as $config) {
        try { $pdo->prepare($insert_ignore)->execute($config); } catch(Exception $e){}
    }

} catch(Exception $e) {
    // Ignora erros de estrutura
}

// Verificações de Segurança
verificarLogin();
if (isset($_GET['sair'])) logout();

// Dados do Usuário Logado
$pdo = getDB();
$user = getUsuarioLogado();

if (!$user || $user['status'] === 'inativo') {
    session_destroy();
    die("<h1 style='text-align:center;margin-top:100px;color:#ef4444;'>🚫 Conta Suspensa</h1><p style='text-align:center;'>Entre em contato com o administrador.</p>");
}

$id_usuario = $user['id'];
$role = $user['role'] ?? 'user'; // Define 'user' como padrão se não existir
$primeiro_nome = explode(' ', trim($user['nome']))[0];
$notificacao = '';
$stats_sugestoes = 0;
$stats_users = 0;

// Atualiza role na sessão se não estiver definida
if (!isset($_SESSION['role']) || $_SESSION['role'] !== $role) {
    $_SESSION['role'] = $role;
}

// ==========================================
// PROCESSAMENTO DE AÇÕES AJAX/POST
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';

    // =========== AÇÕES DE TODOS OS USUÁRIOS ===========
    if ($acao === 'add_sugestao') {
        $texto = sanitize($_POST['texto']);
        if (!empty($texto)) {
            $stmt = $pdo->prepare("INSERT INTO sugestoes (user_id, texto) VALUES (?, ?)");
            $stmt->execute([$id_usuario, $texto]);
            $notificacao = "showToast('Sucesso', 'Sua ideia foi enviada!', 'success');";
        }
    }
    
    // --- EVENTOS / PRESENÇA ---
    if ($acao === 'confirmar_presenca') {
        $evento_id = $_POST['evento_id'];
        $status = $_POST['status_presenca'] ?? 'confirmado'; // 'confirmado' ou 'recusado'
        $acompanhantes = (int)($_POST['acompanhantes'] ?? 0);
        
        $stmt = $pdo->prepare("SELECT id FROM eventos_presenca WHERE evento_id = ? AND user_id = ?");
        $stmt->execute([$evento_id, $id_usuario]);
        
        if ($stmt->fetch()) {
            if($status === 'remover') {
                $pdo->prepare("DELETE FROM eventos_presenca WHERE evento_id = ? AND user_id = ?")->execute([$evento_id, $id_usuario]);
                // Remove contribuições se desistir do evento
                $pdo->prepare("DELETE FROM eventos_contribuicoes WHERE evento_id = ? AND user_id = ?")->execute([$evento_id, $id_usuario]);
                $notificacao = "showToast('Cancelado', 'Sua presença foi cancelada', 'info');";
            } else {
                $pdo->prepare("UPDATE eventos_presenca SET status = ?, acompanhantes = ? WHERE evento_id = ? AND user_id = ?")->execute([$status, $acompanhantes, $evento_id, $id_usuario]);
                $msg = $status === 'confirmado' ? 'Presença confirmada!' : 'Você marcou que não irá.';
                $notificacao = "showToast('Atualizado', '{$msg}', 'success');";
            }
        } else {
            if($status !== 'remover') {
                $pdo->prepare("INSERT INTO eventos_presenca (evento_id, user_id, status, acompanhantes) VALUES (?, ?, ?, ?)")->execute([$evento_id, $id_usuario, $status, $acompanhantes]);
                $msg = $status === 'confirmado' ? 'Você confirmou sua presença!' : 'Você marcou que não irá.';
                $notificacao = "showToast('Confirmado', '{$msg}', 'success');";
            }
        }
    }

    if ($acao === 'gerenciar_contribuicao') {
        $evento_id = $_POST['evento_id'];
        $item_nome = sanitize($_POST['item_nome']);
        $operacao = $_POST['operacao']; // 'adicionar' ou 'remover'

        if ($operacao === 'adicionar') {
            $stmt = $pdo->prepare("INSERT INTO eventos_contribuicoes (evento_id, user_id, item_nome) VALUES (?, ?, ?)");
            $stmt->execute([$evento_id, $id_usuario, $item_nome]);
            $notificacao = "showToast('Adicionado', 'Você assumiu: {$item_nome}', 'success');";
        } else {
            $stmt = $pdo->prepare("DELETE FROM eventos_contribuicoes WHERE evento_id = ? AND user_id = ? AND item_nome = ?");
            $stmt->execute([$evento_id, $id_usuario, $item_nome]);
            $notificacao = "showToast('Removido', 'Contribuição removida', 'info');";
        }
    }
    
    // --- FÓRUM ---
    if ($acao === 'criar_post_forum') {
        $titulo = sanitize($_POST['titulo']);
        $conteudo = sanitize($_POST['conteudo']);
        $categoria = sanitize($_POST['categoria'] ?? 'geral');
        
        if (!empty($titulo) && !empty($conteudo)) {
            $stmt = $pdo->prepare("INSERT INTO forum_posts (user_id, titulo, conteudo, categoria) VALUES (?, ?, ?, ?)");
            $stmt->execute([$id_usuario, $titulo, $conteudo, $categoria]);
            $notificacao = "showToast('Publicado', 'Seu post foi criado com sucesso!', 'success');";
        }
    }
    
    if ($acao === 'comentar_post') {
        $post_id = $_POST['post_id'];
        $comentario = sanitize($_POST['comentario']);
        
        if (!empty($comentario)) {
            $stmt = $pdo->prepare("INSERT INTO forum_comentarios (post_id, user_id, comentario) VALUES (?, ?, ?)");
            $stmt->execute([$post_id, $id_usuario, $comentario]);
            $notificacao = "showToast('Comentado', 'Comentário adicionado!', 'success');";
        }
    }
    
    if ($acao === 'curtir_post') {
        $post_id = $_POST['post_id'];
        
        // Verifica se já curtiu
        $stmt = $pdo->prepare("SELECT id FROM forum_curtidas WHERE post_id = ? AND user_id = ?");
        $stmt->execute([$post_id, $id_usuario]);
        
        if ($stmt->fetch()) {
            // Remove curtida
            $pdo->prepare("DELETE FROM forum_curtidas WHERE post_id = ? AND user_id = ?")->execute([$post_id, $id_usuario]);
            $notificacao = "showToast('Curtida removida', 'Você descurtiu este post', 'success');";
        } else {
            // Adiciona curtida
            $pdo->prepare("INSERT INTO forum_curtidas (post_id, user_id) VALUES (?, ?)")->execute([$post_id, $id_usuario]);
            $notificacao = "showToast('Curtido', 'Você curtiu este post!', 'success');";
        }
    }

    
    // --- GESTÃO DE PERFIL ---
    if ($acao === 'update_perfil') {
        try {
            $nome = sanitize($_POST['nome']);
            $whatsapp = sanitize($_POST['whatsapp']);
            $genero = sanitize($_POST['genero'] ?? 'Não declarado');
            $especialidade = sanitize($_POST['especialidade']);
            $registro_tipo = sanitize($_POST['registro_tipo']);
            $registro_numero = sanitize($_POST['registro_numero']);
            $area_atuacao = sanitize($_POST['area_atuacao']);
            $formacao_superior = sanitize($_POST['formacao_superior']);
            $instagram = sanitize($_POST['instagram']);
            $website = sanitize($_POST['website']);
            $bio = trim($_POST['bio'] ?? '');
            
            // Novos campos
            $endereco = sanitize($_POST['endereco'] ?? '');
            $descricao_trabalho = sanitize($_POST['descricao_trabalho'] ?? '');
            $aceita_parcerias = sanitize($_POST['aceita_parcerias'] ?? 'Não');
            $preco_social = sanitize($_POST['preco_social'] ?? 'Não');
            
            // Formações (Pós)
            $pos = $_POST['formacao_pos'] ?? [];
            $pos = array_filter(array_map('sanitize', $pos));
            $formacao_pos_json = json_encode(array_values($pos));

            try {
                $stmt = $pdo->prepare("UPDATE profissionais SET 
                    nome = ?, whatsapp = ?, genero = ?, especialidade = ?, registro_tipo = ?, 
                    registro_numero = ?, area_atuacao = ?, formacao_superior = ?, 
                    formacao_pos = ?, instagram = ?, website = ?, bio = ?,
                    endereco = ?, descricao_trabalho = ?, aceita_parcerias = ?, preco_social = ?,
                    updated_at = CURRENT_TIMESTAMP
                    WHERE id = ?");
                $stmt->execute([
                    $nome, $whatsapp, $genero, $especialidade, $registro_tipo,
                    $registro_numero, $area_atuacao, $formacao_superior,
                    $formacao_pos_json, $instagram, $website, $bio,
                    $endereco, $descricao_trabalho, $aceita_parcerias, $preco_social,
                    $id_usuario
                ]);
            } catch (PDOException $e) {
                // Fallback se updated_at não existir
                $stmt = $pdo->prepare("UPDATE profissionais SET 
                    nome = ?, whatsapp = ?, genero = ?, especialidade = ?, registro_tipo = ?, 
                    registro_numero = ?, area_atuacao = ?, formacao_superior = ?, 
                    formacao_pos = ?, instagram = ?, website = ?, bio = ?,
                    endereco = ?, descricao_trabalho = ?, aceita_parcerias = ?, preco_social = ?
                    WHERE id = ?");
                $stmt->execute([
                    $nome, $whatsapp, $genero, $especialidade, $registro_tipo,
                    $registro_numero, $area_atuacao, $formacao_superior,
                    $formacao_pos_json, $instagram, $website, $bio,
                    $endereco, $descricao_trabalho, $aceita_parcerias, $preco_social,
                    $id_usuario
                ]);
            }

            $_SESSION['nome_usuario'] = $nome;
            $notificacao = "showToast('Perfil Atualizado', 'Seus dados foram salvos com sucesso!', 'success');";
        } catch (Exception $e) {
            $notificacao = "showToast('Erro', 'Falha ao salvar: ".$e->getMessage()."', 'error');";
        }
        $user = getUsuarioLogado();
    }

    // --- ALTERAR SENHA ---
    if ($acao === 'change_password') {
        try {
            $senha_atual = $_POST['senha_atual'] ?? '';
            $nova_senha = $_POST['nova_senha'] ?? '';
            $confirma_senha = $_POST['confirma_senha'] ?? '';

            if (empty($senha_atual) || empty($nova_senha) || empty($confirma_senha)) {
                throw new Exception("Todos os campos de senha são obrigatórios.");
            }
            if (strlen($nova_senha) < 8) {
                throw new Exception("A nova senha deve ter pelo menos 8 caracteres.");
            }
            if ($nova_senha !== $confirma_senha) {
                throw new Exception("A nova senha e a confirmação não coincidem.");
            }

            // Busca senha atual no banco
            $stmt = $pdo->prepare("SELECT senha FROM profissionais WHERE id = ?");
            $stmt->execute([$id_usuario]);
            $user_db = $stmt->fetch();

            if (!$user_db || !verifyPassword($senha_atual, $user_db['senha'])) {
                throw new Exception("A senha atual digitada está incorreta.");
            }

            $novoHash = hashPassword($nova_senha);
            try {
                $stmt = $pdo->prepare("UPDATE profissionais SET senha = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
                $stmt->execute([$novoHash, $id_usuario]);
            } catch (PDOException $e) {
                $stmt = $pdo->prepare("UPDATE profissionais SET senha = ? WHERE id = ?");
                $stmt->execute([$novoHash, $id_usuario]);
            }

            $notificacao = "showToast('Sucesso', 'Sua senha foi alterada com sucesso!', 'success');";
        } catch (Exception $e) {
            $notificacao = "showToast('Erro', '".$e->getMessage()."', 'error');";
        }
    }

    if ($acao === 'upload_foto_perfil') {
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === 0) {
            $max_size = 5 * 1024 * 1024;
            if ($_FILES['foto']['size'] > $max_size) {
                $notificacao = "showToast('Erro', 'Arquivo muito grande! Máximo 5MB.', 'error');";
            } else {
                $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
                $permitidos = ['jpg', 'jpeg', 'png', 'webp'];
                if (in_array($ext, $permitidos)) {
                    if (!empty($user['foto']) && file_exists($user['foto'])) @unlink($user['foto']);
                    
                    $new_name = 'uploads/profile_' . $id_usuario . '_' . time() . '.' . $ext;
                    if (!is_dir('uploads')) mkdir('uploads', 0755, true);
                    if (move_uploaded_file($_FILES['foto']['tmp_name'], $new_name)) {
                        try {
                            $pdo->prepare("UPDATE profissionais SET foto = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?")->execute([$new_name, $id_usuario]);
                        } catch(Exception $e) {
                            $pdo->prepare("UPDATE profissionais SET foto = ? WHERE id = ?")->execute([$new_name, $id_usuario]);
                        }
                        $notificacao = "showToast('Foto Atualizada', 'Sua nova foto já foi salva.', 'success');";
                    }
                } else {
                    $notificacao = "showToast('Erro', 'Formato inválido. Use JPG, PNG ou WebP.', 'error');";
                }
            }
        }
        $user = getUsuarioLogado();
    }

    if ($acao === 'delete_foto_perfil') {
        if (!empty($user['foto']) && file_exists($user['foto'])) @unlink($user['foto']);
        try {
            $pdo->prepare("UPDATE profissionais SET foto = NULL, updated_at = CURRENT_TIMESTAMP WHERE id = ?")->execute([$id_usuario]);
        } catch(Exception $e) {
            $pdo->prepare("UPDATE profissionais SET foto = NULL WHERE id = ?")->execute([$id_usuario]);
        }
        $notificacao = "showToast('Foto Removida', 'Sua foto de perfil foi excluída.', 'info');";
        $user = getUsuarioLogado();
    }

    // =========== AÇÕES DE ADMIN E SUPERADMIN ===========
    if ($role === ROLE_ADMIN || $role === ROLE_SUPERADMIN) {
        
        if ($acao === 'delete_post_forum') {
            $post_id = $_POST['post_id'];
            $pdo->prepare("DELETE FROM forum_curtidas WHERE post_id = ?")->execute([$post_id]);
            $pdo->prepare("DELETE FROM forum_comentarios WHERE post_id = ?")->execute([$post_id]);
            $pdo->prepare("DELETE FROM forum_posts WHERE id = ?")->execute([$post_id]);
            $notificacao = "showToast('Excluído', 'O tópico foi apagado com sucesso.', 'success');";
        }
        
        // --- EVENTOS ---
        if ($acao === 'add_evento') {
            $titulo = sanitize($_POST['titulo']);
            $descricao = sanitize($_POST['descricao']);
            $data_evento = $_POST['data_evento'];
            $local = sanitize($_POST['local']);
            $mapa_link = sanitize($_POST['mapa_link'] ?? '');
            
            // Novos campos
            $rsvp_ativo = isset($_POST['rsvp_ativo']) ? 1 : 0;
            $permite_acompanhantes = isset($_POST['permite_acompanhantes']) ? 1 : 0;
            $colaborativo_ativo = isset($_POST['colaborativo_ativo']) ? 1 : 0;
            
            $itens_colaborativos = '';
            if (isset($_POST['itens_colaborativos'])) {
                if (is_array($_POST['itens_colaborativos'])) {
                    $itens_colaborativos = implode(', ', array_filter($_POST['itens_colaborativos'], 'strlen'));
                } else {
                    $itens_colaborativos = sanitize($_POST['itens_colaborativos']);
                }
            }
            
            // Upload Foto Evento
            $capa = '';
            if (!empty($_FILES['capa']['name'])) {
                if (!is_dir('uploads/eventos')) mkdir('uploads/eventos', 0755, true);
                $ext = pathinfo($_FILES['capa']['name'], PATHINFO_EXTENSION);
                $capa = 'uploads/eventos/' . time() . '_' . rand(100,999) . '.' . $ext;
                move_uploaded_file($_FILES['capa']['tmp_name'], $capa);
            }
            
            $stmt = $pdo->prepare("INSERT INTO eventos (titulo, descricao, data_evento, local, mapa_link, rsvp_ativo, permite_acompanhantes, colaborativo_ativo, itens_colaborativos, capa, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$titulo, $descricao, $data_evento, $local, $mapa_link, $rsvp_ativo, $permite_acompanhantes, $colaborativo_ativo, $itens_colaborativos, $capa, $id_usuario]);
            $notificacao = "showToast('Sucesso', 'Evento criado com sucesso!', 'success');";
        }

        if ($acao === 'edit_evento') {
            $id_ev = $_POST['id_evento'];
            $titulo = sanitize($_POST['titulo']);
            $descricao = sanitize($_POST['descricao']);
            $data_evento = $_POST['data_evento'];
            $local = sanitize($_POST['local']);
            $mapa_link = sanitize($_POST['mapa_link'] ?? '');
            
            // Novos campos
            $rsvp_ativo = isset($_POST['rsvp_ativo']) ? 1 : 0;
            $permite_acompanhantes = isset($_POST['permite_acompanhantes']) ? 1 : 0;
            $colaborativo_ativo = isset($_POST['colaborativo_ativo']) ? 1 : 0;
            
            $itens_colaborativos = '';
            if (isset($_POST['itens_colaborativos'])) {
                if (is_array($_POST['itens_colaborativos'])) {
                    $itens_colaborativos = implode(', ', array_filter($_POST['itens_colaborativos'], 'strlen'));
                } else {
                    $itens_colaborativos = sanitize($_POST['itens_colaborativos']);
                }
            }
            
            $update_capa_sql = "";
            $params = [$titulo, $descricao, $data_evento, $local, $mapa_link, $rsvp_ativo, $permite_acompanhantes, $colaborativo_ativo, $itens_colaborativos];
            
            if (!empty($_FILES['capa']['name'])) {
                if (!is_dir('uploads/eventos')) mkdir('uploads/eventos', 0755, true);
                $ext = pathinfo($_FILES['capa']['name'], PATHINFO_EXTENSION);
                $capa = 'uploads/eventos/' . time() . '_' . rand(100,999) . '.' . $ext;
                
                // Remove antiga se existir
                $st_search = $pdo->prepare("SELECT capa FROM eventos WHERE id = ?");
                $st_search->execute([$id_ev]);
                if($ev_old = $st_search->fetch()) {
                    if(!empty($ev_old['capa']) && file_exists($ev_old['capa'])) @unlink($ev_old['capa']);
                }
                
                move_uploaded_file($_FILES['capa']['tmp_name'], $capa);
                $update_capa_sql = ", capa = ?";
                $params[] = $capa;
            }
            
            $params[] = $id_ev;
            
            $stmt = $pdo->prepare("UPDATE eventos SET titulo = ?, descricao = ?, data_evento = ?, local = ?, mapa_link = ?, rsvp_ativo = ?, permite_acompanhantes = ?, colaborativo_ativo = ?, itens_colaborativos = ?{$update_capa_sql} WHERE id = ?");
            $stmt->execute($params);
            $notificacao = "showToast('Sucedido', 'Evento atualizado!', 'success');";
        }

        if ($acao === 'delete_evento') {
            $id_evento = $_POST['id_evento'];
            $pdo->prepare("DELETE FROM eventos_presenca WHERE evento_id = ?")->execute([$id_evento]);
            $pdo->prepare("DELETE FROM eventos_contribuicoes WHERE evento_id = ?")->execute([$id_evento]);
            $pdo->prepare("DELETE FROM eventos WHERE id = ?")->execute([$id_evento]);
            $notificacao = "showToast('Excluído', 'Evento removido com sucesso!', 'error');";
        }

        if ($acao === 'delete_presenca') {
            $tipo = $_POST['tipo_presenca'];
            $id_p = $_POST['presenca_id'];
            
            if ($tipo === 'interna') {
                $pdo->prepare("DELETE FROM eventos_presenca WHERE id = ?")->execute([$id_p]);
            } else {
                $pdo->prepare("DELETE FROM eventos_presenca_externa WHERE id = ?")->execute([$id_p]);
            }
            $notificacao = "showToast('Removido', 'Presença removida com sucesso!', 'success');";
        }

        if ($acao === 'toggle_contribuicao') {
            $ev_id = $_POST['evento_id'];
            $item = $_POST['item_nome'];
            $mode = $_POST['modo']; // 'adicionar' ou 'remover'
            $obs = sanitize($_POST['obs'] ?? '');

            if ($mode === 'adicionar') {
                $pdo->prepare("INSERT INTO eventos_contribuicoes (evento_id, user_id, item_nome, contribuicao_obs) VALUES (?, ?, ?, ?)")
                    ->execute([$ev_id, $id_usuario, $item, $obs]);
                $notificacao = "showToast('Confirmado', 'Você está levando: $item', 'success');";
            } else {
                $pdo->prepare("DELETE FROM eventos_contribuicoes WHERE evento_id = ? AND user_id = ? AND item_nome = ?")
                    ->execute([$ev_id, $id_usuario, $item]);
                $notificacao = "showToast('Removido', 'Você não está mais levando este item.', 'info');";
            }
        }
    
        // --- MATERIAIS ---
        if ($acao === 'add_material') {
            $titulo = sanitize($_POST['titulo']);
            $categoria = sanitize($_POST['categoria']);
            $descricao = sanitize($_POST['descricao'] ?? '');
            $autor = sanitize($_POST['autor'] ?? '');
            $url_externa = sanitize($_POST['url_externa'] ?? '');
            $tipo_mat = sanitize($_POST['tipo'] ?? 'material');
            $visibilidade = $_POST['visibilidade'];
            $caminho = $url_externa; // Se houver link externo, o caminho padrão é ele
            
            if (!empty($_FILES['arquivo']['name'])) {
                if (!is_dir('uploads')) mkdir('uploads', 0755, true);
                $ext = pathinfo($_FILES['arquivo']['name'], PATHINFO_EXTENSION);
                $caminho = 'uploads/' . time() . '_' . rand(100,999) . '.' . $ext;
                move_uploaded_file($_FILES['arquivo']['tmp_name'], $caminho);
            }
            $capa = '';
            
            if (!empty($_FILES['capa']['name'])) {
                if (!is_dir('uploads/capas')) mkdir('uploads/capas', 0755, true);
                $ext = pathinfo($_FILES['capa']['name'], PATHINFO_EXTENSION);
                $capa = 'uploads/capas/' . time() . '_' . rand(100,999) . '.' . $ext;
                move_uploaded_file($_FILES['capa']['tmp_name'], $capa);
            }
            
            try {
                $stmt = $pdo->prepare("INSERT INTO materiais (titulo, categoria, descricao, autor, url_externa, tipo, caminho, capa, visibilidade, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$titulo, $categoria, $descricao, $autor, $url_externa, $tipo_mat, $caminho, $capa, $visibilidade, $id_usuario]);
            } catch (PDOException $e) {
                // Fallback robusto se colunas falharem
                $stmt = $pdo->prepare("INSERT INTO materiais (titulo, categoria, descricao, autor, url_externa, tipo, caminho, capa, visibilidade, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$titulo, $categoria, $descricao, $autor, $url_externa, $tipo_mat, $caminho, $capa, $visibilidade, $id_usuario]);
            }
            
            $notificacao = "showToast('Sucesso', 'Material adicionado com sucesso!', 'success');";
        }
        
        if ($acao === 'edit_material') {
            $id_mat = $_POST['id_material'];
            $titulo = sanitize($_POST['titulo']);
            $categoria = sanitize($_POST['categoria']);
            $descricao = sanitize($_POST['descricao'] ?? '');
            $autor = sanitize($_POST['autor'] ?? '');
            $url_externa = sanitize($_POST['url_externa'] ?? '');
            $tipo_mat = sanitize($_POST['tipo'] ?? 'material');
            $visibilidade = $_POST['visibilidade'];
            
            $update_capa_sql = "";
            $params = [$titulo, $categoria, $descricao, $autor, $url_externa, $tipo_mat, $visibilidade];
            
            if (!empty($_FILES['capa']['name'])) {
                if (!is_dir('uploads/capas')) mkdir('uploads/capas', 0755, true);
                $ext = pathinfo($_FILES['capa']['name'], PATHINFO_EXTENSION);
                $capa = 'uploads/capas/' . time() . '_' . rand(100,999) . '.' . $ext;
                
                // remove antiga
                $stmt_old = $pdo->prepare("SELECT capa FROM materiais WHERE id = ?");
                $stmt_old->execute([$id_mat]);
                if($mat_old = $stmt_old->fetch()) {
                    if(!empty($mat_old['capa'])) @unlink($mat_old['capa']);
                }
                
                move_uploaded_file($_FILES['capa']['tmp_name'], $capa);
                $update_capa_sql = ", capa = ?";
                $params[] = $capa;
            }
            $params[] = $id_mat;
            
            try {
                $stmt = $pdo->prepare("UPDATE materiais SET titulo = ?, categoria = ?, descricao = ?, autor = ?, url_externa = ?, tipo = ?, visibilidade = ?{$update_capa_sql}, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
                $stmt->execute($params);
            } catch (PDOException $e) {
                $stmt = $pdo->prepare("UPDATE materiais SET titulo = ?, categoria = ?, descricao = ?, autor = ?, url_externa = ?, tipo = ?, visibilidade = ?{$update_capa_sql} WHERE id = ?");
                $stmt->execute($params);
            }
            $notificacao = "showToast('Atualizado', 'Material editado!', 'success');";
        }
        
        if ($acao === 'delete_material') {
            $id_mat = $_POST['id_material'];
            $stmt = $pdo->prepare("SELECT caminho, capa FROM materiais WHERE id = ?");
            $stmt->execute([$id_mat]);
            if($mat = $stmt->fetch()) {
                if(!empty($mat['caminho'])) @unlink($mat['caminho']);
                if(!empty($mat['capa'])) @unlink($mat['capa']);
            }
            
            $pdo->prepare("DELETE FROM materiais WHERE id = ?")->execute([$id_mat]);
            $notificacao = "showToast('Excluído', 'Material removido com sucesso!', 'error');";
        }


        
        // --- SUGESTÕES ---
        if ($acao === 'status_sugestao') {
            $id_sug = $_POST['id_sugestao'];
            $status = $_POST['status'];
            try {
                $pdo->prepare("UPDATE sugestoes SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?")->execute([$status, $id_sug]);
            } catch (PDOException $e) {
                // Fallback: banco sem coluna updated_at
                $pdo->prepare("UPDATE sugestoes SET status = ? WHERE id = ?")->execute([$status, $id_sug]);
            }
            $notificacao = "showToast('Atualizado', 'Status da sugestão alterado!', 'success');";
        }
        
        // --- CONFIGURAÇÕES ---
        if ($acao === 'salvar_configuracoes') {
            verificarPermissao(ROLE_ADMIN);
            
            $whatsapp_auto = $_POST['whatsapp_auto_abrir'] ?? '0';
            $whatsapp_msg = $_POST['whatsapp_mensagem_template'] ?? '';
            
            // Atualiza configurações
            try {
                $stmt = $pdo->prepare("UPDATE configuracoes SET valor = ?, updated_at = CURRENT_TIMESTAMP WHERE chave = ?");
                $stmt->execute([$whatsapp_auto, 'whatsapp_auto_abrir']);
                $stmt->execute([$whatsapp_msg, 'whatsapp_mensagem_template']);
            } catch (PDOException $e) {
                $stmt = $pdo->prepare("UPDATE configuracoes SET valor = ? WHERE chave = ?");
                $stmt->execute([$whatsapp_auto, 'whatsapp_auto_abrir']);
                $stmt->execute([$whatsapp_msg, 'whatsapp_mensagem_template']);
            }
            
            $notificacao = "showToast('Salvo', 'Configurações atualizadas com sucesso!', 'success');";
        }
        
        if ($acao === 'delete_sugestao') {
            $id_sug = $_POST['id_sugestao'];
            $pdo->prepare("DELETE FROM sugestoes WHERE id = ?")->execute([$id_sug]);
            $notificacao = "showToast('Excluído', 'Sugestão removida!', 'error');";
        }
    }

    // =========== AÇÕES EXCLUSIVAS DO SUPERADMIN ===========
    if ($role === ROLE_SUPERADMIN) {
        
        // GERAR LINK DE CONVITE
        if ($acao === 'gerar_convite') {
            $limite = (int)$_POST['limite'];
            $validade_h = (int)$_POST['validade_horas'];
            $role_atribuida = $_POST['role'];
            $token = bin2hex(random_bytes(16)); // 32 chars
            
            $expira = date('Y-m-d H:i:s', strtotime("+{$validade_h} hours"));
            
            $stmt = $pdo->prepare("INSERT INTO convites (token, expira_em, limite_usos, role_atribuida) VALUES (?, ?, ?, ?)");
            $stmt->execute([$token, $expira, $limite, $role_atribuida]);
            
            $notificacao = "showToast('Link Gerado', 'O link de convite foi criado com sucesso!', 'success');";
        }
        
        // DELETAR LINK DE CONVITE
        if ($acao === 'delete_convite') {
            $id_c = $_POST['id_convite'];
            $pdo->prepare("DELETE FROM convites WHERE id = ?")->execute([$id_c]);
            $notificacao = "showToast('Excluído', 'O link de convite foi removido.', 'error');";
        }

        // CRIAR NOVO USUÁRIO
        if ($acao === 'criar_usuario') {
            $nome = sanitize($_POST['nome']);
            $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
            $senha = $_POST['senha'];
            $genero = sanitize($_POST['genero'] ?? 'Não declarado');
            $especialidade = sanitize($_POST['especialidade'] ?? '');
            $whatsapp = sanitize($_POST['whatsapp'] ?? '');
            $role_novo = $_POST['role'];
            
            // Verifica se email já existe
            $stmt = $pdo->prepare("SELECT id FROM profissionais WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $notificacao = "showToast('Erro', 'Este e-mail já está cadastrado!', 'error');";
            } else {
                $senhaHash = hashPassword($senha);
                $stmt = $pdo->prepare("INSERT INTO profissionais (nome, email, senha, genero, especialidade, whatsapp, role, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'ativo')");
                $stmt->execute([$nome, $email, $senhaHash, $genero, $especialidade, $whatsapp, $role_novo]);
                $notificacao = "showToast('Sucesso', 'Novo usuário criado com sucesso!', 'success');";
            }
        }
        
        // EDITAR USUÁRIO
        if ($acao === 'editar_usuario') {
            $id_user = $_POST['id_user'];
            $nome = sanitize($_POST['nome']);
            $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
            $genero = sanitize($_POST['genero'] ?? 'Não declarado');
            $especialidade = sanitize($_POST['especialidade'] ?? '');
            $whatsapp = sanitize($_POST['whatsapp'] ?? '');
            $role_novo = $_POST['role'];
            $nova_senha = $_POST['nova_senha'] ?? '';
            
            // Proteção: não pode remover seu próprio superadmin
            if ($id_user == $id_usuario && $role_novo !== ROLE_SUPERADMIN) {
                $notificacao = "showToast('Erro', 'Você não pode remover seu próprio acesso de Super Admin!', 'error');";
            } else {
                try {
                    if (!empty($nova_senha)) {
                        $senhaHash = hashPassword($nova_senha);
                        $stmt = $pdo->prepare("UPDATE profissionais SET nome = ?, email = ?, senha = ?, genero = ?, especialidade = ?, whatsapp = ?, role = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
                        $stmt->execute([$nome, $email, $senhaHash, $genero, $especialidade, $whatsapp, $role_novo, $id_user]);
                    } else {
                        $stmt = $pdo->prepare("UPDATE profissionais SET nome = ?, email = ?, genero = ?, especialidade = ?, whatsapp = ?, role = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
                        $stmt->execute([$nome, $email, $genero, $especialidade, $whatsapp, $role_novo, $id_user]);
                    }
                } catch (PDOException $e) {
                    // Fallback: banco sem coluna updated_at
                    if (!empty($nova_senha)) {
                        $senhaHash = hashPassword($nova_senha);
                        $stmt = $pdo->prepare("UPDATE profissionais SET nome = ?, email = ?, senha = ?, genero = ?, especialidade = ?, whatsapp = ?, role = ? WHERE id = ?");
                        $stmt->execute([$nome, $email, $senhaHash, $genero, $especialidade, $whatsapp, $role_novo, $id_user]);
                    } else {
                        $stmt = $pdo->prepare("UPDATE profissionais SET nome = ?, email = ?, genero = ?, especialidade = ?, whatsapp = ?, role = ? WHERE id = ?");
                        $stmt->execute([$nome, $email, $genero, $especialidade, $whatsapp, $role_novo, $id_user]);
                    }
                }
                $notificacao = "showToast('Atualizado', 'Dados do usuário atualizados!', 'success');";
            }
        }
        
        // DELETAR USUÁRIO
        if ($acao === 'deletar_usuario') {
            $id_user = $_POST['id_user'];
            
            // Proteção: não pode deletar a si mesmo
            if ($id_user == $id_usuario) {
                $notificacao = "showToast('Erro', 'Você não pode deletar sua própria conta!', 'error');";
            } else {
                // Remove sugestões do usuário antes
                $pdo->prepare("DELETE FROM sugestoes WHERE user_id = ?")->execute([$id_user]);
                $pdo->prepare("DELETE FROM profissionais WHERE id = ?")->execute([$id_user]);
                $notificacao = "showToast('Excluído', 'Usuário removido permanentemente do sistema!', 'error');";
            }
        }
        
        // ATIVAR/DESATIVAR USUÁRIO
        if ($acao === 'toggle_status_usuario') {
            $id_user = $_POST['id_user'];
            $novo_status = $_POST['status'];
            
            // Proteção: não pode desativar a si mesmo
            if ($id_user == $id_usuario) {
                $notificacao = "showToast('Erro', 'Você não pode alterar o status da sua própria conta!', 'error');";
            } else {
                try {
                    $pdo->prepare("UPDATE profissionais SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?")->execute([$novo_status, $id_user]);
                } catch (PDOException $e) {
                    // Fallback: banco sem coluna updated_at
                    $pdo->prepare("UPDATE profissionais SET status = ? WHERE id = ?")->execute([$novo_status, $id_user]);
                }
                $acao_msg = $novo_status === 'ativo' ? 'ativado' : 'desativado';
                $notificacao = "showToast('Atualizado', 'Usuário {$acao_msg} com sucesso!', 'success');";
            }
        }

        // APROVAR SOLICITAÇÃO
        // ==========================================
        // CONFIGURAÇÃO DE E-MAIL (IMPORTANTE!)
        // ==========================================
        // Para enviar e-mails no Windows/XAMPP siga os passos:
        // 1. Abra: C:\xampp\php\php.ini
        // 2. Procure por [mail function] e configure:
        //    SMTP = smtp.gmail.com
        //    smtp_port = 587
        //    sendmail_from = seu-email@gmail.com
        // 3. OU use PHPMailer (recomendado):
        //    composer require phpmailer/phpmailer
        // 4. Configure app password no Gmail:
        //    https://myaccount.google.com/apppasswords
        // ==========================================
        if ($acao === 'aprovar_solicitacao') {
            verificarPermissao(ROLE_ADMIN);
            $id_user = $_POST['id_user'];
            
            // Busca dados do usuário
            $stmt = $pdo->prepare("SELECT nome, email, whatsapp FROM profissionais WHERE id = ?");
            $stmt->execute([$id_user]);
            $usuario = $stmt->fetch();
            
            if (!$usuario) {
                $notificacao = "showToast('Erro', 'Usuário não encontrado!', 'error');";
            } else {
                // Senha temporária padrão
                $senha_temp = 'melodias123';
                $senhaHash = hashPassword($senha_temp);
                
                try {
                    $pdo->prepare("UPDATE profissionais SET status = 'ativo', senha = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?")->execute([$senhaHash, $id_user]);
                } catch (PDOException $e) {
                    $pdo->prepare("UPDATE profissionais SET status = 'ativo', senha = ? WHERE id = ?")->execute([$senhaHash, $id_user]);
                }
                
                // Prepara e envia e-mail
                $nome = $usuario['nome'];
                $email = $usuario['email'];
                $whatsapp = $usuario['whatsapp'];
                
                // E-mail HTML
                $assunto = "Bem-vindo(a) ao Melodias - Acesso Liberado!";
                $mensagem_email = "
                <!DOCTYPE html>
                <html>
                <head>
                    <meta charset='UTF-8'>
                    <style>
                        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                        .header { background: linear-gradient(135deg, #1b333d 0%, #6e2b3a 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                        .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
                        .credentials { background: white; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #6e2b3a; }
                        .credential-item { margin: 15px 0; font-size: 16px; }
                        .credential-label { font-weight: bold; color: #1b333d; }
                        .credential-value { color: #6e2b3a; font-size: 18px; font-weight: bold; }
                        .btn { display: inline-block; background: #6e2b3a; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; margin-top: 15px; }
                        .footer { text-align: center; margin-top: 20px; color: #666; font-size: 14px; }
                    </style>
                </head>
                <body>
                    <div class='container'>
                        <div class='header'>
                            <h1>🎵 Melodias</h1>
                            <p>Rede de Profissionais em Saúde Mental</p>
                        </div>
                        <div class='content'>
                            <h2>Olá, $nome!</h2>
                            <p>Sua solicitação de acesso foi <strong>aprovada com sucesso</strong>! 🎉</p>
                            <p>Agora você faz parte da nossa rede de profissionais em saúde mental de Tatuí.</p>
                            
                            <div class='credentials'>
                                <h3 style='color: #6e2b3a; margin-top: 0;'>📋 Seus Dados de Acesso</h3>
                                <div class='credential-item'>
                                    <span class='credential-label'>🔗 Link de Acesso:</span><br>
                                    <a href='http://localhost/karen_site/Site/melodias/login.php' style='color: #6e2b3a;'>http://localhost/karen_site/Site/melodias/login.php</a>
                                </div>
                                <div class='credential-item'>
                                    <span class='credential-label'>📧 E-mail:</span><br>
                                    <span class='credential-value'>$email</span>
                                </div>
                                <div class='credential-item'>
                                    <span class='credential-label'>🔑 Senha Temporária:</span><br>
                                    <span class='credential-value'>melodias123</span>
                                </div>
                            </div>
                            
                            <p><strong>⚠️ Importante:</strong> Sua senha temporária é <code>melodias123</code>. Por segurança, é obrigatório alterar sua senha após o primeiro acesso.</p>
                            
                            <p>No painel você poderá:</p>
                            <ul>
                                <li>Acessar materiais exclusivos da biblioteca</li>
                                <li>Participar do fórum de discussões</li>
                                <li>Enviar sugestões e ideias</li>
                                <li>Conectar-se com outros profissionais</li>
                            </ul>
                            
                            <div style='text-align: center;'>
                                <a href='http://localhost/karen_site/Site/melodias/login.php' class='btn'>Acessar o Sistema</a>
                            </div>
                            
                            <div class='footer'>
                                <p style='background: #fff3cd; padding: 12px; border-radius: 5px; border-left: 4px solid #ffc107; margin-bottom: 15px;'>
                                    <strong>⚠️ Este é um e-mail automático. Por favor, NÃO responda este e-mail.</strong>
                                </p>
                                <p>Se tiver dúvidas ou problemas de acesso, entre em contato com a coordenação através do WhatsApp ou pela página de contato.</p>
                                <p style='margin-top: 20px;'><strong>Melodias</strong> - Conexões em Saúde Mental de Tatuí</p>
                            </div>
                        </div>
                    </div>
                </body>
                </html>
                ";
                
                // ===================================
                // INTEGRAÇÃO COM WHATSAPP
                // ===================================
                
                // Busca configurações
                $config_auto = $pdo->query("SELECT valor FROM configuracoes WHERE chave = 'whatsapp_auto_abrir'")->fetchColumn();
                $config_template = $pdo->query("SELECT valor FROM configuracoes WHERE chave = 'whatsapp_mensagem_template'")->fetchColumn();
                
                // Substitui variáveis no template
                $link_login = 'https://melodias.karengomes.com.br/login'; // Usando URL de produção
                $mensagem_whats = str_replace(
                    ['{NOME}', '{LINK}', '{EMAIL}', '{SENHA}'],
                    [$nome, $link_login, $email, 'melodias123'],
                    $config_template ?: "Olá {$nome}! Bem-vindo ao Melodias. 🎵 Seu acesso foi aprovado!\n\nAcesse: {$link_login}\nE-mail: {$email}\nSenha: melodias123"
                );
                
                // Remove +55 e formatação do WhatsApp (API precisa só números com DDI)
                $whats_numero = preg_replace('/[^0-9]/', '', $whatsapp);
                if (strlen($whats_numero) > 0 && substr($whats_numero, 0, 2) !== '55') {
                    $whats_numero = '55' . $whats_numero; // Adiciona DDI Brasil
                }
                
                // Codifica mensagem para URL
                $mensagem_encoded = rawurlencode($mensagem_whats);
                $whatsapp_url = "https://wa.me/{$whats_numero}?text={$mensagem_encoded}";
                
                if ($config_auto == '1') {
                    // Abre WhatsApp automaticamente
                    $notificacao = "showToast('Aprovado', '✅ Acesso liberado! Abrindo WhatsApp...', 'success'); " .
                                 "setTimeout(() => window.open('{$whatsapp_url}', '_blank'), 1000);";
                } else {
                    // Mostra popup com opção manual
                    $msg_modal = "📧 <b>ACESSO APROVADO!</b><br><br>" .
                                  "👤 {$nome}<br>" .
                                  "📱 WhatsApp: {$whatsapp}<br><br>" .
                                  "━━━━━━━━━━━━━━━━━━━━━━━<br>" .
                                  "🔗 Link: <a href='{$link_login}' target='_blank'>{$link_login}</a><br>" .
                                  "📧 Login: {$email}<br>" .
                                  "🔑 Senha: melodias123<br>" .
                                  "━━━━━━━━━━━━━━━━━━━━━━━<br><br>" .
                                  "💬 <a href='{$whatsapp_url}' target='_blank' class='btn btn-success btn-sm' style='display:inline-block; margin-top:10px;'><i class='fab fa-whatsapp'></i> Enviar via WhatsApp</a>";
                    
                    $notificacao = "showToast('Aprovado', '✅ Acesso liberado!', 'success'); " .
                                 "setTimeout(() => { 
                                     confirmarAcao({
                                         titulo: 'Dados de Acesso',
                                         msg: `" . addslashes($msg_modal) . "`,
                                         icon: 'fa-solid fa-id-card',
                                         iconClass: 'icon-info',
                                         btnText: 'OK, Copiado',
                                         btnClass: 'btn-primary'
                                     });
                                 }, 800);";
                }
            }
        }

        // REJEITAR SOLICITAÇÃO
        if ($acao === 'rejeitar_solicitacao') {
            verificarPermissao(ROLE_ADMIN);
            $id_user = $_POST['id_user'];
            $pdo->prepare("DELETE FROM profissionais WHERE id = ?")->execute([$id_user]);
            $notificacao = "showToast('Rejeitado', 'Solicitação rejeitada e removida do sistema.', 'error');";
        }
    }
}

$pagina = $_GET['page'] ?? 'dashboard';

// Verifica se o banco está atualizado (Auto-migração para Contribuições)
if ($role === ROLE_SUPERADMIN) {
    try {
        // Tenta rodar a migração silenciosamente. Se já existir, o PDO apenas ignorará (ou falhará no try/catch)
        // Isso garante que em produção as colunas sejam criadas no primeiro login do Super Admin.
        @$pdo->exec("ALTER TABLE eventos_presenca_externa ADD contribuicao_obs TEXT");
        @$pdo->exec("ALTER TABLE eventos_presenca ADD contribuicao_obs TEXT");
        @$pdo->exec("ALTER TABLE eventos_contribuicoes ADD contribuicao_obs TEXT");
    } catch (Exception $e) { /* Colunas já existem ou erro silencioso */ }
}
$banco_desatualizado = false;

?><!DOCTYPE html>
<html lang="pt-BR" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#6e2b3a">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Melodias">
    <!-- SEO -->
    <title>Melodias | Sistema de Gestão Premium</title>
    <meta name="description" content="Sistema de Gestão Melodias — plataforma premium para gerenciamento de membros, biblioteca, fórum comunitário e sugestões do grupo.">
    <meta name="keywords" content="Melodias, sistema, gestão, membros, psicologia, música">
    <meta name="robots" content="noindex, nofollow">
    <meta name="author" content="Sistema Melodias">
    <!-- Open Graph -->
    <meta property="og:title" content="Melodias | Sistema de Gestão">
    <meta property="og:description" content="Plataforma de gestão premium do grupo Melodias.">
    <meta property="og:type" content="website">
    <meta property="og:image" content="https://melodias.karengomes.com.br/images/share-banner.png">
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="images/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="images/favicon.png">
    <link rel="icon" type="image/png" sizes="16x16" href="images/favicon.png">
    <link rel="apple-touch-icon" href="images/favicon.png">
    <link rel="shortcut icon" href="images/favicon.ico">
    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* ========================================================
           SISTEMA MELODIAS - DESIGN PREMIUM COMPLETO
        ======================================================== */
        :root {
            --bg-body: #f1f5f9; --bg-card: #ffffff; --bg-sidebar: #0d1b2a;
            --text-main: #1e293b; --text-muted: #64748b; --border: #e2e8f0;
            --primary: #6e2b3a; --primary-hover: #4a1d27; --accent: #1b333d;
            --success: #10b981; --warning: #f59e0b; --danger: #ef4444; --info: #3b82f6;
            --shadow: 0 1px 3px rgba(0,0,0,0.07), 0 2px 6px rgba(0,0,0,0.04);
            --shadow-lg: 0 8px 24px rgba(0,0,0,0.1), 0 2px 8px rgba(0,0,0,0.05);
            --radius: 10px; --radius-lg: 16px;
            --transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        [data-theme="dark"] {
            --bg-body: #0f172a; --bg-card: #1e293b; --bg-sidebar: #020617;
            --text-main: #f1f5f9; --text-muted: #94a3b8; --border: #334155;
            --primary: #9d405a; --primary-hover: #c14e6f;
            --shadow: 0 10px 25px -5px rgba(0,0,0,0.5);
            --shadow-lg: 0 20px 25px -5px rgba(0,0,0,0.7);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        html { overflow-x: hidden; } /* overflow-x NO html, não no body — evita quebrar position:fixed */
        body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: var(--bg-body); color: var(--text-main); transition: var(--transition); line-height: 1.6; }

        /* Animações */
        @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes slideIn { from { transform: translateX(100%); } to { transform: translateX(0); } }
        @keyframes scaleIn { from { opacity: 0; transform: scale(0.9); } to { opacity: 1; transform: scale(1); } }
        .anim-fade { animation: fadeIn 0.5s ease forwards; }


        /* Layout Principal */
        .app-container { display: flex; min-height: 100vh; }
        
        /* ========== SIDEBAR ========== */
        .sidebar {
            width: 270px; background: var(--bg-sidebar); color: #fff;
            display: flex; flex-direction: column; position: fixed;
            top: 0; left: 0; height: 100vh; z-index: 100;
            transition: var(--transition);
            overflow-y: auto; overflow-x: hidden;
            scrollbar-width: thin; scrollbar-color: rgba(255,255,255,0.1) transparent;
        }
        .sidebar::-webkit-scrollbar { width: 4px; }
        .sidebar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 4px; }
        .logo-area { 
            padding: 22px 20px 18px; text-align: center; 
            border-bottom: 1px solid rgba(255,255,255,0.07);
            background: rgba(0,0,0,0.15);
        }
        .logo-area img {
            width: 10vh;
            height: auto;
            filter: brightness(0) invert(1) opacity(0.9);
            transition: var(--transition);
        }
        .logo-area img:hover { opacity: 1; transform: scale(1.03); filter: brightness(0) invert(1); }
        .logo-tagline {
            font-size: 0.68em; color: rgba(255,255,255,0.4);
            letter-spacing: 1.5px; text-transform: uppercase; margin-top: 6px;
            font-weight: 600;
        }
        .nav-links { padding: 14px 0; flex-grow: 1; }
        .nav-section-title {
            padding: 16px 20px 8px; font-size: 0.65em; 
            color: rgba(255,255,255,0.3); text-transform: uppercase; 
            font-weight: 700; letter-spacing: 2px;
        }
        .nav-link { 
            display: flex; align-items: center; padding: 11px 20px; 
            color: rgba(255,255,255,0.55); text-decoration: none; 
            transition: var(--transition); border-left: 3px solid transparent; 
            margin: 1px 8px; border-radius: 0 10px 10px 0; position: relative;
            font-size: 0.9em;
        }
        .nav-link i { margin-right: 12px; font-size: 1em; width: 22px; text-align: center; flex-shrink: 0; }
        .nav-link:hover { color: #fff; background: rgba(255,255,255,0.07); transform: translateX(3px); }
        .nav-link.active { 
            color: #fff; background: rgba(110,43,58,0.4); 
            border-left-color: #c14e6f; font-weight: 600;
            box-shadow: inset 0 0 20px rgba(110,43,58,0.2);
        }
        .nav-link .badge {
            margin-left: auto; font-size: 0.7em;
            animation: pulse-badge 2s infinite;
        }
        @keyframes pulse-badge {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.15); opacity: 0.85; }
        }
        .sidebar-footer { padding: 16px; border-top: 1px solid rgba(255,255,255,0.07); }
        .sidebar-user-mini {
            display: flex; align-items: center; gap: 10px; padding: 10px 14px;
            background: rgba(255,255,255,0.05); border-radius: 12px; margin-bottom: 10px;
        }
        .sidebar-user-mini .mini-av {
            width: 34px; height: 34px; border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), #9d405a);
            display: flex; align-items: center; justify-content: center;
            color: white; font-weight: 700; font-size: 0.8em; flex-shrink: 0;
        }
        .sidebar-user-mini .mini-info { min-width: 0; }
        .sidebar-user-mini .mini-name { font-size: 0.82em; font-weight: 600; color: rgba(255,255,255,0.85); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .sidebar-user-mini .mini-role { font-size: 0.68em; color: rgba(255,255,255,0.4); text-transform: uppercase; letter-spacing: 0.5px; }
        
        /* ========== MAIN CONTENT ========== */
        .main-content { 
            flex-grow: 1; margin-left: 270px; 
            display: flex; flex-direction: column; 
            transition: var(--transition); min-height: 100vh;
        }
        
        /* ========== TOPBAR ========== */
        .topbar { 
            display: flex; justify-content: space-between; align-items: center; 
            padding: 12px 28px; background: var(--bg-card); 
            border-bottom: 1px solid var(--border); position: sticky; 
            top: 0; z-index: 900;
            box-shadow: 0 1px 0 var(--border), 0 4px 20px rgba(0,0,0,0.04);
            backdrop-filter: blur(10px);
        }
        .topbar-left { display: flex; align-items: center; gap: 16px; flex: 1; }
        .search-bar { 
            flex: 1; max-width: 420px; position: relative;
        }
        .search-bar input {
            width: 100%; padding: 9px 15px 9px 40px; border-radius: 30px;
            border: 1.5px solid var(--border); background: var(--bg-body);
            font-size: 0.88em; transition: var(--transition); font-family: inherit;
            color: var(--text-main);
        }
        .search-bar input:focus {
            outline: none; border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(110,43,58,0.1);
            background: var(--bg-card);
        }
        .search-bar i {
            position: absolute; left: 14px; top: 50%;
            transform: translateY(-50%); color: var(--text-muted); font-size: 0.85em;
        }
        .topbar-right { display: flex; gap: 8px; align-items: center; }
        
        .quick-stat {
            display: flex; align-items: center; gap: 6px; padding: 7px 13px;
            background: rgba(110,43,58,0.06); border-radius: 20px;
            font-size: 0.82em; font-weight: 600; color: var(--primary);
            border: 1px solid rgba(110,43,58,0.12);
        }
        .quick-stat i { font-size: 1em; }
        
        .notif-btn {
            position: relative; background: none; border: 1.5px solid var(--border);
            color: var(--text-muted); font-size: 1.1em; cursor: pointer;
            transition: var(--transition); padding: 7px; border-radius: 10px;
            width: 38px; height: 38px; display: flex; align-items: center;
            justify-content: center;
        }
        .notif-btn:hover { border-color: var(--primary); color: var(--primary); background: rgba(110,43,58,0.05); }
        .notif-badge {
            position: absolute; top: -4px; right: -4px;
            background: var(--danger); color: white;
            border-radius: 10px; padding: 2px 5px;
            font-size: 0.6em; font-weight: 700;
            min-width: 16px; text-align: center;
            border: 2px solid var(--bg-card);
        }
        
        /* Dropdown Usuário */
        .user-dropdown { position: relative; }
        .user-trigger {
            display: flex; align-items: center; gap: 10px;
            cursor: pointer; padding: 6px 12px; border-radius: 12px;
            transition: var(--transition); background: var(--bg-body);
            border: 1.5px solid var(--border); min-height: 44px;
        }
        .user-trigger:hover { background: rgba(110,43,58,0.05); border-color: var(--primary); }
        .user-info { text-align: right; }
        .user-info .name { font-weight: 700; font-size: 0.88em; }
        .user-info .role { 
            font-size: 0.68em; color: var(--text-muted); 
            text-transform: uppercase; font-weight: 600; letter-spacing: 0.3px;
        }
        .user-avatar { 
            width: 36px; height: 36px; 
            background: linear-gradient(135deg, var(--primary), var(--accent)); 
            border-radius: 50%; display: flex; align-items: center; 
            justify-content: center; color: white; font-weight: 700; 
            font-size: 1em; box-shadow: 0 2px 8px rgba(110,43,58,0.4);
            flex-shrink: 0;
        }
        .dropdown-menu {
            position: absolute; top: calc(100% + 8px); right: 0;
            background: var(--bg-card); border-radius: 14px;
            box-shadow: 0 15px 40px rgba(0,0,0,0.15), 0 0 0 1px var(--border);
            min-width: 240px; display: none; opacity: 0;
            transform: translateY(-8px) scale(0.97);
            transition: var(--transition); z-index: 1000; overflow: hidden;
        }
        .dropdown-menu.active {
            display: block; opacity: 1; transform: translateY(0) scale(1);
        }
        .dropdown-header { padding: 14px 18px; border-bottom: 1px solid var(--border); background: rgba(110,43,58,0.03); }
        .dropdown-header strong { display: block; color: var(--text-main); margin-bottom: 2px; font-size: 0.88em; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 200px; }
        .dropdown-header small { color: var(--text-muted); font-size: 0.75em; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: block; max-width: 200px; }
        /* Overlay que fecha o dropdown ao clicar fora (mobile) */
        .dropdown-backdrop { display: none; position: fixed; inset: 0; z-index: 850; background: rgba(0,0,0,0.02); }
        .dropdown-item {
            padding: 11px 18px; display: flex; align-items: center;
            gap: 10px; color: var(--text-main); text-decoration: none;
            transition: var(--transition); font-size: 0.88em;
        }
        .dropdown-item:hover { background: rgba(110,43,58,0.05); color: var(--primary); padding-left: 22px; }
        .dropdown-item i { width: 18px; text-align: center; color: var(--text-muted); font-size: 0.9em; }
        .dropdown-divider { height: 1px; background: var(--border); margin: 4px 0; }
        
        .theme-btn, .mobile-toggle { 
            background: none; border: 1.5px solid var(--border); color: var(--text-muted); 
            font-size: 1.1em; cursor: pointer; transition: var(--transition); 
            padding: 7px; border-radius: 10px; width: 38px; height: 38px;
            display: flex; align-items: center; justify-content: center;
        }
        
        /* Estilo para quando o modal está aberto (apenas overflow se necessário) */
        html.modal-active {
            overflow: hidden;
        }
        html.modal-active .sidebar,
        html.modal-active .topbar,
        html.modal-active .bottom-nav {
            z-index: 0 !important;
        }
        
        .theme-btn:hover { border-color: var(--primary); transform: rotate(20deg); color: var(--primary); background: rgba(110,43,58,0.05); }
        .mobile-toggle { display: none; margin-right: 15px; }

        .mobile-toggle:hover { border-color: var(--primary); color: var(--primary); }
        
        /* Overlay Mobile */
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(0, 0, 0, 0.4);
            z-index: 998;
            opacity: 0;
            transition: opacity 0.3s ease;
            backdrop-filter: blur(2px);
        }
        .sidebar-overlay.active {
            display: block;
            opacity: 1;
        }
        
        /* ========== CONTENT AREA ========== */
        .content { padding: 28px 30px; flex-grow: 1; }
        
        /* ========== BOTTOM NAV MOBILE (app-like) ========== */
        .bottom-nav {
            display: none;
            position: fixed; bottom: 0; left: 0; right: 0;
            background: var(--bg-card);
            border-top: 1px solid var(--border);
            z-index: 200;
            padding: 4px 0 max(4px, env(safe-area-inset-bottom));
            box-shadow: 0 -2px 12px rgba(0,0,0,0.08);
        }
        .bottom-nav-inner {
            display: flex; justify-content: space-around; align-items: center;
            max-width: 480px; margin: 0 auto;
        }
        .bnav-item {
            display: flex; flex-direction: column; align-items: center; gap: 2px;
            padding: 5px 8px; border-radius: 10px;
            text-decoration: none; color: var(--text-muted);
            font-size: 0.58em; font-weight: 600; text-transform: uppercase;
            letter-spacing: 0.2px; transition: var(--transition);
            flex: 1; max-width: 72px; min-width: 0;
            position: relative;
        }
        .bnav-item i { font-size: 1.25em; }
        .bnav-item span { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 100%; }
        .bnav-item:hover, .bnav-item.active { color: var(--primary); }
        .bnav-item.active i { transform: translateY(-1px); }
        .bnav-item .bnav-dot {
            position: absolute; top: 3px; right: calc(50% - 16px);
            width: 5px; height: 5px; border-radius: 50%;
            background: var(--danger); border: 1.5px solid var(--bg-card);
        }
        .bnav-fab {
            width: 46px; height: 46px; border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), #9d405a);
            color: white; display: flex; align-items: center; justify-content: center;
            font-size: 1.2em; border: none; cursor: pointer;
            box-shadow: 0 4px 14px rgba(110,43,58,0.4);
            transition: var(--transition); margin-top: -6px; flex-shrink: 0;
            text-decoration: none;
        }
        .bnav-fab:hover { transform: scale(1.08); }
        .page-header { margin-bottom: 30px; }
        .page-header h1 { 
            font-size: 2em; font-weight: 800; color: var(--text-main); 
            margin-bottom: 5px;
        }
        .page-header p { color: var(--text-muted); font-size: 0.95em; }
        .page-header-actions { 
            display: flex; justify-content: space-between; 
            align-items: center; flex-wrap: wrap; gap: 15px; 
        }
        
        /* ========== CARDS & GRIDS ========== */
        .grid-cards { 
            display: grid; 
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); 
            gap: 20px; 
        }
        .card { 
            background: var(--bg-card); border-radius: 12px; 
            padding: 25px; box-shadow: var(--shadow); 
            border: 1px solid var(--border); transition: var(--transition); 
            position: relative; overflow: hidden;
        }
        .card:hover { transform: translateY(-3px); box-shadow: var(--shadow-lg); }
        .card-icon { 
            width: 50px; height: 50px; border-radius: 12px; 
            display: flex; align-items: center; justify-content: center; 
            font-size: 1.5em; margin-bottom: 15px;
        }
        .card-title { font-size: 1.15em; font-weight: 700; margin-bottom: 8px; }
        .card-text { color: var(--text-muted); font-size: 0.9em; line-height: 1.6; margin-bottom: 15px; }
        
        /* ========== TABELA PREMIUM ========== */
        .table-container {
            background: var(--bg-card); border-radius: 12px;
            box-shadow: var(--shadow); border: 1px solid var(--border);
            overflow-x: auto; overflow-y: visible;
        }
        table { width: 100%; border-collapse: collapse; }
        thead th { 
            background: rgba(0,0,0,0.02); padding: 15px 20px; 
            text-align: left; color: var(--text-muted); 
            font-size: 0.8em; text-transform: uppercase; 
            letter-spacing: 0.5px; font-weight: 700; 
            border-bottom: 2px solid var(--border);
        }
        tbody td { 
            padding: 18px 20px; border-bottom: 1px solid var(--border); 
            color: var(--text-main); vertical-align: middle; 
        }
        tbody tr:hover { background: rgba(0,0,0,0.01); }
        tbody tr:last-child td { border-bottom: none; }

        /* ========== COMPONENTES ========== */
        .btn { 
            padding: 10px 20px; border-radius: 8px; font-weight: 600; 
            cursor: pointer; border: none; text-decoration: none; 
            transition: var(--transition); display: inline-flex; 
            align-items: center; gap: 8px; font-size: 0.85em; 
            box-shadow: var(--shadow);
        }
        .btn-primary { background: var(--primary); color: #fff; }
        .btn-primary:hover { background: var(--primary-hover); transform: translateY(-2px); }
        .btn-success { background: var(--success); color: #fff; }
        .btn-success:hover { background: #059669; transform: translateY(-2px); }
        .btn-danger { background: var(--danger); color: #fff; }
        .btn-danger:hover { background: #dc2626; transform: translateY(-2px); }
        .btn-outline { 
            background: transparent; border: 1.5px solid var(--border); 
            color: var(--text-main); box-shadow: none;
        }
        .btn-outline:hover { border-color: var(--primary); color: var(--primary); background: rgba(110, 43, 58, 0.05); }
        .btn-icon { padding: 8px 12px; }
        .btn-sm { padding: 6px 12px; font-size: 0.78em; }
        .btn-lg { padding: 11px 22px; font-size: 0.92em; }
        .btn-block { width: 100%; justify-content: center; }
        
        .input-group { margin-bottom: 18px; }
        .input-group label { 
            display: block; margin-bottom: 8px; color: var(--text-main); 
            font-size: 0.85em; font-weight: 600; 
        }
        .input-control, select, textarea, .premium-input { 
            width: 100%; padding: 12px 15px; border-radius: 10px; 
            border: 1.5px solid var(--border); background: var(--bg-body); 
            color: var(--text-main); transition: var(--transition); 
            font-family: inherit; font-size: 0.9em;
        }
        .input-control:focus, select:focus, textarea:focus, .premium-input:focus { 
            outline: none; border-color: var(--primary); 
            box-shadow: 0 0 0 3px rgba(110, 43, 58, 0.1); 
            background: var(--bg-card);
        }
        textarea { resize: vertical; min-height: 80px; }
        .premium-input {
            background: rgba(110,43,58,0.02);
            border-color: rgba(110,43,58,0.1);
        }
        .premium-input:focus {
            background: #fff;
            border-color: var(--primary);
        }
        
        /* Input Password Wrapper */
        .input-password-wrapper { position: relative; display: flex; align-items: center; }
        .input-password-wrapper .input-control { padding-right: 45px; }
        .btn-toggle-password {
            position: absolute; right: 10px; background: none; border: none;
            color: var(--text-muted); cursor: pointer; padding: 6px;
            display: flex; align-items: center; justify-content: center;
            transition: var(--transition); z-index: 5;
        }
        .btn-toggle-password:hover { color: var(--primary); }
        
        .badge { 
            padding: 5px 12px; border-radius: 20px; 
            font-size: 0.7em; font-weight: 700; 
            text-transform: uppercase; display: inline-block;
        }
        .badge-success { background: rgba(16, 185, 129, 0.15); color: #10b981; }
        .badge-warning { background: rgba(245, 158, 11, 0.15); color: #f59e0b; }
        .badge-danger { background: rgba(239, 68, 68, 0.15); color: #ef4444; }
        .badge-info { background: rgba(59, 130, 246, 0.15); color: #3b82f6; }
        .badge-purple { background: rgba(168, 85, 247, 0.15); color: #a855f7; }

        /* ========== MODAIS AVANÇADOS ========== */
        .modal-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.75); backdrop-filter: blur(8px);
            z-index: 10000; display: none; justify-content: center;
            align-items: center; animation: fadeIn 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            padding: 16px;
        }
        .modal-overlay.active { display: flex; }
        .modal-content {
            background: var(--bg-card); width: 100%; max-width: 600px;
            border-radius: 16px; padding: 0; box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            animation: scaleIn 0.3s; position: relative;
            max-height: calc(100vh - 32px);
            overflow: hidden; display: flex; flex-direction: column;
        }
        .modal-header {
            display: flex; justify-content: space-between;
            align-items: center; padding: 20px 24px;
            border-bottom: 1px solid var(--border); flex-shrink: 0;
        }
        .modal-header h2 {
            font-size: 1.15em; font-weight: 700; color: var(--text-main);
        }
        .close-modal {
            background: none; border: none; font-size: 1.3em;
            color: var(--text-muted); cursor: pointer; transition: var(--transition);
            width: 34px; height: 34px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }
        .close-modal:hover { background: rgba(239, 68, 68, 0.1); color: var(--danger); }
        .modal-body { padding: 24px; overflow-y: auto; flex-grow: 1; -webkit-overflow-scrolling: touch; }
        .modal-footer {
            padding: 16px 24px; border-top: 1px solid var(--border);
            display: flex; gap: 10px; justify-content: flex-end; flex-shrink: 0;
            flex-wrap: wrap;
        }
        .form-grid {
            display: grid; grid-template-columns: 1fr 1fr; gap: 15px;
        }
        @media (max-width: 600px) {
            .form-grid { grid-template-columns: 1fr; }
        }

        /* ========== TOASTS ========== */
        #toast-container { 
            position: fixed; bottom: 25px; right: 25px; 
            z-index: 9999; display: flex; flex-direction: column; 
            gap: 12px; max-width: 400px;
        }
        .toast { 
            background: var(--bg-card); border-left: 5px solid var(--primary); 
            padding: 18px 20px; border-radius: 10px; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.2); 
            display: flex; align-items: center; gap: 15px; 
            animation: slideIn 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        .toast.success { border-color: var(--success); }
        .toast.error { border-color: var(--danger); }
        .toast.warning { border-color: var(--warning); }
        .toast-icon { font-size: 1.5em; }
        .toast-content h4 { 
            color: var(--text-main); margin: 0 0 4px 0; 
            font-size: 0.95em; font-weight: 700; 
        }
        .toast-content p { 
            margin: 0; color: var(--text-muted); font-size: 0.85em; 
        }

        /* ========== CONFIRM DIALOG ========== */
        .confirm-dialog {
            background: var(--bg-card); padding: 30px; border-radius: 16px;
            max-width: 450px; text-align: center; box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        .confirm-dialog .icon-warning { font-size: 4em; color: var(--warning); margin-bottom: 20px; }
        .confirm-dialog .icon-success { font-size: 4em; color: var(--success); margin-bottom: 20px; }
        .confirm-dialog .icon-danger { font-size: 4em; color: var(--danger); margin-bottom: 20px; }
        .confirm-dialog .icon-info { font-size: 4em; color: var(--info); margin-bottom: 20px; }
        .confirm-dialog h3 {
            font-size: 1.5em; font-weight: 800; margin-bottom: 10px;
        }
        .confirm-dialog p {
            color: var(--text-muted); margin-bottom: 25px;
        }
        .confirm-dialog .btn-group {
            display: flex; gap: 10px; justify-content: center;
        }

        /* ========== FÓRUM ========== */
        .grid-forum {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
        }
        .forum-card {
            background: var(--bg-card); border-radius: 12px;
            padding: 20px; box-shadow: var(--shadow);
            border: 1px solid var(--border); transition: var(--transition);
            cursor: pointer; position: relative;
        }
        .forum-card:hover {
            transform: translateY(-3px); box-shadow: var(--shadow-lg);
            border-color: var(--primary);
        }
        .forum-card-header {
            display: flex; justify-content: space-between;
            align-items: center; margin-bottom: 12px;
        }
        .forum-cat-badge {
            background: rgba(110, 43, 58, 0.1);
            color: var(--primary); padding: 5px 12px;
            border-radius: 20px; font-size: 0.75em;
            font-weight: 700; text-transform: uppercase;
        }
        .forum-date {
            font-size: 0.75em; color: var(--text-muted);
        }
        .forum-title {
            font-size: 1.15em; font-weight: 700;
            margin-bottom: 12px; color: var(--text-main);
        }
        .forum-title a {
            text-decoration: none; color: inherit;
            transition: var(--transition);
        }
        .forum-title a:hover {
            color: var(--primary);
        }
        .forum-excerpt {
            color: var(--text-muted); font-size: 0.9em;
            line-height: 1.6; margin-bottom: 15px;
        }
        .forum-card-footer {
            display: flex; justify-content: space-between;
            align-items: center; padding-top: 15px;
            border-top: 1px solid var(--border);
        }
        .forum-author {
            display: flex; align-items: center; gap: 10px;
            font-size: 0.85em; font-weight: 600;
        }
        .mini-avatar {
            width: 32px; height: 32px;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            border-radius: 50%; display: flex;
            align-items: center; justify-content: center;
            color: white; font-weight: 700; font-size: 0.9em;
        }
        .forum-stats {
            display: flex; gap: 15px;
            font-size: 0.85em; color: var(--text-muted);
        }
        .forum-stats span {
            display: flex; align-items: center; gap: 5px;
        }
        .filter-tabs {
            display: flex; gap: 10px; flex-wrap: wrap;
        }
        .filter-tab {
            padding: 10px 18px; border-radius: 25px;
            background: var(--bg-card); border: 1.5px solid var(--border);
            color: var(--text-main); text-decoration: none;
            font-size: 0.85em; font-weight: 600;
            transition: var(--transition);
        }
        .filter-tab:hover {
            border-color: var(--primary);
            background: rgba(110, 43, 58, 0.05);
        }
        .filter-tab.active {
            background: var(--primary); color: white;
            border-color: var(--primary);
        }
        .empty-state {
            grid-column: 1 / -1;
            text-align: center; padding: 60px 20px;
            color: var(--text-muted);
        }

        /* ----- File Upload Personalizado ----- */
        .file-upload-wrapper {
            position: relative;
            margin-top: 5px;
        }
        .file-upload-box {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 24px;
            border: 2px dashed var(--border);
            border-radius: 12px;
            background: rgba(0,0,0,0.015);
            cursor: pointer;
            transition: var(--transition);
            text-align: center;
            color: var(--text-muted);
        }
        .file-upload-box:hover {
            border-color: var(--primary);
            background: rgba(110, 43, 58, 0.04);
            color: var(--primary);
        }
        .file-upload-box input[type="file"] {
            display: none;
        }
        .file-upload-box i {
            font-size: 2.2em;
            margin-bottom: 12px;
            color: var(--primary);
            opacity: 0.8;
            transition: var(--transition);
        }
        .file-upload-box:hover i {
            transform: translateY(-3px);
            opacity: 1;
        }
        .file-upload-box span.file-name {
            font-size: 0.9em;
            font-weight: 600;
            color: var(--text-main);
            margin-bottom: 4px;
            word-break: break-all;
        }
        .file-upload-box span.file-hint {
            font-size: 0.8em;
            opacity: 0.7;
        }

        /* ========== RESPONSIVO TABLET (768px–1199px) ========== */
        @media (max-width: 1199px) and (min-width: 769px) {
            .sidebar { width: 220px; }
            .main-content { margin-left: 220px; }
            .logo-area img { width: 110px; }
            .nav-link { font-size: 0.85em; padding: 10px 16px; }
            .content { padding: 20px; }
            .topbar { padding: 10px 20px; }
        }

        /* ========== RESPONSIVO TABLET (1024px) ========== */
        @media (max-width: 1024px) {
            .mobile-toggle { display: flex; }
            .sidebar { transform: translateX(-100%); width: 280px; z-index: 1100; }
            .sidebar.show { transform: translateX(0); }
            .main-content { margin-left: 0; }
            .topbar { left: 0; width: 100%; position: sticky; top: 0; z-index: 900; }

            /* Page Header */
            .page-header { flex-direction: column; align-items: flex-start; gap: 12px; }
            .page-header h1 { font-size: 1.5em; }
            .page-header-actions { width: 100%; display: flex; flex-wrap: wrap; gap: 8px; }
            .page-header-actions .btn { flex: 1; min-width: 140px; justify-content: center; font-size: 0.85em; }

            /* Grid */
            .dash-row { grid-template-columns: 1fr !important; }
            .dash-kpis { grid-template-columns: 1fr 1fr !important; gap: 12px !important; }

            /* Dropdown tablet */
            .user-dropdown .dropdown-menu {
                position: fixed; top: 68px; right: 15px;
                width: 260px; max-width: calc(100vw - 30px);
                border-radius: 14px; box-shadow: 0 12px 40px rgba(0,0,0,0.18);
                z-index: 1200;
                display: none;
            }
            .user-dropdown .dropdown-menu.active { display: block; }
        }

        /* ========== RESPONSIVO MOBILE (<768px) ========== */
        @media (max-width: 768px) {
            /* Sidebar drawer */
            .sidebar {
                transform: translateX(-100%);
                box-shadow: none;
                width: 280px;
                z-index: 999;
            }
            .sidebar.show {
                transform: translateX(0);
                box-shadow: 20px 0 60px rgba(0,0,0,0.5);
            }
            .main-content { margin-left: 0; width: 100%; }
            .mobile-toggle { display: flex !important; }

            /* Bottom nav */
            .bottom-nav { display: block; }
            .content { padding: 14px 12px; padding-bottom: 90px !important; }

            /* Topbar */
            .topbar { padding: 10px 14px; gap: 8px; }
            .topbar-left { display: none; }
            .topbar-right { flex: 1; justify-content: flex-end; gap: 6px; }
            .quick-stat { display: none; }
            .user-info { display: none; }
            .notif-btn, .theme-btn { width: 36px; height: 36px; font-size: 1em; }
            .user-trigger { border: none; padding: 4px; background: transparent; }
            .user-avatar { width: 34px; height: 34px; font-size: 0.9em; }

            /* Dropdown usuário no mobile */
            .user-dropdown .dropdown-menu {
                position: fixed; top: 62px; right: 12px;
                width: calc(100vw - 24px); max-width: 300px;
                border-radius: 14px;
                box-shadow: 0 12px 40px rgba(0,0,0,0.18), 0 0 0 1px var(--border);
                z-index: 1000;
            }
            .dropdown-header strong,
            .dropdown-header small { max-width: calc(100vw - 80px); }

            /* Grids */
            .grid-cards { grid-template-columns: 1fr; gap: 10px; }
            .grid-forum  { grid-template-columns: 1fr; gap: 10px; }
            .forum-grid-v2 { grid-template-columns: 1fr !important; }
            .forum-stats-bar { grid-template-columns: 1fr 1fr !important; }

            /* Cards */
            .card { padding: 18px; }
            .page-header h1 { font-size: 1.3em; }

            /* Tabela responsiva */
            .table-container { border-radius: 8px; }
            tbody td { padding: 12px 14px; font-size: 0.88em; }
            thead th { padding: 12px 14px; }

            /* Perfil */
            .profile-sticky-footer { padding: 16px; margin-top: 24px; }

            /* Modais mobile */
            .modal-overlay { padding: 10px; align-items: flex-end; }
            .modal-content {
                max-width: 100%; border-radius: 18px 18px 12px 12px;
                max-height: 92vh;
            }
            .modal-header { padding: 16px 18px; }
            .modal-header h2 { font-size: 1.05em; }
            .modal-body { padding: 16px 18px; }
            .modal-footer { padding: 12px 18px; gap: 8px; }
            .modal-footer .btn { flex: 1; justify-content: center; }

            /* Toast mobile */
            #toast-container { bottom: 80px; right: 12px; left: 12px; max-width: 100%; }
        }

        @media (max-width: 480px) {
            .dash-kpis { grid-template-columns: 1fr !important; }
            .forum-stats-bar { grid-template-columns: 1fr !important; }
            .content { padding: 10px 8px; padding-bottom: 90px !important; }
            .topbar { padding: 8px 10px; }
            .card { padding: 14px; }
            .btn { padding: 8px 14px; font-size: 0.82em; }
            .btn-lg { padding: 10px 16px !important; font-size: 0.88em !important; }

            /* Modais telas pequenas */
            .modal-overlay { padding: 0; align-items: flex-end; }
            .modal-content {
                border-radius: 20px 20px 0 0;
                max-height: 95vh;
            }
            .modal-header h2 { font-size: 0.98em; }
            .modal-body { padding: 14px 16px; }
            .modal-footer { flex-direction: column; }
        }
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: rgba(0,0,0,0.05); border-radius: 10px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(110,43,58,0.2); border-radius: 10px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: rgba(110,43,58,0.4); }
    </style>
</head>
<body>
 

    <!-- Overlay Mobile para fechar sidebar -->
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>
    <!-- Backdrop para fechar dropdown do usuário -->
    <div class="dropdown-backdrop" id="dropdown-backdrop" onclick="closeUserDropdown()"></div>

    <div class="app-container">
        
        <!-- ========== SIDEBAR ========== -->
        <nav class="sidebar" id="sidebar">
            <!-- Logo Real -->
            <div class="logo-area">
                <a href="?page=dashboard">
                    <img src="images/logo-melodias.png" alt="Melodias" title="Melodias — Sistema de Gestão">
                </a>
            </div>

            <div class="nav-links">
                <a href="?page=dashboard" class="nav-link <?php if($pagina=='dashboard') echo 'active';?>">
                    <i class="fa-solid fa-gauge-high"></i> Dashboard
                </a>
                <a href="?page=biblioteca" class="nav-link <?php if($pagina=='biblioteca') echo 'active';?>">
                    <i class="fa-solid fa-graduation-cap"></i> Aprendizados
                </a>
                <a href="?page=materiais_apoio" class="nav-link <?php if($pagina=='materiais_apoio') echo 'active';?>">
                    <i class="fa-solid fa-file-pdf"></i> Materiais de Apoio
                </a>
                <a href="?page=forum" class="nav-link <?php if($pagina=='forum') echo 'active';?>">
                    <i class="fa-solid fa-comments"></i> Fórum
                </a>
                
                <?php if($role === ROLE_ADMIN || $role === ROLE_SUPERADMIN || $role === ROLE_EDITOR): ?>
                    <div class="nav-section-title">Administração</div>
                    <a href="?page=materiais" class="nav-link <?php if($pagina=='materiais') echo 'active';?>">
                        <i class="fa-solid fa-file-lines"></i> Materiais
                    </a>
                    <a href="?page=sugestoes" class="nav-link <?php if($pagina=='sugestoes') echo 'active';?>">
                        <i class="fa-solid fa-lightbulb"></i> Sugestões
                    </a>
                    <?php
                    $pendentes_count = 0;
                    if($role !== ROLE_EDITOR) {
                        try { $pendentes_count = $pdo->query("SELECT COUNT(*) FROM profissionais WHERE status = 'pendente'")->fetchColumn(); } catch(Exception $e) {}
                    }
                    ?>
                    <?php if($role !== ROLE_EDITOR): ?>
                    <a href="?page=solicitacoes" class="nav-link <?php if($pagina=='solicitacoes') echo 'active';?>">
                        <i class="fa-solid fa-user-clock"></i> Solicitações
                        <?php if($pendentes_count > 0): ?>
                            <span class="badge" style="background:var(--danger);color:white;padding:2px 8px;border-radius:10px;font-size:0.72em;margin-left:auto;"><?php echo $pendentes_count; ?></span>
                        <?php endif; ?>
                    </a>
                    <?php endif; ?>

                    <?php if($role !== ROLE_EDITOR): ?>
                    <a href="?page=configuracoes" class="nav-link <?php if($pagina=='configuracoes') echo 'active';?>">
                        <i class="fa-solid fa-gear"></i> Configurações
                    </a>
                    <?php endif; ?>
                <?php endif; ?>

                <div class="nav-section-title">Comunidade</div>
                <a href="?page=membros" class="nav-link <?php if($pagina=='membros') echo 'active';?>">
                    <i class="fa-solid fa-address-book"></i> Diretório de Membros
                </a>
                <a href="?page=eventos" class="nav-link <?php if($pagina=='eventos') echo 'active';?>">
                    <i class="fa-solid fa-calendar-check"></i> Encontros & Eventos
                </a>
                
                <?php if($role === ROLE_SUPERADMIN): ?>
                    <div class="nav-section-title">Super Admin</div>
                    <a href="?page=usuarios" class="nav-link <?php if($pagina=='usuarios') echo 'active';?>">
                        <i class="fa-solid fa-users-gear"></i> Usuários
                    </a>
                <?php endif; ?>
            </div>

            <div class="sidebar-footer">
                <!-- Mini user card -->
                <div class="sidebar-user-mini">
                    <div class="mini-av"><?php echo strtoupper(substr($primeiro_nome, 0, 1)); ?></div>
                    <div class="mini-info">
                        <div class="mini-name"><?php echo htmlspecialchars($primeiro_nome); ?></div>
                        <div class="mini-role"><?php echo $role === ROLE_SUPERADMIN ? '👑 Super Admin' : ($role === ROLE_ADMIN ? '🛡️ Admin' : '👤 Membro'); ?></div>
                    </div>
                </div>
                <a href="?sair=1" class="btn btn-outline btn-block" style="color:rgba(255,255,255,0.7);border-color:rgba(255,255,255,0.15);font-size:0.85em;">
                    <i class="fa-solid fa-right-from-bracket"></i> Sair
                </a>
            </div>
        </nav>

        <!-- ========== MAIN CONTENT ========== -->
        <main class="main-content">
            <!-- ========== TOPBAR MELHORADA ========== -->
            <header class="topbar">
                <button class="mobile-toggle" onclick="toggleSidebar()">
                    <i class="fa-solid fa-bars"></i>
                </button>
                
                <div class="topbar-left">
                    <div class="search-bar">
                        <i class="fa-solid fa-search"></i>
                        <input type="text" placeholder="Pesquisar no sistema..." id="global-search">
                    </div>
                </div>
                
                <div class="topbar-right">
                    <?php if ($role === ROLE_ADMIN || $role === ROLE_SUPERADMIN || $role === ROLE_EDITOR): 
                        // Stats para Admin/SuperAdmin/Editor
                        $stats_users = $pdo->query("SELECT COUNT(*) FROM profissionais WHERE status = 'ativo'")->fetchColumn();
                        $stats_sugestoes = $pdo->query("SELECT COUNT(*) FROM sugestoes WHERE status = 'pendente'")->fetchColumn();
                    ?>
                        <div class="quick-stat" title="Usuários Ativos">
                            <i class="fa-solid fa-users"></i>
                            <span><?php echo $stats_users; ?></span>
                        </div>
                        <div class="quick-stat" title="Sugestões Pendentes">
                            <i class="fa-solid fa-lightbulb"></i>
                            <span><?php echo $stats_sugestoes; ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <button class="notif-btn" onclick="openModal('modalNotificacoes')" title="Notificações">
                        <i class="fa-solid fa-bell"></i>
                        <?php if ($role >= ROLE_ADMIN && $stats_sugestoes > 0): ?>
                            <span class="notif-badge"><?php echo $stats_sugestoes; ?></span>
                        <?php endif; ?>
                    </button>
                    
                    <button class="theme-btn" onclick="toggleTheme()" title="Alternar Tema">
                        <i class="fa-solid fa-moon"></i>
                    </button>
                    
                    <div class="user-dropdown">
                        <div class="user-trigger" onclick="toggleUserDropdown(event)">
                            <div class="user-info">
                                <div class="name"><?php echo $primeiro_nome; ?></div>
                                <div class="role"><?php 
                                    echo $role === ROLE_SUPERADMIN ? '👑 Super Admin' : 
                                         ($role === ROLE_ADMIN ? '🛡️ Admin' : '👤 Membro'); 
                                ?></div>
                            </div>
                            <div class="user-avatar"><?php echo strtoupper(substr($primeiro_nome, 0, 1)); ?></div>
                        </div>
                        
                        <div class="dropdown-menu" id="user-dropdown">
                            <div class="dropdown-header">
                                <strong><?php echo htmlspecialchars($primeiro_nome); ?></strong>
                                <small><?php echo htmlspecialchars($user['email']); ?></small>
                            </div>
                            <a href="?page=perfil" class="dropdown-item">
                                <i class="fa-solid fa-user-circle"></i>
                                Meu Perfil
                            </a>
                            <a href="#" onclick="openModal('modalTrocarSenha'); closeUserDropdown(); return false;" class="dropdown-item">
                                <i class="fa-solid fa-key"></i>
                                Trocar Senha
                            </a>
                            <?php if ($role === ROLE_ADMIN || $role === ROLE_SUPERADMIN): ?>
                                <a href="?page=configuracoes" class="dropdown-item">
                                    <i class="fa-solid fa-gear"></i>
                                    Configurações
                                </a>
                            <?php endif; ?>
                            <div class="dropdown-divider"></div>
                            <a href="?sair=1" class="dropdown-item" style="color: var(--danger)">
                                <i class="fa-solid fa-right-from-bracket"></i>
                                Sair
                            </a>
                        </div>
                    </div>
                </div>
            </header>

            <!-- ========== CONTENT AREA ========== -->
            <div class="content anim-fade">
                
                <?php if ($banco_desatualizado && ($role === ROLE_ADMIN || $role === ROLE_SUPERADMIN)): ?>
                    <div style="background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%); color: #78350f; padding: 20px; border-radius: 12px; margin-bottom: 30px; border-left: 5px solid #b45309; box-shadow: 0 4px 15px rgba(245, 158, 11, 0.3);">
                        <div style="display: flex; align-items: center; gap: 15px;">
                            <i class="fa-solid fa-triangle-exclamation" style="font-size: 2em;"></i>
                            <div style="flex: 1;">
                                <h3 style="margin: 0 0 8px 0; font-size: 1.2em;">⚠️ Banco de Dados Desatualizado</h3>
                                <p style="margin: 0 0 12px 0; line-height: 1.6;">
                                    Seu banco de dados precisa ser atualizado para funcionar corretamente com todas as funcionalidades do sistema. 
                                    Algumas páginas podem apresentar erros até que você execute o script de atualização.
                                </p>
                                <a href="setup_banco.php" style="background: #78350f; color: white; padding: 10px 20px; border-radius: 8px; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; font-weight: 600; transition: background 0.3s;">
                                    <i class="fa-solid fa-wrench"></i>
                                    Atualizar Banco de Dados Agora
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

<?php

// ### PÁGINA: DASHBOARD ###
if ($pagina === 'dashboard'):
    // Coleta estatísticas para o dashboard
    $dash_total_users   = 0;
    $dash_ativos        = 0;
    $dash_pendentes_d   = 0;
    $dash_materiais_d   = 0;
    $dash_forum_d       = 0;
    $dash_sugestoes_d   = 0;
    try {
        $dash_total_users = $pdo->query("SELECT COUNT(*) FROM profissionais")->fetchColumn();
        $dash_ativos      = $pdo->query("SELECT COUNT(*) FROM profissionais WHERE status='ativo'")->fetchColumn();
        $dash_pendentes_d = $pdo->query("SELECT COUNT(*) FROM profissionais WHERE status='pendente'")->fetchColumn();
        $dash_materiais_d = $pdo->query("SELECT COUNT(*) FROM materiais")->fetchColumn();
        $dash_forum_d     = $pdo->query("SELECT COUNT(*) FROM forum_posts WHERE status='ativo'")->fetchColumn();
        $dash_sugestoes_d = $pdo->query("SELECT COUNT(*) FROM sugestoes WHERE status='nova'")->fetchColumn();
    } catch(Exception $e) {}
    
    // Atividade recente (últimos membros aprovados)
    $recent_members = [];
    try {
        $recent_members = $pdo->query("SELECT nome, email, role, created_at FROM profissionais WHERE status='ativo' ORDER BY id DESC LIMIT 5")->fetchAll();
    } catch(Exception $e) {}
    
    // Posts recentes do fórum
    $recent_posts = [];
    try {
        $recent_posts = $pdo->query("SELECT fp.titulo, fp.categoria, fp.created_at, p.nome as autor FROM forum_posts fp LEFT JOIN profissionais p ON fp.user_id = p.id WHERE fp.status='ativo' ORDER BY fp.id DESC LIMIT 4")->fetchAll();
    } catch(Exception $e) {}
?>

<!-- ===== DASHBOARD PREMIUM ===== -->
<style>
.dash-hero {
    background: linear-gradient(135deg, var(--accent) 0%, var(--primary) 100%);
    border-radius: 20px;
    padding: 35px 40px;
    margin-bottom: 30px;
    color: white;
    position: relative;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    box-shadow: 0 15px 40px rgba(110,43,58,0.3);
}
.dash-hero::before {
    content: '';
    position: absolute;
    width: 300px; height: 300px;
    border-radius: 50%;
    background: rgba(255,255,255,0.05);
    top: -100px; right: -80px;
}
.dash-hero::after {
    content: '';
    position: absolute;
    width: 200px; height: 200px;
    border-radius: 50%;
    background: rgba(255,255,255,0.04);
    bottom: -80px; right: 150px;
}
.dash-hero-text h1 {
    font-size: 2.2em;
    font-weight: 800;
    margin-bottom: 8px;
    text-shadow: 0 2px 10px rgba(0,0,0,0.2);
}
.dash-hero-text p {
    opacity: 0.85;
    font-size: 1em;
    max-width: 500px;
    line-height: 1.7;
}
.dash-hero-time {
    text-align: right;
    flex-shrink: 0;
}
.dash-hero-time .clock {
    font-size: 2.5em;
    font-weight: 800;
    letter-spacing: 2px;
    text-shadow: 0 2px 10px rgba(0,0,0,0.2);
}
.dash-hero-time .date-str {
    opacity: 0.8;
    font-size: 0.9em;
    margin-top: 4px;
}

/* KPI Cards */
.dash-kpis {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
    gap: 16px;
    margin-bottom: 30px;
}
.kpi-card {
    background: var(--bg-card);
    border-radius: 16px;
    padding: 22px 20px;
    box-shadow: var(--shadow);
    border: 1px solid var(--border);
    position: relative;
    overflow: hidden;
    transition: var(--transition);
    cursor: default;
}
.kpi-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-lg);
}
.kpi-card::before {
    content: '';
    position: absolute;
    top: 0; right: 0;
    width: 80px; height: 80px;
    border-radius: 0 16px 0 100%;
    opacity: 0.08;
}
.kpi-card.kpi-blue::before  { background: var(--info); }
.kpi-card.kpi-green::before { background: var(--success); }
.kpi-card.kpi-red::before   { background: var(--danger); }
.kpi-card.kpi-purple::before{ background: #a855f7; }
.kpi-card.kpi-yellow::before{ background: var(--warning); }
.kpi-card.kpi-main::before  { background: var(--primary); }

.kpi-icon {
    width: 44px; height: 44px;
    border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.3em;
    margin-bottom: 14px;
}
.kpi-card.kpi-blue  .kpi-icon { background: rgba(59,130,246,0.12);  color: var(--info); }
.kpi-card.kpi-green .kpi-icon { background: rgba(16,185,129,0.12);  color: var(--success); }
.kpi-card.kpi-red   .kpi-icon { background: rgba(239,68,68,0.12);   color: var(--danger); }
.kpi-card.kpi-purple.kpi-icon { background: rgba(168,85,247,0.12);  color: #a855f7; }
.kpi-card.kpi-yellow .kpi-icon{ background: rgba(245,158,11,0.12);  color: var(--warning); }
.kpi-card.kpi-main  .kpi-icon { background: rgba(110,43,58,0.12);   color: var(--primary); }
.kpi-card.kpi-purple .kpi-icon{ background: rgba(168,85,247,0.12);  color: #a855f7; }

.kpi-value {
    font-size: 2.2em;
    font-weight: 800;
    line-height: 1;
    margin-bottom: 4px;
    color: var(--text-main);
}
.kpi-label {
    font-size: 0.78em;
    color: var(--text-muted);
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Dashboard grid rows */
.dash-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 24px;
    margin-bottom: 24px;
}
.dash-row.row-3 {
    grid-template-columns: 2fr 1fr;
}
@media (max-width: 900px) {
    .dash-row, .dash-row.row-3 { grid-template-columns: 1fr; }
    .dash-hero { flex-direction: column; text-align: center; padding: 24px 20px; }
    .dash-hero-time { text-align: center; }
    .dash-hero-text h1 { font-size: 1.5em; }
    .dash-hero-time .clock { font-size: 1.8em; }
    .dash-kpis { grid-template-columns: repeat(2, 1fr); }
    .kpi-value { font-size: 1.7em; }
    .kpi-card { padding: 16px; }
}

.dash-section-title {
    font-size: 1.05em;
    font-weight: 700;
    color: var(--text-main);
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    gap: 10px;
}
.dash-section-title i {
    color: var(--primary);
}

/* Quick actions */
.quick-actions {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
}
.quick-action-btn {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 16px;
    border-radius: 12px;
    text-decoration: none;
    font-weight: 600;
    font-size: 0.85em;
    transition: var(--transition);
    border: 1.5px solid var(--border);
    color: var(--text-main);
    background: var(--bg-body);
    cursor: pointer;
    width: 100%;
    text-align: left;
    font-family: inherit;
}
.quick-action-btn:hover {
    border-color: var(--primary);
    color: var(--primary);
    background: rgba(110,43,58,0.04);
    transform: translateX(4px);
}
.quick-action-btn i {
    width: 32px; height: 32px;
    background: rgba(110,43,58,0.1);
    color: var(--primary);
    border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1em;
    flex-shrink: 0;
}

/* Members list */
.member-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 0;
    border-bottom: 1px solid var(--border);
}
.member-item:last-child { border-bottom: none; }
.member-avatar-sm {
    width: 36px; height: 36px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--primary), var(--accent));
    color: white;
    display: flex; align-items: center; justify-content: center;
    font-weight: 700;
    font-size: 0.9em;
    flex-shrink: 0;
}
.member-details { flex: 1; min-width: 0; }
.member-name { font-weight: 600; font-size: 0.9em; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.member-email { font-size: 0.76em; color: var(--text-muted); }

/* Activity timeline */
.activity-item {
    display: flex;
    gap: 14px;
    padding: 12px 0;
    border-bottom: 1px dashed var(--border);
}
.activity-item:last-child { border-bottom: none; }
.activity-dot {
    width: 10px; height: 10px;
    border-radius: 50%;
    background: var(--primary);
    margin-top: 5px;
    flex-shrink: 0;
}
.activity-dot.green { background: var(--success); }
.activity-dot.blue  { background: var(--info); }
.activity-dot.yellow{ background: var(--warning); }
.activity-text { font-size: 0.88em; color: var(--text-main); }
.activity-time { font-size: 0.75em; color: var(--text-muted); margin-top: 2px; }

/* Sugestão form melhorado */
.sugestao-form-wrapper textarea {
    min-height: 90px;
    resize: none;
}

/* Barra de progresso */
.progress-bar-wrap {
    background: var(--border);
    border-radius: 10px;
    height: 8px;
    margin-top: 6px;
    overflow: hidden;
}
.progress-bar-fill {
    height: 100%;
    border-radius: 10px;
    background: linear-gradient(90deg, var(--primary), var(--accent));
    transition: width 1s cubic-bezier(0.4,0,0.2,1);
    width: 0;
}
.whatsapp-banner {
    background: linear-gradient(135deg, #128C7E 0%, #25D366 100%);
    border-radius: 20px;
    padding: 30px;
    margin-bottom: 30px;
    color: white;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 30px;
    box-shadow: 0 15px 35px rgba(37, 211, 102, 0.2);
    border: 1px solid rgba(255,255,255,0.1);
    position: relative;
    overflow: hidden;
}
.whatsapp-banner::before {
    content: '';
    position: absolute;
    width: 250px;
    height: 250px;
    background: rgba(255,255,255,0.08);
    border-radius: 50%;
    top: -100px;
    right: -50px;
}
.whatsapp-banner-content {
    display: flex;
    align-items: center;
    gap: 25px;
    z-index: 1;
}
.whatsapp-banner-icon {
    width: 64px;
    height: 64px;
    background: rgba(255,255,255,0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.2em;
    flex-shrink: 0;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}
.whatsapp-banner-text h2 {
    font-size: 1.6em;
    font-weight: 800;
    margin-bottom: 5px;
}
.whatsapp-banner-text p {
    font-size: 1em;
    opacity: 0.95;
    line-height: 1.6;
}
.whatsapp-banner-btn {
    background: white !important;
    color: #128C7E !important;
    font-weight: 800 !important;
    padding: 14px 30px !important;
    border-radius: 50px !important;
    font-size: 1em !important;
    box-shadow: 0 8px 20px rgba(0,0,0,0.15) !important;
    white-space: nowrap;
    z-index: 1;
    transition: all 0.3s ease !important;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 8px;
}
.whatsapp-banner-btn:hover {
    transform: translateY(-3px) scale(1.03) !important;
    box-shadow: 0 12px 25px rgba(0,0,0,0.2) !important;
    background: #f8fafc !important;
}

@media (max-width: 768px) {
    .whatsapp-banner {
        flex-direction: column;
        text-align: center;
        padding: 25px 20px;
        gap: 25px;
    }
    .whatsapp-banner-content {
        flex-direction: column;
        gap: 15px;
    }
    .whatsapp-banner-btn {
        width: 100%;
        justify-content: center;
    }
}
</style>

<!-- HERO -->
<div class="dash-hero">
    <div class="dash-hero-text">
        <h1>👋 Olá, <?php echo $primeiro_nome; ?>!</h1>
        <p>Bem-vindo de volta ao Sistema Melodias. Aqui está um resumo da plataforma hoje.</p>
        <?php if($role === ROLE_SUPERADMIN): ?>
            <span style="display:inline-flex;align-items:center;gap:8px;background:rgba(255,255,255,0.15);padding:6px 14px;border-radius:20px;font-size:0.82em;margin-top:12px;">
                <i class="fa-solid fa-crown"></i> Super Administrador · Acesso Total
            </span>
        <?php elseif($role === ROLE_ADMIN): ?>
            <span style="display:inline-flex;align-items:center;gap:8px;background:rgba(255,255,255,0.15);padding:6px 14px;border-radius:20px;font-size:0.82em;margin-top:12px;">
                <i class="fa-solid fa-shield"></i> Administrador
            </span>
        <?php else: ?>
            <span style="display:inline-flex;align-items:center;gap:8px;background:rgba(255,255,255,0.15);padding:6px 14px;border-radius:20px;font-size:0.82em;margin-top:12px;">
                <i class="fa-solid fa-user"></i> Membro da Rede
            </span>
        <?php endif; ?>

        <?php if(empty($user['foto']) || empty($user['bio'])): ?>
            <div style="margin-top: 15px; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); padding: 12px 18px; border-radius: 12px; display: flex; align-items: center; gap: 12px;">
                <i class="fa-solid fa-circle-info" style="font-size: 1.2em; color: #fbbf24;"></i>
                <div style="font-size: 0.85em;">
                    <strong>Dica:</strong> Seu perfil está incompleto. 
                    <a href="?page=perfil" style="color: white; font-weight: 700; text-decoration: underline;">Complete seu perfil</a> 
                    para aparecer no diretório de profissionais!
                </div>
            </div>
        <?php endif; ?>
    </div>
    <div class="dash-hero-time">
        <div class="clock" id="dash-clock">--:--</div>
        <div class="date-str" id="dash-date">...</div>
    </div>
</div>

<!-- KPI CARDS -->
<div class="dash-kpis">
    <?php if($role === ROLE_ADMIN || $role === ROLE_SUPERADMIN): ?>
    <div class="kpi-card kpi-blue">
        <div class="kpi-icon"><i class="fa-solid fa-users"></i></div>
        <div class="kpi-value" data-target="<?php echo $dash_total_users; ?>">0</div>
        <div class="kpi-label">Total de Membros</div>
    </div>
    <div class="kpi-card kpi-green">
        <div class="kpi-icon"><i class="fa-solid fa-user-check"></i></div>
        <div class="kpi-value" data-target="<?php echo $dash_ativos; ?>">0</div>
        <div class="kpi-label">Membros Ativos</div>
    </div>
    <div class="kpi-card kpi-red">
        <div class="kpi-icon"><i class="fa-solid fa-user-clock"></i></div>
        <div class="kpi-value" data-target="<?php echo $dash_pendentes_d; ?>">0</div>
        <div class="kpi-label">Aguardando Aprovação</div>
    </div>
    <?php endif; ?>
    <div class="kpi-card kpi-main">
        <div class="kpi-icon"><i class="fa-solid fa-book-bookmark"></i></div>
        <div class="kpi-value" data-target="<?php echo $dash_materiais_d; ?>">0</div>
        <div class="kpi-label">Materiais na Biblioteca</div>
    </div>
    <div class="kpi-card kpi-purple">
        <div class="kpi-icon"><i class="fa-solid fa-comments"></i></div>
        <div class="kpi-value" data-target="<?php echo $dash_forum_d; ?>">0</div>
        <div class="kpi-label">Tópicos no Fórum</div>
    </div>
    <div class="kpi-card kpi-yellow">
        <div class="kpi-icon"><i class="fa-solid fa-lightbulb"></i></div>
        <div class="kpi-value" data-target="<?php echo $dash_sugestoes_d; ?>">0</div>
        <div class="kpi-label">Sugestões Abertas</div>
    </div>
</div>

<!-- BANNER WHATSAPP (APENAS PARA NÃO-ADMINS) -->
<?php if($role !== ROLE_ADMIN && $role !== ROLE_SUPERADMIN): ?>
<div class="whatsapp-banner anim-fade" style="margin-top: 10px;">
    <div class="whatsapp-banner-content">
        <div class="whatsapp-banner-icon">
            <i class="fa-brands fa-whatsapp"></i>
        </div>
        <div class="whatsapp-banner-text">
            <h2>Fazer parte do nosso grupo!</h2>
            <p>Entre em nossa comunidade exclusiva no WhatsApp e conecte-se com outros profissionais de Tatuí.</p>
        </div>
    </div>
    <a href="https://chat.whatsapp.com/DgI58PF9nwNENGFgafzYrX" target="_blank" class="whatsapp-banner-btn">
        <i class="fa-solid fa-arrow-right-to-bracket"></i> Entrar no Grupo
    </a>
</div>
<?php endif; ?>

<!-- ROW 1 -->
<div class="dash-row row-3">
    <!-- Ações Rápidas + Posts do Fórum -->
    <div class="card">
        <div class="dash-section-title">
            <i class="fa-solid fa-bolt"></i> Ações Rápidas
        </div>
        <div class="quick-actions">
            <a href="?page=biblioteca" class="quick-action-btn">
                <i class="fa-solid fa-book"></i>
                <span>Biblioteca</span>
            </a>
            <a href="?page=forum" class="quick-action-btn">
                <i class="fa-solid fa-comments"></i>
                <span>Fórum</span>
            </a>
            <button class="quick-action-btn" onclick="openModal('modalSugestaoRapida')">
                <i class="fa-solid fa-lightbulb"></i>
                <span>Enviar Ideia</span>
            </button>
            <a href="https://chat.whatsapp.com/DgI58PF9nwNENGFgafzYrX" target="_blank" class="quick-action-btn">
                <i class="fa-brands fa-whatsapp"></i>
                <span>WhatsApp</span>
            </a>
            <?php if($role === ROLE_ADMIN || $role === ROLE_SUPERADMIN): ?>
            <a href="?page=solicitacoes" class="quick-action-btn">
                <i class="fa-solid fa-user-plus"></i>
                <span>Solicitações<?php if($dash_pendentes_d > 0): ?> (<?php echo $dash_pendentes_d; ?>)<?php endif; ?></span>
            </a>
            <a href="?page=materiais" class="quick-action-btn">
                <i class="fa-solid fa-upload"></i>
                <span>Add Material</span>
            </a>
            <?php endif; ?>
            <?php if($role === ROLE_SUPERADMIN): ?>
            <a href="?page=usuarios" class="quick-action-btn">
                <i class="fa-solid fa-users-gear"></i>
                <span>Usuários</span>
            </a>
            <a href="?page=configuracoes" class="quick-action-btn">
                <i class="fa-solid fa-gear"></i>
                <span>Config</span>
            </a>
            <?php endif; ?>
        </div>
        
        <?php if(!empty($recent_posts)): ?>
        <div class="dash-section-title" style="margin-top: 28px;">
            <i class="fa-solid fa-fire"></i> Últimos Tópicos do Fórum
        </div>
        <?php foreach($recent_posts as $rp): 
            $cat_icons = ['duvidas'=>'❓','discussao'=>'💭','recursos'=>'📚','anuncios'=>'📢'];
            $rp_icon = $cat_icons[$rp['categoria']] ?? '💬';
        ?>
        <div class="activity-item">
            <div class="activity-dot blue"></div>
            <div>
                <div class="activity-text"><strong><?php echo $rp_icon; ?> <?php echo htmlspecialchars(mb_substr($rp['titulo'], 0, 55)); ?></strong></div>
                <div class="activity-time">por <?php echo htmlspecialchars($rp['autor'] ?? 'Anônimo'); ?> · <?php echo formatarData($rp['created_at'] ?? ''); ?></div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <!-- Últimos Membros + Taxa de Atividade -->
    <div style="display:flex;flex-direction:column;gap:24px;">
        <!-- Sugestão Rápida -->
        <div class="card sugestao-form-wrapper">
            <div class="dash-section-title">
                <i class="fa-solid fa-comment-dots"></i> Caixa de Ideias
            </div>
            <p style="font-size:0.85em;color:var(--text-muted);margin-bottom:14px;line-height:1.6;">Sugira temas para os próximos encontros do grupo!</p>
            <form method="POST">
                <input type="hidden" name="acao" value="add_sugestao">
                <textarea name="texto" class="input-control" rows="3" placeholder="✍️ Escreva sua ideia aqui..." required style="margin-bottom: 12px; font-size: 0.88em; resize: none;"></textarea>
                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fa-solid fa-paper-plane"></i> Enviar Sugestão
                </button>
            </form>
        </div>
        
        <!-- Taxa de Membros Ativos -->
        <?php if($role === ROLE_ADMIN || $role === ROLE_SUPERADMIN): ?>
        <div class="card">
            <div class="dash-section-title">
                <i class="fa-solid fa-chart-pie"></i> Taxa de Atividade
            </div>
            <?php
                $taxa = ($dash_total_users > 0) ? round(($dash_ativos / $dash_total_users) * 100) : 0;
            ?>
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
                <span style="font-size:0.85em;color:var(--text-muted);">Membros Ativos</span>
                <strong style="color:var(--success);"><?php echo $taxa; ?>%</strong>
            </div>
            <div class="progress-bar-wrap">
                <div class="progress-bar-fill" data-width="<?php echo $taxa; ?>"></div>
            </div>
            <div style="margin-top:18px;display:grid;gap:8px;">
                <div style="display:flex;justify-content:space-between;font-size:0.84em;">
                    <span style="color:var(--text-muted);">✅ Ativos</span><strong style="color:var(--success);"><?php echo $dash_ativos; ?></strong>
                </div>
                <div style="display:flex;justify-content:space-between;font-size:0.84em;">
                    <span style="color:var(--text-muted);">⏳ Pendentes</span><strong style="color:var(--warning);"><?php echo $dash_pendentes_d; ?></strong>
                </div>
                <div style="display:flex;justify-content:space-between;font-size:0.84em;">
                    <span style="color:var(--text-muted);">👥 Total</span><strong><?php echo $dash_total_users; ?></strong>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- ROW 2: Últimos Membros (somente admin+) -->
<?php if(($role === ROLE_ADMIN || $role === ROLE_SUPERADMIN) && !empty($recent_members)): ?>
<div class="card" style="margin-bottom:24px;">
    <div class="dash-section-title">
        <i class="fa-solid fa-user-group"></i> Membros Recentes
        <a href="?page=usuarios" style="margin-left:auto;font-size:0.8em;color:var(--primary);text-decoration:none;font-weight:600;">Ver todos →</a>
    </div>
    <div>
        <?php foreach($recent_members as $rm):
            $rm_role_label = $rm['role'] === ROLE_SUPERADMIN ? 'Super Admin' : ($rm['role'] === ROLE_ADMIN ? 'Admin' : 'Membro');
            $rm_role_color = $rm['role'] === ROLE_SUPERADMIN ? 'var(--primary)' : ($rm['role'] === ROLE_ADMIN ? 'var(--info)' : 'var(--success)');
        ?>
        <div class="member-item">
            <div class="member-avatar-sm"><?php echo strtoupper(substr($rm['nome'], 0, 1)); ?></div>
            <div class="member-details">
                <div class="member-name"><?php echo htmlspecialchars($rm['nome']); ?></div>
                <div class="member-email"><?php echo htmlspecialchars($rm['email']); ?></div>
            </div>
            <span style="font-size:0.72em;font-weight:700;color:<?php echo $rm_role_color; ?>;background:rgba(0,0,0,0.04);padding:4px 10px;border-radius:20px;">
                <?php echo $rm_role_label; ?>
            </span>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Modal Sugestão Rápida do Dashboard -->
<div class="modal-overlay" id="modalSugestaoRapida">
    <div class="modal-content" style="max-width:480px;">
        <div class="modal-header">
            <h2>💡 Enviar Sugestão</h2>
            <button class="close-modal" onclick="closeModal('modalSugestaoRapida')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="modal-body">
            <form method="POST">
                <input type="hidden" name="acao" value="add_sugestao">
                <div class="input-group">
                    <label>Sua ideia ou sugestão</label>
                    <textarea name="texto" class="input-control" rows="5" placeholder="Escreva aqui sua sugestão de tema, atividade ou melhoria para o grupo..." required></textarea>
                </div>
                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fa-solid fa-paper-plane"></i> Enviar Sugestão
                </button>
            </form>
        </div>
    </div>
</div>

<script>
// Clock do hero
function updateClock() {
    const now = new Date();
    const h = String(now.getHours()).padStart(2,'0');
    const m = String(now.getMinutes()).padStart(2,'0');
    const days = ['Domingo','Segunda','Terça','Quarta','Quinta','Sexta','Sábado'];
    const months = ['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'];
    const dayName = days[now.getDay()];
    const dateStr = `${dayName}, ${now.getDate()} de ${months[now.getMonth()]} de ${now.getFullYear()}`;
    const el = document.getElementById('dash-clock');
    const el2 = document.getElementById('dash-date');
    if(el) el.textContent = `${h}:${m}`;
    if(el2) el2.textContent = dateStr;
}
setInterval(updateClock, 1000);
updateClock();
</script>
<?php 
// ### PÁGINA: MEU PERFIL ###
elseif ($pagina === 'perfil'):
    $user_p = getUsuarioLogado();
    $formacao_pos = json_decode($user_p['formacao_pos'] ?? '[]', true);
?>
    <div class="page-header" style="background: linear-gradient(135deg, rgba(110, 43, 58, 0.05) 0%, rgba(212, 165, 116, 0.05) 100%); padding: 24px 28px; border-radius: 14px; margin-bottom: 24px; border: 1px solid rgba(110, 43, 58, 0.1);">
        <div class="page-title">
            <i class="fa-solid fa-id-card-clip" style="color: var(--primary); font-size: 1.5em;"></i>
            <h1 style="font-family: 'Playfair Display', serif; font-weight: 800;">Minha Identidade Digital</h1>
        </div>
        <p style="color: var(--text-muted); font-size: 1.1em; margin-top: 10px;">Personalize como você é visto pela rede e por potenciais pacientes no nosso diretório.</p>
    </div>

    <div class="profile-layout">
        <!-- Sidebar: Gestão Visual -->
        <div class="profile-sidebar">
            <div class="card premium-photo-card" style="position: sticky; top: 100px;">
                <div class="dash-section-title" style="justify-content: center; font-size: 1.2em; border:none; margin-bottom: 25px;">
                    <i class="fa-solid fa-camera-retro"></i> Sua Imagem
                </div>
                
                <div class="avatar-container">
                    <div class="avatar-wrapper shadow-premium" id="avatarPreviewContainer">
                        <?php if(!empty($user_p['foto']) && file_exists($user_p['foto'])): ?>
                            <img src="<?php echo $user_p['foto']; ?>?t=<?php echo time(); ?>" class="avatar-img" id="currentAvatar">
                        <?php else: ?>
                            <div class="avatar-placeholder" id="avatarLetter">
                                <?php echo strtoupper(substr($user_p['nome'], 0, 1)); ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="avatar-loading-overlay" id="avatarLoading">
                            <div class="loading-spinner-rings"></div>
                        </div>
                    </div>
                </div>

                <div class="photo-actions" style="margin-top: 30px;">
                    <?php if(!empty($user_p['foto'])): ?>
                        <div style="display: flex; flex-direction: column; gap: 12px;">
                            <label class="btn btn-outline btn-block" style="cursor: pointer; position: relative; border-style: dashed; opacity: 0.6; pointer-events: none;">
                                <i class="fa-solid fa-lock"></i> Exclua para Alterar
                            </label>
                            <form method="POST" id="formDeleteFoto" onsubmit="return confirm('Deseja realmente remover sua foto de perfil?')">
                                <input type="hidden" name="acao" value="delete_foto_perfil">
                                <button type="submit" class="btn btn-danger btn-block" style="background: #fff1f2; color: #e11d48; border: 1px solid #fecdd3;">
                                    <i class="fa-solid fa-trash-can"></i> Remover Foto Atual
                                </button>
                            </form>
                        </div>
                    <?php else: ?>
                        <form method="POST" enctype="multipart/form-data" id="formUploadFoto">
                            <input type="hidden" name="acao" value="upload_foto_perfil">
                            <label class="btn btn-primary btn-block btn-upload-anim" style="cursor: pointer; padding: 18px; font-weight: 700;">
                                <i class="fa-solid fa-cloud-arrow-up"></i> Carregar Nova Foto
                                <input type="file" name="foto" id="fotoInput" accept="image/*" style="display:none" onchange="autoSubmitPhoto(this)">
                            </label>
                            <div style="text-align: center; margin-top: 15px;">
                                <span style="font-size: 0.8em; color: var(--text-muted);"><i class="fa-solid fa-circle-info"></i> JPG, PNG ou WebP</span>
                                <br>
                                <b style="font-size: 0.85em; color: var(--primary);">Máximo 5MB</b>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Main: Dados Profissionais -->
        <div class="profile-main">
            <form method="POST" id="mainProfileForm" class="anim-fade-up">
                <input type="hidden" name="acao" value="update_perfil">
                
                <!-- Sessão: Essenciais -->
                <div class="card glass-card" style="margin-bottom: 25px;">
                    <div class="dash-section-title"><i class="fa-solid fa-user-tie"></i> Informações Básicas</div>
                    
                    <div class="form-grid">
                        <div class="input-group">
                            <label>Nome Completo <span style="color:red">*</span></label>
                            <input type="text" name="nome" class="input-control premium-input" value="<?php echo htmlspecialchars($user_p['nome']); ?>" required placeholder="Como deseja ser chamado">
                        </div>
                        <div class="input-group">
                            <label>WhatsApp Profissional <span style="color:red">*</span></label>
                            <input type="text" name="whatsapp" class="input-control premium-input" value="<?php echo htmlspecialchars($user_p['whatsapp']); ?>" required placeholder="(15) 99999-9999" id="phone_mask">
                        </div>
                    </div>

                    <div class="form-grid">
                        <div class="input-group">
                            <label>Gênero <span style="color:red">*</span></label>
                            <select name="genero" class="input-control premium-input" required>
                                <option value="Masculino" <?php echo ($user_p['genero'] ?? '') === 'Masculino' ? 'selected' : ''; ?>>Masculino</option>
                                <option value="Feminino" <?php echo ($user_p['genero'] ?? '') === 'Feminino' ? 'selected' : ''; ?>>Feminino</option>
                                <option value="Não declarado" <?php echo ($user_p['genero'] ?? '') === 'Não declarado' ? 'selected' : ''; ?>>Não declarado</option>
                            </select>
                        </div>
                        <div class="input-group">
                            <label>Sua Profissão Principal <span style="color:red">*</span></label>
                            <select name="especialidade" class="input-control premium-input" onchange="updateRegistrationType(this.value)" id="main_profession" required>
                                <option value="">Selecione sua área...</option>
                                <?php 
                                    $professions = ['Psicólogo', 'Médico', 'Psiquiatra', 'Enfermeiro', 'Fisioterapeuta', 'Psicopedagogo', 'Neuropsicólogo', 'Terapeuta Ocupacional', 'Assistente Social', 'Fonoaudiólogo'];
                                    foreach($professions as $p): 
                                        $selected = ($user_p['especialidade'] === $p) ? 'selected' : '';
                                        echo "<option value='$p' $selected>$p</option>";
                                    endforeach;
                                ?>
                            </select>
                        </div>
                        <div class="input-group" id="reg_group" style="display: <?php echo !empty($user_p['registro_tipo']) ? 'block' : 'none'; ?>;">
                            <label id="reg_label">Número do <span id="reg_type_name"><?php echo $user_p['registro_tipo'] ?? 'Registro'; ?></span></label>
                            <div style="display: flex; gap: 10px;">
                                <input type="text" name="registro_tipo" id="reg_type_input" class="input-control premium-input" value="<?php echo htmlspecialchars($user_p['registro_tipo'] ?? ''); ?>" style="width: 90px; text-align: center; font-weight: 800; background: #f1f5f9; color: var(--primary);" readonly>
                                <input type="text" name="registro_numero" class="input-control premium-input" value="<?php echo htmlspecialchars($user_p['registro_numero'] ?? ''); ?>" placeholder="Digite o número">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sessão: Expertise -->
                <div class="card glass-card" style="margin-bottom: 25px;">
                    <div class="dash-section-title"><i class="fa-solid fa-graduation-cap"></i> Formação & Atuação</div>
                    
                    <div class="input-group">
                        <label>Graduação Principal</label>
                        <input type="text" name="formacao_superior" class="input-control premium-input" value="<?php echo htmlspecialchars($user_p['formacao_superior'] ?? ''); ?>" placeholder="Ex: Psicologia pela UNESP">
                    </div>

                    <div class="input-group">
                        <label>Especializações / Pós-Graduações / MBA</label>
                        <div id="dynamicPosContainer">
                            <?php if(!empty($formacao_pos)): foreach($formacao_pos as $p_item): ?>
                                <div class="dynamic-row">
                                    <input type="text" name="formacao_pos[]" class="input-control premium-input" value="<?php echo htmlspecialchars($p_item); ?>">
                                    <button type="button" class="btn-remove-row" onclick="this.parentElement.remove()"><i class="fa-solid fa-times"></i></button>
                                </div>
                            <?php endforeach; else: ?>
                                <div class="dynamic-row">
                                    <input type="text" name="formacao_pos[]" class="input-control premium-input" placeholder="Ex: Pós em Terapia Cognitivo Comportamental">
                                </div>
                            <?php endif; ?>
                        </div>
                        <button type="button" class="btn btn-outline btn-sm" onclick="addAcademicField()" style="margin-top: 12px; border-style: dashed; width: 100%; border-radius: 8px;">
                            <i class="fa-solid fa-plus-circle"></i> Adicionar Mais Uma Formação
                        </button>
                    </div>

                    <div class="input-group" style="margin-top: 25px;">
                        <label>Sub-áreas / Áreas de Foco</label>
                        <input type="text" name="area_atuacao" class="input-control premium-input" value="<?php echo htmlspecialchars($user_p['area_atuacao'] ?? ''); ?>" placeholder="Ex: Ansiedade, Luto, Infantil, Gerontologia...">
                        <small style="color: var(--text-muted); margin-top: 5px; display: block;"><i class="fa-solid fa-lightbulb"></i> Separe por vírgulas. Isso ajuda outros profissionais a te encontrarem.</small>
                    </div>
                </div>

                <!-- Sessão: Detalhes do Profissional -->
                <div class="card glass-card" style="margin-bottom: 25px;">
                    <div class="dash-section-title"><i class="fa-solid fa-briefcase"></i> Atuação Detalhada</div>

                    <div class="form-grid">
                        <div class="input-group">
                            <label>O Que Você Faz? (Resumo da sua atuação)</label>
                            <input type="text" name="descricao_trabalho" class="input-control premium-input" value="<?php echo htmlspecialchars($user_p['descricao_trabalho'] ?? ''); ?>" placeholder="Ex: Avaliação Neuropsicológica Infantil, etc">
                        </div>
                        <div class="input-group">
                            <label>Endereço de Atendimento / Clínica</label>
                            <input type="text" name="endereco" class="input-control premium-input" value="<?php echo htmlspecialchars($user_p['endereco'] ?? ''); ?>" placeholder="Seu endereço comercial ou 'Atendimento Online'">
                        </div>
                    </div>
                    
                    <div class="form-grid">
                        <div class="input-group">
                            <label>Disponível para Parcerias?</label>
                            <select name="aceita_parcerias" class="input-control premium-input">
                                <option value="Sim" <?php echo ($user_p['aceita_parcerias'] ?? '') === 'Sim' ? 'selected' : ''; ?>>Sim, estou aberto(a) a indicações e parcerias</option>
                                <option value="Não" <?php echo ($user_p['aceita_parcerias'] ?? 'Não') === 'Não' ? 'selected' : ''; ?>>Não, no momento não</option>
                            </select>
                        </div>
                        <div class="input-group">
                            <label>Trabalha com Preço Social / Instituições?</label>
                            <select name="preco_social" class="input-control premium-input">
                                <option value="Sim" <?php echo ($user_p['preco_social'] ?? '') === 'Sim' ? 'selected' : ''; ?>>Sim, realizo atendimentos sociais</option>
                                <option value="Não" <?php echo ($user_p['preco_social'] ?? 'Não') === 'Não' ? 'selected' : ''; ?>>Não realizo</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Sessão: Presença Digital -->
                <div class="card glass-card" style="margin-bottom: 30px;">
                    <div class="dash-section-title"><i class="fa-solid fa-share-nodes"></i> Presença Digital & Biografia</div>
                    
                    <div class="form-grid">
                        <div class="input-group">
                            <label><i class="fa-brands fa-instagram" style="color:#E1306C"></i> Instagram Profissional</label>
                            <input type="text" name="instagram" class="input-control premium-input" value="<?php echo htmlspecialchars($user_p['instagram'] ?? ''); ?>" placeholder="@seu.perfil">
                        </div>
                        <div class="input-group">
                            <label><i class="fa-solid fa-globe" style="color:#3b82f6"></i> Website ou Portfólio</label>
                            <input type="url" name="website" class="input-control premium-input" value="<?php echo htmlspecialchars($user_p['website'] ?? ''); ?>" placeholder="https://seusite.com.br">
                        </div>
                    </div>

                    <div class="input-group">
                        <label>Sua História / Biografia</label>
                        <textarea name="bio" class="input-control premium-input" rows="6" placeholder="Conte um pouco sobre sua trajetória, sua abordagem e como você trabalha..."><?php echo htmlspecialchars($user_p['bio'] ?? ''); ?></textarea>
                    </div>
                </div>

                <div class="profile-sticky-footer">
                    <button type="submit" class="btn btn-primary btn-lg btn-save-premium" id="saveProfileBtn">
                        <i class="fa-solid fa-check-double"></i> 
                        <span>Atualizar Perfil Completo</span>
                    </button>
                    <p style="text-align: center; margin-top: 15px; font-size: 0.85em; color: var(--text-muted);">
                        <i class="fa-solid fa-shield-halved"></i> Suas informações estão seguras e são usadas apenas para a rede Melodias.
                    </p>
                </div>
            </form>
        </div>
    </div>

    <style>
        .profile-layout { display: grid; grid-template-columns: 320px 1fr; gap: 40px; align-items: start; }
        .premium-photo-card { padding: 24px 20px !important; text-align: center; border-radius: 16px; background: rgba(255,255,255,0.7); backdrop-filter: blur(10px); border: 1px solid rgba(110, 43, 58, 0.08); }
        .avatar-container { margin: 20px 0; display: flex; justify-content: center; }
        .avatar-wrapper { 
            width: 190px; height: 190px; border-radius: 50%; border: 8px solid #fff; 
            box-shadow: 0 15px 45px rgba(0,0,0,0.12); overflow: hidden; position: relative;
            background: #f8fafc; transition: transform 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        .avatar-wrapper:hover { transform: scale(1.02); }
        .avatar-img { width: 100%; height: 100%; object-fit: cover; }
        .avatar-placeholder { 
            width: 100%; height: 100%; display: flex; align-items: center; 
            justify-content: center; font-size: 6em; font-weight: 800; 
            color: #cbd5e1; background: #f1f5f9;
        }
        .avatar-loading-overlay {
            position: absolute; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(110, 43, 58, 0.4); display: none; align-items: center; 
            justify-content: center; z-index: 5; backdrop-filter: blur(2px);
        }
        .glass-card { background: rgba(255,255,255,0.6); backdrop-filter: blur(12px); border: 1px solid rgba(110, 43, 58, 0.05); border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.03); }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 25px; }
        .premium-input { 
            border: 2px solid #eef2f6; border-radius: 14px; padding: 14px 18px; 
            font-size: 1.05em; transition: all 0.3s; background: #fff;
        }
        .premium-input:focus { border-color: var(--primary); box-shadow: 0 0 0 4px rgba(110, 43, 58, 0.1); outline: none; }
        .dynamic-row { display: flex; gap: 12px; margin-bottom: 12px; animation: slideInUp 0.3s; }
        .btn-remove-row { 
            background: #fff1f2; color: #e11d48; border: none; border-radius: 10px;
            width: 48px; height: 50px; display: flex; align-items: center; justify-content: center;
            cursor: pointer; transition: 0.2s;
        }
        .btn-remove-row:hover { background: #ffe4e6; transform: scale(1.05); }
        .profile-sticky-footer {
            margin-top: 28px; padding: 20px; background: rgba(110, 43, 58, 0.03);
            border-radius: 14px; border: 1px dashed rgba(110, 43, 58, 0.2);
        }
        .btn-save-premium { width: 100%; padding: 14px 20px; font-size: 0.95em; border-radius: 10px; font-weight: 700; letter-spacing: 0.3px; box-shadow: 0 4px 14px rgba(110, 43, 58, 0.2); }
        .btn-upload-anim:hover { transform: translateY(-3px); box-shadow: 0 8px 20px rgba(110, 43, 58, 0.2); }

        @keyframes slideInUp { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

        @media (max-width: 1024px) {
            .profile-layout { grid-template-columns: 1fr; }
            .form-grid { grid-template-columns: 1fr; }
            .profile-sidebar { order: 2; }
            .profile-main { order: 1; }
            .avatar-wrapper { width: 140px; height: 140px; }
            .avatar-placeholder { font-size: 4em; }
            .btn-save-premium { padding: 12px 18px !important; font-size: 0.9em !important; }
        }

        /* Spinner Animado */
        .loading-spinner-rings {
            width: 40px; height: 40px; border: 4px solid rgba(255,255,255,0.3);
            border-top-color: #fff; border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
    </style>

    <script>
        function updateRegistrationType(val) {
            const group = document.getElementById('reg_group');
            const typeInput = document.getElementById('reg_type_input');
            const typeName = document.getElementById('reg_type_name');
            
            const registryMap = {
                'Psicólogo': 'CRP',
                'Médico': 'CRM',
                'Psiquiatra': 'CRM',
                'Enfermeiro': 'COREN',
                'Fisioterapeuta': 'CREFITO',
                'Psicopedagogo': 'CBO',
                'Neuropsicólogo': 'CRP',
                'Assistente Social' : 'CRESS',
                'Fonoaudiólogo' : 'CRFa'
            };

            if (registryMap[val]) {
                group.style.display = 'block';
                typeInput.value = registryMap[val];
                typeName.innerText = registryMap[val];
            } else {
                group.style.display = 'none';
                typeInput.value = '';
            }
        }

        function addAcademicField() {
            const container = document.getElementById('dynamicPosContainer');
            const div = document.createElement('div');
            div.className = 'dynamic-row';
            div.innerHTML = `
                <input type="text" name="formacao_pos[]" class="input-control premium-input" placeholder="Nova especialização...">
                <button type="button" class="btn-remove-row" onclick="this.parentElement.remove()"><i class="fa-solid fa-times"></i></button>
            `;
            container.appendChild(div);
            div.querySelector('input').focus();
        }

        function autoSubmitPhoto(input) {
            if (input.files && input.files[0]) {
                const file = input.files[0];
                if (file.size > 5 * 1024 * 1024) {
                    showToast('Arquivo Gigante!', 'O limite é de 5MB. Sua foto tem ' + (file.size/1024/1024).toFixed(2) + 'MB.', 'error');
                    input.value = '';
                    return;
                }
                
                // Overlay de Carregamento
                document.getElementById('avatarLoading').style.display = 'flex';
                // Envio Automático
                input.form.submit();
            }
        }

        document.getElementById('mainProfileForm').addEventListener('submit', function() {
            const btn = document.getElementById('saveProfileBtn');
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Sincronizando com o Servidor...';
        });

        // Inicialização
        document.addEventListener('DOMContentLoaded', () => {
            const currentProf = document.getElementById('main_profession').value;
            if (currentProf) updateRegistrationType(currentProf);
        });
    </script>

<?php 
// ### PÁGINA: DIRETÓRIO DE MEMBROS ###
elseif ($pagina === 'membros'):
    $membros = $pdo->query("SELECT * FROM profissionais WHERE status = 'ativo' ORDER BY nome ASC")->fetchAll();
?>
    <div class="page-header">
        <div class="page-title">
            <i class="fa-solid fa-address-book"></i>
            <h1>Diretório de Profissionais</h1>
        </div>
        <p style="color: var(--text-muted);">Conecte-se com nossa rede local em Tatuí.</p>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 24px;">
        <?php foreach($membros as $m): 
            $sigla = !empty($m['registro_tipo']) ? $m['registro_tipo'] . " " . $m['registro_numero'] : $m['especialidade'];
        ?>
            <div class="card anim-fade" style="padding: 0; overflow: hidden; display: flex; flex-direction: column;">
                <!-- Header do Card com Foto -->
                <div style="background: linear-gradient(135deg, var(--accent) 0%, var(--primary) 100%); height: 80px; position: relative;"></div>
                <div style="margin: -40px auto 15px; width: 100px; height: 100px; border-radius: 50%; border: 4px solid var(--bg-card); overflow: hidden; background: #f1f5f9; box-shadow: var(--shadow); z-index: 1;">
                    <?php if(!empty($m['foto']) && file_exists($m['foto'])): ?>
                        <img src="<?php echo $m['foto']; ?>" style="width: 100%; height: 100%; object-fit: cover;">
                    <?php else: ?>
                        <div style="width: 100%; height: 100%; background: #e2e8f0; display: flex; align-items: center; justify-content: center; color: #94a3b8; font-weight: 700; font-size: 2em;"><?php echo strtoupper(substr($m['nome'], 0, 1)); ?></div>
                    <?php endif; ?>
                </div>

                <div style="padding: 15px 20px 25px; text-align: center; flex: 1; display: flex; flex-direction: column;">
                    <h3 style="margin: 0; font-size: 1.15em; font-weight: 800; color: var(--primary);"><?php echo htmlspecialchars($m['nome']); ?></h3>
                    <div style="font-size: 0.82em; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin: 5px 0 12px; letter-spacing: 0.5px;">
                        <?php echo htmlspecialchars(formatarProfissao($m['especialidade'], $m['genero'] ?? '')); ?>
                    </div>
                    
                    <?php if(!empty($m['registro_numero'])): ?>
                    <div style="display: inline-block; background: rgba(110,43,58,0.06); color: var(--primary); padding: 4px 12px; border-radius: 20px; font-size: 0.75em; font-weight: 800; margin-bottom: 12px;">
                        <?php echo htmlspecialchars($m['registro_tipo']); ?> <?php echo htmlspecialchars($m['registro_numero']); ?>
                    </div>
                    <?php endif; ?>

                    <p style="font-size: 0.88em; color: var(--text-muted); line-height: 1.5; margin-bottom: 20px; flex: 1;">
                        <?php echo !empty($m['bio']) ? htmlspecialchars(mb_substr($m['bio'], 0, 100)) . '...' : 'Olá! Faça parte da nossa rede de profissionais em Tatuí.'; ?>
                    </p>

                    <a href="?page=ver_perfil&id=<?php echo $m['id']; ?>" class="btn btn-outline" style="margin-bottom: 15px; border-radius: 8px; width: 100%; border-style: dashed; display: flex; align-items: center; justify-content: center; gap: 8px; text-decoration: none;">
                        <i class="fa-solid fa-address-card"></i> 
                        <span>Ver Perfil Detalhado</span>
                    </a>

                    <div style="display: flex; justify-content: center; gap: 15px; border-top: 1px solid var(--border); padding-top: 15px;">
                        <?php if(!empty($m['instagram'])): ?>
                            <a href="https://instagram.com/<?php echo str_replace('@', '', $m['instagram']); ?>" target="_blank" style="color: #E1306C; font-size: 1.3em;" title="Instagram"><i class="fa-brands fa-instagram"></i></a>
                        <?php endif; ?>
                        <?php if(!empty($m['website'])): ?>
                            <a href="<?php echo $m['website']; ?>" target="_blank" style="color: var(--info); font-size: 1.3em;" title="Website"><i class="fa-solid fa-globe"></i></a>
                        <?php endif; ?>
                        <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $m['whatsapp']); ?>" target="_blank" style="color: #25D366; font-size: 1.3em;" title="Chamar WhatsApp"><i class="fa-brands fa-whatsapp"></i></a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

<?php 
// ### PÁGINA: VER PERFIL DETALHADO ###
elseif ($pagina === 'ver_perfil'):
    $id_ver = $_GET['id'] ?? 0;
    $stmt = $pdo->prepare("SELECT * FROM profissionais WHERE id = ?");
    $stmt->execute([$id_ver]);
    $m = $stmt->fetch();

    if (!$m) {
        echo "<div class='card glass-card' style='text-align:center; padding:100px 20px;'><i class='fa-solid fa-user-slash' style='font-size:4em; color:var(--text-muted); opacity:0.3; margin-bottom:20px;'></i><h1>Usuário não encontrado</h1><a href='?page=membros' class='btn btn-primary' style='margin-top:20px;'>Voltar ao Diretório</a></div>";
    } else {
        $sigla_reg = !empty($m['registro_tipo']) ? $m['registro_tipo'] . " " . $m['registro_numero'] : "";
        $profissao_f = formatarProfissao($m['especialidade'], $m['genero'] ?? '');
?>
    <div class="profile-public-container anim-fade">
        <!-- Header / Banner -->
        <div class="profile-public-header" style="background: linear-gradient(135deg, var(--accent) 0%, var(--primary) 100%);">
            <a href="?page=membros" class="btn-back-public"><i class="fa-solid fa-arrow-left"></i> Voltar</a>
        </div>

        <div class="profile-public-content">
            <!-- Sidebar: Foto e Infos Rápidas -->
            <div class="profile-public-sidebar">
                <div class="card glass-card public-photo-card">
                    <div class="public-avatar-wrapper">
                        <?php if(!empty($m['foto']) && file_exists($m['foto'])): ?>
                            <img src="<?php echo $m['foto']; ?>" class="public-avatar-img">
                        <?php else: ?>
                            <div class="public-avatar-placeholder"><?php echo strtoupper(substr($m['nome'], 0, 1)); ?></div>
                        <?php endif; ?>
                    </div>
                    <h2 class="public-nome"><?php echo htmlspecialchars($m['nome']); ?></h2>
                    <div class="public-tag"><?php echo htmlspecialchars($profissao_f); ?></div>
                    
                    <?php if(!empty($sigla_reg)): ?>
                    <div class="public-reg"><?php echo htmlspecialchars($sigla_reg); ?></div>
                    <?php endif; ?>

                    <div class="public-contacts">
                        <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $m['whatsapp']); ?>" target="_blank" class="btn btn-success btn-block" style="border-radius:12px; margin-bottom:10px;">
                            <i class="fa-brands fa-whatsapp"></i> Chamar WhatsApp
                        </a>
                        <div style="display: flex; gap: 10px; justify-content: center; margin-top: 15px;">
                            <?php if(!empty($m['instagram'])): ?>
                                <a href="https://instagram.com/<?php echo str_replace('@', '', $m['instagram']); ?>" target="_blank" class="contact-circle inst" title="Instagram"><i class="fa-brands fa-instagram"></i></a>
                            <?php endif; ?>
                            <?php if(!empty($m['website'])): ?>
                                <a href="<?php echo $m['website']; ?>" target="_blank" class="contact-circle web" title="Website"><i class="fa-solid fa-globe"></i></a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="card glass-card" style="margin-top:20px; padding:20px;">
                    <div class="dash-section-title" style="margin-bottom:10px;"><i class="fa-solid fa-location-dot"></i> Endereço</div>
                    <p style="font-size:0.9em; color:var(--text-main); line-height:1.5;">
                        <?php echo !empty($m['endereco']) ? htmlspecialchars($m['endereco']) : 'Atendimento a combinar / Online'; ?>
                    </p>
                </div>
            </div>

            <!-- Main: Bio e Formação -->
            <div class="profile-public-main">
                <div class="card glass-card" style="margin-bottom:24px;">
                    <div class="dash-section-title"><i class="fa-solid fa-user-tie"></i> Sobre Mim / Biografia</div>
                    <div class="public-bio-text">
                        <?php echo !empty($m['bio']) ? nl2br(htmlspecialchars($m['bio'])) : 'Olá! Sou profissional da rede Melodias.'; ?>
                    </div>
                </div>

                <div class="card glass-card" style="margin-bottom:24px;">
                    <div class="dash-section-title"><i class="fa-solid fa-briefcase"></i> O Quê Faço</div>
                    <div class="public-bio-text">
                        <?php echo !empty($m['descricao_trabalho']) ? nl2br(htmlspecialchars($m['descricao_trabalho'])) : 'Informação não detalhada.'; ?>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 20px; padding-top: 20px; border-top: 1px solid var(--border);">
                        <div class="status-badge-public">
                            <span class="label">Aceita Parcerias?</span>
                            <span class="value <?php echo $m['aceita_parcerias']==='Sim'?'success':'muted'; ?>"><?php echo $m['aceita_parcerias'] ?? 'Não'; ?></span>
                        </div>
                        <div class="status-badge-public">
                            <span class="label">Preço Social?</span>
                            <span class="value <?php echo $m['preco_social']==='Sim'?'info':'muted'; ?>"><?php echo $m['preco_social'] ?? 'Não'; ?></span>
                        </div>
                    </div>
                </div>

                <div class="card glass-card">
                    <div class="dash-section-title"><i class="fa-solid fa-graduation-cap"></i> Formação & Especialidades</div>
                    
                    <div class="academic-timeline">
                        <?php if(!empty($m['formacao_superior'])): ?>
                            <div class="academic-item">
                                <div class="academic-icon"><i class="fa-solid fa-university"></i></div>
                                <div class="academic-details">
                                    <strong>Formação Acadêmica</strong>
                                    <span><?php echo htmlspecialchars($m['formacao_superior']); ?></span>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php 
                        try {
                            $pos = json_decode($m['formacao_pos'] ?? '[]', true);
                            if(!empty($pos)):
                                foreach($pos as $p): if(empty($p)) continue;
                        ?>
                            <div class="academic-item">
                                <div class="academic-icon pos"><i class="fa-solid fa-certificate"></i></div>
                                <div class="academic-details">
                                    <strong>Especialização / Pós</strong>
                                    <span><?php echo htmlspecialchars($p); ?></span>
                                </div>
                            </div>
                        <?php endforeach; endif; } catch(Exception $e){} ?>
                    </div>

                    <?php if(!empty($m['area_atuacao'])): ?>
                        <div style="margin-top:25px; padding-top:20px; border-top: 1px solid var(--border);">
                            <strong>Foco de Atuação:</strong>
                            <div style="margin-top:10px; display:flex; flex-wrap:wrap; gap:8px;">
                                <?php 
                                $tags = explode(',', $m['area_atuacao']);
                                foreach($tags as $t): if(trim($t) !== ''):
                                ?>
                                    <span class="atuacao-tag"><?php echo htmlspecialchars(trim($t)); ?></span>
                                <?php endif; endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
<?php } ?>

    <style>
        .profile-public-container { background: var(--bg-body); min-height: 100vh; margin: -20px; }
        .profile-public-header { height: 200px; position: relative; display: flex; align-items: flex-start; padding: 30px; }
        .btn-back-public { background: rgba(255,255,255,0.2); color: white; padding: 10px 20px; border-radius: 50px; text-decoration: none; font-weight: 700; backdrop-filter: blur(10px); transition: 0.3s; }
        .btn-back-public:hover { background: rgba(255,255,255,0.3); transform: translateX(-5px); }
        
        .profile-public-content { max-width: 1100px; margin: -100px auto 50px; padding: 0 20px; display: grid; grid-template-columns: 340px 1fr; gap: 30px; }
        .public-photo-card { text-align: center; padding: 24px 16px !important; }
        .public-avatar-wrapper { width: 180px; height: 180px; border-radius: 50%; border: 6px solid #fff; box-shadow: var(--shadow-lg); overflow: hidden; margin: 0 auto 20px; background: #f8fafc; }
        .public-avatar-img { width: 100%; height: 100%; object-fit: cover; }
        .public-avatar-placeholder { width: 100%; height: 100%; background: #e2e8f0; display: flex; align-items: center; justify-content: center; font-size: 5em; font-weight: 800; color: #94a3b8; }
        
        .public-nome { font-size: 1.8em; color: var(--primary); margin-bottom: 5px; font-family: 'Playfair Display', serif; }
        .public-tag { font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; font-size: 0.85em; margin-bottom: 15px; }
        .public-reg { display: inline-block; background: rgba(110,43,58,0.06); color: var(--primary); padding: 5px 15px; border-radius: 20px; font-size: 0.8em; font-weight: 800; margin-bottom: 25px; }
        
        .contact-circle { width: 45px; height: 45px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.25em; color: white; transition: 0.3s; }
        .contact-circle.inst { background: #E1306C; }
        .contact-circle.web { background: var(--info); }
        .contact-circle:hover { transform: scale(1.15) rotate(10deg); box-shadow: var(--shadow-lg); }

        .public-bio-text { color: var(--text-main); line-height: 1.8; font-size: 1em; }
        .atuacao-tag { background: white; border: 1.5px solid var(--border); padding: 6px 14px; border-radius: 50px; font-size: 0.82em; font-weight: 600; color: var(--text-muted); }
        
        .status-badge-public { display: flex; flex-direction: column; }
        .status-badge-public .label { font-size: 0.75em; color: var(--text-muted); font-weight: 700; text-transform: uppercase; }
        .status-badge-public .value { font-weight: 800; font-size: 1.05em; }
        .status-badge-public .value.success { color: var(--success); }
        .status-badge-public .value.info { color: var(--info); }
        .status-badge-public .value.muted { color: #94a3b8; }

        .academic-timeline { display: flex; flex-direction: column; gap: 20px; margin-top: 10px; }
        .academic-item { display: flex; gap: 15px; align-items: flex-start; }
        .academic-icon { width: 40px; height: 40px; background: rgba(110, 43, 58, 0.08); color: var(--primary); border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .academic-icon.pos { background: rgba(245, 158, 11, 0.1); color: var(--warning); }
        .academic-details { display: flex; flex-direction: column; }
        .academic-details strong { font-size: 0.9em; color: var(--text-main); }
        .academic-details span { font-size: 0.85em; color: var(--text-muted); }

        @media (max-width: 900px) {
            .profile-public-content { grid-template-columns: 1fr; }
            .profile-public-sidebar { order: 1; }
            .profile-public-main { order: 2; }
            .profile-public-header { height: 150px; }
            .public-nome { font-size: 1.4em; }
        }
        @media (max-width: 480px) {
            .profile-public-content { padding: 0 12px; margin-top: -60px; }
            .public-avatar-wrapper { width: 130px; height: 130px; }
        }
    </style>
<?php 
// ### PÁGINAS SEGUINTES... ###
// ### PÁGINAS: APRENDIZADOS E MATERIAIS DE APOIO ###
elseif ($pagina === 'biblioteca' || $pagina === 'materiais_apoio'):
    $is_apoio = ($pagina === 'materiais_apoio');
    try {
        $filter = $is_apoio ? "tipo = 'material'" : "tipo IN ('ebook', 'minicurso', 'indicacao')";
        $sql = ($role === ROLE_ADMIN || $role === ROLE_SUPERADMIN) 
            ? "SELECT * FROM materiais WHERE {$filter} ORDER BY created_at DESC" 
            : "SELECT * FROM materiais WHERE {$filter} AND visibilidade = 'todos' ORDER BY created_at DESC";
        $materiais = $pdo->query($sql)->fetchAll();
    } catch (PDOException $e) {
        $filter = $is_apoio ? "tipo = 'material'" : "tipo IN ('ebook', 'minicurso', 'indicacao')";
        $sql = ($role === ROLE_ADMIN || $role === ROLE_SUPERADMIN) 
            ? "SELECT * FROM materiais WHERE {$filter} ORDER BY id DESC" 
            : "SELECT * FROM materiais WHERE {$filter} AND visibilidade = 'todos' ORDER BY id DESC";
        $materiais = $pdo->query($sql)->fetchAll();
    }
?>
                <div class="page-header-actions">
                    <div>
                        <h1><i class="fa-solid <?php echo $is_apoio ? 'fa-copy' : 'fa-graduation-cap'; ?>"></i> <?php echo $is_apoio ? 'Materiais de Apoio' : 'Aprendizados & Conhecimento'; ?></h1>
                        <p style="color: var(--text-muted);"><?php echo $is_apoio ? 'Acesse documentos, anamneses e formulários úteis.' : 'E-books, Mini Cursos e Conteúdos Extras para sua formação.'; ?></p>
                    </div>
                    <div class="input-group" style="margin: 0; min-width: 300px;">
                        <input type="text" id="buscaMaterial" onkeyup="filtrarMateriais()" 
                               class="input-control" placeholder="🔍 Buscar conteúdo..." 
                               style="border-radius: 30px; box-shadow: var(--shadow);">
                    </div>
                </div>

                <?php
                $materiais_tipos = ['material' => [], 'ebook' => [], 'minicurso' => [], 'indicacao' => []];
                foreach ($materiais as $m) {
                    $t = $m['tipo'] ?? 'material';
                    if (isset($materiais_tipos[$t])) $materiais_tipos[$t][] = $m;
                }

                if ($is_apoio) {
                    $secoes = [['id' => 'sec_material', 'titulo' => 'Documentos, Anamneses & Formulários', 'icon' => 'fa-file-pdf', 'tipo' => 'material', 'color' => '#6e2b3a']];
                } else {
                    $secoes = [
                        ['id' => 'sec_ebook', 'titulo' => 'E-books & Guias Práticos', 'icon' => 'fa-book-open', 'tipo' => 'ebook', 'color' => '#10b981'],
                        ['id' => 'sec_minicurso', 'titulo' => 'Mini Cursos & Masterclasses', 'icon' => 'fa-video', 'tipo' => 'minicurso', 'color' => '#3b82f6'],
                        ['id' => 'sec_indicacao', 'titulo' => 'Indicações & Conteúdos Extras', 'icon' => 'fa-lightbulb', 'tipo' => 'indicacao', 'color' => '#f59e0b']
                    ];
                }

                foreach ($secoes as $secao):
                    $lista = $materiais_tipos[$secao['tipo']] ?? [];
                    if (empty($lista)) continue;
                ?>
                    <div class="dash-section-title" id="<?php echo $secao['id']; ?>" style="margin-top: 40px; border-bottom: 2px solid <?php echo $secao['color']; ?>; padding-bottom: 10px; display: flex; align-items: center; gap: 12px; color: <?php echo $secao['color']; ?>;">
                        <i class="fa-solid <?php echo $secao['icon']; ?>"></i> <?php echo $secao['titulo']; ?>
                        <span style="font-size: 0.5em; background: <?php echo $secao['color']; ?>22; color: <?php echo $secao['color']; ?>; border: 1px solid <?php echo $secao['color']; ?>44; padding: 4px 12px; border-radius: 20px; margin-left: auto;">
                            <?php echo count($lista); ?> itens
                        </span>
                    </div>

                    <div class="grid-cards" id="grid_<?php echo $secao['tipo']; ?>" style="margin-top: 20px;">
                        <?php foreach($lista as $m): ?>
                            <div class="card material-item" style="padding: 0; overflow: hidden; display: flex; flex-direction: column; transition: transform 0.3s ease;">
                                <?php if(!empty($m['capa'])): ?>
                                    <div style="position: relative; cursor: pointer;" onclick='abrirPreview(<?php echo htmlspecialchars(json_encode($m), ENT_QUOTES, "UTF-8"); ?>)'>
                                        <img src="<?php echo htmlspecialchars($m['capa']); ?>" alt="Capa" style="width: 100%; height: 180px; object-fit: cover;">
                                        <div class="overlay-preview" style="position: absolute; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.4); display:flex; align-items:center; justify-content:center; opacity:0; transition:0.3s;">
                                            <i class="fa-solid fa-eye" style="color:white; font-size:2em;"></i>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div onclick='abrirPreview(<?php echo htmlspecialchars(json_encode($m), ENT_QUOTES, "UTF-8"); ?>)' style="width: 100%; height: 180px; background: linear-gradient(135deg, <?php echo $secao['color']; ?>22 0%, <?php echo $secao['color']; ?>11 100%); display: flex; align-items: center; justify-content: center; color: <?php echo $secao['color']; ?>; font-size: 4em; cursor: pointer;">
                                        <i class="fa-solid <?php echo $secao['icon']; ?>"></i>
                                    </div>
                                <?php endif; ?>
                                
                                <div style="padding: 22px; flex: 1; display: flex; flex-direction: column;">
                                    <div style="font-size: 0.75em; text-transform: uppercase; font-weight: 800; color: <?php echo $secao['color']; ?>; letter-spacing: 1px; margin-bottom: 8px;">
                                        <?php echo htmlspecialchars($m['categoria']); ?>
                                    </div>
                                    <h3 class="card-title material-titulo" style="margin: 0 0 12px 0; font-size: 1.15em; line-height: 1.4; color: var(--text-main); font-weight: 700;"><?php echo $m['titulo']; ?></h3>
                                    
                                    <?php if(!empty($m['autor'])): ?>
                                        <div style="font-size: 0.85em; color: var(--text-muted); margin-bottom: 12px; display: flex; align-items: center; gap: 6px;">
                                            <i class="fa-solid fa-user-tie"></i> <?php echo htmlspecialchars($m['autor']); ?>
                                        </div>
                                    <?php endif; ?>

                                    <?php if(!empty($m['descricao'])): ?>
                                        <p style="font-size: 0.88em; color: var(--text-muted); margin-bottom: 20px; line-height: 1.6; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                            <?php echo htmlspecialchars($m['descricao']); ?>
                                        </p>
                                    <?php endif; ?>

                                    <div style="margin-top: auto; display: grid; grid-template-columns: 1fr auto; gap: 10px;">
                                        <?php 
                                        $link = $m['url_externa'] ?: $m['caminho'];
                                        $btn_text = $m['tipo'] === 'minicurso' ? 'Assistir' : ($m['tipo'] === 'ebook' ? 'Ler Online' : 'Visualizar');
                                        $btn_icon = $m['tipo'] === 'minicurso' ? 'fa-play' : ($m['tipo'] === 'ebook' ? 'fa-book-open' : 'fa-eye');
                                        ?>
                                        <button onclick='abrirPreview(<?php echo htmlspecialchars(json_encode($m), ENT_QUOTES, "UTF-8"); ?>)' class="btn" style="background: <?php echo $secao['color']; ?>; color: white; border-radius: 8px; font-weight: 700;">
                                            <i class="fa-solid <?php echo $btn_icon; ?>"></i> <?php echo $btn_text; ?>
                                        </button>
                                        
                                        <?php if(empty($m['url_externa'])): ?>
                                            <a href="<?php echo htmlspecialchars($m['caminho']); ?>" download class="btn btn-outline" style="border-radius: 8px; width: 42px; display: flex; align-items: center; justify-content: center; padding: 0;" title="Baixar Arquivo">
                                                <i class="fa-solid fa-download"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>

                <?php if(count($materiais) === 0): ?>
                    <div class="card" style="margin-top: 40px; padding: 60px; text-align: center;">
                        <i class="fa-solid fa-folder-open" style="font-size: 4em; color: var(--text-muted); opacity: 0.2; margin-bottom: 20px;"></i>
                        <h2 style="color: var(--text-muted);">Nenhum conteúdo por aqui</h2>
                        <p style="color: var(--text-muted);">Fique atento às atualizações do sistema!</p>
                    </div>
                <?php endif; ?>

                <style>
                    .material-item:hover .overlay-preview { opacity: 1 !important; }
                    .material-item:hover { transform: translateY(-5px); box-shadow: var(--shadow-lg); }
                </style>

<?php 
// ### PÁGINA: GESTÃO DE MATERIAIS (ADMIN/SUPERADMIN) ###
elseif ($pagina === 'materiais'):
    verificarPermissao(ROLE_ADMIN);
    try {
        $materiais = $pdo->query("SELECT m.*, p.nome as criado_por FROM materiais m LEFT JOIN profissionais p ON m.created_by = p.id ORDER BY m.created_at DESC")->fetchAll();
    } catch (PDOException $e) {
        // Fallback se created_by ou created_at não existirem
        $materiais = $pdo->query("SELECT * FROM materiais ORDER BY id DESC")->fetchAll();
    }
?>
                <div class="page-header-actions">
                    <div>
                        <h1><i class="fa-solid fa-file-lines"></i> Gestão de Materiais</h1>
                        <p style="color: var(--text-muted);">Gerencie os arquivos disponíveis na biblioteca</p>
                    </div>
                    <button onclick="openModal('modalAddMaterial')" class="btn btn-primary">
                        <i class="fa-solid fa-plus"></i> Novo Material
                    </button>
                </div>

                <div class="grid-cards">
                    <?php if(count($materiais) > 0): foreach($materiais as $m): ?>
                        <div class="card material-item" style="padding: 0; overflow: hidden; display: flex; flex-direction: column;">
                            <?php if(!empty($m['capa'])): ?>
                                <img src="<?php echo htmlspecialchars($m['capa']); ?>" alt="Capa" style="width: 100%; height: 160px; object-fit: cover; border-bottom: 1px solid var(--border);">
                            <?php else: ?>
                                <div style="width: 100%; height: 160px; background: rgba(0,0,0,0.02); display: flex; align-items: center; justify-content: center; color: var(--text-muted); font-size: 3em; border-bottom: 1px solid var(--border);">
                                    <i class="fa-solid <?php echo ($m['tipo']??'') === 'minicurso' ? 'fa-video' : (($m['tipo']??'') === 'ebook' ? 'fa-book-open' : 'fa-file-lines'); ?>"></i>
                                </div>
                            <?php endif; ?>
                            
                            <div style="padding: 20px; flex: 1; display: flex; flex-direction: column;">
                                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px;">
                                    <span class="badge" style="background: rgba(0,0,0,0.05); color: var(--text-muted); text-transform: uppercase; font-size: 0.7em;">
                                        <?php 
                                            $tl = ['minicurso'=>'🎬 Curso','ebook'=>'📚 E-book','material'=>'📄 Apoio','indicacao'=>'💡 Indica'];
                                            echo $tl[$m['tipo']??'material'] ?? '📄';
                                        ?>
                                    </span>
                                    <?php if($m['visibilidade'] === 'admin'): ?>
                                        <span class="badge badge-warning"><i class="fa-solid fa-lock"></i> Restrito</span>
                                    <?php else: ?>
                                        <span class="badge badge-success"><i class="fa-solid fa-users"></i> Público</span>
                                    <?php endif; ?>
                                </div>

                                <h3 class="card-title material-titulo" style="margin-top: 0; font-size: 1.1em; line-height: 1.4;"><?php echo $m['titulo']; ?></h3>
                                <p class="material-categoria" style="color: var(--primary); font-weight: 600; font-size: 0.85em; margin-bottom: 10px;">
                                    <?php echo $m['categoria']; ?>
                                </p>

                                <?php if(!empty($m['descricao'])): ?>
                                    <p style="font-size: 0.85em; color: var(--text-muted); margin-bottom: 15px; line-height: 1.5; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                        <?php echo htmlspecialchars($m['descricao']); ?>
                                    </p>
                                <?php endif; ?>

                                <div style="margin-top: auto; display: flex; gap: 8px; justify-content: space-between; border-top: 1px solid var(--border); padding-top: 15px;">
                                    <a href="<?php echo $m['caminho']; ?>" download class="btn btn-outline btn-sm" style="flex: 1; text-align: center; max-height: 38px; display: flex; align-items: center; justify-content: center;">
                                        <i class="fa-solid fa-download"></i> <span style="margin-left:5px;">Baixar</span>
                                    </a>
                                    <button onclick='editarMaterial(<?php echo htmlspecialchars(json_encode($m), ENT_QUOTES, "UTF-8"); ?>)' class="btn btn-primary btn-icon btn-sm" title="Editar">
                                        <i class="fa-solid fa-pen"></i>
                                    </button>
                                    <button onclick="confirmarDelete('material', <?php echo $m['id']; ?>, '<?php echo addslashes($m['titulo']); ?>')" class="btn btn-danger btn-icon btn-sm" title="Excluir">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </div>
                                <div style="margin-top: 10px; font-size: 0.75em; color: var(--text-muted); opacity: 0.7;">
                                    <i class="fa-solid fa-user"></i> <?php echo $m['criado_por'] ?? 'Sistema'; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; else: ?>
                        <div class="card" style="grid-column: 1 / -1;">
                            <p style="text-align: center; color: var(--text-muted); padding: 20px;">
                                <i class="fa-solid fa-folder-open" style="font-size: 3em; display: block; opacity: 0.3; margin-bottom: 15px;"></i>
                                Nenhum material cadastrado ainda.
                            </p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Modais de Materiais movidos para o final do arquivo para melhor organização -->




<?php 
// ### PÁGINA: SUGESTÕES (ADMIN/SUPERADMIN) ###
elseif ($pagina === 'sugestoes'):
    verificarPermissao(ROLE_ADMIN);
    try {
        $sugestoes = $pdo->query("SELECT s.*, p.nome as autor FROM sugestoes s JOIN profissionais p ON s.user_id = p.id ORDER BY s.created_at DESC")->fetchAll();
    } catch (PDOException $e) {
        // Fallback se created_at não existir
        $sugestoes = $pdo->query("SELECT s.*, p.nome as autor FROM sugestoes s JOIN profissionais p ON s.user_id = p.id ORDER BY s.id DESC")->fetchAll();
    }
?>
                <div class="page-header">
                    <h1><i class="fa-solid fa-lightbulb"></i> Caixa de Sugestões</h1>
                    <p style="color: var(--text-muted);">Gerencie as ideias enviadas pelos membros</p>
                </div>

                <div class="grid-cards">
                    <?php if(count($sugestoes) > 0): foreach($sugestoes as $s): ?>
                        <div class="card">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                                <?php 
                                    $badgeClass = $s['status'] === 'Aprovada' ? 'badge-success' : 
                                                  ($s['status'] === 'Em Análise' ? 'badge-warning' : 'badge-info');
                                ?>
                                <span class="badge <?php echo $badgeClass; ?>"><?php echo $s['status']; ?></span>
                                <small style="color: var(--text-muted);"><?php echo formatarData($s['created_at'] ?? $s['data_cadastro'] ?? ''); ?></small>
                            </div>
                            <div style="background: rgba(0,0,0,0.02); padding: 15px; border-radius: 8px; margin-bottom: 15px; border-left: 3px solid var(--primary);">
                                <p style="font-size: 0.95em; line-height: 1.6; margin: 0;">"<?php echo $s['texto']; ?>"</p>
                            </div>
                            <p style="font-size: 0.85em; color: var(--text-muted); margin-bottom: 15px;">
                                <i class="fa-solid fa-user"></i> Por <strong><?php echo $s['autor']; ?></strong>
                            </p>
                            <div style="display: flex; gap: 8px;">
                                <form method="POST" style="flex: 1;">
                                    <input type="hidden" name="acao" value="status_sugestao">
                                    <input type="hidden" name="id_sugestao" value="<?php echo $s['id']; ?>">
                                    <select name="status" class="input-control" onchange="this.form.submit()" 
                                            style="padding: 8px; font-size: 0.85em;">
                                        <option value="nova" <?php if($s['status']=='nova') echo 'selected';?>>Nova</option>
                                        <option value="Em Análise" <?php if($s['status']=='Em Análise') echo 'selected';?>>Em Análise</option>
                                        <option value="Aprovada" <?php if($s['status']=='Aprovada') echo 'selected';?>>Aprovada</option>
                                        <option value="Arquivada" <?php if($s['status']=='Arquivada') echo 'selected';?>>Arquivada</option>
                                    </select>
                                </form>
                                <button onclick="confirmarDelete('sugestao', <?php echo $s['id']; ?>, 'esta sugestão')" 
                                        class="btn btn-danger btn-icon">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; else: ?>
                        <div class="card">
                            <p style="text-align: center; color: var(--text-muted);">
                                <i class="fa-solid fa-inbox" style="font-size: 3em; margin-bottom: 15px; display: block; opacity: 0.3;"></i>
                                Nenhuma sugestão enviada ainda.
                            </p>
                        </div>
                    <?php endif; ?>
                </div>

<?php 
// ### PÁGINA: SOLICITAÇÕES DE ACESSO (ADMIN+) ###
elseif ($pagina === 'solicitacoes'):
    verificarPermissao(ROLE_ADMIN);
    // Busca solicitações pendentes
    try {
        $solicitacoes = $pdo->query("SELECT * FROM profissionais WHERE status = 'pendente' ORDER BY created_at DESC")->fetchAll();
    } catch (PDOException $e) {
        $solicitacoes = $pdo->query("SELECT * FROM profissionais WHERE status = 'pendente' ORDER BY id DESC")->fetchAll();
    }
?>
                <div class="page-header">
                    <h1><i class="fa-solid fa-user-clock"></i> Solicitações de Acesso</h1>
                    <p style="color: var(--text-muted);">Aguardando aprovação para ingressar no sistema</p>
                </div>

                <?php if(count($solicitacoes) > 0): ?>
                    <div class="grid-cards">
                        <?php foreach($solicitacoes as $sol): ?>
                            <div class="card">
                                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 15px;">
                                    <div style="width: 50px; height: 50px; background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 1.5em; font-weight: bold;">
                                        <?php echo strtoupper(substr($sol['nome'], 0, 1)); ?>
                                    </div>
                                    <div style="flex: 1;">
                                        <h3 style="margin: 0; font-size: 1.1em; color: var(--primary);"><?php echo $sol['nome']; ?></h3>
                                        <p style="margin: 5px 0 0; font-size: 0.85em; color: var(--text-muted);">
                                            <?php echo $sol['especialidade']; ?>
                                        </p>
                                    </div>
                                    <span class="badge badge-warning">Pendente</span>
                                </div>

                                <div style="background: rgba(0,0,0,0.02); padding: 15px; border-radius: 8px; margin-bottom: 15px;">
                                    <div style="display: grid; gap: 10px;">
                                        <div>
                                            <i class="fa-solid fa-envelope" style="color: var(--primary); width: 20px;"></i>
                                            <strong style="font-size: 0.85em;">E-mail:</strong><br>
                                            <span style="font-size: 0.9em; margin-left: 28px;"><?php echo $sol['email']; ?></span>
                                        </div>
                                        <div>
                                            <i class="fa-brands fa-whatsapp" style="color: #25D366; width: 20px;"></i>
                                            <strong style="font-size: 0.85em;">WhatsApp:</strong><br>
                                            <span style="font-size: 0.9em; margin-left: 28px;"><?php echo $sol['whatsapp']; ?></span>
                                        </div>
                                        <div>
                                            <i class="fa-solid fa-clock" style="color: var(--text-muted); width: 20px;"></i>
                                            <strong style="font-size: 0.85em;">Solicitado em:</strong><br>
                                            <span style="font-size: 0.9em; margin-left: 28px;">
                                                <?php 
                                                $data = isset($sol['created_at']) ? $sol['created_at'] : ($sol['data_cadastro'] ?? '');
                                                echo !empty($data) ? date('d/m/Y H:i', strtotime($data)) : 'Data não disponível';
                                                ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <div style="border-top: 1px solid #e0e0e0; padding-top: 15px;">
                                    <button onclick="aprovarSolicitacao(<?php echo $sol['id']; ?>, '<?php echo addslashes($sol['nome']); ?>', '<?php echo addslashes($sol['email']); ?>', '<?php echo addslashes($sol['whatsapp']); ?>')" 
                                            class="btn btn-success" style="width: 100%; margin-bottom: 8px;">
                                        <i class="fa-solid fa-check-circle"></i> Aprovar Acesso
                                    </button>
                                    <button onclick="confirmarRejeicao(<?php echo $sol['id']; ?>, '<?php echo addslashes($sol['nome']); ?>')" 
                                            class="btn btn-danger" style="width: 100%;">
                                        <i class="fa-solid fa-times-circle"></i> Rejeitar
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="card" style="text-align: center; padding: 60px 20px;">
                        <i class="fa-solid fa-check-circle" style="font-size: 4em; color: var(--success); opacity: 0.3; margin-bottom: 20px;"></i>
                        <h3 style="color: var(--text-muted); margin: 0;">Nenhuma solicitação pendente</h3>
                        <p style="color: var(--text-muted); margin-top: 10px;">
                            Todas as solicitações foram processadas!
                        </p>
                    </div>
                <?php endif; ?>

<?php 
// ### PÁGINA: CONFIGURAÇÕES (ADMIN E SUPERADMIN) ###
elseif ($pagina === 'configuracoes'):
    verificarPermissao(ROLE_ADMIN);
    
    // Busca configurações atuais
    $config_auto = $pdo->query("SELECT valor FROM configuracoes WHERE chave = 'whatsapp_auto_abrir'")->fetchColumn() ?? '1';
    $config_template = $pdo->query("SELECT valor FROM configuracoes WHERE chave = 'whatsapp_mensagem_template'")->fetchColumn() ?? '';
?>
    <div class="page-header">
        <div class="page-title">
            <i class="fa-solid fa-gear"></i>
            <h1>Configurações do Sistema</h1>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3><i class="fa-brands fa-whatsapp" style="color: #25D366;"></i> Integração WhatsApp</h3>
            <p style="color: var(--text-muted); margin-top: 5px;">
                Configure como o sistema se comporta ao aprovar novos usuários
            </p>
        </div>
        <div class="card-body">
            <form method="POST" style="max-width: 800px;">
                <input type="hidden" name="acao" value="salvar_configuracoes">
                
                <!-- Opção de Abrir WhatsApp Automaticamente -->
                <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 25px;">
                    <label style="display: flex; align-items: center; cursor: pointer; font-size: 16px;">
                        <input type="checkbox" name="whatsapp_auto_abrir" value="1" <?php if($config_auto == '1') echo 'checked'; ?> 
                               style="width: 20px; height: 20px; margin-right: 12px; cursor: pointer;">
                        <div>
                            <strong>🚀 Abrir WhatsApp Web automaticamente ao aprovar usuário</strong>
                            <p style="color: var(--text-muted); margin: 5px 0 0 0; font-size: 14px;">
                                Quando ativado, ao aprovar um usuário o WhatsApp Web será aberto automaticamente 
                                com a mensagem pronta para enviar. Se desativado, apenas mostrará os dados na tela.
                            </p>
                        </div>
                    </label>
                </div>

                <!-- Template da Mensagem -->
                <div style="margin-bottom: 20px;">
                    <label class="form-label">
                        <strong>📝 Mensagem do WhatsApp</strong>
                        <span style="font-size: 12px; color: var(--text-muted); font-weight: normal;">
                            (Use as variáveis: {NOME}, {LINK}, {EMAIL}, {SENHA})
                        </span>
                    </label>
                    <textarea 
                        name="whatsapp_mensagem_template" 
                        rows="12" 
                        class="form-control" 
                        style="font-family: monospace; font-size: 14px; line-height: 1.6;"
                        required
                    ><?php echo htmlspecialchars($config_template); ?></textarea>
                    <small style="color: var(--text-muted); display: block; margin-top: 8px;">
                        💡 <strong>Dica:</strong> Use *texto* para negrito e _texto_ para itálico no WhatsApp
                    </small>
                </div>

                <!-- Preview -->
                <div style="background: #e7f3ff; border-left: 4px solid #2196F3; padding: 15px; border-radius: 5px; margin-bottom: 25px;">
                    <strong style="color: #1976D2;">🔍 Preview da mensagem:</strong>
                    <div id="preview-mensagem" style="margin-top: 10px; white-space: pre-wrap; font-family: monospace; font-size: 13px; color: #333;">
                        <?php 
                        $preview = str_replace(
                            ['{NOME}', '{LINK}', '{EMAIL}', '{SENHA}'],
                            ['<strong>João Silva</strong>', '<strong>https://melodias.karengomes.com.br/login</strong>', '<strong>joao@email.com</strong>', '<strong>melodias123</strong>'],
                            htmlspecialchars($config_template)
                        );
                        echo $preview;
                        ?>
                    </div>
                </div>

                <!-- Botões -->
                <div style="display: flex; gap: 10px;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-save"></i> Salvar Configurações
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="restaurarPadrao()">
                        <i class="fa-solid fa-rotate-left"></i> Restaurar Padrão
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Atualiza preview em tempo real
        document.querySelector('textarea[name="whatsapp_mensagem_template"]').addEventListener('input', function(e) {
            let texto = e.target.value;
            texto = texto.replace(/\{NOME\}/g, '<strong>João Silva</strong>');
            texto = texto.replace(/\{LINK\}/g, '<strong>https://melodias.karengomes.com.br/login</strong>');
            texto = texto.replace(/\{EMAIL\}/g, '<strong>joao@email.com</strong>');
            texto = texto.replace(/\{SENHA\}/g, '<strong>melodias123</strong>');
            document.getElementById('preview-mensagem').innerHTML = texto;
        });

        function restaurarPadrao() {
            const mensagemPadrao = `🎉 *Bem-vindo(a) ao Melodias!*

Olá {NOME}, sua solicitação foi *aprovada*!

📋 *Seus dados de acesso:*

🔗 *Link:*
{LINK}

📧 *Email/Login:*
{EMAIL}

🔑 *Senha Temporária:*
melodias123

⚠️ _Recomendamos trocar sua senha após o primeiro acesso._

✨ Agora você faz parte da nossa rede de profissionais em saúde mental!`;
            
            document.querySelector('textarea[name="whatsapp_mensagem_template"]').value = mensagemPadrao;
            document.querySelector('textarea[name="whatsapp_mensagem_template"]').dispatchEvent(new Event('input'));
        }
    </script>

<?php 
// ### PÁGINA: GESTÃO DE USUÁRIOS (APENAS SUPERADMIN) ###
elseif ($pagina === 'usuarios'):
    verificarPermissao(ROLE_SUPERADMIN);
    try {
        $usuarios = $pdo->query("SELECT * FROM profissionais ORDER BY created_at DESC")->fetchAll();
    } catch (PDOException $e) {
        // Fallback se created_at não existir
        $usuarios = $pdo->query("SELECT * FROM profissionais ORDER BY id DESC")->fetchAll();
    }
?>
                <div class="page-header-actions">
                    <div>
                        <h1><i class="fa-solid fa-users-gear"></i> Gestão de Usuários</h1>
                        <p style="color: var(--text-muted);">Controle total sobre usuários e permissões</p>
                    </div>
                    <button onclick="openModal('modalCriarUsuario')" class="btn btn-primary">
                        <i class="fa-solid fa-user-plus"></i> Novo Usuário
                    </button>
                </div>

                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Usuário</th>
                                <th>Contato</th>
                                <th>Permissão</th>
                                <th>Status</th>
                                <th style="text-align: right;">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($usuarios as $u): ?>
                            <tr>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 12px;">
                                        <div style="width: 35px; height: 35px; background: linear-gradient(135deg, var(--primary), var(--accent)); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 700;">
                                            <?php echo strtoupper(substr($u['nome'], 0, 1)); ?>
                                        </div>
                                        <div>
                                            <strong><?php echo $u['nome']; ?></strong>
                                            <div style="font-size: 0.8em; color: var(--text-muted);">
                                                <?php echo htmlspecialchars(formatarProfissao($u['especialidade'] ?? 'Sem especialidade', $u['genero'] ?? '')); ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td style="font-size: 0.85em;">
                                    <div style="margin-bottom: 4px;">
                                        <i class="fa-solid fa-envelope" style="width: 16px;"></i> <?php echo $u['email']; ?>
                                    </div>
                                    <?php if($u['whatsapp']): ?>
                                    <div>
                                        <i class="fa-brands fa-whatsapp" style="width: 16px; color: #10b981;"></i> <?php echo $u['whatsapp']; ?>
                                    </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php 
                                        if($u['role'] === ROLE_SUPERADMIN) {
                                            echo '<span class="badge badge-purple"><i class="fa-solid fa-crown"></i> Super Admin</span>';
                                        } elseif($u['role'] === ROLE_ADMIN) {
                                            echo '<span class="badge badge-danger"><i class="fa-solid fa-shield"></i> Admin</span>';
                                        } elseif($u['role'] === ROLE_EDITOR) {
                                            echo '<span class="badge" style="background:var(--accent); color:white;"><i class="fa-solid fa-pen-nib"></i> Editor</span>';
                                        } else {
                                            echo '<span class="badge badge-info"><i class="fa-solid fa-user"></i> Membro</span>';
                                        }
                                    ?>
                                </td>
                                <td>
                                    <?php if($u['status'] === 'ativo'): ?>
                                        <span class="badge badge-success"><i class="fa-solid fa-check-circle"></i> Ativo</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger"><i class="fa-solid fa-ban"></i> Inativo</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: right;">
                                    <div style="display: flex; gap: 8px; justify-content: flex-end;">
                                        <button onclick='editarUsuario(<?php echo htmlspecialchars(json_encode($u, JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, "UTF-8"); ?>)' 
                                                class="btn btn-outline btn-icon btn-sm" title="Editar">
                                            <i class="fa-solid fa-pen"></i>
                                        </button>
                                        
                                        <?php if($u['id'] != $id_usuario): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="acao" value="toggle_status_usuario">
                                                <input type="hidden" name="id_user" value="<?php echo $u['id']; ?>">
                                                <input type="hidden" name="status" value="<?php echo $u['status'] === 'ativo' ? 'inativo' : 'ativo'; ?>">
                                                <button type="submit" class="btn btn-<?php echo $u['status'] === 'ativo' ? 'warning' : 'success'; ?> btn-icon btn-sm" 
                                                        title="<?php echo $u['status'] === 'ativo' ? 'Desativar' : 'Ativar'; ?>">
                                                    <i class="fa-solid fa-<?php echo $u['status'] === 'ativo' ? 'lock' : 'unlock'; ?>"></i>
                                                </button>
                                            </form>
                                            
                                            <button onclick="confirmarDelete('usuario', <?php echo $u['id']; ?>, '<?php echo addslashes($u['nome']); ?>')" 
                                                    class="btn btn-danger btn-icon btn-sm" title="Excluir">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Nova Seção: Convites Externos -->
                <div style="margin-top: 40px; border-top: 1px solid var(--border); padding-top: 30px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
                        <div>
                            <h2 style="font-size: 1.4em; font-weight: 800; display: flex; align-items: center; gap: 10px;">
                                <i class="fa-solid fa-paper-plane" style="color: var(--primary);"></i> Links de Convite Externos
                            </h2>
                            <p style="color: var(--text-muted); font-size: 0.9em;">Gere links para pessoas se cadastrem sozinhas com limites e validade.</p>
                        </div>
                        <button onclick="openModal('modalGerarConvite')" class="btn btn-outline" style="border-radius: 10px; font-weight: 600;">
                            <i class="fa-solid fa-link"></i> Gerar Novo Link
                        </button>
                    </div>

                    <?php 
                        try {
                            $convites = $pdo->query("SELECT * FROM convites ORDER BY created_at DESC")->fetchAll();
                        } catch (Exception $e) {
                            $convites = [];
                        }
                        if(empty($convites)):
                    ?>
                        <div style="text-align: center; padding: 40px; background: rgba(0,0,0,0.02); border: 2px dashed var(--border); border-radius: 20px;">
                            <i class="fa-solid fa-link-slash" style="font-size: 2em; color: var(--text-muted); margin-bottom: 15px; opacity: 0.5;"></i>
                            <p style="color: var(--text-muted); font-weight: 600;">Nenhum link de convite ativo.</p>
                        </div>
                    <?php else: ?>
                        <div class="grid-cards" style="grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));">
                            <?php foreach($convites as $c): 
                                $expirado = strtotime($c['expira_em']) < time();
                                $esgotado = $c['usos_atuais'] >= $c['limite_usos'];
                                $cor = ($expirado || $esgotado) ? 'var(--text-muted)' : 'var(--primary)';
                                $full_url = "https://".$_SERVER['HTTP_HOST']. dirname($_SERVER['PHP_SELF']) ."/registro.php?token=".$c['token'];
                            ?>
                                <div class="card" style="padding: 20px; position: relative; opacity: <?php echo ($expirado || $esgotado) ? '0.7' : '1'; ?>;">
                                    <div style="display: flex; justify-content: space-between; margin-bottom: 15px;">
                                        <div style="background: rgba(110,43,58,0.1); width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; color: <?php echo $cor; ?>;">
                                            <i class="fa-solid fa-link"></i>
                                        </div>
                                        <?php if($expirado): ?>
                                            <span class="badge badge-danger">Expirado</span>
                                        <?php elseif($esgotado): ?>
                                            <span class="badge badge-warning">Esgotado</span>
                                        <?php else: ?>
                                            <span class="badge badge-success">Ativo</span>
                                        <?php endif; ?>
                                    </div>
                                    <h4 style="margin-bottom: 5px; font-weight: 700;">Cargo: <?php echo ucfirst($c['role_atribuida']); ?></h4>
                                    <p style="font-size: 0.8em; color: var(--text-muted); margin-bottom: 15px;">
                                        <i class="fa-solid fa-users" style="width: 15px;"></i> <?php echo $c['usos_atuais']; ?> / <?php echo $c['limite_usos']; ?> inscrições<br>
                                        <i class="fa-solid fa-clock" style="width: 15px;"></i> Expira: <?php echo date('d/m/Y H:i', strtotime($c['expira_em'])); ?>
                                    </p>
                                    <div style="display: flex; gap: 8px;">
                                        <button onclick='copiarLink("<?php echo $full_url; ?>")' class="btn btn-primary btn-sm" style="flex: 1; border-radius: 8px;">
                                            <i class="fa-solid fa-copy"></i> Copiar Link
                                        </button>
                                        <form method="POST" style="flex: 0;">
                                            <input type="hidden" name="acao" value="delete_convite">
                                            <input type="hidden" name="id_convite" value="<?php echo $c['id']; ?>">
                                            <button type="submit" class="btn btn-danger btn-sm" style="border-radius: 8px; width: 38px;" title="Remover">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
<?php 
// ### PÁGINA: FÓRUM ###
elseif ($pagina === 'forum'):
    $post_id = $_GET['post'] ?? null;
    
    // ==========================================
    // MODO: VIEW ÚNICA DO POST
    // ==========================================
    if ($post_id): 
        // Incrementa views
        $pdo->prepare("UPDATE forum_posts SET views = views + 1 WHERE id = ?")->execute([$post_id]);
        
        $stmt = $pdo->prepare("SELECT f.*, p.nome as autor_nome, p.role as autor_role 
                              FROM forum_posts f 
                              JOIN profissionais p ON f.user_id = p.id 
                              WHERE f.id = ? AND f.status = 'ativo'");
        $stmt->execute([$post_id]);
        $post = $stmt->fetch();
        
        if (!$post):
            echo "<div class='page-header'><h1>🚫 Tópico não encontrado</h1><a href='?page=forum' class='btn btn-outline'>Voltar ao Fórum</a></div>";
        else:
            // Comentários
            $stmtC = $pdo->prepare("SELECT c.*, p.nome as autor_nome, p.role as autor_role 
                                    FROM forum_comentarios c JOIN profissionais p ON c.user_id = p.id 
                                    WHERE c.post_id = ? ORDER BY c.created_at ASC");
            $stmtC->execute([$post_id]);
            $comentarios = $stmtC->fetchAll();
            
            // Likes
            $curtido = $pdo->query("SELECT COUNT(*) FROM forum_curtidas WHERE post_id = $post_id AND user_id = $id_usuario")->fetchColumn() > 0;
            $total_likes = $pdo->query("SELECT COUNT(*) FROM forum_curtidas WHERE post_id = $post_id")->fetchColumn();
            
            $cat_map = [
                'duvidas'   => ['icon'=>'❓','label'=>'Dúvidas','color'=>'#f59e0b','bg'=>'rgba(245,158,11,0.1)'],
                'discussao' => ['icon'=>'💭','label'=>'Discussão','color'=>'#6366f1','bg'=>'rgba(99,102,241,0.1)'],
                'recursos'  => ['icon'=>'📚','label'=>'Recursos','color'=>'#10b981','bg'=>'rgba(16,185,129,0.1)'],
                'anuncios'  => ['icon'=>'📢','label'=>'Anúncios','color'=>'#3b82f6','bg'=>'rgba(59,130,246,0.1)'],
                'geral'     => ['icon'=>'💬','label'=>'Geral','color'=>'var(--primary)','bg'=>'rgba(110,43,58,0.1)'],
            ];
            $cat = $cat_map[$post['categoria']] ?? $cat_map['geral'];
?>
<style>
.post-view-header { background: var(--bg-card); padding: 30px; border-radius: 16px; box-shadow: var(--shadow); border: 1px solid var(--border); border-top: 5px solid <?php echo $cat['color']; ?>; margin-bottom: 20px; }
.post-meta-top { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 15px; flex-wrap: wrap; gap: 10px; }
.post-badge { display: inline-flex; align-items: center; gap: 6px; padding: 6px 14px; border-radius: 20px; background: <?php echo $cat['bg']; ?>; color: <?php echo $cat['color']; ?>; font-weight: 700; font-size: 0.8em; text-transform: uppercase; letter-spacing: 0.5px; }
.post-title-lg { font-size: 1.8em; font-weight: 800; color: var(--text-main); margin-bottom: 20px; line-height: 1.3; }
.post-author-row { display: flex; align-items: center; gap: 12px; }
.post-av { width: 44px; height: 44px; border-radius: 50%; background: linear-gradient(135deg, var(--primary), var(--accent)); color: white; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 1.1em; flex-shrink: 0; }
.post-author-info .pa-name { font-weight: 700; color: var(--text-main); font-size: 0.95em; }
.post-author-info .pa-role { font-size: 0.75em; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; margin-top: 2px; }
.post-author-info .pa-role.admin { color: var(--danger); font-weight: 700; }
.post-date { font-size: 0.85em; color: var(--text-muted); margin-top: 5px; }

.post-content-body { background: var(--bg-card); padding: 30px; border-radius: 16px; box-shadow: var(--shadow); border: 1px solid var(--border); margin-bottom: 20px; font-size: 1.05em; line-height: 1.8; color: var(--text-main); white-space: pre-wrap; word-break: break-word; }
.post-actions-bar { display: flex; align-items: center; justify-content: space-between; padding: 15px 30px; background: var(--bg-card); border-radius: 16px; box-shadow: var(--shadow); border: 1px solid var(--border); margin-bottom: 30px; flex-wrap: wrap; gap: 10px; }
.post-actions-left { display: flex; align-items: center; gap: 15px; }
.post-actions-right { display: flex; align-items: center; gap: 15px; }
.action-stat { font-size: 0.9em; font-weight: 600; color: var(--text-muted); display: inline-flex; align-items: center; gap: 6px; }

.comments-section { margin-top: 40px; }
.comments-title { font-size: 1.3em; font-weight: 800; margin-bottom: 20px; color: var(--text-main); display: flex; align-items: center; gap: 10px; }
.comment-box { display: flex; gap: 16px; margin-bottom: 20px; }
.comment-bubble { background: var(--bg-card); border: 1px solid var(--border); padding: 18px; border-radius: 0 16px 16px 16px; flex: 1; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
.comment-header { display: flex;justify-content: space-between; margin-bottom: 8px; align-items: center; }
.comment-author { font-weight: 700; font-size: 0.9em; color: var(--text-main); display:flex; align-items:center; gap: 8px;}
.comment-date { font-size: 0.75em; color: var(--text-muted); }
.comment-text { font-size: 0.95em; color: var(--text-main); line-height: 1.6; white-space: pre-wrap; }

.reply-form { background: var(--bg-card); border: 1px solid var(--border); padding: 20px; border-radius: 16px; margin-top: 30px; box-shadow: var(--shadow); }
.reply-form h4 { margin-bottom: 15px; font-size: 1.1em; color: var(--text-main); }
.reply-textarea { width: 100%; border: 1.5px solid var(--border); border-radius: 12px; padding: 15px; font-size: 0.95em; font-family: inherit; resize: vertical; min-height: 120px; margin-bottom: 15px; background: var(--bg-body); color: var(--text-main); transition: var(--transition); }
.reply-textarea:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(110,43,58,0.1); background: var(--bg-card); }

/* Buttons inside view */
.btn-like-lg { padding: 10px 20px; border-radius: 30px; font-weight: 700; font-size: 0.9em; display: inline-flex; align-items: center; gap: 8px; cursor: pointer; border: 1.5px solid var(--border); background: transparent; color: var(--text-muted); transition: var(--transition); }
.btn-like-lg:hover, .btn-like-lg.ativo { border-color: #ef4444; color: #ef4444; background: rgba(239,68,68,0.05); }

@media(max-width: 768px) {
    .post-view-header, .post-content-body, .post-actions-bar, .reply-form { padding: 20px; }
    .post-title-lg { font-size: 1.4em; }
    .comment-box { gap: 10px; }
}
</style>

<div style="margin-bottom: 20px;">
    <a href="?page=forum" class="btn btn-outline" style="border:none;background:rgba(0,0,0,0.05);"><i class="fa-solid fa-arrow-left"></i> Voltar ao Fórum</a>
</div>

<div class="post-view-header">
    <div class="post-meta-top">
        <span class="post-badge"><?php echo $cat['icon'].' '.$cat['label']; ?></span>
        
        <?php if($role === ROLE_ADMIN || $role === ROLE_SUPERADMIN): ?>
        <button type="button" onclick="confirmarDelete('post_forum', <?php echo $post['id']; ?>, <?php echo htmlspecialchars(json_encode($post['titulo'], JSON_HEX_APOS | JSON_HEX_QUOT)); ?>)" class="btn btn-danger btn-sm" title="Apagar Tópico">
            <i class="fa-solid fa-trash"></i> Apagar
        </button>
        <?php endif; ?>
    </div>
    
    <h1 class="post-title-lg"><?php echo htmlspecialchars($post['titulo']); ?></h1>
    
    <div class="post-author-row">
        <div class="post-av"><?php echo strtoupper(substr($post['autor_nome'],0,1)); ?></div>
        <div class="post-author-info">
            <div class="pa-name"><?php echo htmlspecialchars($post['autor_nome']); ?></div>
            <div class="pa-role <?php echo ($post['autor_role']==='admin'||$post['autor_role']==='superadmin')?'admin':''; ?>">
                <?php echo ($post['autor_role']==='admin'||$post['autor_role']==='superadmin')?'🛡️ Moderador':'👤 Membro'; ?>
            </div>
            <div class="post-date"><i class="fa-regular fa-clock"></i> Publicado em <?php echo date('d/m/Y H:i', strtotime($post['created_at'])); ?></div>
        </div>
    </div>
</div>

<div class="post-content-body">
    <?php echo htmlspecialchars($post['conteudo']); ?>
</div>

<div class="post-actions-bar">
    <div class="post-actions-left">
        <button class="btn-like-lg <?php echo $curtido?'ativo':''; ?>" onclick="curtirPost(<?php echo $post['id']; ?>, true)">
            <i class="fa-<?php echo $curtido?'solid':'regular'; ?> fa-heart"></i> <?php echo $curtido?'Curtiu':'Curtir'; ?> (<?php echo $total_likes; ?>)
        </button>
    </div>
    <div class="post-actions-right">
        <span class="action-stat"><i class="fa-solid fa-eye"></i> <?php echo $post['views']; ?> visualizações</span>
        <span class="action-stat"><i class="fa-solid fa-comments"></i> <?php echo count($comentarios); ?> respostas</span>
    </div>
</div>

<div class="comments-section" id="respostas">
    <h3 class="comments-title"><i class="fa-regular fa-comments"></i> Respostas da Comunidade</h3>
    
    <?php if(empty($comentarios)): ?>
        <div style="text-align:center; padding: 40px; background: rgba(0,0,0,0.02); border-radius: 12px; color: var(--text-muted); margin-bottom: 20px;">
            <i class="fa-regular fa-comment-dots" style="font-size: 3em; opacity: 0.3; margin-bottom: 10px;"></i>
            <p>Nenhum comentário ainda. Seja o primeiro a responder!</p>
        </div>
    <?php else:
        foreach($comentarios as $c): 
            $is_admin = ($c['autor_role']==='admin' || $c['autor_role']==='superadmin');
            $is_owner = ($c['autor_nome'] === $post['autor_nome']);
    ?>
        <div class="comment-box">
            <div class="post-av" style="width:38px;height:38px;font-size:0.9em;"><?php echo strtoupper(substr($c['autor_nome'],0,1)); ?></div>
            <div class="comment-bubble">
                <div class="comment-header">
                    <div class="comment-author">
                        <?php echo htmlspecialchars($c['autor_nome']); ?>
                        <?php if($is_admin): ?><span style="color:var(--danger);font-size:0.8em;" title="Moderador"><i class="fa-solid fa-shield"></i></span><?php endif; ?>
                        <?php if($is_owner): ?><span style="background:var(--primary);color:white;padding:2px 6px;border-radius:10px;font-size:0.7em;font-weight:700;">AUTOR</span><?php endif; ?>
                    </div>
                    <div style="display:flex; align-items:center; gap:10px;">
                        <div class="comment-date"><?php echo date('d/m/Y H:i', strtotime($c['created_at'])); ?></div>
                        
                        <?php if($role === ROLE_ADMIN || $role === ROLE_SUPERADMIN || $c['user_id'] == $id_usuario): ?>
                            <div class="comment-actions" style="display:flex; gap:5px;">
                                <button onclick='abrirEditarComentario(<?php echo $c['id']; ?>, <?php echo htmlspecialchars(json_encode($c['comentario'], JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, "UTF-8"); ?>)' class="btn-icon" style="background:none; border:none; color:var(--text-muted); cursor:pointer; font-size:0.8em;" title="Editar"><i class="fa-solid fa-pen"></i></button>
                                <?php if($role === ROLE_ADMIN || $role === ROLE_SUPERADMIN): ?>
                                    <button onclick="confirmarDelete('comentario', <?php echo $c['id']; ?>)" class="btn-icon" style="background:none; border:none; color:var(--danger); cursor:pointer; font-size:0.8em;" title="Excluir"><i class="fa-solid fa-trash"></i></button>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="comment-text" id="coment-text-<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['comentario']); ?></div>
            </div>
        </div>
    <?php endforeach; endif; ?>

    <!-- Modal Editar Comentário -->
    <div class="modal-overlay" id="modalEditComentario">
        <div class="modal-content" style="max-width:500px;">
            <div class="modal-header">
                <h2><i class="fa-solid fa-pen"></i> Editar Comentário</h2>
                <button class="close-modal" onclick="closeModal('modalEditComentario')"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <input type="hidden" name="acao" value="edit_comentario">
                    <input type="hidden" name="comentario_id" id="edit_coment_id">
                    <div class="input-group">
                        <textarea name="comentario" id="edit_coment_text" class="reply-textarea" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">Salvar Alterações</button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="reply-form">
        <h4>Deixe sua resposta</h4>
        <form method="POST" action="">
            <input type="hidden" name="acao" value="comentar_post">
            <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
            <textarea name="comentario" class="reply-textarea" placeholder="Escreva seu comentário..." required></textarea>
            <div style="display:flex; justify-content:flex-end;">
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-reply"></i> Enviar Resposta</button>
            </div>
        </form>
    </div>
</div>

<script>
    function abrirEditarComentario(id, texto) {
        document.getElementById('edit_coment_id').value = id;
        document.getElementById('edit_coment_text').value = texto;
        openModal('modalEditComentario');
    }

function curtirPost(postId, reload = false) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = `<input type="hidden" name="acao" value="curtir_post"><input type="hidden" name="post_id" value="${postId}">`;
    document.body.appendChild(form);
    form.submit();
}

function abrirEditarComentario(id, texto) {
    document.getElementById('edit_coment_id').value = id;
    document.getElementById('edit_coment_text').value = texto;
    openModal('modalEditComentario');
}
</script>


<?php 
        endif; // endif post found
    
    // ==========================================
    // MODO: INDEX (LISTA DO FÓRUM)
    // ==========================================
    else:
        $categoria_filtro = $_GET['cat'] ?? 'todos';
        
        $query = "SELECT f.*, p.nome as autor_nome, 
                  (SELECT COUNT(*) FROM forum_comentarios WHERE post_id = f.id) as num_comentarios,
                  (SELECT COUNT(*) FROM forum_curtidas WHERE post_id = f.id) as num_curtidas,
                  (SELECT COUNT(*) FROM forum_curtidas WHERE post_id = f.id AND user_id = :user_id) as user_curtiu
                  FROM forum_posts f 
                  JOIN profissionais p ON f.user_id = p.id 
                  WHERE f.status = 'ativo'";
        
        if ($categoria_filtro !== 'todos') {
            $query .= " AND f.categoria = :categoria";
        }
        $query .= " ORDER BY f.created_at DESC";
        
        $stmt = $pdo->prepare($query);
        $stmt->bindValue(':user_id', $id_usuario, PDO::PARAM_INT);
        if ($categoria_filtro !== 'todos') {
            $stmt->bindValue(':categoria', $categoria_filtro, PDO::PARAM_STR);
        }
        $stmt->execute();
        $posts = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $posts[] = $row;
        }
        
        // Stats do fórum
        $forum_total_posts = 0; $forum_total_coments = 0; $forum_total_membros = 0;
        try {
            $forum_total_posts   = $pdo->query("SELECT COUNT(*) FROM forum_posts WHERE status='ativo'")->fetchColumn();
            $forum_total_coments = $pdo->query("SELECT COUNT(*) FROM forum_comentarios")->fetchColumn();
            $forum_total_membros = $pdo->query("SELECT COUNT(DISTINCT user_id) FROM forum_posts WHERE status='ativo'")->fetchColumn();
        } catch(Exception $e) {}
?>
<!-- ===== FÓRUM PREMIUM ===== -->
<style>
.forum-stats-bar{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:28px;}
.forum-stat-item{background:var(--bg-card);border-radius:14px;padding:18px 22px;display:flex;align-items:center;gap:16px;box-shadow:var(--shadow);border:1px solid var(--border);transition:var(--transition);}
.forum-stat-item:hover{transform:translateY(-2px);box-shadow:var(--shadow-lg);}
.forum-stat-icon{width:46px;height:46px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.3em;flex-shrink:0;}
.forum-stat-info .fsnum{font-size:1.8em;font-weight:800;line-height:1;color:var(--text-main);}
.forum-stat-info .fslbl{font-size:0.75em;color:var(--text-muted);font-weight:600;text-transform:uppercase;letter-spacing:0.5px;margin-top:2px;}
.forum-toolbar{display:flex;align-items:center;gap:14px;margin-bottom:18px;flex-wrap:wrap;}
.forum-sw{flex:1;min-width:200px;position:relative;}
.forum-sw i{position:absolute;left:14px;top:50%;transform:translateY(-50%);color:var(--text-muted);pointer-events:none;}
.forum-sw input{width:100%;padding:11px 15px 11px 40px;border-radius:30px;border:1.5px solid var(--border);background:var(--bg-body);color:var(--text-main);font-size:0.9em;transition:var(--transition);font-family:inherit;}
.forum-sw input:focus{outline:none;border-color:var(--primary);box-shadow:0 0 0 3px rgba(110,43,58,0.1);}
.filter-pills{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:24px;}
.filter-pill{padding:7px 16px;border-radius:30px;font-size:0.82em;font-weight:600;text-decoration:none;border:1.5px solid var(--border);color:var(--text-muted);transition:var(--transition);background:var(--bg-body);white-space:nowrap;}
.filter-pill:hover{border-color:var(--primary);color:var(--primary);background:rgba(110,43,58,0.04);}
.filter-pill.active{background:var(--primary);color:white;border-color:var(--primary);}
.forum-grid-v2{display:grid;grid-template-columns:repeat(auto-fill,minmax(340px,1fr));gap:20px;}
.fcat-duvidas{--cat-color:#f59e0b;--cat-bg:rgba(245,158,11,0.1);}
.fcat-discussao{--cat-color:#6366f1;--cat-bg:rgba(99,102,241,0.1);}
.fcat-recursos{--cat-color:#10b981;--cat-bg:rgba(16,185,129,0.1);}
.fcat-anuncios{--cat-color:#3b82f6;--cat-bg:rgba(59,130,246,0.1);}
.fcat-geral{--cat-color:var(--primary);--cat-bg:rgba(110,43,58,0.1);}
.forum-card-v2{background:var(--bg-card);border-radius:16px;padding:22px;box-shadow:var(--shadow);border:1px solid var(--border);border-top:4px solid var(--cat-color,var(--primary));transition:var(--transition);display:flex;flex-direction:column;gap:12px;position:relative;}
.forum-card-v2:hover{transform:translateY(-4px);box-shadow:var(--shadow-lg);}
.fcard-admin-action { position:absolute; top: 15px; right: 15px; z-index: 10; }
.fcard-cat-badge{display:inline-flex;align-items:center;gap:6px;padding:4px 12px;border-radius:20px;background:var(--cat-bg);color:var(--cat-color);font-size:0.72em;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;width:fit-content;}
.fcard-title{font-size:1.15em;font-weight:800;line-height:1.4;color:var(--text-main);}
.fcard-title a{text-decoration:none;color:inherit;transition:var(--transition);}
.fcard-title a:hover{color:var(--primary);}
.fcard-excerpt{font-size:0.88em;color:var(--text-muted);line-height:1.6;flex:1;}
.fcard-footer{display:flex;align-items:center;justify-content:space-between;padding-top:12px;border-top:1px solid var(--border);gap:10px;}
.fcard-author{display:flex;align-items:center;gap:8px;min-width:0;}
.fcard-avatar{width:30px;height:30px;border-radius:50%;background:linear-gradient(135deg,var(--primary),var(--accent));color:white;font-weight:700;font-size:0.75em;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
.fcard-author-name{font-size:0.82em;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:120px;}
.fcard-actions{display:flex;align-items:center;gap:10px;font-size:0.8em;color:var(--text-muted);flex-shrink:0;}
.fcard-stat{display:flex;align-items:center;gap:4px;font-weight:600;}
.btn-curtir{display:inline-flex;align-items:center;gap:5px;padding:5px 10px;border-radius:20px;border:1.5px solid var(--border);background:transparent;cursor:pointer;font-size:0.8em;font-weight:600;color:var(--text-muted);transition:var(--transition);font-family:inherit;}
.btn-curtir:hover,.btn-curtir.curtido{border-color:#ef4444;color:#ef4444;background:rgba(239,68,68,0.06);}
.forum-empty-v2{grid-column:1/-1;text-align:center;padding:80px 20px;color:var(--text-muted);}
.forum-empty-v2 i{font-size:4em;margin-bottom:20px;display:block;opacity:0.4;}
.forum-empty-v2 h3{font-size:1.3em;margin-bottom:8px;}
@media(max-width:768px){.forum-stats-bar{grid-template-columns:1fr 1fr;}.forum-grid-v2{grid-template-columns:1fr;}}
</style>

<div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:16px;margin-bottom:26px;">
    <div>
        <h1 style="font-size:1.9em;font-weight:800;margin:0 0 4px 0;">💬 Fórum Comunitário</h1>
        <p style="color:var(--text-muted);margin:0;font-size:0.95em;">Compartilhe conhecimentos e conecte-se com a rede</p>
    </div>
    <button class="btn btn-primary" onclick="openModal('modalNovoPost')" style="box-shadow: 0 4px 15px rgba(110,43,58,0.3);">
        <i class="fa-solid fa-pen-to-square"></i> Criar Tópico
    </button>
</div>

<div class="forum-stats-bar">
    <div class="forum-stat-item">
        <div class="forum-stat-icon" style="background:rgba(99,102,241,0.1);color:#6366f1;"><i class="fa-solid fa-newspaper"></i></div>
        <div class="forum-stat-info"><div class="fsnum"><?php echo $forum_total_posts; ?></div><div class="fslbl">Tópicos Ativos</div></div>
    </div>
    <div class="forum-stat-item">
        <div class="forum-stat-icon" style="background:rgba(16,185,129,0.1);color:#10b981;"><i class="fa-solid fa-comments"></i></div>
        <div class="forum-stat-info"><div class="fsnum"><?php echo $forum_total_coments; ?></div><div class="fslbl">Comentários</div></div>
    </div>
    <div class="forum-stat-item">
        <div class="forum-stat-icon" style="background:rgba(110,43,58,0.1);color:var(--primary);"><i class="fa-solid fa-users"></i></div>
        <div class="forum-stat-info"><div class="fsnum"><?php echo $forum_total_membros; ?></div><div class="fslbl">Participantes</div></div>
    </div>
</div>

<div class="forum-toolbar">
    <div class="forum-sw">
        <i class="fa-solid fa-magnifying-glass"></i>
        <input type="text" id="forumSearch" placeholder="Pesquisar títulos ou assunto..." oninput="filtrarForum(this.value)">
    </div>
</div>

<div class="filter-pills">
    <a href="?page=forum&cat=todos"     class="filter-pill <?php echo $categoria_filtro==='todos'?'active':''; ?>">🌐 Todos</a>
    <a href="?page=forum&cat=duvidas"   class="filter-pill <?php echo $categoria_filtro==='duvidas'?'active':''; ?>">❓ Dúvidas</a>
    <a href="?page=forum&cat=discussao" class="filter-pill <?php echo $categoria_filtro==='discussao'?'active':''; ?>">💭 Discussão</a>
    <a href="?page=forum&cat=recursos"  class="filter-pill <?php echo $categoria_filtro==='recursos'?'active':''; ?>">📚 Recursos</a>
    <a href="?page=forum&cat=anuncios"  class="filter-pill <?php echo $categoria_filtro==='anuncios'?'active':''; ?>">📢 Anúncios</a>
</div>

<div class="forum-grid-v2" id="forumGrid">
<?php if (empty($posts)): ?>
    <div class="forum-empty-v2">
        <i class="fa-regular fa-comments"></i>
        <h3>Nenhum tópico encontrado</h3>
        <p>Seja o primeiro a publicar<?php echo $categoria_filtro!=='todos'?' nesta categoria':''; ?>!</p>
        <button class="btn btn-primary" onclick="openModal('modalNovoPost')" style="margin-top:20px;">
            <i class="fa-solid fa-plus"></i> Criar Primeiro Tópico
        </button>
    </div>
<?php else:
    $cat_map = [
        'duvidas'   => ['icon'=>'❓','class'=>'fcat-duvidas'],
        'discussao' => ['icon'=>'💭','class'=>'fcat-discussao'],
        'recursos'  => ['icon'=>'📚','class'=>'fcat-recursos'],
        'anuncios'  => ['icon'=>'📢','class'=>'fcat-anuncios'],
        'geral'     => ['icon'=>'💬','class'=>'fcat-geral'],
    ];
    foreach ($posts as $post):
        $cat = $cat_map[$post['categoria']] ?? $cat_map['geral'];
        $curtido = (int)($post['user_curtiu'] ?? 0) > 0;
?>
    <div class="forum-card-v2 <?php echo $cat['class']; ?> forum-item"
         data-titulo="<?php echo strtolower(htmlspecialchars($post['titulo'])); ?>"
         data-conteudo="<?php echo strtolower(htmlspecialchars(substr($post['conteudo'],0,200))); ?>"
         onclick="window.location.href='?page=forum&post=<?php echo $post['id']; ?>'"
         style="cursor:pointer;">
        
        <?php if($role === ROLE_ADMIN || $role === ROLE_SUPERADMIN): ?>
        <div class="fcard-admin-action" onclick="event.stopPropagation();">
            <button type="button" onclick="confirmarDelete('post_forum', <?php echo $post['id']; ?>, <?php echo htmlspecialchars(json_encode($post['titulo'], JSON_HEX_APOS | JSON_HEX_QUOT)); ?>)" class="btn btn-danger btn-sm" style="padding: 4px 8px; border-radius: 8px;" title="Apagar Tópico">
                <i class="fa-solid fa-trash"></i>
            </button>
        </div>
        <?php endif; ?>

        <div><span class="fcard-cat-badge"><?php echo $cat['icon'].' '.ucfirst($post['categoria']); ?></span></div>
        <h3 class="fcard-title">
            <a href="?page=forum&post=<?php echo $post['id']; ?>"><?php echo htmlspecialchars($post['titulo']); ?></a>
        </h3>
        <div class="fcard-excerpt"><?php echo nl2br(htmlspecialchars(mb_substr($post['conteudo'],0,140))); ?><?php if(mb_strlen($post['conteudo'])>140) echo '...'; ?></div>
        
        <div class="fcard-footer" onclick="event.stopPropagation();">
            <div class="fcard-author">
                <div class="fcard-avatar"><?php echo strtoupper(substr($post['autor_nome'],0,1)); ?></div>
                <span class="fcard-author-name" title="<?php echo htmlspecialchars($post['autor_nome']); ?>"><?php echo htmlspecialchars($post['autor_nome']); ?></span>
            </div>
            <div class="fcard-actions">
                <span class="fcard-stat" title="Views"><i class="fa-solid fa-eye"></i> <?php echo $post['views']; ?></span>
                <span class="fcard-stat" title="Comentários"><i class="fa-solid fa-comment"></i> <?php echo $post['num_comentarios']; ?></span>
                <button class="btn-curtir <?php echo $curtido?'curtido':''; ?>" onclick="curtirPost(<?php echo $post['id']; ?>)">
                    <i class="fa-<?php echo $curtido?'solid':'regular'; ?> fa-heart"></i> <?php echo $post['num_curtidas']; ?>
                </button>
            </div>
        </div>
    </div>
<?php endforeach; endif; ?>
</div>
</div>

<div id="forumNoResult" style="display:none;text-align:center;padding:60px 20px;color:var(--text-muted);">
    <i class="fa-solid fa-magnifying-glass" style="font-size:3em;opacity:0.3;display:block;margin-bottom:16px;"></i>
    <h3>Nenhum tópico encontrado para esta busca</h3>
</div>


<script>
function filtrarForum(query) {
    const q = query.toLowerCase().trim();
    const items = document.querySelectorAll('.forum-item');
    let visible = 0;
    items.forEach(item => {
        const titulo   = item.dataset.titulo   || '';
        const conteudo = item.dataset.conteudo || '';
        const match = !q || titulo.includes(q) || conteudo.includes(q);
        item.style.display = match ? '' : 'none';
        if (match) visible++;
    });
    const noResult = document.getElementById('forumNoResult');
    if(noResult) noResult.style.display = (visible === 0 && q) ? 'block' : 'none';
}
function curtirPost(postId) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = `<input type="hidden" name="acao" value="curtir_post"><input type="hidden" name="post_id" value="${postId}">`;
    document.body.appendChild(form);
    form.submit();
}
</script>

<?php endif; // fecha if($post_id): ?>

<?php 
// ### PÁGINA: EVENTOS / ENCONTROS ###
elseif ($pagina === 'eventos'):
    $eventos = $pdo->query("SELECT * FROM eventos ORDER BY data_evento ASC")->fetchAll();
    
    // Pega as presenças do usuário logado
    $presencas_user = [];
    $stmt = $pdo->prepare("SELECT evento_id, status FROM eventos_presenca WHERE user_id = ?");
    $stmt->execute([$id_usuario]);
    while($row = $stmt->fetch()) {
        $presencas_user[$row['evento_id']] = $row['status'];
    }
?>
    <div class="page-header-actions">
        <div>
            <h1><i class="fa-solid fa-calendar-check"></i> Encontros & Eventos</h1>
            <p style="color: var(--text-muted);">Acompanhe os próximos encontros da rede Melodias e confirme sua participação.</p>
        </div>
        <?php if($role === ROLE_ADMIN || $role === ROLE_SUPERADMIN || $role === ROLE_EDITOR): ?>
        <button onclick="openModal('modalAddEvento')" class="btn btn-primary" style="border-radius: 8px;">
            <i class="fa-solid fa-plus"></i> Criar Novo Evento
        </button>
        <?php endif; ?>
    </div>

    <div class="grid-cards" style="margin-top: 25px;">
        <?php if(count($eventos) > 0): foreach($eventos as $ev): ?>
            <div class="card event-card" style="padding: 0; overflow: hidden; display: flex; flex-direction: column; transition: transform 0.3s ease; height: 100%; min-height: 520px; max-height: 750px; background: white;">
                <?php if(!empty($ev['capa']) && file_exists($ev['capa'])): ?>
                    <div style="width: 100%; height: 180px; min-height: 180px; overflow: hidden; position: relative; background: #000; display: flex; align-items: center; justify-content: center;">
                        <img src="<?php echo htmlspecialchars($ev['capa']); ?>" style="width: 100%; height: 100%; object-fit: contain; position: relative; z-index: 2;" alt="Capa do Evento">
                        <div style="position: absolute; inset: 0; background-image: url('<?php echo htmlspecialchars($ev['capa']); ?>'); background-size: cover; background-position: center; filter: blur(20px) brightness(0.4); opacity: 0.7; transform: scale(1.2);"></div>
                        <div style="position: absolute; inset: 0; background: linear-gradient(to top, rgba(0,0,0,0.6) 0%, transparent 60%); z-index: 3;"></div>
                    </div>
                <?php else: ?>
                    <div style="width: 100%; height: 80px; min-height: 80px; background: linear-gradient(135deg, var(--accent) 0%, var(--primary) 100%); position: relative;">
                        <div style="position: absolute; inset: 0; background: url('images/pattern.png'); opacity: 0.1; mix-blend-mode: overlay;"></div>
                    </div>
                <?php endif; ?>

                <div style="padding: 20px; border-bottom: 1px solid var(--border); background: <?php echo empty($ev['capa']) ? 'transparent' : 'rgba(0,0,0,0.02)'; ?>;">
                    <h3 style="margin: 0; font-size: 1.15em; font-weight: 800; color: var(--primary); display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;"><?php echo htmlspecialchars($ev['titulo']); ?></h3>
                    
                    <div style="font-size: 0.8em; margin-top: 8px; color: var(--text-muted); display: flex; flex-direction: column; gap: 4px;">
                        <span style="display: flex; align-items: center; gap: 6px;"><i class="fa-regular fa-calendar" style="color: var(--primary);"></i> <?php echo date('d/m/Y \à\s H:i', strtotime($ev['data_evento'])); ?></span>
                        <?php if(!empty($ev['local'])): ?>
                            <span style="display: flex; align-items: center; gap: 6px;"><i class="fa-solid fa-location-dot" style="color: var(--primary);"></i> <?php echo htmlspecialchars($ev['local']); ?></span>
                        <?php endif; ?>
                    </div>

                    <?php if($role === ROLE_ADMIN || $role === ROLE_SUPERADMIN || $role === ROLE_EDITOR): ?>
                    <div style="display: flex; gap: 8px; margin-top: 15px; padding-top: 12px; border-top: 1px dashed var(--border);">
                        <button onclick='abrirEditarEvento(<?php echo htmlspecialchars(json_encode($ev, JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, "UTF-8"); ?>)' class="btn btn-sm" style="flex: 1; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; gap: 6px; background: white; border: 1px solid var(--border); color: var(--text-muted); font-size: 0.75em;" title="Editar Evento">
                            <i class="fa-solid fa-pen"></i> Editar
                        </button>
                        <button onclick='abrirRelatorio(<?php echo $ev['id']; ?>)' class="btn btn-sm" style="height: 32px; width: 32px; border-radius: 8px; padding: 0; display: flex; align-items: center; justify-content: center; background: white; border: 1px solid var(--border); color: var(--text-muted);" title="Ver Relatório">
                            <i class="fa-solid fa-chart-pie"></i>
                        </button>
                        <button onclick='copiarLinkPublico(<?php echo $ev['id']; ?>)' class="btn btn-sm" style="height: 32px; width: 32px; border-radius: 8px; padding: 0; display: flex; align-items: center; justify-content: center; background: white; border: 1px solid var(--border); color: var(--text-muted);" title="Copiar Link Público">
                            <i class="fa-solid fa-link"></i>
                        </button>
                        <button onclick="confirmarDelete('evento', <?php echo $ev['id']; ?>, '<?php echo addslashes($ev['titulo']); ?>')" class="btn btn-danger btn-sm" style="height: 32px; width: 32px; border-radius: 8px; padding: 0; display: flex; align-items: center; justify-content: center; background: #fff1f2; color: #ef4444; border: 1px solid #fecaca;" title="Excluir Evento">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </div>
                    <?php endif; ?>
                </div>

                <div style="padding: 15px 20px; flex: 1; display: flex; flex-direction: column; overflow: hidden;">
                    <div style="margin-bottom: 15px; flex-shrink: 0;">
                        <p style="font-size: 0.88em; color: var(--text-muted); line-height: 1.5; margin-bottom: 5px; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden;"><?php echo htmlspecialchars($ev['descricao'] ?? ''); ?></p>
                        <a href="javascript:void(0)" onclick='verDetalhesEvento(<?php echo htmlspecialchars(json_encode($ev, JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, "UTF-8"); ?>)' style="font-size: 0.8em; color: var(--primary); font-weight: 700; text-decoration: none;">Ver detalhes <i class="fa-solid fa-chevron-right" style="font-size: 0.8em;"></i></a>
                    </div>
                    
                    <?php 
                        try {
                            $stmt_total = $pdo->prepare("SELECT COUNT(*) as total, SUM(acompanhantes) as extras FROM eventos_presenca WHERE evento_id = ? AND status = 'confirmado'");
                            $stmt_total->execute([$ev['id']]);
                            $totais = $stmt_total->fetch();
                            $stmt_ext = $pdo->prepare("SELECT COUNT(*) as total, SUM(acompanhantes) as extras FROM eventos_presenca_externa WHERE evento_id = ? AND status = 'confirmado'");
                            $stmt_ext->execute([$ev['id']]);
                            $ext = $stmt_ext->fetch();
                            $presencas_total = (int)($totais['total'] ?? 0) + (int)($ext['total'] ?? 0);
                            $pessoas_total = $presencas_total + (int)($totais['extras'] ?? 0) + (int)($ext['extras'] ?? 0);
                            $status_user_row = $pdo->prepare("SELECT status, acompanhantes FROM eventos_presenca WHERE evento_id = ? AND user_id = ?");
                            $status_user_row->execute([$ev['id'], $id_usuario]);
                            $meu_status = $status_user_row->fetch();
                            $status_user = $meu_status['status'] ?? null;
                            $meus_acompanhantes = $meu_status['acompanhantes'] ?? 0;
                        } catch (Exception $e) {
                            $presencas_total = 0; $pessoas_total = 0; $status_user = null; $meus_acompanhantes = 0;
                        }
                    ?>
                    
                    <!-- Stats compactos -->
                    <div style="display: flex; gap: 10px; margin-bottom: 15px; flex-shrink: 0;">
                        <div style="flex: 1; background: #f8fafc; padding: 8px; border-radius: 10px; text-align: center; border: 1px solid #e2e8f0;">
                            <span style="display: block; font-size: 0.65em; text-transform: uppercase; color: var(--text-muted); font-weight: 700; margin-bottom: 2px;">Confirmados</span>
                            <span style="font-size: 1.1em; font-weight: 800; color: var(--primary);"><?php echo $presencas_total; ?></span>
                        </div>
                        <div style="flex: 1; background: #f0fdf4; padding: 8px; border-radius: 10px; text-align: center; border: 1px solid #dcfce7;">
                            <span style="display: block; font-size: 0.65em; text-transform: uppercase; color: var(--text-muted); font-weight: 700; margin-bottom: 2px;">Total</span>
                            <span style="font-size: 1.1em; font-weight: 800; color: #166534;"><?php echo $pessoas_total; ?></span>
                        </div>
                    </div>

                    <?php if($ev['colaborativo_ativo']): ?>
                        <div style="background: #fffbeb; border: 1px solid #fef3c7; border-radius: 12px; padding: 12px; margin-bottom: 15px; flex: 1; overflow: hidden; display: flex; flex-direction: column; min-height: 100px;">
                            <h4 style="font-size: 0.8em; color: #92400e; margin-bottom: 8px; display: flex; align-items: center; gap: 6px; flex-shrink: 0;"><i class="fa-solid fa-mug-hot"></i> Itens Colaborativos</h4>
                            <div style="overflow-y: auto; flex: 1; padding-right: 5px;" class="custom-scrollbar">
                                <?php 
                                    $itens = array_filter(array_map('trim', explode(',', $ev['itens_colaborativos'])));
                                    
                                    // Buscar TODAS as contribuições (Internas e Externas)
                                    $sql_all = "
                                        SELECT item_nome, p.nome as pessoa, 'int' as tipo, ec.user_id 
                                        FROM eventos_contribuicoes ec 
                                        JOIN profissionais p ON ec.user_id = p.id 
                                        WHERE ec.evento_id = ?
                                        UNION ALL
                                        SELECT contribuicao_item as item_nome, nome as pessoa, 'ext' as tipo, 0 as user_id
                                        FROM eventos_presenca_externa 
                                        WHERE evento_id = ? AND status = 'confirmado' AND contribuicao_item IS NOT NULL AND contribuicao_item != ''
                                    ";
                                    $stmt_all = $pdo->prepare($sql_all);
                                    $stmt_all->execute([$ev['id'], $ev['id']]);
                                    $lista_contribuicoes = $stmt_all->fetchAll();

                                    $meus_itens = []; $distribuicao = [];
                                    foreach($lista_contribuicoes as $ct) {
                                        $p_nome = explode(' ', $ct['pessoa'])[0];
                                        $label = ($ct['tipo'] === 'ext') ? $p_nome . " (Ext)" : $p_nome;
                                        $distribuicao[$ct['item_nome']][] = $label;
                                        if($ct['user_id'] == $id_usuario) $meus_itens[] = $ct['item_nome'];
                                    }
                                ?>
                                <?php foreach($itens as $it): 
                                    $estou_levando = in_array($it, $meus_itens);
                                    $quem_leva = $distribuicao[$it] ?? [];
                                    $ja_tem_alguem = !empty($quem_leva);
                                ?>
                                    <div style="display: flex; align-items: center; justify-content: space-between; font-size: 0.8em; padding: 6px 0; border-bottom: 1px solid rgba(146,64,14,0.05); <?php echo $ja_tem_alguem ? 'opacity: 0.85;' : ''; ?>">
                                        <div style="display: flex; flex-direction: column; max-width: 60%;">
                                            <span style="font-weight: 700; color: #451a03; <?php echo $ja_tem_alguem ? 'text-decoration: line-through; color: #92400e;' : ''; ?>">
                                                <?php echo htmlspecialchars($it); ?>
                                            </span>
                                            <span style="font-size: 0.75em; color: #92400e; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; font-style: italic;">
                                                <?php echo empty($quem_leva) ? '<span style="opacity:0.5">Disponível</span>' : '<i class="fa-solid fa-user-tag" style="font-size:0.9em"></i> '.implode(', ', $quem_leva); ?>
                                            </span>
                                        </div>
                                        <?php if($status_user === 'confirmado'): ?>
                                            <button onclick="toggleContribuicao(<?php echo $ev['id']; ?>, '<?php echo addslashes($it); ?>', '<?php echo $estou_levando ? 'remover' : 'adicionar'; ?>')" 
                                                    class="btn btn-sm <?php echo $estou_levando ? 'btn-success' : 'btn-outline'; ?>" 
                                                    style="padding: 2px 8px; border-radius: 12px; font-size: 0.75em; border-width: 1px; min-width: 75px;">
                                                <?php echo $estou_levando ? 'Levando' : ($ja_tem_alguem ? '+ Apoiar' : '+ Item'); ?>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <div style="margin-top: auto; flex-shrink: 0;">
                        <?php if($ev['rsvp_ativo']): ?>
                            <?php if($status_user === 'confirmado'): ?>
                                <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 10px; padding: 12px; text-align: center;">
                                    <div style="color: #166534; font-weight: 700; font-size: 0.85em; margin-bottom: 8px;">
                                        <i class="fa-solid fa-circle-check"></i> Você vai!
                                        <?php if($meus_acompanhantes > 0): ?> <small>(+<?php echo $meus_acompanhantes; ?>)</small> <?php endif; ?>
                                    </div>
                                    <div style="display: flex; gap: 6px;">
                                        <button onclick="confirmarPresenca(<?php echo $ev['id']; ?>, 'remover')" class="btn btn-sm" style="flex: 1; background: #fff; border: 1px solid #fca5a5; color: #991b1b; font-size: 0.8em; height: 32px;">Cancelar</button>
                                        <?php if($ev['permite_acompanhantes']): ?>
                                            <button onclick="abrirAcompanhantes(<?php echo $ev['id']; ?>, <?php echo $meus_acompanhantes; ?>)" class="btn btn-sm" style="background: #fff; border: 1px solid #10b981; color: #10b981; width: 32px; height: 32px; padding: 0;"><i class="fa-solid fa-users"></i></button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                                    <button onclick="confirmarPresenca(<?php echo $ev['id']; ?>, 'confirmado')" class="btn btn-primary btn-sm" style="height: 40px; font-weight: 700;">Sim, irei</button>
                                    <button onclick="confirmarPresenca(<?php echo $ev['id']; ?>, 'recusado')" class="btn btn-outline btn-sm" style="height: 40px; font-weight: 600; font-size: 0.85em; border-style: dashed;">Não poderei</button>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>

                        <?php if(!empty($ev['mapa_link'])): ?>
                            <a href="<?php echo htmlspecialchars($ev['mapa_link']); ?>" target="_blank" class="btn btn-outline btn-block btn-sm" style="margin-top: 8px; border-radius: 8px; font-size: 0.8em; height: 36px; display: flex; align-items: center; justify-content: center; gap: 6px; color: var(--text-muted);">
                                <i class="fa-solid fa-location-arrow"></i> Link / Mapa
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; else: ?>
            <div class="card" style="grid-column: 1 / -1; padding: 60px; text-align: center;">
                <i class="fa-solid fa-calendar-xmark" style="font-size: 4em; color: var(--text-muted); opacity: 0.3; margin-bottom: 20px; display: block;"></i>
                <h3 style="color: var(--text-main); font-size: 1.3em;">Nenhum encontro agendado</h3>
                <p style="color: var(--text-muted); margin-top: 5px;">Acompanhe o painel em breve para novas oportunidades de conexão.</p>
            </div>
        <?php endif; ?>
    </div> <!-- .grid-cards -->
<?php 
// ### PÁGINA: RELATÓRIO DE EVENTO (FRAGMENTO AJAX) ###
elseif ($pagina === 'event_report'):
    $ev_id = (int)$_GET['id'];
    $ev = $pdo->prepare("SELECT * FROM eventos WHERE id = ?");
    $ev->execute([$ev_id]);
    $evento = $ev->fetch();
    
    if(!$evento) die("<p class='error'>Evento não encontrado.</p>");
    
    // Lista de presença
    $presencas = $pdo->prepare("SELECT ep.*, p.nome, p.whatsapp FROM eventos_presenca ep JOIN profissionais p ON ep.user_id = p.id WHERE ep.evento_id = ? ORDER BY p.nome ASC");
    $presencas->execute([$ev_id]);
    $lista_p = $presencas->fetchAll();

    // Lista externa
    $presencas_ext = $pdo->prepare("SELECT * FROM eventos_presenca_externa WHERE evento_id = ? ORDER BY nome ASC");
    $presencas_ext->execute([$ev_id]);
    $lista_p_ext = $presencas_ext->fetchAll();
    
    // Lista de contribuições
    $contribuicoes = $pdo->prepare("SELECT ec.*, p.nome FROM eventos_contribuicoes ec JOIN profissionais p ON ec.user_id = p.id WHERE ec.evento_id = ? ORDER BY ec.item_nome ASC");
    $contribuicoes->execute([$ev_id]);
    $lista_c = $contribuicoes->fetchAll();
    
    $itens_definidos = array_filter(array_map('trim', explode(',', $evento['itens_colaborativos'])));
    $totais_itens = [];
    foreach($lista_c as $c) {
        $totais_itens[$c['item_nome']][] = $c['nome'] . (!empty($c['contribuicao_obs']) ? ' <span style="font-weight:400; opacity:0.7; font-size:0.9em;">('.$c['contribuicao_obs'].')</span>' : '');
    }
    foreach($lista_p_ext as $ext) {
        if(!empty($ext['contribuicao_item'])) {
            $obs = !empty($ext['contribuicao_obs']) ? ' <span style="font-weight:400; opacity:0.7; font-size:0.9em;">('.$ext['contribuicao_obs'].')</span>' : '';
            $totais_itens[$ext['contribuicao_item']][] = $ext['nome'] . ' (Ext)' . $obs;
        }
    }

    $total_geral = count($lista_p) + count($lista_p_ext);
?>
<div id="report_data" style="display:none;">
    <div style="padding: 30px;">
        <div style="margin-bottom:25px;">
        <h3 style="margin:0; font-size:1.4em; color:var(--text-main);"><?php echo htmlspecialchars($evento['titulo']); ?></h3>
        <p style="color:var(--text-muted); margin:5px 0 0 0; font-size:0.9em;"><i class="fa-regular fa-calendar"></i> <?php echo date('d/m/Y H:i', strtotime($evento['data_evento'])); ?> • <?php echo htmlspecialchars($evento['local']); ?></p>
    </div>

    <!-- Seção de Presença -->
    <div style="margin-bottom:30px;">
        <h4 style="display:flex; align-items:center; justify-content:space-between; border-bottom:2px solid var(--border); padding-bottom:8px; margin-bottom:15px; font-size:1em; color:var(--primary);">
            <span><i class="fa-solid fa-users"></i> Lista de Presença</span>
            <span style="font-size:0.8em; background:var(--primary); color:white; padding:2px 10px; border-radius:20px;"><?php echo $total_geral; ?> pessoas</span>
        </h4>
        <table class="premium-table" style="width:100%; font-size:0.9em;">
            <thead>
                <tr>
                    <th style="text-align:left; padding:10px;">Nome</th>
                    <th style="text-align:center; padding:10px;">Status</th>
                    <th style="text-align:center; padding:10px;">Extras</th>
                    <th style="text-align:left; padding:10px;">Obs</th>
                    <th style="text-align:right; padding:10px;">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($lista_p as $p): ?>
                <tr>
                    <td style="padding:10px; font-weight:600;"><?php echo htmlspecialchars($p['nome']); ?></td>
                    <td style="padding:10px; text-align:center;">
                        <span class="badge" style="background:<?php echo $p['status']==='confirmado'?'#dcfce7':'#fee2e2'; ?>; color:<?php echo $p['status']==='confirmado'?'#166534':'#991b1b'; ?>;">
                            <?php echo $p['status']==='confirmado'?'Confirmado':'Não vai'; ?>
                        </span>
                    </td>
                    <td style="padding:10px; text-align:center;"><?php echo $p['acompanhantes'] > 0 ? '+'.$p['acompanhantes'] : '-'; ?></td>
                    <td style="padding:10px; font-size:0.85em; color:var(--text-muted);"><?php echo htmlspecialchars($p['contribuicao_obs'] ?? '-'); ?></td>
                    <td style="padding:10px; text-align:right;">
                        <div style="display:flex; justify-content:flex-end; gap:6px;">
                            <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/','',$p['whatsapp']); ?>" target="_blank" class="btn btn-sm btn-outline" style="padding:4px 8px; border-radius:8px; display:inline-flex; align-items:center; height:32px;" title="WhatsApp"><i class="fa-brands fa-whatsapp"></i></a>
                            <button onclick='deletarPresenca("interna", <?php echo $p["id"]; ?>, <?php echo htmlspecialchars(json_encode($p["nome"]), ENT_QUOTES, "UTF-8"); ?>)' class="btn btn-sm btn-outline" style="padding:4px 8px; border-radius:8px; color:var(--danger); border-color:#fee2e2; display:inline-flex; align-items:center; height:32px;" title="Excluir"><i class="fa-solid fa-trash-can"></i></button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>

                <?php foreach($lista_p_ext as $ext): ?>
                <tr style="background: rgba(168, 85, 247, 0.03);">
                    <td style="padding:10px; font-weight:600;"><?php echo htmlspecialchars($ext['nome']); ?> <span class="badge badge-purple" style="font-size:0.55em; vertical-align:middle; margin-left:5px;">CONVIDADO EXT.</span></td>
                    <td style="padding:10px; text-align:center;">
                        <span class="badge" style="background:#f3e8ff; color:#6b21a8;">Convidado</span>
                    </td>
                    <td style="padding:10px; text-align:center;"><?php echo $ext['acompanhantes'] > 0 ? '+'.$ext['acompanhantes'] : '-'; ?></td>
                    <td style="padding:10px; font-size:0.85em; color:var(--text-muted);"><?php echo htmlspecialchars($ext['contribuicao_obs'] ?? '-'); ?></td>
                    <td style="padding:10px; text-align:right;">
                        <div style="display:flex; justify-content:flex-end; gap:6px;">
                            <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/','',$ext['whatsapp']); ?>" target="_blank" class="btn btn-sm btn-outline" style="padding:4px 8px; border-radius:8px; display:inline-flex; align-items:center; height:32px;" title="WhatsApp"><i class="fa-brands fa-whatsapp"></i></a>
                            <button onclick='deletarPresenca("externa", <?php echo $ext["id"]; ?>, <?php echo htmlspecialchars(json_encode($ext["nome"]), ENT_QUOTES, "UTF-8"); ?>)' class="btn btn-sm btn-outline" style="padding:4px 8px; border-radius:8px; color:var(--danger); border-color:#fee2e2; display:inline-flex; align-items:center; height:32px;" title="Excluir"><i class="fa-solid fa-trash-can"></i></button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>

                <?php if(empty($lista_p) && empty($lista_p_ext)): ?><tr><td colspan="5" style="text-align:center; padding:20px; color:var(--text-muted);">Nenhuma confirmação ainda.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Seção de Contribuições -->
    <?php if($evento['colaborativo_ativo']): ?>
    <div>
        <h4 style="display:flex; align-items:center; justify-content:space-between; border-bottom:2px solid var(--border); padding-bottom:8px; margin-bottom:15px; font-size:1em; color:var(--primary);">
            <span><i class="fa-solid fa-cart-shopping"></i> Divisão de Responsabilidades</span>
        </h4>
        <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap:15px;">
            <?php foreach($itens_definidos as $it): 
                $responsaveis = $totais_itens[$it] ?? [];
            ?>
                <div style="background:var(--bg-body); padding:12px; border-radius:10px; border:1px solid <?php echo !empty($responsaveis)?'rgba(16,185,129,0.2)':'rgba(239,68,68,0.2)'; ?>;">
                    <div style="font-weight:700; font-size:0.9em; margin-bottom:5px; color:var(--text-main);"><?php echo htmlspecialchars($it); ?></div>
                    <div style="font-size:0.8em; color:var(--text-muted);">
                        <?php if(empty($responsaveis)): ?>
                            <span style="color:var(--danger); font-weight:600;"><i class="fa-solid fa-triangle-exclamation"></i> Pendente</span>
                        <?php else: ?>
                            <?php echo implode(', ', $responsaveis); ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <div style="margin-top:30px; display:flex; justify-content:flex-end;">
        <button onclick="window.print()" class="btn btn-outline" style="border-radius:10px;"><i class="fa-solid fa-print"></i> Gerar PDF / Imprimir</button>
    </div>
    </div>
</div>
<?php 
// ### FIM PÁGINA: RELATÓRIO DE EVENTO ###
?>
                <div class="page-header">
                    <h1>😕 Página Não Encontrada</h1>
                    <p>A página que você está procurando não existe.</p>
                    <a href="?page=dashboard" class="btn btn-primary" style="margin-top: 20px;">
                        <i class="fa-solid fa-house"></i> Voltar ao Dashboard
                    </a>
                </div>
<?php endif; ?>

            </div>
        </main>
    </div>

    <!-- Modal de Notificações Rápidas -->
    <div class="modal-overlay" id="modalNotificacoes">
        <div class="modal-content" style="max-width: 400px; padding: 0;">
            <div class="modal-header" style="background: var(--primary); color: white; border-radius: 20px 20px 0 0;">
                <h2>🔔 Notificações</h2>
                <button class="close-modal" onclick="closeModal('modalNotificacoes')" style="color: white;"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div class="modal-body" style="padding: 0; max-height: 350px; overflow-y: auto;">
                <?php if ($role !== ROLE_USER && $stats_sugestoes > 0): ?>
                    <a href="?page=sugestoes" class="dropdown-item" style="padding: 20px; border-bottom: 1px solid var(--border);">
                        <div style="background: rgba(110,43,58,0.1); width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: var(--primary); margin-right: 15px;">
                            <i class="fa-solid fa-lightbulb"></i>
                        </div>
                        <div>
                            <strong style="display: block; font-size: 0.9em;">Novas Sugestões</strong>
                            <small style="color: var(--text-muted);">Você tem <?php echo $stats_sugestoes; ?> ideias pendentes.</small>
                        </div>
                    </a>
                <?php else: ?>
                    <div style="padding: 40px 20px; text-align: center; color: var(--text-muted);">
                        <i class="fa-solid fa-bell-slash" style="font-size: 2em; opacity: 0.3; margin-bottom: 15px; display: block;"></i>
                        <p>Nenhuma nova notificação.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal: Trocar Senha -->
    <div class="modal-overlay" id="modalTrocarSenha">
        <div class="modal-content" style="max-width: 450px;">
            <div class="modal-header">
                <h2><i class="fa-solid fa-key"></i> Alterar Senha</h2>
                <button class="close-modal" onclick="closeModal('modalTrocarSenha')"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <input type="hidden" name="acao" value="change_password">
                    <div class="input-group">
                        <label>Senha Atual</label>
                        <div class="input-password-wrapper">
                            <input type="password" name="senha_atual" id="pwd_atual" class="input-control" required placeholder="Sua senha atual">
                            <button type="button" class="btn-toggle-password" onclick="togglePassword(this, 'pwd_atual')">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    <div class="input-group">
                        <label>Nova Senha (mínimo 8 caracteres)</label>
                        <div class="input-password-wrapper">
                            <input type="password" name="nova_senha" id="pwd_nova" class="input-control" required minlength="8" placeholder="Novas senha">
                            <button type="button" class="btn-toggle-password" onclick="togglePassword(this, 'pwd_nova')">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    <div class="input-group">
                        <label>Confirmar Nova Senha</label>
                        <div class="input-password-wrapper">
                            <input type="password" name="confirma_senha" id="pwd_confirma" class="input-control" required minlength="8" placeholder="Repita a nova senha">
                            <button type="button" class="btn-toggle-password" onclick="togglePassword(this, 'pwd_confirma')">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fa-solid fa-save"></i> Atualizar Senha
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal: Preview / Leitura Online -->
    <div class="modal-overlay" id="modalPreview">
        <div class="modal-content" style="max-width: 95vw; width: 1200px; height: 95vh; display: flex; flex-direction: column; overflow: hidden;">
            <div class="modal-header" style="background: var(--bg-card); border-bottom: 1px solid var(--border);">
                <h2 id="previewTitle" style="margin: 0; font-size: 1.2em;"><i class="fa-solid fa-eye"></i> Visualização</h2>
                <button class="close-modal" onclick="closeModal('modalPreview')">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <div class="modal-body" style="flex: 1; padding: 0; overflow: hidden; background: #1a1a1a; position: relative; display: flex; align-items: center; justify-content: center;">
                <div id="previewLoader" style="position: absolute; color: white; display: flex; flex-direction: column; align-items: center; gap: 15px; z-index: 10;">
                    <i class="fa-solid fa-circle-notch fa-spin fa-2x"></i>
                    <span>Carregando conteúdo...</span>
                </div>
                <iframe id="previewFrame" style="width: 100%; height: 100%; border: none; display: none; background: white;" onload="this.style.display='block'; document.getElementById('previewLoader').style.display='none';"></iframe>
                <div id="previewImageContainer" style="display: none; width: 100%; height: 100%; overflow: auto; padding: 20px; text-align: center;">
                    <img id="previewImage" style="max-width: 100%; height: auto; border-radius: 8px; box-shadow: 0 10px 30px rgba(0,0,0,0.5);">
                </div>
                <div id="previewVideoContainer" style="display: none; width: 100%; height: 100%; align-items: center; justify-content: center;">
                    <video id="previewVideo" controls style="max-width: 100%; max-height: 100%;"></video>
                </div>
            </div>
            <div class="modal-footer" style="padding: 15px 25px; background: var(--bg-card); border-top: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center;">
                <div id="previewAuthor" style="color: var(--text-muted); font-size: 0.9em; font-weight: 500;"></div>
                <a id="previewDownload" href="#" download class="btn btn-primary" style="padding: 10px 20px; border-radius: 8px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-download"></i> Baixar Original
                </a>
            </div>
        </div>
    </div>

    <!-- Toast Container -->
    <div id="toast-container"></div>

    <!-- ========== BOTTOM NAV MOBILE ========== -->
    <nav class="bottom-nav">
        <div class="bottom-nav-inner">
            <a href="?page=dashboard" class="bnav-item <?php echo $pagina==='dashboard'?'active':''; ?>">
                <i class="fa-solid fa-gauge-high"></i>
                <span>Início</span>
            </a>
            <a href="?page=biblioteca" class="bnav-item <?php echo $pagina==='biblioteca'?'active':''; ?>">
                <i class="fa-solid fa-book-bookmark"></i>
                <span>Biblioteca</span>
            </a>
            <!-- FAB central: Fórum -->
            <a href="?page=forum" class="bnav-fab" title="Fórum">
                <i class="fa-solid fa-comments"></i>
            </a>
            <a href="?page=sugestoes" class="bnav-item <?php echo $pagina==='sugestoes'?'active':''; ?>">
                <i class="fa-solid fa-lightbulb"></i>
                <span>Ideias</span>
            </a>
            <a href="?page=perfil" class="bnav-item <?php echo $pagina==='perfil'?'active':''; ?>">
                <i class="fa-solid fa-circle-user"></i>
                <span>Perfil</span>
            </a>
        </div>
    </nav>


    <!-- Modal de Confirmação Genérico (Premium) -->
    <div class="modal-overlay" id="modalConfirmAction" style="justify-content: center; align-items: center;">
        <div class="confirm-dialog anim-fade" style="width: 100%; max-width: 420px; margin: 20px;">
            <div id="confirmActionIcon" class="icon-warning" style="font-size: 3.5em; margin-bottom: 20px;">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>
            <h3 id="confirmActionTitle" style="font-size: 1.6em; font-weight: 800; margin-bottom: 12px;">Tem Certeza?</h3>
            <p id="confirmActionMessage" style="color: var(--text-muted); margin-bottom: 30px; line-height: 1.6; font-size: 0.95em;">Esta ação não pode ser desfeita!</p>
            <div class="btn-group" style="display: flex; gap: 12px; justify-content: center; width: 100%; flex-wrap: wrap;">
                <button class="btn btn-outline" onclick="closeModal('modalConfirmAction')" style="flex: 1; min-width: 140px; justify-content: center;">
                    <i class="fa-solid fa-xmark"></i> Cancelar
                </button>
                <button class="btn" id="confirmActionBtn" style="flex: 1; min-width: 140px; justify-content: center;">
                    <i class="fa-solid fa-check"></i> Confirmar
                </button>
            </div>
        </div>
    </div>

    <!-- Modais de Administração de Usuários (Global) -->
    <!-- Modal: Criar Usuário (Premium) -->
    <div class="modal-overlay" id="modalCriarUsuario">
        <div class="modal-content" style="max-width: 550px; border-radius: 24px; padding: 0; overflow: hidden; border: none; box-shadow: 0 30px 60px rgba(0,0,0,0.4);">
            <div style="background: linear-gradient(135deg, var(--primary), var(--primary-hover)); padding: 30px; color: white; position: relative;">
                <h2 style="margin: 0; display: flex; align-items: center; gap: 15px; font-size: 1.5em; font-weight: 800;">
                    <div style="background: rgba(255,255,255,0.2); width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                        <i class="fa-solid fa-user-plus"></i>
                    </div>
                    Novo Usuário
                </h2>
                <p style="margin: 10px 0 0; opacity: 0.8; font-size: 0.9em; font-weight: 500;">Preencha os dados básicos para acesso à rede.</p>
                <button class="close-modal" onclick="closeModal('modalCriarUsuario')" style="position: absolute; top: 25px; right: 25px; background: rgba(255,255,255,0.15); border: none; color: white; width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.2s;">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            
            <div class="modal-body" style="padding: 30px;">
                <form method="POST">
                    <input type="hidden" name="acao" value="criar_usuario">
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div class="form-group" style="grid-column: span 2;">
                            <label style="font-weight:700; font-size:0.85em; margin-bottom:8px; display:block;">Nome Completo *</label>
                            <input type="text" name="nome" class="input-control premium-input" placeholder="Ex: Maria Silva Santos" required style="width:100%; padding:14px; border-radius:12px;">
                        </div>
                        <div class="form-group" style="grid-column: span 2;">
                            <label style="font-weight:700; font-size:0.85em; margin-bottom:8px; display:block;">E-mail *</label>
                            <input type="email" name="email" class="input-control premium-input" placeholder="email@exemplo.com" required style="width:100%; padding:14px; border-radius:12px;">
                        </div>
                        <div class="form-group" style="grid-column: span 2;">
                            <label style="font-weight:700; font-size:0.85em; margin-bottom:8px; display:block;">Senha de Acesso *</label>
                            <div style="position: relative;">
                                <input type="password" name="senha" id="new_user_pass_v3" class="input-control premium-input" placeholder="Mínimo 6 caracteres" required style="width:100%; padding:14px; padding-right: 45px; border-radius:12px;">
                                <button type="button" onclick="togglePassword(this, 'new_user_pass_v3')" style="position: absolute; right: 15px; top: 14px; background: none; border: none; color: var(--text-muted); cursor: pointer;">
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        <div class="form-group">
                            <label style="font-weight:700; font-size:0.85em; margin-bottom:8px; display:block;">Especialidade</label>
                            <input type="text" name="especialidade" class="input-control premium-input" placeholder="Ex: Psicólogo" style="width:100%; padding:14px; border-radius:12px;">
                        </div>
                        <div class="form-group">
                            <label style="font-weight:700; font-size:0.85em; margin-bottom:8px; display:block;">Gênero</label>
                            <select name="genero" class="input-control premium-input" style="width:100%; padding:14px; border-radius:12px;">
                                <option value="Não declarado">Não declarado</option>
                                <option value="Masculino">Masculino</option>
                                <option value="Feminino">Feminino</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label style="font-weight:700; font-size:0.85em; margin-bottom:8px; display:block;">WhatsApp</label>
                            <input type="text" name="whatsapp" class="input-control premium-input" placeholder="(00) 00000-0000" style="width:100%; padding:14px; border-radius:12px;">
                        </div>
                        <div class="form-group">
                            <label style="font-weight:700; font-size:0.85em; margin-bottom:8px; display:block;">Permissão *</label>
                            <select name="role" class="input-control premium-input" required style="width:100%; padding:14px; border-radius:12px;">
                                <option value="user" selected>👤 Membro Normal</option>
                                <option value="editor">✍️ Editor de Conteúdo</option>
                                <option value="admin">🛡️ Administrador</option>
                            </select>
                        </div>
                    </div>

                    <div style="margin-top: 35px; display: flex; gap: 15px;">
                        <button type="button" class="btn btn-outline" onclick="closeModal('modalCriarUsuario')" style="flex: 1; padding: 16px; border-radius: 14px; font-weight: 700;">Cancelar</button>
                        <button type="submit" class="btn btn-primary" style="flex: 2; padding: 16px; border-radius: 14px; font-weight: 800; background: linear-gradient(135deg, var(--primary), var(--primary-hover)); border: none; box-shadow: 0 10px 25px rgba(110,43,58,0.25);">
                            <i class="fa-solid fa-user-check"></i> Criar Usuário
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal: Gerar Convite -->
    <div class="modal-overlay" id="modalGerarConvite">
        <div class="modal-content" style="max-width: 450px; border-radius: 20px;">
            <div class="modal-header">
                <h2><i class="fa-solid fa-link"></i> Gerar Link Externo</h2>
                <button class="close-modal" onclick="closeModal('modalGerarConvite')">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <input type="hidden" name="acao" value="gerar_convite">
                    <div class="form-group">
                        <label>Limite de Inscrições</label>
                        <input type="number" name="limite" class="input-control" value="40" min="1" max="500" required style="width:100%; border-radius:10px;">
                        <small style="color:var(--text-muted); display:block; margin-top:5px;">Máximo de inscritos permitidos para este link.</small>
                    </div>
                    <div class="form-group" style="margin-top:15px;">
                        <label>Validade do Link (Duração)</label>
                        <select name="validade_horas" class="input-control" required style="width:100%; border-radius:10px;">
                            <option value="2">2 Horas</option>
                            <option value="6">6 Horas</option>
                            <option value="24" selected>24 Horas (1 dia)</option>
                            <option value="48">48 Horas (2 dias)</option>
                            <option value="168">7 Dias</option>
                            <option value="720">30 Dias</option>
                        </select>
                    </div>
                    <div class="form-group" style="margin-top:15px;">
                        <label>Nível de Acesso Padrão</label>
                        <select name="role" class="input-control" required style="width:100%; border-radius:10px;">
                            <option value="user" selected>Membro Normal</option>
                            <option value="editor">Editor de Conteúdo</option>
                            <option value="admin">Administrador</option>
                        </select>
                    </div>
                    <div style="margin-top: 25px; display: flex; gap: 10px;">
                        <button type="submit" class="btn btn-primary" style="width: 100%; padding: 14px; border-radius: 12px; font-weight: 800;">
                            <i class="fa-solid fa-plus-circle"></i> Gerar Link Externo
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal: Editar Usuário -->
    <div class="modal-overlay" id="modalEditarUsuario">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fa-solid fa-pen"></i> Editar Usuário</h2>
                <button class="close-modal" onclick="closeModal('modalEditarUsuario')">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <input type="hidden" name="acao" value="editar_usuario">
                    <input type="hidden" name="id_user" id="edit_user_id">
                    <div class="form-group">
                        <label>Nome Completo *</label>
                        <input type="text" name="nome" id="edit_user_nome" class="input-control" required style="width:100%;">
                    </div>
                    <div class="form-group" style="margin-top:15px;">
                        <label>E-mail *</label>
                        <input type="email" name="email" id="edit_user_email" class="input-control" required style="width:100%;">
                    </div>
                    <div class="form-group" style="margin-top:15px;">
                        <label>Nova Senha (deixe vazio para manter a atual)</label>
                        <div style="position: relative;">
                            <input type="password" name="nova_senha" id="edit_user_pass" class="input-control" 
                                   placeholder="Digite apenas se quiser alterar" minlength="6" style="width:100%; padding-right:40px;">
                            <button type="button" onclick="togglePassword(this, 'edit_user_pass')" style="position:absolute; right:10px; top:12px; border:none; background:none; cursor:pointer;">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    <div class="form-group" style="margin-top:15px;">
                        <label>Especialidade</label>
                        <input type="text" name="especialidade" id="edit_user_especialidade" class="input-control" style="width:100%;">
                    </div>
                    <div class="form-group" style="margin-top:15px;">
                        <label>Gênero</label>
                        <select name="genero" id="edit_user_genero" class="input-control" style="width:100%;">
                            <option value="Masculino">Masculino</option>
                            <option value="Feminino">Feminino</option>
                            <option value="Não declarado">Não declarado</option>
                        </select>
                    </div>
                    <div class="form-group" style="margin-top:15px;">
                        <label>WhatsApp</label>
                        <input type="text" name="whatsapp" id="edit_user_whatsapp" class="input-control" style="width:100%;">
                    </div>
                    <div class="form-group" style="margin-top:15px;">
                        <label>Nível de Permissão *</label>
                        <select name="role" id="edit_user_role" class="input-control" style="width:100%;">
                            <option value="user">👤 Usuário Normal (Membro)</option>
                            <option value="editor">✍️ Editor (Materiais/Eventos)</option>
                            <option value="admin">🛡️ Administrador</option>
                            <option value="superadmin">👑 Super Administrador</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width:100%; margin-top:20px; padding:12px; border-radius:10px;">
                        <i class="fa-solid fa-save"></i> Salvar Alterações
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        console.log("Melodias: Scripts JS Carregados.");
        // ========================================================
        // JAVASCRIPT - SISTEMA COMPLETO
        // ========================================================

        // === VISIBILIDADE DE SENHA ===
        function togglePassword(btn, targetId) {
            const input = document.getElementById(targetId);
            const icon = btn.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }

        // === TEMA CLARO/ESCURO ===
        function toggleTheme() {
            const html = document.documentElement;
            const currentTheme = html.getAttribute('data-theme');
            const newTheme = currentTheme === 'light' ? 'dark' : 'light';
            html.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            
            // Atualiza ícone
            const icon = document.querySelector('.theme-btn i');
            if (icon) {
                icon.classList.toggle('fa-moon');
                icon.classList.toggle('fa-sun');
            }
        }
        
        // Aplica tema salvo
        const savedTheme = localStorage.getItem('theme');
        if (savedTheme === 'dark') {
            document.documentElement.setAttribute('data-theme', 'dark');
            const themeIcon = document.querySelector('.theme-btn i');
            if (themeIcon) themeIcon.classList.replace('fa-moon', 'fa-sun');
        }

        // === SIDEBAR MOBILE ===
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            sidebar.classList.toggle('show');
            overlay.classList.toggle('active');
        }
        
        function closeSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            sidebar.classList.remove('show');
            overlay.classList.remove('active');
        }
        
        // Fechar sidebar ao clicar em um link (mobile)
        document.querySelectorAll('.sidebar .nav-link').forEach(link => {
            link.addEventListener('click', closeSidebar);
        });

        // === DROPDOWN DO USUÁRIO ===
        function toggleUserDropdown(event) {
            if (event) event.stopPropagation();
            const dropdown = document.getElementById('user-dropdown');
            const backdrop = document.getElementById('dropdown-backdrop');
            if (!dropdown) return;
            const isOpen = dropdown.classList.contains('active');
            if (isOpen) {
                closeUserDropdown();
            } else {
                dropdown.classList.add('active');
                if (backdrop) backdrop.style.display = 'block';
                document.body.style.overflow = 'hidden'; // evita scroll
            }
        }
        function closeUserDropdown() {
            const dropdown = document.getElementById('user-dropdown');
            const backdrop = document.getElementById('dropdown-backdrop');
            if (dropdown) dropdown.classList.remove('active');
            if (backdrop) backdrop.style.display = 'none';
            document.body.style.overflow = '';
        }

        // Fechar elementos ao clicar fora
        document.addEventListener('click', function(e) {
            // Dropdown de perfil
            const dropdown = document.getElementById('user-dropdown');
            const trigger = document.querySelector('.user-trigger');
            if (dropdown && dropdown.classList.contains('active') &&
                trigger && !dropdown.contains(e.target) && !trigger.contains(e.target)) {
                closeUserDropdown();
            }

            // Fechar sidebar mobile ao clicar no overlay
            const overlay = document.getElementById('sidebarOverlay');
            if (e.target === overlay) {
                closeSidebar();
            }
        });

        // === BUSCA GLOBAL ===
        const globalSearch = document.getElementById('global-search');
        if (globalSearch) {
            globalSearch.addEventListener('input', function(e) {
                const query = e.target.value.toLowerCase().trim();
                
                if (query.length < 2) return;
                
                // Busca nos cards visíveis
                document.querySelectorAll('.stat-card, .material-card, .sug-card, .user-card').forEach(card => {
                    const text = card.textContent.toLowerCase();
                    card.style.display = text.includes(query) ? '' : 'none';
                });
            });
            
            // Limpar busca
            globalSearch.addEventListener('keyup', function(e) {
                if (e.key === 'Escape') {
                    globalSearch.value = '';
                    document.querySelectorAll('.stat-card, .material-card, .sug-card, .user-card').forEach(card => {
                        card.style.display = '';
                    });
                }
            });
        }

        // === SISTEMA DE TOASTS ===
        function showToast(titulo, mensagem, tipo) {
            const container = document.getElementById('toast-container');
            const toast = document.createElement('div');
            toast.className = `toast ${tipo}`;
            
            const icones = {
                success: '<i class="fa-solid fa-circle-check toast-icon" style="color: var(--success);"></i>',
                error: '<i class="fa-solid fa-circle-xmark toast-icon" style="color: var(--danger);"></i>',
                warning: '<i class="fa-solid fa-triangle-exclamation toast-icon" style="color: var(--warning);"></i>'
            };
            
            toast.innerHTML = `
                ${icones[tipo] || icones.success}
                <div class="toast-content">
                    <h4>${titulo}</h4>
                    <p>${mensagem}</p>
                </div>
            `;
            
            container.appendChild(toast);
            
            // Remove após 4 segundos
            setTimeout(() => {
                toast.style.animation = 'slideIn 0.4s reverse';
                setTimeout(() => toast.remove(), 400);
            }, 4000);
        }

        // === SISTEMA DE MODAIS ===
        function openModal(id) {
            const modal = document.getElementById(id);
            if (modal) {
                modal.classList.add('active');
                document.body.style.overflow = 'hidden';
                document.documentElement.classList.add('modal-active'); // Esconde sidebar/topbar
                
                // Hierarquia de Z-INDEX para modais sobrepostos
                if (id === 'modalConfirmAction') {
                    modal.style.zIndex = '10002'; // Acima de tudo
                } else if (id === 'modalRelatorioEvento') {
                    modal.style.zIndex = '10001'; // Segunda camada
                } else {
                    modal.style.zIndex = '10000'; // Base
                }
                
                console.log("Abrindo Modal: " + id);
            }
        }
        
        function closeModal(id) {
            const modal = document.getElementById(id);
            if (modal) {
                modal.classList.remove('active');
                
                // Só remove o 'modal-active' se não houver outros modais abertos
                const openModals = document.querySelectorAll('.modal-overlay.active');
                if (openModals.length === 0) {
                    document.body.style.overflow = '';
                    document.documentElement.classList.remove('modal-active');
                }
            }
            
            // Se for o modal de preview, para o conteúdo
            if(id === 'modalPreview') {
                const frame = document.getElementById('previewFrame');
                const video = document.getElementById('previewVideo');
                if(frame) frame.src = '';
                if(video) { video.pause(); video.src = ''; }
            }
        }

        // === FUNÇÕES GLOBAIS ===
        function copiarLink(url) {
            if (navigator.clipboard) {
                navigator.clipboard.writeText(url).then(() => {
                    showToast('Copiado', 'Link copiado!', 'success');
                }).catch(() => {
                    const i = document.createElement('input'); i.value = url; document.body.appendChild(i); i.select(); document.execCommand('copy'); document.body.removeChild(i);
                    showToast('Copiado', 'Link copiado!', 'success');
                });
            } else {
                const i = document.createElement('input'); i.value = url; document.body.appendChild(i); i.select(); document.execCommand('copy'); document.body.removeChild(i);
                showToast('Copiado', 'Link copiado!', 'success');
            }
        }
        function copiarTexto(t) { copiarLink(t); }

        // === EVENTOS: FUNÇÕES GLOBAIS ===
        function confirmarPresenca(evento_id, status) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `<input type="hidden" name="acao" value="confirmar_presenca"><input type="hidden" name="evento_id" value="${evento_id}"><input type="hidden" name="status_presenca" value="${status}">`;
            document.body.appendChild(form);
            form.submit();
        }

        function updateAcompanhantes(eventoId, qtd) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `<input type="hidden" name="acao" value="confirmar_presenca"><input type="hidden" name="evento_id" value="${eventoId}"><input type="hidden" name="status_presenca" value="confirmado"><input type="hidden" name="acompanhantes" value="${qtd}">`;
            document.body.appendChild(form);
            form.submit();
        }

        function toggleContribuicao(eventoId, itemNome, operacao) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `<input type="hidden" name="acao" value="gerenciar_contribuicao"><input type="hidden" name="evento_id" value="${eventoId}"><input type="hidden" name="item_nome" value="${itemNome}"><input type="hidden" name="operacao" value="${operacao}">`;
            document.body.appendChild(form);
            form.submit();
        }

        function copiarLinkPublico(eventoId) {
            // Remove o nome do arquivo atual do path (funciona com ou sem .php na URL)
            const dir = window.location.origin + window.location.pathname.replace(/\/[^\/]*$/, '/');
            const link = dir + 'rsvp.php?id=' + eventoId;
            if (navigator.clipboard) {
                navigator.clipboard.writeText(link).then(() => showToast('Sucesso', 'Link copiado!', 'success'));
            } else {
                prompt('Copie o link público:', link);
            }
        }

        function syncDateTime(type) {
            const dateEl = document.getElementById('ev_date_' + type);
            const timeEl = document.getElementById('ev_time_' + type);
            const hiddenEl = document.getElementById('ev_dt_' + type + '_hidden');
            if (dateEl && timeEl && hiddenEl && dateEl.value && timeEl.value) {
                hiddenEl.value = dateEl.value + ' ' + timeEl.value + ':00';
            }
        }

        function abrirRelatorio(eventoId) {
            openModal('modalRelatorioEvento');
            const container = document.getElementById('relatorio_content');
            if (!container) return;
            container.innerHTML = '<div style="text-align:center;padding:40px;"><i class="fa-solid fa-circle-notch fa-spin fa-2x" style="color:var(--primary);"></i></div>';
            fetch('painel.php?page=event_report&id=' + eventoId)
                .then(res => res.text())
                .then(html => {
                    const doc = new DOMParser().parseFromString(html, 'text/html');
                    const content = doc.getElementById('report_data');
                    container.innerHTML = content ? content.innerHTML : '<p style="text-align:center;padding:30px;color:var(--danger);">Erro ao carregar relatório.</p>';
                })
                .catch(() => { container.innerHTML = '<p style="text-align:center;padding:30px;color:var(--danger);">Falha na conexão.</p>'; });
        }

        function abrirEditarEvento(ev) {
            document.getElementById('edit_ev_id').value = ev.id;
            document.getElementById('edit_ev_titulo').value = ev.titulo || '';
            if (ev.data_evento) {
                const parts = ev.data_evento.split(' ');
                const dateEl = document.getElementById('edit_ev_date_only');
                const timeEl = document.getElementById('edit_ev_time_only');
                const hiddenEl = document.getElementById('edit_ev_data_hidden');
                if (dateEl) dateEl.value = parts[0];
                if (timeEl) timeEl.value = parts[1] ? parts[1].substring(0, 5) : '';
                if (hiddenEl) hiddenEl.value = ev.data_evento;
            }
            const localEl = document.getElementById('edit_ev_local');
            const mapaEl  = document.getElementById('edit_ev_mapa');
            const descEl  = document.getElementById('edit_ev_desc');
            const rsvpEl  = document.getElementById('edit_ev_rsvp');
            const acompEl = document.getElementById('edit_ev_acompanhantes');
            const colabEl = document.getElementById('edit_ev_colaborativo');
            const itensEl = document.getElementById('edit_ev_itens');
            const colabOpt= document.getElementById('colab_options_edit_v2');
            if (localEl) localEl.value = ev.local || '';
            if (mapaEl)  mapaEl.value  = ev.mapa_link || '';
            if (descEl)  descEl.value  = ev.descricao || '';
            if (rsvpEl)  rsvpEl.checked  = parseInt(ev.rsvp_ativo) === 1;
            if (acompEl) acompEl.checked = parseInt(ev.permite_acompanhantes) === 1;
            const colab = parseInt(ev.colaborativo_ativo) === 1;
            if (colabEl) colabEl.checked = colab;
            if (colabOpt) colabOpt.style.display = colab ? 'block' : 'none';
            
            // Popular itens dinâmicos
            const container = document.getElementById('edit_items_container');
            if (container) {
                container.innerHTML = '';
                const itens = (ev.itens_colaborativos || '').split(',').map(i => i.trim()).filter(i => i !== '');
                if (itens.length === 0) {
                    addItemToCollab('edit_items_container');
                } else {
                    itens.forEach(item => {
                        const div = document.createElement('div');
                        div.style.display = 'flex';
                        div.style.gap = '8px';
                        div.innerHTML = `<input type="text" name="itens_colaborativos[]" class="input-control premium-input" value="${item}" placeholder="Ex: Café, Bolo..."><button type="button" onclick="removeItemFromCollab(this)" class="btn btn-outline" style="padding:8px 12px; color:var(--danger); border-color:#fee2e2;"><i class="fa-solid fa-trash-can"></i></button>`;
                        container.appendChild(div);
                    });
                }
            }
            openModal('modalEditEvento');
        }

        function confirmarDelete(tipo, id, nome) {
            if (tipo === 'evento') {
                confirmarAcao({
                    titulo: 'Excluir Evento',
                    msg: `Deseja apagar o evento <b>${nome}</b>?<br><br>As presenças também serão apagadas.`,
                    icon: 'fa-solid fa-calendar-xmark',
                    iconClass: 'icon-danger',
                    btnText: 'Excluir',
                    btnClass: 'btn-danger',
                    callback: function() {
                        const f = document.createElement('form');
                        f.method = 'POST';
                        f.innerHTML = `<input type="hidden" name="acao" value="delete_evento"><input type="hidden" name="id_evento" value="${id}">`;
                        document.body.appendChild(f);
                        f.submit();
                    }
                });
            } else if (tipo === 'post_forum') {
                confirmarAcao({
                    titulo: 'Excluir Tópico',
                    msg: `Deseja apagar o tópico <b>${nome}</b>?`,
                    icon: 'fa-solid fa-trash',
                    iconClass: 'icon-danger',
                    btnText: 'Excluir',
                    btnClass: 'btn-danger',
                    callback: function() {
                        const f = document.createElement('form');
                        f.method = 'POST';
                        f.innerHTML = `<input type="hidden" name="acao" value="delete_post_forum"><input type="hidden" name="post_id" value="${id}">`;
                        document.body.appendChild(f);
                        f.submit();
                    }
                });
            } else if (tipo === 'comentario') {
                confirmarAcao({
                    titulo: 'Excluir Comentário',
                    msg: 'Deseja apagar este comentário?',
                    icon: 'fa-solid fa-trash',
                    iconClass: 'icon-danger',
                    btnText: 'Excluir',
                    btnClass: 'btn-danger',
                    callback: function() {
                        const f = document.createElement('form');
                        f.method = 'POST';
                        f.innerHTML = `<input type="hidden" name="acao" value="delete_comentario"><input type="hidden" name="comentario_id" value="${id}">`;
                        document.body.appendChild(f);
                        f.submit();
                    }
                });
            } else if (tipo === 'usuario') {
                confirmarAcao({
                    titulo: 'Excluir Usuário',
                    msg: `Deseja apagar o usuário <b>${nome}</b>?`,
                    icon: 'fa-solid fa-user-slash',
                    iconClass: 'icon-danger',
                    btnText: 'Excluir',
                    btnClass: 'btn-danger',
                    callback: function() {
                        const f = document.createElement('form');
                        f.method = 'POST';
                        f.innerHTML = `<input type="hidden" name="acao" value="delete_usuario"><input type="hidden" name="id_user" value="${id}">`;
                        document.body.appendChild(f);
                        f.submit();
                    }
                });
            }
        }
        
        function deletarPresenca(tipo, id, nome) {
            confirmarAcao({
                titulo: 'Remover Confirmação',
                msg: `Deseja remover a presença de <b>${nome}</b>?`,
                icon: 'fa-solid fa-user-xmark',
                iconClass: 'icon-danger',
                btnText: 'Remover',
                btnClass: 'btn-danger',
                callback: function() {
                    const f = document.createElement('form');
                    f.method = 'POST';
                    f.innerHTML = `<input type="hidden" name="acao" value="delete_presenca"><input type="hidden" name="tipo_presenca" value="${tipo}"><input type="hidden" name="presenca_id" value="${id}">`;
                    document.body.appendChild(f);
                    f.submit();
                }
            });
        }
        
        function addItemToCollab(containerId) {
            const container = document.getElementById(containerId);
            if (!container) return;
            const div = document.createElement('div');
            div.style.display = 'flex';
            div.style.gap = '8px';
            div.innerHTML = `<input type="text" name="itens_colaborativos[]" class="input-control premium-input" placeholder="Novo item..."><button type="button" onclick="removeItemFromCollab(this)" class="btn btn-outline" style="padding:8px 12px; color:var(--danger); border-color:#fee2e2;"><i class="fa-solid fa-trash-can"></i></button>`;
            container.appendChild(div);
            // Focus no novo input
            div.querySelector('input').focus();
        }

        function removeItemFromCollab(btn) {
            const container = btn.parentElement.parentElement;
            if (container.children.length > 1) {
                btn.parentElement.remove();
            } else {
                btn.parentElement.querySelector('input').value = '';
            }
        }

        // === PREVIEW DE MATERIAL (LER ONLINE) ===
        function abrirPreview(m) {
            const modal = document.getElementById('modalPreview');
            const frame = document.getElementById('previewFrame');
            const imgCont = document.getElementById('previewImageContainer');
            const vidCont = document.getElementById('previewVideoContainer');
            const loader = document.getElementById('previewLoader');
            const title = document.getElementById('previewTitle');
            const author = document.getElementById('previewAuthor');
            const dl = document.getElementById('previewDownload');
            
            // Reset
            frame.style.display = 'none';
            imgCont.style.display = 'none';
            vidCont.style.display = 'none';
            loader.style.display = 'flex';
            frame.src = '';
            
            title.innerHTML = `<i class="fa-solid fa-eye"></i> Visualizando: ${m.titulo}`;
            author.innerHTML = m.autor ? `<i class="fa-solid fa-user-tie"></i> Autor: ${m.autor}` : '';
            
            const link = m.url_externa || m.caminho;
            dl.href = link;
            
            // Link externo geralmente não é download direto
            dl.style.display = m.url_externa ? 'none' : 'inline-flex'; 
            
            const parts = link.split('.');
            const ext = parts.length > 1 ? parts.pop().toLowerCase() : '';
            
            // YouTube Handler
            if (m.url_externa && (link.includes('youtube.com') || link.includes('youtu.be'))) {
                let videoId = '';
                if (link.includes('youtube.com/watch?v=')) videoId = link.split('v=')[1].split('&')[0];
                else if (link.includes('youtu.be/')) videoId = link.split('youtu.be/')[1].split('?')[0];
                frame.src = `https://www.youtube.com/embed/${videoId}?autoplay=1`;
                frame.style.display = 'block';
            } 
            // Imagens
            else if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(ext)) {
                document.getElementById('previewImage').src = link;
                imgCont.style.display = 'block';
                loader.style.display = 'none';
            } 
            // Vídeos diretos
            else if (['mp4', 'webm', 'ogg'].includes(ext)) {
                document.getElementById('previewVideo').src = link;
                vidCont.style.display = 'flex';
                loader.style.display = 'none';
            } 
            // PDF e outros (tenta abrir no frame)
            else {
                frame.src = link;
                frame.style.display = 'block';
            }
            
            openModal('modalPreview');
        }
        
        // Fechar modal clicando fora
        window.onclick = function(event) {
            if (event.target.classList.contains('modal-overlay')) {
                event.target.classList.remove('active');
                document.body.style.overflow = '';
            }
        }
        
        // Fechar com ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                document.querySelectorAll('.modal-overlay.active').forEach(modal => {
                    modal.classList.remove('active');
                });
                document.body.style.overflow = '';
            }
        });

        // === FILTRAR MATERIAIS ===
        function filtrarMateriais() {
            const busca = document.getElementById('buscaMaterial').value.toLowerCase();
            const itens = document.querySelectorAll('.material-item');
            
            itens.forEach(item => {
                const titulo = item.querySelector('.material-titulo').innerText.toLowerCase();
                const desc = item.querySelector('p')?.innerText.toLowerCase() || '';
                
                if (titulo.includes(busca) || desc.includes(busca)) {
                    item.style.display = 'flex';
                } else {
                    item.style.display = 'none';
                }
            });
        }

        // === EDITAR MATERIAL ===
        function editarMaterial(dados) {
            document.querySelector('#modalEditMaterial form').reset(); 
            document.getElementById('edit_mat_id').value = dados.id; 
            document.getElementById('edit_mat_titulo').value = dados.titulo; 
            document.getElementById('edit_mat_categoria').value = dados.categoria; 
            document.getElementById('edit_mat_tipo').value = dados.tipo || 'material'; 
            document.getElementById('edit_mat_autor').value = dados.autor || ''; 
            document.getElementById('edit_mat_url_externa').value = dados.url_externa || ''; 
            document.getElementById('edit_mat_descricao').value = dados.descricao || ''; 
            document.getElementById('edit_mat_visibilidade').value = dados.visibilidade; 
            document.querySelectorAll('#modalEditMaterial .file-name').forEach(el => {
                el.innerText = 'Consultar material ou Escolher nova capa';
            });
            openModal('modalEditMaterial');
        }

        // === EDITAR USUÁRIO ===
        function editarUsuario(dados) {
            document.getElementById('edit_user_id').value = dados.id;
            document.getElementById('edit_user_nome').value = dados.nome;
            document.getElementById('edit_user_email').value = dados.email;
            document.getElementById('edit_user_genero').value = dados.genero || 'Não declarado';
            document.getElementById('edit_user_especialidade').value = dados.especialidade || '';
            document.getElementById('edit_user_whatsapp').value = dados.whatsapp || '';
            document.getElementById('edit_user_role').value = dados.role;
            openModal('modalEditarUsuario');
        }

        // === CONFIRMAR AÇÃO (GENÉRICO) ===
        function confirmarAcao(dados) {
            const modalId = 'modalConfirmAction';
            const iconDiv = document.getElementById('confirmActionIcon');
            const titleEl = document.getElementById('confirmActionTitle');
            const msgEl   = document.getElementById('confirmActionMessage');
            const btnEl   = document.getElementById('confirmActionBtn');
            
            iconDiv.innerHTML = `<i class="${dados.icon || 'fa-solid fa-triangle-exclamation'}"></i>`;
            iconDiv.className = dados.iconClass || 'icon-warning';
            titleEl.innerText = dados.titulo || 'Tem Certeza?';
            msgEl.innerHTML   = dados.msg || '';
            
            btnEl.className = 'btn ' + (dados.btnClass || 'btn-primary');
            btnEl.innerHTML = `<i class="${dados.btnIcon || 'fa-solid fa-check'}"></i> ${dados.btnText || 'Confirmar'}`;
            
            btnEl.onclick = function() {
                if (typeof dados.callback === 'function') {
                    dados.callback();
                }
                closeModal(modalId);
            };
            
            openModal(modalId);
        }

        // === CONFIRMAR DELETE AVANÇADO ===
        function confirmarDelete(tipo, id, nome = '') {
            const config = {
                material:   { msg: `Você está prestes a excluir o material "<strong>${nome}</strong>".`, field: 'id_material', acao: 'delete_material' },
                sugestao:   { msg: `Deseja realmente excluir esta sugestão?`, field: 'id_sugestao', acao: 'delete_sugestao' },
                usuario:    { msg: `Você está prestes a excluir o usuário "<strong>${nome}</strong>" e todos os seus dados.`, field: 'id_user', acao: 'deletar_usuario' },
                post_forum: { msg: `Tem certeza que deseja apagar o tópico "<strong>${nome}</strong>" e todos os comentários?`, field: 'post_id', acao: 'delete_post_forum' },
                comentario: { msg: `Deseja excluir este comentário permanentemente?`, field: 'comentario_id', acao: 'delete_comentario' },
                evento:     { msg: `Você está prestes a excluir o evento "<strong>${nome}</strong>" e todos os dados relacionados.`, field: 'id_evento', acao: 'delete_evento' }
            };
            
            const item = config[tipo] || { msg: 'Esta ação não pode ser desfeita!', field: 'id', acao: 'delete_' + tipo };

            confirmarAcao({
                titulo: 'Confirmar Exclusão',
                msg: item.msg + '<br><small style="color:var(--text-muted);opacity:0.8;">Esta ação é permanente e irreversível.</small>',
                btnText: 'Sim, Excluir',
                btnClass: 'btn-danger',
                btnIcon: 'fa-solid fa-trash',
                icon: 'fa-solid fa-trash-can',
                iconClass: 'icon-danger',
                callback: function() {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.innerHTML = `<input type="hidden" name="acao" value="${item.acao}"><input type="hidden" name="${item.field}" value="${id}">`;
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }

        // === APROVAR SOLICITAÇÃO ===
        function aprovarSolicitacao(id, nome, email, whatsapp) {
            const msgHtml = `Você está prestes a <b>APROVAR</b> o acesso de:<br><br>` +
                           `👤 Nome: <b>${nome}</b><br>` +
                           `📧 E-mail: <b>${email}</b><br><br>` +
                           `<div style='background:rgba(0,0,0,0.03);padding:15px;border-radius:10px;font-size:0.9em;'>` +
                           `🔑 Senha de Acesso: <b>melodias123</b>` +
                           `</div><br>` +
                           `Deseja confirmar a aprovação?`;
            
            confirmarAcao({
                titulo: 'Aprovar Acesso',
                msg: msgHtml,
                btnText: 'Confirmar Aprovação',
                btnClass: 'btn-success',
                btnIcon: 'fa-solid fa-check-double',
                icon: 'fa-solid fa-user-check',
                iconClass: 'icon-success',
                callback: function() {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.innerHTML = `
                        <input type="hidden" name="acao" value="aprovar_solicitacao">
                        <input type="hidden" name="id_user" value="${id}">
                    `;
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }

        // === REJEITAR SOLICITAÇÃO ===
        function confirmarRejeicao(id, nome) {
            confirmarAcao({
                titulo: 'Rejeitar Solicitação',
                msg: `Tem certeza que deseja <b>REJEITAR</b> a solicitação de:<br><b>${nome}</b><br><br>Esta pessoa será removida do sistema.`,
                btnText: 'Sim, Rejeitar',
                btnClass: 'btn-danger',
                btnIcon: 'fa-solid fa-user-xmark',
                icon: 'fa-solid fa-user-slash',
                iconClass: 'icon-danger',
                callback: function() {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.innerHTML = `
                        <input type="hidden" name="acao" value="rejeitar_solicitacao">
                        <input type="hidden" name="id_user" value="${id}">
                    `;
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }

        // === KPI E BARRAS DE PROGRESSO ===
        document.querySelectorAll('.kpi-value[data-target]').forEach(el => {
            const target = parseInt(el.dataset.target) || 0;
            if(target === 0) { el.textContent = '0'; return; }
            let current = 0;
            const step = Math.max(1, Math.floor(target / 30));
            const timer = setInterval(() => {
                current = Math.min(current + step, target);
                el.textContent = current;
                if(current >= target) clearInterval(timer);
            }, 40);
        });

        document.querySelectorAll('.progress-bar-fill[data-width]').forEach(el => {
            setTimeout(() => {
                el.style.width = el.dataset.width + '%';
            }, 300);
        });

        // === EXECUTAR NOTIFICAÇÃO DO PHP ===
        <?php echo $notificacao; ?>

        // ========================================================
        // SISTEMA DE RECONEXÃO AUTOMÁTICA
        // Detecta queda de servidor/rede sem fazer logout automático.
        // Só redireciona para login se a sessão expirou de fato.
        // ========================================================
        (function() {
            const PING_URL     = 'ping.php';
            const PING_INTERVAL_OK   = 30000;  // 30s quando online
            const PING_INTERVAL_FAIL = 5000;   // 5s quando offline (tentativa de reconexão)
            const MAX_RETRIES  = 0;            // 0 = tentar indefinidamente enquanto offline

            let isOffline    = false;
            let banner       = null;
            let pingTimer    = null;

            function criarBanner() {
                if (banner) return;
                banner = document.createElement('div');
                banner.id = 'reconect-banner';
                banner.style.cssText = [
                    'position:fixed','top:0','left:0','width:100%','z-index:99999',
                    'background:linear-gradient(90deg,#1b333d,#6e2b3a)',
                    'color:#fff','padding:12px 20px',
                    'display:flex','align-items:center','justify-content:center',
                    'gap:14px','font-family:Inter,sans-serif','font-size:0.92em',
                    'font-weight:600','box-shadow:0 4px 20px rgba(0,0,0,0.4)',
                    'animation:slideInBanner 0.4s ease','border-bottom:2px solid rgba(255,255,255,0.15)'
                ].join(';');

                // Spinner SVG
                const spin = document.createElement('span');
                spin.innerHTML = `<svg width="20" height="20" viewBox="0 0 50 50" style="animation:spinSvg 1s linear infinite;vertical-align:middle;"><circle cx="25" cy="25" r="20" fill="none" stroke="rgba(255,255,255,0.3)" stroke-width="5"/><circle cx="25" cy="25" r="20" fill="none" stroke="#fff" stroke-width="5" stroke-dasharray="31 94" stroke-linecap="round"/></svg>`;

                const msg = document.createElement('span');
                msg.id = 'reconect-msg';
                msg.textContent = '⚡ Conexão perdida — reconectando automaticamente...';

                banner.appendChild(spin);
                banner.appendChild(msg);
                document.body.insertBefore(banner, document.body.firstChild);

                // Injeta keyframes
                if (!document.getElementById('reconect-style')) {
                    const st = document.createElement('style');
                    st.id = 'reconect-style';
                    st.textContent = `
                        @keyframes slideInBanner { from { transform:translateY(-100%); opacity:0; } to { transform:translateY(0); opacity:1; } }
                        @keyframes spinSvg { to { transform:rotate(360deg); } }
                    `;
                    document.head.appendChild(st);
                }
            }

            function removerBanner() {
                if (banner) {
                    banner.style.transition = 'transform 0.35s ease, opacity 0.35s ease';
                    banner.style.transform  = 'translateY(-100%)';
                    banner.style.opacity    = '0';
                    setTimeout(() => { if(banner){ banner.remove(); banner = null; } }, 400);
                }
            }

            function ping() {
                fetch(PING_URL, { credentials: 'same-origin', cache: 'no-store' })
                    .then(res => {
                        if (res.ok) {
                            return res.json().then(data => {
                                if (data.status === 'ok') {
                                    // Sessão válida, servidor online
                                    if (isOffline) {
                                        isOffline = false;
                                        // Feedback de reconexão bem-sucedida
                                        const msgEl = document.getElementById('reconect-msg');
                                        if (msgEl) msgEl.textContent = '✅ Reconectado com sucesso!';
                                        if (banner) banner.style.background = '#10b981';
                                        setTimeout(() => {
                                            removerBanner();
                                            // Recarrega a página para garantir dados atualizados
                                            setTimeout(() => location.reload(), 600);
                                        }, 1200);
                                    }
                                    scheduleNext(PING_INTERVAL_OK);
                                } else {
                                    // Sessão expirada (401 mas JSON com status expired)
                                    handleSessionExpired();
                                }
                            });
                        } else if (res.status === 401) {
                            // Sessão realmente expirada
                            handleSessionExpired();
                        } else {
                            // Outro erro HTTP (servidor com problema)
                            handleOffline();
                        }
                    })
                    .catch(() => {
                        // Sem conexão com o servidor (servidor offline)
                        handleOffline();
                    });
            }

            function handleOffline() {
                if (!isOffline) {
                    isOffline = true;
                    criarBanner();
                }
                scheduleNext(PING_INTERVAL_FAIL);
            }

            function handleSessionExpired() {
                // Sessão expirou de verdade → vai para login
                if (banner) {
                    const msgEl = document.getElementById('reconect-msg');
                    if (msgEl) msgEl.textContent = '⚠️ Sessão expirada — redirecionando para o login...';
                    if (banner) banner.style.background = '#ef4444';
                }
                setTimeout(() => { window.location.href = 'login.php'; }, 1800);
            }

            function scheduleNext(ms) {
                clearTimeout(pingTimer);
                pingTimer = setTimeout(ping, ms);
            }

            // Inicia o primeiro ping após 30s
            scheduleNext(PING_INTERVAL_OK);

            // Reage a eventos nativos do browser (online/offline)
            window.addEventListener('offline', () => {
                handleOffline();
            });
            window.addEventListener('online', () => {
                // Browser acha que voltou — confirma via ping
                clearTimeout(pingTimer);
                ping();
            });

            // Intercepta erros de fetch nas ações do sistema para mostrar o banner
            const _origFetch = window.fetch;
            window.fetch = function(...args) {
                return _origFetch(...args).catch(err => {
                    handleOffline();
                    return Promise.reject(err);
                });
            };
        })();
    </script>


    <!-- Modal Add Material -->
    <div class="modal-overlay" id="modalAddMaterial">
        <div class="modal-content" style="max-width: 640px;">
            <div class="modal-header">
                <h2><i class="fa-solid fa-plus-circle"></i> Novo Material</h2>
                <button class="close-modal" onclick="closeModal('modalAddMaterial')"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div class="modal-body custom-scrollbar" style="max-height: 80vh; overflow-y: auto;">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="acao" value="add_material">
                    
                    <div class="input-group">
                        <label>Imagem de Capa (Opcional)</label>
                        <div class="file-upload-wrapper">
                            <label class="file-upload-box" for="mat_capa_add">
                                <i class="fa-solid fa-image"></i>
                                <span class="file-name" id="name_mat_capa_add">Escolher Capa (Padrão 1200x600)</span>
                                <input type="file" name="capa" id="mat_capa_add" accept="image/*" onchange="document.getElementById('name_mat_capa_add').innerText = this.files[0].name" style="display:none !important;">
                            </label>
                        </div>
                    </div>

                    <div class="input-group">
                        <label>Título do Material *</label>
                        <input type="text" name="titulo" class="input-control premium-input" required placeholder="Ex: Guia de Ansiedade, Ebook de Saúde Mental...">
                    </div>

                    <div class="form-grid">
                        <div class="input-group">
                            <label>Tipo de Conteúdo *</label>
                            <select name="tipo" class="input-control premium-input" required>
                                <option value="material">📄 Apoio (PDF, Docs)</option>
                                <option value="ebook">📚 E-book / Guia</option>
                                <option value="minicurso">🎬 Curso / Aula</option>
                                <option value="indicacao">💡 Indicação de Estudo</option>
                            </select>
                        </div>
                        <div class="input-group">
                            <label>Categoria *</label>
                            <input type="text" name="categoria" class="input-control premium-input" required placeholder="Ex: Clínica, Infantil...">
                        </div>
                    </div>

                    <div class="input-group">
                        <label>Autor / Facilitador</label>
                        <input type="text" name="autor" class="input-control premium-input" placeholder="Ex: Maria Silva">
                    </div>

                    <div class="input-group">
                        <label>Link Externo (YouTube, Drive, etc.)</label>
                        <input type="url" name="url_externa" class="input-control premium-input" placeholder="https://...">
                    </div>

                    <div class="input-group">
                        <label>Descrição</label>
                        <textarea name="descricao" class="input-control premium-input" rows="3" placeholder="O que será abordado..."></textarea>
                    </div>

                    <div class="form-grid">
                        <div class="input-group">
                            <label>Visibilidade *</label>
                            <select name="visibilidade" class="input-control premium-input" required>
                                <option value="todos">🌐 Todos os Membros</option>
                                <option value="admin">🔒 Apenas Admin</option>
                            </select>
                        </div>
                        <div class="input-group">
                            <label>Upload do Arquivo *</label>
                            <div class="file-upload-wrapper">
                                <label class="file-upload-box" for="mat_file_add" style="padding: 10px;">
                                    <i class="fa-solid fa-file-arrow-up" style="font-size: 1.2em; margin-bottom: 5px;"></i>
                                    <span class="file-name" id="name_mat_file_add" style="font-size: 0.8em;">Anexar Arquivo</span>
                                    <input type="file" name="arquivo" id="mat_file_add" onchange="document.getElementById('name_mat_file_add').innerText = this.files[0].name" style="display:none !important;">
                                </label>
                            </div>
                        </div>
                    </div>

                    <div style="display:flex; gap:12px; justify-content:flex-end; margin-top: 30px;">
                        <button type="button" class="btn btn-outline" onclick="closeModal('modalAddMaterial')">Cancelar</button>
                        <button type="submit" class="btn btn-primary btn-lg" style="border-radius: 14px; font-weight: 800; padding: 12px 30px;"><i class="fa-solid fa-check"></i> Salvar Material</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Edit Material -->
    <div class="modal-overlay" id="modalEditMaterial">
        <div class="modal-content" style="max-width: 640px;">
            <div class="modal-header">
                <h2><i class="fa-solid fa-pen-to-square"></i> Editar Material</h2>
                <button class="close-modal" onclick="closeModal('modalEditMaterial')"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div class="modal-body custom-scrollbar" style="max-height: 80vh; overflow-y: auto;">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="acao" value="edit_material">
                    <input type="hidden" name="id_material" id="edit_mat_id">
                    
                    <div class="input-group">
                        <label>Alterar Imagem de Capa</label>
                        <div class="file-upload-wrapper">
                            <label class="file-upload-box" for="mat_capa_edit">
                                <i class="fa-solid fa-image"></i>
                                <span class="file-name" id="name_mat_capa_edit">Substituir Capa Atual</span>
                                <input type="file" name="capa" id="mat_capa_edit" accept="image/*" onchange="document.getElementById('name_mat_capa_edit').innerText = this.files[0].name" style="display:none !important;">
                            </label>
                        </div>
                    </div>

                    <div class="input-group">
                        <label>Título do Material *</label>
                        <input type="text" name="titulo" id="edit_mat_titulo" class="input-control premium-input" required>
                    </div>

                    <div class="form-grid">
                        <div class="input-group">
                            <label>Tipo de Conteúdo *</label>
                            <select name="tipo" id="edit_mat_tipo" class="input-control premium-input" required>
                                <option value="material">📄 Apoio (PDF, Docs)</option>
                                <option value="ebook">📚 E-book / Guia</option>
                                <option value="minicurso">🎬 Curso / Aula</option>
                                <option value="indicacao">💡 Indicação</option>
                            </select>
                        </div>
                        <div class="input-group">
                            <label>Categoria *</label>
                            <input type="text" name="categoria" id="edit_mat_categoria" class="input-control premium-input" required>
                        </div>
                    </div>

                    <div class="input-group">
                        <label>Autor / Facilitador</label>
                        <input type="text" name="autor" id="edit_mat_autor" class="input-control premium-input">
                    </div>

                    <div class="input-group">
                        <label>Link Externo (URL)</label>
                        <input type="url" name="url_externa" id="edit_mat_url_externa" class="input-control premium-input">
                    </div>

                    <div class="input-group">
                        <label>Descrição</label>
                        <textarea name="descricao" id="edit_mat_descricao" class="input-control premium-input" rows="3"></textarea>
                    </div>

                    <div class="input-group">
                        <label>Visibilidade *</label>
                        <select name="visibilidade" id="edit_mat_visibilidade" class="input-control premium-input" required>
                            <option value="todos">🌐 Todos os Membros</option>
                            <option value="admin">🔒 Apenas Admin</option>
                        </select>
                    </div>

                    <div style="display:flex; gap:12px; justify-content:flex-end; margin-top: 30px;">
                        <button type="button" class="btn btn-outline" onclick="closeModal('modalEditMaterial')">Cancelar</button>
                        <button type="submit" class="btn btn-primary btn-lg" style="border-radius: 14px; font-weight: 800; padding: 12px 30px;"><i class="fa-solid fa-save"></i> Salvar Alterações</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Add Evento -->
    <div class="modal-overlay" id="modalAddEvento">
        <div class="modal-content" style="max-width: 640px;">
            <div class="modal-header">
                <h2><i class="fa-solid fa-calendar-plus"></i> Criar Novo Encontro</h2>
                <button class="close-modal" onclick="closeModal('modalAddEvento')"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div class="modal-body">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="acao" value="add_evento">
                    
                    <div class="input-group">
                        <label>Imagem de Capa (Opcional)</label>
                        <div class="file-upload-wrapper">
                            <label class="file-upload-box" for="capa_add_file">
                                <i class="fa-solid fa-cloud-arrow-up"></i>
                                <span class="file-name" id="name_capa_add_file">Clique para subir a imagem de capa</span>
                                <span class="file-hint">Recomendado: 1200x600px</span>
                                <input type="file" name="capa" id="capa_add_file" accept="image/*" onchange="document.getElementById('name_capa_add_file').innerText = this.files[0].name" style="display:none !important;">
                            </label>
                        </div>
                    </div>

                    <div class="input-group">
                        <label>Título do Evento *</label>
                        <input type="text" name="titulo" class="input-control premium-input" required placeholder="Ex: Café de Networking, Encontro Mensal...">
                    </div>

                    <div class="form-grid">
                        <div class="input-group">
                            <label>📅 Data *</label>
                            <input type="date" id="ev_date_add" class="input-control premium-input" required onchange="syncDateTime('add')">
                        </div>
                        <div class="input-group">
                            <label>🕒 Hora *</label>
                            <input type="time" id="ev_time_add" class="input-control premium-input" required onchange="syncDateTime('add')">
                        </div>
                        <input type="hidden" name="data_evento" id="ev_dt_add_hidden" required>
                    </div>

                    <div class="input-group">
                        <label>Localização / Formato *</label>
                        <input type="text" name="local" class="input-control premium-input" required placeholder="Ex: Online (Zoom), Clínica Melodias...">
                    </div>

                    <div class="input-group">
                        <label>Link para Mapa (Opcional)</label>
                        <input type="url" name="mapa_link" class="input-control premium-input" placeholder="Google Maps ou Link do Zoom">
                    </div>

                    <div class="input-group">
                        <label>Descrição / Pauta</label>
                        <textarea name="descricao" class="input-control premium-input" rows="4" placeholder="Detalhes do encontro..."></textarea>
                    </div>

                    <div style="background:var(--bg-body); padding:20px; border-radius:12px; border:1px solid var(--border); margin-top:15px;">
                        <h4 style="margin-bottom:15px; font-size:0.9em; text-transform:uppercase; color:var(--primary); letter-spacing:1px; display:flex; align-items:center; gap:8px;"><i class="fa-solid fa-toolbox"></i> Ferramentas Interativas</h4>
                        
                        <div style="display:flex; flex-direction:column; gap:12px;">
                            <label style="display:flex; align-items:center; gap:12px; font-weight:600; cursor:pointer; font-size:0.95em;">
                                <input type="checkbox" name="rsvp_ativo" value="1" checked style="width:20px; height:20px;"> Confirmação de Presença (RSVP)
                            </label>
                            
                            <label style="display:flex; align-items:center; gap:12px; font-weight:600; cursor:pointer; font-size:0.95em;">
                                <input type="checkbox" name="permite_acompanhantes" value="1" style="width:20px; height:20px;"> Permitir acompanhantes
                            </label>

                            <label style="display:flex; align-items:center; gap:12px; font-weight:600; cursor:pointer; font-size:0.95em;">
                                <input type="checkbox" name="colaborativo_ativo" value="1" onchange="document.getElementById('colab_options_add_v2').style.display = this.checked ? 'block':'none'" style="width:20px; height:20px;"> Divisão de Itens/Organização
                            </label>
                        </div>

                        <div id="colab_options_add_v2" style="display:none; margin-top:20px; border-top:1px solid var(--border); padding-top:15px;">
                            <label style="font-size:0.85em; font-weight:700; display:block; margin-bottom:8px;">Itens para contribuição:</label>
                            <div id="add_items_container" style="display:flex; flex-direction:column; gap:8px; margin-bottom:12px;">
                                <div style="display:flex; gap:8px;">
                                    <input type="text" name="itens_colaborativos[]" class="input-control premium-input" placeholder="Ex: Café, Bolo, etc">
                                    <button type="button" onclick="removeItemFromCollab(this)" class="btn btn-outline" style="padding:8px 12px; color:var(--danger); border-color:#fee2e2;"><i class="fa-solid fa-trash-can"></i></button>
                                </div>
                            </div>
                            <button type="button" onclick="addItemToCollab('add_items_container')" class="btn btn-outline btn-sm" style="width:100%; border-style:dashed;">
                                <i class="fa-solid fa-plus-circle"></i> Adicionar Outro Item
                            </button>
                        </div>
                    </div>

                    <div style="display:flex; gap:12px; justify-content:flex-end; margin-top: 30px;">
                        <button type="button" class="btn btn-outline" onclick="closeModal('modalAddEvento')">Cancelar</button>
                        <button type="submit" class="btn btn-primary btn-lg" style="border-radius: 14px; font-weight: 800; padding: 12px 30px;"><i class="fa-solid fa-paper-plane"></i> Publicar Evento</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Edit Evento -->
    <div class="modal-overlay" id="modalEditEvento">
        <div class="modal-content" style="max-width: 640px;">
            <div class="modal-header">
                <h2><i class="fa-solid fa-calendar-pen"></i> Ajustar Encontro</h2>
                <button class="close-modal" onclick="closeModal('modalEditEvento')"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div class="modal-body">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="acao" value="edit_evento">
                    <input type="hidden" name="id_evento" id="edit_ev_id">
                    
                    <div class="input-group">
                        <label>Imagem de Capa</label>
                        <div class="file-upload-wrapper">
                            <label class="file-upload-box" for="capa_edit_file_v2">
                                <i class="fa-solid fa-image"></i>
                                <span class="file-name" id="name_capa_edit_v2">Clique para alterar a imagem</span>
                                <input type="file" name="capa" id="capa_edit_file_v2" accept="image/*" onchange="document.getElementById('name_capa_edit_v2').innerText = this.files[0].name" style="display:none !important;">
                            </label>
                        </div>
                    </div>

                    <div class="input-group">
                        <label>Título do Evento *</label>
                        <input type="text" name="titulo" id="edit_ev_titulo" class="input-control premium-input" required>
                    </div>

                    <div class="form-grid">
                        <div class="input-group">
                            <label>📅 Data *</label>
                            <input type="date" id="edit_ev_date_only" class="input-control premium-input" required onchange="syncDateTime('edit')">
                        </div>
                        <div class="input-group">
                            <label>🕒 Hora *</label>
                            <input type="time" id="edit_ev_time_only" class="input-control premium-input" required onchange="syncDateTime('edit')">
                        </div>
                        <input type="hidden" name="data_evento" id="edit_ev_data_hidden" required>
                    </div>

                    <div class="input-group">
                        <label>Localização / Formato *</label>
                        <input type="text" name="local" id="edit_ev_local" class="input-control premium-input" required>
                    </div>

                    <div class="input-group">
                        <label>Link para Mapa</label>
                        <input type="url" name="mapa_link" id="edit_ev_mapa" class="input-control premium-input">
                    </div>

                    <div class="input-group">
                        <label>Descrição / Pauta</label>
                        <textarea name="descricao" id="edit_ev_desc" class="input-control premium-input" rows="4"></textarea>
                    </div>

                    <div style="background:var(--bg-body); padding:20px; border-radius:12px; border:1px solid var(--border); margin-top:15px;">
                        <h4 style="margin-bottom:15px; font-size:0.9em; text-transform:uppercase; color:var(--primary); letter-spacing:1px;"><i class="fa-solid fa-toolbox"></i> Ferramentas</h4>
                        <div style="display:flex; flex-direction:column; gap:12px;">
                            <label style="display:flex; align-items:center; gap:12px; font-weight:600; cursor:pointer;">
                                <input type="checkbox" name="rsvp_ativo" value="1" id="edit_ev_rsvp" style="width:20px; height:20px;"> RSVP (Confirmações)
                            </label>
                            <label style="display:flex; align-items:center; gap:12px; font-weight:600; cursor:pointer;">
                                <input type="checkbox" name="permite_acompanhantes" value="1" id="edit_ev_acompanhantes" style="width:20px; height:20px;"> Acompanhantes
                            </label>
                            <label style="display:flex; align-items:center; gap:12px; font-weight:600; cursor:pointer;">
                                <input type="checkbox" name="colaborativo_ativo" value="1" id="edit_ev_colaborativo" onchange="document.getElementById('colab_options_edit_v2').style.display = this.checked ? 'block':'none'" style="width:20px; height:20px;"> Divisão Colaborativa
                            </label>
                        </div>
                        <div id="colab_options_edit_v2" style="display:none; margin-top:20px; border-top:1px solid var(--border); padding-top:15px;">
                            <label style="font-size:0.85em; font-weight:700; display:block; margin-bottom:8px;">Itens para contribuição:</label>
                            <div id="edit_items_container" style="display:flex; flex-direction:column; gap:8px; margin-bottom:12px;">
                                <!-- Gerado via JS -->
                            </div>
                            <button type="button" onclick="addItemToCollab('edit_items_container')" class="btn btn-outline btn-sm" style="width:100%; border-style:dashed;">
                                <i class="fa-solid fa-plus-circle"></i> Adicionar Outro Item
                            </button>
                        </div>
                    </div>

                    <div style="display:flex; gap:12px; justify-content:flex-end; margin-top: 30px;">
                        <button type="button" class="btn btn-outline" onclick="closeModal('modalEditEvento')">Cancelar</button>
                        <button type="submit" class="btn btn-primary btn-lg" style="border-radius: 14px; font-weight: 800; padding: 12px 30px;"><i class="fa-solid fa-save"></i> Salvar Alterações</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Relatório do Evento -->
    <div class="modal-overlay" id="modalRelatorioEvento">
        <div class="modal-content" style="max-width: 680px;">
            <div class="modal-header">
                <h2><i class="fa-solid fa-chart-line"></i> Gestão do Evento</h2>
                <button class="close-modal" onclick="closeModal('modalRelatorioEvento')"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div id="relatorio_content" class="modal-body" style="padding:0;">
                <div style="text-align:center; padding:60px;">
                    <i class="fa-solid fa-circle-notch fa-spin fa-2x" style="color:var(--primary);"></i>
                </div>
            </div>
        </div>
    </div>
    <!-- Modal Visualizar Detalhes do Evento -->
    <div class="modal-overlay" id="modalVerEvento" style="z-index: 10000;">
        <div class="modal-content" style="max-width: 600px;">
            <div class="modal-header">
                <h2 id="ver_ev_titulo" style="color: var(--primary); font-weight: 900; font-size: 1.4em;">Detalhes do Evento</h2>
                <button class="close-modal" onclick="closeModal('modalVerEvento')"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div class="modal-body custom-scrollbar" style="max-height: 80vh; overflow-y: auto;">
                <div id="ver_ev_capa_container" style="width: 100%; height: 220px; border-radius: 12px; overflow: hidden; margin-bottom: 20px; display: none; position: relative; background: #000;">
                    <img id="ver_ev_capa" src="" style="width: 100%; height: 100%; object-fit: contain; position: relative; z-index: 2;">
                    <div id="ver_ev_capa_blur" style="position: absolute; inset: 0; background-size: cover; background-position: center; filter: blur(20px) brightness(0.4); opacity: 0.7; transform: scale(1.2);"></div>
                </div>

                <div style="display: flex; gap: 15px; margin-bottom: 20px; flex-wrap: wrap;">
                    <div style="background: #f8fafc; padding: 10px 15px; border-radius: 10px; border: 1px solid #e2e8f0; font-size: 0.85em; flex: 1; min-width: 140px;">
                        <i class="fa-regular fa-calendar" style="color: var(--primary); margin-right: 6px;"></i> <strong id="ver_ev_data"></strong>
                    </div>
                    <div id="ver_ev_local_container" style="background: #f8fafc; padding: 10px 15px; border-radius: 10px; border: 1px solid #e2e8f0; font-size: 0.85em; flex: 1; min-width: 140px;">
                        <i class="fa-solid fa-location-dot" style="color: var(--primary); margin-right: 6px;"></i> <strong id="ver_ev_local"></strong>
                    </div>
                </div>

                <div style="background: rgba(110,43,58,0.03); border-left: 4px solid var(--primary); padding: 15px; border-radius: 0 12px 12px 0; margin-bottom: 25px;">
                    <h4 style="font-size: 0.75em; text-transform: uppercase; letter-spacing: 1px; color: var(--text-muted); margin-bottom: 8px;">Descrição Completa</h4>
                    <p id="ver_ev_descricao" style="font-size: 0.95em; color: var(--text-main); line-height: 1.7; white-space: pre-wrap; margin: 0;"></p>
                </div>

                <div id="ver_ev_mapa_container" style="margin-top: 10px; display: none;">
                    <a id="ver_ev_mapa_link" href="" target="_blank" class="btn btn-outline btn-block" style="border-radius: 12px; font-weight: 700; color: var(--primary); border-color: var(--primary);">
                        <i class="fa-solid fa-location-arrow"></i> Ver no Google Maps / Abrir Link
                    </a>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline btn-block" onclick="closeModal('modalVerEvento')">Fechar</button>
            </div>
        </div>
    </div>

    <script>
    function verDetalhesEvento(ev) {
        document.getElementById('ver_ev_titulo').innerText = ev.titulo;
        // Fix for iOS/Safari date formatting by replacing space with T if needed
        const dateStr = ev.data_evento ? ev.data_evento.replace(' ', 'T') : '';
        document.getElementById('ver_ev_data').innerText = dateStr ? new Date(dateStr).toLocaleString('pt-BR', { dateStyle: 'short', timeStyle: 'short' }).replace(',', ' às') : 'Data não informada';
        document.getElementById('ver_ev_descricao').innerText = ev.descricao || 'Nenhuma descrição fornecida.';
        
        // Capa
        const capaCont = document.getElementById('ver_ev_capa_container');
        if (ev.capa) {
            document.getElementById('ver_ev_capa').src = ev.capa;
            document.getElementById('ver_ev_capa_blur').style.backgroundImage = `url('${ev.capa}')`;
            capaCont.style.display = 'block';
        } else {
            capaCont.style.display = 'none';
        }

        // Local
        const localCont = document.getElementById('ver_ev_local_container');
        if (ev.local) {
            document.getElementById('ver_ev_local').innerText = ev.local;
            localCont.style.display = 'block';
        } else {
            localCont.style.display = 'none';
        }

        // Mapa
        const mapaCont = document.getElementById('ver_ev_mapa_container');
        if (ev.mapa_link) {
            document.getElementById('ver_ev_mapa_link').href = ev.mapa_link;
            mapaCont.style.display = 'block';
        } else {
            mapaCont.style.display = 'none';
        }

        openModal('modalVerEvento');
    }

    function toggleContribuicao(evId, item, modo) {
        if (modo === 'remover') {
            confirmarAcao({
                titulo: 'Remover Item',
                msg: `Você não quer mais levar "<b>${item}</b>"?`,
                btnText: 'Sim, Remover',
                btnClass: 'btn-danger',
                callback: function() {
                    submitForm({ acao: 'toggle_contribuicao', evento_id: evId, item_nome: item, modo: 'remover' });
                }
            });
        } else {
            confirmarAcao({
                titulo: 'Apoiar Encontro',
                msg: `Você se comprometeu a levar "<b>${item}</b>".<br><br><label style="font-size:0.85em; font-weight:600; display:block; margin-bottom:8px;">Observação? (Opcional)</label><input type="text" id="contrib_obs_input" class="premium-input" placeholder="Ex: Levando 2 pacotes..." style="width:100%;">`,
                btnText: 'Confirmar Apoio',
                btnClass: 'btn-success',
                callback: function() {
                    const obs = document.getElementById('contrib_obs_input').value;
                    submitForm({ acao: 'toggle_contribuicao', evento_id: evId, item_nome: item, modo: 'adicionar', obs: obs });
                }
            });
        }
    }

    function submitForm(dados) {
        const form = document.createElement('form');
        form.method = 'POST';
        for (const key in dados) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = key;
            input.value = dados[key];
            form.appendChild(input);
        }
        document.body.appendChild(form);
        form.submit();
    }
    </script>

</body>
</html>
