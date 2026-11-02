// Accessibility check with axe-core (WCAG 2.2 A/AA rules) on the main pages, desktop and phone, English and Thai.
// Usage: node tools/a11y.js [baseUrl]   (needs: npm i --no-save axe-core)
const { chromium, devices } = require('playwright');
const axeSource = require('fs').readFileSync(require.resolve('axe-core/axe.min.js'), 'utf8');
const base = process.argv[2] || 'http://127.0.0.1:9400';
const pages = ['/', '/shop/', '/product/chicken-jerky-strips/', '/product/beef-liver-crunch/', '/cart/', '/checkout/', '/how-much-should-a-condo-dog-eat/', '/guides/', '/stockists/', '/help/', '/th/', '/th/shop/', '/nothing-here/'];

(async () => {
  const browser = await chromium.launch();
  const totals = {};
  for (const [label, opts] of [['desktop', { viewport: { width: 1440, height: 900 } }], ['phone', { ...devices['iPhone 13'], deviceScaleFactor: 1 }]]) {
    const ctx = await browser.newContext(opts);
    const page = await ctx.newPage();
    await page.goto(base + '/', { waitUntil: 'load', timeout: 180000 });
    // Put something in the cart so cart and checkout render their full forms.
    await page.goto(base + '/product/chicken-jerky-strips/', { waitUntil: 'load', timeout: 180000 });
    await page.click('.yt-variations__btn');
    await page.waitForSelector('.single_add_to_cart_button:not(.disabled)');
    await Promise.all([page.waitForNavigation({ waitUntil: 'load', timeout: 120000 }), page.click('.single_add_to_cart_button')]);
    for (const p of pages) {
      await page.goto(base + p, { waitUntil: 'load', timeout: 180000 });
      await page.evaluate(() => document.querySelectorAll('.yt-reveal, .yt-reveal-stagger').forEach((e) => e.classList.add('is-in')));
      await page.waitForTimeout(p.includes('checkout') || p.includes('cart') ? 4000 : 1200);
      await page.addScriptTag({ content: axeSource });
      const res = await page.evaluate(async () => {
        const r = await window.axe.run(document, {
          runOnly: { type: 'tag', values: ['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa', 'wcag22aa'] },
          exclude: [['#wpadminbar']],
        });
        return r.violations.map((v) => ({ id: v.id, impact: v.impact, n: v.nodes.length, help: v.help, targets: v.nodes.slice(0, 3).map((n) => n.target.join(' ') + ' :: ' + (n.failureSummary || '').split('\n').slice(1, 2).join('').trim().slice(0, 120)) }));
      });
      for (const v of res) {
        const key = v.id;
        totals[key] = totals[key] || { impact: v.impact, help: v.help, where: [] };
        totals[key].where.push(`${label} ${p} (${v.n}): ${v.targets.join(' | ')}`);
      }
      console.log(label, p, res.length ? res.map((v) => v.id + '×' + v.n).join(', ') : 'clean');
    }
    await ctx.close();
  }
  console.log('\n=== by rule ===');
  for (const [id, t] of Object.entries(totals)) {
    console.log(`\n[${t.impact}] ${id}: ${t.help}`);
    t.where.slice(0, 6).forEach((w) => console.log('   ' + w.slice(0, 400)));
  }
  await browser.close();
})().catch((e) => { console.error('A11Y FAILED:', e.message); process.exit(1); });
