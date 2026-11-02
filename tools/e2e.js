// End-to-end: buy by transfer → upload slip → staff marks paid; buy by COD; sold-out notify.
// Usage: node tools/e2e.js [baseUrl] [outDir]   (screenshots go to outDir)
const { chromium } = require('playwright');
const path = require('path');
const fs = require('fs');

const base = process.argv[2] || 'http://127.0.0.1:9400';
const out = process.argv[3] || '/tmp/shots/e2e';
fs.mkdirSync(out, { recursive: true });
const slip = path.join(__dirname, '..', 'theme', 'yummy-treats', 'seed', 'img', 'sample-slip.png');
const log = (...a) => console.log('•', ...a);
const go = (page, p) => page.goto(base + p, { waitUntil: 'load', timeout: 180000 });

async function addToCart(page, slug, sizeIndex = 0) {
  await go(page, '/product/' + slug + '/');
  const sizes = await page.$$('.yt-variations__btn:not([disabled])');
  if (sizes.length) {
    await sizes[Math.min(sizeIndex, sizes.length - 1)].click();
    await page.waitForSelector('.single_add_to_cart_button:not(.disabled)', { timeout: 30000 });
  }
  await Promise.all([page.waitForNavigation({ waitUntil: 'load', timeout: 120000 }), page.click('.single_add_to_cart_button')]);
  const msg = await page.$eval('.woocommerce-message, .wc-block-components-notice-banner', (e) => e.textContent.trim()).catch(() => 'no notice');
  log('  added', slug, '→', msg.slice(0, 60));
}

async function checkout(page, method) {
  await go(page, '/checkout/');
  await page.waitForSelector('#shipping-first_name', { state: 'attached', timeout: 90000 });
  // A returning customer sees their saved address as a card; open it for editing.
  const edit = await page.$('.wc-block-components-address-card__edit');
  if (edit && !(await page.isVisible('#shipping-first_name'))) { await edit.click(); await page.waitForSelector('#shipping-first_name', { timeout: 30000 }); }
  const fill = async (sel, val) => { const el = await page.$(sel); if (el) { await el.fill(''); await el.fill(val); } };
  await fill('#email', 'e2e@example.com');
  await fill('#shipping-first_name', 'Test');
  await fill('#shipping-last_name', '[e2e]');
  await fill('#shipping-address_1', '[House number, street]');
  await fill('#shipping-address_2', 'Khlong Toei Nuea');
  await fill('#shipping-city', 'Watthana');
  await fill('#shipping-postcode', '10110');
  const consent = page.getByLabel(/I agree that Yummy Treats/i);
  if (await consent.count()) { await consent.check(); }
  await page.waitForTimeout(1500);
  await page.click('#radio-control-wc-payment-method-options-' + method, { force: true });
  await page.waitForTimeout(1000);
  await page.click('.wc-block-components-checkout-place-order-button');
  try {
    await page.waitForURL(/order-received/, { timeout: 90000 });
  } catch (e) {
    const problems = await page.$$eval('.wc-block-components-validation-error, .wc-block-components-notice-banner', (els) => els.map((x) => x.textContent.trim()));
    await page.screenshot({ path: out + '/checkout-failed.png', fullPage: true });
    throw new Error('checkout did not complete: ' + (problems.join(' | ') || 'no visible errors'));
  }
  await page.waitForLoadState('load');
  return page.url();
}

