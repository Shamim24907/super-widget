# Super Widget

Modular widget pack for Elementor with its own admin dashboard.
Requires WordPress 5.8+, PHP 7.4+, Elementor 3.5+. WooCommerce is optional (needed for the product source).

## Automatic updates (v2.6)

A plugin that is not on WordPress.org gets no update notices. Super Widget therefore checks one address of yours for a newer version, and WordPress then updates it like any other plugin (automatically, if you leave "Install new versions automatically" on).

**One-time setup**
1. Put the plugin in a GitHub repository (public).
2. In `super-widget.php` set `SUPER_WIDGET_UPDATE_SOURCE` to `'your-name/your-repo'` and build the zip. Every site you install that zip on already knows where to look. (Or fill it in per site: Super Widget > Settings > Updates.)

**Every time you change the plugin**
1. Raise the version in `super-widget.php` (both the header `Version:` and `SUPER_WIDGET_VERSION`).
2. Zip the folder so the zip contains one folder named `super-widget`.
3. On GitHub: Releases > Draft a new release > tag `v2.6.1` (same number as the version) > attach the zip > Publish.
4. Within about 12 hours every site shows the update and installs it. Super Widget > Tools > "Check for updates now" does it at once.

**Without GitHub:** host a JSON file (see `update-manifest.example.json`) and the zip on your own https server, and use the JSON link as the update source. Add the zip's `sha256` there and a download that does not match is refused.

Safety: the check runs only in the admin / WP-Cron, never for visitors; https only; the download must come from GitHub or from the same host as your JSON file; the request sends no site address; results are cached for 12 hours. A private repository is not supported (it needs a secret token on every site); use the JSON option with your own server for that.

Do not rely on this for security fixes alone: also keep Elementor, WordPress and PHP updated.

## Dashboard (Super Widget menu)

**v2.5:** widgets are grouped by category (Cards, Products, Testimonials, Timelines, Images & galleries). The Dashboard shows one tile per category; clicking it opens the Widgets tab on that category. Widgets tab has category chips (All / each category), search, per-category Enable / Disable and a sticky Save bar. A module picks its category with `'category' => 'cards'` in its `meta.php` (unknown slugs get their own group, no category means "Other").


| Tab | What it does |
| --- | --- |
| Dashboard | Stats, system status checks, quick actions |
| Widgets | Search, switch each widget on/off, enable/disable all |
| Settings | Disable all animations, global accent color, delete data on uninstall |
| Tools | Export / import settings (JSON), reset to defaults |
| Help | Getting started, how to add a widget, copyable system info |

Disabled widgets are not loaded at all (no PHP class, no CSS, no JS).

## Folder structure

```
super-widget/
├── super-widget.php                main file
├── uninstall.php
├── includes/
│   ├── class-plugin.php            dependency checks, bootstrap
│   ├── class-manager.php           widget discovery + Elementor registration + assets
│   ├── class-settings.php          one option, sanitizing, global CSS variables
│   └── class-admin.php             dashboard, tabs, export/import/reset
├── widgets/
│   └── card-grid/
│       ├── card-grid.php           class SPW_Card_Grid_Widget
│       ├── products.php            WooCommerce query + variant data
│       └── meta.php                title, description, icon, version (Widgets tab)
└── assets/
    ├── admin/                      dashboard CSS + JS
    ├── css/card-grid.css           handle: spw-card-grid
    └── js/card-grid.js             handle: spw-card-grid
```

## Adding a new widget (example: icon-box)

1. `widgets/icon-box/icon-box.php` with class `SPW_Icon_Box_Widget`
   (extends `\Elementor\Widget_Base`, name `spw-icon-box`, category `super-widget`).
2. Optional `widgets/icon-box/meta.php` returning `title`, `description`, `icon`
   (a dashicon class) and `version` for the Widgets tab.
