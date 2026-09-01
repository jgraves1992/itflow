<?php

/*
 * ITFlow - Database update to version 2.7.9 (from 2.7.8)
 * Included by admin/database_updates.php - do not access directly
 */

defined('FROM_DB_UPDATER') || die("Direct file access is not allowed");

    // ── settings table ──────────────────────────────────────────────────────
    // Production DBs running < 2.7.1 may be missing columns that intermediate
    // migrations added. load_global_settings.php SELECT-lists them all, so a
    // single missing column is a fatal SQL error on every page load.
    // ADD COLUMN IF NOT EXISTS is idempotent — columns that already exist are
    // skipped silently, so this block is safe to run on any version.

    // Mail / OAuth columns (added in early 2.x migrations)
    mysqli_query($mysqli, "ALTER TABLE `settings`
        ADD COLUMN IF NOT EXISTS `config_smtp_provider` varchar(200) DEFAULT NULL,
        ADD COLUMN IF NOT EXISTS `config_imap_provider` varchar(200) DEFAULT NULL,
        ADD COLUMN IF NOT EXISTS `config_mail_oauth_client_id` varchar(255) DEFAULT NULL,
        ADD COLUMN IF NOT EXISTS `config_mail_oauth_client_secret` varchar(255) DEFAULT NULL,
        ADD COLUMN IF NOT EXISTS `config_mail_oauth_tenant_id` varchar(255) DEFAULT NULL,
        ADD COLUMN IF NOT EXISTS `config_mail_oauth_refresh_token` text DEFAULT NULL,
        ADD COLUMN IF NOT EXISTS `config_mail_oauth_access_token` text DEFAULT NULL,
        ADD COLUMN IF NOT EXISTS `config_mail_oauth_access_token_expires_at` datetime DEFAULT NULL");

    // Invoice / Quote / Project numbering columns
    mysqli_query($mysqli, "ALTER TABLE `settings`
        ADD COLUMN IF NOT EXISTS `config_invoice_show_tax_id` tinyint(1) NOT NULL DEFAULT 0,
        ADD COLUMN IF NOT EXISTS `config_recurring_invoice_prefix` varchar(200) DEFAULT NULL,
        ADD COLUMN IF NOT EXISTS `config_recurring_invoice_next_number` int(11) NOT NULL DEFAULT 1,
        ADD COLUMN IF NOT EXISTS `config_quote_notification_email` varchar(200) DEFAULT NULL,
        ADD COLUMN IF NOT EXISTS `config_project_prefix` varchar(200) NOT NULL DEFAULT 'PRJ-',
        ADD COLUMN IF NOT EXISTS `config_project_next_number` int(11) NOT NULL DEFAULT 1");

    // Ticket behaviour columns
    mysqli_query($mysqli, "ALTER TABLE `settings`
        ADD COLUMN IF NOT EXISTS `config_ticket_timer_autostart` tinyint(1) NOT NULL DEFAULT 0,
        ADD COLUMN IF NOT EXISTS `config_ticket_email_parse_unknown_senders` int(1) NOT NULL DEFAULT 0,
        ADD COLUMN IF NOT EXISTS `config_ticket_ordering` tinyint(1) NOT NULL DEFAULT 0,
        ADD COLUMN IF NOT EXISTS `config_ticket_moving_columns` tinyint(1) NOT NULL DEFAULT 1");

    // Auth / security / portal columns
    mysqli_query($mysqli, "ALTER TABLE `settings`
        ADD COLUMN IF NOT EXISTS `config_login_remember_me_expire` int(11) NOT NULL DEFAULT 3,
        ADD COLUMN IF NOT EXISTS `config_azure_client_id` varchar(200) DEFAULT NULL,
        ADD COLUMN IF NOT EXISTS `config_azure_client_secret` varchar(200) DEFAULT NULL,
        ADD COLUMN IF NOT EXISTS `config_client_portal_enable` tinyint(1) NOT NULL DEFAULT 1,
        ADD COLUMN IF NOT EXISTS `config_whitelabel_enabled` int(11) NOT NULL DEFAULT 0,
        ADD COLUMN IF NOT EXISTS `config_whitelabel_key` text DEFAULT NULL");

    // Misc operational columns
    mysqli_query($mysqli, "ALTER TABLE `settings`
        ADD COLUMN IF NOT EXISTS `config_log_retention` int(11) NOT NULL DEFAULT 90,
        ADD COLUMN IF NOT EXISTS `config_telemetry` tinyint(1) DEFAULT 0,
        ADD COLUMN IF NOT EXISTS `config_destructive_deletes_enable` tinyint(1) NOT NULL DEFAULT 0,
        ADD COLUMN IF NOT EXISTS `config_theme_dark` tinyint(1) NOT NULL DEFAULT 0");

    // Business hours / SLA columns
    mysqli_query($mysqli, "ALTER TABLE `settings`
        ADD COLUMN IF NOT EXISTS `config_business_days` varchar(20) NOT NULL DEFAULT '1,2,3,4,5',
        ADD COLUMN IF NOT EXISTS `config_business_hours_start` time NOT NULL DEFAULT '09:00:00',
        ADD COLUMN IF NOT EXISTS `config_business_hours_end` time NOT NULL DEFAULT '17:00:00',
        ADD COLUMN IF NOT EXISTS `config_sla_warning_percent` tinyint(3) NOT NULL DEFAULT 75,
        ADD COLUMN IF NOT EXISTS `config_sla_notification_email` varchar(200) DEFAULT NULL");

    // Cron / backup / update columns
    mysqli_query($mysqli, "ALTER TABLE `settings`
        ADD COLUMN IF NOT EXISTS `config_cron_last_dispatch_at` datetime DEFAULT NULL,
        ADD COLUMN IF NOT EXISTS `config_backup_retention_days` int(11) NOT NULL DEFAULT 30,
        ADD COLUMN IF NOT EXISTS `config_backup_retention_count` int(11) NOT NULL DEFAULT 5,
        ADD COLUMN IF NOT EXISTS `config_backup_cron_type` varchar(20) NOT NULL DEFAULT 'full',
        ADD COLUMN IF NOT EXISTS `config_update_queued_at` datetime DEFAULT NULL,
        ADD COLUMN IF NOT EXISTS `config_update_latest_commit` varchar(40) DEFAULT NULL,
        ADD COLUMN IF NOT EXISTS `config_update_pending_commits` text DEFAULT NULL,
        ADD COLUMN IF NOT EXISTS `config_update_checked_at` datetime DEFAULT NULL");

    // Internal client link — the confirmed-missing column that caused the fatal crash
    mysqli_query($mysqli, "ALTER TABLE `settings`
        ADD COLUMN IF NOT EXISTS `config_internal_client_id` int(11) NOT NULL DEFAULT 0");

    // ── user_settings table ─────────────────────────────────────────────────
    // Dashboard section toggles added in v26.09 — add as DEFAULT 1 so existing
    // users see a full dashboard immediately after upgrading (opt-out model).
    mysqli_query($mysqli, "ALTER TABLE `user_settings`
        ADD COLUMN IF NOT EXISTS `user_config_dashboard_financial_enable` tinyint(1) NOT NULL DEFAULT 1,
        ADD COLUMN IF NOT EXISTS `user_config_dashboard_technical_enable` tinyint(1) NOT NULL DEFAULT 1");

    // Backfill rows that existed before the ALTER; MariaDB DEFAULT on ADD COLUMN
    // applies to new inserts only — existing rows get 0 on some engine versions.
    mysqli_query($mysqli, "UPDATE `user_settings` SET
        `user_config_dashboard_financial_enable` = 1,
        `user_config_dashboard_technical_enable` = 1
        WHERE `user_config_dashboard_financial_enable` = 0
          AND `user_config_dashboard_technical_enable` = 0");
