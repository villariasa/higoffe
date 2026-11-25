# Higoffe — Build Tracker

Living status doc. Update this as the team works — it's the source of truth
for "what's actually done" vs. the plan at `/home/litecloud/.claude/plans/rosy-strolling-bengio.md`.

## Direction pivot (read this first)

Higoffe is now **social-first**: the feed/profiles/follows/comments are the
primary surface and the app's landing page; chat-to-order is a supporting
feature reachable from a café profile or a post, not the front door. See the
replan discussion in conversation for the full rationale and the USP:
*"the only app where discovering a drink socially and getting it made are
the same tap."* `index.php` now routes both roles to `/feed/index.php`
first.

## Status: scaffold complete, needs real Supabase + manual testing

Every file in the plan's directory structure (§3) has been written — models,
pages, the schema, the shared includes. **None of it has been run against a
real database or a real browser yet** — this environment has no PHP
interpreter and no display, so everything below was built by careful
hand-review against the Farmer.php convention, not by executing it. Treat
first-run as a real QA pass, not a formality.

## What's built

| Area | Files | Status |
|---|---|---|
| Schema | `schema.sql` | Written, **not yet run** against Supabase |
| DB connection | `config/Database.php`, `config/db_credentials.example.php` | Written, needs real pooler credentials in `config/db_credentials.php` (gitignored, copy from the example file) |
| Shared shell | `includes/header.php`, `footer.php`, `auth_check.php` | Written |
| Auth | `models/User.php`, `auth/*.php` | Written — register (customer/seller), login, logout, session role |
| Cafés | `models/Cafe.php`, `cafes/*.php` | Written — profile setup/edit, public view (menu+feed+follow+chat entry), discover/search |
| Menu | `models/Product.php`, `ProductSize.php`, `ProductAddon.php`, `menu/*.php` | Written — CRUD + sizes/add-ons management |
| Feed | `models/Post.php`, `PostLike.php`, `Follow.php`, `feed/*.php` | Written — posts, AJAX likes. **Comments/reviews deliberately not built** (plan §1 trim) |
| Chat | `models/Conversation.php`, `Message.php`, `chat/*.php` | Written — AJAX polling (3s), atomic claim-free start/reuse |
| Inquiry queue | `queue/*.php` | Written — waiting/active/resolved tabs, **atomic `claim()`** (plan §4), 8-10s badge poll |
| Chat-to-order | `orders/create_order_card.php` | Written — session-cart-based item builder, posts an `order_card` message type |
| Order management | `models/Order.php`, `OrderItem.php`, `orders/*.php` | Written — guarded `transition()` state machine (plan §5), customer confirm/cancel, seller status buttons, dashboards |
| Admin | `admin/*.php` | Written — counts dashboard, suspend users, verify cafés, delete posts |
| Email verification | `config/Mailer.php`, `includes/mail_helpers.php`, `auth/verify.php`, `resend_verification.php` | Written — hand-rolled SMTP client (no Composer), register no longer auto-logs-in, login blocks unverified accounts. **Needs real SMTP credentials to actually test** — see below |
| Admin-editable SMTP | `models/Settings.php`, `admin/settings.php` | Written — SMTP host/port/username/password/from/encryption are editable from `/admin/settings.php`, stored in the new `settings` table, and take priority over env vars / `config/smtp_credentials.php` once saved |
| Real image uploads | `higoffe-upload-worker/` (new Cloudflare Worker + R2 bucket), `config/Uploads.php`, `includes/image_upload_field.php`/`image_upload.js` | Written — browser uploads straight to R2 via a signed, HMAC-token, 5-min-expiry, no AWS SDK/signing in PHP. Wired into menu photos, café logo/cover, post photos, profile avatars, chat photos, payment-proof screenshots. **Worker is not deployed yet** — see next actions |
| Social pivot: posts by anyone | `models/Post.php` (rewritten), `feed/create_post.php` | Written — `posts.author_user_id` + nullable `cafe_id`; customers post personal experiences, sellers optionally post as their café |
| Comments | `models/PostComment.php`, `feed/comment.php`, `feed/index.php` | Written — now core (was stretch), AJAX add, flat (no nesting) |
| User profiles + user-to-user follows | `models/UserFollow.php`, `profile/view.php`, `profile/edit.php`, `users.bio`/`avatar_url` | Written — bio/avatar, follower/following counts, follow/unfollow |
| Messenger-style chat upgrade | `chat/_message_bubble.php`, `chat/conversation.php`, `chat/send_message.php` | Written — avatar circles, rounded "tail" bubbles, real image messages (camera button, not just order cards), centered muted `system` message type |
| Scripted auto-replies | `chat/start.php`, `queue/claim.php`, `models/Message::createSystem()` | Written — "a barista will be with you shortly" on inquiry start, "you're now chatting with X" on claim |
| Payment-proof flow | `orders.payment_status`/`payment_proof_url`, `orders/submit_payment.php`, `orders/confirm_payment.php`, `orders/view.php` | Written — customer uploads a GCash/bank screenshot, seller confirms; deliberate manual stand-in since real online payment was out of scope |
| Content-moderation reports | `models/Report.php`, `reports/submit.php`, `admin/reports.php` | Written — report a post or café, admin resolves |
| Sales report | `admin/sales_report.php`, `Order::getSalesSummary/getOrderCountsByStatus/getTopProducts` | Written — date-range revenue/orders/top-products, plain SQL aggregates |
| Soft delete + pgsql bool fix (migration 009) | `posts.is_deleted` + `products.is_deleted`; `Post::delete`/`Product::delete` now flag instead of destroying (fixes a latent FK crash deleting any ever-ordered product; likes/comments/order history survive); all read queries + tagged-product joins filter deleted. Fixed pdo_pgsql boolean binding (`false`→`''` crash) in `setSuspended`/`setVerified`/`Product::update`, and the GROUP BY error in `getOrderCountsByStatus`. Two-tap confirmations added to admin suspend/verify/resolve actions | Written |
| Product social (migration 008) | Posts can tag a product (`posts.product_id`, chip in feed/permalink, "Share a post" from the product page); products get likes (`product_likes`, AJAX `menu/like.php`) + verified-buyer reviews (`product_reviews`, gated on a Completed order containing that item) with star aggregates on café-page menu cards and trending; new product detail page `menu/view.php` | Written |
| Check-ins + post visibility | `posts.tagged_cafe_id` ("Jane is at Higoffe Coffee" header w/ map-pin) + `posts.visibility` (`public`/`cafe_only`) — café-only posts appear on the café page (and to their author) but are excluded from the global feed/profiles; create-post form has café check-in select + visibility choice | Written |
| Admin panel redesign | Dark-coffee persistent sidebar (desktop) + sticky top bar with horizontal nav (mobile) on EVERY admin page via `header.php` `$admin_nav`; all 7 pages rebuilt: stats dashboard w/ pending-reports alert, real tables for users/cafés (status pills, rating column), card-grid post moderation, styled reports w/ view-target links, sales report, SMTP form | Written |
| Desktop 3-column shell | Full-bleed desktop: sticky left sidebar (logo, location chip, vertical nav + Notifications badge, user mini-card) replaces the top navbar; Home gets a sticky right rail (search, Popular/Nearby cafés w/ ratings, Trending drinks, Suggested-to-follow via `UserFollow::getSuggestions`); chat is a true two-pane messenger on desktop (`chat/_conversation_list.php`, inbox now open to sellers too); seller dashboard gets 7-day completed/revenue stat cards (`Order::getCafeSalesSummary`). Mobile untouched | Written |
| Café reviews | `cafe_reviews` table (migration 007), `models/CafeReview.php` — verified-buyer gate (Completed order required, enforced server-side), upsert one-review-per-customer, star summary on café header + all café cards (`Cafe` queries now return `rating_avg`/`rating_count`), review list + write/update form on café page | Written |
| Post timestamps + share | `time_ago()` helper ("Just now"/"5 mins ago") on feed posts, comments, reviews, chat list; copy-link share button on feed posts; permalink page `feed/post.php` (`Post::getByIdWithMeta`) | Written |
| Turbo Drive SPA navigation | Turbo 8 via CDN in `header.php` — links swap `<body>` without full reloads, URL via History API; `Turbo.setFormMode("off")` (forms submit natively since our PHP re-renders 200s on errors); NProgress rewired to `turbo:visit`/`turbo:load`; chat poll interval self-clears when its DOM is gone | Written |
| Dialog-free UI | `includes/ui.js` — toast system (`higoffeToast`), two-tap confirm via `data-confirm` attr, inline report-reason box; all `alert/confirm/prompt` removed from customer/seller pages (admin keeps native) | Written |
| Upload size limits | Per-scope caps enforced in the Worker (avatar 2MB, product 3MB, cafe 4MB, rest 5MB) + matching client pre-check and hint text (`$max_mb` on `render_image_upload_field`) | Written — redeploy the Worker |
| Location ask + wiring | `location.js` now loaded in header (Turbo-aware re-init), dismissible "See cafés near you — Allow / Not now" banner on location-wanting pages, chips trigger permission | Written |
| Post deletion + notifications | Owners delete own posts (`feed/delete_post.php`, trash button on feed + own profile); `Post::delete()` fixed to remove likes/comments first (FK bug); admin delete now notifies the poster via new `notifications` table (migration 006), bell + unread badge in both top bars, `/notifications.php` page | Written |
| Heroicons + responsive desktop | `includes/icons.php` (Heroicon helper, zero emoji anywhere outside admin), desktop top navbar in `header.php` (≥lg) with bottom nav mobile-only, grid layouts on desktop (home rows, explore results, queue, order dashboards, café menu), eye-toggle password fields, `LOCATION_PLAN.md` (nearby-cafés feature documented but deferred; `includes/location.js` ready, unused) | Written |
| Grab/Foodpanda-style mobile UI | `includes/header.php` (app shell + NProgress top-loader + cream/coffee/accent Tailwind palette), `includes/bottom_nav.php` (fixed 5-tab role-aware nav), `includes/components.php` (café/drink/search/avatar/empty-state components), plus every customer/seller screen restyled | Written — mobile-first max-w-md app frame; Home = discovery sections (categories, popular cafés, trending drinks via `Product::getTrending`) + social feed; Explore with filter chips; café profile hero; messenger chat with seller quick-reply chips; seller dashboard home; order tracking timeline. Admin pages intentionally kept desktop-plain |

