# Agent Skills

A shared repository of AI agent skills for use across projects. Skills teach AI assistants (Claude Code, Cursor, etc.) domain-specific procedures for common tasks.

## Structure

```
skills/         — Custom skills authored here
vendor/         — External skill collections (git submodules)
  wordpress/    — https://github.com/WordPress/agent-skills
```

## Skill Format

Each skill lives in its own directory with a `SKILL.md` file using YAML frontmatter:

```yaml
---
name: skill-name
description: "What this skill does (concise, used for routing)"
compatibility: "Target platform and version constraints"
---
```

Followed by these sections (in order):

1. **When to use** — trigger conditions
2. **Inputs required** — what to gather before starting
3. **Procedure** — step-by-step checklist
4. **Verification** — how to confirm success
5. **Failure modes** — common gotchas
6. **Escalation** — when to ask for help

Optionally include a `references/` subdirectory for deeper documentation and a `scripts/` subdirectory for deterministic helper scripts.

## Skills

### Custom skills (`skills/`)

The **Upstream** column shows live package versions. Compare against the **Written against** column to spot skills that may need refreshing. See each skill's `metadata.written_against` frontmatter for the full version map. Skills marked *static* have no public release API.

| Skill | Description | Written against | Upstream (live) |
| --- | --- | --- | --- |
| [github-cli](skills/github-cli/) | Use the `gh` CLI to manage GitHub PRs, issues, repos, releases, and Actions from the terminal. Includes JSON/jq scripting patterns and `gh api` usage. | 2026-05-07<br>`gh 2.x` | [![gh](https://img.shields.io/github/v/release/cli/cli?label=gh&style=flat-square)](https://github.com/cli/cli/releases) |
| [terminus-wp-cli](skills/terminus-wp-cli/) | Run WP-CLI commands on Pantheon environments via Terminus. Covers installation, authentication, environment targeting, common commands, and Pantheon-specific cache/session commands. | 2026-05-07<br>`terminus 3.x` · `wp-cli 2.x` | [![terminus](https://img.shields.io/github/v/release/pantheon-systems/terminus?label=terminus&style=flat-square)](https://github.com/pantheon-systems/terminus/releases) [![wp-cli](https://img.shields.io/github/v/release/wp-cli/wp-cli?label=wp-cli&style=flat-square)](https://github.com/wp-cli/wp-cli/releases) |
| [wp-client-repo-setup](skills/wp-client-repo-setup/) | Adopt an existing WordPress client repo and add minimal Composer or NPM tooling for WPCS/PHPCS, PHP compatibility, and `@wordpress/scripts`. | 2026-05-22<br>`WP 6.9` · `phpcs 3.x` · `wpcs 3.x` · `wp-scripts 30.x` | [![WordPress](https://img.shields.io/badge/dynamic/json?url=https%3A%2F%2Fapi.wordpress.org%2Fcore%2Fversion-check%2F1.7%2F&query=%24.offers%5B0%5D.current&label=WordPress&style=flat-square)](https://wordpress.org/news/category/releases/) [![phpcs](https://img.shields.io/github/v/release/squizlabs/PHP_CodeSniffer?label=phpcs&style=flat-square)](https://github.com/squizlabs/PHP_CodeSniffer/releases) [![wpcs](https://img.shields.io/github/v/release/WordPress/WordPress-Coding-Standards?label=wpcs&style=flat-square)](https://github.com/WordPress/WordPress-Coding-Standards/releases) [![wp-scripts](https://img.shields.io/npm/v/@wordpress/scripts?label=wp-scripts&style=flat-square)](https://www.npmjs.com/package/@wordpress/scripts) |
| [wp-client-repo-review](skills/wp-client-repo-review/) | Review an existing WordPress client repo for security issues, best-practice risks, and actionable follow-up after or alongside linting. | 2026-05-22<br>`WP 6.9` · `phpcs 3.x` · `wpcs 3.x` | [![WordPress](https://img.shields.io/badge/dynamic/json?url=https%3A%2F%2Fapi.wordpress.org%2Fcore%2Fversion-check%2F1.7%2F&query=%24.offers%5B0%5D.current&label=WordPress&style=flat-square)](https://wordpress.org/news/category/releases/) [![phpcs](https://img.shields.io/github/v/release/squizlabs/PHP_CodeSniffer?label=phpcs&style=flat-square)](https://github.com/squizlabs/PHP_CodeSniffer/releases) [![wpcs](https://img.shields.io/github/v/release/WordPress/WordPress-Coding-Standards?label=wpcs&style=flat-square)](https://github.com/WordPress/WordPress-Coding-Standards/releases) |
| [wp-admin-ui](skills/wp-admin-ui/) | Build or extend WordPress admin screens: legacy PHP/CSS patterns vs. React/DataViews, admin color scheme variables, mounting React in wp-admin, and SCSS design tokens. | 2026-05-22<br>`WP 6.9` · `wp-components 28.x` | [![WordPress](https://img.shields.io/badge/dynamic/json?url=https%3A%2F%2Fapi.wordpress.org%2Fcore%2Fversion-check%2F1.7%2F&query=%24.offers%5B0%5D.current&label=WordPress&style=flat-square)](https://wordpress.org/news/category/releases/) [![wp-components](https://img.shields.io/npm/v/@wordpress/components?label=wp-components&style=flat-square)](https://www.npmjs.com/package/@wordpress/components) |
| [local-wp-db](skills/local-wp-db/) | Query the database of a WordPress site running under Local by WPEngine on macOS via the site-specific Unix socket. | 2026-05-22<br>`Local 6.x` · `MySQL 8.0` | [![Local](https://img.shields.io/badge/local--by--wpengine-6.x-8B5CF6?style=flat-square)](https://localwp.com) [![mysql](https://img.shields.io/badge/mysql-8.0-4479A1?style=flat-square)](https://dev.mysql.com/downloads/mysql/) *static* |

### WordPress skills (`vendor/wordpress/`)

Upstream skills from [WordPress/agent-skills](https://github.com/WordPress/agent-skills). See that repo's README for the full list.

---

## Installing Skills

Use the WordPress agent-skills build tooling to install skills into a project:

```bash
# Install WordPress skills globally
cd vendor/wordpress
node shared/scripts/skillpack-install.mjs --global --targets=claude

# Install to a specific project
node shared/scripts/skillpack-install.mjs --dest=../../your-project --targets=claude
```

For custom skills in `skills/`, either copy the skill directory into a target project's `.claude/skills/` directory or symlink the individual skill directory into `~/.claude/skills/` for global availability across projects.

## Adding External Skill Collections

```bash
git submodule add <repo-url> vendor/<name>
git submodule update --init --recursive
```

## Cloning This Repository

```bash
git clone --recurse-submodules <repo-url>
# or, after a plain clone:
git submodule update --init --recursive
```
