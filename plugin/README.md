# Sena Studio

A phone-first WordPress admin screen for running a small handmade business on top of WooCommerce. One **Studio** menu, seven tabs: Today, Orders, Pieces, Requests, Posts, Events, Settings. It reads and writes WooCommerce data directly; nothing is duplicated.

- Requires WordPress 6.8+, PHP 8.3+. WooCommerce 9+ (tested with 11) for Orders, Pieces and quotes (the plugin runs without it, with a notice).
- HPOS (custom order tables) compatible.
- Text domain: `sena-studio`.

## Install

1. Upload `sena-studio.zip` via **Plugins → Add New → Upload Plugin**, or unzip into `wp-content/plugins/sena-studio/`.
2. Activate. A **Studio** item appears at the top of the admin menu. Anyone with the `manage_woocommerce` capability (Administrator, Shop manager) can use it.
3. Open **Studio → Settings** and set your packing-slip note, deposit percentage and notification email.
4. Optional: create pages with the two shortcodes below.

On a phone, the tab bar sits at the bottom of the screen. Add the admin URL (`/wp-admin/admin.php?page=sena-studio`) to the home screen for an app-like shortcut.

## Tabs

| Tab | What it does |
| --- | --- |
| **Today** | A small portal: greeting and one-line weekly pulse, monthly goal with progress bar, milestones ladder, stat tiles, 12-week sales chart, best sellers and unseen pieces, an automatic next-steps checklist, seasonal ideas and quick actions. See *Today portal* below. |
| **Orders** | Needs packing / Shipped / All, search by name or order number. Per order: Mark packed (custom `wc-packed` status), Add tracking (USPS / UPS / FedEx / Other, stored as `_sena_tracking_carrier` / `_sena_tracking_number`), printable packing slip, Pirate Ship link, Mark shipped (sets *completed*, sends the WooCommerce completed email with tracking appended). |
| **Pieces** | Product grid with status chip (Available / Sold / Made to order / Showcase from `_sena_status` + stock). Quick edit price, stock, status, Mark sold. **Add a piece** opens a short form: photos (media library or phone camera), name, price, category, one-of-a-kind vs made to order, short story, optional Etsy link (`_etsy_listing_url`). Creates a published simple product. |
| **Requests** | Inbox for the `[sena_custom_order_form]` shortcode. Status filter, detail panel, notes, quote amount. **Send quote** creates a pending WooCommerce order (deposit % or full amount) with a "Custom order deposit" line and emails the customer the pay link. |
| **Posts** | Approval queue for social posts (`sena_post`). Image, editable caption, hashtags, date/time, platform, Approve / Skip / Save / Copy caption / Mark published. Fed by the REST API (see below) or the New post button. |
| **Events** | Upcoming / past list, add and edit `sena_event` posts (`event_date` Y-m-d, `event_place`, `event_link`). If the theme already registers `sena_event`, the plugin uses it as is. |
| **Settings** | Thank-you note, deposit percentage, notification email, Pirate Ship link toggle. |

## Today portal

Sections, top to bottom (phone first; two columns from 768px, up to seven stat tiles from 1280px):

