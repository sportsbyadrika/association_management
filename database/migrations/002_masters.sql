-- ---------------------------------------------------------------------------
-- 002_masters.sql — association-scoped lookup / reference tables
-- ---------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS member_types (
    id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    association_id BIGINT UNSIGNED NOT NULL,
    name           VARCHAR(120) NOT NULL,
    description    VARCHAR(255) NULL,
    is_active      TINYINT(1) NOT NULL DEFAULT 1,
    created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_mt_association (association_id),
    CONSTRAINT fk_mt_association FOREIGN KEY (association_id)
        REFERENCES associations (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS income_heads (
    id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    association_id BIGINT UNSIGNED NOT NULL,
    name           VARCHAR(120) NOT NULL,
    description    VARCHAR(255) NULL,
    is_active      TINYINT(1) NOT NULL DEFAULT 1,
    created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_ih_association (association_id),
    CONSTRAINT fk_ih_association FOREIGN KEY (association_id)
        REFERENCES associations (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS expenditure_heads (
    id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    association_id BIGINT UNSIGNED NOT NULL,
    name           VARCHAR(120) NOT NULL,
    description    VARCHAR(255) NULL,
    is_active      TINYINT(1) NOT NULL DEFAULT 1,
    created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_eh_association (association_id),
    CONSTRAINT fk_eh_association FOREIGN KEY (association_id)
        REFERENCES associations (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS project_types (
    id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    association_id BIGINT UNSIGNED NOT NULL,
    name           VARCHAR(120) NOT NULL,
    description    VARCHAR(255) NULL,
    is_active      TINYINT(1) NOT NULL DEFAULT 1,
    created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_pt_association (association_id),
    CONSTRAINT fk_pt_association FOREIGN KEY (association_id)
        REFERENCES associations (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS bank_accounts (
    id                     BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    association_id         BIGINT UNSIGNED NOT NULL,
    account_name           VARCHAR(160) NOT NULL,
    type                   ENUM('association','treasurer') NOT NULL DEFAULT 'association',
    account_number_masked  VARCHAR(40) NULL,
    opening_balance        DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    description            VARCHAR(255) NULL,
    is_active              TINYINT(1) NOT NULL DEFAULT 1,
    created_at             TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at             TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_ba_association (association_id),
    CONSTRAINT fk_ba_association FOREIGN KEY (association_id)
        REFERENCES associations (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
