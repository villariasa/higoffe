-- Run once against the existing Supabase project.
-- Product-level social: posts can tag a product; products get likes +
-- verified reviews/ratings.

ALTER TABLE posts ADD COLUMN IF NOT EXISTS product_id INTEGER REFERENCES products(product_id);
-- Check-in tag ("is at X") + per-post visibility.
ALTER TABLE posts ADD COLUMN IF NOT EXISTS tagged_cafe_id INTEGER REFERENCES cafes(cafe_id);
ALTER TABLE posts ADD COLUMN IF NOT EXISTS visibility VARCHAR(10) NOT NULL DEFAULT 'public';

CREATE TABLE IF NOT EXISTS product_reviews (
    review_id        SERIAL PRIMARY KEY,
    product_id       INTEGER NOT NULL REFERENCES products(product_id),
    customer_user_id INTEGER NOT NULL REFERENCES users(user_id),
    rating           INTEGER NOT NULL CHECK (rating BETWEEN 1 AND 5),
    review_text      TEXT,
    created_at       TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    UNIQUE (product_id, customer_user_id)
);

CREATE TABLE IF NOT EXISTS product_likes (
    like_id    SERIAL PRIMARY KEY,
    product_id INTEGER NOT NULL REFERENCES products(product_id),
    user_id    INTEGER NOT NULL REFERENCES users(user_id),
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    UNIQUE (product_id, user_id)
);
