-- 009_blood_group_and_additional_memberships.sql
-- Adds a blood group to members, an "Additional Membership" master, and a
-- member <-> additional membership link table (many-to-many).
--
-- No foreign-key constraints (see 007/008) to stay compatible with the target
-- schema; integrity is enforced in the application layer. Indexes are kept.

ALTER TABLE members
    ADD COLUMN blood_group VARCHAR(5) NULL AFTER gender;

CREATE TABLE IF NOT EXISTS additional_memberships (
    id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    association_id BIGINT UNSIGNED NOT NULL,
    name           VARCHAR(120) NOT NULL,
    description    VARCHAR(255) NULL,
    is_active      TINYINT(1) NOT NULL DEFAULT 1,
    created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_am_association (association_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS member_additional_memberships (
    id                       BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    association_id           BIGINT UNSIGNED NOT NULL,
    member_id                BIGINT UNSIGNED NOT NULL,
    additional_membership_id BIGINT UNSIGNED NOT NULL,
    created_at               TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_mam_member_membership (member_id, additional_membership_id),
    KEY idx_mam_member (member_id),
    KEY idx_mam_membership (additional_membership_id),
    KEY idx_mam_association (association_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed a few default additional-membership categories per association.
INSERT INTO additional_memberships (association_id, name, is_active)
SELECT a.id, t.name, 1
FROM associations a
CROSS JOIN (
    SELECT 'Executive Committee' AS name UNION ALL SELECT 'Sports Wing' UNION ALL
    SELECT 'Cultural Wing' UNION ALL SELECT 'Youth Wing' UNION ALL SELECT 'Women Wing'
) t
WHERE NOT EXISTS (
    SELECT 1 FROM additional_memberships am
    WHERE am.association_id = a.id AND am.name = t.name
);
