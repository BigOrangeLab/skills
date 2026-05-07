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

## Installing Skills

Use the WordPress agent-skills build tooling to install skills into a project:

```bash
# Install WordPress skills globally
cd vendor/wordpress
node shared/scripts/skillpack-install.mjs --global --targets=claude

# Install to a specific project
node shared/scripts/skillpack-install.mjs --dest=../../your-project --targets=claude
```

For custom skills in `skills/`, copy the skill directory into the target project's `.claude/skills/` directory.

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
