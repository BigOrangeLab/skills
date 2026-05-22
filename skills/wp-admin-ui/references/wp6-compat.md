# WordPress 6.x Compatibility Notes

Use this reference when building or maintaining admin UI that must support WordPress 6.x, or when migrating 6.x code to WP 7.0.

---

## PHP minimum

WordPress 6.x supported PHP 7.2.24 as the minimum. WordPress 7.0 raised this to **PHP 7.4.0**. Code using 7.4+ features (typed properties, arrow functions, `array_is_list()`) is safe in WP 7.0 but must be avoided or gated in 6.x–compatible code.

---

## Post editor CSS enqueuing

**WP 6.x behaviour:** The post editor was only iframed for blocks using API v3. API v2 blocks forced a non-iframed editor, and CSS enqueued via `admin_enqueue_scripts` would reach the editor UI.

**WP 7.0:** The editor is always iframed regardless of block API version.

For code that must run on both versions, enqueue on both hooks:

```php
// Reaches editor in WP 6.x (non-iframed) and outer admin in WP 7.0
add_action( 'admin_enqueue_scripts', 'my_plugin_admin_styles' );

// Reaches editor in WP 7.0 (always iframed)
add_action( 'enqueue_block_editor_assets', 'my_plugin_editor_styles' );
```

For WP 7.0-only code, use `enqueue_block_editor_assets` exclusively for editor-targeting CSS.

---

## DataViews grouping API

**WP 6.x:** `groupByField` accepted a string (field key):

```js
<DataViews
  groupByField="status"
  ...
/>
```

**WP 7.0:** `groupByField` is removed. Use `groupBy` (object):

```js
<DataViews
  groupBy={{ field: 'status', direction: 'asc', labelVisibility: 'always' }}
  ...
/>
```

Migration: replace any `groupByField="<key>"` with `groupBy={{ field: '<key>' }}`.

---

## Default admin color scheme

**WP 6.x:** The default color scheme was "Default" (the classic grey scheme). "Modern" (blue/indigo) was opt-in.

**WP 7.0:** "Modern" is the default for all users. "Fresh" (the classic blue, formerly called "Default") is now the second option.

Visual impact: any UI that assumed the old grey scheme as the baseline may look different out of the box. No markup changes required — the change is CSS-only and existing CSS custom property consumption is unaffected.

---

## `wp-base-styles` stylesheet handle (WP 7.0+)

WP 7.0 introduced `wp-base-styles` as a consolidated stylesheet that delivers admin CSS custom properties (the five `--wp-admin-*` variables) to both wp-admin and the block editor iframe. In WP 6.x, these variables were only reliably available inside wp-admin, not the editor iframe.

Code that needs `--wp-admin-theme-color` inside the editor iframe can now depend on `wp-base-styles` being enqueued.
