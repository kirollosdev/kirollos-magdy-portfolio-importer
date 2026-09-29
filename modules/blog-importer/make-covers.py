# Renders a 1200x630 branded cover for each post in posts/manifest.json into posts/covers/.
# Run after build-posts.js:  python make-covers.py <fonts-dir>
import json
import os
import random
import sys

from PIL import Image, ImageDraw, ImageFilter, ImageFont

ROOT = os.path.dirname(os.path.abspath(__file__))
FONTS = sys.argv[1] if len(sys.argv) > 1 else os.path.join(ROOT, 'fonts')
W, H = 1200, 630
BG = (11, 11, 18)
PURPLE = (157, 0, 255)
PURPLE_DARK = (110, 0, 179)
GREEN = (98, 255, 0)
WHITE = (255, 255, 255)
MUTED = (160, 160, 185)


def font(name, size, weight=None):
    f = ImageFont.truetype(os.path.join(FONTS, name), size)
    if weight:
        f.set_variation_by_axes([weight, 100] if name.startswith('Roboto') else [weight])
    return f


def glow(base, xy, radius, color, alpha):
    layer = Image.new('RGBA', base.size, (0, 0, 0, 0))
    d = ImageDraw.Draw(layer)
    x, y = xy
    d.ellipse((x - radius, y - radius, x + radius, y + radius), fill=color + (alpha,))
    layer = layer.filter(ImageFilter.GaussianBlur(radius * 0.6))
    base.alpha_composite(layer)


def particles(base, rnd):
    layer = Image.new('RGBA', base.size, (0, 0, 0, 0))
    d = ImageDraw.Draw(layer)
    pts = [(rnd.randint(0, W), rnd.randint(0, H)) for _ in range(46)]
    for i, a in enumerate(pts):
        for b in pts[i + 1:]:
            dist = ((a[0] - b[0]) ** 2 + (a[1] - b[1]) ** 2) ** 0.5
            if dist < 150:
                d.line([a, b], fill=PURPLE + (int(55 * (1 - dist / 150)),), width=1)
    for x, y in pts:
        r = rnd.choice([1.5, 2, 2.5])
        d.ellipse((x - r, y - r, x + r, y + r), fill=(200, 160, 255, 120))
    base.alpha_composite(layer)


def wrap(draw, text, f, max_w):
    words, lines, cur = text.split(), [], ''
    for w in words:
        test = (cur + ' ' + w).strip()
        if draw.textlength(test, font=f) <= max_w:
            cur = test
        else:
            lines.append(cur)
            cur = w
    lines.append(cur)
    return lines


def browser_card(base, rnd):
    # Frosted "website" mockup on the right, echoing the glassmorphism on the portfolio.
    x0, y0, x1, y1 = 830, 150, 1130, 470
    blurred = base.crop((x0, y0, x1, y1)).filter(ImageFilter.GaussianBlur(18))
    base.paste(blurred, (x0, y0))
    layer = Image.new('RGBA', base.size, (0, 0, 0, 0))
    d = ImageDraw.Draw(layer)
    d.rounded_rectangle((x0, y0, x1, y1), 22, fill=(255, 255, 255, 18), outline=(255, 255, 255, 60), width=2)
    d.line((x0, y0 + 44, x1, y0 + 44), fill=(255, 255, 255, 40), width=1)
    for i, c in enumerate([(255, 95, 87), (254, 188, 46), (40, 200, 64)]):
        d.ellipse((x0 + 20 + i * 22, y0 + 16, x0 + 32 + i * 22, y0 + 28), fill=c + (220,))
    d.rounded_rectangle((x0 + 24, y0 + 66, x1 - 24, y0 + 150), 12, fill=PURPLE + (70,))
    y = y0 + 172
    for _ in range(4):
        w = rnd.randint(140, 250)
        d.rounded_rectangle((x0 + 24, y, x0 + 24 + w, y + 12), 6, fill=(255, 255, 255, 55))
        y += 26
    d.rounded_rectangle((x0 + 24, y1 - 56, x0 + 140, y1 - 24), 16, fill=GREEN + (230,))
    base.alpha_composite(layer)


def render(item, out_dir):
    rnd = random.Random(item['slug'])
    img = Image.new('RGBA', (W, H), BG + (255,))
    glow(img, (W - 120 + rnd.randint(-40, 40), 60), 330, PURPLE, 150)
    glow(img, (80, H + 40), 280, PURPLE_DARK, 130)
    glow(img, (rnd.randint(500, 800), H - 40), 120, GREEN, 40)
    particles(img, rnd)
    browser_card(img, rnd)

    d = ImageDraw.Draw(img)
    left = 80

    chip_f = font('Roboto.ttf', 20, 600)
    label = item['category'].upper()
    tw = d.textlength(label, font=chip_f)
    d.rounded_rectangle((left, 92, left + tw + 36, 132), 20, outline=GREEN, width=2)
    d.text((left + 18, 112), label, font=chip_f, fill=GREEN, anchor='lm')

    max_w = 700
    for size in (68, 62, 56, 50, 46):
        title_f = font('Roboto.ttf', size, 800)
        lines = wrap(d, item['cover_title'], title_f, max_w)
        if len(lines) <= 3:
            break
    line_h = int(size * 1.15)
    block_h = line_h * len(lines)
    y = 170 + (270 - block_h) // 2
    for line in lines:
        d.text((left, y), line, font=title_f, fill=WHITE)
        y += line_h

    for i in range(140):
        t = i / 139
        c = tuple(int(PURPLE[k] + (GREEN[k] - PURPLE[k]) * t) for k in range(3))
        d.line((left + i, 488, left + i, 492), fill=c)

    name_f = font('Comfortaa.ttf', 28, 700)
    role_f = font('Roboto.ttf', 22, 400)
    d.text((left, 530), 'Kirollos Magdy', font=name_f, fill=WHITE, anchor='ls')
    nw = d.textlength('Kirollos Magdy', font=name_f)
    d.text((left + nw + 16, 530), 'WordPress Developer', font=role_f, fill=MUTED, anchor='ls')
    d.text((W - 70, 530), 'kirollosmagdy.com', font=role_f, fill=MUTED, anchor='rs')

    out = os.path.join(out_dir, item['slug'] + '.jpg')
    img.convert('RGB').save(out, 'JPEG', quality=86, optimize=True, progressive=True)
    return out


def main():
    manifest = json.load(open(os.path.join(ROOT, 'posts', 'manifest.json'), encoding='utf-8'))
    out_dir = os.path.join(ROOT, 'posts', 'covers')
    os.makedirs(out_dir, exist_ok=True)
    for f in os.listdir(out_dir):
        os.remove(os.path.join(out_dir, f))
    for item in manifest:
        path = render(item, out_dir)
        print(os.path.basename(path), os.path.getsize(path) // 1024, 'KB')


if __name__ == '__main__':
    main()
