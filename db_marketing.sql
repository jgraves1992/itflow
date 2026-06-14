-- ITFlow Marketing Module - Database Schema
-- Run this against your ITFlow database before using the module.
-- Follows ITFlow naming conventions: all columns prefixed with table name.

CREATE TABLE IF NOT EXISTS `marketing_leads` (
  `lead_id` int(11) NOT NULL AUTO_INCREMENT,
  `lead_name` varchar(200) NOT NULL,
  `lead_email` varchar(200) NOT NULL,
  `lead_company` varchar(200) DEFAULT NULL,
  `lead_phone` varchar(50) DEFAULT NULL,
  `lead_source` varchar(100) DEFAULT NULL,
  `lead_status` varchar(50) NOT NULL DEFAULT 'new',
  `lead_notes` text DEFAULT NULL,
  `lead_unsubscribed` tinyint(1) NOT NULL DEFAULT 0,
  `lead_unsubscribed_at` datetime DEFAULT NULL,
  `lead_unsubscribe_token` varchar(64) NOT NULL DEFAULT '',
  `lead_created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `lead_updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `lead_archived_at` datetime DEFAULT NULL,
  `lead_client_id` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`lead_id`),
  UNIQUE KEY `lead_unsubscribe_token` (`lead_unsubscribe_token`),
  KEY `lead_email` (`lead_email`),
  KEY `lead_status` (`lead_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `marketing_sequences` (
  `sequence_id` int(11) NOT NULL AUTO_INCREMENT,
  `sequence_name` varchar(200) NOT NULL,
  `sequence_description` text DEFAULT NULL,
  `sequence_from_name` varchar(200) DEFAULT NULL,
  `sequence_from_email` varchar(200) DEFAULT NULL,
  `sequence_active` tinyint(1) NOT NULL DEFAULT 1,
  `sequence_created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `sequence_updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `sequence_archived_at` datetime DEFAULT NULL,
  PRIMARY KEY (`sequence_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- step_delay_days is cumulative from enrollment date (not from previous step)
-- e.g. step 1 = day 0, step 2 = day 3, step 3 = day 7
CREATE TABLE IF NOT EXISTS `marketing_sequence_steps` (
  `step_id` int(11) NOT NULL AUTO_INCREMENT,
  `step_sequence_id` int(11) NOT NULL,
  `step_order` int(11) NOT NULL DEFAULT 1,
  `step_delay_days` int(11) NOT NULL DEFAULT 0,
  `step_subject` varchar(500) NOT NULL,
  `step_body` longtext NOT NULL,
  `step_created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `step_updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`step_id`),
  KEY `step_sequence_id` (`step_sequence_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- enrollment_status: active, completed, paused, unsubscribed
CREATE TABLE IF NOT EXISTS `marketing_enrollments` (
  `enrollment_id` int(11) NOT NULL AUTO_INCREMENT,
  `enrollment_lead_id` int(11) NOT NULL,
  `enrollment_sequence_id` int(11) NOT NULL,
  `enrollment_status` varchar(50) NOT NULL DEFAULT 'active',
  `enrollment_next_step_id` int(11) NOT NULL DEFAULT 0,
  `enrollment_next_send_at` datetime DEFAULT NULL,
  `enrollment_enrolled_at` datetime NOT NULL DEFAULT current_timestamp(),
  `enrollment_completed_at` datetime DEFAULT NULL,
  PRIMARY KEY (`enrollment_id`),
  UNIQUE KEY `unique_enrollment` (`enrollment_lead_id`, `enrollment_sequence_id`),
  KEY `enrollment_lead_id` (`enrollment_lead_id`),
  KEY `enrollment_next_send_at` (`enrollment_next_send_at`),
  KEY `enrollment_status` (`enrollment_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `marketing_email_log` (
  `log_id` int(11) NOT NULL AUTO_INCREMENT,
  `log_lead_id` int(11) NOT NULL,
  `log_enrollment_id` int(11) NOT NULL,
  `log_sequence_id` int(11) NOT NULL,
  `log_step_id` int(11) NOT NULL,
  `log_recipient_email` varchar(255) NOT NULL,
  `log_subject` varchar(500) NOT NULL,
  `log_sent_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`log_id`),
  KEY `log_lead_id` (`log_lead_id`),
  KEY `log_enrollment_id` (`log_enrollment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
