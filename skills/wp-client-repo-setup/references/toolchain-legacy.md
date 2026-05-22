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

Use `.eslintrc.js` only when the project is pinned to `@wordpress/scripts` < 32 **and** cannot upgrade. Running ESLint v9 (shipped in `@wordpress/scripts` 32+) against an `.eslintrc.*` file will emit deprecation warnings and in future versions may silently skip the config.

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
