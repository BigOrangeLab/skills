---
name: wp-client-repo-review
description: "Use when reviewing an existing WordPress client repository for security issues, WordPress best practices, and actionable findings after or alongside linting and standards checks."
compatibility: "WordPress plugins, themes, mu-plugins, and site repos. Requires filesystem access to PHP source files; linting/standards output is optional input."
license: MIT
metadata:
  author: georgestephanis
  version: "1.0"
---

# WordPress Client Repo Review

## When to use

Use this skill when a WordPress client repository needs a focused review for security, maintainability, and WordPress best-practice concerns instead of, or in addition to, tooling setup.

Do NOT use as a substitute for running linting and standards tools — run those first and supply their output as input to this review. Do NOT use for repos where the primary ask is adding tooling; use `wp-client-repo-setup` for that.

## Inputs required

- The repository root and repo shape: plugin, theme, mu-plugin, site, or mixed codebase
- Any existing lint, standards, or static-analysis results
- The highest-risk code surfaces: database access, REST routes, admin forms, uploads, authentication, cron, or remote requests
- Any project-specific support constraints such as target WordPress or PHP versions

## Procedure

1. Start from the riskiest surfaces first.
   - Look at SQL queries, form handlers, REST endpoints, upload flows, serialization, and privileged actions.
   - Use existing lint or standards output to find the highest-signal paths.

2. Prioritize findings in this order.
   - SQL safety and prepared queries
   - Escaping and output handling
   - Input sanitization and validation
   - Nonces and capability checks
   - REST API `permission_callback` coverage
   - File access, upload, and deserialization risks
   - Deprecated APIs and compatibility issues
   - Performance issues caused by repeated queries, autoload bloat, or unbounded hooks

   For detailed patterns and function-level examples in each category, see [references/security-checklist.md](references/security-checklist.md).

3. Keep findings actionable.
   - Report concrete bugs, security issues, regressions, and missing tests before style commentary.
   - Prefer the smallest code reference set that supports the finding.
   - Distinguish between must-fix security issues and follow-up cleanup.

4. Use WordPress conventions as the standard.
   - Check capability and nonce handling for privileged actions.
   - Check sanitization on input boundaries and escaping on output boundaries.
   - Check REST exposure for schema, validation, and permissions.

5. Tie review output back to verification.
   - Mention which command output, code path, or test surface supports each finding.
   - Call out residual risk if tooling is absent or incomplete.

## Verification

- Findings are ordered by severity and supported by specific code references.
- Security and behavior risks appear before style or polish observations.
- Review conclusions align with actual code paths, not generic WordPress advice.
- Residual testing or tooling gaps are called out explicitly when they limit confidence.
- Client has a written summary distinguishing must-fix security issues from follow-up cleanup items.

## Failure modes

- Leading with low-value style comments while missing security defects
- Reporting generic WordPress advice without proving it applies to the repo
- Ignoring permission checks on REST routes or admin actions
- Treating formatter output as a substitute for a security review
- Missing compatibility risks in legacy client hosting environments

## Escalation

Ask for user input when:

- The review boundary is unclear and the repo is too large for a full audit.
- A finding depends on undocumented client infrastructure or deployment assumptions.
- The risk tradeoff requires business context, such as legacy browser or PHP support guarantees.