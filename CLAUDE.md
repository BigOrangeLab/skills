# Agent Skills Repository

This repo collects AI agent skills for use across projects.

## Layout

- `skills/` — custom skills authored here; follow the SKILL.md format below
- `vendor/wordpress/` — WordPress/agent-skills submodule (trunk branch)

Current custom WordPress client-repo skills:

- `wp-client-repo-setup` for adding or normalizing Composer and NPM tooling in existing repos
- `wp-client-repo-review` for security and best-practice review passes on existing repos
- `skill-freshness-remediation` for refreshing stale skills while preserving older-version guidance that still matters

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
