---
name: wp-7-1-upgrade
description: "Use when auditing or updating a WordPress plugin, theme, or site codebase for WordPress 7.1 compatibility — the always-iframed post editor, removed @wordpress/components APIs, changed list-table markup, jQuery UI 1.14.2, and changed filter defaults — or when adopting 7.1's new APIs (SVG icons, tooltips, Abilities API filters, responsive and pseudo style states, new block supports)."
compatibility: "WordPress 7.1 (released August 2026), Gutenberg 23.6. Targets plugins, themes, block themes, mu-plugins, and full site repos. Requires filesystem access to PHP, JS, CSS, and block.json/theme.json source. Guidance for 7.0 and earlier is retained where behavior differs by version."
license: MIT
metadata:
    author: georgestephanis
    version: "1.0"
    written: "2026-08-12"
    written_against:
        wordpress: "7.1"
        gutenberg: "23.6"
        wp-components: "33.1.0"
        jquery-ui: "1.14.2"
---

# WordPress 7.1 Upgrade

## When to use

Use this skill when a codebase needs to be checked against, or updated for, WordPress 7.1. Typical triggers:

- A site or client repo is about to move to 7.1 and needs a compatibility pass.
- A plugin or theme extends the block editor, admin list tables, the toolbar, or the media library.
- Editor UI broke after a 7.1 upgrade (blank canvas, dead event handlers, misplaced controls).
- Console deprecation warnings appeared from `@wordpress/*` packages.
- You want to adopt a 7.1 API deliberately (SVG icons, tooltips, Abilities filters, responsive styles).

Do NOT use this skill as a general security or standards review — use `wp-client-repo-review` for that. Do NOT use it for WordPress.org submission checks — use `wp-plugin-directory-review`.

## Inputs required

- The repository root, and the repo shape: plugin, theme, block theme, mu-plugin, site, or mixed.
- The WordPress version being upgraded **from**. 7.0 → 7.1 is the common path; jumping from 6.x means earlier dev notes also apply and this skill is only the last leg.
- Whether the code ships editor JavaScript (`enqueue_block_editor_assets`, `@wordpress/scripts` build, `block.json` with `editorScript`).
- Whether the code registers custom blocks, and their `apiVersion`.
- Whether the code customizes admin list tables, the admin bar, or the media library.
- Whether a staging environment running 7.1 is available for verification.

## Procedure

Work in this order. Steps 1–3 are compatibility (things that break); steps 4–5 are adoption (things you may now use).

1. **Run the deterministic scan first.**

    ```bash
    ./scripts/audit-wp-71.sh /path/to/repo
    ```

    It greps for every mechanically detectable 7.1 risk and prints findings grouped by severity, each with the reference section that explains the fix. Treat its output as the worklist, not the whole story — it cannot see runtime behavior.

2. **Triage the blocking breakages.** These change behavior with no deprecation shim:
    - **The post editor is now always iframed.** Any editor JS that reaches for the global `document` or `window` to touch the canvas is now looking at the wrong document. This is the single largest source of 7.1 breakage.
    - **Removed from `@wordpress/components`:** the `Navigation` component family and `__experimentalApplyValueToSides`. Both were deprecated in 6.8 and are now gone.
    - **`__next40pxDefaultSize` is inert.** Form controls are 40px unconditionally; passing `false` no longer opts out.
    - **Post list table row headers moved.** The `<th scope="row">` moved from the checkbox column to the title column. CSS and JS selectors assuming `th.check-column` or `td.column-title` break.
    - **jQuery UI 1.14.2** removed `$.fn._form`, `$.ui.ie`, `$.ui.safeActiveElement`, and `$.ui.safeBlur`.

    Full detail, before/after markup, and fixes: [references/breaking-changes.md](references/breaking-changes.md).

3. **Triage the silent behavior changes.** These do not error — they quietly do something different, which makes them the ones that reach production:
    - `media_library_infinite_scrolling` now defaults to `true`, with a new per-user opt-out.
    - `notify_post_author` now receives an approval-aware default and its return value is final; `__return_true` callbacks will now email for spam and trashed comments.
    - `getEntityRecords()` returns **all** records for non-paginated entities instead of silently slicing to 10.
    - The Navigation block no longer propagates font-size to child items.
    - Block-level preset classes dropped to root-level CSS specificity via `:where()`.
    - `pasteHandler()` switched its Markdown parser from showdown to marked.
    - `@wordpress/nux` is now a no-op; `@wordpress/reusable-blocks` logs deprecations.
    - Client-side media processing sends `Document-Isolation-Policy` on editor screens, which affects cross-origin scripts and browser-side fetches of remote media.

    Same reference: [references/breaking-changes.md](references/breaking-changes.md).

