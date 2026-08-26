# ALB troubleshooting

## The layout is broken on the front end

1. **Check shortcode balance.** _Enfold → Layout Builder_ ships a content check that parses the page and prints the shortcode tree, flagging unbalanced tags. Run it on both ALB and non-ALB pages — a stray `[/av_one_half]` from a paste is the usual cause.
2. **Look for illegal nesting.** A Color Section, Grid Row, Tab Section, LayerSlider, Masonry, or fullwidth slider inside a column silently misrenders instead of erroring.
3. **Look for raw `[` or `]` in text.** Square brackets typed into a Textblock or an input field corrupt the internal structure. Replace with `###91###` and `###93###`, or install Kriesi's _Special Character Translation_ plugin.
4. **Check for duplicate `av_uid`.** Hand-copied blocks share ids and therefore share generated CSS. Re-save via the builder to have Enfold reassign them, or edit them yourself.
5. **Clear caches.** Enfold writes per-page CSS/JS. _Enfold → Performance_ → delete old CSS and JS files, then clear any page-cache plugin and CDN.

## The builder will not load

Symptoms: the ALB canvas is blank, elements do not drag, or the page shows a raw shortcode blob.

1. **JS error** — open the browser console on the edit screen. Almost always a plugin conflict.
2. **Disable script merging** (_Enfold → Performance_) and hard-reload. Merged/cached JS masks the real error and serves stale builder code.
3. **Deactivate plugins** in halves until the console clears.
4. **Memory** — a very large ALB page can exhaust PHP memory during parse. Raise `WP_MEMORY_LIMIT` / `memory_limit` and retry.
5. **Confirm the post type supports ALB** — see `avf_alb_supported_post_types` in [developer-api.md](developer-api.md).

## The page looks empty after switching editors

Expected, not a bug. ALB and the default (classic/block) editor read **different data**: ALB from `_aviaLayoutBuilderCleanData`, the default editor from `post_content` as WordPress wrote it. Switching does not convert anything.

Recovery: switch back. The other dataset is still intact unless the post was saved in the new mode. If it was saved, check post revisions — Enfold stores ALB meta on revisions (`_aviaLayoutBuilder_active`, `_aviaLayoutBuilderCleanData`, `_avia_builder_shortcode_tree` are all revision-tracked).

## Duplicating an ALB page produces a broken copy

Third-party duplicate-post plugins commonly mangle ALB data. Use Enfold's own documented duplication script (linked from the _Intro to Layout Builder_ docs) instead, or copy manually: create the target post, then copy `post_content`, `_aviaLayoutBuilderCleanData`, and set `_aviaLayoutBuilder_active` to `active`.

## Custom CSS class or ID field is missing

- Enable _Enfold → Layout Builder → General Builder Options → Developer options_.
- On Enfold older than 4.1: `add_theme_support( 'avia_template_builder_custom_css' );`
- If a field is hidden despite the setting, check for an `avf_alb_get_developer_settings` filter returning `'hide'` or `'deactivate'`.

## Custom classes have no effect

Enfold's own selectors are specific. Prefix with `#top` (and `#wrap_all` if still losing):

```css
#top .ktf-darkborder {
	border: 1px solid #333;
}
```

Use a unique prefix per project so class names never collide with the theme or a plugin.

## Frontend media uploader or lightbox dead with a CDN enabled

_Enfold → Performance → Disable Features_ → set **Self hosted videos and audio features (WP-Mediaelement scripts)** to _Always load media features (= WP default behaviour)_. By default Enfold loads `wp-mediaelement` only when it detects a need; plugins relying on WordPress's default behaviour break under that optimisation.

## A locked CET option did not propagate

Locked Custom Element Template values apply on the next **page load** of each instance, and Enfold serves cached per-page CSS. Clear the server cache and Enfold's generated CSS/JS after locking an option.

## Changes to the parent theme disappeared

Enfold updates overwrite the parent theme. Every customization belongs in a child theme. If `functions.php` edits must temporarily live in the parent, they have to go _after_
`if ( isset( $avia_config['use_child_theme_functions_only'] ) ) return;` — and they will still be lost on update.

## Element attribute does not do what the docs say

The documentation is illustrative, not exhaustive, and lags the theme. Read the element source:

```bash
E=wp-content/themes/enfold/config-templatebuilder/avia-shortcodes
grep -n "'id'\s*=>" "$E/<element>/<element>.php"     # attribute names
grep -n "'std'\s*=>" "$E/<element>/<element>.php"     # defaults
sed -n '/function shortcode_handler/,/^\t\t}/p' "$E/<element>/<element>.php"
```

Cross-check the behaviour change against `https://kriesi.at/documentation/enfold/changelog/` for the installed version.
