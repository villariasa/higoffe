-- Multiple photos per post (Facebook/Instagram-style carousel).
-- posts.image_url keeps holding the first photo, unchanged, so every
-- existing single-image render spot (feed thumbnail, profile grid,
-- notifications, etc.) keeps working with zero changes; the feed/post
-- permalink pages query this table to render the rest as a carousel.
CREATE TABLE IF NOT EXISTS post_images (
    post_image_id INTEGER PRIMARY KEY,
    post_id       INTEGER NOT NULL REFERENCES posts(post_id) ON DELETE CASCADE,
    image_url     VARCHAR(255) NOT NULL,
    position      INTEGER NOT NULL DEFAULT 0,
    created_at    TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS idx_post_images_post ON post_images(post_id, position);
