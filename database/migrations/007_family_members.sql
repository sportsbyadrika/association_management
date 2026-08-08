-- 007_family_members.sql
-- Family member type master + family member sub-records (attached to a member).

CREATE TABLE IF NOT EXISTS `family_member_types` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `association_id` bigint(20) unsigned NOT NULL,
  `name` varchar(120) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_fmt_association` (`association_id`),
  CONSTRAINT `fk_fmt_association` FOREIGN KEY (`association_id`) REFERENCES `associations` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `family_members` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `association_id` bigint(20) unsigned NOT NULL,
  `member_id` bigint(20) unsigned NOT NULL,
  `family_member_type_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(180) NOT NULL,
  `age` tinyint(3) unsigned DEFAULT NULL,
  `gender` enum('male','female','other') DEFAULT NULL,
  `mobile` varchar(20) DEFAULT NULL,
  `whatsapp` varchar(20) DEFAULT NULL,
  `email` varchar(190) DEFAULT NULL,
  `occupation` varchar(160) DEFAULT NULL,
  `relation` varchar(120) DEFAULT NULL,
  `photo_path` varchar(255) DEFAULT NULL,
  `notes` varchar(1000) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_fm_association` (`association_id`),
  KEY `idx_fm_member` (`member_id`),
  KEY `idx_fm_type` (`family_member_type_id`),
  CONSTRAINT `fk_fm_association` FOREIGN KEY (`association_id`) REFERENCES `associations` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_fm_member` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_fm_type` FOREIGN KEY (`family_member_type_id`) REFERENCES `family_member_types` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed default family member types for existing associations.
INSERT INTO `family_member_types` (`association_id`, `name`, `is_active`)
SELECT a.id, t.name, 1
FROM `associations` a
CROSS JOIN (
  SELECT 'Spouse' AS name UNION ALL SELECT 'Child' UNION ALL
  SELECT 'Parent' UNION ALL SELECT 'Sibling' UNION ALL SELECT 'Other'
) t
WHERE NOT EXISTS (
  SELECT 1 FROM `family_member_types` fmt
  WHERE fmt.association_id = a.id AND fmt.name = t.name
);
