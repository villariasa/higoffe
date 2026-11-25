# Higoffe — Build Tracker

Living status doc. Update this as the team works — it's the source of truth
for "what's actually done" vs. the plan at `/home/litecloud/.claude/plans/rosy-strolling-bengio.md`.

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

## Known simplifications (intentional, not bugs — see plan §1)

- **No dedicated notifications table.** Chat/queue "new activity" signals piggyback on the existing poll endpoints. Closing a tab loses that signal.
- **Order-card rendering in the live poll path is simplified.** A freshly-arrived `order_card` message shows a bare "view/respond" link via JS; the richer inline summary only renders on full page load (via `chat/_message_bubble.php`). Acceptable MVP trade-off, not worth JS-side templating effort.
- **Post comments and reviews**: tables exist in `schema.sql`, no UI built. Stretch-only per plan §1 — build only if Week 3 has slack.
- **One café per seller account** (`cafes.owner_user_id` is effectively 1:1 in how the UI uses it) — fine for MVP scope, no multi-branch support (matches plan's explicit cuts).

## Not yet done (next actions for the team)

1. **Run `schema.sql`** in the Supabase SQL editor for the project. Confirm no errors, confirm `order_statuses` seeded with all 7 labels.
2. **Create `config/db_credentials.php`** from the example file, using the **Transaction Pooler** connection string (port 6543), not the direct 5432 connection — see the comment in `db_credentials.example.php` for why.
3. **Install PHP locally** (this build environment had none — `php -l` was never run on any of these files). Lint every file before first deploy:
   ```
   find . -name "*.php" -exec php -l {} \;
   ```
4. **Click through the full core loop manually** — see `TASKS_CHECKLIST.md` §3 for the exact steps. This has never been executed, only reviewed by eye.
5. **Run the concurrency race tests** (plan §8) — these are the single highest-risk area since they were never actually exercised against a live DB.
6. **Run the XSS checklist** (plan §11) against every free-text field listed there.
7. Decide hosting (Railway/Render — verify `pdo_pgsql` is enabled) and deploy.

## Security review notes from the build itself

- Every `menu/`, `orders/` action endpoint checks café/seller ownership before mutating data (`menu/edit.php`, `menu/delete.php`, `orders/update_status.php`, `orders/create_order_card.php` all verify the acting seller owns the café/order in question) — this wasn't explicitly called out in the plan's code snippets but is required for real security, not just the happy path.
- `chat/send_message.php` and `chat/poll_messages.php` both verify the requesting user is an actual participant in the conversation (customer or assigned seller) before returning/accepting anything.
- All dynamic output uses `htmlspecialchars($x, ENT_QUOTES, 'UTF-8')` per plan §11 — but **this still needs the explicit manual XSS test pass**, since a missed spot won't show up any other way.
