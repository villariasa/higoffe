-- Run once against the existing Supabase project.
-- Supports the pivot to social-first: any user can post (not just
-- sellers), user profiles get a bio/avatar, and users can follow users
-- (not just cafés).

ALTER TABLE users ADD COLUMN IF NOT EXISTS bio VARCHAR(280);
ALTER TABLE users ADD COLUMN IF NOT EXISTS avatar_url VARCHAR(255);

-- posts: add author_user_id, backfill it from the café owner for existing
-- rows, then make cafe_id nullable so personal (non-café) posts can exist.
ALTER TABLE posts ADD COLUMN IF NOT EXISTS author_user_id INTEGER REFERENCES users(user_id);
UPDATE posts p SET author_user_id = c.owner_user_id
    FROM cafes c WHERE p.cafe_id = c.cafe_id AND p.author_user_id IS NULL;
ALTER TABLE posts ALTER COLUMN author_user_id SET NOT NULL;
ALTER TABLE posts ALTER COLUMN cafe_id DROP NOT NULL;

CREATE TABLE IF NOT EXISTS user_follows (
    user_follow_id    SERIAL PRIMARY KEY,
    follower_user_id  INTEGER NOT NULL REFERENCES users(user_id),
    followed_user_id  INTEGER NOT NULL REFERENCES users(user_id),
    created_at        TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    UNIQUE (follower_user_id, followed_user_id),
    CHECK (follower_user_id != followed_user_id)
);
