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

**Security:** WPCS 3.4.1 fixes an arbitrary command execution issue in the `WordPress.WP.EnqueuedResourceParameters` sniff, part of the default `WordPress` ruleset this skill scaffolds. Pin `wp-coding-standards/wpcs` to `^3.4.1` (not just `^3.3`) — this matters even for internal repos, since PHPCS is often run against contributor branches or PRs.

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

    <config name="minimum_wp_version" value="7.0"/>

    <rule ref="WordPress"/>
</ruleset>
```

Set `minimum_wp_version` to the project's actual minimum supported WordPress version. This governs deprecation sniffs (`WordPress.WP.DeprecatedFunctions`, `.DeprecatedParameters`, `.Capabilities`, etc.).

When PHP compatibility checks are enabled, add:

```xml
<config name="testVersion" value="7.4-"/>
<rule ref="PHPCompatibilityWP"/>
```

Set `testVersion` to match the project's minimum supported PHP version (WordPress 7.0 dropped PHP 7.2/7.3; the new floor is PHP 7.4). For projects still supporting PHP 7.2, use `7.2-`. See [toolchain-legacy.md](toolchain-legacy.md) for pre-WP-7.0 config values.

### Composer scripts

```json
"scripts": {
    "lint": "phpcs",
    "lint:fix": "phpcbf"
}
```

---

## JS/CSS toolchain (npm / pnpm)

### Node.js version and which major to install

Check the Node.js version the repo and its CI actually run (`node --version`, `.nvmrc`, `.node-version`, `engines.node`, CI `setup-node`) before installing. Recent `@wordpress/scripts` majors raised the floor:

| `@wordpress/scripts` | `engines.node`                     | Bundled Prettier / Stylelint |
| -------------------- | ---------------------------------- | ---------------------------- |
| 36.x–37.x (current)  | `^22.22.2 \|\| ^24.15.0 \|\| >=26` | wp-prettier 3.9.6 / 17       |
| 35.x                 | `^20.19.0 \|\| >=22.13.0`          | wp-prettier 3.0.3 / 17       |
| 34.x                 | `>=18.12.0`                        | wp-prettier 3.0.3 / 16       |

If the repo's Node does not satisfy the current major, pin the newest major it does satisfy (for example `npm install --save-dev @wordpress/scripts@^35`) rather than bumping the repo's Node version as a side effect of a tooling pass. Raise the Node upgrade separately.

### Required package

```bash
npm install --save-dev @wordpress/scripts
```

Add style linting when CSS or SCSS is present:

```bash
npm install --save-dev @wordpress/stylelint-config
```

`@wordpress/stylelint-config` 27 declares `stylelint` `^17.14.1` and `stylelint-scss` `^7.2.0` as peers. `@wordpress/scripts` 36.1+ supplies both; on older majors, or if the install reports an unmet `stylelint-scss` peer, add it as a dev dependency too.

### `package.json` scripts

```json
"scripts": {
    "lint:js": "wp-scripts lint-js",
    "lint:css": "wp-scripts lint-style",
    "format": "wp-scripts format",
    "format:check": "prettier --check \"**/*.{cjs,cts,js,json,jsx,mjs,mts,ts,tsx,yml,yaml}\""
}
```

Use the scripts that match file types actually present — do not add `lint:css` to a JS-only repo.

### Format checking (`format:check`)

`wp-scripts format` always runs Prettier with `--write` and does not forward `--check` (verified in 34.0.0 and 37.0.0). A `"format:check": "wp-scripts format --check"` script therefore **rewrites files and exits 0** — it can never fail CI. If an existing repo has that script, replace it.

Run Prettier directly for the check, against the same config and file types that `wp-scripts format` uses:

```bash
npm install --save-dev "prettier@npm:wp-prettier@^3.9.6" @wordpress/prettier-config
```

- Add `"prettier": "@wordpress/prettier-config"` to `package.json` (or keep an existing Prettier config). `wp-scripts format` detects that key and uses it, so both commands share one config. Without a project config, bare `prettier` would fall back to Prettier's defaults, not WordPress style.
- Match the `wp-prettier` version to the one the installed `@wordpress/scripts` bundles (3.9.6 for 36+, 3.0.3 for 34–35) so both commands produce identical output.
- Add a `.prettierignore` listing `build` and `vendor` (plus `pnpm-lock.yaml` for pnpm repos). `wp-scripts format` falls back to a bundled ignore file; bare `prettier` does not.
- Keep the extension glob above. `wp-scripts format` only formats those extensions, while `prettier --check .` would also flag CSS, SCSS, and Markdown that `format` never fixes. On `@wordpress/scripts` < 36, which does not format `.cjs`/`.mjs`/`.cts`/`.mts`, drop those four from the glob.

### Upgrading `@wordpress/scripts` across 36.0.0

36.0.0 moved the bundled Prettier from wp-prettier 3.0.3 to 3.9.6, so `wp-scripts format` output changes on code that was already formatted. When bumping an existing repo past 35.x, run `format:check` first and report the scope of the reformat; commit any reformatting separately from the version bump, and only with approval for a repo-wide change.

### ESLint configuration (`@wordpress/scripts` 32+)

`@wordpress/scripts` 32.x upgraded to ESLint v10 and dropped support for the legacy `.eslintrc.*` format. Custom ESLint config must use the **flat config** format (`eslint.config.js`, `.cjs`, or `.mjs`).

**Minimal `eslint.config.js` for a WordPress plugin:**

```js
const wpPlugin = require("@wordpress/eslint-plugin");

