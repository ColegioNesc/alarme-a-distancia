-- ========================================================
-- Arquivo Otimizado para Importação no INFINITYFREE
-- Projeto: Alarme a Distância
-- ========================================================
-- INSTRUÇÃO PARA O INFINITYFREE:
-- 1. No Painel do InfinityFree (VistaPanel), crie o banco MySQL (ex: epiz_12345678_alarme).
-- 2. Abra o phpMyAdmin pelo painel do InfinityFree.
-- 3. Clique no nome do seu banco de dados na coluna esquerda do phpMyAdmin.
-- 4. Clique na aba "Importar" (Import).
-- 5. Selecione este arquivo (infinityfree_import.sql) e clique em "Executar" (Go).
--
-- NOTA: Este arquivo NÃO contém "CREATE DATABASE" nem "USE",
-- evitando o erro "#1044 - Access denied" do InfinityFree.
-- ========================================================

SET FOREIGN_KEY_CHECKS = 0;

-- --------------------------------------------------------
-- 1. Tabela de Usuários
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `usuarios` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nome` VARCHAR(150) NOT NULL,
  `email` VARCHAR(191) NOT NULL,
  `senha` VARCHAR(255) NOT NULL,
  `raio_padrao` INT UNSIGNED NOT NULL DEFAULT 300,
  `tipo_alarme_padrao` ENUM('som', 'vibracao', 'ambos') NOT NULL DEFAULT 'ambos',
  `som_alarme` VARCHAR(50) NOT NULL DEFAULT 'sirene',
  `criado_em` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_usuarios_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 2. Tabela de Destinos Favoritos
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `destinos_favoritos` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `usuario_id` INT UNSIGNED NOT NULL,
  `titulo` VARCHAR(100) NOT NULL,
  `endereco` VARCHAR(255) NOT NULL,
  `lat` DECIMAL(10, 8) NOT NULL,
  `lng` DECIMAL(11, 8) NOT NULL,
  `raio_preferido` INT UNSIGNED NOT NULL DEFAULT 300,
  `criado_em` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_favoritos_usuario` (`usuario_id`),
  CONSTRAINT `fk_favoritos_usuario` 
    FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) 
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 3. Tabela de Viagens / Trajetos Monitorados
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `viagens` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `usuario_id` INT UNSIGNED NULL,
  `destino_nome` VARCHAR(255) NOT NULL,
  `destino_lat` DECIMAL(10, 8) NOT NULL,
  `destino_lng` DECIMAL(11, 8) NOT NULL,
  `raio_metros` INT UNSIGNED NOT NULL DEFAULT 300,
  `tipo_alarme` VARCHAR(50) NOT NULL DEFAULT 'ambos',
  `som_alarme` VARCHAR(50) NOT NULL DEFAULT 'sirene',
  `status` ENUM('ativo', 'pausado', 'concluido', 'cancelado') NOT NULL DEFAULT 'ativo',
  `acionado` TINYINT(1) NOT NULL DEFAULT 0,
  `criado_em` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `concluido_em` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_viagens_usuario` (`usuario_id`),
  KEY `idx_viagens_status` (`status`),
  CONSTRAINT `fk_viagens_usuario` 
    FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) 
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Dados Iniciais / Demonstração (Opcional)
-- Senha de teste: 123456
-- --------------------------------------------------------
INSERT INTO `usuarios` (`id`, `nome`, `email`, `senha`, `raio_padrao`, `tipo_alarme_padrao`, `som_alarme`)
VALUES (1, 'Usuário Teste', 'teste@teste.com', '$2y$10$62PRxIAJyzWelWq0W541De0/w5sDpKTzIh4ZF6veH5/biB6u2tJ7K', 300, 'ambos', 'sirene')
ON DUPLICATE KEY UPDATE `nome` = VALUES(`nome`);

INSERT INTO `destinos_favoritos` (`id`, `usuario_id`, `titulo`, `endereco`, `lat`, `lng`, `raio_preferido`)
VALUES (1, 1, 'Trabalho', 'Avenida Paulista, São Paulo - SP', -23.56168400, -46.65598100, 300)
ON DUPLICATE KEY UPDATE `titulo` = VALUES(`titulo`);

SET FOREIGN_KEY_CHECKS = 1;

