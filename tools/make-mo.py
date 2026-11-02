"""Build the Thai translation files from theme/yummy-treats/seed/translations-th.php (one dictionary, several outputs).

Usage (or: npm run mo):
  php -r 'echo json_encode(include "theme/yummy-treats/seed/translations-th.php");' \
    | python3 -I tools/make-mo.py theme/yummy-treats/languages plugins/yt-slip-upload/languages

Writes, as .po (for people) and .mo (for WordPress):
  theme languages/th               the theme's own strings (domain yummy-treats) plus the "regular" page text
  theme languages/woocommerce-th   WooCommerce overrides, plus woocommerce-th.json for its JavaScript (block cart/checkout)
  theme languages/default-th       WordPress core overrides (pagination, skip link, search title)
  plugin languages/yt-slip-upload-th
Gettext rows are [domain, english, thai] with an optional 4th item (context) and 5th (plural english).
Thai has one plural form, so a plural row needs just one translation."""
import json, struct, sys, os

theme_dir = sys.argv[1]
plugin_dir = sys.argv[2] if len(sys.argv) > 2 else None
d = json.load(sys.stdin)

# key -> (context, singular, plural, thai) per output
outputs = {'th': {}, 'woocommerce-th': {}, 'default-th': {}, 'yt-slip-upload-th': {}}
for orig, th in d['regular'].items():
    outputs['th'][orig] = (None, orig, None, th)
for row in d['gettext']:
    dom, orig, th = row[0], row[1], row[2]
    ctx = row[3] if len(row) > 3 else None
    plural = row[4] if len(row) > 4 else None
    name = {'yummy-treats': 'th', 'woocommerce': 'woocommerce-th', 'default': 'default-th', 'yt-slip-upload': 'yt-slip-upload-th'}.get(dom)
    if name is None:
        sys.exit('unknown text domain: %s' % dom)
    outputs[name][(ctx or '') + '\x04' + orig] = (ctx, orig, plural, th)

def mo_key(ctx, orig, plural):
    return ((ctx + '\x04') if ctx else '') + orig + (('\x00' + plural) if plural else '')

def write(out_dir, name, entries, project):
    entries = {k: v for k, v in entries.items() if v[1] and v[3] and v[1] != v[3]}
    header = ('Project-Id-Version: %s\nMIME-Version: 1.0\nContent-Type: text/plain; charset=UTF-8\n'
              'Content-Transfer-Encoding: 8bit\nLanguage: th\nPlural-Forms: nplurals=1; plural=0;\nX-Generator: tools/make-mo.py\n') % project
    rows = sorted(entries.values(), key=lambda v: mo_key(*v[:3]))
    esc = lambda s: s.replace('\\', '\\\\').replace('"', '\\"').replace('\n', '\\n')
    with open(os.path.join(out_dir, name + '.po'), 'w', encoding='utf-8') as f:
        f.write('msgid ""\nmsgstr ""\n' + ''.join('"%s\\n"\n' % l for l in header.strip().split('\n')) + '\n')
        for ctx, orig, plural, th in rows:
            if ctx:
                f.write('msgctxt "%s"\n' % esc(ctx))
            f.write('msgid "%s"\n' % esc(orig))
            if plural:
                f.write('msgid_plural "%s"\nmsgstr[0] "%s"\n\n' % (esc(plural), esc(th)))
            else:
                f.write('msgstr "%s"\n\n' % esc(th))
    # .mo: little-endian, no hash table, keys sorted.
    items = [('', header)] + [(mo_key(c, o, p), t) for c, o, p, t in rows]
    items.sort(key=lambda kv: kv[0].encode('utf-8'))
    ids = b''; strs = b''; offsets = []
    for k, v in items:
        kb = k.encode('utf-8'); vb = v.encode('utf-8')
        offsets.append((len(ids), len(kb), len(strs), len(vb)))
        ids += kb + b'\0'; strs += vb + b'\0'
    n = len(items)
    keystart = 7 * 4 + 16 * n
    valuestart = keystart + len(ids)
    koffsets = []; voffsets = []
    for o1, l1, o2, l2 in offsets:
        koffsets += [l1, o1 + keystart]; voffsets += [l2, o2 + valuestart]
    mo = struct.pack('Iiiiiii', 0x950412de, 0, n, 7 * 4, 7 * 4 + n * 8, 0, 0)
    mo += struct.pack('%di' % len(koffsets), *koffsets) + struct.pack('%di' % len(voffsets), *voffsets) + ids + strs
    with open(os.path.join(out_dir, name + '.mo'), 'wb') as f:
        f.write(mo)
    print(os.path.join(out_dir, name + '.mo'), len(rows), 'strings')
    return rows

def write_js(out_dir, name, rows, domain):
    """Locale data for wp.i18n.setLocaleData(): {"": {header}, "english": ["thai"], "ctx\u0004english": ["thai"]}.
    The header keeps English plural rules, so a WooCommerce plural we have not translated still reads "13 items"
    rather than "13 item"; plurals we do translate carry the Thai in both forms."""
    data = {'': {'domain': domain, 'lang': 'th', 'plural_forms': 'nplurals=2; plural=(n != 1);'}}
    for ctx, orig, plural, th in rows:
        data[((ctx + '\x04') if ctx else '') + orig] = [th, th] if plural else [th]
    with open(os.path.join(out_dir, name + '.json'), 'w', encoding='utf-8') as f:
        json.dump(data, f, ensure_ascii=False, separators=(',', ':'))
    print(os.path.join(out_dir, name + '.json'), len(rows), 'strings')

os.makedirs(theme_dir, exist_ok=True)
write(theme_dir, 'th', outputs['th'], 'Yummy Treats! theme')
woo = write(theme_dir, 'woocommerce-th', outputs['woocommerce-th'], 'Yummy Treats! WooCommerce overrides')
write_js(theme_dir, 'woocommerce-th', woo, 'woocommerce')
write(theme_dir, 'default-th', outputs['default-th'], 'Yummy Treats! WordPress overrides')
if plugin_dir:
    os.makedirs(plugin_dir, exist_ok=True)
    write(plugin_dir, 'yt-slip-upload-th', outputs['yt-slip-upload-th'], 'Bank Slip Upload for WooCommerce')
