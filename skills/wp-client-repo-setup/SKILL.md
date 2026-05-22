---
name: wp-client-repo-setup
description: "Use when adopting an existing WordPress client repository and adding or aligning Composer and NPM tooling for WPCS/PHPCS, PHP compatibility checks, and @wordpress/scripts linting or formatting."
compatibility: "Claude Code and Claude desktop workflows using ~/.claude/skills"
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

4. For JS, JSX, CSS, and SCSS, prefer WordPress-native tooling.
   - Add `@wordpress/scripts` for linting and formatting when it fits the project.
   - Add `@wordpress/stylelint-config` when style linting is needed.
   - Use scripts such as `lint:js`, `lint:css`, `format`, and `format:check` only if they fit the repo's conventions.

5. Keep adoption incremental.
   - Put new dependencies in development-only sections.
   - Avoid broad formatting churn unless the task explicitly asks for normalization.
   - Explain any exclusions or relaxations for legacy code.

6. TODO: support a configurable preferred stack profile.
   - Examples: `PHPCS only`, `PHPCS + PHPStan`, or `pnpm-first` front-end tooling.

## Verification

- The repo still uses its existing package manager unless a change was explicitly requested.
- Composer and NPM config changes are minimal and development-only.
- Added scripts execute successfully or fail only on genuine code issues.
- Lint or format commands match the file types actually present in the repo.
- The final summary explains why each tool was added.

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