-- Run once against the existing Supabase project.

-- Chat upgrade: real image messages + scripted auto-reply messages.
ALTER TABLE messages DROP CONSTRAINT IF EXISTS messages_message_type_check;
ALTER TABLE messages ADD CONSTRAINT messages_message_type_check
    CHECK (message_type IN ('text','order_card','image','system'));

-- Manual payment-proof flow (stand-in for real online payment, which was
-- explicitly out of scope per the original plan).
ALTER TABLE orders ADD COLUMN IF NOT EXISTS payment_status VARCHAR(20) NOT NULL DEFAULT 'unpaid';
ALTER TABLE orders ADD CONSTRAINT IF NOT EXISTS orders_payment_status_check
    CHECK (payment_status IN ('unpaid','proof_submitted','confirmed'));
ALTER TABLE orders ADD COLUMN IF NOT EXISTS payment_proof_url VARCHAR(255);

-- Content-moderation reports now have a real admin review screen.
CREATE TABLE IF NOT EXISTS reports (
    report_id        SERIAL PRIMARY KEY,
    reporter_user_id INTEGER NOT NULL REFERENCES users(user_id),
    target_type      VARCHAR(20) NOT NULL CHECK (target_type IN ('post','user','cafe')),
    target_id        INTEGER NOT NULL,
    reason           TEXT,
    status           VARCHAR(20) NOT NULL DEFAULT 'pending' CHECK (status IN ('pending','resolved')),
    created_at       TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
ALTER TABLE reports ADD COLUMN IF NOT EXISTS status VARCHAR(20) NOT NULL DEFAULT 'pending';
ALTER TABLE reports ADD CONSTRAINT IF NOT EXISTS reports_status_check CHECK (status IN ('pending','resolved'));
