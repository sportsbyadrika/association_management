-- ---------------------------------------------------------------------------
-- 004_member_number_and_financial_years.sql
--   * adds members.member_number (unique per association)
--   * adds the financial_years master table
-- ---------------------------------------------------------------------------

ALTER TABLE members
    ADD COLUMN member_number VARCHAR(50) NULL AFTER association_id;

-- Unique per association. MySQL allows multiple NULLs, so existing rows
-- without a number are unaffected until they are edited.
ALTER TABLE members
    ADD UNIQUE KEY uniq_members_number (association_id, member_number);

CREATE TABLE IF NOT EXISTS financial_years (
    id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    association_id BIGINT UNSIGNED NOT NULL,
    label          VARCHAR(60) NOT NULL,
    start_date     DATE NOT NULL,
    end_date       DATE NOT NULL,
    is_active      TINYINT(1) NOT NULL DEFAULT 1,
    created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_fy_association (association_id),
    KEY idx_fy_range (association_id, start_date, end_date),
    CONSTRAINT fk_fy_association FOREIGN KEY (association_id)
        REFERENCES associations (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
