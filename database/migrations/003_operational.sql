-- ---------------------------------------------------------------------------
-- 003_operational.sql — members, projects, demands, receipts, expenditures
-- ON DELETE rules RESTRICT against financial history; masters/members use
-- soft-delete (is_active) instead of destructive deletes.
-- ---------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS members (
    id                    BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    association_id        BIGINT UNSIGNED NOT NULL,
    member_type_id        BIGINT UNSIGNED NULL,
    name                  VARCHAR(180) NOT NULL,
    age                   TINYINT UNSIGNED NULL,
    gender                ENUM('male','female','other') NULL,
    address               VARCHAR(500) NULL,
    mobile                VARCHAR(20) NULL,
    whatsapp              VARCHAR(20) NULL,
    email                 VARCHAR(190) NULL,
    family_members_count  SMALLINT UNSIGNED NULL,
    occupation            VARCHAR(160) NULL,
    joined_on             DATE NULL,
    photo_path            VARCHAR(255) NULL,
    notes                 VARCHAR(1000) NULL,
    is_active             TINYINT(1) NOT NULL DEFAULT 1,
    created_at            TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at            TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_members_association (association_id),
    KEY idx_members_type (member_type_id),
    CONSTRAINT fk_members_association FOREIGN KEY (association_id)
        REFERENCES associations (id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_members_type FOREIGN KEY (member_type_id)
        REFERENCES member_types (id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Link a member self-service user back to its member record.
ALTER TABLE users
    ADD CONSTRAINT fk_users_member FOREIGN KEY (member_id)
        REFERENCES members (id) ON DELETE SET NULL ON UPDATE CASCADE;

CREATE TABLE IF NOT EXISTS projects (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    association_id  BIGINT UNSIGNED NOT NULL,
    project_type_id BIGINT UNSIGNED NULL,
    name            VARCHAR(180) NOT NULL,
    description     TEXT NULL,
    status          ENUM('planned','active','completed','on_hold','cancelled') NOT NULL DEFAULT 'active',
    target_amount   DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    start_date      DATE NULL,
    end_date        DATE NULL,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_projects_association (association_id),
    KEY idx_projects_type (project_type_id),
    CONSTRAINT fk_projects_association FOREIGN KEY (association_id)
        REFERENCES associations (id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_projects_type FOREIGN KEY (project_type_id)
        REFERENCES project_types (id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS project_milestones (
    id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    project_id   BIGINT UNSIGNED NOT NULL,
    title        VARCHAR(180) NOT NULL,
    description  TEXT NULL,
    photo_path   VARCHAR(255) NULL,
    achieved_on  DATE NULL,
    created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_pm_project (project_id),
    CONSTRAINT fk_pm_project FOREIGN KEY (project_id)
        REFERENCES projects (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS demands (
    id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    association_id BIGINT UNSIGNED NOT NULL,
    member_id      BIGINT UNSIGNED NOT NULL,
    purpose        ENUM('subscription','project','other') NOT NULL DEFAULT 'subscription',
    project_id     BIGINT UNSIGNED NULL,
    amount         DECIMAL(12,2) NOT NULL,
    due_date       DATE NULL,
    status         ENUM('pending','partial','paid','cancelled') NOT NULL DEFAULT 'pending',
    remarks        VARCHAR(500) NULL,
    created_by     BIGINT UNSIGNED NULL,
    created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_demands_association (association_id),
    KEY idx_demands_member (member_id),
    KEY idx_demands_project (project_id),
    CONSTRAINT fk_demands_association FOREIGN KEY (association_id)
        REFERENCES associations (id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_demands_member FOREIGN KEY (member_id)
        REFERENCES members (id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_demands_project FOREIGN KEY (project_id)
        REFERENCES projects (id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS receipts (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    association_id  BIGINT UNSIGNED NOT NULL,
    member_id       BIGINT UNSIGNED NULL,
    income_head_id  BIGINT UNSIGNED NULL,
    project_id      BIGINT UNSIGNED NULL,
    demand_id       BIGINT UNSIGNED NULL,
    amount          DECIMAL(12,2) NOT NULL,
    mode            ENUM('cash','fund_transfer') NOT NULL DEFAULT 'cash',
    bank_account_id BIGINT UNSIGNED NULL,
    received_on     DATE NOT NULL,
    remarks         VARCHAR(500) NULL,
    created_by      BIGINT UNSIGNED NULL,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_receipts_association (association_id),
    KEY idx_receipts_member (member_id),
    KEY idx_receipts_income_head (income_head_id),
    KEY idx_receipts_project (project_id),
    KEY idx_receipts_bank (bank_account_id),
    KEY idx_receipts_date (received_on),
    CONSTRAINT fk_receipts_association FOREIGN KEY (association_id)
        REFERENCES associations (id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_receipts_member FOREIGN KEY (member_id)
        REFERENCES members (id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_receipts_income_head FOREIGN KEY (income_head_id)
        REFERENCES income_heads (id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_receipts_project FOREIGN KEY (project_id)
        REFERENCES projects (id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_receipts_demand FOREIGN KEY (demand_id)
        REFERENCES demands (id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_receipts_bank FOREIGN KEY (bank_account_id)
        REFERENCES bank_accounts (id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS expenditures (
    id                   BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    association_id       BIGINT UNSIGNED NOT NULL,
    expenditure_head_id  BIGINT UNSIGNED NULL,
    project_id           BIGINT UNSIGNED NULL,
    category             ENUM('project','association') NOT NULL DEFAULT 'association',
    amount               DECIMAL(12,2) NOT NULL,
    paid_on              DATE NOT NULL,
    bank_account_id      BIGINT UNSIGNED NULL,
    mode                 ENUM('cash','fund_transfer') NOT NULL DEFAULT 'cash',
    remarks              VARCHAR(500) NULL,
    created_by           BIGINT UNSIGNED NULL,
    created_at           TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at           TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_exp_association (association_id),
    KEY idx_exp_head (expenditure_head_id),
    KEY idx_exp_project (project_id),
    KEY idx_exp_bank (bank_account_id),
    KEY idx_exp_date (paid_on),
    CONSTRAINT fk_exp_association FOREIGN KEY (association_id)
        REFERENCES associations (id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_exp_head FOREIGN KEY (expenditure_head_id)
        REFERENCES expenditure_heads (id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_exp_project FOREIGN KEY (project_id)
        REFERENCES projects (id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_exp_bank FOREIGN KEY (bank_account_id)
        REFERENCES bank_accounts (id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
