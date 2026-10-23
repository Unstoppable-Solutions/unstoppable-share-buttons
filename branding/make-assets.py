#!/usr/bin/env python3
"""
Generate the WordPress.org directory assets for Unstoppable Share Buttons.

Writes into ../.wordpress-org/ :
    banner-772x250.png
    banner-1544x500.png
    icon-128x128.png
    icon-256x256.png

Design philosophy: see DESIGN-PHILOSOPHY.md ("Quiet Propagation").

The banner's diagram carries exactly 48 stations, one per network the plugin
supports. That count is load-bearing notation, not decoration -- if the network
count ever changes, change STATIONS to match or remove the caption.

Run:  python3 make-assets.py
"""

import math
import os

from PIL import Image, ImageDraw, ImageFont

# --------------------------------------------------------------------------
# Palette. Drawn from the plugin's own stylesheets so the listing and the
# product agree: gold is the Unstoppable brand mark (#f0b849), the darks and
# slates are frontend.css's dark scheme.
# --------------------------------------------------------------------------
GOLD = (240, 184, 73)
WHITE = (255, 255, 255)
INK_TOP = (18, 23, 31)
INK_BOT = (11, 14, 19)
SLATE = (58, 71, 92)
MUTED = (120, 133, 152)
FAINT = (74, 85, 104)

HERE = os.path.dirname(os.path.abspath(__file__))
OUT = os.path.abspath(os.path.join(HERE, os.pardir, ".wordpress-org"))
FONTS = (
    "/root/.claude/skills/synced/"
    "6548aba4-c3ca-4b88-8c5f-bdd1a619b3f9_2e114b27-e4c4-4061-833d-5c8d48eafb45/"
    "canvas-design/canvas-fonts"
)

DISPLAY = os.path.join(FONTS, "InstrumentSans-Bold.ttf")
MONO = os.path.join(FONTS, "GeistMono-Regular.ttf")

# Arc radius, station count, node side, alpha, angular half-span (degrees).
# Intervals widen as they travel outward; the eye reads that as acceleration.
ARCS = [
    (48, 6, 13.0, 255, 72),
    (86, 10, 11.0, 220, 58),
    (122, 14, 9.2, 176, 45),
    (160, 18, 7.6, 128, 36),
]
STATIONS = sum(a[1] for a in ARCS)          # 48
LIT = {(0, 2), (1, 4), (2, 1), (2, 11), (3, 8)}   # the handful carrying metal


def tracked(draw, xy, text, font, fill, tracking=0.0, anchor_y="ls"):
    """Draw text with letterspacing. PIL has no tracking, so step per glyph."""
    x, y = xy
    for ch in text:
        draw.text((x, y), ch, font=font, fill=fill, anchor="l" + anchor_y[1])
        x += draw.textlength(ch, font=font) + tracking
    return x


def tracked_width(draw, text, font, tracking=0.0):
    if not text:
        return 0.0
    return sum(draw.textlength(c, font=font) for c in text) + tracking * (len(text) - 1)


def ground(size, top, bottom):
    """A gradient so smooth its transitions cannot be located: build it tiny
    and let the resampler do the interpolation."""
    seed = Image.new("RGB", (1, 16))
    px = seed.load()
    for i in range(16):
        t = i / 15.0
        px[0, i] = tuple(round(top[c] + (bottom[c] - top[c]) * t) for c in range(3))
    return seed.resize(size, Image.BICUBIC)


def rounded(draw, cx, cy, side, radius, fill):
    h = side / 2.0
    draw.rounded_rectangle([cx - h, cy - h, cx + h, cy + h], radius=radius, fill=fill)


