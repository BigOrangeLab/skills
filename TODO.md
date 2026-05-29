# TODO

## Skills to add

### wp-staging-deploy

Deploy to a staging environment, run smoke tests, and promote to production. Builds on `wp-github-deploy`.

Scope:

- Multi-environment workflow: feature branch → staging deploy → smoke tests → production promote
- Smoke test patterns: URL/status-code checks, critical-path WP-CLI assertions (can WordPress bootstrap, are key plugins active)
- Rollback procedure: revert the staging or production deploy to the previous commit without data loss
- Environment-specific variable handling (`STAGING_HOST`, `STAGING_PATH`, etc. alongside the production equivalents)
- Coordination with `wp-github-deploy` host references — staging on the same host vs. a separate environment

---

### wp-database-migration

Run WP-CLI `search-replace`, `db export`/`import`, and serialised-data safety operations across environments. Priority use cases: domain changes when moving between environments, pulling a production DB down to a local or staging environment, and multi-table `search-replace` safety checks.

Scope:

- Always `--dry-run` first on any non-throwaway database
- Serialised-data safe: prefer `wp search-replace` over raw SQL; document why
- Multisite-aware: `--network` flag and per-site URL replacement
- Credential handling: never pipe passwords through `echo`; use `--prompt-db-pass` or a `.my.cnf`
- Integration with Local by WPEngine and WordPress Studio for local targets
- Cross-reference `local-wp-db` skill for Local socket connection details

---

### wp-plugin-audit

Licensing compliance and abandoned-plugin risk review. Complements `wp-integrity-check` (file content) and `wp-plugin-version-check` (available updates). Intended to run once per client engagement or before a major launch.

Scope:

- GPL compatibility check: identify non-GPL-compatible plugins in a GPL-licensed codebase
- Abandoned-plugin detection: no updates in 2+ years, removed from wp.org directory (a security signal)
- Commercial license inventory: cross-reference PLUGINS.md License column against actual renewal obligations
- Closed/removed wp.org plugins: `api.wordpress.org/plugins/info/1.0/{slug}.json` returns `false` for delisted plugins — flag these for review
- Risk tiers: critical (removed from wp.org + modified files), high (no updates 2+ years), medium (license unverified), low (up to date, open source)
