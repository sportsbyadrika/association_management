-- 017_demand_activity_links.sql
-- A due can now be raised for an activity: a project, a gift or an event
-- (in addition to a subscription). Add the gift/event links alongside the
-- existing project_id. No FK constraints (see 007-016).

ALTER TABLE demands
    ADD COLUMN gift_id BIGINT UNSIGNED NULL AFTER project_id,
    ADD COLUMN event_id BIGINT UNSIGNED NULL AFTER gift_id;

ALTER TABLE demands ADD KEY idx_demands_gift (gift_id);
ALTER TABLE demands ADD KEY idx_demands_event (event_id);
