"""Build theme/yummy-treats/languages/{th.po,th.mo,woocommerce-th.po,woocommerce-th.mo} from seed/translations-th.php.
Usage: php -r 'echo json_encode(include "theme/yummy-treats/seed/translations-th.php");' | python3 -I tools/make-mo.py theme/yummy-treats/languages
WordPress loads th.mo for the theme on /th/ pages (TranslatePress switches the locale), so gettext strings cost no database lookups."""
import json, struct, sys, os

out_dir = sys.argv[1]
d = json.load(sys.stdin)
theme = dict(d['regular'])
woo = {}
for dom, orig, th in d['gettext']:
    (theme if dom == 'yummy-treats' else woo)[orig] = th

def write(domain_file, entries, project):
    entries = {k: v for k, v in entries.items() if k and v and k != v}
    header = ('Project-Id-Version: %s\nMIME-Version: 1.0\nContent-Type: text/plain; charset=UTF-8\n'
              'Content-Transfer-Encoding: 8bit\nLanguage: th\nPlural-Forms: nplurals=1; plural=0;\nX-Generator: tools/make-mo.py\n') % project
    items = [('', header)] + sorted(entries.items())
    # .po (for humans and future editing)
    with open(os.path.join(out_dir, domain_file + '.po'), 'w', encoding='utf-8') as f:
        f.write('msgid ""\nmsgstr ""\n' + ''.join('"%s\\n"\n' % l for l in header.strip().split('\n')) + '\n')
        for k, v in items[1:]:
            esc = lambda s: s.replace('\\', '\\\\').replace('"', '\\"').replace('\n', '\\n')
            f.write('msgid "%s"\nmsgstr "%s"\n\n' % (esc(k), esc(v)))
    # .mo (binary, little-endian, no hash table)
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
    with open(os.path.join(out_dir, domain_file + '.mo'), 'wb') as f:
        f.write(mo)
    print(domain_file, len(entries), 'strings')

os.makedirs(out_dir, exist_ok=True)
write('th', theme, 'Yummy Treats! theme')
write('woocommerce-th', woo, 'Yummy Treats! WooCommerce overrides')
