-- 021_event_image.sql
-- Allow an event to carry a display image (banner / photo).

ALTER TABLE events ADD COLUMN image_path VARCHAR(255) NULL AFTER description;