## Known simplifications (intentional, not bugs)

- **No dedicated notifications table.** Chat/queue/social "new activity" signals piggyback on existing poll endpoints or on-page counts. Closing a tab loses that signal.
- **Order-card rendering in the live poll path is simplified** for text/order_card parity with the new image-message path — both now render inline without a page reload.
- **Reviews are not a separate feature** — a post about a café *is* the review, by design (social-first pivot).
- **One café per seller account** — fine for MVP scope, no multi-branch support.
- **Comments are flat, not threaded.** Reports target `post`/`cafe` only (not generic `user` reports, though the column allows it).
- **Reacts/share**: discussed, not yet built — see the separate reactions/share proposal delivered alongside this update.

## Not yet done (next actions for the team)

1. **Run `schema.sql`** in the Supabase SQL editor for the project. Confirm no errors, confirm `order_statuses` seeded with all 7 labels. If your Supabase project already has data (schema ran before email verification existed), run `migrations/001_add_email_verification.sql` instead of re-running all of `schema.sql`.
2. **Create `config/db_credentials.php`** from the example file, using the **Transaction Pooler** connection string (port 6543), not the direct 5432 connection — see the comment in `db_credentials.example.php` for why.
3. **Create `config/smtp_credentials.php`** from `config/smtp_credentials.example.php`. Gmail SMTP needs an **App Password** (requires 2FA enabled on the Google account) — a normal password will be rejected. For development without spamming real inboxes, use Mailtrap.io's sandbox SMTP instead.
4. **Install PHP locally** (this build environment had none — `php -l` was never run on any of these files). Lint every file before first deploy:
   ```
   find . -name "*.php" -exec php -l {} \;
   ```
