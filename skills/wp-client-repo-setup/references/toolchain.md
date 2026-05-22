# Toolchain Reference

Canonical packages, config templates, and script conventions for the setup skill.

---

## PHP toolchain (Composer)

### Required packages

```bash
composer require --dev \
  squizlabs/php_codesniffer \
  wp-coding-standards/wpcs \
  dealerdirect/phpcodesniffer-composer-installer
```

Add PHP compatibility checks when the target PHP floor matters:

```bash
composer require --dev phpcompatibility/phpcompatibility-wp
```

### Minimum `phpcs.xml.dist` template

```xml
<?xml version="1.0"?>
<ruleset name="Project">
    <description>WordPress coding standards</description>

    <file>.</file>
    <exclude-pattern>vendor/</exclude-pattern>
    <exclude-pattern>node_modules/</exclude-pattern>

    <arg name="basepath" value="."/>
    <arg name="colors"/>
    <arg value="sp"/>

    <config name="minimum_wp_version" value="6.4"/>

    <rule ref="WordPress"/>
</ruleset>
```

Add a `<config name="testVersion" value="7.2-"/>` line and `<rule ref="PHPCompatibilityWP"/>` when PHP compatibility checks are enabled.

### Composer scripts

```json
"scripts": {
    "lint": "phpcs",
    "lint:fix": "phpcbf"
}
```

---

## JS/CSS toolchain (npm / pnpm)

### Required package

```bash
npm install --save-dev @wordpress/scripts
```

Add style linting when CSS or SCSS is present:

```bash
npm install --save-dev @wordpress/stylelint-config
```

### `package.json` scripts

```json
"scripts": {
    "lint:js": "wp-scripts lint-js",
    "lint:css": "wp-scripts lint-style",
    "format": "wp-scripts format",
    "format:check": "wp-scripts format --check"
}
```

Use the scripts that match file types actually present — do not add `lint:css` to a JS-only repo.

### `.stylelintrc.json` (when needed)

```json
{
    "extends": ["@wordpress/stylelint-config"]
}
```

---

## Notes on coexistence

- If the repo already uses a different PHPCS ruleset (`phpcs.xml` without `.dist`), extend it rather than replacing it.
- If a prettier or ESLint config already exists, check for conflicts before adding `@wordpress/scripts` — its bundled ESLint config may clash with existing rules.
- For `pnpm` repos, replace `npm install` with `pnpm add` and confirm `pnpm` is listed in `engines` or `.npmrc`.
