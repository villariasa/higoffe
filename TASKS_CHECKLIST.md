# Higoffe — Task Checklist

Check items off as you go. Mirrors the phase plan at
`/home/litecloud/.claude/plans/rosy-strolling-bengio.md`. See `TRACKER.md`
for the current high-level build status.

## 0. One-time setup (whole team, do first)

- [ ] Run `schema.sql` in the Supabase SQL editor; confirm all tables created, no errors
- [ ] Confirm `order_statuses` has all 7 rows (Pending, Confirmed, Preparing, Ready for Pickup, Out for Delivery, Completed, Cancelled)
- [ ] Each teammate copies `config/db_credentials.example.php` → `config/db_credentials.php` and fills in the **Transaction Pooler** connection string (port 6543) — not the direct 5432 connection
- [ ] Confirm `config/db_credentials.php` is actually gitignored before anyone commits (`git status` should not show it)
- [ ] Install PHP locally if you don't have it; run `php -l` on every file once before first use
- [ ] Agree on hosting (Railway or Render — confirm `pdo_pgsql` extension is enabled before committing to a host)

## 1. Auth module (own: whoever picks this up)

- [ ] Register as customer — appears in `users` table with `role='customer'`
- [ ] Register as seller — appears with `role='seller'`
- [ ] Duplicate email on registration is rejected with a clear message
- [ ] Login with correct credentials redirects to the right role dashboard
- [ ] Login with wrong password is rejected
- [ ] Logout clears the session (protected page redirects to login afterward)
- [ ] Direct URL to a protected page while logged out redirects to `/auth/login.php`
- [ ] A suspended user cannot log in (test via `admin/users.php` after admin exists)

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
- [ ] Admin views specifically (`admin/users.php`, `admin/cafes.php`, `admin/posts.php`) — don't skip admin, it's not exempt

## 6. Admin module

- [ ] Suspend a user, confirm they can no longer log in; unsuspend, confirm they can again
- [ ] Verify a café, confirm the "✓ Verified" badge appears on its public page
- [ ] Delete a post from `admin/posts.php`, confirm it disappears from the public feed

## 7. Deployment

- [ ] Deploy to chosen host, confirm `pdo_pgsql` connects using the pooler DSN (not direct 5432)
- [ ] Re-run the full §3 core loop once against the live deployment, not just localhost
- [ ] Tag/note the commit where the core loop is confirmed working end-to-end, as a rollback point before further polish
