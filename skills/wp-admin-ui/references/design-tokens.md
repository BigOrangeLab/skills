# SCSS Design Tokens

Canonical file: `src/wp-admin/css/colors/_tokens.scss`

These are **SCSS variables** — they compile to static values and are not exposed as CSS custom properties at runtime. Use them only in build-time stylesheets. For runtime theming, use the CSS custom properties documented in [css-variables.md](css-variables.md).

For React components, prefer the equivalent token from `@wordpress/components` (`SPACE`, `__experimentalGetVariableFromKey`, etc.) rather than hardcoding values.

---

## Spacing (4px base grid)

| Token           | Value | Use for                 |
| --------------- | ----- | ----------------------- |
| `$grid-unit-05` | 4px   | Micro gap, icon padding |
| `$grid-unit-10` | 8px   | Tight spacing           |
| `$grid-unit-15` | 12px  |                         |
| `$grid-unit-20` | 16px  | Standard padding        |
| `$grid-unit-30` | 24px  | Section gap             |
| `$grid-unit-40` | 32px  | Large section gap       |
| `$grid-unit-50` | 40px  |                         |
| `$grid-unit-60` | 48px  |                         |
| `$grid-unit-70` | 56px  |                         |

## Border radius

| Token          | Value  | Use for                  |
| -------------- | ------ | ------------------------ |
| `$radius-xs`   | 1px    |                          |
| `$radius-s`    | 2px    | Inputs, buttons          |
| `$radius-m`    | 4px    | Focus rings              |
| `$radius-l`    | 8px    | Cards, dashboard widgets |
| `$radius-full` | 9999px | Pills, avatars           |

Note: post editor metaboxes intentionally use radius 0.

## Gray scale

`$gray-100` (#f0f0f0, page background) → `$gray-200` → `$gray-300` → `$gray-400` → `$gray-600` → `$gray-700` → `$gray-800` → `$gray-900` (#1e1e1e, primary text).

There is intentionally no `$gray-500`.

## Semantic / alert colors (theme-independent)

| Token              | Use                       |
| ------------------ | ------------------------- |
| `$alert-yellow`    | Warning text              |
| `$alert-yellow-bg` | Warning notice background |
| `$alert-green`     | Success text              |
| `$alert-green-bg`  | Success notice background |
| `$alert-red`       | Error text                |
| `$alert-red-bg`    | Error notice background   |
| `$alert-blue`      | Info text                 |
| `$alert-blue-bg`   | Info notice background    |

## Button heights

| Token                    | Value | Context                           |
| ------------------------ | ----- | --------------------------------- |
| `$button-height-default` | 40px  | New default ("next-default-40px") |
| `$button-height-compact` | 32px  | Dense toolbars                    |
| `$button-height-small`   | 24px  | Very tight contexts               |

## Input borders

| Token                         | Value |
| ----------------------------- | ----- |
| `$input-border-width-default` | 1px   |
| `$input-border-width-focus`   | 1.5px |

## Typography

| Token                  | Value | Use             |
| ---------------------- | ----- | --------------- |
| `$font-size-xs`        | 11px  |                 |
| `$font-size-s`         | 12px  |                 |
| `$font-size-m`         | 13px  | Base body       |
| `$font-size-l`         | 15px  |                 |
| `$font-size-xl`        | 20px  | Screen headings |
| `$font-weight-regular` | 400   |                 |
| `$font-weight-medium`  | 500   |                 |

## Elevation (shadows)

`$elevation-xs` → `$elevation-s` → `$elevation-m` → `$elevation-l`

Use for: cards, popovers, modals (ascending z-depth).

## Card padding

`$card-padding-xs` (8px) through `$card-padding-lg-h` / `$card-padding-lg-v`. Dashboard widgets use `$radius-l` (8px).
