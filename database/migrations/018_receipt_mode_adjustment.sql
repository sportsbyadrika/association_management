-- 018_receipt_mode_adjustment.sql
-- Allow receipts to be recorded as an "adjustment" (non-cash, non-transfer)
-- in addition to cash / fund transfer.

ALTER TABLE receipts
    MODIFY COLUMN mode ENUM('cash','fund_transfer','adjustment') NOT NULL DEFAULT 'cash';
