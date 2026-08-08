-- 008_gifts.sql
-- Gift type master + gifts tracker (in = donations received, out = gifts given).

CREATE TABLE IF NOT EXISTS `gift_types` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `association_id` bigint(20) unsigned NOT NULL,
  `name` varchar(120) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_gt_association` (`association_id`),
  CONSTRAINT `fk_gt_association` FOREIGN KEY (`association_id`) REFERENCES `associations` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `gifts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `association_id` bigint(20) unsigned NOT NULL,
  `gift_type_id` bigint(20) unsigned DEFAULT NULL,
  `direction` enum('in','out') NOT NULL DEFAULT 'in',
  `title` varchar(180) NOT NULL,
  `party` varchar(180) DEFAULT NULL,
  `member_id` bigint(20) unsigned DEFAULT NULL,
  `value` decimal(12,2) NOT NULL DEFAULT 0.00,
  `gift_date` date DEFAULT NULL,
  `description` varchar(1000) DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_gift_association` (`association_id`),
  KEY `idx_gift_type` (`gift_type_id`),
  KEY `idx_gift_member` (`member_id`),
  CONSTRAINT `fk_gift_association` FOREIGN KEY (`association_id`) REFERENCES `associations` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_gift_type` FOREIGN KEY (`gift_type_id`) REFERENCES `gift_types` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_gift_member` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed default gift types for existing associations.
INSERT INTO `gift_types` (`association_id`, `name`, `is_active`)
SELECT a.id, t.name, 1
FROM `associations` a
CROSS JOIN (
  SELECT 'Cash' AS name UNION ALL SELECT 'In-kind' UNION ALL
  SELECT 'Trophy' UNION ALL SELECT 'Certificate' UNION ALL SELECT 'Other'
) t
WHERE NOT EXISTS (
  SELECT 1 FROM `gift_types` gt
  WHERE gt.association_id = a.id AND gt.name = t.name
);
