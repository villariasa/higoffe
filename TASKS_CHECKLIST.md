# Higoffe — Task Checklist

Check items off as you go. Mirrors the phase plan at
`/home/litecloud/.claude/plans/rosy-strolling-bengio.md`. See `TRACKER.md`
for the current high-level build status.

## 0. One-time setup (whole team, do first)

- [ ] Run `schema.sql` in the Supabase SQL editor; confirm all tables created, no errors
- [ ] Confirm `order_statuses` has all 7 rows (Pending, Confirmed, Preparing, Ready for Pickup, Out for Delivery, Completed, Cancelled)
- [ ] Each teammate copies `config/db_credentials.example.php` → `config/db_credentials.php` and fills in the **Transaction Pooler** connection string (port 6543) — not the direct 5432 connection
- [ ] Confirm `config/db_credentials.php` is actually gitignored before anyone commits (`git status` should not show it)
- [ ] If your Supabase project already had `users` before email verification was added, run `migrations/001_add_email_verification.sql`
- [ ] Copy `config/smtp_credentials.example.php` → `config/smtp_credentials.php`, fill in real SMTP details (Gmail requires an **App Password**, not your normal password — needs 2FA enabled first)
- [ ] Confirm `config/smtp_credentials.php` is gitignored too
- [ ] Set the `APP_URL` environment variable (e.g. `https://higoffe.onrender.com`, or `http://localhost:8000` locally) — verification emails use this, never the request's Host header (that header can be forged)
- [ ] Install PHP locally if you don't have it; run `php -l` on every file once before first use
- [ ] Agree on hosting (Railway or Render — confirm `pdo_pgsql` extension is enabled, and that outbound SMTP ports 587/465 aren't blocked, before committing to a host)
- [ ] Run `migrations/004_social_first_pivot.sql` (adds `posts.author_user_id`, makes `posts.cafe_id` nullable, adds `user_follows`) — required before using the feed at all
- [ ] Create the R2 bucket `higoffe-images` in the Cloudflare dashboard, enable public access (r2.dev URL is fastest to start), note the public URL
- [ ] In `higoffe-upload-worker/`: `npm install`, set `PUBLIC_BASE_URL` in `wrangler.toml` to that public URL, `wrangler secret put UPLOAD_SIGNING_SECRET` (generate with `php -r "echo bin2hex(random_bytes(32));"`), `wrangler secret put ALLOWED_ORIGINS` (your deployed app's origin), then `npm run deploy`
- [ ] Copy `config/upload_credentials.example.php` → `config/upload_credentials.php`, set `UPLOAD_WORKER_URL` to the deployed Worker's URL and `UPLOAD_SIGNING_SECRET` to the **exact same value** you just set on the Worker
- [ ] Confirm `config/upload_credentials.php` is gitignored too

## 0b. Email verification (new — test before relying on it)

- [ ] Register a new customer account with a real email you control — confirm the verification email actually arrives (check spam folder too)
- [ ] Click the link — confirm it logs you in and `users.email_verified` becomes `TRUE` in the DB
- [ ] Try logging in with an unverified account (register, don't click the link) — confirm login is blocked with a "please verify" message and a working resend link
- [ ] Use the resend link/form — confirm a second email arrives with a new token
- [ ] Log in as admin, go to `/admin/settings.php`, confirm SMTP settings saved there actually get used (temporarily set a wrong password via the UI, confirm sending fails; fix it, confirm it works again) — this proves the DB settings path is really taking priority over the file/env fallback
- [ ] Manually expire a token in the DB (`UPDATE users SET verification_token_expires_at = NOW() - INTERVAL '1 day' WHERE user_id = ...`) and click the old link — confirm it shows "link expired", not a crash
- [ ] Submit an email that was never registered to `resend_verification.php` — confirm it shows the same generic "if registered..." message (doesn't reveal whether the email exists)

## 1. Auth module (own: whoever picks this up)

- [ ] Register as customer — appears in `users` table with `role='customer'`
- [ ] Register as seller — appears with `role='seller'`
- [ ] Duplicate email on registration is rejected with a clear message
- [ ] Login with correct credentials redirects to the right role dashboard
- [ ] Login with wrong password is rejected
- [ ] Logout clears the session (protected page redirects to login afterward)
- [ ] Direct URL to a protected page while logged out redirects to `/auth/login.php`
- [ ] A suspended user cannot log in (test via `admin/users.php` after admin exists)

## 1b. Image uploads (new — test before relying on it anywhere)

- [ ] On `menu/create.php`, pick a photo, confirm it uploads and previews, confirm it saved on `menu/index.php`
- [ ] Try uploading a non-image file renamed to `.jpg` — confirm the Worker rejects it (magic-byte sniffing)
- [ ] Try uploading a >5MB file — confirm it's rejected with a clear message
- [ ] Wait 6+ minutes after loading a form, then try uploading — confirm it fails with "link expired" (proves the 5-min token actually expires) and that reloading the page fixes it

## 1c. Social feed, profiles, comments, follows (new — primary surface now)

- [ ] Customer creates a personal post (no "post as café" checkbox shown) — appears in feed attributed to their name/avatar, links to their profile
- [ ] Seller creates a post with "post as café" checked — appears attributed to the café, links to the café page
- [ ] Seller creates a post with "post as café" unchecked — appears as a personal post from that seller (not the café)
- [ ] Like/unlike toggles and count updates without a page reload
- [ ] Add a comment — appears immediately without reload, and persists on refresh
- [ ] Visit another user's `/profile/view.php?id=`, follow them, confirm follower/following counts update on both profiles
- [ ] Report a post and a café — confirm both show up in `/admin/reports.php`, "Mark resolved" removes them from the pending list
- [ ] `/admin/sales_report.php` — change the date range, confirm revenue/order-count/top-products actually change

## 2. Café + Menu modules

- [ ] Seller without a café is redirected to `cafes/profile.php` from menu/queue/orders pages
- [ ] Café profile create + edit both work, values persist
- [ ] Add product, edit product, toggle availability, delete product all work
- [ ] Add a size and an add-on to a product, confirm both show on `cafes/view.php`'s menu tab
- [ ] `cafes/discover.php` search finds a café by partial name match
- [ ] Follow/unfollow toggle works and persists

## 3. Core chat-to-order loop — run this end to end, in order

- [ ] As customer, visit a café's public page, click "Chat / Inquire" → lands in `chat/conversation.php`, conversation row created with `status='waiting'`
- [ ] As seller, the same inquiry appears in `queue/index.php` under Waiting, sorted oldest-first
- [ ] Seller clicks Accept → conversation moves to Active, seller is redirected into the chat
- [ ] Customer and seller exchange messages; both sides see new messages within ~3s via polling, without refreshing the page
- [ ] Seller clicks "Create order" → builds a cart (at least one item with a size and an add-on), finalizes → an `order_card` message appears in the conversation for both parties
- [ ] Customer clicks into the order card → lands on `orders/confirm.php`, sees correct items/total
- [ ] Customer clicks Confirm → order status becomes Confirmed, seller dashboard (`orders/seller_dashboard.php`) shows it under Confirmed
- [ ] Seller walks the order through every status button on `orders/view.php` in order: Preparing → Ready for Pickup (or Out for Delivery) → Completed
- [ ] Completed order shows correctly in `orders/customer_history.php`
- [ ] At the very start, confirm the auto-reply system message ("A barista will be with you shortly") appears centered/muted, not as a chat bubble from anyone
- [ ] When a seller claims, confirm the "You're now chatting with X" system message appears for both parties
- [ ] Send a photo in chat (camera icon) — confirm it appears as an image bubble for both parties within one poll cycle
- [ ] After the customer confirms the order, go to `orders/submit_payment.php`, upload a screenshot — confirm the order's payment section shows "waiting for confirmation" and the image appears in the chat as both an image message and a system note
- [ ] As the seller, click "Confirm payment" — confirm the order shows "✓ Payment confirmed" and a system message appears in chat

## 4. Concurrency tests (do these deliberately — highest grading/demo risk)

- [ ] **Queue claim race**: same waiting inquiry open in two browser tabs, logged in as two *different* seller accounts for the same café. Click Accept in both within under a second. Expect exactly one success; the other tab's `alert('Already claimed...')` fires and the page reloads showing it as Active, not duplicated.
- [ ] **Order status race**: fire two `update_status.php` POSTs with the same `to_label`/current status nearly simultaneously (curl or Postman, two terminals). Confirm only one actually changed the status (re-query the order afterward — it should have advanced exactly one step, not two, and not thrown an error either side).
- [ ] **Double-submit order confirm**: on `orders/confirm.php`, click Confirm twice quickly before the page navigates away. Confirm only one transition applied (status is `Confirmed`, not stuck in some broken state).

## 5. Input validation / XSS checklist (do this per field, before marking any module done)

For **every** field below: submit literally `<h1>hello</h1>` and then
`<script>alert(1)</script>`. Expected result both times: the page displays
the literal text with visible angle brackets — not a rendered heading, no
JS alert. If either fails, find the missing `htmlspecialchars($x, ENT_QUOTES, 'UTF-8')`
call for that field and fix it before merging.

- [ ] Chat message body (`chat/conversation.php` input, both the sender's and recipient's view)
- [ ] Post caption (`feed/create_post.php` → `feed/index.php`)
- [ ] Café name, description, address (`cafes/profile.php` → `cafes/view.php`)
- [ ] Product name, description (`menu/create.php` / `edit.php` → `cafes/view.php`, `menu/index.php`)
- [ ] Size label, add-on label (`menu/edit.php` → `orders/create_order_card.php`)
- [ ] Delivery address (`orders/create_order_card.php` → `orders/view.php`, `orders/confirm.php`)
- [ ] Full name (register forms → anywhere it's echoed: chat, queue, admin user list, order customer name)
- [ ] Profile bio (`profile/edit.php` → `profile/view.php`)
- [ ] Post comment text (`feed/index.php` comment box)
- [ ] Report reason (`feed/index.php`/`cafes/view.php` report prompt → `admin/reports.php`)
- [ ] Admin views specifically (`admin/users.php`, `admin/cafes.php`, `admin/posts.php`, `admin/reports.php`) — don't skip admin, it's not exempt

## 6. Admin module

- [ ] Suspend a user, confirm they can no longer log in; unsuspend, confirm they can again
- [ ] Verify a café, confirm the "✓ Verified" badge appears on its public page
- [ ] Delete a post from `admin/posts.php`, confirm it disappears from the public feed

## 7. Deployment

- [ ] Deploy to chosen host, confirm `pdo_pgsql` connects using the pooler DSN (not direct 5432)
- [ ] Re-run the full §3 core loop once against the live deployment, not just localhost
- [ ] Tag/note the commit where the core loop is confirmed working end-to-end, as a rollback point before further polish