1. **Greeting + pulse** – "Good morning, Sena" (first name, else the first word of the display name), the date and one sentence computed from live data and the weekday ("2 orders to pack and 1 new request. Nice week.").
2. **Monthly goal** – the `monthly_goal` setting (default 500, editable inline on the card and in Settings). Shows this month's net sales (orders in *processing*, *packed*, *completed*, *on-hold*, minus refunds), percentage, progress bar, days left and "you need about $X per day". Set the goal to 0 to hide the numbers.
3. **Milestones** – a 12-rung ladder of tiny, auto-detected steps (every piece priced, 2+ photos each, an event on the site, first website order, first custom request answered, 5 orders, first 5-star review, a post every week for 4 weeks, first $500 month, 10 repeat customers, first $1,000 month, 100 orders). Done rungs get a check, the current one is highlighted with a why-line, an encouraging line and a button. "Tick by hand" stores a manual override in the `sena_studio_milestones` option (nonce-protected; "Untick" removes it). Filter: `sena_studio_milestones( array $items, array $data, array $insights )` – each item needs `id`, `title`, `why`, `button`, `url`, `done`; `progress` is optional.
4. **Numbers that matter** – sales this week vs last week (up/down), orders this month, average order value, repeat-customer share (customers with 2+ orders, from the WooCommerce analytics lookup table when present), open custom requests, pieces for sale, and visitors in the last 7 days when MonsterInsights is installed and its overview report is reachable. Otherwise the tile says "Connect Google Analytics" and links to MonsterInsights (settings, or the installer). No fake numbers. Filter `sena_studio_visitors_7d` can supply an integer from another source.
5. **Sales chart** – last 12 weeks of net sales as inline SVG generated in PHP (no JS libraries), week labels, hover titles, and a visually hidden table for screen readers. Below it, sales by channel for the last 90 days: *Etsy* (order meta `_etsy_order_id`), *Custom orders* (`created_via` `sena-studio` / `sena-studio-quote`, or meta `_sena_request_id`), else *Website*. With a single channel it shows one line of text instead.
6. **Best sellers / Pieces nobody has seen** – top 5 pieces by units in 90 days with thumbnail, units and revenue; and up to 5 published, in-stock pieces with zero sales in 90 days, each with **Draft a post**: creates an Instagram `sena_post` draft (first description paragraph + 5 hashtags from categories, tags and the name, filterable via `sena_studio_draft_hashtags`; image = product image) and jumps to the Posts tab.
7. **Next steps** – an automatically generated checklist ordered by impact: orders to pack, new requests, goal behind pace (after the 10th), no sale in 14 days, posts awaiting approval, pieces without a price, low stock, review requests (completed orders delivered 10–60 days ago without `_sena_review_asked`; **Mark asked** sets that meta and adds an order note), pieces with fewer than 2 photos, nothing posted in 7 days, no upcoming event. **Later** hides an item for 7 days (per user, `sena_studio_dismissed_steps`). Filter: `sena_studio_next_steps( array $steps, array $data, array $insights )` – each step needs `id`, `title`, `why`, `button`, `url`, `priority` (lower first).
8. **This season** – three static idea cards by month (Valentine's lead time Dec–Jan, Mother's Day Mar–Apr, weddings May–Jun, back-to-school journals Jul–Aug, holiday markets Sep–Nov), always plus one personalisation idea and one process-video idea. Filter `sena_studio_season_ideas( array $ideas, int $month )`. No AI calls.
9. **Quick actions** – Add a piece, Add an event, New post, and **Print N packing slips** (one printable page, a page break per *processing* order, same slip layout as the single packing slip).

The 12-week series, best sellers, channels and totals are cached in a 10-minute transient keyed by the day (`sena_studio_insights_YYYYMMDD`) and flushed on `woocommerce_order_status_changed`, `woocommerce_new_order` and `woocommerce_order_refunded`. Every section renders with WooCommerce inactive or with zero orders.

### Admin bar and login redirect

A **Studio** link is added to the admin bar for anyone who can use Studio. Users with `manage_woocommerce` are sent to the Studio page after login unless they were heading somewhere specific (`redirect_to` is respected). Disable with:

```php
add_filter( 'sena_studio_login_redirect', '__return_false' );
```

## Shortcodes

### `[sena_custom_order_form]`
Accessible custom-order request form: name, email, description, budget, optional reference image (JPG/PNG/WebP/GIF/HEIC, max 5 MB). Honeypot, nonce and a per-visitor rate limit. Creates a `sena_request` and emails the notification address with a link into Studio.

### `[sena_track_order]`
Customer-facing lookup: order number + billing email returns the order status and tracking (with a carrier link when known). Tracking also appears on the customer's order view page and in the *completed* email.

## Custom order status

`wc-packed` ("Packed") is registered and inserted after *Processing* in the WooCommerce status list. It counts as a paid status.

## REST API

Namespace: `sena-studio/v1`. Every route requires the `manage_woocommerce` capability. Authenticate with an **application password** (Users → Profile → Application Passwords) using HTTP Basic auth, or with cookie + `X-WP-Nonce` from inside WordPress.

