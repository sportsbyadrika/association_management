-- 011_events.sql
-- Event type master + events tracker (similar to gifts/projects).
-- No foreign-key constraints (see 007-010); integrity enforced in the app.

CREATE TABLE IF NOT EXISTS event_types (
    id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    association_id BIGINT UNSIGNED NOT NULL,
    name           VARCHAR(120) NOT NULL,
    description    VARCHAR(255) NULL,
    is_active      TINYINT(1) NOT NULL DEFAULT 1,
    created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_et_association (association_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS events (
    id                 BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    association_id     BIGINT UNSIGNED NOT NULL,
    event_type_id      BIGINT UNSIGNED NULL,
    title              VARCHAR(180) NOT NULL,
    venue              VARCHAR(255) NULL,
    location           VARCHAR(255) NULL,
    start_date         DATE NULL,
    end_date           DATE NULL,
    registration_start DATE NULL,
    registration_end   DATE NULL,
    status             ENUM('planned','completed','cancelled') NOT NULL DEFAULT 'planned',
    value              DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    description        VARCHAR(1000) NULL,
    created_by         BIGINT UNSIGNED NULL,
    created_at         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_event_association (association_id),
    KEY idx_event_type (event_type_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed default event types for existing associations.
INSERT INTO event_types (association_id, name, is_active)
SELECT a.id, t.name, 1
FROM associations a
CROSS JOIN (
    SELECT 'Annual Day' AS name UNION ALL SELECT 'Festival' UNION ALL
    SELECT 'Meeting' UNION ALL SELECT 'Sports' UNION ALL SELECT 'Other'
) t
WHERE NOT EXISTS (
    SELECT 1 FROM event_types et
    WHERE et.association_id = a.id AND et.name = t.name
);
