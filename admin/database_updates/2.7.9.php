<?php

/*
 * ITFlow - Database update to version 2.7.9 (from 2.7.8)
 * Included by admin/database_updates.php - do not access directly
 */

defined('FROM_DB_UPDATER') || die("Direct file access is not allowed");

    // Dashboard section toggles added in v26.09 — let each user choose which
    // panels the dashboard shows (Financial / Technical). We add as DEFAULT 1
    // so existing users see a full dashboard immediately after upgrading
    // (opt-out model), matching the pre-v26.09 behaviour where all sections
    // were always shown.
    mysqli_query($mysqli, "ALTER TABLE `user_settings`
        ADD COLUMN IF NOT EXISTS `user_config_dashboard_financial_enable` tinyint(1) NOT NULL DEFAULT 1,
        ADD COLUMN IF NOT EXISTS `user_config_dashboard_technical_enable` tinyint(1) NOT NULL DEFAULT 1");

    // Backfill any rows that already existed before the ALTER (ALTER … DEFAULT
    // only governs new inserts; existing rows get the column's MariaDB default,
    // which may still be 0 on some engines when the ADD runs without a value).
    mysqli_query($mysqli, "UPDATE `user_settings` SET
        `user_config_dashboard_financial_enable` = 1,
        `user_config_dashboard_technical_enable` = 1
        WHERE `user_config_dashboard_financial_enable` = 0
          AND `user_config_dashboard_technical_enable` = 0");
