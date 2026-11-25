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
    created_at     TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE cafes (
    cafe_id        SERIAL PRIMARY KEY,
    owner_user_id  INTEGER NOT NULL REFERENCES users(user_id),
    cafe_name      VARCHAR(150) NOT NULL,
    description    TEXT,
    address        VARCHAR(255),
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

CREATE TABLE posts (
    post_id        SERIAL PRIMARY KEY,
    cafe_id        INTEGER NOT NULL REFERENCES cafes(cafe_id),
    caption        TEXT,
    image_url      VARCHAR(255),
    created_at     TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE post_likes (
    like_id        SERIAL PRIMARY KEY,
    post_id        INTEGER NOT NULL REFERENCES posts(post_id),
    user_id        INTEGER NOT NULL REFERENCES users(user_id),
    created_at     TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    UNIQUE (post_id, user_id)
);

-- stretch-only, not built in MVP unless Week 3 has slack
CREATE TABLE post_comments (
    comment_id     SERIAL PRIMARY KEY,
    post_id        INTEGER NOT NULL REFERENCES posts(post_id),
    user_id        INTEGER NOT NULL REFERENCES users(user_id),
    comment_text   TEXT NOT NULL,
    created_at     TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE follows (
    follow_id        SERIAL PRIMARY KEY,
    customer_user_id INTEGER NOT NULL REFERENCES users(user_id),
    cafe_id          INTEGER NOT NULL REFERENCES cafes(cafe_id),
    created_at       TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    UNIQUE (customer_user_id, cafe_id)
);

CREATE TABLE conversations (
    conversation_id         SERIAL PRIMARY KEY,
    customer_user_id        INTEGER NOT NULL REFERENCES users(user_id),
    cafe_id                 INTEGER NOT NULL REFERENCES cafes(cafe_id),
    assigned_seller_user_id INTEGER NULL REFERENCES users(user_id),
    status                  VARCHAR(20) NOT NULL DEFAULT 'waiting'
                             CHECK (status IN ('waiting','active','resolved')),
    created_at              TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    last_message_at         TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE messages (
    message_id      SERIAL PRIMARY KEY,
    conversation_id INTEGER NOT NULL REFERENCES conversations(conversation_id),
    sender_user_id  INTEGER NOT NULL REFERENCES users(user_id),
    message_type    VARCHAR(20) NOT NULL DEFAULT 'text' CHECK (message_type IN ('text','order_card')),
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

-- optional / stretch
CREATE TABLE reports (
    report_id        SERIAL PRIMARY KEY,
    reporter_user_id INTEGER NOT NULL REFERENCES users(user_id),
    target_type      VARCHAR(20) NOT NULL CHECK (target_type IN ('post','user','cafe')),
    target_id        INTEGER NOT NULL,
    reason           TEXT,
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
