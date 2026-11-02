// Finds English left on the Thai pages: visible text, placeholders and button labels with no Thai in them.
// Usage: node tools/th-gaps.js [baseUrl] [extra paths…]   or   node tools/th-gaps.js baseUrl --only path…
// Product names, [placeholders], prices and the brand stay English on purpose and are skipped.
const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

const base = process.argv[2] || 'http://127.0.0.1:9400';
const only = process.argv[3] === '--only';
const extra = process.argv.slice(only ? 4 : 3);
const products = JSON.parse(fs.readFileSync(path.join(__dirname, '../theme/yummy-treats/seed/products.json'), 'utf8')).map((p) => p.name);
const keep = new Set([...products, 'Yummy Treats!', 'Yummy Treats', 'EN', 'LINE', 'PromptPay', 'PDPA', 'Visa', 'Mastercard', 'JCB']);

const pages = only ? ['[fill cart]', ...extra] : [
  '/th/', '/th/shop/', '/th/product/chicken-jerky-strips/', '/th/product/lamb-pumpkin-fresh-meal/', '/th/product/beef-liver-crunch/',
  '/th/guides/', '/th/how-much-should-a-condo-dog-eat/', '/th/switching-your-cat-to-a-new-food/', '/th/treats-and-training-how-many-is-too-many/',
  '/th/help/', '/th/stockists/', '/th/nothing-here/', '/th/?s=zzzz&post_type=product', '/th/cart/', '[fill cart]', '/th/cart/', '/th/checkout/',
  ...extra,
];

const collect = () => {
  const out = [];
  const thai = /[฀-๿]/;
  const latin = /[A-Za-z]{2,}/;
  const visible = (el) => {
    if (!el || el.closest('#wpadminbar, script, style, noscript, template, [aria-hidden="true"]')) return false;
    const s = getComputedStyle(el);
    return s.display !== 'none' && s.visibility !== 'hidden' && el.getClientRects().length > 0;
  };
  const walker = document.createTreeWalker(document.body, NodeFilter.SHOW_TEXT);
  for (let n = walker.nextNode(); n; n = walker.nextNode()) {
    const t = n.textContent.replace(/\s+/g, ' ').trim();
    if (t && latin.test(t) && !thai.test(t) && visible(n.parentElement)) out.push(t);
  }
  document.querySelectorAll('input[placeholder], textarea[placeholder], [aria-label]').forEach((el) => {
    if (el.closest('#wpadminbar')) return;
    for (const a of ['placeholder', 'aria-label']) {
      const t = (el.getAttribute(a) || '').trim();
      if (t && latin.test(t) && !thai.test(t)) out.push(`@${a}: ${t}`);
    }
  });
  return out;
};

(async () => {
  const browser = await chromium.launch();
  const page = await (await browser.newContext({ viewport: { width: 1440, height: 900 } })).newPage();
  await page.goto(base + '/', { waitUntil: 'load', timeout: 180000 });
  const seen = new Map();
  for (const p of pages) {
    if (p === '[fill cart]') {
      await page.goto(base + '/th/product/chicken-jerky-strips/', { waitUntil: 'load', timeout: 180000 });
      await page.click('.yt-variations__btn');
      await page.waitForSelector('.single_add_to_cart_button:not(.disabled)');
      await Promise.all([page.waitForNavigation({ waitUntil: 'load', timeout: 120000 }), page.click('.single_add_to_cart_button')]);
      continue;
    }
    await page.goto(base + p, { waitUntil: 'load', timeout: 180000 });
    await page.waitForTimeout(/cart|checkout|order-received/.test(p) ? 4000 : 800);
    const found = [...new Set(await page.evaluate(collect))].filter((t) => !keep.has(t) && !/^\[.*\]$/.test(t) && !/^[฿\d.,\s–-]+$/.test(t));
    const fresh = found.filter((t) => !seen.has(t));
    found.forEach((t) => seen.set(t, seen.get(t) || p));
    console.log(`\n## ${p}  (${found.length} English, ${fresh.length} new)`);
    fresh.forEach((t) => console.log('  ' + t.slice(0, 160)));
  }
  await browser.close();
})();
