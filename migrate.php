<?php
/**
 * MIGRATE.PHP — Melodias Karen Gomes
 * Roda migração do banco de dados direto pelo browser.
 * APAGUE ESTE ARQUIVO APÓS EXECUTAR!
 */

require_once 'config.php';

$pdo = getDB();
$driver = dbDriver(); // 'mysql' em produção, 'sqlite' local

$results = [];

function runMigration(PDO $pdo, string $driver, string $desc, string $mysql_sql, string $sqlite_sql = ''): array {
    $sql = ($driver === 'mysql') ? $mysql_sql : ($sqlite_sql ?: $mysql_sql);
    try {
        $pdo->exec($sql);
        return ['status' => 'ok', 'desc' => $desc, 'msg' => 'Executado com sucesso'];
    } catch (PDOException $e) {
        $msg = $e->getMessage();
        // Ignorar erros de "já existe"
        if (
            strpos($msg, 'Duplicate column') !== false ||   // MySQL: coluna já existe
            strpos($msg, 'already exists') !== false ||     // SQLite: já existe
            strpos($msg, "1060") !== false                  // MySQL código de coluna duplicada
        ) {
            return ['status' => 'skip', 'desc' => $desc, 'msg' => 'Já existia, ignorado'];
        }
        return ['status' => 'error', 'desc' => $desc, 'msg' => $msg];
    }
}

// ====================================================
// TABELA: profissionais — novas colunas de perfil
// ====================================================
$prof_cols = [
    ['role', "ADD COLUMN role VARCHAR(50) DEFAULT 'user'", "ADD COLUMN role TEXT DEFAULT 'user'"],
    ['status', "ADD COLUMN status VARCHAR(50) DEFAULT 'ativo'", "ADD COLUMN status TEXT DEFAULT 'ativo'"],
    ['senha', "ADD COLUMN senha VARCHAR(255)", "ADD COLUMN senha TEXT"],
    ['foto', "ADD COLUMN foto VARCHAR(512)", "ADD COLUMN foto TEXT"],
    ['bio', "ADD COLUMN bio TEXT", "ADD COLUMN bio TEXT"],
    ['registro_tipo', "ADD COLUMN registro_tipo VARCHAR(50)", "ADD COLUMN registro_tipo TEXT"],
    ['registro_numero', "ADD COLUMN registro_numero VARCHAR(50)", "ADD COLUMN registro_numero TEXT"],
    ['area_atuacao', "ADD COLUMN area_atuacao VARCHAR(255)", "ADD COLUMN area_atuacao TEXT"],
    ['formacao_superior', "ADD COLUMN formacao_superior VARCHAR(255)", "ADD COLUMN formacao_superior TEXT"],
    ['formacao_pos', "ADD COLUMN formacao_pos VARCHAR(255)", "ADD COLUMN formacao_pos TEXT"],
    ['instagram', "ADD COLUMN instagram VARCHAR(100)", "ADD COLUMN instagram TEXT"],
    ['website', "ADD COLUMN website VARCHAR(255)", "ADD COLUMN website TEXT"],
    ['genero', "ADD COLUMN genero VARCHAR(50) DEFAULT 'Não declarado'", "ADD COLUMN genero TEXT DEFAULT 'Não declarado'"],
    ['endereco', "ADD COLUMN endereco TEXT", "ADD COLUMN endereco TEXT"],
    ['descricao_trabalho', "ADD COLUMN descricao_trabalho TEXT", "ADD COLUMN descricao_trabalho TEXT"],
    ['aceita_parcerias', "ADD COLUMN aceita_parcerias VARCHAR(20) DEFAULT 'Não'", "ADD COLUMN aceita_parcerias TEXT DEFAULT 'Não'"],
    ['preco_social', "ADD COLUMN preco_social VARCHAR(20) DEFAULT 'Não'", "ADD COLUMN preco_social TEXT DEFAULT 'Não'"],
    ['welcome_seen', "ADD COLUMN welcome_seen TINYINT(1) DEFAULT 0", "ADD COLUMN welcome_seen INTEGER DEFAULT 0"],
    ['created_at', "ADD COLUMN created_at DATETIME DEFAULT CURRENT_TIMESTAMP", "ADD COLUMN created_at DATETIME DEFAULT CURRENT_TIMESTAMP"],
    ['updated_at', "ADD COLUMN updated_at DATETIME DEFAULT CURRENT_TIMESTAMP", "ADD COLUMN updated_at DATETIME DEFAULT CURRENT_TIMESTAMP"]
];

