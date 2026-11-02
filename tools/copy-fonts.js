// Copies the three font families from the Fontsource npm packages into the theme (OFL-1.1 licensed).
const fs = require('fs'), path = require('path');
const out = path.join(__dirname, '..', 'theme', 'yummy-treats', 'assets', 'fonts');
fs.mkdirSync(out, { recursive: true });
const files = [
  ['@fontsource-variable/bricolage-grotesque/files/bricolage-grotesque-latin-wght-normal.woff2', 'bricolage-grotesque-latin-wght-normal.woff2'],
  ['@fontsource-variable/space-grotesk/files/space-grotesk-latin-wght-normal.woff2', 'space-grotesk-latin-wght-normal.woff2'],
  ...[400, 700, 800].flatMap(w => [
    [`@fontsource/prompt/files/prompt-thai-${w}-normal.woff2`, `prompt-thai-${w}-normal.woff2`],
    [`@fontsource/prompt/files/prompt-latin-${w}-normal.woff2`, `prompt-latin-${w}-normal.woff2`],
  ]),
];
for (const [src, dest] of files) {
  fs.copyFileSync(require.resolve(src), path.join(out, dest));
  console.log('copied', dest);
}
