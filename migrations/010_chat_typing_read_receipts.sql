-- Adds "typing..." indicators and "Seen" read receipts to chat, built on
-- top of the existing AJAX-polling architecture (no WebSocket server,
-- no new library — fits the no-frameworks coding standard).
ALTER TABLE conversations
    ADD COLUMN IF NOT EXISTS customer_last_read_id INTEGER NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS seller_last_read_id   INTEGER NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS customer_typing_at    TIMESTAMPTZ NULL,
    ADD COLUMN IF NOT EXISTS seller_typing_at      TIMESTAMPTZ NULL;