| Method | Route | Purpose |
| --- | --- | --- |
| `GET` | `/posts?status=draft&per_page=20&page=1` | List posts. `status` is one of `draft`, `approved`, `published`, `skipped` or empty for all. |
| `POST` | `/posts` | Create a post. Body: `platform` (instagram / tiktok / pinterest), `caption` (required), `hashtags`, `product_id`, `image_id`, `scheduled` (any date string), `status` (default `draft`). |
| `GET` | `/posts/{id}` | One post. |
| `POST`/`PUT`/`PATCH` | `/posts/{id}` | Update any of the fields above. |
| `POST` | `/posts/{id}/approve` | Set status `approved`. |
| `POST` | `/posts/{id}/skip` | Set status `skipped`. |
| `POST` | `/posts/{id}/publish` | Set status `published` (call this from the automation after it posts). |
| `GET` | `/products/recent?per_page=10&since=2025-01-01` | Recently published products with image URLs, price, status, description, categories and Etsy link. Webhook-friendly input for caption generation. |

Post objects look like:

```json
{
  "id": 123,
  "platform": "instagram",
  "caption": "New in the shop: ...",
  "hashtags": "#handmade #leather",
  "product_id": 45,
  "product_name": "Oak leaf card wallet",
  "product_url": "https://example.com/product/oak-leaf-card-wallet/",
  "scheduled": "2025-06-01T09:00",
  "status": "draft",
  "image_id": 300,
  "image_url": "https://example.com/wp-content/uploads/...jpg",
  "created": "2025-05-30T14:02:11+00:00",
  "modified": "2025-05-30T14:02:11+00:00"
}
```

### Example: n8n (or curl)

Set `SITE`, `USER` and `APP_PASSWORD` (spaces in the application password are fine).

```bash
# 1. Pull the newest pieces to write captions for
curl -u "$USER:$APP_PASSWORD" "$SITE/wp-json/sena-studio/v1/products/recent?per_page=5"

# 2. Push a draft into the approval queue
curl -u "$USER:$APP_PASSWORD" \
  -H "Content-Type: application/json" \
  -X POST "$SITE/wp-json/sena-studio/v1/posts" \
  -d '{
    "platform": "instagram",
    "caption": "Fresh off the bench: a hand-stitched oak leaf wallet in chestnut veg-tan.",
    "hashtags": "#handmade #leathercraft #slowmade",
    "product_id": 45,
    "scheduled": "2025-06-01 09:00"
  }'

# 3. Later, fetch everything Sena approved and post it
curl -u "$USER:$APP_PASSWORD" "$SITE/wp-json/sena-studio/v1/posts?status=approved"

# 4. Mark as published once it is live
curl -u "$USER:$APP_PASSWORD" -X POST "$SITE/wp-json/sena-studio/v1/posts/123/publish"
```

In n8n use an **HTTP Request** node with *Authentication: Basic Auth* (WordPress user + application password) and *Body Content Type: JSON*.

## Settings

| Setting | Used for |
| --- | --- |
| Thank-you note | Printed at the bottom of every packing slip. |
| Deposit percentage | Default deposit when sending a quote from a request (full amount is also offered). |
| Notification email | Receives new custom requests; used as Reply-To on quote emails. |
| Pirate Ship link | Shows a "Buy label" button on unshipped orders. |
| Monthly sales goal | Dollar target for the Today goal card (default 500; also editable inline on the card). |

Stored in the `sena_studio_settings` option.

## Data

- Order meta: `_sena_tracking_carrier`, `_sena_tracking_number`, `_sena_request_id` (on quote orders), `_sena_review_asked`.
- Options: `sena_studio_settings`, `sena_studio_milestones` (manual ticks); per-user option `sena_studio_dismissed_steps`.
- Product meta: `_sena_status` (available / sold / made_to_order / showcase), `_etsy_listing_url`.
- CPTs: `sena_request` (meta `_sena_req_*`), `sena_post` (meta `_sena_platform`, `_sena_hashtags`, `_sena_product_id`, `_sena_scheduled`, `_sena_status`, `_sena_image_id`, `_sena_published_at`), `sena_event` (meta `event_date`, `event_place`, `event_link`).
- A hidden virtual product with SKU `sena-custom-deposit` is created on first quote and reused.

## Uninstall

Deleting the plugin removes its settings, requests and social posts. Events are kept (the theme may own them). WooCommerce orders, products and their meta are never touched.

## Security notes

Every admin action is behind a capability check and a nonce; all input is sanitized and all output escaped; uploads are validated by extension and real MIME type and size-limited; the public form has a honeypot and a rate limit; REST routes require `manage_woocommerce`.
