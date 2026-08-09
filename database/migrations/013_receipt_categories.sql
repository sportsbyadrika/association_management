-- 013_receipt_categories.sql
-- Give receipts a category (general / project / gift / event) with matching
-- link columns, mirroring expenditures. The existing project_id becomes the
-- "project" category's link. No FK constraints (see 007-012).

ALTER TABLE receipts
    ADD COLUMN category ENUM('general','project','gift','event') NOT NULL DEFAULT 'general' AFTER income_head_id,
    ADD COLUMN gift_id BIGINT UNSIGNED NULL AFTER project_id,
    ADD COLUMN event_id BIGINT UNSIGNED NULL AFTER gift_id;

ALTER TABLE receipts ADD KEY idx_rcpt_gift (gift_id);
ALTER TABLE receipts ADD KEY idx_rcpt_event (event_id);

-- Backfill: receipts already tied to a project are the "project" category.
UPDATE receipts SET category = 'project' WHERE project_id IS NOT NULL;
