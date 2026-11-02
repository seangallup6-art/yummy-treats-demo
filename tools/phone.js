// Phone screenshots (390 × 844, touch, 2x) of the main pages, plus a cart → checkout pass.
// Usage: node tools/phone.js [baseUrl] [outDir]
const { chromium, devices } = require('playwright');
const fs = require('fs');
const base = process.argv[2] || 'http://127.0.0.1:9400';
const out = process.argv[3] || '/tmp/shots/phone';
fs.mkdirSync(out, { recursive: true });

(async () => {
  const browser = await chromium.launch();
  const ctx = await browser.newContext({ ...devices['iPhone 13'], deviceScaleFactor: 1 });
  const page = await ctx.newPage();
  const go = async (p) => {
    await page.goto(base + p, { waitUntil: 'load', timeout: 180000 });
    await page.evaluate(() => document.querySelectorAll('.yt-reveal, .yt-reveal-stagger').forEach((e) => e.classList.add('is-in')));
    await page.waitForTimeout(1200);
  };
  const shot = async (name, full = true) => page.screenshot({ path: `${out}/${name}.png`, fullPage: full });
  const overflow = async () => page.evaluate(() => {
    const w = document.documentElement.clientWidth;
    return [...document.querySelectorAll('body *')].filter((e) => e.getBoundingClientRect().right > w + 1 && getComputedStyle(e).position !== 'fixed')
      .slice(0, 6).map((e) => (e.className && typeof e.className === 'string' ? '.' + e.className.split(' ')[0] : e.tagName) + ' ' + Math.round(e.getBoundingClientRect().right));
  });
  const smallTargets = async () => page.evaluate(() => [...document.querySelectorAll('main a, main button, main input:not([type=hidden]), header a, header button')]
    .filter((e) => { const r = e.getBoundingClientRect(); return r.width > 0 && (r.height < 24 || r.width < 24); })
    .slice(0, 8).map((e) => (e.textContent || e.getAttribute('aria-label') || e.tagName).trim().slice(0, 24) + ' ' + Math.round(e.getBoundingClientRect().width) + 'x' + Math.round(e.getBoundingClientRect().height)));

  await go('/');
  for (const [name, p] of [['home', '/'], ['shop', '/shop/'], ['product', '/product/chicken-jerky-strips/'], ['soldout', '/product/beef-liver-crunch/'], ['guide', '/how-much-should-a-condo-dog-eat/'], ['stockists', '/stockists/'], ['help', '/help/'], ['home-th', '/th/']]) {
    await go(p);
    console.log(name, '| overflow:', JSON.stringify(await overflow()), '| small targets:', JSON.stringify(await smallTargets()));
    await shot(name);
  }
  // Header menu open.
  await go('/');
  const burger = await page.$('.wp-block-navigation__responsive-container-open');
  if (burger) { await burger.click(); await page.waitForTimeout(600); await shot('menu-open', false); await page.keyboard.press('Escape'); }
  // Filters overlay on shop.
  await go('/shop/');
  const filterBtn = await page.$('.wc-block-product-filters__open-overlay, .wp-block-woocommerce-product-filters button');
  if (filterBtn) { await filterBtn.click(); await page.waitForTimeout(800); await shot('shop-filters-open', false); }
  // Sticky bar: scroll the product page past the buy button.
  await go('/product/chicken-jerky-strips/');
  await page.evaluate(() => window.scrollTo(0, 1600));
  await page.waitForTimeout(800);
  console.log('stickybar shown:', await page.$eval('.yt-stickybar', (e) => e.classList.contains('is-shown') + ' / ' + e.textContent.trim()).catch(() => 'none'));
  await shot('product-sticky', false);
  // Cart → checkout.
  await go('/product/chicken-jerky-strips/');
  await page.click('.yt-variations__btn');
  await page.waitForSelector('.single_add_to_cart_button:not(.disabled)');
  await Promise.all([page.waitForNavigation({ waitUntil: 'load', timeout: 120000 }), page.click('.single_add_to_cart_button')]);
  await go('/cart/');
  await page.waitForSelector('.wc-block-cart-items__row', { timeout: 60000 }).catch(() => {});
  await page.waitForTimeout(1500);
  console.log('cart | overflow:', JSON.stringify(await overflow()));
  await shot('cart');
  await go('/checkout/');
  await page.waitForSelector('.wc-block-components-checkout-step', { timeout: 60000 }).catch(() => {});
  await page.waitForTimeout(2000);
  console.log('checkout | overflow:', JSON.stringify(await overflow()), '| postcode inputmode:', await page.$eval('#shipping-postcode', (e) => e.inputMode || e.type).catch(() => 'n/a'), '| phone:', await page.$eval('#shipping-phone', (e) => e.type + '/' + (e.inputMode || '')).catch(() => 'n/a'));
  await shot('checkout');
  await browser.close();
})().catch((e) => { console.error('PHONE FAILED:', e.message); process.exit(1); });
