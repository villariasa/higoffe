-- Persistent order drafts (replaces fragile session-based cart).
-- Render free tier sleeps and kills sessions; storing the draft in the DB
-- lets customers continue building their order after a server restart.
CREATE TABLE IF NOT EXISTS order_drafts (
    draft_id         SERIAL PRIMARY KEY,
    conversation_id  INTEGER NOT NULL REFERENCES conversations(conversation_id) ON DELETE CASCADE,
    customer_user_id INTEGER NOT NULL REFERENCES users(user_id) ON DELETE CASCADE,
    items            JSONB   NOT NULL DEFAULT '[]',
    updated_at       TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    UNIQUE(conversation_id, customer_user_id)
);

-- Add link column to notifications so they are clickable.
ALTER TABLE notifications ADD COLUMN IF NOT EXISTS link TEXT NULL;

-- Index for fast per-user draft lookup.
CREATE INDEX IF NOT EXISTS idx_order_drafts_customer ON order_drafts(customer_user_id);
