-- Run once against the existing Supabase project.
CREATE TABLE IF NOT EXISTS cafe_reviews (
    review_id        SERIAL PRIMARY KEY,
    cafe_id          INTEGER NOT NULL REFERENCES cafes(cafe_id),
    customer_user_id INTEGER NOT NULL REFERENCES users(user_id),
    rating           INTEGER NOT NULL CHECK (rating BETWEEN 1 AND 5),
    review_text      TEXT,
    created_at       TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    UNIQUE (cafe_id, customer_user_id)
);
