# Toolchain Legacy Reference

Archived configuration patterns for older toolchain versions. Use when targeting a repo that cannot yet upgrade to the current stack.

---

## ESLint legacy config (`@wordpress/scripts` < 32)

`@wordpress/scripts` versions before 32 shipped ESLint v8, which uses the **legacy config** format (`.eslintrc.js`, `.eslintrc.cjs`, or `.eslintrc.json`).

**Minimal `.eslintrc.js` for a WordPress plugin (ESLint v8):**

```js
module.exports = {
	extends: ["plugin:@wordpress/eslint-plugin/recommended"],
	ignorePatterns: ["build/", "node_modules/", "vendor/"],
};
```

**With custom rule overrides:**

```js
module.exports = {
	extends: ["plugin:@wordpress/eslint-plugin/recommended"],
	ignorePatterns: ["build/", "node_modules/", "vendor/"],
	rules: {
		"no-console": "warn",
	},
};
```

### When to use the legacy format

Use `.eslintrc.js` only when the project is pinned to `@wordpress/scripts` < 32 **and** cannot upgrade. Running ESLint v10 (shipped in `@wordpress/scripts` 32+) against an `.eslintrc.*` file will emit deprecation warnings and in future versions may silently skip the config.

For projects that must support both old and new `@wordpress/scripts` in different environments, add a compatibility shim:

```js
// eslint.config.js — flat config, used by wp-scripts 32+
const { FlatCompat } = require("@eslint/eslintrc");
const compat = new FlatCompat();

module.exports = [
	...compat.extends("plugin:@wordpress/eslint-plugin/recommended"),
	{ ignores: ["build/**", "node_modules/**", "vendor/**"] },
];
```

This requires `@eslint/eslintrc` as a dev dependency. Avoid this unless the dual-support constraint is unavoidable.

---

## Pre-WP-7.0 PHPCS config values

### `minimum_wp_version`

For repos that still target **WordPress 6.x** (6.4–6.9):

```xml
<config name="minimum_wp_version" value="6.4"/>
```

This controls which functions WPCS flags as deprecated. Use the actual minimum supported WordPress version, not the installed version.

### PHP compatibility floor (WP 6.x era)

WordPress 6.x supported PHP 7.2.24 as its minimum. For repos targeting WP 6.x or mixed 6.x/7.0:

```xml
<config name="testVersion" value="7.2-"/>
<rule ref="PHPCompatibilityWP"/>
```

WordPress 7.0 raised the PHP floor to 7.4.0. If the repo has dropped WP 6.x support, update to `7.4-` and remove any PHP 7.2/7.3 workarounds.

---

## Repos pinned below `@wordpress/scripts` 36 (older Node.js)

Use this when the repo's Node.js cannot meet the 36+ floor (`^22.22.2 || ^24.15.0 || >=26`) and you pinned an older major per [toolchain.md](toolchain.md#nodejs-version-and-which-major-to-install).

- **35.x** (Node `^20.19.0 || >=22.13.0`): already on Stylelint 17, still on wp-prettier 3.0.3. `lint-style` uses the older static config detection, so it does not pick up ancestor or `~/.config/stylelint` configs.
- **34.x** (Node `>=18.12.0`): Stylelint 16 and wp-prettier 3.0.3. `@wordpress/stylelint-config` 25+ requires Stylelint 17, so pin `@wordpress/stylelint-config@^24` (peers: `stylelint` `^16.8.2`, `stylelint-scss` `^6.4.0`).
- For the `format:check` script on either, install `prettier@npm:wp-prettier@^3.0.3` and drop `cjs`, `cts`, `mjs`, and `mts` from the glob. Before 36, `wp-scripts format` does not expand directories to those extensions.
- When the repo later moves to Node 22.22+/24.15+, upgrading to 36+ brings the Prettier 3.9.6 reformat — see [Upgrading across 36.0.0](toolchain.md#upgrading-wordpressscripts-across-3600).

---

## Stylelint legacy config (`@wordpress/scripts` < 30)

Versions before 30 used `@wordpress/stylelint-config` without the `/scss-stylistic` variant. Stylistic rules were under the plain `stylelint-` prefix, not `@stylistic/`:

```json
{
	"extends": ["@wordpress/stylelint-config"],
	"rules": {
		"color-hex-length": "long"
	}
}
```

In `@wordpress/scripts` 30+, the default extends `@wordpress/stylelint-config/scss-stylistic`. Stylistic overrides now require the `@stylistic/` prefix — see `toolchain.md`.
