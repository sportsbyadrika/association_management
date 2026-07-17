-- ---------------------------------------------------------------------------
-- 006_demand_purposes.sql
--   * new association-scoped "Demand Purpose" master with a mandatory/optional
--     type (drives the dashboard outstanding split)
--   * demands reference a demand_purpose_id; the project link is now
--     independent of purpose
--   * migrate the old demands.purpose enum, then drop it
-- ---------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS demand_purposes (
    id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    association_id BIGINT UNSIGNED NOT NULL,
    name           VARCHAR(120) NOT NULL,
    type           ENUM('mandatory','optional') NOT NULL DEFAULT 'optional',
    is_active      TINYINT(1) NOT NULL DEFAULT 1,
    created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_dp_association (association_id),
    CONSTRAINT fk_dp_association FOREIGN KEY (association_id)
        REFERENCES associations (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed default purposes for every existing association.
INSERT INTO demand_purposes (association_id, name, type, is_active)
    SELECT id, 'Subscription', 'mandatory', 1 FROM associations;
INSERT INTO demand_purposes (association_id, name, type, is_active)
    SELECT id, 'Project Contribution', 'optional', 1 FROM associations;
INSERT INTO demand_purposes (association_id, name, type, is_active)
    SELECT id, 'Donation', 'optional', 1 FROM associations;
INSERT INTO demand_purposes (association_id, name, type, is_active)
    SELECT id, 'Other', 'optional', 1 FROM associations;

-- Add the reference column.
ALTER TABLE demands
    ADD COLUMN demand_purpose_id BIGINT UNSIGNED NULL AFTER member_id;

-- Migrate existing enum values to the seeded purposes.
UPDATE demands d
JOIN demand_purposes dp
  ON dp.association_id = d.association_id
 AND dp.name = CASE d.purpose
        WHEN 'subscription' THEN 'Subscription'
        WHEN 'project'      THEN 'Project Contribution'
        ELSE 'Other'
     END
SET d.demand_purpose_id = dp.id;

ALTER TABLE demands
    ADD KEY idx_demands_purpose (demand_purpose_id),
    ADD CONSTRAINT fk_demands_purpose FOREIGN KEY (demand_purpose_id)
        REFERENCES demand_purposes (id) ON DELETE SET NULL ON UPDATE CASCADE;

-- Purpose is now a master; drop the old enum column.
ALTER TABLE demands DROP COLUMN purpose;
