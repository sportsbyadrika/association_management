-- 020_heal_orphaned_activity_links.sql
-- Gifts and events carry no foreign keys, so deleting one left its receipts and
-- expenditures pointing at a row that no longer exists. The Income & Expenditure
-- report inner-joins those tables, so such rows silently vanished (they were not
-- counted under the activity nor under "general"). Clear the dangling links so
-- the money falls back to general/association and is visible again.

UPDATE receipts     SET gift_id  = NULL, category = 'general'
    WHERE gift_id  IS NOT NULL AND gift_id  NOT IN (SELECT id FROM gifts);
UPDATE receipts     SET event_id = NULL, category = 'general'
    WHERE event_id IS NOT NULL AND event_id NOT IN (SELECT id FROM events);

UPDATE expenditures SET gift_id  = NULL, category = 'association'
    WHERE gift_id  IS NOT NULL AND gift_id  NOT IN (SELECT id FROM gifts);
UPDATE expenditures SET event_id = NULL, category = 'association'
    WHERE event_id IS NOT NULL AND event_id NOT IN (SELECT id FROM events);
