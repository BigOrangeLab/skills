---
name: wp-admin-ui
description: "Use when building or extending WordPress admin screens: choosing between legacy PHP/CSS patterns and modern React/DataViews, applying admin color scheme variables, mounting React in wp-admin, and using the correct SCSS tokens."
compatibility: "WordPress 6.9+. PHP 7.2+ for legacy screens; Node.js and @wordpress/scripts build tooling required for React screens."
license: MIT
metadata:
  author: georgestephanis
  version: "1.0"
  written: "2026-05-22"
  written_against:
    wordpress: "6.9"
    wordpress-components: "28.x"
---

# WordPress Admin UI

## When to use

Use this skill when:

- Building a new plugin settings page, custom admin screen, or wp-admin feature
- Extending or restyling an existing admin screen
- Migrating a legacy PHP/CSS admin page toward the React-based design system
- Debugging admin UI that breaks under color scheme changes or in RTL locales

Do NOT use for block editor or site editor UI — use `wp-block-development` or `wpds` for those. Do NOT use for front-end theme output.

## Inputs required

- What is being built: new screen, extension of existing screen, or migration
- Target WordPress version (determines which DataViews APIs and component features are available)
- Whether the screen is PHP-rendered only, React-driven, or mixed
- Whether the repo has an existing JS build pipeline (`@wordpress/scripts` or equivalent)

## Procedure

### 1. Choose the rendering approach

| Situation | Use |
|---|---|
| Simple settings form, no complex interactivity | Legacy PHP + `.wrap` / `.form-table` patterns |
| List, table, or grid of data objects | React + `@wordpress/dataviews` |
| Complex interactive UI, wizard, or custom controls | React + `@wordpress/components` |
| Extending an existing legacy screen | Match its approach; do not mix paradigms in one screen |

### 2a. Legacy PHP screen

Follow the canonical markup conventions so the screen feels native and notices inject correctly:

```html
<div class="wrap">
    <h1 class="wp-heading-inline">Screen Title</h1>
    <a href="#" class="page-title-action">Add New</a>
    <hr class="wp-header-end">
    <!-- notices are injected here by WordPress -->
    <form method="post" action="options.php">
        <!-- Settings API fields -->
        <table class="form-table" role="presentation">...</table>
        <p class="submit">
            <button type="submit" class="button button-primary">Save Changes</button>
        </p>
    </form>
</div>
```

For full class and element reference, see [references/legacy-patterns.md](references/legacy-patterns.md).

### 2b. React screen

1. In the PHP page callback, output only an empty mount point inside `.wrap`:
   ```php
   echo '<div class="wrap"><div id="my-feature-root"></div></div>';
   ```
   Do not render content in PHP that React will replace — it causes hydration flashes and screen-reader regressions.

2. Enqueue the required script handles. Minimum set for most screens:
   - `wp-element` (React + `createRoot`)
   - `wp-components` + its style handle (delivers the WPDS look)
   - `wp-i18n`
   - `wp-data` (if using the datastore)
   - `wp-dataviews` (if displaying list/table UI)

3. Mount with `createRoot` from `@wordpress/element`:
   ```js
   import { createRoot } from '@wordpress/element';
   import App from './app';
   const node = document.getElementById( 'my-feature-root' );
   if ( node ) { createRoot( node ).render( <App /> ); }
   ```

4. For list/table/grid UI, use `@wordpress/dataviews` instead of hand-rolling tables.

### 3. Apply color scheme–safe styles

**Always** use CSS custom properties for accent colors — never hardcode hex values:

```css
.my-button:focus {
    border-color: var(--wp-admin-theme-color, #3858e9);
    box-shadow: 0 0 0 var(--wp-admin-border-width-focus, 1.5px) var(--wp-admin-theme-color, #3858e9);
    outline: 2px solid transparent;
}
```

For the full set of exposed runtime CSS variables, see [references/css-variables.md](references/css-variables.md).

For SCSS authored inside core (spacing, typography, radii, elevation), use the token variables from `src/wp-admin/css/colors/_tokens.scss`. Do not consume them as runtime `var()` — they compile to static values. See [references/design-tokens.md](references/design-tokens.md).

### 4. Icons

Prefer inline SVGs via `@wordpress/icons`. Do not use `dashicons` in new screens.

```jsx
import { plus } from '@wordpress/icons';
import { Icon } from '@wordpress/components';
<Icon icon={ plus } />
```

### 5. RTL support

Prefer CSS logical properties so RTL works without a separate override file:

```css
/* prefer this */
margin-inline-start: 16px;
padding-inline-end: 8px;

/* over this */
margin-left: 16px; /* requires /*rtl:margin-right:16px*/ */
```

When `/*rtl:*/` comment overrides are unavoidable, model them on the existing patterns in `src/wp-admin/css/about.css`.

## Verification

- Screen renders without JS errors in the browser console
- Admin notices appear in the correct position (below `<hr class="wp-header-end">`) without layout shifts
- UI looks native under all nine color schemes (modern, fresh, light, blue, coffee, ectoplasm, midnight, ocean, sunrise) — switch at Users → Profile
- No hardcoded accent colors (`#3858e9`, `#2145e6`, etc.) in authored CSS
- Keyboard navigation reaches all interactive elements; focus rings are visible
- Layout is correct in RTL locales (`define('WPLANG', 'he_IL')` or equivalent)

## Failure modes

**Color scheme changes break the accent color**
— A hex color was hardcoded. Replace with `var(--wp-admin-theme-color, #3858e9)`.

**Admin notices appear at the top of the page, not inside `.wrap`**
— The `<hr class="wp-header-end">` marker is missing or misplaced. WordPress injects admin notices immediately after this element.

**React screen flashes unstyled content on load**
— PHP is outputting content inside the mount point that React replaces. Render only the empty `<div id="..."></div>` in PHP.

**SCSS tokens don't resolve at runtime (`var(--gray-700)` is undefined)**
— SCSS tokens are compiled to static values, not exposed as CSS custom properties. Use the 5 documented runtime variables for dynamic theming; use SCSS variables only in build-time stylesheets.

**Dashicons don't render for some users**
— The dashicons font may not be enqueued on all screens. Migrate to `@wordpress/icons` inline SVGs.

**Layout breaks on mobile or when the admin menu is collapsed**
— CSS is not accounting for the `.folded` / `.auto-fold` body classes or the `#wpadminbar` height (32px desktop / 46px mobile at ≤ 782px). Use the existing core CSS that handles these states rather than overriding.

## Escalation

- If a required component or token is missing from `@wordpress/components`, raise it upstream in the Gutenberg repo rather than creating a local workaround.
- For design decisions (layout, spacing, interaction patterns), consult the Figma WordPress Design System library before implementing.
- Storybook (living component docs): https://wordpress.github.io/gutenberg/
- Figma: https://www.figma.com/design/804HN2REV2iap2ytjRQ055/WordPress-Design-System
