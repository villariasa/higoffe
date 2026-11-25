-- Run this once against the existing Supabase project (schema.sql already
-- has this table for fresh installs).

CREATE TABLE IF NOT EXISTS settings (
    setting_key   VARCHAR(100) PRIMARY KEY,
    setting_value TEXT,
    updated_at    TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