module.exports = [
	...wpPlugin.configs.recommended,
	{
		ignores: ["build/**", "node_modules/**", "vendor/**"],
	},
];
```

If the repo has an existing `.eslintrc.js` or `.eslintrc.cjs`, migrate it — `wp-scripts lint-js` will emit deprecation warnings and may silently ignore the old file in future versions.

For projects that must support `@wordpress/scripts` <32 alongside 32+, see [toolchain-legacy.md](toolchain-legacy.md).

### `.stylelintrc.json`

Commit a project `.stylelintrc.json` whenever you add `lint:css`. As of `@wordpress/scripts` 36.0.0, `lint-style` finds config with Stylelint's own resolver, which also searches **ancestor directories and `~/.config/stylelint`**. A repo with no local config can silently pick up an unrelated config from a parent folder or a developer's home directory instead of the bundled default, so lint results differ between machines and CI.

`@wordpress/scripts` 30+ uses `@wordpress/stylelint-config/scss-stylistic` as its default (includes stylistic rules). Stylistic rule overrides now use the `@stylistic/` prefix:

```json
{
	"extends": ["@wordpress/stylelint-config/scss-stylistic"],
	"rules": {
		"@stylistic/color-hex-length": "long"
	}
}
```

### Formatting config detection (`@wordpress/scripts` 32.5+)

`wp-scripts format` and `wp-scripts lint-style` only fall back to the bundled default config when the repo has no config of its own. As of 32.5.0 that detection also covers `.cjs`/`.mjs` stylelint configs, and as of 34.0.0 it covers `prettier.config.mjs` in addition to `.prettierrc*` and `prettier.config.js`. If a repo already has formatting config in one of these files, `wp-scripts` will use it instead of silently applying its bundled default — don't assume the bundled config is in effect without checking for these files first.

---

## Stack profiles

Profiles are opt-in (see step 2 of the procedure). With no profile selected, the PHP and JS/CSS sections above are the default path and apply unchanged.

| Profile         | PHP tooling          | JS/CSS tooling                | Pick when                                                                                                         |
| --------------- | -------------------- | ----------------------------- | ----------------------------------------------------------------------------------------------------------------- |
| `phpcs-only`    | WPCS/PHPCS           | none                          | The team wants PHP standards only, even though the repo ships JS or CSS                                           |
| `phpcs-phpstan` | WPCS/PHPCS + PHPStan | none                          | The repo wants static analysis, or already has `phpstan.neon(.dist)` or `phpstan/phpstan`                         |
| `pnpm-first`    | WPCS/PHPCS           | `@wordpress/scripts` via pnpm | The repo has opted into pnpm (`packageManager` or `engines.pnpm` in `package.json`, or pnpm settings in `.npmrc`) |

Every profile uses the [required Composer packages](#required-packages), the [`phpcs.xml.dist` template](#minimum-phpcsxmldist-template), and the [Composer scripts](#composer-scripts) above. Add `phpcompatibility/phpcompatibility-wp` under the same conditions as the default path.

### `phpcs-only`

No `package.json` changes, even when JS or CSS files are present. PHPCS 3 also scans `.js` and `.css` files by default, so restrict it to PHP in `phpcs.xml.dist` or it will report on the front-end files this profile deliberately leaves alone:

```xml
<arg name="extensions" value="php"/>
```

List the front-end files left unlinted in the final summary so the omission is a recorded decision, not an oversight.

### `phpcs-phpstan`

Everything in `phpcs-only` (including the `extensions` argument), plus PHPStan with WordPress stubs. Allow the extension installer plugin first so Composer does not block it in non-interactive runs:

```bash
composer config allow-plugins.phpstan/extension-installer true
composer require --dev \
  phpstan/phpstan \
  phpstan/extension-installer \
  szepeviktor/phpstan-wordpress
