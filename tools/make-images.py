"""Generate placeholder product images (front + back) as flat PNGs. Run once; output committed with the theme."""
import json, sys
from PIL import Image, ImageDraw, ImageFont

SRC, OUT = sys.argv[1], sys.argv[2]
INK = (0, 0, 0)
BOLD = ImageFont.truetype('/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf', 64)
SMALL = ImageFont.truetype('/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf', 34)
TINY = ImageFont.truetype('/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf', 28)

def hexrgb(h): return tuple(int(h[i:i+2], 16) for i in (1, 3, 5))

def wrap(draw, text, font, width):
    words, lines, cur = text.split(), [], ''
    for w in words:
        t = (cur + ' ' + w).strip()
        if draw.textlength(t, font=font) <= width: cur = t
        else: lines.append(cur); cur = w
    if cur: lines.append(cur)
    return lines

def bag(p, back=False):
    S = 1000
    im = Image.new('RGB', (S, S), hexrgb(p['tint']))
    d = ImageDraw.Draw(im)
    fill = hexrgb(p['fill'])
    # bag body
    d.rounded_rectangle((250, 170, 750, 880), radius=48, fill=fill, outline=INK, width=8)
    # crimped top
    d.rounded_rectangle((230, 130, 770, 215), radius=20, fill=(255, 255, 255), outline=INK, width=8)
    for x in range(260, 760, 36):
        d.line((x, 140, x, 205), fill=INK, width=4)
    # label
    d.rounded_rectangle((300, 300, 700, 700), radius=36, fill=(255, 255, 255), outline=INK, width=8)
    if not back:
        d.text((330, 330), 'YUMMY', font=SMALL, fill=hexrgb('#FF5CC8'))
        d.text((330, 372), 'TREATS!', font=SMALL, fill=INK)
        font, lh = BOLD, 70
        lines = wrap(d, p['name'], font, 340)
        if len(lines) > 2:
            font = ImageFont.truetype('/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf', 46); lh = 52
            lines = wrap(d, p['name'], font, 340)
        y = 430
        for line in lines[:4]:
            d.text((330, y), line, font=font, fill=INK); y += lh
        d.text((330, 650), p['pet'].upper() + ' · ' + p['type'].upper(), font=TINY, fill=(61, 61, 61))
    else:
        d.text((330, 330), 'INGREDIENTS', font=SMALL, fill=INK)
        y = 390
        for line in wrap(d, '[Ingredient list and guaranteed analysis go here — supplied by the client]', TINY, 340):
            d.text((330, y), line, font=TINY, fill=(61, 61, 61)); y += 36
        d.text((330, 600), '[Back of bag]', font=SMALL, fill=INK)
    # sticker
    d.ellipse((640, 740, 790, 890), fill=hexrgb('#FDFF00'), outline=INK, width=8)
    st = ImageFont.truetype('/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf', 22)
    d.text((715, 800), 'MADE IN', font=st, fill=INK, anchor='mm')
    d.text((715, 830), 'THAILAND', font=st, fill=INK, anchor='mm')
    return im

data = json.load(open(SRC))
for p in data:
    bag(p).save(f"{OUT}/{p['sku']}-1.png", optimize=True)
    bag(p, True).save(f"{OUT}/{p['sku']}-2.png", optimize=True)
print('wrote', len(data) * 2)
