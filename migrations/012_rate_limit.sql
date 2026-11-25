-- Rate-limit buckets for auth endpoints (login, register, resend-verification).
-- A simple sliding-window counter per IP; the cleaner runs in a cron-like
-- fashion from the PHP app itself (prune called on every request, O(1) delete).
CREATE TABLE IF NOT EXISTS rate_limit_buckets (
    bucket_key VARCHAR(128) NOT NULL,
    window_start TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    hit_count INT NOT NULL DEFAULT 1,
    PRIMARY KEY (bucket_key, window_start)
);
CREATE INDEX IF NOT EXISTS idx_rl_buckets_key ON rate_limit_buckets(bucket_key);
