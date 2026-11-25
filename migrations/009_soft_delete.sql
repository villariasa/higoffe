-- Run once against the existing Supabase project.
-- Soft delete for posts and products: rows are flagged, never destroyed,
-- so likes/comments/order history stay intact and content is recoverable.
ALTER TABLE posts    ADD COLUMN IF NOT EXISTS is_deleted BOOLEAN NOT NULL DEFAULT FALSE;
ALTER TABLE products ADD COLUMN IF NOT EXISTS is_deleted BOOLEAN NOT NULL DEFAULT FALSE;
