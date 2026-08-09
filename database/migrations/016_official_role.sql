-- 016_official_role.sql
-- Add an "official" login role: committee officials who can see only the
-- Dashboard and Reports.

ALTER TABLE users
    MODIFY role ENUM('super_admin','association_admin','association_staff','official','member') NOT NULL;
