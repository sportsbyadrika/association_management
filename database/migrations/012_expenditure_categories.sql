-- 012_expenditure_categories.sql
-- Allow expenditures to be categorised against a gift or an event (in addition
-- to project / association). No FK constraints (see 007-011).

ALTER TABLE expenditures
    MODIFY COLUMN category ENUM('project','association','gift','event') NOT NULL DEFAULT 'association';

ALTER TABLE expenditures
    ADD COLUMN gift_id BIGINT UNSIGNED NULL AFTER project_id,
    ADD COLUMN event_id BIGINT UNSIGNED NULL AFTER gift_id;

ALTER TABLE expenditures ADD KEY idx_exp_gift (gift_id);
ALTER TABLE expenditures ADD KEY idx_exp_event (event_id);
