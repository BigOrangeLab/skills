---
name: enfold-alb
description: "Use when building, editing, extending, or programmatically generating page content for the Enfold theme's Avia Advanced Layout Builder (ALB) — shortcode structure, element catalog, ALB post meta, custom elements, dynamic content, and the classic/block editor switch."
compatibility: "Enfold 7.x (verified against 7.1.6) on WordPress 5.x+. Element set and developer filters largely apply back to Enfold 4.5; version-specific notes are called out inline. ALB ships only with Enfold — it is not sold as a standalone plugin."
license: MIT
metadata:
    author: georgestephanis
    version: "1.0"
    written: "2026-08-26"
    written_against:
        enfold: "7.1.6"
---

# Enfold — Avia Advanced Layout Builder (ALB)

ALB is the drag-and-drop page builder bundled with the Enfold theme. It is **shortcode-based**: every element is an Avia shortcode (`[av_section]`, `[av_textblock]`, …) stored in `post_content`. There is no block-editor or React layer — understanding the shortcode grammar is the whole job.

The single most important structural fact: **ALB and the default editor use two different datasets and you cannot switch back and forth without losing content.** Toggling the editor does not convert anything.

## When to use

- Building or editing pages on an Enfold site through ALB
- Reading, generating, or migrating ALB page content programmatically (imports, bulk edits, WP-CLI scripts)
- Adding a custom element to ALB, or overriding a bundled one from a child theme
- Enabling ALB on a custom post type, or forcing/hiding the editor switch
- Debugging a page whose layout broke, or whose builder will not load
- Wiring dynamic post/custom-field data into ALB elements

Do NOT use for generic WordPress block development (`wp-block-development`), classic-editor/TinyMCE work unrelated to Avia, or non-Enfold page builders. Enfold theme options unrelated to the builder (header, typography, performance) are outside this skill.

## Inputs required

- **Enfold version** — `grep -m1 '^Version:' wp-content/themes/enfold/style.css`. Element availability and modal options move between minor versions.
- **Theme path** — the bundled elements at `enfold/config-templatebuilder/avia-shortcodes/` are the authoritative attribute reference; the docs are not exhaustive.
- **Child theme presence** — all customization belongs in a child theme. If none exists, create one before adding filters (`https://kriesi.at/documentation/enfold/child-theme/`).
- **Target post/page ID** and whether ALB is currently active on it (see step 1).
- **Whether the site is live** — ALB content edits are destructive and not automatically reversible. Never bulk-rewrite production `post_content` without a database backup.

## Procedure

### 1. Determine whether ALB is active on the post

ALB stores three post meta keys:

| Meta key                       | Contents                                                                  |
| ------------------------------ | ------------------------------------------------------------------------- |
| `_aviaLayoutBuilder_active`    | `'active'` when ALB is on, empty string when the default editor is in use |
| `_aviaLayoutBuilderCleanData`  | The canonical ALB shortcode source — mirrored into `post_content` on save |
| `_avia_builder_shortcode_tree` | Cached parsed structure of the shortcodes, rebuilt on save                |

```bash
wp post meta get <ID> _aviaLayoutBuilder_active
wp post meta get <ID> _aviaLayoutBuilderCleanData
```

`_aviaLayoutBuilderCleanData` is the source of truth the builder reads; `post_content` is what the front end renders. Keep them in sync — writing only one leaves the page and the editor disagreeing.

In PHP, prefer the API over raw meta: `AviaHelper::builder_status( $post_id )` / `$avia_config['builder']->get_alb_builder_status( $post_id )`.

### 2. Read the existing structure before changing it

Enable **debug mode** to see (and hand-edit) the live shortcode of the page under the builder. In the child theme `functions.php`:

```php
add_action( 'avia_builder_mode', 'builder_set_debug' );
function builder_set_debug() {
    return 'debug';
}
```

(Without a child theme, place this _after_ the `if ( isset( $avia_config['use_child_theme_functions_only'] ) ) return;` line in the parent `functions.php` — it will be lost on theme update.)

The debug textarea has **no validation**. Malformed or unbalanced shortcodes there will break the layout silently.

### 3. Write the layout

Follow the grammar and nesting rules in [references/elements.md](references/elements.md). The essentials:

- **Layout Elements** (`av_section`, `av_layout_row` + `av_cell_*`, `av_tab_section`, and the `av_one_half`-style column tags) define structure.
- **Content Elements** (`av_textblock`, `av_heading`, `av_button`, …) and **Media Elements** (`av_image`, `av_slideshow`, `av_video`, …) go inside them.
- **Full-width elements cannot be nested inside columns** — Color Section, LayerSlider, Masonry, Grid Row, Tab Section. They always span 100% and push a sidebar to the bottom of the page.
- The **first column in a row** carries the bare `first` attribute; the last carries `last`.
- Every element gets a unique `av_uid='av-xxxxx'`. Duplicates are repaired on save, but generate distinct values when writing content yourself.

### 4. Reuse layouts instead of rebuilding them

- **ALB Templates** — save the current page layout by name via the _Templates_ control at the top right of the builder, then load it on another page.
- **Custom Element Templates (CET)** — predefine and optionally _lock_ an element's settings so every new instance inherits them; locked options propagate to existing instances on reload. Enable under _Enfold → Custom Elements_.
- **Custom Layout & Dynamic Content** — build a reusable layout section (a CPT) and drop it into posts with the `av_custom_layout` element, filling fields from post data with `{wp_post_title}`-style dynamic content. Enable under _Enfold → Layout Builder → Custom Layout And Dynamic Content_.