def banner(scale=4):
    """Master banner at `scale`x of 772x250."""
    W, H = 772 * scale, 250 * scale
    img = ground((W, H), INK_TOP, INK_BOT).convert("RGB")
    layer = Image.new("RGBA", (W, H), (0, 0, 0, 0))
    d = ImageDraw.Draw(layer)

    ox, oy = 580 * scale, 125 * scale

    # --- apparatus: the arcs themselves, barely there ---------------------
    # Openness is expressed by dissipation, not by clipping: every station
    # stays inside the frame, and the system fades instead of being cut.
    for r, _, _, alpha, span in ARCS:
        R = r * scale
        d.arc(
            [ox - R, oy - R, ox + R, oy + R],
            start=-span, end=span,
            fill=(74, 85, 104, round(alpha * 0.26)),
            width=max(1, round(scale * 0.5)),
        )

    # --- emission halo at the origin --------------------------------------
    halo = Image.new("L", (1, 1))
    hr = int(58 * scale)
    halo = Image.new("L", (hr * 2, hr * 2), 0)
    hd = ImageDraw.Draw(halo)
    for i in range(hr, 0, -1):
        a = int(26 * (1 - i / hr) ** 2.1)
        hd.ellipse([hr - i, hr - i, hr + i, hr + i], fill=a)
    glow = Image.new("RGBA", (hr * 2, hr * 2), GOLD + (0,))
    glow.putalpha(halo)
    layer.alpha_composite(glow, (ox - hr, oy - hr))

    # --- 48 stations ------------------------------------------------------
    for ai, (r, n, side, alpha, span) in enumerate(ARCS):
        R = r * scale
        s = side * scale
        for i in range(n):
            t = 0.5 if n == 1 else i / (n - 1.0)
            ang = math.radians(-span + 2 * span * t)
            x = ox + R * math.cos(ang)
            y = oy + R * math.sin(ang)
            lit = (ai, i) in LIT
            col = GOLD + (alpha,) if lit else SLATE + (alpha,)
            rounded(d, x, y, s, max(1.0, s * 0.24), col)

    # --- the origin: one lit station, larger ------------------------------
    rounded(d, ox, oy, 19 * scale, 19 * scale * 0.24, GOLD + (255,))

    img = Image.alpha_composite(img.convert("RGBA"), layer)
    d = ImageDraw.Draw(img)

    # --- language ---------------------------------------------------------
    f_word = ImageFont.truetype(DISPLAY, round(33 * scale))
    f_eyebrow = ImageFont.truetype(MONO, round(8.0 * scale))
    f_spec = ImageFont.truetype(MONO, round(8.6 * scale))
    f_note = ImageFont.truetype(MONO, round(6.6 * scale))

    L = 56 * scale                      # the single axis everything flushes to

    tracked(d, (L, 57 * scale), "FOR WORDPRESS", f_eyebrow, MUTED,
            tracking=2.6 * scale)

    d.text((L, 78 * scale), "UNSTOPPABLE", font=f_word, fill=GOLD, anchor="la")
    d.text((L, 112 * scale), "SHARE BUTTONS", font=f_word, fill=WHITE, anchor="la")

    d.line([L, 164 * scale, 268 * scale, 164 * scale], fill=FAINT,
           width=max(1, round(scale * 0.5)))

    tracked(d, (L, 185 * scale),
            "48 NETWORKS / FOUR PLACEMENTS / NO THIRD-PARTY SCRIPTS",
            f_spec, MUTED, tracking=0.55 * scale)

    tracked(d, (L, 219 * scale), "FIG. 01", f_note, FAINT, tracking=1.5 * scale)
    tracked(d, (116 * scale, 219 * scale),
            "PROPAGATION WITHOUT RETURN / %d STATIONS PLOTTED" % STATIONS,
            f_note, (70, 81, 98), tracking=1.0 * scale)

    # --- containment check: nothing may touch an edge it wasn't built to --
    probe = ImageDraw.Draw(Image.new("RGB", (10, 10)))
    for txt, font, tr, x in (
        ("48 NETWORKS / FOUR PLACEMENTS / NO THIRD-PARTY SCRIPTS", f_spec, 0.55 * scale, L),
        ("PROPAGATION WITHOUT RETURN / %d STATIONS PLOTTED" % STATIONS, f_note, 1.0 * scale, 116 * scale),
    ):
        end = x + tracked_width(probe, txt, font, tr)
        assert end < 430 * scale, "text runs into the diagram: %.0f" % (end / scale)
    for word in ("UNSTOPPABLE", "SHARE BUTTONS"):
        assert L + d.textlength(word, font=f_word) < 430 * scale, word

    return img.convert("RGB")


def icon(scale=8):
    """Master icon at `scale`x of 128x128. Must survive 32px in search."""
    S = 128 * scale
    img = Image.new("RGBA", (S, S), (0, 0, 0, 0))
    d = ImageDraw.Draw(img)

    # The button form itself, carrying the plugin's own corner radius.
    tile = ground((S, S), (24, 30, 40), (13, 17, 23)).convert("RGBA")
    mask = Image.new("L", (S, S), 0)
    ImageDraw.Draw(mask).rounded_rectangle([0, 0, S - 1, S - 1],
                                           radius=round(26 * scale), fill=255)
    img.paste(tile, (0, 0), mask)
    d = ImageDraw.Draw(img)

    # One node sending to two. Three marks and two paths is the most detail
    # that survives a 32px render; anything finer turns to mud.
    # Nudged left of geometric centre: the two right-hand nodes carry more
    # mass, so centring on the maths reads as leaning right.
    a = (38 * scale, 64 * scale)
    b = (88 * scale, 38 * scale)
    c = (88 * scale, 90 * scale)
    w = round(8.6 * scale)

    d.line([a, b], fill=GOLD, width=w)
    d.line([a, c], fill=GOLD, width=w)
    for (x, y), r in ((a, 14.5), (b, 13.0), (c, 13.0)):
        rr = r * scale
        d.ellipse([x - rr, y - rr, x + rr, y + rr], fill=GOLD)

    return img


def main():
    os.makedirs(OUT, exist_ok=True)

    b = banner(scale=4)
    for w, h in ((1544, 500), (772, 250)):
        b.resize((w, h), Image.LANCZOS).save(
            os.path.join(OUT, "banner-%dx%d.png" % (w, h)), optimize=True)

    ic = icon(scale=8)
    for n in (256, 128):
        ic.resize((n, n), Image.LANCZOS).save(
            os.path.join(OUT, "icon-%dx%d.png" % (n, n)), optimize=True)

    for f in sorted(os.listdir(OUT)):
        p = os.path.join(OUT, f)
        with Image.open(p) as im:
            print("%-24s %-9s %-5s %6.1f KB"
                  % (f, "x".join(map(str, im.size)), im.mode,
                     os.path.getsize(p) / 1024.0))


if __name__ == "__main__":
    main()
