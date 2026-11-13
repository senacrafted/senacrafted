# Sena Crafted — WordPress theme

A classic (PHP) WordPress + WooCommerce theme that reproduces the hand-drawn one-page site at senacrafted.com: handwriting font, cream/red/khaki palette, painted stripes and gingham, the wavy custom-orders frame, doodles, the filterable product grid with the photo lightbox, about, events, contact card and the mushroom footer.

* Theme folder: `sena-crafted/` · zip: `sena-crafted.zip`
* Requires WordPress 6.8+, PHP 8.1+ (built against PHP 8.3), WooCommerce 9+ (optional, but the shop section is empty without it).

## Install

1. WordPress admin → **Appearance → Themes → Add New → Upload Theme**.
2. Choose `sena-crafted.zip`, install, then **Activate**.
3. **Settings → Reading**: set *Your homepage displays* to **A static page** and pick any page as the homepage (the theme's `front-page.php` renders the one-page layout regardless of that page's content).
4. **Appearance → Menus** (optional): create a menu and assign it to *Primary (header)*. If no menu is assigned, the theme shows the original links (Shop, Custom, Find me, About, Contact) that jump to the sections on the front page. Use `/#work`, `/#custom`, `/#events`, `/#about`, `/#contact` as custom-link URLs if you build your own menu. A *Footer* menu location is also available.
5. WooCommerce → Settings → Products: set the Shop page. Import products (e.g. from `migration/woocommerce-products.csv`) and upload product photos to the media library; the first product image is the card tile, the second shows on hover, all gallery images show in the lightbox.

## Customizer (Appearance → Customize → Sena Crafted)

| Section | Settings |
| --- | --- |
| Announcement & tagline | Announcement bar text (empty = hidden), tagline (hidden h1 text for SEO / screen readers) |
| About section | Heading ("Hi, I'm Sena!"), about paragraphs (blank line between paragraphs), the three photos (big left, top right, bottom right) |
| Custom orders | Custom orders text, custom order page URL (default `/custom-order/`) |
| Contact & socials | Hello text (line breaks kept), email, Instagram handle, TikTok handle (empty = button hidden), location shown in the footer |

Defaults are the values from the old `works.js` `SITE` object, so the site looks right before you touch anything.

## Product cards

* Categories: filter pills are built automatically from the **top-level product categories** ("All" + one pill per category). Sub-categories roll up into their parent.
* Order: all published products, newest first (shop archive follows WooCommerce ordering).
* Price: WooCommerce price (ranges for variable products). Made-to-order pieces show a trailing "+".
* Product meta `_sena_status` (add it in the product's *Custom Fields* box, or via CSV import column `Meta: _sena_status`):
  * `custom` → "Made to order" badge and an **Order one** button linking to the custom-order page.
  * `showcase` → "Showcase piece" (not purchasable).
  * `sold` or simply *out of stock* → "Sold" badge, price struck through, **Ask about similar** email button.
  * anything else → **Add to cart** (simple products, AJAX) or **View** (variable products). Products without a price fall back to a **Buy on Etsy** button when the `_etsy_listing_url` meta is set, otherwise **Ask about it** (email).
* Clicking a photo opens the lightbox with every gallery image, the full description, the price and the same action button.

## Events ("Find me at a festival")

The theme registers an **Events** post type (admin menu → Events). Each event has a title plus a **Date**, **Place** and optional **Details link** in the "Event details" box. The front page lists upcoming events soonest first and hides past ones automatically. When there are none it shows "No festivals scheduled yet, follow Instagram for dates."

## Pages the theme expects

Create these pages (any content / form plugin you like):

| Slug | Used by |
| --- | --- |
| `/custom-order/` | "Start a custom order" button and every "Order one" button (changeable in the Customizer) |
| `/shop/` | WooCommerce shop page (set under WooCommerce → Settings → Products) |
| `/shipping-returns/` | Link it from the footer menu / checkout terms |
| `/faq/` | Link it from the footer menu |

Cart, checkout and my-account pages are the standard WooCommerce pages (blocks or shortcodes both work) and are styled by the theme.

## Files

```
sena-crafted/
├── style.css              theme header only
├── functions.php          supports, enqueues, image sizes
├── theme.json             palette + fonts for the editor
├── header.php / footer.php (footer holds the lightbox <dialog>)
├── front-page.php         the one-page layout
├── index.php, page.php, single.php, 404.php, searchform.php
├── inc/customizer.php     Customizer settings + defaults
├── inc/events.php         sena_event post type + meta box
├── inc/woocommerce.php    hooks + the product card renderer
├── inc/template-tags.php  helpers (doodles, mailto, paragraphs)
├── inc/class-sena-flat-walker.php
├── woocommerce/           archive-product, content-product, content-single-product, loop/*
├── assets/css/main.css    styles (ported from styles.css + WooCommerce)
├── assets/js/main.js      filters, lightbox, wavy frame (no jQuery)
├── assets/img/            drawings, stripes, gingham, logo, default about photos
├── fonts/SenaHandwriting-Regular.ttf
└── screenshot.png
```

SEO: the `<title>` comes from WordPress (title-tag); Open Graph / meta descriptions are left to Yoast or a similar plugin.