See [references/developer-api.md](references/developer-api.md) for the dynamic-content syntax and its modal-field escaping rule.

### 5. Enable developer options when you need hooks for CSS

_Enfold → Layout Builder → General Builder Options_ exposes per-element **Custom CSS Class**, **Custom ID**, **heading tag**, and **ARIA label** fields. Prefix custom classes to avoid collisions (`ktf-darkborder`, not `darkborder`).

On Enfold older than 4.1, the custom-class field needs `add_theme_support( 'avia_template_builder_custom_css' );`.

### 6. Extend or override elements from the child theme

Register a child-theme shortcodes directory ahead of the parent's so same-named files win:

```php
add_filter( 'avia_load_shortcodes', 'avia_include_shortcode_template', 15, 1 );
function avia_include_shortcode_template( $paths ) {
    array_unshift( $paths, get_stylesheet_directory() . '/shortcodes/' );
    return $paths;
}
```

Copy the element folder you want to change from `enfold/config-templatebuilder/avia-shortcodes/` into `<child>/shortcodes/`, or add a new class extending `aviaShortcodeTemplate`. Full class contract in [references/developer-api.md](references/developer-api.md).

### 7. Enable ALB on custom post types

```php
add_filter( 'avf_alb_supported_post_types', function ( array $types ) {
    $types[] = 'my_cpt';
    return $types;
} );

add_filter( 'avf_metabox_layout_post_types', function ( array $types ) {
    $types[] = 'my_cpt';
    return $types;
} );
```

To force ALB on and hide the editor switch, return `true` from `avf_force_alb_usage` — but gate it on post type _and_ on whether existing posts already use the classic editor, or you will hide their content.

## Verification

- **Structure parses** — load the page in the builder. Elements render as builder cards, not as a raw text blob. A blob means unbalanced shortcodes.
- **Front end matches** — view the page logged out. Compare against the builder preview; a full-width element that suddenly sits inside a container usually means it was nested illegally.
- **Shortcode balance** — Enfold ships a validator at _Enfold → Layout Builder_ ("check the content" / shortcode tree). It reports unbalanced tags and prints the parsed tree.
- **Meta consistency** — `_aviaLayoutBuilderCleanData` and `post_content` should be identical after a save. If you wrote content programmatically, confirm both.
- **After adding a custom element** — it appears in the correct builder tab, its modal opens, and its shortcode round-trips through a save unchanged.
- **Clear caches** — Enfold merges and caches CSS/JS per page. After changing styling or element output, clear the server cache and _Enfold → Performance → Delete old CSS and JS files_; otherwise you are inspecting stale output.

## Failure modes

- **Switching editors wipes content.** ALB and the default editor read different data. Switching does not migrate — copy the shortcode out first (debug mode) if you need it.
- **Square brackets break the builder.** `[` and `]` typed into Textblock or input fields corrupt the internal structure. Substitute `###91###` and `###93###`, or install Kriesi's Special Character Translation plugin.
- **Nesting a full-width element inside a column** produces a broken or duplicated layout rather than an error.
- **Duplicating an ALB page with a third-party duplicate-post plugin** frequently corrupts the shortcode data. Use Enfold's documented duplication script instead.
- **`av_uid` collisions** after hand-copying shortcode blocks cause elements to share styling. Change the uid values when pasting.
- **Builder will not load** — almost always a JS error from a plugin conflict or a merged-script cache. Test with plugins disabled and script merging off before suspecting the theme.
- **Editing the debug textarea** has no safety net; a missing quote or bracket silently destroys the layout on save.
- **Customizations in the parent theme** are lost on theme update. Everything goes in the child theme.
- **Frontend media uploader or lightbox dead with a CDN** — set _Enfold → Performance → Disable Features → Self hosted videos and audio features_ to "Always load media features (= WP default behaviour)".

## Escalation

- **Attributes not in the docs** — read the element's PHP under `enfold/config-templatebuilder/avia-shortcodes/<element>/`; `shortcode_insert_button()` gives the tag and tab, `popup_elements()` gives every option `id` and its `std` default. That file is authoritative; the documentation is not exhaustive.
- **Ask the user before** rewriting `post_content` in bulk, forcing ALB onto an existing post type, or clearing Enfold's CSS/JS cache on production.
- **Kriesi support forum** (`https://kriesi.at/support/`) for theme bugs — requires a valid purchase code. Version-specific behaviour changes are listed in `https://kriesi.at/documentation/enfold/changelog/`.
- If the site has an unusual Enfold version (pre-4.5, or a heavily patched parent theme), confirm the element and filter names against the local theme copy before trusting anything here.

## References

- [references/elements.md](references/elements.md) — full element catalog, shortcode grammar, nesting rules, canonical markup
- [references/developer-api.md](references/developer-api.md) — filters, custom elements, dynamic content, programmatic generation
- [references/troubleshooting.md](references/troubleshooting.md) — diagnostics for broken layouts and a builder that will not load
- Upstream: `https://kriesi.at/documentation/enfold/intro-to-layout-builder/`