3. Optional `assets/css/icon-box.css` and `assets/js/icon-box.js`, registered
   automatically as handle `spw-icon-box`. Return `array( 'spw-global', 'spw-icon-box' )`
   from `get_style_depends()` to also get the shared accent color variables.

Naming rule: folder `foo-bar` -> file `foo-bar.php` -> class `SPW_Foo_Bar_Widget`.

## Testimonial 3 widget (v1.4)

Review cards in a grid (Google-review style) with a summary bar. Plain CSS grid, no slider.

- Cards: avatar (photo, or an icon such as the Google "G"), name (optional link), date / small text, star rating (decimals allowed), quote icon and review text.
- Grid Layout (like the other grid widgets): Columns 1-4, column gap and row gap per device, equal height cards, quote icon beside or above the review, optional max lines.
- Summary bar: title, stars with optional text, button with link and icon, placed below or above the reviews. Layout per device: title left / stars center / button right, or stacked (centered or left).
- Style: section background, one Accent color, cards (background, border, radius, padding, normal / hover shadow), avatar, name, date, review, stars (size, color, gap), quote icon, bar and button (normal / hover).
- Animation: entrance (fade, slide, zoom) with delay between cards, hover lift. Respects the global "disable animations" setting and `prefers-reduced-motion`.
- Lightweight: one CSS file and a tiny reveal script. Stars are one CSS mask per rating, no icon font for them.

## Product Card 2 widget (v1.3)

A clean product grid: category pill on the image, serif title, price and a
full-width Add to Cart button (4 columns on desktop, 2 on tablet, 1 on mobile).

- Source: specific products, collection (category / tag) or custom cards. The WooCommerce query is shared with Card Grid.
- Badge: product category (default), sale label, same text on every card, or none.
- One **Accent color** (Style > Card) colors badge, price and button; each part can still be overridden.
- Columns and gaps per device, image shape / fit / padding, title lines (height is reserved so rows line up), button width / icon / hover colors.
- Hover: lift card and/or zoom image. Entrance animation with stagger. Both respect the global "disable animations" setting and `prefers-reduced-motion`.
- Variable products: the button sends the visitor to the product page to choose options.
- The title uses a Georgia-style serif by default (no web font is loaded). Pick any font in Style > Title > Typography.

## Premium Cards widget (v2.2)

**Slider navigation (v2.2):** choose Arrows + dots / Arrows / Dots / None. Arrows and pagination each get a position: top, middle or bottom x left, center or right (middle-center puts one arrow on each side of the cards), plus distance controls.

One card widget with **9 designs** (pick in Design & layout), as a grid or a slider.

- **Designs:** Blog (picture, gradient badge, author), Clean (picture, text, plain pill button), Post (inset picture, meta row, pill button), Overlay (text on the picture, round arrow in a notch), Icon over the picture, Centered icon + full-width bar button, Glass (icon, dark pill button), Soft tint (icon tile, big round arrow), Accent bar (icon circle, text link).
- **Per card:** title, text, badge (+ gradient colors), picture, built-in icon (16 line icons) or your own icon, author / date / photo, link (whole card), button text, accent color, card background.
- **Auto visibility:** every design shows the parts its look uses. Picture, icon, badge and author can each be set to Auto / Show / Hide. Text and button have switches. Button look: automatic, pill, bar, round arrow or text link.
- **Style:** columns per device, gap, alignment per device, radius, padding, background (color or gradient), border, shadow, minimum height, text lines, picture height / radius / fit / filters (also on hover), overlay color and strength, icon box / size / colors / radius / shadow, badge, author, title / text / button typography and colors (also on hover), arrow and dot colors.
- **Animation:** fade, fade up, zoom, blur-in, pop or flip entrance (speed + stagger), hover lift (distance), picture zoom (amount), icon pop / spin / wiggle, shine sweep. Respects the global "disable animations" setting and `prefers-reduced-motion`.
- **Grid or slider on every device:** the Layout control is responsive, so each device (desktop, tablet, mobile ...) can be a grid or a slider. Example: 3-column grid on desktop, one-card slider on mobile. Columns per device set how many cards show at once; "show a bit of the next card" (peek) hints that more is to the right.
- **Slider:** swipe, arrows, dots, keyboard, optional autoplay that pauses on hover. Arrows sit under the cards next to the dots (or on the sides). On devices that use a grid the arrows, dots and autoplay switch off by themselves.

