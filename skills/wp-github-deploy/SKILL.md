---
name: wp-github-deploy
description: "Use when setting up or improving GitHub Actions deployments for a WordPress site. Covers host-specific deployment workflows (Kinsta, WP Engine, Pantheon, generic SSH/rsync) and a manually-triggered drift-detection workflow that surfaces files changed directly on production that would be overwritten by deploying."
compatibility: "WordPress sites with a GitHub repo and SSH/API access to the host. Requires gh CLI locally."
license: MIT
metadata:
    author: georgestephanis
    version: "1.0"
    written: "2026-05-29"
    written_against:
        gh: "2.92.0"
        actions/checkout: "v4"
        webfactory/ssh-agent: "v0.9"
---

# WordPress GitHub Deployment Setup

## When to use

Use this skill when:

- A WordPress site's repo is on GitHub but has no automated deployment workflow.
- An existing deployment workflow needs updating or the site has migrated hosts.
- You want to detect production drift — files edited directly on the server that would be overwritten by the next deploy.
- You are finishing up a `wp-production-git-adoption` run and Phase 9 pointed here.

Do NOT use this skill to set up the repo itself — use `wp-production-git-adoption` first if the local copy is not yet connected to the remote.

## Inputs required

- **GitHub repo slug** — `owner/repo`, accessible via `gh`.
- **Hosting provider** — Kinsta, WP Engine, Pantheon, or generic SSH. Determines which workflow template applies.
- **SSH credentials** — host, user, port, and SSH private key for the production server (for SSH-based hosts).
- **Deploy path on server** — the absolute path to the directory that should be updated (e.g. `/www/sitename_123/public/wp-content/`). Confirm by SSHing in and running `pwd` from the wp-content or WP root.
- **Branch to deploy from** — usually `trunk`, `main`, or `master`. Confirm against the repo's default branch.
- **`.deployignore` location** — the file listing files that should not be deployed. Must exist in the repo root before the workflow is created.

## Procedure

### Phase 1: Detect the host and deployment pattern

Confirm the hosting provider and their preferred deployment method before writing any workflow YAML. The wrong pattern will either not work or require secrets the host does not support.

| Host | Preferred pattern | Notes |
|------|-------------------|-------|
| **Kinsta** | SSH + rsync | SSH gateway; port varies per site. Kinsta also offers a "Push to deploy" webhook but rsync gives more control. |
| **WP Engine** | Official GitHub Action (`wpengine/github-action-wpe-site-deploy`) | Uses WP Engine's SSH Git Push feature. Simplest setup. |
| **Pantheon** | `terminus` + official Pantheon actions | Multidev workflow; more complex. Use the `terminus-wp-cli` skill for context. |
| **Generic SSH** | rsync over SSH | Works for any host with SSH access and a known deploy path. |

Ask the user to confirm the host if it is not already documented in AGENTS.md or a README.

### Phase 2: Create the deployment workflow

Create `.github/workflows/deploy.yml` (or a host-specific name) using the appropriate template below. Commit it to a feature branch or directly to the default branch — do not push until the required secrets are configured (Phase 3).

#### Template A — Generic SSH / Kinsta (rsync)

```yaml
name: Deploy to Production

on:
  push:
    branches:
      - trunk   # change to match the repo's default branch

jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4

      - name: Set up SSH
        uses: webfactory/ssh-agent@v0.9
        with:
          ssh-private-key: ${{ secrets.DEPLOY_SSH_KEY }}

      - name: Add host to known_hosts
        run: ssh-keyscan -p ${{ vars.DEPLOY_PORT }} ${{ vars.DEPLOY_HOST }} >> ~/.ssh/known_hosts

      - name: Deploy via rsync
        run: |
          rsync -avz --delete \
            --no-perms --no-owner --no-group \
            --exclude-from=.deployignore \
            --exclude='.git' \
            ./ \
            ${{ vars.DEPLOY_USER }}@${{ vars.DEPLOY_HOST }}:${{ vars.DEPLOY_PATH }}/
        env:
          RSYNC_RSH: ssh -p ${{ vars.DEPLOY_PORT }}
```

**Required secrets/variables:**

| Name | Type | Value |
|------|------|-------|
| `DEPLOY_SSH_KEY` | Secret | Private key whose public half is authorized on the server |
| `DEPLOY_HOST` | Variable | Server hostname or IP |
| `DEPLOY_PORT` | Variable | SSH port (default 22, Kinsta varies) |
| `DEPLOY_USER` | Variable | SSH username |
| `DEPLOY_PATH` | Variable | Absolute path on server to deploy into |

#### Template B — WP Engine

```yaml
name: Deploy to WP Engine

on:
  push:
    branches:
      - trunk

jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4

      - name: Deploy to WP Engine
        uses: wpengine/github-action-wpe-site-deploy@v3
        with:
          WPE_SSHG_KEY_PRIVATE: ${{ secrets.WPE_SSHG_KEY_PRIVATE }}
          WPE_ENV: ${{ vars.WPE_ENV }}
          SRC_PATH: ./
          REMOTE_PATH: wp-content/
          EXCLUDES: .deployignore
```

