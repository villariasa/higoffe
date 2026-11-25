-- Higoffe schema — Supabase PostgreSQL
-- Run this once in the Supabase SQL editor (or psql) before any app code runs.
-- See /home/litecloud/.claude/plans/rosy-strolling-bengio.md §2 for the full plan this came from.

CREATE TABLE users (
    user_id        SERIAL PRIMARY KEY,
    role           VARCHAR(20) NOT NULL CHECK (role IN ('customer','seller','admin')),
    full_name      VARCHAR(100) NOT NULL,
    email          VARCHAR(150) NOT NULL UNIQUE,
    password_hash  VARCHAR(255) NOT NULL,
    phone          VARCHAR(30),
    is_suspended   BOOLEAN NOT NULL DEFAULT FALSE,
    email_verified BOOLEAN NOT NULL DEFAULT FALSE,
    verification_token             VARCHAR(64),
    verification_token_expires_at  TIMESTAMPTZ,
    bio            VARCHAR(280),
    avatar_url     VARCHAR(255),
    created_at     TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE cafes (
    cafe_id        SERIAL PRIMARY KEY,
    owner_user_id  INTEGER NOT NULL REFERENCES users(user_id),
    cafe_name      VARCHAR(150) NOT NULL,
    description    TEXT,
    address        VARCHAR(255),
    latitude       NUMERIC(9,6),
    longitude      NUMERIC(9,6),
    logo_url       VARCHAR(255),
    cover_url      VARCHAR(255),
    is_verified    BOOLEAN NOT NULL DEFAULT FALSE,
    created_at     TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE products (
    product_id     SERIAL PRIMARY KEY,
    cafe_id        INTEGER NOT NULL REFERENCES cafes(cafe_id),
    product_name   VARCHAR(150) NOT NULL,
    description    TEXT,
    base_price     NUMERIC(10,2) NOT NULL,
    image_url      VARCHAR(255),
    is_available   BOOLEAN NOT NULL DEFAULT TRUE,
    is_deleted     BOOLEAN NOT NULL DEFAULT FALSE,
    created_at     TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE product_sizes (
    size_id        SERIAL PRIMARY KEY,
    product_id     INTEGER NOT NULL REFERENCES products(product_id),
    size_label     VARCHAR(30) NOT NULL,
    price_addon    NUMERIC(10,2) NOT NULL DEFAULT 0
);

CREATE TABLE product_addons (
    addon_id       SERIAL PRIMARY KEY,
    product_id     INTEGER NOT NULL REFERENCES products(product_id),
    addon_label    VARCHAR(50) NOT NULL,
    addon_price    NUMERIC(10,2) NOT NULL DEFAULT 0
);

-- Posts are the primary social surface: EITHER a seller posting as their
-- café (cafe_id set) OR any user posting a personal café experience
-- (cafe_id NULL). author_user_id is always the real poster, for either
-- case. product_id optionally tags a specific drink/menu item the post
-- is about — the feed renders it as a product chip linking to the item.
-- tagged_cafe_id = a check-in ("Jane is at Higoffe Coffee") — distinct
-- from cafe_id, which means the post was authored AS that café.
-- visibility: 'public' = global feed; 'cafe_only' = shown only on the
-- relevant café's page (and to the author themselves).
CREATE TABLE posts (
    post_id        SERIAL PRIMARY KEY,
    author_user_id INTEGER NOT NULL REFERENCES users(user_id),
    cafe_id        INTEGER NULL REFERENCES cafes(cafe_id),
    tagged_cafe_id INTEGER NULL REFERENCES cafes(cafe_id),
    product_id     INTEGER NULL REFERENCES products(product_id),
    visibility     VARCHAR(10) NOT NULL DEFAULT 'public' CHECK (visibility IN ('public','cafe_only')),
    caption        TEXT,
    image_url      VARCHAR(255),
    is_deleted     BOOLEAN NOT NULL DEFAULT FALSE,
    created_at     TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE post_likes (
    like_id        SERIAL PRIMARY KEY,
    post_id        INTEGER NOT NULL REFERENCES posts(post_id),
    user_id        INTEGER NOT NULL REFERENCES users(user_id),
    created_at     TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    UNIQUE (post_id, user_id)
);

-- Core social feature (was stretch-only under the old ordering-first scope).
CREATE TABLE post_comments (
    comment_id     SERIAL PRIMARY KEY,
    post_id        INTEGER NOT NULL REFERENCES posts(post_id),
    user_id        INTEGER NOT NULL REFERENCES users(user_id),
    comment_text   TEXT NOT NULL,
    created_at     TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- Customer follows a café (unchanged from the original plan).
CREATE TABLE follows (
    follow_id        SERIAL PRIMARY KEY,
    customer_user_id INTEGER NOT NULL REFERENCES users(user_id),
    cafe_id          INTEGER NOT NULL REFERENCES cafes(cafe_id),
    created_at       TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    UNIQUE (customer_user_id, cafe_id)
);

-- User follows user — separate table from `follows` on purpose: two plain
-- tables are simpler and clearer than one polymorphic nullable-pair table.
CREATE TABLE user_follows (
    user_follow_id    SERIAL PRIMARY KEY,
    follower_user_id  INTEGER NOT NULL REFERENCES users(user_id),
    followed_user_id  INTEGER NOT NULL REFERENCES users(user_id),
    created_at        TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    UNIQUE (follower_user_id, followed_user_id),
    CHECK (follower_user_id != followed_user_id)
);

CREATE TABLE conversations (
    conversation_id         SERIAL PRIMARY KEY,
    customer_user_id        INTEGER NOT NULL REFERENCES users(user_id),
    cafe_id                 INTEGER NOT NULL REFERENCES cafes(cafe_id),
    assigned_seller_user_id INTEGER NULL REFERENCES users(user_id),
    status                  VARCHAR(20) NOT NULL DEFAULT 'waiting'
                             CHECK (status IN ('waiting','active','resolved')),
    created_at              TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    last_message_at         TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    -- Read receipts + typing indicators (polled, not pushed — see migration 010).
    customer_last_read_id   INTEGER NOT NULL DEFAULT 0,
    seller_last_read_id     INTEGER NOT NULL DEFAULT 0,
    customer_typing_at      TIMESTAMPTZ NULL,
    seller_typing_at        TIMESTAMPTZ NULL
);

CREATE TABLE messages (
    message_id      SERIAL PRIMARY KEY,
    conversation_id INTEGER NOT NULL REFERENCES conversations(conversation_id),
    sender_user_id  INTEGER NOT NULL REFERENCES users(user_id),
    message_type    VARCHAR(20) NOT NULL DEFAULT 'text' CHECK (message_type IN ('text','order_card','image','system')),
    body            TEXT,
    order_id        INTEGER NULL,
    image_url       VARCHAR(255),
    created_at      TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE order_statuses (
    status_id    SERIAL PRIMARY KEY,
    status_label VARCHAR(30) NOT NULL UNIQUE
);

CREATE TABLE orders (
    order_id           SERIAL PRIMARY KEY,
    conversation_id    INTEGER NOT NULL REFERENCES conversations(conversation_id),
    customer_user_id   INTEGER NOT NULL REFERENCES users(user_id),
    cafe_id            INTEGER NOT NULL REFERENCES cafes(cafe_id),
    status_id          INTEGER NOT NULL REFERENCES order_statuses(status_id),
    fulfillment_type   VARCHAR(20) NOT NULL DEFAULT 'pickup' CHECK (fulfillment_type IN ('pickup','delivery')),
    delivery_address   VARCHAR(255),
    subtotal           NUMERIC(10,2) NOT NULL DEFAULT 0,
    additional_charges NUMERIC(10,2) NOT NULL DEFAULT 0,
    total_amount       NUMERIC(10,2) NOT NULL DEFAULT 0,
    payment_status     VARCHAR(20) NOT NULL DEFAULT 'unpaid'
                        CHECK (payment_status IN ('unpaid','proof_submitted','confirmed')),
    payment_proof_url  VARCHAR(255),
    created_at         TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at         TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE order_items (
    order_item_id SERIAL PRIMARY KEY,
    order_id      INTEGER NOT NULL REFERENCES orders(order_id),
    product_id    INTEGER NOT NULL REFERENCES products(product_id),
    size_id       INTEGER NULL REFERENCES product_sizes(size_id),
    quantity      INTEGER NOT NULL DEFAULT 1,
    unit_price    NUMERIC(10,2) NOT NULL,
    line_total    NUMERIC(10,2) NOT NULL
);

CREATE TABLE order_item_addons (
    order_item_addon_id SERIAL PRIMARY KEY,
    order_item_id       INTEGER NOT NULL REFERENCES order_items(order_item_id),
    addon_id             INTEGER NOT NULL REFERENCES product_addons(addon_id),
    addon_price          NUMERIC(10,2) NOT NULL
);

-- Admin-editable key/value config (SMTP settings live here — see
-- admin/settings.php — so they can be changed from the UI without a
-- redeploy, instead of being locked to env vars / a config file).
CREATE TABLE settings (
    setting_key   VARCHAR(100) PRIMARY KEY,
    setting_value TEXT,
    updated_at    TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- Product (menu item) reviews: one per customer per product, only from
-- customers whose Completed order actually contained that product
-- (verified-buyer gate, enforced in PHP).
CREATE TABLE product_reviews (
    review_id        SERIAL PRIMARY KEY,
    product_id       INTEGER NOT NULL REFERENCES products(product_id),
    customer_user_id INTEGER NOT NULL REFERENCES users(user_id),
    rating           INTEGER NOT NULL CHECK (rating BETWEEN 1 AND 5),
    review_text      TEXT,
    created_at       TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    UNIQUE (product_id, customer_user_id)
);

-- Product likes (lightweight favorites on menu items).
CREATE TABLE product_likes (
    like_id    SERIAL PRIMARY KEY,
    product_id INTEGER NOT NULL REFERENCES products(product_id),
    user_id    INTEGER NOT NULL REFERENCES users(user_id),
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    UNIQUE (product_id, user_id)
);

-- Café reviews: one per customer per café, only from customers with at
-- least one Completed order there ("verified buyer" reviews — enforced in
-- PHP). Re-reviewing updates the existing row.
CREATE TABLE cafe_reviews (
    review_id        SERIAL PRIMARY KEY,
    cafe_id          INTEGER NOT NULL REFERENCES cafes(cafe_id),
    customer_user_id INTEGER NOT NULL REFERENCES users(user_id),
    rating           INTEGER NOT NULL CHECK (rating BETWEEN 1 AND 5),
    review_text      TEXT,
    created_at       TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    UNIQUE (cafe_id, customer_user_id)
);

-- In-app notifications (e.g. "an admin removed your post"). Minimal by
-- design: plain text body, read flag, newest-first list.
CREATE TABLE notifications (
    notification_id SERIAL PRIMARY KEY,
    user_id         INTEGER NOT NULL REFERENCES users(user_id),
    body            TEXT NOT NULL,
    is_read         BOOLEAN NOT NULL DEFAULT FALSE,
    created_at      TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- Content-moderation reports (customers flag a post/café; admin reviews).
CREATE TABLE reports (
    report_id        SERIAL PRIMARY KEY,
    reporter_user_id INTEGER NOT NULL REFERENCES users(user_id),
    target_type      VARCHAR(20) NOT NULL CHECK (target_type IN ('post','user','cafe')),
    target_id        INTEGER NOT NULL,
    reason           TEXT,
    status           VARCHAR(20) NOT NULL DEFAULT 'pending' CHECK (status IN ('pending','resolved')),
    created_at       TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- Seed the controlled order-status lookup (run once, after the table exists).
INSERT INTO order_statuses (status_label) VALUES
    ('Pending'),
    ('Confirmed'),
    ('Preparing'),
    ('Ready for Pickup'),
    ('Out for Delivery'),
    ('Completed'),
    ('Cancelled');
