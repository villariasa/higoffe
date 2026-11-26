-- Per the teacher's requirement: primary keys must not auto-increment.
-- SERIAL in Postgres is just INTEGER + a DEFAULT nextval(sequence) + the
-- sequence being owned by the column — dropping the DEFAULT removes the
-- auto-increment behavior without changing the column type or touching
-- any existing data/foreign keys. The app now computes each id itself
-- (config/IdGenerator.php) before every insert.
ALTER TABLE users ALTER COLUMN user_id DROP DEFAULT;
ALTER TABLE cafes ALTER COLUMN cafe_id DROP DEFAULT;
ALTER TABLE products ALTER COLUMN product_id DROP DEFAULT;
ALTER TABLE product_sizes ALTER COLUMN size_id DROP DEFAULT;
ALTER TABLE product_addons ALTER COLUMN addon_id DROP DEFAULT;
ALTER TABLE posts ALTER COLUMN post_id DROP DEFAULT;
ALTER TABLE post_likes ALTER COLUMN like_id DROP DEFAULT;
ALTER TABLE post_comments ALTER COLUMN comment_id DROP DEFAULT;
ALTER TABLE follows ALTER COLUMN follow_id DROP DEFAULT;
ALTER TABLE user_follows ALTER COLUMN user_follow_id DROP DEFAULT;
ALTER TABLE conversations ALTER COLUMN conversation_id DROP DEFAULT;
ALTER TABLE messages ALTER COLUMN message_id DROP DEFAULT;
ALTER TABLE order_statuses ALTER COLUMN status_id DROP DEFAULT;
ALTER TABLE orders ALTER COLUMN order_id DROP DEFAULT;
ALTER TABLE order_items ALTER COLUMN order_item_id DROP DEFAULT;
ALTER TABLE order_item_addons ALTER COLUMN order_item_addon_id DROP DEFAULT;
ALTER TABLE cafe_reviews ALTER COLUMN review_id DROP DEFAULT;
ALTER TABLE product_likes ALTER COLUMN like_id DROP DEFAULT;
ALTER TABLE product_reviews ALTER COLUMN review_id DROP DEFAULT;
ALTER TABLE notifications ALTER COLUMN notification_id DROP DEFAULT;
ALTER TABLE reports ALTER COLUMN report_id DROP DEFAULT;
ALTER TABLE order_drafts ALTER COLUMN draft_id DROP DEFAULT;