## Collection Grid widget (v1.9)

Category / collection cards (a colored tile, a label and a cut-out picture), like the pet-shop reference.

- **Layouts:** *Collage*, *Normal grid* (1-6 columns per device) or *Slider* (swipe, arrows, dots, keyboard, optional autoplay that pauses on hover).
- **7 collage styles:** Classic (the reference), Mirror, Sides, Half + four, Banner + three, Stair, Magazine. Each repeats every 4-5 cards, so it works best with 5 / 10 / 15 cards. On phones every style becomes 2 columns.
- **Per card:** label, small text, badge, picture, link, card color + optional second color (gradient), label color and position (6 spots), picture position, **picture size 20-220 %**, move left/right and up/down, rotate, flip, fit; **card width (of 12 columns) and height (rows)**; "full width on mobile".
- **Style:** gap, row / card height per device, corner radius, border, shadow, picture spacing, gradient angle, dark fade behind the label, picture filter (normal + hover), label typography / color / background / padding / radius, small text and badge styling, hover shadow / tint / label color, arrow size / colors, dot colors.
- **Animation:** fade, fade up, zoom, blur-in or pop entrance with speed and stagger; hover lift (distance), picture zoom (amount), shine sweep, hover speed. Respects the global "disable animations" setting and `prefers-reduced-motion`.

## Timeline widget (v1.7)

Vertical or horizontal timeline, with or without pictures.

- **Items:** year / label, title, text, optional picture, optional link and marker icon. A step without a picture is shown as text only; "Show pictures" turns all pictures off.
- **Vertical:** sides alternate (left / right), all on the left, all on the right, or the line on the left edge. The layout can be different on desktop, tablet and mobile (default: single column on mobile).
- **Horizontal:** steps in a row (1-5 visible, responsive), text above / below / alternate, swipe, scroll or arrows for more steps.
- **Markers:** tick (like the reference), dot, ring or icon.
- **Design (v1.7):** *Cards* (default, premium: soft shadow, gradient year pill, big ghost year, accent bar), *Glass* (frosted) or *Classic* (the old flat look).
- **Premium motion:** "Reveal" entrance (card, picture and text animate in), spotlight on the current step (others dim), smooth line fill, marker pop + pulse, picture parallax (vertical).
- **Hover colors:** card background / border / accent bar / shadow / lift, marker color, year color, title color, second accent color for gradients.
- **Animation:** entrance (fade, fade up, slide from the side, zoom), line that fills while scrolling, hover (lift text / zoom picture). Respects the global "disable animations" setting and `prefers-reduced-motion`.
- **Style:** year and title typography (script-style title by default, no web font is loaded), colors, picture size / shape / border / radius / shadow, optional text box, line and marker sizes.

## Image Shapes widget (v1.1)

One image widget with ready-made shapes, split into **Single** and **Multi** (Content > Shape > Type).

