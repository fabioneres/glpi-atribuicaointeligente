CREATE TABLE IF NOT EXISTS `glpi_plugin_atribuicaointeligente_config_display` (
   `id` int unsigned NOT NULL AUTO_INCREMENT,
   PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `glpi_plugin_atribuicaointeligente_config_display` (`id`)
SELECT 1
WHERE NOT EXISTS (
   SELECT 1
   FROM `glpi_plugin_atribuicaointeligente_config_display`
   WHERE `id` = 1
);

CREATE TABLE IF NOT EXISTS `glpi_plugin_atribuicaointeligente_configs` (
   `id` int unsigned NOT NULL AUTO_INCREMENT,
   `auto_assign_group` tinyint NOT NULL DEFAULT 1,
   `auto_assign_type` tinyint NOT NULL DEFAULT 0,
   `auto_assign_mode` tinyint NOT NULL DEFAULT 0,
   `exclude_managers` tinyint NOT NULL DEFAULT 1,
   `date_creation` timestamp NULL DEFAULT NULL,
   `date_mod` timestamp NULL DEFAULT NULL,
   PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

INSERT INTO `glpi_plugin_atribuicaointeligente_configs`
   (`id`, `auto_assign_group`, `auto_assign_type`, `auto_assign_mode`, `exclude_managers`, `date_creation`, `date_mod`)
SELECT 1, 1, 0, 0, 1, NOW(), NOW()
WHERE NOT EXISTS (
   SELECT 1
   FROM `glpi_plugin_atribuicaointeligente_configs`
   WHERE `id` = 1
);

CREATE TABLE IF NOT EXISTS `glpi_plugin_atribuicaointeligente_assignments` (
   `id` int unsigned NOT NULL AUTO_INCREMENT,
   `itilcategories_id` int unsigned NULL,
   `is_active` tinyint NOT NULL DEFAULT 1,
   `last_assignment_index` int NULL DEFAULT NULL,
   PRIMARY KEY (`id`),
   UNIQUE KEY `ix_itilcategories_uq` (`itilcategories_id`),
   KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE IF NOT EXISTS `glpi_plugin_atribuicaointeligente_unavailabilities` (
   `id` int unsigned NOT NULL AUTO_INCREMENT,
   `users_id` int unsigned NOT NULL DEFAULT 0,
   `entities_id` int unsigned NOT NULL DEFAULT 0,
   `type` varchar(32) NOT NULL,
   `date_start` datetime NULL DEFAULT NULL,
   `date_end` datetime NULL DEFAULT NULL,
   `weekday` tinyint NULL DEFAULT NULL,
   `comment` text NULL,
   `is_active` tinyint NOT NULL DEFAULT 1,
   `date_creation` timestamp NULL DEFAULT NULL,
   `date_mod` timestamp NULL DEFAULT NULL,
   PRIMARY KEY (`id`),
   KEY `idx_user` (`users_id`),
   KEY `idx_entity` (`entities_id`),
   KEY `idx_active` (`is_active`),
   KEY `idx_period` (`date_start`, `date_end`),
   KEY `idx_weekday` (`weekday`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE IF NOT EXISTS `glpi_plugin_atribuicaointeligente_decision_logs` (
   `id` int unsigned NOT NULL AUTO_INCREMENT,
   `tickets_id` int unsigned NULL DEFAULT NULL,
   `groups_id` int unsigned NULL DEFAULT NULL,
   `itilcategories_id` int unsigned NULL DEFAULT NULL,
   `entities_id` int unsigned NULL DEFAULT NULL,
   `mode` varchar(32) NULL DEFAULT NULL,
   `selected_users_id` int unsigned NULL DEFAULT NULL,
   `ignored_users` longtext NULL,
   `reason` text NULL,
   `date_creation` timestamp NULL DEFAULT NULL,
   PRIMARY KEY (`id`),
   KEY `idx_ticket` (`tickets_id`),
   KEY `idx_group` (`groups_id`),
   KEY `idx_selected_user` (`selected_users_id`),
   KEY `idx_date_creation` (`date_creation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
