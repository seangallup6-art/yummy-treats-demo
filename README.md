# Yummy Treats! — WooCommerce demo shop

A non-functional but convincing WooCommerce shop for the Fastwork portfolio: own-brand pet treats for
Bangkok dog and cat owners, in English and Thai, with Thai payment habits (bank transfer + slip, PromptPay,
cash on delivery), a stockists page and care guides. Design direction: **Billboard** (yellow ground, black
outlines, hard shadows, pill buttons, lowercase headings).

Nothing here is real: products, prices, shops, addresses and the LINE account are placeholders in
`[brackets]` or invented for the demo. No real brands, no invented statistics, no fake reviews. The three
guides make only claims that are checked against the sources listed at the end of each guide.

## How it fits together (top-down)

Think of it like a React app that ships with its own mock server:

| Layer | What it is | Where |
|---|---|---|
| **Runtime** | WordPress + WooCommerce running on PHP-wasm (WordPress Playground). No server, no MySQL — SQLite in the browser or in Node. | `blueprint/*.json` |
| **Theme** | The whole look: a *block theme*. `theme.json` is the design-token file (palette, fonts, spacing, button styles); templates and template parts are HTML files of blocks; patterns are PHP files that output blocks. Think `theme.json` = Tailwind config, templates = page components, patterns = reusable sections. | `theme/yummy-treats/` |
| **Shop logic** | Small PHP filters that bend WooCommerce to the research findings: one badge per card, price per kg, Thai address fields, PDPA consent, pre-order dates, order-confirmation states per payment method, back-in-stock emails. | `theme/yummy-treats/inc/` |
| **Plugin** | *Bank Slip Upload for WooCommerce*: the customer sends a transfer slip after ordering, staff check it and mark the order paid. Separate from the theme so it survives a theme change. | `plugins/yt-slip-upload/` |
| **Seed** | Idempotent script that creates the products, categories, shipping zones, payment methods, pages, guides, stockists, four demo orders and the Thai dictionary. Runs from the Blueprint. | `theme/yummy-treats/seed/` |
| **Translations** | One dictionary (`seed/translations-th.php`) feeds everything: TranslatePress for page text, `.mo` files for PHP strings, a JSON file for WooCommerce's JavaScript (block cart and checkout). | `theme/yummy-treats/languages/`, `plugins/yt-slip-upload/languages/` |
| **Tools** | Playwright tests (smoke, full purchase, phone, accessibility, Thai gaps), screenshot, image generator, font copier, translation builder, site assembler. | `tools/` |

## Run it locally

Needs Node 22+, PHP 8 on the command line (for `npm run mo`) and Python 3.

```bash
npm install
bash tools/setup-site.sh      # WordPress + WooCommerce + Opn from GitHub into ./site (git-ignored)
# drop translatepress-multilingual.zip into vendor/ and re-run setup-site.sh
npm run dev                   # http://127.0.0.1:9400 — logs you in as admin automatically
```

The first boot runs `blueprint/local.json`: activates the plugins and theme, sets the shop options and
seeds the content. Seeding is idempotent, so restarting is safe. Edit anything in `theme/` or `plugins/`
and refresh. To start over, stop the server and delete `site/wp-content/database` and `site/wp-content/uploads`.

| Command | What it does |
|---|---|
| `npm run shot -- /shop/ out.png 390` | Full-page screenshot at a given width. |
| `npm run smoke` | Picks a size, adds to cart, opens cart and checkout, reports what it saw. |
| `node tools/e2e.js` | The whole buying flow: transfer order → slip upload → admin marks paid; cash-on-delivery order; sold-out "notify me". |
| `node tools/phone.js` | Phone-width pass: horizontal overflow and tap targets under 24 px on the main pages. |
| `node tools/a11y.js` | axe-core (WCAG 2.2 AA rules) on the main pages, desktop and phone, English and Thai. Needs `npm i --no-save axe-core`. |
| `npm run th-gaps` | Lists English text still showing on the Thai pages (product names and `[placeholders]` are skipped on purpose). |
| `npm run mo` | Rebuilds the Thai `.mo` and `.json` files after editing `seed/translations-th.php`. |
| `npm run images` | Regenerates the placeholder pack shots in `theme/yummy-treats/seed/img/`. |

## Adding or changing Thai

1. Add the line to `theme/yummy-treats/seed/translations-th.php`: page text under `regular`
   (`'English' => 'ไทย'`), code strings under `gettext` (`array( domain, 'English', 'ไทย' )`, with an
   optional context and plural as 4th and 5th items).
2. `npm run mo` for code strings; re-run the seed (or start over) for page text.
3. `npm run th-gaps` to see what is still English.

Page text is matched exactly as WordPress renders it, so write curly apostrophes (’) in content, and keep links
at the end of a sentence (each text piece is translated on its own).

## The shareable link

Repo: <https://github.com/seangallup6-art/yummy-treats-demo>. Once the `main` branch is pushed, open:

```
https://playground.wordpress.net/?blueprint-url=https://raw.githubusercontent.com/seangallup6-art/yummy-treats-demo/main/blueprint/playground.json
```

