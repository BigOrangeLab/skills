# Agent Skills Repository

This repo collects AI agent skills for use across projects.

## Layout

- `skills/` — custom skills authored here; follow the SKILL.md format below
- `vendor/wordpress/` — WordPress/agent-skills submodule (trunk branch)

Current custom WordPress client-repo skills (canonical list with version badges is in README.md — keep in sync when adding skills):

- `wp-client-repo-setup` for adding or normalizing Composer and NPM tooling in existing repos
- `wp-client-repo-review` for security and best-practice review passes on existing repos
- `wp-production-git-adoption` for connecting an existing production site to GitHub for the first time, or re-aligning a diverged local snapshot to its remote
- `wp-github-deploy` for setting up GitHub Actions deployment workflows and drift-detection for WordPress sites (Kinsta, WP Engine, Pantheon, Pressable, generic SSH)
- `wp-integrity-check` for auditing a WordPress site's plugins, themes, and core for unexpected modifications using checksums and upstream comparisons
- `wp-plugin-version-check` for automated plugin version tracking via a scheduled GitHub Actions workflow that opens PRs when installed versions fall behind upstream
- `skill-freshness-remediation` for refreshing stale skills while preserving older-version guidance that still matters

Utility skills (cross-project):

- `github-cli` for GitHub PR, issue, release, and workflow management from the terminal
- `local-wp-db` for querying Local by WPEngine site databases on macOS
- `terminus-wp-cli` for WP-CLI on Pantheon environments via Terminus
- `wp-admin-ui` for building or extending WordPress admin screens (legacy PHP or React/DataViews)

## Skill file format

Each skill is a directory containing `SKILL.md` with mandatory YAML frontmatter:

```yaml
---
name: skill-name
description: "One-line description used for routing"
compatibility: "Platform and version constraints"
---
```

Sections (required, in order): When to use → Inputs required → Procedure → Verification → Failure modes → Escalation.

Push depth into `references/*.md`; keep `SKILL.md` concise.

## Working in this repo

- To scaffold a new skill: `node vendor/wordpress/shared/scripts/scaffold-skill.mjs <name>`
- To validate skills: `node vendor/wordpress/eval/harness/run.mjs`
- Submodule branch is `trunk` — run `git submodule update --remote vendor/wordpress` to pull upstream changes
