# AudioShelf — GeneratePress Child Theme

A blank-slate **GeneratePress child theme** that turns WordPress into an audiobook
library, matching the prototype you approved:

- **Three-column homepage** — left sidebar (age categories, category modifiers,
  popular languages, hot searches), latest audiobooks in the middle, recent +
  most popular on the right.
- **Catalog page** — every audiobook as a small tile, **4 per row**, with a
  search box (title / content / narrator), a filter rail, sorting and pagination.
  Every filter combination is a plain GET URL, so views are shareable.
- **Audiobook detail page** — big cover, mock player button, rating, tags,
  related titles.
- **Header menus** — Home / Catalog / About / Log in / Sign up (use any
  membership plugin for real accounts; the theme is membership-agnostic).
- **Hot searches** — every catalog search is logged and the top 10 terms
  resurface in the left sidebar automatically.

## Requirements

| Software | Version |
| --- | --- |
| WordPress | 6.0+ |
| GeneratePress (free) | any recent version — no premium module needed |
| This child theme | 1.0.0 |

## Installation (5 minutes)

1. **Install GeneratePress**
   `Appearance → Themes → Add New → search "GeneratePress" → Install → Activate`.
2. **Install this child theme**
   `Appearance → Themes → Add New → Upload Theme → choose
   audioshelf-gp-child.zip → Install → Activate`.
   Activating flushes the rewrite rules automatically.
3. **Permalinks** — `Settings → Permalinks → select "Post name" → Save`.
4. **Create the two key pages**
   - `Pages → Add New` → title **Catalog** → in the sidebar set
     *Template → Audiobook Catalog* → Publish.
   - `Pages → Add New` → title **Home** → set *Template → Audiobook Homepage* →
     Publish.
   - *(Optional)* an **About** page with a normal paragraph layout.
5. **Set the front page** — `Settings → Reading → Your homepage displays: A
   static page → Homepage: Home`.
6. **Header menu** — `Appearance → Menus → create "Main Menu"` → add Home,
   Catalog, About → assign to the **Primary** location. GeneratePress renders
   the header bar; the theme's CSS keeps it clean.
7. **Login / Sign up links** — the fastest options:
   - add *Log in* / *Sign up* custom links to the menu, or
   - install a membership plugin (Ultimate Member, Members, WP-Members) — its
     `[ultimatemember]`-style shortcodes work on normal pages.

## Adding audiobooks

`Audiobooks → Add New` in the dashboard:

1. **Title** — the audiobook title.
2. **Content** — the description shown on the detail page.
3. **Featured Image** — square cover art (≥ 480×480). This is the tile cover.
4. **Taxonomies** (boxes on the right):
   - *Age categories* — e.g. Kids (5–8), Middle Grade (9–12), Teen (13–17), Adult (18+)
   - *Categories* — e.g. Fantasy, Science Fiction, Mystery & Thriller
   - *Languages* — e.g. English, Spanish, French, German, Persian, Arabic
   - *Category modifiers* — free-form tags: Epic Fantasy, Dystopian, Magic Realism…
5. **Audiobook details** meta box — Book author, Narrator, Duration (minutes),
   Rating (0–5), Popularity (listen count). Narrator is searchable in the catalog.

## Sidebars as widgets (optional)

The home sidebars are real widget areas:

- `Appearance → Widgets → **Audiobook Left Sidebar** / **Audiobook Right Sidebar**`

If you leave them empty, the built-in defaults render (the same lists as the
prototype). Drop widgets in to override — e.g. a promo banner on the right.

## How the catalog filters map to URLs

| Filter | GET param | Example |
| --- | --- | --- |
| Search term | `ab_q` | `?ab_q=harry` |
| Age category (slug) | `ab_age` | `?ab_age=teen-13-17` |
| Category (slug) | `ab_cat` | `?ab_cat=fantasy` |
| Language (slug) | `ab_lang` | `?ab_lang=persian` |
| Category modifiers (slugs, comma = AND) | `ab_tags` | `?ab_tags=epic-fantasy,adventure` |
| Sort: newest / popular / rating / title / shortest | `ab_sort` | `?ab_sort=popular` |
| Page | `paged` | handled by `paginate_links` |

## Tweaking the look

- Grid density: `assets/css/audioshelf.css` → `.ab-tile-grid { grid-template-columns: repeat(4, ...) }`
  and `.ab-catalog`'s `posts_per_page = 12` in `page-templates/template-catalog.php`
  (keep it a multiple of 4).
- Accent color: `--ab-accent` at the top of the CSS file.
- All class names are prefixed `ab-` so they never clash with GeneratePress.

## Files

```
audioshelf-gp-child/
├── style.css                      # child theme manifest (Template: generatepress)
├── functions.php                  # enqueues, search-logger, narrator search
├── assets/css/audioshelf.css      # all custom styling
├── inc/
│   ├── audiobook-post-type.php    # CPT + 4 taxonomies + meta box
│   └── helpers.php                # catalog URL, hot searches, fallback sidebars
├── page-templates/
│   ├── template-home.php          # three-column homepage
│   └── template-catalog.php       # search + filters + 4-per-row grid
├── single-audiobook.php           # detail page
└── template-parts/
    └── tile-audiobook.php         # one reusable tile
```

## Notes

- The "player" is intentionally a styled placeholder — wire it to your audio
  files (or a service like Spotify/ SoundCloud embeds) when you go live.
- Hot searches are stored in the `ab_hot_searches` option (top 10, case-
  insensitive). Clear it with any options manager or WP-CLI:
  `wp option delete ab_hot_searches`.
- Sample ratings / durations / popularity in the prototype were demo values —
  enter your real numbers per audiobook in the meta box.