4. **Verify the things a grep cannot reach.** Load the editor on 7.1 and check, in this order: the block canvas renders and blocks are selectable; custom inspector controls appear and persist values; admin bar nodes appear correctly in the Site Editor (the toolbar is now persistent there); list table row actions and bulk selection still work; media uploads complete.

5. **Adopt new APIs only where they replace something the repo already hand-rolls.** 7.1 adds real surface area, and the highest-value adoptions are the ones that delete custom code:
    - Custom SVG icon plumbing → `wp_register_icon()` / `wp_get_icon()`.
    - `title` attributes and pointer-only help text → `wp_get_tooltip()` / `wp_get_toggletip()`.
    - Manual `array_filter()` over abilities → `wp_get_abilities( $args )`.
    - Custom media-query CSS for block styles → `@mobile` / `@tablet` in `theme.json`.
    - Custom hover/focus CSS for buttons and nav links → `:hover` / `:focus` style states.
    - Hardcoded admin UI colors → `wp-theme` design tokens and `ThemeProvider`.

    Signatures, arguments, and worked examples: [references/new-apis.md](references/new-apis.md).

6. **Record the outcome.** Separate must-fix compatibility items from optional adoption, and note which items were verified on 7.1 versus reasoned about statically.

Source dev notes for every claim in this skill: [references/dev-notes-index.md](references/dev-notes-index.md).

## Verification

- `scripts/audit-wp-71.sh` runs clean, or every remaining hit is annotated with why it is safe.
- The block editor loads on 7.1 with the plugin/theme active, and custom blocks are insertable, selectable, and editable.
- Editor JS event handlers still fire when the target element is inside the canvas iframe.
- No `@wordpress/*` deprecation warnings in the browser console during a normal edit session.
- Admin list tables render with working bulk-select, row actions, and any custom columns.
- Admin bar nodes appear (or are deliberately hidden) in both the Post Editor and Site Editor.
- If the repo ships `theme.json`, `wp theme.json validate` or an equivalent schema check passes against the 7.1 schema.
- Front-end output for Navigation blocks and gradient/preset-styled blocks is visually unchanged, or the change is understood and accepted.

## Failure modes

- **Assuming grep coverage is complete.** The iframe boundary, cross-origin isolation, and preset specificity issues are runtime behaviors; a static scan finds candidates, not confirmations.
- **Fixing the iframe by disabling the iframe.** There is no supported opt-out in 7.1. Code that special-cases the non-iframed editor needs the fix, not a flag.
- **Swapping `document` for `ownerDocument` blindly.** It only works from an element already inside the canvas; calling it on an admin-side node yields the same wrong document.
- **Removing `__next40pxDefaultSize` while leaving `size="__unstable-large"`.** The `size` prop is deprecated on several of the same components and its removal is the other half of the change.
- **Treating ability filtering as authorization.** `wp_get_abilities()` filters discovery; `permission_callback` is still the only security boundary.
- **Adding `:where()`-defeating CSS.** Raising specificity to win back the old block-level preset behavior reintroduces the responsive-states bug 7.1 fixed.
- **Testing only in Chrome.** Client-side media processing is Chromium-only and falls back silently elsewhere, so a Chrome-only test exercises a different code path than most non-Chrome visitors.
- **Bumping "Tested up to" without running the editor.** The most common 7.1 breakages produce a working admin and a broken canvas.

## Escalation

Ask for user input when:

- The repo depends on a third-party plugin that breaks under 7.1 and has no compatible release — the choice to pin WordPress, patch the vendor, or drop the plugin is a business decision.
- Editor JS is minified or vendored without source, so the iframe audit cannot be completed.
- Restoring the pre-7.1 Navigation font-size or preset specificity behavior is possible but perpetuates a known upstream bug; confirm before adding the compatibility shim.
- Cross-origin isolation conflicts with an existing ad, analytics, or page-builder integration on editor screens, and disabling client-side media processing site-wide is the tradeoff.
- The upgrade path starts before 7.0, in which case earlier field guides need to be worked through first.
