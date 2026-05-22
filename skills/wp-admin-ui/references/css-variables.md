# Runtime CSS Custom Properties

These are the **only** CSS custom properties that wp-admin exposes at runtime. Everything else (gray scale, spacing, typography, elevations) lives as SCSS in `src/wp-admin/css/colors/_tokens.scss` and compiles to static values — do not try to consume those as `var()`.

Color scheme stylesheets override these five variables. Your UI should consume them so it respects the user's chosen scheme.

| Variable | Modern default | Use for |
|---|---|---|
| `--wp-admin-theme-color` | `#3858e9` | Primary accent — link color, primary button bg, active states |
| `--wp-admin-theme-color--rgb` | `56, 88, 233` | Tinted backgrounds: `rgba(var(--wp-admin-theme-color--rgb), 0.04)` |
| `--wp-admin-theme-color-darker-10` | `#2145e6` | Hover state on primary actions |
| `--wp-admin-theme-color-darker-20` | `#183ad6` | Active/pressed state, darker accents |
| `--wp-admin-border-width-focus` | `1.5px` | Focus ring width |

## Canonical focus pattern

Used throughout core (`src/wp-includes/css/buttons.css`, `src/wp-includes/css/editor.css`):

```css
.my-element:focus {
    border-color: var(--wp-admin-theme-color, #3858e9);
    box-shadow: 0 0 0 var(--wp-admin-border-width-focus, 1.5px) var(--wp-admin-theme-color, #3858e9);
    outline: 2px solid transparent;
}
```

Always include the fallback value (`#3858e9`) so styles remain sensible if a stylesheet loads before the color scheme is applied.

## Color schemes

The nine registered schemes (in display order): **modern** (default since WP 7.0), **fresh** (classic blue), **light**, **blue**, **coffee**, **ectoplasm**, **midnight**, **ocean**, **sunrise**.

Each scheme compiles to a stylesheet that overrides the five properties above plus admin menu/bar selectors. Source: `src/wp-admin/css/colors/`, one folder per scheme. Registration and ordering: `src/wp-admin/includes/misc.php`.

Never hardcode the modern-scheme hex values (`#3858e9`, `#2145e6`, `#183ad6`) in new CSS.
