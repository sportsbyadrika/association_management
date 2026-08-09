-- 010_gift_members.sql
-- Per-member contributions for a gift (both directions). A gift can list many
-- members, each with their own contribution amount. A default per-person
-- amount on the gift pre-fills new rows to reduce typing.
--
-- No foreign-key constraints (see 007-009); integrity enforced in the app.

ALTER TABLE gifts
    ADD COLUMN default_contribution DECIMAL(12,2) NULL AFTER value;

CREATE TABLE IF NOT EXISTS gift_members (
    id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    association_id BIGINT UNSIGNED NOT NULL,
    gift_id        BIGINT UNSIGNED NOT NULL,
    member_id      BIGINT UNSIGNED NOT NULL,
    contribution   DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_gm_gift_member (gift_id, member_id),
    KEY idx_gm_gift (gift_id),
    KEY idx_gm_member (member_id),
    KEY idx_gm_association (association_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
