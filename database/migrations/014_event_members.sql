-- 014_event_members.sql
-- Per-member contributions for an event (mirrors gift_members). A default
-- per-person amount on the event pre-fills new rows to reduce typing.
-- No foreign-key constraints (see 007-013); integrity enforced in the app.

ALTER TABLE events
    ADD COLUMN default_contribution DECIMAL(12,2) NULL AFTER value;

CREATE TABLE IF NOT EXISTS event_members (
    id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    association_id BIGINT UNSIGNED NOT NULL,
    event_id       BIGINT UNSIGNED NOT NULL,
    member_id      BIGINT UNSIGNED NOT NULL,
    contribution   DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_em_event_member (event_id, member_id),
    KEY idx_em_event (event_id),
    KEY idx_em_member (member_id),
    KEY idx_em_association (association_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
