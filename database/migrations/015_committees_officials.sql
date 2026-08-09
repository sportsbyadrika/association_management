-- 015_committees_officials.sql
-- Committees (with a period + active status), an Official Designation master,
-- and committee officials. Officials may optionally have a login (user_id).
-- No foreign-key constraints (see 007-014); integrity enforced in the app.

CREATE TABLE IF NOT EXISTS official_designations (
    id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    association_id BIGINT UNSIGNED NOT NULL,
    name           VARCHAR(120) NOT NULL,
    description    VARCHAR(255) NULL,
    is_active      TINYINT(1) NOT NULL DEFAULT 1,
    created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_od_association (association_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS committees (
    id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    association_id BIGINT UNSIGNED NOT NULL,
    name           VARCHAR(180) NOT NULL,
    start_date     DATE NULL,
    end_date       DATE NULL,
    is_active      TINYINT(1) NOT NULL DEFAULT 1,
    description    VARCHAR(1000) NULL,
    created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_committee_association (association_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS committee_officials (
    id                      BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    association_id          BIGINT UNSIGNED NOT NULL,
    committee_id            BIGINT UNSIGNED NOT NULL,
    official_designation_id BIGINT UNSIGNED NULL,
    member_id               BIGINT UNSIGNED NULL,
    user_id                 BIGINT UNSIGNED NULL,
    name                    VARCHAR(180) NOT NULL,
    phone                   VARCHAR(20) NULL,
    email                   VARCHAR(190) NULL,
    address                 VARCHAR(500) NULL,
    photo_path              VARCHAR(255) NULL,
    sort_order              INT NOT NULL DEFAULT 0,
    created_at              TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at              TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_co_association (association_id),
    KEY idx_co_committee (committee_id),
    KEY idx_co_designation (official_designation_id),
    KEY idx_co_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed default official designations for existing associations.
INSERT INTO official_designations (association_id, name, is_active)
SELECT a.id, t.name, 1
FROM associations a
CROSS JOIN (
    SELECT 'President' AS name UNION ALL SELECT 'Vice President' UNION ALL
    SELECT 'Secretary' UNION ALL SELECT 'Joint Secretary' UNION ALL
    SELECT 'Treasurer' UNION ALL SELECT 'Committee Member'
) t
WHERE NOT EXISTS (
    SELECT 1 FROM official_designations od
    WHERE od.association_id = a.id AND od.name = t.name
);
