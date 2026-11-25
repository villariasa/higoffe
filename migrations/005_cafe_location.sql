-- Run once against the existing Supabase project.
-- Adds geographic coordinates to cafes for the "Nearby Cafés" feature.

ALTER TABLE cafes ADD COLUMN IF NOT EXISTS latitude  NUMERIC(9,6);
ALTER TABLE cafes ADD COLUMN IF NOT EXISTS longitude NUMERIC(9,6);