```

`szepeviktor/phpstan-wordpress` 2.x requires PHPStan 2.x and pulls in `php-stubs/wordpress-stubs`; `phpstan/extension-installer` registers it automatically, so no `includes:` entry is needed for it.

Minimum `phpstan.neon.dist`:

```neon
parameters:
	level: 5
	paths:
		- .
	excludePaths:
		- vendor
		- node_modules (?)
```

- Narrow `paths` to the plugin or theme source directories when the repo root holds more than code.
- The `(?)` suffix marks an exclude path as optional. Without it PHPStan 2 errors when the directory does not exist, which is the normal state for `node_modules` in a repo with no JS tooling.
- Set `phpVersion` (for example `phpVersion: 70400`) to the repo's minimum supported PHP version when it is below the PHP that runs the analysis.
- On legacy code, keep adoption incremental: start at a level the code already passes, or record the existing errors with `vendor/bin/phpstan analyse --generate-baseline` and add `includes: [phpstan-baseline.neon]`, rather than raising the level until the build fails.

Add an analysis script alongside the PHPCS scripts. The WordPress stubs are large, so raise PHPStan's default memory limit:

```json
"scripts": {
    "lint": "phpcs",
    "lint:fix": "phpcbf",
    "analyse": "phpstan analyse --memory-limit=1G"
}
```

### `pnpm-first`

Confirm the repo has opted into pnpm before adding anything. Any one check must pass; if none does, stop and ask rather than switching package managers:

```bash
node -p "require('./package.json').packageManager ?? 'not set'"   # e.g. pnpm@10.x (corepack)
node -p "require('./package.json').engines?.pnpm ?? 'not set'"
cat .npmrc
```

Install with pnpm and keep `pnpm-lock.yaml` as the only lockfile. Do not create a `package-lock.json`:

```bash
pnpm add --save-dev @wordpress/scripts
pnpm add --save-dev @wordpress/stylelint-config   # only when CSS or SCSS is present
```

Use the [`package.json` scripts](#packagejson-scripts) from the JS/CSS section unchanged and run them with `pnpm run <script>`. CI should install with `pnpm install --frozen-lockfile`.

- `wp-scripts format` formats `.yml`/`.yaml` files, so it rewrites `pnpm-lock.yaml` unless that file is ignored. Add it to the project's `.prettierignore`. A project `.prettierignore` replaces the bundled one (which ignores `build` and `vendor`), so list those too:

    ```text
    build
    vendor
    pnpm-lock.yaml
    ```

- `prettier` and `@wordpress/prettier-config` from [Format checking](#format-checking-formatcheck) must be direct dev dependencies here: pnpm does not hoist `@wordpress/scripts`' copy, so a bare `prettier` call would not resolve otherwise.
- Before `@wordpress/scripts` 36.1.0, `build` and `start` could fail to find the default browserslist config when dependencies are not hoisted (pnpm, or npm with `install-strategy=linked`). The lint and format scripts above are unaffected; if the repo also builds with `wp-scripts`, use 36.1.0 or later.
- pnpm 10 skips dependency build scripts by default and prints an `approve-builds` notice after installing `@wordpress/scripts`. `wp-scripts lint-js`, `lint-style`, and `format` work without approving them, so do not approve builds just to silence the notice.

---

## Notes on coexistence

- If the repo already uses a different PHPCS ruleset (`phpcs.xml` without `.dist`), extend it rather than replacing it.
- If a prettier or ESLint config already exists, check for conflicts before adding `@wordpress/scripts` — its bundled ESLint config may clash with existing rules.
- For `pnpm` repos, use the [`pnpm-first` profile](#pnpm-first): replace `npm install` with `pnpm add` and confirm the repo has opted into pnpm via `packageManager`, `engines`, or `.npmrc`.
