# Yummy Treats! — WooCommerce demo shop

A non-functional but convincing WooCommerce shop for the Fastwork portfolio: own-brand pet treats for
Bangkok dog and cat owners, Thai first / English second, four Thai payment cues, stockists page, care
guides. Design direction: **Billboard** (mustard ground, ink outlines, hard shadows, pill buttons).

Nothing here is real: products, prices, shops, addresses and the LINE account are placeholders in
`[brackets]` or invented for the demo. No real brands, no invented statistics, no fake reviews.

## How it fits together (top-down)

Think of it like a React app that ships with its own mock server:

| Layer | What it is | Where |
|---|---|---|
| **Runtime** | WordPress + WooCommerce running on PHP-wasm (WordPress Playground). No server, no MySQL — SQLite in the browser or in Node. | `blueprint/*.json` |
| **Theme** | The whole look: a *block theme*. `theme.json` is the design-token file (palette, fonts, spacing, button styles); templates and template parts are HTML files of blocks; patterns are PHP files that output blocks. Think `theme.json` = Tailwind config, templates = page components, patterns = reusable sections. | `theme/yummy-treats/` |
| **Shop logic** | Small PHP filters that bend WooCommerce to the research findings: one badge per card, price per kg, Thai address fields, PDPA consent, pre-order wording. | `theme/yummy-treats/inc/` |
| **Seed** | Idempotent script that creates the 24 products, categories, shipping zones, payment gateways, pages, guides and stockists. Runs from the Blueprint. | `theme/yummy-treats/seed/` |
| **Tools** | Playwright screenshot + smoke test, placeholder image generator, font copier, site assembler. | `tools/` |

## Run it locally

Needs Node 22+ and Python 3 (for the placeholder images only).

```bash
npm install
bash tools/setup-site.sh      # WordPress + WooCommerce + Opn from GitHub into ./site (git-ignored)
# optional: drop translatepress-multilingual.zip into vendor/ and re-run setup-site.sh
npm run dev                   # http://127.0.0.1:9400 — logs you in as admin automatically
```

The first boot runs `blueprint/local.json`: activates the plugins and theme, sets the shop options and
seeds the content. Seeding is idempotent, so restarting is safe. Edit anything in `theme/` and refresh.

- `npm run shot -- /shop/ out.png 390` — full-page screenshot at a given width.
- `npm run smoke` — picks a size, adds to cart, opens cart and checkout, reports what it saw.
- `npm run images` — regenerate the placeholder pack shots in `theme/yummy-treats/seed/img/`.

## The shareable link

Repo: <https://github.com/seangallup6-art/yummy-treats-demo>. Once the `main` branch is pushed, open:

```
https://playground.wordpress.net/?blueprint-url=https://raw.githubusercontent.com/seangallup6-art/yummy-treats-demo/main/blueprint/playground.json
```

That boots a fresh WordPress in the visitor's browser, installs WooCommerce, TranslatePress and Opn
from their sources, installs the theme from the repo and runs the same seed. Every visitor gets their
own throwaway copy, which is exactly right for a demo: they can "order" anything and nothing is charged.

## What the theme does, file by file

- `style.css` — theme header + two rules: Thai (`:lang(th)`) switches to Prompt and shrinks display
  sizes 20 %; `prefers-reduced-motion` kills all animation.
- `theme.json` — palette (`base` mustard, `contrast` ink, `accent` raspberry, `accent-2` blue, tints),
  three font families (self-hosted from Fontsource, OFL), fluid type scale, hard-shadow presets,
  button/heading/link defaults, block styles (`card`, `card-flat`, `band-ink`, button colours).
- `assets/css/theme.css` — components: marquee, nav pills, cart button, product cards + badges,
  filter chips, product page buy box + variation buttons, cart/checkout, forms, bands, reveals,
  phone adjustments.
- `assets/js/` — `marquee.js` (pause control), `motion.js` (scroll reveals), `shop.js` (variation
  buttons, added-to-cart toast, free-delivery bar, stockist chips). Vanilla, deferred, ~5 KB total.
- `inc/woocommerce.php` — the badge rules (sold out › pre-order › sale % › only N left › new),
  price-per-kg, pre-order line in cart/order, Thai address locale, PDPA + LINE checkout fields,
  button wording, free-shipping threshold helper.
- `inc/stockists.php` — `yt_stockist` post type + province/district/type taxonomies + `[yt_stockists]`.
- `templates/` — front page, shop/category/search, product, cart, checkout, order confirmation,
  guides index/single, stockists, help, generic page, 404.
- `patterns/` — hero, dogs/cats split, featured products, "read the bag", guides teaser, stockists
  teaser, LINE band, marquee, language switch, product reassurance, cart free bar, confirmation steps.

## Still to do (Phase 4 → 5)

See `docs/` in the portfolio repo for the running decisions log and to-do list. Headline items:
slip-upload plugin, TranslatePress setup (TH strings), Opn test keys, order confirmation per payment
method, polish pass on phone, hosted link test.