- **23 single shapes:** arch, arch with floating labels, capsule + colour oval, two leaf shapes, drop, pebble, circle, circle with ring, soft square, wide pill, colour block behind, corner block, outline frame, tilted card + seal, circle / square with a diagonal slash (+ round arrow button), hexagon, diamond, octagon, cut corners, curved bottom, star.
- **8 collages (2-3 pictures):** big + framed small picture with arrow, two pictures + number badge, picture + info card + round picture, twin capsules + round badge, colour block + two pictures, big and small circle, two overlapping pictures, trio.
- Shapes are plain CSS (border-radius / clip-path); nothing is rasterised, so they scale with the widget. Add a new shape by adding one line to `shapes()` in `image-shapes.php` and one CSS block in `image-shapes.css`.
- Editable: width and alignment per device, box proportion per device, colours, outline thickness, block distance, shadow, borders, picture focus, CSS filters (normal + hover), badge / label colours and typography.
- Motion: hover (zoom, lift, tilt, spread), shine, entrance (7 types), floating small pictures. All respect the global "disable animations" setting and `prefers-reduced-motion`.
- Link on the picture, or on the round arrow button for shapes that have one.
- **Badges & extras (v1.1):** badge card, round arrow button (arrow / chevron / play / plus), round seal and floating labels. Each one is Automatic / Show / Hide, works on every shape, and has its own colours, size and position (centre X / Y). Labels are a list: add, remove, set text, tick icon and position per label.
- **Colour block & outline:** add, remove or restyle (background / gradient, border, radius, rotation, opacity). Plus a background behind the picture (shows through PNG cut-outs), a main picture border and small-picture borders.

## Image Mosaic widget (v1.5)

One image shown as a collage of shapes (each tile shows its own part of the same picture).

- Layouts: soft collage (like the reference), full puzzle, 3 x 3 puzzle, bento, staggered columns, wide top + 4, steps, or **Custom**.
- Custom: add as many shapes as you like; set Left / Top / Width / Height (percent of the mosaic) and a shape for each.
- Shapes: rounded, square, circle/oval, pill, arch, leaf, hexagon, diamond.
- Style: column gap + optional row gap, corner radius, border, shadow, tile background, max width, alignment, height, focus point of the crop.
- Entrance (fade / fade up / zoom, staggered) and hover pop; both respect the global "disable animations" setting and `prefers-reduced-motion`.

## Card Grid widget (v1.2)

Source (Products section):

- **Specific products**: pick WooCommerce products (or type extra IDs).
- **Collection (category / tag)**: pick one or more collections, set count, order, hide out-of-stock.
- **Custom cards**: the old repeater, for non-shop content. Existing cards keep working.

Variants (WooCommerce variable products):

- One chip per variation (a single attribute shows just the value, several show `Red / 1 L`).
- Choosing a chip updates price, old price, sale badge, image and the Add to Cart button.
- Out-of-stock variations are struck through and disable the button.
- The product's default variation is pre-selected, else the first in-stock one.

Add to cart:

- Simple and variable products add over `wc-ajax=add_to_cart` (mini-cart fragments refresh,
  "redirect to cart" setting respected). Without JS the button is a normal link.
- External products link to their URL, grouped products to the product page.

Equal height and alignment (pure CSS, no JS measuring):

- Cards in a row stretch to the same height.
- Title reserves N lines (default 2), description N lines (default 3); longer text is clipped.
- The variants row, old-price slot and price/button row are always printed, so cards without
  variants or without a sale still line up.
- Variant chips stay on one row (swipe on mobile). "Wrap" is available in the Equal Height section.

