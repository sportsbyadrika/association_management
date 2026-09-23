-- 019_rename_contributions_income_head.sql
-- Rename the "Project Contribution" income head to "Contributions" so it reads
-- that way on the receipt form, the Income & Expenditure report and PDFs.
-- Only the income head is renamed; the "Project Contribution" demand purpose is
-- left unchanged (it is referenced by name in code).

UPDATE income_heads SET name = 'Contributions' WHERE name = 'Project Contribution';
