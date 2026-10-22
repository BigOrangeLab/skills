---
name: wp-7-2-upgrade
description: "Use when preparing a WordPress plugin, theme, or site codebase for WordPress 7.2 (due December 2026) — retired script/style concatenation and the 410 load-scripts.php, wp_kses() rebuilt on the HTML API, SVG admin menu and toolbar icons, WP_DEBUG on by default for local environments, removed Interactivity API directives, @wordpress/components deprecations — or when adopting 7.2's new APIs (Secrets API, entity fields and actions registries, data-wp-html, text-shadow and background-clip block supports)."
compatibility: "WordPress 7.2 pre-release, verified against 7.2 Beta 1 (20 October 2026) with Gutenberg 24.2. Final release is scheduled for 8 December 2026; re-verify at RC1 (17 November). Targets plugins, themes, block themes, mu-plugins, and full site repos. Builds on wp-7-1-upgrade for sites still on 7.0 or earlier."
license: MIT
metadata:
    author: georgestephanis
    version: "1.0"
    written: "2026-10-22"
    written_against:
        wordpress: "7.2-beta1"
        gutenberg: "24.2.0"
---

# WordPress 7.2 Upgrade Preparation

## When to use

Use this skill when a codebase needs to be checked against, or readied for, WordPress 7.2 before or shortly after it ships. Typical triggers:

- A client site or product will move to 7.2 and needs a compatibility pass ahead of the release.
- A plugin or theme touches script/style loading, `wp_kses()`, the admin menu or toolbar, the Interactivity API, or the dashboard.
- Server, CDN, or WAF config mentions `load-scripts.php` or `load-styles.php`.
- A 7.2 beta or RC broke something, or deprecation notices appeared after upgrading.
- You want to adopt a 7.2 API deliberately (Secrets API, entity fields/actions, `data-wp-html`).

Do NOT use this skill as a general security or standards review — use `wp-client-repo-review`. For the 7.0 → 7.1 leg (always-iframed editor, jQuery UI 1.14.2, list-table row headers), use `wp-7-1-upgrade` first.

**7.2 is not released yet.** Every item in the references carries a status: **In Beta 1** (committed to trunk and in the 7.2 Beta 1 build), **Roadmap** (announced, not committed as of Beta 1 — may not ship), or **Not in 7.2**. Only Beta 1 items are actionable; treat Roadmap items as things to watch.

## Inputs required

- The repository root and its shape: plugin, theme, block theme, mu-plugin, site, or mixed.
- The WordPress version being upgraded **from**. From 7.1 this skill covers the whole leg; from 7.0 or earlier, run `wp-7-1-upgrade` first.
- Server, proxy, CDN, or WAF configuration that ships with the repo (`.htaccess`, nginx/Apache snippets, Cloudflare rules), if any.
- Whether the code ships editor or front-end JavaScript, Interactivity API directives, custom admin menu or toolbar styling, or custom `wp_kses()` allowlists.
- Whether a staging environment running the latest 7.2 beta or RC is available (`wp core update --version=7.2-beta1`, or the WordPress Beta Tester plugin).

## Procedure

Steps 1–4 are compatibility (things that break); step 5 is adoption.

1. **Run the deterministic scan first.**

    ```bash
    ./scripts/audit-wp-72.sh /path/to/repo
    ```

    It greps for every mechanically detectable 7.2 risk and prints findings by severity, each pointing to the reference section with the fix. It also scans server config files, not only code. Treat it as the worklist, not the whole story.

2. **Triage the blocking changes** (In Beta 1, no shim):
    - **Script/style concatenation is gone.** `load-scripts.php` and `load-styles.php` now answer **410 Gone**. `CONCATENATE_SCRIPTS` is ignored; the `print_head_scripts`, `print_footer_scripts`, `print_admin_styles`, and `print_late_styles` filters still fire but their return values are ignored. Server rules, CDN purges, or security plugins that special-case those URLs need updating.
    - **Interactivity API removals.** `data-wp-ignore` is gone; two-hyphen unique IDs (`data-wp-on--click--id`) are no longer recognized — use three (`data-wp-on--click---id`); `!` on a function value no longer works.
    - **`InspectorControls` `__experimentalGroup`** is removed (use `group`), and the `as` prop of `PluginMoreMenuItem`, `PluginSidebarMoreMenuItem`, and `PluginPreviewMenuItem` is ignored.
    - **"Howdy" moved.** The toolbar's `my-account` node title no longer contains "Howdy"; it moved into the `user-actions` dropdown. String replacements on the old title silently stop working.

    Detail, detection, and fixes: [references/breaking-changes.md](references/breaking-changes.md).