Other: responsive columns and gaps, entrance animation, hover lift / zoom, style controls for
every part, wishlist heart (saved in the visitor's browser), `prefers-reduced-motion` respected.

Performance: the editor-only product and collection lists are never queried on the front end.
The front end loads one CSS file and one small JS file, only on pages that use the widget.

## Testimonial Slider widget

- Centered slider like the reference design: flat active card, tilted side cards clipped by the edges.
- Heading with sparkle decorations (optional), photo (or letter circle), name + job, small text,
  star rating (decimals allowed, e.g. 4.5) with number, review, dots and round arrows.
- Equal height without JS: all cards share one grid cell. Small text and review reserve a set number
  of lines (Equal Height & Alignment), the stars and review are pinned to the bottom of every card.
- **Slider Layout** (like a grid's Columns): "Cards visible" 1-4 and "Side cards peeking" 0-3, set
  separately for desktop, tablet and mobile. Card width can be fixed or fit to the container.
- Smooth motion: easing choice, slide speed, side cards shrink slightly and grow back, content fades in
  step by step, active dot fills while autoplay runs. Cards follow the finger/mouse while dragging.
- Click a side card, keyboard arrows, autoplay with pause on hover, infinite loop.
- Hover lift and separate Normal / Active / Hover shadows, entrance animation, tilt, slide speed.
- Responsive card width, gap, tilt, typography and spacing per device. Respects `prefers-reduced-motion`.
- Stars are drawn with one CSS mask per card (no icon font, no images).

## Testimonial 2 widget

One testimonial at a time (no curve / tilt):

- Heading area (accent line, title, subtitle), big round photo with decorative ring and circle,
  opening and closing quote marks (any Elementor icon), review, name, role, optional star rating.
- Round arrows at the sides (move under the content on mobile) and a pill indicator with a white
  marker that slides to the active testimonial.
- Equal height: all slides share one grid cell and the review reserves a set number of lines.
- Grid Layout (like the grid widgets): Columns 1-3 testimonials visible per device, column gap, row gap, image resolution, title and name HTML tags. Photo beside or above the text, photo column width, text alignment, photo left/right.
- Compact by container width: a slide narrower than 520px stacks the photo above centered text, and a widget narrower than 640px moves the arrows under the content. This follows the width of the section the widget sits in, not just the screen (measured by the script with ResizeObserver, no CSS size containment, so the widget never collapses inside shrink-to-fit parents).
- No panel shadow by default (add one in Style > Panel > Box Shadow).
- Transitions: slide + fade, fade or zoom; speed, easing, slide distance, content reveal, entrance animation.
- Swipe / drag with the slide following the finger, autoplay, loop, pause on hover, keyboard arrows.
- Style controls for panel, heading, photo, circles, text, stars (size, color, alignment, space above, below and between), quote marks (size, color, position), arrows and pill.

## Arrow and pagination position (both testimonial widgets)

Arrow position and dots / pill position are separate responsive options (desktop, tablet, mobile):

- Arrows: sides (middle / top / bottom), or a pair at top or bottom, left / center / right.
- Dots / pill: top or bottom, left / center / right.
- If arrows and dots share an area they stack (dots first). Spacing between navigation and slider,
  and between dots and arrows, has its own controls.
- Side arrows move to a pair under the slider when the widget is narrower than 640px.
- Testimonial 2 "Review space": match the longest review (default, no empty gap, nothing cut) or
  reserve a fixed number of lines.

## Arrow colors (both testimonial widgets)

One set of Normal / Hover controls (icon color, background, border or shadow) styles **both**
arrows, in every position. Switch on "Style the next arrow separately" to give the next arrow its
own colors. Both arrows react to hover in the same way. Dots / pill have a single set of colors.

## Renaming widgets

Super Widget > Widgets: each widget has a "Name in Elementor" field, and the Elementor panel category
can be renamed too. Empty = default name. Reload the Elementor editor to see changes.

## Security notes

- Direct access blocked in every PHP file.
- All output escaped; links through Elementor's `add_link_attributes()`.
- Select values checked against whitelists before use in markup.
- Dashboard needs `manage_options`; forms use nonces; settings go through one
  sanitize function that accepts known widget slugs and valid hex colors only.
- Import takes pasted JSON only (max 20 KB), no file uploads.
- No external requests, no `eval`, no custom database tables, no file writes,
  no remote scripts or fonts.

## Easiest free flow (GitHub only, no server, no domain)
1. Upload the contents of this folder (including `.github`) to a new **public** GitHub repository.
2. Releases > Draft a new release > tag `v2.7.0` > Publish. GitHub Actions builds `super-widget.zip` and attaches it. It sets the version and the update source (your repo) inside the zip for you.
3. Download that zip from the release and install it on your sites.
4. Later: change code in the repo, publish a release with the next tag (`v2.7.1`). Sites update themselves within about 12 hours.
