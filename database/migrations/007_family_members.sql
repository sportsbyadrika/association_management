-- 007_family_members.sql
-- Family member type master + family member sub-records (attached to a member).
-- Column types mirror the existing migrations exactly so foreign keys match.

CREATE TABLE IF NOT EXISTS family_member_types (
    id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    association_id BIGINT UNSIGNED NOT NULL,
    name           VARCHAR(120) NOT NULL,
    description    VARCHAR(255) NULL,
    is_active      TINYINT(1) NOT NULL DEFAULT 1,
    created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_fmt_association (association_id),
    CONSTRAINT fk_fmt_association FOREIGN KEY (association_id)
        REFERENCES associations (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS family_members (
    id                    BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    association_id        BIGINT UNSIGNED NOT NULL,
    member_id             BIGINT UNSIGNED NOT NULL,
    family_member_type_id BIGINT UNSIGNED NULL,
    name                  VARCHAR(180) NOT NULL,
    age                   TINYINT UNSIGNED NULL,
    gender                ENUM('male','female','other') NULL,
    mobile                VARCHAR(20) NULL,
    whatsapp              VARCHAR(20) NULL,
    email                 VARCHAR(190) NULL,
    occupation            VARCHAR(160) NULL,
    relation              VARCHAR(120) NULL,
    photo_path            VARCHAR(255) NULL,
    notes                 VARCHAR(1000) NULL,
    is_active             TINYINT(1) NOT NULL DEFAULT 1,
    created_at            TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at            TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_fm_association (association_id),
    KEY idx_fm_member (member_id),
    KEY idx_fm_type (family_member_type_id),
    CONSTRAINT fk_fm_association FOREIGN KEY (association_id)
        REFERENCES associations (id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_fm_member FOREIGN KEY (member_id)
        REFERENCES members (id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_fm_type FOREIGN KEY (family_member_type_id)
        REFERENCES family_member_types (id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed default family member types for existing associations.
INSERT INTO family_member_types (association_id, name, is_active)
SELECT a.id, t.name, 1
FROM associations a
CROSS JOIN (
    SELECT 'Spouse' AS name UNION ALL SELECT 'Child' UNION ALL
    SELECT 'Parent' UNION ALL SELECT 'Sibling' UNION ALL SELECT 'Other'
) t
WHERE NOT EXISTS (
    SELECT 1 FROM family_member_types fmt
    WHERE fmt.association_id = a.id AND fmt.name = t.name
);