3. **Triage the silent behavior changes** — they don't error, which is why they reach production:
    - **`wp_kses()` runs on the HTML API by default.** Output markup is normalized, `<script>`/`<style>` contents are dropped even when allowlisted, malformed SVG/MathML/comments are rejected, and block delimiters are reserialized. `pre_kses` still runs. A `wp_kses_force_legacy_parser` opt-out exists as a stopgap.
    - **`WP_DEBUG` defaults to `true`** when `WP_ENVIRONMENT_TYPE` is `local` (and `WP_DEBUG` isn't defined) — notices now print on most local dev setups, which can corrupt AJAX/REST/JSON output there.
    - **Admin menu and toolbar icons are inline SVG**, not the Dashicons font, for core items. CSS that targets `::before` on core menu or toolbar icons stops applying.
    - **Dashboard:** Recent Comments moved out of the Activity widget into its own `dashboard_recent_comments` widget; Activity gains "On This Day".
    - **New `/random/` URLs and a public `random` query var**, sitemap XSL removal, `NO_BACKSLASH_ESCAPES` stripped from the DB session, application-password-created emails, `@wordpress/icons` now stroke-based (recolor with `color`, not `fill`), and `@wordpress/components` deprecations scheduled for removal in 7.4.

    Same reference: [references/breaking-changes.md](references/breaking-changes.md).

4. **Verify what a grep can't reach** on a 7.2 beta/RC site: admin screens load their scripts and styles (watch the Network panel for 410s); the login → dashboard → editor path works; custom admin menu and toolbar icons render and recolor with the admin color scheme; sanitized user content (comments, custom fields, form builders) round-trips unchanged through `wp_kses()`; Interactivity blocks hydrate and their events fire; `/random/` and any custom `random` routes behave as intended.

5. **Adopt new APIs only where they delete or harden existing code:**
    - API keys and tokens stored as plaintext options → **Secrets API** (`wp_set_secret()` / `wp_get_secret()`, or `wp_import_option_as_secret()`).
    - Custom DataViews field/action JS → server-side `wp_register_entity_fields()` / `wp_register_entity_actions()`.
    - `innerHTML` assignments in Interactivity stores → `data-wp-html` with `asDangerousHTML()` / `wp_interactivity_as_dangerous_html()`.
    - Hand-rolled text-shadow or gradient-text CSS → `typography.textShadow` / `background.backgroundClip` block supports.
    - Plugin admin menu icons → a registered namespaced icon name in `add_menu_page()`.

    Signatures and examples: [references/new-apis.md](references/new-apis.md).

6. **Record the outcome.** Separate must-fix items from optional adoption; mark each as verified on a 7.2 build versus reasoned statically; list any Roadmap item the codebase depends on so it is rechecked at RC1 and at release.

Sources, status of each item, and the release schedule: [references/sources.md](references/sources.md).

## Verification

- `scripts/audit-wp-72.sh` runs clean, or every remaining hit is annotated with why it is safe.
- With the code active on a 7.2 beta/RC: no 410 responses in the browser Network panel on admin screens or the login screen, and no PHP notices or `_deprecated_*` messages in `debug.log` during a normal admin session.
- Admin menu and toolbar items added by the code show their icons, and the icons follow the admin color scheme.
- Representative content sanitized by the code produces the expected HTML under the new `wp_kses()`, checked without `wp_kses_force_legacy_parser`.
- Interactivity API blocks hydrate, their directives run, and no development-mode warnings appear in the console.
- If the repo pins WordPress or declares "Tested up to", it is only bumped to 7.2 after the checks above pass on a 7.2 build.

## Failure modes

- **Treating Roadmap items as shipped.** Description List block, Sudo mode, `default_role` hardening, SameSite cookies, and React 19 are not in Beta 1. Don't write code against them yet.
- **Shipping `wp_kses_force_legacy_parser` as the fix.** It hides the difference instead of resolving it, and core calls it a stopgap. Use it only to bisect, then fix the input or the allowlist.
- **Filtering the legacy parser at the default priority.** Core adds `__return_false` at priority 10; a plugin's `__return_true` at 10 only wins if it is added later. Use a later priority when bisecting.
- **Restoring concatenation.** There is no supported way back; `CONCATENATE_SCRIPTS` is no longer read. Fix whatever relied on the combined URLs.
- **Assuming only core menu icons changed.** Plugin `dashicons-*` icons still use the font, but any CSS written against the core icons' `::before` glyphs breaks.
- **Reading local-only notices as production bugs.** The new local `WP_DEBUG` default surfaces existing notices; the bug may be old. Fix it anyway, but don't misattribute it to 7.2.
- **Testing only on Beta 1.** Changes keep landing until RC1 (17 November 2026). Re-run the scan and the checks on the latest RC before sign-off.

## Escalation

Ask for user input when:

- Server, CDN, or WAF configuration that references `load-scripts.php`/`load-styles.php` is managed outside the repo (host panel, Cloudflare dashboard).
- Content sanitized by `wp_kses()` changes in a way that is visible to site visitors, and deciding between fixing stored content and loosening an allowlist is an editorial call.
- A third-party plugin breaks on the 7.2 beta and has no fixed release — pinning WordPress, patching the vendor, or replacing the plugin is a business decision.
- The site already uses `/random/` (or a `random` query arg) for its own feature and must decide whether to disable core's via `wp_enable_random_content_redirect`.
- The work depends on a Roadmap item; confirm whether to wait for RC1 or build without it.
