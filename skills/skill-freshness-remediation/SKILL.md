---
name: skill-freshness-remediation
description: "Use when remediating skill version freshness issues, updating written_against metadata after upstream drift, or preparing a PR that refreshes existing skills without discarding still-relevant older-version guidance."
compatibility: "Skill repositories that use SKILL.md frontmatter with metadata.written_against and optional GitHub issue or PR freshness reports. Requires filesystem access; GitHub issue, PR, or workflow output is optional but recommended."
license: MIT
metadata:
    author: georgestephanis
    version: "1.0"
    written: "2026-05-22"
    written_against:
        gh: "2.92.0"
---

# Skill Freshness Remediation

## When to use

Use this skill when a freshness workflow, issue, or PR shows that one or more skills may be stale and need review against newer upstream versions.

Use it when you need to update `metadata.written_against`, revise examples or procedures, or prepare a maintenance PR similar to the remediation done for Issue #2 and PR #3 in this repository.

Do NOT use it to create a brand-new skill from scratch. Do NOT use it to blindly replace all older-version guidance with the newest release behavior.

## Inputs required

- The target skill paths, or the freshness issue, report, or PR that identifies them
- The current `SKILL.md` and any `references/*.md` files for each target skill
- The current freshness output, such as `.github/scripts/check-skill-versions.mjs` output or the GitHub issue body
- Upstream release notes, changelogs, or docs for both the version the skill was written against and the newer version being considered
- The support boundary for older versions, if known

## Procedure

1. Scope the remediation before editing.
    - Start from the flagged rows in the freshness report or issue.
    - Confirm which tools or versions are actually stale enough to merit a content review.
    - Distinguish metadata drift from real procedural drift.

2. Review the existing skill before changing anything.
    - Read the full `SKILL.md` plus any linked references.
    - Identify commands, screenshots, APIs, package names, and behavioral assumptions that are version-sensitive.
    - Note where the current content is still valid across multiple versions.

3. Compare old and new upstream behavior.
    - Check release notes and primary docs for breaking changes, renamed commands, changed defaults, removed features, and new prerequisites.
    - Prefer updating only the sections that actually changed.
    - Do not widen the edit surface just because a newer version exists.

4. Preserve still-relevant older-version guidance.
    - Do **not** throw away older-version instructions just because they are no longer the latest.
    - If both old and new versions may still matter, keep both and label them clearly.
    - Prefer patterns such as version-qualified bullets, short compatibility notes, or separate subsections when behavior differs.
    - Only remove old-version guidance when the support boundary has clearly changed or the user explicitly wants legacy coverage dropped.

    For concrete preservation patterns, see [references/version-preservation.md](references/version-preservation.md).

5. Update metadata last, after content review.
    - Update `metadata.written` to the review date.
    - Update `metadata.written_against` only for tools you actually revalidated.
    - If the skill still intentionally covers older versions, reflect that in `compatibility` or the body text instead of pretending the old behavior no longer exists.

6. Keep the PR or summary explicit.
    - Call out which skills were reviewed, which versions were checked, and where content changed versus where metadata alone changed.
    - Mention any remaining legacy branches or unsupported older-version behavior that was intentionally retained.
    - If a freshness issue row remains `no source`, note that it still requires manual tracking.

## Verification

- Each updated skill still describes valid behavior for the supported version range.
- Older-version guidance remains present when it is still relevant to supported workflows.
- `metadata.written` and `metadata.written_against` match what was actually reviewed.
- Version-specific differences are labeled clearly enough that a future maintainer can tell which guidance applies where.
- Repo validation passes after the update, such as `npm run lint` and any freshness-report command used by the repo.

## Failure modes

- Replacing useful legacy guidance with latest-version guidance without checking current support expectations
- Updating `written_against` without actually reviewing the content
- Treating every version bump as a reason for a full rewrite
- Dropping caveats for unsupported or manually tracked tools because they have no public release API
- Mixing old and new instructions together without labeling which version they apply to

## Escalation

Ask for user input when:

- It is unclear whether older versions still need to be supported.
- Upstream docs conflict with observed behavior or release notes are incomplete.
- The remediation requires a policy decision about officially dropping legacy guidance.
- A skill appears stale, but the repo intentionally targets an older tool or platform version.