(async () => {
  const browser = await chromium.launch();
  const ctx = await browser.newContext({ viewport: { width: 1440, height: 900 } });
  const page = await ctx.newPage();
  page.on('dialog', (d) => { log('dialog:', d.message()); d.dismiss(); });
  const errors = [];
  page.on('pageerror', (e) => errors.push(e.message));
  await go(page, '/'); // Playground logs in on first request.

  // 1. Transfer order.
  await addToCart(page, 'chicken-jerky-strips', 1);
  await addToCart(page, 'lamb-pumpkin-fresh-meal', 0); // pre-order item
  const transferUrl = await checkout(page, 'bacs');
  log('transfer order:', transferUrl.replace(base, ''));
  log('  heading:', (await page.textContent('.wp-block-woocommerce-order-confirmation-status h1')).trim());
  log('  steps:', (await page.$$eval('.yt-steps li', (l) => l.map((x) => x.className + ':' + x.querySelector('.yt-steps__title').textContent).join(' | '))));
  log('  preorder note:', await page.$eval('.yt-next__preorder', (e) => e.textContent.trim()).catch(() => 'none'));
  log('  guide:', await page.$eval('.yt-guide-card__title', (e) => e.textContent.trim()).catch(() => 'none'));
  await page.screenshot({ path: out + '/1-transfer-waiting.png', fullPage: true });

  // 2. Upload the slip.
  await page.setInputFiles('#yt-slip-file', slip);
  await page.waitForTimeout(500);
  await Promise.all([page.waitForURL(/yt-slip=/, { timeout: 180000 }), page.click('.yt-slip__submit', { noWaitAfter: true })]);
  await page.waitForLoadState('load');
  log('  after upload:', page.url().replace(base, '').replace(/key=[^&]+/, 'key=…'));
  log('  notice:', await page.$eval('.yt-slip__notice', (e) => e.textContent.trim()).catch(() => 'none'));
  log('  heading:', (await page.textContent('.wp-block-woocommerce-order-confirmation-status h1')).trim());
  await page.screenshot({ path: out + '/2-transfer-checking.png', fullPage: true });

  // 3. Staff: orders list and the slip box, then mark paid.
  const orderId = (transferUrl.match(/order-received\/(\d+)/) || [])[1];
  await go(page, '/wp-admin/admin.php?page=wc-orders');
  const hpos = !(await page.$('body.wp-die-message, #error-page')) && (await page.$('table.wc-orders-list-table, .wc-orders-list-table'));
  if (!hpos) { await go(page, '/wp-admin/edit.php?post_type=shop_order'); }
  await page.screenshot({ path: out + '/3-admin-orders.png', fullPage: false });
  log('  slip column:', await page.$$eval('td.column-yt_slip', (c) => c.map((x) => x.textContent.trim() || '–').join(', ')).catch(() => 'n/a'));
  await go(page, hpos ? '/wp-admin/admin.php?page=wc-orders&action=edit&id=' + orderId : '/wp-admin/post.php?action=edit&post=' + orderId);
  await page.screenshot({ path: out + '/4-admin-order-slip.png', fullPage: false });
  const mark = await page.$('#yt-slip-upload a.button-primary');
  log('  mark-paid button:', !!mark);
  if (mark) { await Promise.all([page.waitForNavigation({ waitUntil: 'load', timeout: 180000 }), mark.click({ noWaitAfter: true })]); }
  log('  status after:', await page.$eval('#order_status', (s) => s.value).catch(() => 'n/a'));
  await page.goto(transferUrl, { waitUntil: 'load' });
  log('  customer heading now:', (await page.textContent('.wp-block-woocommerce-order-confirmation-status h1')).trim());
  await page.screenshot({ path: out + '/5-paid.png', fullPage: true });

  // 4. Cash on delivery.
  await addToCart(page, 'big-dog-chew-box');
  const codUrl = await checkout(page, 'cod');
  log('cod order:', codUrl.replace(base, '').replace(/key=[^&]+/, 'key=…'));
  log('  heading:', (await page.textContent('.wp-block-woocommerce-order-confirmation-status h1')).trim());
  log('  steps:', (await page.$$eval('.yt-steps li', (l) => l.map((x) => x.className + ':' + x.querySelector('.yt-steps__title').textContent).join(' | '))));
  await page.screenshot({ path: out + '/6-cod.png', fullPage: true });

  // 5. Sold out: notify me.
  await go(page, '/product/beef-liver-crunch/');
  log('notify form:', !!(await page.$('#yt-notify form')));
  await page.fill('#yt-notify-email', 'e2e@example.com');
  await page.check('.yt-notify__consent input');
  await Promise.all([page.waitForURL(/yt-notify=/, { timeout: 180000 }), page.click('.yt-notify__form button[type=submit]', { noWaitAfter: true })]);
  log('  result:', await page.$eval('.yt-notify__done', (e) => e.textContent.trim()).catch(() => 'no confirmation'));
  await page.screenshot({ path: out + '/7-notify.png', fullPage: false });

  log('page errors:', errors.length ? errors.join(' / ') : 'none');
  await browser.close();
})().catch((e) => { console.error('E2E FAILED:', e.message); process.exit(1); });
