-- "Active now" green dot on avatars (Messenger/Instagram-style presence),
-- not to be confused with a conversation's waiting/active/resolved status.
ALTER TABLE users ADD COLUMN IF NOT EXISTS last_active_at TIMESTAMPTZ NULL;
