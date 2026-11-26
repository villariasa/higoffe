-- models/Notification.php was already writing a `link` column that never
-- got migrated — every Notification::create() call (new inquiry, likes,
-- comments, reviews, order status changes) has been crashing with
-- SQLSTATE 42703 until this runs.
ALTER TABLE notifications
    ADD COLUMN IF NOT EXISTS link VARCHAR(255) NULL;
