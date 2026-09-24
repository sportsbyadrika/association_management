-- 022_backfill_subscription_income_head.sql
-- Some subscription collections were saved with no income head, so they land
-- under "Other Income" on the Income & Expenditure report instead of
-- "Subscription". Tag existing receipts that pay a Subscription due (and have no
-- income head yet) with the association's "Subscription" income head.

UPDATE receipts r
JOIN demands d          ON d.id = r.demand_id
JOIN demand_purposes dp ON dp.id = d.demand_purpose_id
JOIN income_heads ih    ON ih.association_id = r.association_id AND ih.name = 'Subscription'
SET r.income_head_id = ih.id
WHERE r.income_head_id IS NULL
  AND dp.name = 'Subscription';