foreach ($prof_cols as $col) {
    $results[] = runMigration($pdo, $driver, "profissionais: coluna {$col[0]}", 
        "ALTER TABLE profissionais {$col[1]}",
        "ALTER TABLE profissionais {$col[2]}"
    );
}

// ====================================================
// TABELA: materiais — novas colunas
// ====================================================
$mat_cols = [
    ['descricao', "ADD COLUMN descricao TEXT", "ADD COLUMN descricao TEXT"],
    ['tipo', "ADD COLUMN tipo VARCHAR(50) DEFAULT 'material'", "ADD COLUMN tipo TEXT DEFAULT 'material'"],
    ['autor', "ADD COLUMN autor VARCHAR(255)", "ADD COLUMN autor TEXT"],
    ['url_externa', "ADD COLUMN url_externa TEXT", "ADD COLUMN url_externa TEXT"],
    ['capa', "ADD COLUMN capa VARCHAR(512)", "ADD COLUMN capa TEXT"],
    ['created_by', "ADD COLUMN created_by INT", "ADD COLUMN created_by INTEGER"],
    ['created_at', "ADD COLUMN created_at DATETIME DEFAULT CURRENT_TIMESTAMP", "ADD COLUMN created_at DATETIME DEFAULT CURRENT_TIMESTAMP"],
    ['updated_at', "ADD COLUMN updated_at DATETIME DEFAULT CURRENT_TIMESTAMP", "ADD COLUMN updated_at DATETIME DEFAULT CURRENT_TIMESTAMP"]
];

foreach ($mat_cols as $col) {
    $results[] = runMigration($pdo, $driver, "materiais: coluna {$col[0]}", 
        "ALTER TABLE materiais {$col[1]}",
        "ALTER TABLE materiais {$col[2]}"
    );
}

// ====================================================
// TABELA: eventos — adicionar colunas novas
// ====================================================
$results[] = runMigration($pdo, $driver,
    'eventos: coluna capa (imagem de capa)',
    "ALTER TABLE eventos ADD COLUMN capa VARCHAR(512) DEFAULT NULL",
    "ALTER TABLE eventos ADD COLUMN capa TEXT DEFAULT NULL"
);

$results[] = runMigration($pdo, $driver,
    'eventos: coluna rsvp_ativo',
    "ALTER TABLE eventos ADD COLUMN rsvp_ativo TINYINT(1) NOT NULL DEFAULT 1",
    "ALTER TABLE eventos ADD COLUMN rsvp_ativo INTEGER DEFAULT 1"
);

$results[] = runMigration($pdo, $driver,
    'eventos: coluna permite_acompanhantes',
    "ALTER TABLE eventos ADD COLUMN permite_acompanhantes TINYINT(1) NOT NULL DEFAULT 0",
    "ALTER TABLE eventos ADD COLUMN permite_acompanhantes INTEGER DEFAULT 0"
);

$results[] = runMigration($pdo, $driver,
    'eventos: coluna colaborativo_ativo',
    "ALTER TABLE eventos ADD COLUMN colaborativo_ativo TINYINT(1) NOT NULL DEFAULT 0",
    "ALTER TABLE eventos ADD COLUMN colaborativo_ativo INTEGER DEFAULT 0"
);

$results[] = runMigration($pdo, $driver,
    'eventos: coluna itens_colaborativos',
    "ALTER TABLE eventos ADD COLUMN itens_colaborativos TEXT DEFAULT NULL",
    "ALTER TABLE eventos ADD COLUMN itens_colaborativos TEXT DEFAULT NULL"
);

$results[] = runMigration($pdo, $driver,
    'eventos: coluna mapa_link',
    "ALTER TABLE eventos ADD COLUMN mapa_link VARCHAR(1024) DEFAULT NULL",
    "ALTER TABLE eventos ADD COLUMN mapa_link TEXT DEFAULT NULL"
);

