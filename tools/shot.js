// node tools/shot.js <path> <out.png> [width]
const { chromium } = require('playwright');
(async () => {
  const [p = '/', out = '/tmp/shots/out.png', w = '1440'] = process.argv.slice(2);
  const browser = await chromium.launch();
  const ctx = await browser.newContext({ viewport: { width: parseInt(w, 10), height: 900 }, deviceScaleFactor: 1 });
  const page = await ctx.newPage();
  const errors = [];
  page.on('console', m => { if (m.type() === 'error') errors.push(m.text()); });
  page.on('pageerror', e => errors.push('PAGEERROR ' + e.message));
  await page.goto('http://127.0.0.1:9400/', { waitUntil: 'networkidle', timeout: 180000 }); // login redirect
  await page.goto('http://127.0.0.1:9400' + p, { waitUntil: 'networkidle', timeout: 180000 });
  await page.evaluate(() => document.querySelectorAll('.yt-reveal, .yt-reveal-stagger').forEach(e => e.classList.add('is-in')));
  await page.evaluate(async () => { for (let y = 0; y < document.body.scrollHeight; y += 600) { window.scrollTo(0, y); await new Promise(r => setTimeout(r, 120)); } window.scrollTo(0, 0); });
  await page.waitForTimeout(1200);
  await page.screenshot({ path: out, fullPage: true });
  console.log('saved', out, 'errors:', errors.length ? errors.join('\n') : 'none');
  await browser.close();
})();