5. **Click through the full core loop manually** — see `TASKS_CHECKLIST.md` §3 for the exact steps. This has never been executed, only reviewed by eye.
6. **Test the email verification flow specifically** — register, confirm the email actually arrives, click the link, confirm login is blocked before verifying and works after. This is brand new code with zero live testing (no SMTP server reachable from this build environment).
7. **Run the concurrency race tests** (plan §8) — these are the single highest-risk area since they were never actually exercised against a live DB.
8. **Run the XSS checklist** (plan §11) against every free-text field listed there.
9. Decide hosting (Railway/Render — verify `pdo_pgsql` is enabled, and confirm outbound SMTP ports 587/465 aren't blocked) and deploy.
10. **Run `migrations/004_social_first_pivot.sql`** (or full `schema.sql` on a fresh DB) before touching the feed — posts now require `author_user_id`.
11. **Deploy `higoffe-upload-worker`**: create the R2 bucket `higoffe-images` in the Cloudflare dashboard, enable its public r2.dev URL (or a custom domain), `wrangler secret put UPLOAD_SIGNING_SECRET` (matching `config/upload_credentials.php`'s value exactly) and `ALLOWED_ORIGINS`, then `npm run deploy` inside `higoffe-upload-worker/`. Nothing with a photo upload button will work until this is live.
12. **Test the whole new social loop**: customer posts a personal photo, a seller posts as their café, both show correctly in the feed, comments and likes work, follow a user and confirm their posts affect nothing they shouldn't (privacy wasn't a requirement here — all posts are public).
13. **Test payment proof end-to-end**: confirm an order, submit a screenshot, confirm as seller, check the chat log shows both the image and the two system messages.

## Security review notes from the build itself

- Every `menu/`, `orders/` action endpoint checks café/seller ownership before mutating data (`menu/edit.php`, `menu/delete.php`, `orders/update_status.php`, `orders/create_order_card.php` all verify the acting seller owns the café/order in question) — this wasn't explicitly called out in the plan's code snippets but is required for real security, not just the happy path.
- `chat/send_message.php` and `chat/poll_messages.php` both verify the requesting user is an actual participant in the conversation (customer or assigned seller) before returning/accepting anything.
- All dynamic output uses `htmlspecialchars($x, ENT_QUOTES, 'UTF-8')` per plan §11 — but **this still needs the explicit manual XSS test pass**, since a missed spot won't show up any other way.
