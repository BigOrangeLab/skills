# WordPress 7.2 — Breaking and Behavior Changes

Ordered by likelihood of breaking a real codebase. Every entry here is **In Beta 1** — committed to `wordpress-develop` trunk and present in the 7.2 Beta 1 build (`7.2-beta1-64606`, Gutenberg pinned at 24.2.0) — and was checked against that source, not only against announcements. Items that were announced but not committed are listed in [sources.md](sources.md#roadmap-items-not-in-beta-1).

---

## 1. Script and style concatenation retired

**Severity: high.** [#57548](https://core.trac.wordpress.org/ticket/57548): [r64120](https://core.trac.wordpress.org/changeset/64120) (prefetching), [r64320](https://core.trac.wordpress.org/changeset/64320) (off by default), [r64585](https://core.trac.wordpress.org/changeset/64585) (retired, 19 October 2026).

### What changed

Admin and login-screen scripts and styles are no longer combined through `load-scripts.php` and `load-styles.php`. Every handle prints as its own `<script>` / `<link>` tag. The two loader files now respond **410 Gone** with a one-line plain-text body, without loading WordPress. To soften the extra requests, the login screen, the Dashboard, and post list tables `rel="prefetch"` the assets of the screen most likely to open next (`wp_prefetch_admin_assets()`, filterable with `wp_prefetch_admin_assets`).

What remains of the API:

| Item                                                                                            | Status in 7.2                                                                  |
| ----------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------ |
| `CONCATENATE_SCRIPTS` constant                                                                  | No longer read                                                                 |
| `COMPRESS_SCRIPTS`, `COMPRESS_CSS`, `ENFORCE_GZIP` constants                                    | No longer read (compression was already a no-op since 4.9.9)                   |
| `$compress_scripts`, `$compress_css` globals                                                    | Kept, default `false`                                                          |
| `print_head_scripts`, `print_footer_scripts`, `print_admin_styles`, `print_late_styles` filters | Fire through `apply_filters_deprecated()`; **return ignored**                  |
| `_print_scripts()`, `_print_styles()`, `script_concat_settings()`, `in_default_dir()`           | Deprecated                                                                     |
| `WP_Dependencies::get_etag()` and the concat properties of `WP_Scripts` / `WP_Styles`           | Deprecated; properties still declared so reads/writes work                     |
| `compression_test()`, `wp_ajax_wp_compression_test()`, the `wp-compression-test` AJAX action    | Deprecated no-ops; action no longer registered                                 |
| `wp-admin/includes/noop.php`                                                                    | Deprecated file; no longer defines stub functions                              |
| TinyMCE                                                                                         | Always two files (`wp-tinymce-root` + `wp-tinymce`); no `wp-tinymce.js` bundle |
| `wp_register_tinymce_scripts()` second parameter                                                | Deprecated (`$force_uncompressed` → `$deprecated`)                             |

### Why code breaks

- Server, CDN, or WAF rules that rewrite, cache-bust, allowlist, or rate-limit `load-scripts.php` / `load-styles.php` now act on a 410.
- Code that returned `false` from a `print_*` filter to suppress or reroute output no longer suppresses anything; the tags print anyway.
- Code that read the concat buffers (`$wp_scripts->concat`, `->print_code`, `->print_html`) to post-process combined output gets nothing to process.
- Admin pages with many enqueued handles make more requests; under HTTP/1.1 or a slow origin this is a visible slowdown.
- A Content-Security-Policy on the admin that never listed individual core script URLs because they came from the loaders must now allow them (same origin, usually already covered by `'self'`).

### Detection

```bash
grep -rnE "load-(scripts|styles)\\\\?\.php" --include="*.php" --include="*.conf" --include="*htaccess" --include="*.js" --include="*.yml" --include="*.yaml" --include="*.json" .
grep -rnE "CONCATENATE_SCRIPTS|COMPRESS_(SCRIPTS|CSS)|ENFORCE_GZIP" --include="*.php" .
grep -rnE "print_(head_scripts|footer_scripts|admin_styles|late_styles)" --include="*.php" .
grep -rnE "_print_(scripts|styles)\(|script_concat_settings|in_default_dir\(|->concat\b|->print_code|->print_html" --include="*.php" .
```

### Fix

- Drop server and CDN rules for the two loaders; they serve nothing useful any more.
- Replace `print_*` filter tricks with `wp_dequeue_script()` / `wp_dequeue_style()`, or `script_loader_tag` / `style_loader_tag` for per-tag changes — these now apply to every core admin asset, which they never did under concatenation.
- Remove `define( 'CONCATENATE_SCRIPTS', ... )` and the compression constants from `wp-config.php` once the site is on 7.2; they are inert.

---

## 2. `wp_kses()` rebuilt on the HTML API

**Severity: high for anything that sanitizes user HTML.** [#66208](https://core.trac.wordpress.org/ticket/66208), [r64233](https://core.trac.wordpress.org/changeset/64233) and follow-ups. [Progress report](https://make.wordpress.org/core/2026/10/07/progress-report-wp_kses/).

**What changed.**

`wp_kses()` now delegates to `wp_sanitize_html_kses()`, a new implementation on `WP_HTML_Tag_Processor`, **enabled by default** in Beta 1 (`default-filters.php` adds `__return_false` to `wp_kses_force_legacy_parser`). The progress report said "7.2 or 7.3 depending on testing", so this can still be reverted to opt-in before release — check the RC.

Observable differences from the legacy parser:

- Output markup is normalized (attribute quoting, casing, entity encoding follow the HTML spec).
- The contents of `<script>` and `<style>` are stripped even when those tags are in the allowlist.
- Some malformed SVG, MathML, and comments that the legacy parser passed through are rejected.
- Every block delimiter is reserialized and normalized (`<!-- wp:block {} -->` is kept as-is only because of a follow-up fix).
- `pre_kses` and the other legacy pre-filters still run, in-band, through `wp_kses_hook()`. `safecss_filter_attr()` still filters `style`.
- `wp_kses_html_error()` is deprecated.

**Why code breaks.**

- Unit tests that assert exact `wp_kses()` / `wp_kses_post()` output strings fail on normalization alone.
- Allowlists that permit `<script>` or `<style>` for a trusted role (custom embed fields, form builders, ad slots) now lose the contents.
- Stored content round-tripped through `wp_kses()` on save is rewritten on the next save, which creates noisy revisions or diffs.

**Detection.**

```bash
grep -rnE "wp_kses(_post|_data)?\(|wp_filter_(post_)?kses" --include="*.php" . | head -50
grep -rnE "['\"](script|style|svg|math)['\"]\s*=>" --include="*.php" .   # allowlists naming these
grep -rnE "assert(Same|Equals)\(.*wp_kses" --include="*.php" tests/
```

**Fix.**

Bisect with the legacy parser, then fix the input or the allowlist, not the switch:

```php
// Bisecting only — not a fix. Core's own callback is at priority 10.
add_filter( 'wp_kses_force_legacy_parser', '__return_true', 20 );
```

- For trusted script or style content, store it outside the kses path (a dedicated meta field written by a capability-checked handler and printed with `wp_print_inline_script_tag()` / `wp_add_inline_style()`).
- Rewrite brittle output-string tests to parse the result (for example with `WP_HTML_Tag_Processor`) and assert on tags and attributes.

---

## 3. Interactivity API removals and Gutenberg API removals

**Severity: high for blocks using these.** Gutenberg 24.2 / `@wordpress/interactivity` 7.0.0 ([#84098](https://github.com/WordPress/gutenberg/pull/84098)); [#83171](https://github.com/WordPress/gutenberg/pull/83171); [#82319](https://github.com/WordPress/gutenberg/pull/82319).

| Removed                                                                                 | Replacement                                                   |
| --------------------------------------------------------------------------------------- | ------------------------------------------------------------- |
| `data-wp-ignore`                                                                        | Nothing — the element is now hydrated; remove the attribute   |
| Two-hyphen unique IDs: `data-wp-watch--id`, `data-wp-on--click--id`                     | Three hyphens: `data-wp-watch---id`, `data-wp-on--click---id` |
| `!` applied to a directive value that resolves to a function                            | A derived-state getter                                        |
| `InspectorControls` `__experimentalGroup` prop (deprecated since 6.2)                   | `group`                                                       |
| `as` prop on `PluginMoreMenuItem`, `PluginSidebarMoreMenuItem`, `PluginPreviewMenuItem` | None; the prop is ignored with a console deprecation          |

With two-hyphen IDs, `data-wp-watch`, `data-wp-init`, and `data-wp-run` now **skip** the directive, and `data-wp-on--click--id` listens for an event literally named `click--id`. Both fail silently in production; a warning shows only in development mode. Server-side directive processing and `data-wp-on-async*` are unchanged.

```bash
grep -rnE "data-wp-ignore" --include="*.php" --include="*.html" --include="*.js" .
grep -rnE "data-wp-(watch|init|run)--[A-Za-z0-9]" --include="*.php" --include="*.html" --include="*.js" .
grep -rnE "data-wp-on(-window|-document)?--[A-Za-z0-9_.-]*[A-Za-z0-9]--[A-Za-z0-9]" --include="*.php" --include="*.html" --include="*.js" .
grep -rnE "__experimentalGroup" --include="*.js" --include="*.jsx" --include="*.ts" --include="*.tsx" .
```

---

## 4. "Howdy" moved within the toolbar

**Severity: medium; extremely common snippet.** `wp_admin_bar_my_account_item()` / `wp_admin_bar_my_account_menu()`, `@since 7.2.0`.

The `my-account` node title is now just the display name (falling back to `user_login` when empty) plus the avatar. "Howdy, %s" moved into the `user-info` node inside the `user-actions` group. Snippets that `str_replace( 'Howdy', ... )` on the `my-account` node, or filter `gettext` for "Howdy," expecting it in the top bar, now change the dropdown instead, or nothing.

```bash
grep -rnE "Howdy" --include="*.php" .
```

Update the code to target `user-info`, or drop it if it only existed to remove "Howdy" from the top bar.

---

## 5. Admin menu and toolbar icons are inline SVG

**Severity: medium for admin styling.** [#65089](https://core.trac.wordpress.org/ticket/65089), [r64583](https://core.trac.wordpress.org/changeset/64583), [proposal](https://make.wordpress.org/core/2026/09/04/replacing-dashicons-in-the-admin-bar-and-menu/).

**What changed.**

- `_wp_menu_output()` maps the 12 core `dashicons-admin-*` / `dashicons-dashboard` menu icons to `core-admin/*` icons and prints them as inline SVG via `wp_get_icon()`, with classes `dashicons-before svg-icon`. The `$menu` global still holds the Dashicons values for back-compat.
- Core toolbar nodes print `<span class="ab-icon svg-icon">` with an inline SVG (WordPress logo, search, and others).
- Plugin menu items using `dashicons-*` are unchanged and still use the font.
- New: `add_menu_page()` accepts a **namespaced icon name** (`my-plugin/logo`) for a registered icon. An unregistered name logs `_doing_it_wrong()` and renders an empty icon slot. See [new-apis.md](new-apis.md#icons).
- There is **no public API** for SVG icons on toolbar nodes yet (`_wp_admin_bar_icon()` is private); keep plugin toolbar icons as they are.
- `wp_color_scheme_menu_icon_styles()` colors the SVGs from the admin color scheme.

**Why code breaks.**

Custom admin CSS that recolors, resizes, or replaces core icons through `::before` (`#adminmenu .menu-icon-post div.wp-menu-image:before`, `#wp-admin-bar-wp-logo > .ab-item .ab-icon:before`) no longer matches anything. White-label plugins that swap the toolbar WordPress logo via `content:` on `::before` stop working.

```bash
grep -rnE "(wp-menu-image|dashicons-before|ab-icon)[^{]*::?before" --include="*.css" --include="*.scss" .
```

Target the `svg` inside `.wp-menu-image` / `.ab-icon` and use `color` (the icons use `currentColor`). To replace the toolbar logo, remove the `wp-logo` node and add your own.

---

## 6. `WP_DEBUG` on by default for local environments

**Severity: medium for local dev and CI.** `wp-includes/default-constants.php`, [r64115](https://core.trac.wordpress.org/changeset/64115) (5 October 2026).

When `WP_DEBUG` is not defined, it now defaults to `true` for `WP_ENVIRONMENT_TYPE` `local` as well as `development` (and whenever a development mode is set). `WP_DEBUG_DISPLAY` already defaults to `true`, so notices and deprecations print to the page. Local by WP Engine, `wp-env`, and many Docker setups set the environment type to `local`.

Effects: PHP notices land inside AJAX, REST, and JSON responses on local sites, breaking front-end code and end-to-end tests that passed before. The 7.2 deprecations in this file appear immediately.

Fix the notices; if a CI or e2e environment must stay quiet, define `WP_DEBUG` (or `WP_DEBUG_DISPLAY`) explicitly in its config.

---

## 7. Dashboard widget changes

**Severity: low–medium.** [#65116](https://core.trac.wordpress.org/ticket/65116), [r64593](https://core.trac.wordpress.org/changeset/64593).

Recent Comments moved out of the Activity widget into a new `dashboard_recent_comments` widget (`wp_dashboard_recent_comments_widget()`); Activity gains an "On This Day" section (`wp_dashboard_on_this_day()`). Code that calls `remove_meta_box( 'dashboard_activity', ... )` to hide recent comments now leaves the new widget showing; code that rearranges dashboard widgets by ID needs the new one. A Screen Options toggle can now disable meta box reordering ([#50699](https://core.trac.wordpress.org/ticket/50699)), so drag-and-drop may be off for some users.

```bash
grep -rnE "dashboard_(activity|recent_comments)|wp_dashboard_setup" --include="*.php" .
```

---

## 8. Random content URLs and the `random` query var

**Severity: medium where the slug is in use.** [r64402](https://core.trac.wordpress.org/changeset/64402).

WordPress adds rewrite rules for `/random/` (translated per site language; filterable base via the `wp_random_base` option) that redirect to a random post, plus `/random/{taxonomy}/{term}/`, `/random/{author-base}/{author}/`, and `/random/{post-type}/`. `random` is a new **public query var**. A published page or post at that URL takes precedence over core's redirect.

Conflicts: custom rewrite rules or routes under `random/` that are not pages; plugins that read a `random` query arg (`$_GET['random']`, `get_query_var( 'random' )`) or add it via `query_vars`, since WordPress now parses it itself.

```bash
grep -rnE "add_rewrite_rule\(\s*['\"]\^?random|['\"]random['\"]" --include="*.php" .
```

Disable with `add_filter( 'wp_enable_random_content_redirect', '__return_false' )` and flush rewrite rules; adjust rules via `random_rewrite_rules`.

---

## 9. Other silent changes

| Change                                                                                                                                                                                                                                                           | Who notices                                                      | Source                                                                                                                              |
| ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ---------------------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------- |
| `NO_BACKSLASH_ESCAPES` is stripped from WordPress's DB session. Re-enable only via `incompatible_sql_modes` in a `db.php` drop-in.                                                                                                                               | Hosts or plugins that set the mode deliberately                  | [dev note](https://make.wordpress.org/core/2026/10/09/no_backslash_escapes-sql-mode-is-now-turned-off-by-default-in-wordpress-7-2/) |
| Users get an email when an application password is created. Disable with `wp_send_application_password_created_email`; customize with `wp_application_password_created_email`.                                                                                   | Provisioning scripts and CI that create app passwords            | [#63927](https://core.trac.wordpress.org/ticket/63927)                                                                              |
| XML sitemaps no longer have XSL stylesheets; `wp_sitemaps_stylesheet_*` filters fire only as deprecations and their results are ignored; `WP_Sitemaps_Stylesheet` deprecated.                                                                                    | SEO plugins styling core sitemaps                                | [r64073](https://core.trac.wordpress.org/changeset/64073)                                                                           |
| `@wordpress/icons` 17+ icons are stroke-based; `fill` no longer recolors them, use CSS `color`. Stroke width scales with size (18.0).                                                                                                                            | Editor UI that recolors icons with `fill`                        | [#82754](https://github.com/WordPress/gutenberg/pull/82754)                                                                         |
| `@wordpress/components`: `Divider`, `ResponsiveWrapper`, `Scrollable`, `ZStack`, `Elevation` deprecated since 7.2, removal planned for 7.4. `MediaUpload`'s `unstableFeaturedImageFlow` renamed `featuredImageFlow`.                                             | Editor and admin React code                                      | [October roundup](https://developer.wordpress.org/news/2026/10/whats-new-for-developers-october-2026/); see `wp-admin-ui`           |
| Editor: cross-origin isolation no longer buffers the page or adds `crossorigin` attributes; `wp_add_crossorigin_attributes()` and `wp_start_cross_origin_isolation_output_buffer()` deprecated (use `wp_send_document_isolation_policy_header()`).               | Code that relied on the injected attributes or the output buffer | [r64489](https://core.trac.wordpress.org/changeset/64489)                                                                           |
| Block markup: `has-link-color` (and related) classes on Accordion, Tabs, Tab Panel, Post Template; Gallery CSS scoped to `:where(.is-layout-flex)`; footnote IDs prefixed `fn-`; Site Title `has-fit-text`; "Custom Link" is now a variation of Navigation Link. | Theme CSS and code that parses block output                      | Gutenberg 24.1 notes                                                                                                                |
| jQuery Migrate 3.4.1 → 3.6.0 (first step toward jQuery 4); more migrate warnings with `SCRIPT_DEBUG`.                                                                                                                                                            | Legacy admin jQuery                                              | [r63983](https://core.trac.wordpress.org/changeset/63983)                                                                           |
| `_truncate_post_slug()` deprecated for `wp_truncate_slug()`; term slugs capped at 200 characters.                                                                                                                                                                | Code calling the private helper                                  | [r63909](https://core.trac.wordpress.org/changeset/63909)                                                                           |
| Imgur and someecards oEmbed providers removed.                                                                                                                                                                                                                   | Content embedding those URLs (now plain links)                   | [r64128](https://core.trac.wordpress.org/changeset/64128), [r63904](https://core.trac.wordpress.org/changeset/63904)                |
| Pingbacks/trackbacks from the same site are auto-approved (`auto_approve_ping`).                                                                                                                                                                                 | Moderation workflows                                             | [r63036](https://core.trac.wordpress.org/changeset/63036)                                                                           |
| `/.well-known/change-password` redirects to the profile screen (`wp_change_password_url`).                                                                                                                                                                       | Sites already routing that path                                  | [r64348](https://core.trac.wordpress.org/changeset/64348)                                                                           |
| Invalid `theme.json` preset items (wrong type) are dropped instead of fataling.                                                                                                                                                                                  | Themes whose broken presets were masked by caching               | [r64491](https://core.trac.wordpress.org/changeset/64491)                                                                           |

Not changed in 7.2, despite earlier expectations: minimum PHP stays **7.4**, minimum MySQL **5.5.5**, and core still bundles **React 18.3.1** (React 19 is on the roadmap as "unlikely to fully land").
