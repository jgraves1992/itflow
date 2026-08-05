-- Migration: link contracts and contract templates to native SLA plans
-- Run each statement individually if your client does not support multi-statement batches.
-- All operations use IF EXISTS / IF NOT EXISTS so the file is safe to re-run.

-- contracts: add new FK column
ALTER TABLE contracts
    ADD COLUMN IF NOT EXISTS contract_sla_id INT(11) DEFAULT NULL;

-- contracts: drop old custom SLA columns
ALTER TABLE contracts DROP COLUMN IF EXISTS contract_sla_low_response_time;
ALTER TABLE contracts DROP COLUMN IF EXISTS contract_sla_low_resolution_time;
ALTER TABLE contracts DROP COLUMN IF EXISTS contract_sla_medium_response_time;
ALTER TABLE contracts DROP COLUMN IF EXISTS contract_sla_medium_resolution_time;
ALTER TABLE contracts DROP COLUMN IF EXISTS contract_sla_high_response_time;
ALTER TABLE contracts DROP COLUMN IF EXISTS contract_sla_high_resolution_time;

-- contract_templates: add new FK column
ALTER TABLE contract_templates
    ADD COLUMN IF NOT EXISTS contract_template_sla_id INT(11) DEFAULT NULL;

-- contract_templates: drop old custom SLA columns
ALTER TABLE contract_templates DROP COLUMN IF EXISTS contract_template_sla_low_response_time;
ALTER TABLE contract_templates DROP COLUMN IF EXISTS contract_template_sla_low_resolution_time;
ALTER TABLE contract_templates DROP COLUMN IF EXISTS contract_template_sla_medium_response_time;
ALTER TABLE contract_templates DROP COLUMN IF EXISTS contract_template_sla_medium_resolution_time;
ALTER TABLE contract_templates DROP COLUMN IF EXISTS contract_template_sla_high_response_time;
ALTER TABLE contract_templates DROP COLUMN IF EXISTS contract_template_sla_high_resolution_time;