**Required secrets/variables:**

| Name | Type | Value |
|------|------|-------|
| `WPE_SSHG_KEY_PRIVATE` | Secret | WP Engine SSH Gateway private key |
| `WPE_ENV` | Variable | WP Engine environment name (install slug) |

See [WP Engine's official docs](https://wpengine.com/support/github-action-deploy/) for generating and authorizing the key pair. Verify parameter names (`WPE_ENV`, `SRC_PATH`, `REMOTE_PATH`, `EXCLUDES`) against the action's README for the pinned version before using — they have changed between major versions.

#### Template C — Pantheon

Pantheon deployments are more complex due to their Git-push model and multidev workflow. Use the `terminus-wp-cli` skill for Pantheon context, then:

```yaml
name: Deploy to Pantheon

on:
  push:
    branches:
      - trunk

jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
        with:
          fetch-depth: 0

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

**Required secrets/variables:**

| Name | Type | Value |
|------|------|-------|
| `PANTHEON_MACHINE_TOKEN` | Secret | Pantheon machine token from your account dashboard |
| `PANTHEON_SITE` | Variable | Pantheon site name slug |
| `PANTHEON_SITE_UUID` | Variable | Site UUID (from `terminus site:info <site>`) |

Pantheon's deployment model differs significantly — consult their docs and the `terminus-wp-cli` skill before finalising.

### Phase 3: Configure GitHub secrets and variables

Add each secret and variable from the template to the GitHub repo before enabling the workflow:

```bash
# Secrets (encrypted):
gh secret set DEPLOY_SSH_KEY < ~/.ssh/deploy_key_for_sitename

# Variables (visible in logs, not encrypted):
gh variable set DEPLOY_HOST --body "163.192.51.91"
gh variable set DEPLOY_PORT --body "37131"
gh variable set DEPLOY_USER --body "sitename"
gh variable set DEPLOY_PATH --body "/www/sitename_123/public/wp-content"
```

Generate a dedicated deploy key pair rather than reusing a personal key:

```bash
ssh-keygen -t ed25519 -C "github-deploy@sitename" -f ~/.ssh/deploy_sitename
# Add the public key to the server's ~/.ssh/authorized_keys
# Add the private key to GitHub as DEPLOY_SSH_KEY
```

Verify the connection manually before the workflow runs:

```bash
ssh -i ~/.ssh/deploy_sitename -p <port> <user>@<host> "echo connected"
```

### Phase 4: Drift detection workflow

Create `.github/workflows/drift-detection.yml`. This workflow is triggered manually and compares the current state of production against a chosen branch — surfacing files that were edited directly on the server and would be overwritten by a deployment. It reuses the same SSH secrets and variables as Template A (`DEPLOY_SSH_KEY`, `DEPLOY_HOST`, `DEPLOY_PORT`, `DEPLOY_USER`, `DEPLOY_PATH`) — provision those before running it.

Note: `--checksum` compares files by content, not timestamps. This is accurate but slow on large plugin directories; on a typical production site the drift step may take several minutes.

```yaml
name: Production Drift Detection

on:
  workflow_dispatch:
    inputs:
      branch:
        description: 'Branch to compare against production'
        required: true
        default: 'trunk'

jobs:
  detect-drift:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
        with:
          ref: ${{ github.event.inputs.branch }}

      - name: Set up SSH
        uses: webfactory/ssh-agent@v0.9
        with:
          ssh-private-key: ${{ secrets.DEPLOY_SSH_KEY }}

      - name: Add host to known_hosts
        run: ssh-keyscan -p ${{ vars.DEPLOY_PORT }} ${{ vars.DEPLOY_HOST }} >> ~/.ssh/known_hosts

      - name: Detect files repo would overwrite on production
        id: forward_drift
        run: |
          echo "## Files that differ: repo → production" >> $GITHUB_STEP_SUMMARY
          echo '```' >> $GITHUB_STEP_SUMMARY
          rsync --dry-run --checksum --recursive --itemize-changes \
            --no-perms --no-owner --no-group \
            --exclude-from=.deployignore \
            --exclude='.git' \
            ./ \
            ${{ vars.DEPLOY_USER }}@${{ vars.DEPLOY_HOST }}:${{ vars.DEPLOY_PATH }}/ \
            | grep '^>f' | tee forward_drift.txt
          cat forward_drift.txt >> $GITHUB_STEP_SUMMARY
          echo '```' >> $GITHUB_STEP_SUMMARY
        env:
          RSYNC_RSH: ssh -p ${{ vars.DEPLOY_PORT }}

      - name: Detect files on production not in repo
        run: |
          echo "## Files on production not tracked in this branch" >> $GITHUB_STEP_SUMMARY
          echo '```' >> $GITHUB_STEP_SUMMARY
          rsync --dry-run --checksum --recursive --itemize-changes \
            --no-perms --no-owner --no-group \
            --exclude-from=.deployignore \
            --exclude='.git' \
            ${{ vars.DEPLOY_USER }}@${{ vars.DEPLOY_HOST }}:${{ vars.DEPLOY_PATH }}/ \
            ./ \
            | grep '^>f' | tee reverse_drift.txt
          cat reverse_drift.txt >> $GITHUB_STEP_SUMMARY
          echo '```' >> $GITHUB_STEP_SUMMARY
        env:
          RSYNC_RSH: ssh -p ${{ vars.DEPLOY_PORT }}

      - name: Summarise
        run: |
          FORWARD=$(wc -l < forward_drift.txt)
          REVERSE=$(wc -l < reverse_drift.txt)
          echo "" >> $GITHUB_STEP_SUMMARY
          echo "**Forward drift** (repo would change on production): $FORWARD file(s)" >> $GITHUB_STEP_SUMMARY
          echo "**Reverse drift** (production has files not in repo): $REVERSE file(s)" >> $GITHUB_STEP_SUMMARY
          if [ "$FORWARD" -gt 0 ] || [ "$REVERSE" -gt 0 ]; then
            echo "::warning::Drift detected. Review the job summary before deploying."
          fi
```

**Reading the output:**

- **Forward drift** — files the deploy workflow *would* overwrite. Any `>f` lines with a `c` checksum flag indicate production differs from the repo. These are prime candidates for cowboy edits that should be backported.
- **Reverse drift** — files that exist on production but are absent from the repo branch. May indicate plugins or files installed directly on the server outside of version control.

Run this workflow before any significant deployment to production, especially when you know or suspect someone has edited files directly on the server.

### Phase 5: Branch protection (human action required)

Raise this with the user and require a conscious decision before closing the session:

> "Branch protection on `<branch>` is not yet configured. For a production-deploy branch, recommended rules are: require pull request reviews before merging, require status checks (e.g. the deploy workflow) to pass, and disallow force-pushes. Would you like to enable this now, defer it, or explicitly skip it?"

- **If yes**: direct them to **GitHub UI → Settings → Branches → Add branch ruleset**. Do not configure it automatically.
- **If deferred or skipped**: note it as a follow-up. Re-raise it in the next session.

A deliberate "not now" is fine. An unreviewed skip is not.

### Phase 6: Test the deployment

Before merging or pushing to the deploy branch, run the drift detection workflow against it. Then trigger a test deploy:

1. Confirm secrets and variables are all set: `gh secret list && gh variable list`
2. Trigger the workflow manually on a non-production-impacting change (e.g. a whitespace-only edit to a tracked file).
3. Verify the file appeared on production: `ssh -p <port> <user>@<host> "stat <deploy-path>/<file>"`
4. Check that `.deployignore`-listed files were excluded (e.g. `AGENTS.md` should not appear on the server).

## Verification

- `.github/workflows/deploy.yml` exists and targets the correct branch.
- `.github/workflows/drift-detection.yml` exists and is manually triggerable via `gh workflow run drift-detection`.
- All required secrets and variables are present: `gh secret list && gh variable list`.
- A test deployment ran without errors and the file change appeared on production.
- `.deployignore`-listed files were not deployed.
- Branch protection decision has been made (enabled, deferred, or consciously skipped).

## Failure modes

- **rsync fails with "Permission denied"** — the deploy key's public half is not in the server's `~/.ssh/authorized_keys`, or the key was generated with a passphrase (don't use a passphrase for deploy keys).
- **Known hosts check fails** — the `ssh-keyscan` step must use the correct port. If the port is wrong, the workflow will hang or fail with a host verification error.
- **`.deployignore` not found** — rsync's `--exclude-from` will error if the file is missing. Confirm the path is relative to the checkout root.
- **Drift detection shows everything as changed** — likely a line-ending mismatch. The templates already include `--no-perms --no-owner --no-group` to suppress permission noise; if all files still show as changed, check for Windows-style CRLF line endings introduced by the git checkout. Add `--checksum` to the deploy template (it's already in drift detection) to confirm whether files differ in content.
- **WP Engine deploy fails with auth error** — the SSH Gateway key must be added to WP Engine's portal under **Users → Your Profile → SSH Keys**, not just to the server's authorized_keys.
- **Pantheon rejects push** — Pantheon requires the site to be in Git connection mode (`terminus connection:set <site>.live git`) before accepting pushes.

## Escalation

Ask for user input when:

- The deploy path is unclear and cannot be confirmed via SSH.
- The `.deployignore` does not yet exist — create it (using the template in `wp-production-git-adoption/references/ignore-templates.md`) before proceeding.
- The host uses a custom deployment mechanism (e.g. Kinsta's native push-to-deploy webhook) that doesn't fit the rsync pattern — get confirmation before using it.
- Drift detection reveals a large number of files changed on production — review them together rather than deciding unilaterally which to backport.
