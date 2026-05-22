---
name: wp-client-repo-setup
description: "Use when adopting an existing WordPress client repository and adding or aligning Composer and NPM tooling for WPCS/PHPCS, PHP compatibility checks, and @wordpress/scripts linting or formatting."
compatibility: "Existing WordPress plugins, themes, mu-plugins, or site repos. Requires PHP 7.2+ target environment; Composer and/or Node.js must be installable."
license: MIT
metadata:
  author: georgestephanis
  version: "1.0"
  written: "2026-05-22"
  written_against:
    wordpress: "6.9"
    phpcs: "3.x"
    wpcs: "3.x"
    wordpress-scripts: "30.x"
---

# WordPress Client Repo Setup

## When to use

Use this skill when an existing WordPress plugin, theme, mu-plugin, or site repository needs development tooling added or normalized without doing a large behavioral rewrite.

## Inputs required

- The repository root
- Whether the repo is a plugin, theme, mu-plugin, site, or mixed codebase
- Existing `composer.json`, `package.json`, lockfiles, CI config, and lint or format scripts
- The dominant file types present: PHP, JS, JSX, CSS, SCSS
- The preferred package manager if one is already in use: `npm`, `pnpm`, or `yarn`
- The target PHP support range if PHP compatibility checks matter

## Procedure

1. Inspect the repo before proposing changes.
   - Detect whether Composer and NPM already exist.
   - Reuse the existing package manager and script naming style.
   - Extend existing lint or format scripts rather than replacing them.

2. Add only the tooling that matches the code present.
   - If PHP exists, prefer Composer-based PHP tooling.
   - If JS, JSX, CSS, or SCSS exists, prefer NPM-based front-end tooling.
   - Do not add NPM to a PHP-only repo unless the task explicitly needs it.

3. For PHP, prefer a minimal WordPress-oriented toolchain.
   - Add `squizlabs/php_codesniffer`.
   - Add `wp-coding-standards/wpcs`.
   - Add `dealerdirect/phpcodesniffer-composer-installer`.
   - Add `phpcompatibility/phpcompatibility-wp` when target hosting constraints or support promises justify it.
   - Add `phpcs.xml.dist` if the repo does not already define a ruleset.
   - See [references/toolchain.md](references/toolchain.md) for the canonical `phpcs.xml.dist` template and `composer.json` script conventions.

4. For JS, JSX, CSS, and SCSS, prefer WordPress-native tooling.
   - Add `@wordpress/scripts` for linting and formatting when it fits the project.
   - Add `@wordpress/stylelint-config` when style linting is needed.
   - Use scripts such as `lint:js`, `lint:css`, `format`, and `format:check` only if they fit the repo's conventions.
   - See [references/toolchain.md](references/toolchain.md) for package install commands, script templates, and coexistence notes.

5. Keep adoption incremental.
   - Put new dependencies in development-only sections.
   - Avoid broad formatting churn unless the task explicitly asks for normalization.
   - Explain any exclusions or relaxations for legacy code.

## Verification

- The repo still uses its existing package manager unless a change was explicitly requested.
- Composer and NPM config changes are minimal and development-only.
- Added scripts execute successfully or fail only on genuine code issues.
- Lint or format commands match the file types actually present in the repo.
- The final summary explains why each tool was added.
- A team member can run `composer install && vendor/bin/phpcs` (or the equivalent npm script) from a fresh checkout without additional configuration.

## Failure modes

- Replacing an existing standards stack instead of extending it
- Adding overlapping formatters or linters that fight each other
- Introducing NPM to a PHP-only legacy repo for no real gain
- Running a repo-wide formatter on untouched legacy code without approval
- Enabling PHP compatibility rules without confirming the target PHP range

## Escalation

Ask for user input when:

- The repo already has a strongly opinionated standards stack and the desired outcome is unclear.
- Tooling changes would create massive style churn.
- The PHP support matrix or package-manager choice is ambiguous.
- CI expectations require a stricter or broader toolchain than the local repo currently implies.