// ====================================================
// TABELA: eventos_presenca — adicionar acompanhantes
// ====================================================
$results[] = runMigration($pdo, $driver,
    'eventos_presenca: coluna acompanhantes',
    "ALTER TABLE eventos_presenca ADD COLUMN acompanhantes INT NOT NULL DEFAULT 0",
    "ALTER TABLE eventos_presenca ADD COLUMN acompanhantes INTEGER DEFAULT 0"
);

$results[] = runMigration($pdo, $driver,
    'eventos_presenca: coluna contribuicao_obs',
    "ALTER TABLE eventos_presenca ADD COLUMN contribuicao_obs TEXT DEFAULT NULL",
    "ALTER TABLE eventos_presenca ADD COLUMN contribuicao_obs TEXT DEFAULT NULL"
);

// ====================================================
// TABELA: eventos_presenca_externa (nova — convidados externos)
// ====================================================
$mysql_ext = "
CREATE TABLE IF NOT EXISTS eventos_presenca_externa (
    id INT AUTO_INCREMENT PRIMARY KEY,
    evento_id INT NOT NULL,
    nome VARCHAR(255) NOT NULL,
    whatsapp VARCHAR(30) DEFAULT NULL,
    acompanhantes INT NOT NULL DEFAULT 0,
    contribuicao_item VARCHAR(255) DEFAULT NULL,
    contribuicao_obs TEXT DEFAULT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'confirmado',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    KEY idx_evento_id (evento_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";
$sqlite_ext = "
CREATE TABLE IF NOT EXISTS eventos_presenca_externa (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    evento_id INTEGER NOT NULL,
    nome TEXT NOT NULL,
    whatsapp TEXT DEFAULT NULL,
    acompanhantes INTEGER DEFAULT 0,
    contribuicao_item TEXT DEFAULT NULL,
    contribuicao_obs TEXT DEFAULT NULL,
    status TEXT DEFAULT 'confirmado',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
";
$results[] = runMigration($pdo, $driver,
    'CREATE TABLE eventos_presenca_externa',
    $mysql_ext, $sqlite_ext
);

// Garantir que a coluna status existe caso a tabela já tenha sido criada antes
$results[] = runMigration($pdo, $driver,
    'eventos_presenca_externa: coluna status',
    "ALTER TABLE eventos_presenca_externa ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'confirmado'",
    "ALTER TABLE eventos_presenca_externa ADD COLUMN status TEXT DEFAULT 'confirmado'"
);

// ====================================================
// TABELA: eventos_contribuicoes (nova — divisão de itens)
// ====================================================
$mysql_contrib = "
CREATE TABLE IF NOT EXISTS eventos_contribuicoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    evento_id INT NOT NULL,
    user_id INT NOT NULL,
    item_nome VARCHAR(255) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    KEY idx_evento_user (evento_id, user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";
$sqlite_contrib = "
CREATE TABLE IF NOT EXISTS eventos_contribuicoes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    evento_id INTEGER NOT NULL,
    user_id INTEGER NOT NULL,
    item_nome TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
";
$results[] = runMigration($pdo, $driver,
    'CREATE TABLE eventos_contribuicoes',
    $mysql_contrib, $sqlite_contrib
);

// ====================================================
// TABELA: convites — links de convite externos
// ====================================================
$mysql_convites = "
CREATE TABLE IF NOT EXISTS convites (
    id INT AUTO_INCREMENT PRIMARY KEY,
    token VARCHAR(64) NOT NULL UNIQUE,
    expira_em DATETIME NOT NULL,
    limite_usos INT NOT NULL DEFAULT 40,
    usos_atuais INT NOT NULL DEFAULT 0,
    role_atribuida VARCHAR(20) NOT NULL DEFAULT 'usuario',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    KEY idx_token (token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";
$sqlite_convites = "
CREATE TABLE IF NOT EXISTS convites (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    token TEXT NOT NULL UNIQUE,
    expira_em DATETIME NOT NULL,
    limite_usos INTEGER DEFAULT 40,
    usos_atuais INTEGER DEFAULT 0,
    role_atribuida TEXT DEFAULT 'usuario',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
";
$results[] = runMigration($pdo, $driver,
    'CREATE TABLE convites',
    $mysql_convites, $sqlite_convites
);

// ====================================================
// SAÍDA HTML
// ====================================================
$ok    = array_filter($results, fn($r) => $r['status'] === 'ok');
$skip  = array_filter($results, fn($r) => $r['status'] === 'skip');
$error = array_filter($results, fn($r) => $r['status'] === 'error');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Migrate — Melodias</title>
<style>
  body { font-family: 'Segoe UI', sans-serif; background: #0f172a; color: #f1f5f9; padding: 40px; }
  h1 { color: #a78bfa; margin-bottom: 30px; }
  .card { background: #1e293b; border-radius: 12px; padding: 20px; margin-bottom: 12px; border-left: 5px solid #3b82f6; display: flex; gap: 16px; align-items: flex-start; }
  .card.ok    { border-color: #10b981; }
  .card.skip  { border-color: #f59e0b; }
  .card.error { border-color: #ef4444; }
  .badge { padding: 3px 10px; border-radius: 20px; font-size: 0.75em; font-weight: 700; text-transform: uppercase; white-space: nowrap; }
  .badge.ok    { background: #10b981; color: #fff; }
  .badge.skip  { background: #f59e0b; color: #fff; }
  .badge.error { background: #ef4444; color: #fff; }
  .desc { font-weight: 600; margin-bottom: 4px; }
  .msg  { font-size: 0.85em; color: #94a3b8; }
  .summary { background: #1e293b; border-radius: 12px; padding: 25px; margin-bottom: 30px; display: flex; gap: 30px; }
  .sum-item { text-align: center; }
  .sum-num { font-size: 2em; font-weight: 800; }
  .sum-lbl { font-size: 0.8em; color: #64748b; }
  .warning { background: #7c2d12; border-radius: 12px; padding: 20px; margin-top: 30px; font-size: 0.95em; }
  .warning strong { color: #fca5a5; }
  .env { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 0.8em; font-weight: 700; background: <?= IS_PRODUCAO ? '#16a34a' : '#2563eb' ?>; color: white; }
</style>
</head>
<body>
<h1>🔧 Migração do Banco de Dados</h1>

<div style="margin-bottom:20px;">
  Ambiente: <span class="env"><?= IS_PRODUCAO ? '🌐 PRODUÇÃO (MySQL)' : '💻 Local (SQLite)' ?></span>
  &nbsp;·&nbsp;
  Banco: <strong><?= IS_PRODUCAO ? DB_MYSQL_NAME . ' @ ' . DB_MYSQL_HOST : DB_FILE ?></strong>
</div>

<div class="summary">
  <div class="sum-item">
    <div class="sum-num" style="color:#10b981"><?= count($ok) ?></div>
    <div class="sum-lbl">Executados</div>
  </div>
  <div class="sum-item">
    <div class="sum-num" style="color:#f59e0b"><?= count($skip) ?></div>
    <div class="sum-lbl">Já existiam</div>
  </div>
  <div class="sum-item">
    <div class="sum-num" style="color:#ef4444"><?= count($error) ?></div>
    <div class="sum-lbl">Erros</div>
  </div>
</div>

<?php foreach ($results as $r): ?>
<div class="card <?= $r['status'] ?>">
  <span class="badge <?= $r['status'] ?>"><?= $r['status'] === 'ok' ? '✓ OK' : ($r['status'] === 'skip' ? '~ Skip' : '✗ Erro') ?></span>
  <div>
    <div class="desc"><?= htmlspecialchars($r['desc']) ?></div>
    <div class="msg"><?= htmlspecialchars($r['msg']) ?></div>
  </div>
</div>
<?php endforeach; ?>

<?php if (count($error) === 0): ?>
<div style="background:#14532d;border-radius:12px;padding:20px;margin-top:20px;">
  ✅ <strong>Migração concluída sem erros!</strong><br>
  <small style="color:#86efac;">Tudo pronto. Você pode usar o sistema normalmente.</small>
</div>
<?php else: ?>
<div style="background:#7f1d1d;border-radius:12px;padding:20px;margin-top:20px;">
  ❌ <strong><?= count($error) ?> erro(s) encontrado(s).</strong> Verifique os itens acima.
</div>
<?php endif; ?>

<div class="warning">
  ⚠️ <strong>IMPORTANTE:</strong> Apague este arquivo após executar!<br>
  <code>melodias.karengomes.com.br/migrate.php</code> não deve ficar acessível publicamente.
</div>

</body>
</html>
