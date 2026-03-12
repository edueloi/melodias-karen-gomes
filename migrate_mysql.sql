-- ============================================================
-- MELODIAS — Migração para MySQL (HostGator)
-- Banco: edua6062_melodias
-- Usuário: edua6062_karengomes
-- ============================================================
-- Execute este arquivo no phpMyAdmin do HostGator:
--   Selecione o banco > aba "SQL" > cole e execute
-- ============================================================

SET NAMES utf8mb4;
SET time_zone = '-03:00';

-- ============================================================
-- 1. TABELA: profissionais (usuários)
-- ============================================================
CREATE TABLE IF NOT EXISTS `profissionais` (
    `id`                  INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `nome`                VARCHAR(255) NOT NULL,
    `email`               VARCHAR(255) NOT NULL UNIQUE,
    `senha`               VARCHAR(255) DEFAULT NULL,
    `especialidade`       VARCHAR(100) DEFAULT NULL,
    `whatsapp`            VARCHAR(30) DEFAULT NULL,
    `genero`              VARCHAR(30) DEFAULT 'Não declarado',
    `role`                VARCHAR(20) DEFAULT 'user',
    `status`              VARCHAR(20) DEFAULT 'ativo',
    `foto`                VARCHAR(255) DEFAULT NULL,
    `bio`                 TEXT DEFAULT NULL,
    `registro_tipo`       VARCHAR(20) DEFAULT NULL,
    `registro_numero`     VARCHAR(50) DEFAULT NULL,
    `area_atuacao`        VARCHAR(255) DEFAULT NULL,
    `formacao_superior`   VARCHAR(255) DEFAULT NULL,
    `formacao_pos`        TEXT DEFAULT NULL,
    `instagram`           VARCHAR(255) DEFAULT NULL,
    `website`             VARCHAR(255) DEFAULT NULL,
    `endereco`            VARCHAR(255) DEFAULT NULL,
    `descricao_trabalho`  TEXT DEFAULT NULL,
    `aceita_parcerias`    VARCHAR(10) DEFAULT 'Não',
    `preco_social`        VARCHAR(10) DEFAULT 'Não',
    `data_cadastro`       DATETIME DEFAULT CURRENT_TIMESTAMP,
    `created_at`          DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at`          DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_email` (`email`),
    INDEX `idx_status` (`status`),
    INDEX `idx_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 2. TABELA: materiais
-- ============================================================
CREATE TABLE IF NOT EXISTS `materiais` (
    `id`           INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `titulo`       VARCHAR(255) NOT NULL,
    `descricao`    TEXT DEFAULT NULL,
    `categoria`    VARCHAR(100) NOT NULL,
    `tipo`         VARCHAR(30) DEFAULT 'arquivo',
    `caminho`      VARCHAR(500) DEFAULT NULL,
    `url_externa`  VARCHAR(500) DEFAULT NULL,
    `capa`         VARCHAR(255) DEFAULT NULL,
    `autor`        VARCHAR(255) DEFAULT NULL,
    `visibilidade` VARCHAR(20) DEFAULT 'todos',
    `created_by`   INT(11) UNSIGNED DEFAULT NULL,
    `created_at`   DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at`   DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_categoria` (`categoria`),
    INDEX `idx_visibilidade` (`visibilidade`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 3. TABELA: sugestoes
-- ============================================================
CREATE TABLE IF NOT EXISTS `sugestoes` (
    `id`             INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`        INT(11) UNSIGNED NOT NULL,
    `texto`          TEXT NOT NULL,
    `status`         VARCHAR(20) DEFAULT 'nova',
    `resposta_admin` TEXT DEFAULT NULL,
    `created_at`     DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at`     DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 4. TABELA: forum_posts
-- ============================================================
CREATE TABLE IF NOT EXISTS `forum_posts` (
    `id`         INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`    INT(11) UNSIGNED NOT NULL,
    `titulo`     VARCHAR(255) NOT NULL,
    `conteudo`   TEXT NOT NULL,
    `categoria`  VARCHAR(50) DEFAULT 'geral',
    `views`      INT(11) DEFAULT 0,
    `status`     VARCHAR(20) DEFAULT 'ativo',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_categoria` (`categoria`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 5. TABELA: forum_comentarios
-- ============================================================
CREATE TABLE IF NOT EXISTS `forum_comentarios` (
    `id`          INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `post_id`     INT(11) UNSIGNED NOT NULL,
    `user_id`     INT(11) UNSIGNED NOT NULL,
    `comentario`  TEXT NOT NULL,
    `created_at`  DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_post_id` (`post_id`),
    INDEX `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 6. TABELA: forum_curtidas
-- ============================================================
CREATE TABLE IF NOT EXISTS `forum_curtidas` (
    `id`         INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `post_id`    INT(11) UNSIGNED NOT NULL,
    `user_id`    INT(11) UNSIGNED NOT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_curtida` (`post_id`, `user_id`),
    INDEX `idx_post_id` (`post_id`),
    INDEX `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 7. TABELA: eventos
-- ============================================================
CREATE TABLE IF NOT EXISTS `eventos` (
    `id`          INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `titulo`      VARCHAR(255) NOT NULL,
    `descricao`   TEXT DEFAULT NULL,
    `data_evento` DATETIME DEFAULT NULL,
    `local`       VARCHAR(255) DEFAULT NULL,
    `mapa_link`   VARCHAR(500) DEFAULT NULL,
    `created_by`  INT(11) UNSIGNED DEFAULT NULL,
    `created_at`  DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_data_evento` (`data_evento`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 8. TABELA: eventos_presenca
-- ============================================================
CREATE TABLE IF NOT EXISTS `eventos_presenca` (
    `id`         INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `evento_id`  INT(11) UNSIGNED NOT NULL,
    `user_id`    INT(11) UNSIGNED NOT NULL,
    `status`     VARCHAR(20) DEFAULT 'confirmado',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_presenca` (`evento_id`, `user_id`),
    INDEX `idx_evento_id` (`evento_id`),
    INDEX `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 9. TABELA: configuracoes
-- ============================================================
CREATE TABLE IF NOT EXISTS `configuracoes` (
    `id`         INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `chave`      VARCHAR(100) NOT NULL UNIQUE,
    `valor`      TEXT DEFAULT NULL,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_chave` (`chave`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 10. DADOS PADRÃO: configuracoes
-- ============================================================
INSERT IGNORE INTO `configuracoes` (`chave`, `valor`) VALUES
('whatsapp_auto_abrir', '1'),
('whatsapp_mensagem_template', '🎉 *Bem-vindo(a) ao Melodias!*\n\nOlá {NOME}, sua solicitação foi *aprovada*!\n\n📋 *Seus dados de acesso:*\n\n🔗 *Link:*\n{LINK}\n\n📧 *Email/Login:*\n{EMAIL}\n\n🔑 *Senha Temporária:*\n{SENHA}\n\n⚠️ _Recomendamos trocar sua senha após o primeiro acesso._\n\n✨ Agora você faz parte da nossa rede de profissionais em saúde mental!');

-- ============================================================
-- FIM DA MIGRAÇÃO
-- ============================================================
