# Pantheon Deployment

Pantheon uses a Git-push model rather than file sync. Deployments push commits to Pantheon's internal Git remote; Pantheon then applies the code to the environment. This is significantly different from rsync-based hosts.

Use the `terminus-wp-cli` skill for broader Pantheon context before setting this up.

## Workflow

```yaml
name: Deploy to Pantheon

on:
  push:
    branches:
      - trunk   # change to match the repo's default branch

jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
        with:
          fetch-depth: 0   # full history required for git push

      - name: Install Terminus
        uses: pantheon-systems/terminus-github-actions@v1
        with:
          pantheon-machine-token: ${{ secrets.PANTHEON_MACHINE_TOKEN }}

      - name: Push to Pantheon
        run: |
          terminus connection:set ${{ vars.PANTHEON_SITE }}.live git
          git remote add pantheon ssh://codeserver.dev.${{ vars.PANTHEON_SITE_UUID }}@codeserver.dev.${{ vars.PANTHEON_SITE_UUID }}.drush.in:2222/~/repository.git
          git push pantheon HEAD:master
```

## Required secrets/variables

| Name | Type | Value |
|------|------|-------|
| `PANTHEON_MACHINE_TOKEN` | Secret | Pantheon machine token from your dashboard (Account → Machine Tokens) |
| `PANTHEON_SITE` | Variable | Pantheon site name slug (from `terminus site:list`) |
| `PANTHEON_SITE_UUID` | Variable | Site UUID (from `terminus site:info <site>`) |

## Notes

- **Connection mode**: Pantheon must be in Git connection mode before accepting pushes. The `terminus connection:set` step handles this, but if the environment is actively receiving SFTP edits, switching to Git mode will discard any uncommitted SFTP changes — confirm with the user first.
- **`HEAD:master`**: Pantheon's internal branch is always `master` regardless of your GitHub branch name. The `HEAD:master` push maps your branch onto it.
- **Multidev**: To push to a multidev environment instead of `live`, replace `.live` and `codeserver.dev.` references with `.multidev-name` and `codeserver.multidev-name.` respectively, and push to `HEAD:multidev-name`.
- **`.deployignore` does not apply** — Pantheon receives the full git history, not a filtered file sync. Exclude files from the Pantheon build using a `.gitignore` committed to the repo, or Pantheon's `pantheon.yml` build configuration.
- **Drift detection** (Phase 4 of the main skill) cannot use rsync against Pantheon — use `terminus env:diffstat` to check for uncommitted server-side changes before deploying.