That boots a fresh WordPress in the visitor's browser, installs WooCommerce, TranslatePress and Opn
from their sources, installs the theme and the slip plugin from the repo and runs the same seed. Every
visitor gets their own throwaway copy: they can "order" anything and nothing is charged. Share `/th/` for
the Thai version.

## What the theme does, file by file

- `style.css` — theme header + two rules: Thai (`:lang(th)`) switches to Prompt and sets headings 20 %
  smaller than their English size; `prefers-reduced-motion` stops all animation.
- `theme.json` — palette (`base` yellow, `contrast` black, `accent` pink, `accent-2` blue, tints), three
  font families (self-hosted from Fontsource, OFL), fluid type scale, hard-shadow presets, button/heading/link
  defaults, block styles (`card`, `card-flat`, `band-ink`, button colours).
- `assets/css/theme.css` — components, in numbered sections: marquee, nav pills, cart button, product cards +
  badges, shop filter column (tinted tiles on desktop, a bottom sheet on phones), product page (gallery, zoom view,
  buy box, "what's in the bag" cards), cart/checkout (tinted step cards, black order summary), order confirmation,
  the added-to-cart toast and floating cart pill, forms, stockists, claim row and bundle promos, phone pass,
  accessibility fixes, guide and product-detail tables.
- `assets/js/` — `marquee.js` (pause control), `motion.js` (scroll reveals), `shop.js` (size buttons, one price
  line that follows the size, Add to cart without a page load, one added-to-cart toast that counts repeat adds,
  free-delivery bar read from WooCommerce's cart store, stockist chips, sticky add-to-cart bar on phones, numeric
  postcode keyboard, cart drawer focus, Copy on the bank account). Vanilla, deferred. `gallery.js` is a small
  Interactivity API store for the product gallery's "1 / 2" count and current-picture marks (a script module,
  product pages only).
- `inc/setup.php` — theme supports, block styles, and loading the Thai overrides (`.mo` for WooCommerce and
  WordPress, JSON for WooCommerce's JavaScript).
- `inc/woocommerce.php` — badge rules (sold out › pre-order › sale % › only N left › new), price per kg,
  pre-order dates in cart and order, Thai address order, PDPA + LINE checkout fields, button wording,
  free-delivery helper, the shared product-card grid (`yt_product_cards()`), translated payment and delivery
  names, Thai labels for the shop filters (WooCommerce keeps those in JSON, out of TranslatePress's reach).
- `inc/product-page.php` — the product gallery's extras (picture names from image captions, zoom button, count
  strip) and the "dark room" zoom view (title, Close, arrows, picture pills), added to WooCommerce's Product
  Gallery block output and driven by its own actions.
- `inc/cart-feedback.php` — the floating cart pill: a second view of WooCommerce's mini-cart state that opens
  the same drawer (the drawer no longer opens by itself after an add).
- `inc/checkout.php` — checkout step headings (who's ordering, where it goes, delivery, how you pay) and the
  total on the Place order button.
- `inc/orders.php` — the order-confirmation page per payment method (transfer waiting / slip checking /
  PromptPay / cash on delivery / paid / shipped / failed), the order sticker, the four steps, pre-order note, a
  guide matched to what was bought.
- `inc/back-in-stock.php` — "Get one email when it is back" on sold-out products, with PDPA consent; the list is
  emailed once on restock and then deleted.
- `inc/stockists.php` — `yt_stockist` post type + province/district/type taxonomies + `[yt_stockists]`.
- `inc/playground.php` — switches off three MySQL-only plugin features when the database is SQLite
  (stock holds, TranslatePress's error notice and its dynamic translation). Does nothing on normal hosting.
- `inc/debug.php` — `?yt-debug=1` prints routing facts and catches redirects (support tool, read-only).
- `templates/` — front page, shop/category/search, product, cart, checkout, order confirmation,
  guides index/single, stockists, help, generic page, 404.
- `patterns/` — shop grid (filter column + products, shared by the shop, category and search templates), hero, claim row, dogs/cats split, featured products, bundle promos, "read the bag", guides
  teaser, stockists teaser, LINE band, marquee, language switch, product reassurance, cart free bar,
  checkout top (back link, title, steps), continue-shopping link, confirmation steps, guide for what was
  bought, products under a guide.

Product descriptions are written by the seed as plain groups (`yt-bag__lead`, `yt-bag__card is-ingredients`,
`is-storage`, `is-feeding` with a table), so the client edits text in the editor and the layout stays.

Guides pick their product cards from the custom field `yt_guide_products` (comma-separated SKUs).

## Not done on purpose

- **Opn (cards, PromptPay)**: installed, switched off until test keys are entered in WooCommerce → Settings →
  Payments on a running site. Keys never go into the repo: a test *secret* key in a public blueprint is
  readable by anyone (see the handoff notes for the options).
- Live courier tracking, subscriptions, reviews (shown empty until there are real ones), a map embed on the
  stockists page (static image instead).
