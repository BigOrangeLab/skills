---
name: wp-github-deploy
description: "Use when setting up or improving GitHub Actions deployments for a WordPress site. Covers host-specific deployment workflows (Kinsta, WP Engine, Pantheon, Pressable, generic SSH/rsync) and a manually-triggered drift-detection workflow that surfaces files changed directly on production that would be overwritten by deploying."
compatibility: "WordPress sites with a GitHub repo. SSH/API access required for rsync-based hosts (generic SSH); Kinsta, WP Engine, Pantheon, and Pressable use host-specific mechanisms. Requires gh CLI locally."
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
- **Hosting provider** — Kinsta, WP Engine, Pantheon, Pressable, or generic SSH. Determines which workflow template applies.
- **SSH credentials** — host, user, port, and SSH private key for the production server (for SSH-based hosts).
- **Deploy path on server** — the absolute path to the directory that should be updated (e.g. `/www/sitename_123/public/wp-content/`). Confirm by SSHing in and running `pwd` from the wp-content or WP root.
- **Branch to deploy from** — usually `trunk`, `main`, or `master`. Confirm against the repo's default branch.
- **`.deployignore` location** — the file listing files that should not be deployed. Must exist in the repo root before the workflow is created.

## Procedure

### Phase 1: Detect the host and deployment pattern

Confirm the hosting provider and their preferred deployment method before writing any workflow YAML. The wrong pattern will either not work or require secrets the host does not support.

| Host | Preferred pattern | Notes |
|------|-------------------|-------|
| **Kinsta** | Server-side git pull (`appleboy/ssh-action`) | CI SSHs in and runs `git fetch && git reset --hard`. Server needs a deploy key on GitHub. IP allowlist must be disabled. |
| **WP Engine** | Official GitHub Action (`wpengine/github-action-wpe-site-deploy`) | Uses WP Engine's SSH Git Push feature. Simplest setup. |
| **Pantheon** | `push-to-pantheon` action (recommended) or manual Terminus | Git-push model; handles Multidev for PRs + Dev for branch pushes. Use the `terminus-wp-cli` skill for context. |
| **Pressable** | Native GitHub integration (no workflow YAML) | Configured entirely in the Pressable control panel. `.deployignore` must exist in the repo *before* activating. |
| **Generic SSH** | rsync over SSH | Works for any host with SSH access and a known deploy path. |

Ask the user to confirm the host if it is not already documented in AGENTS.md or a README.

### Phase 2: Create the deployment workflow

Open the reference for the detected host, copy the workflow YAML into `.github/workflows/deploy.yml` (or a host-specific name), and note the required secrets and variables — you'll provision them in Phase 3.

| Host | Reference |
|------|-----------|
| Kinsta | [references/host-kinsta.md](references/host-kinsta.md) |
| WP Engine | [references/host-wp-engine.md](references/host-wp-engine.md) |
| Pantheon | [references/host-pantheon.md](references/host-pantheon.md) |
| Pressable | [references/host-pressable.md](references/host-pressable.md) |
| Generic SSH | [references/host-generic-ssh.md](references/host-generic-ssh.md) |

Each reference includes the full workflow YAML, required secrets/variables table, and host-specific notes. To add a new host, create `references/host-<name>.md` following the same structure and add a row to this table.

Commit to a feature branch or directly to the default branch — **do not push until secrets are configured** (Phase 3).

### Phase 3: Configure GitHub secrets and variables

Add each secret and variable listed in the host reference file before enabling the workflow. Use `gh` for non-interactive provisioning:

```bash
# Secrets are encrypted and never shown in logs:
gh secret set SECRET_NAME --body "value"
# or pipe a key file directly:
gh secret set DEPLOY_SSH_KEY < ~/.ssh/deploy_key_for_sitename

# Variables are visible in logs — use only for non-sensitive config:
gh variable set VAR_NAME --body "value"
```

Confirm everything is set before pushing: `gh secret list && gh variable list`

**For rsync-based hosts (generic SSH, Pressable)** — generate a dedicated deploy key pair rather than reusing a personal key:

```bash
ssh-keygen -t ed25519 -C "github-deploy@sitename" -f ~/.ssh/deploy_sitename
# Add the public key to the server's ~/.ssh/authorized_keys
# Add the private key to GitHub as DEPLOY_SSH_KEY
```

Verify the connection manually before the workflow runs:

```bash
ssh -i ~/.ssh/deploy_sitename -p <port> <user>@<host> "echo connected"
```

**For Kinsta** — see the one-time server setup steps in [references/host-kinsta.md](references/host-kinsta.md); the server-side deploy key must be added to GitHub before the workflow can pull commits.

### Phase 4: Drift detection workflow

Create `.github/workflows/drift-detection.yml`. This workflow is triggered manually and compares the current state of production against a chosen branch — surfacing files that were edited directly on the server that a deploy would overwrite.

**Host compatibility:**

| Host | Drift detection approach |
|------|--------------------------|
| **Generic SSH** | This rsync workflow — full support |
| **Kinsta** | Kinsta provides SSH access that rsync can theoretically use, but the IP allowlist is a common blocker — GitHub Actions runner IPs may be denied. If rsync is blocked, SSH into the server and run `git status` / `git diff` instead (requires the git-pull deployment model). See [references/host-kinsta.md](references/host-kinsta.md) for both options and their prerequisites. |
| **Pressable** | Pressable SSH credentials work with this workflow even though deployment is native. See [references/host-pressable.md](references/host-pressable.md). |
| **WP Engine** | No direct SSH rsync access. Check via WP Engine's SFTP or the admin file manager. |
| **Pantheon** | Use `terminus env:diffstat <site>.<env>` to surface SFTP edits since the last commit. |

The workflow reuses the same SSH secrets and variables as the rsync deploy workflow (`DEPLOY_SSH_KEY`, `DEPLOY_HOST`, `DEPLOY_PORT`, `DEPLOY_USER`, `DEPLOY_PATH`) — provision those before running it.

Note: `--checksum` compares files by content, not timestamps. This is accurate but slow on large plugin directories; on a typical production site the drift step may take several minutes.

```yaml
name: Production Drift Detection

# When drift is found, creates a GitHub issue (or comments on an existing open
# drift issue) so it is tracked and not forgotten. Close the issue once resolved.

on:
  workflow_dispatch:
    inputs:
      branch:
        description: 'Branch to compare against production'
        required: true
        default: 'trunk'

permissions:
  contents: read
  issues: write

jobs:
  detect-drift:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
        with:
          ref: ${{ github.event.inputs.branch }}

      - name: Set up SSH
        uses: webfactory/ssh-agent@v0.9.0
        with:
          ssh-private-key: ${{ secrets.DEPLOY_SSH_KEY }}

      - name: Add host to known_hosts
        run: ssh-keyscan -p ${{ vars.DEPLOY_PORT }} ${{ vars.DEPLOY_HOST }} >> ~/.ssh/known_hosts

      - name: Forward drift — files repo would change on production
        run: |
          echo "## Forward drift: what a deploy from \`${{ github.event.inputs.branch }}\` would change on production" >> "$GITHUB_STEP_SUMMARY"
          echo "" >> "$GITHUB_STEP_SUMMARY"
          echo "Legend: \`>f\` transfer file · \`*deleting\` remove from production · \`.f\` identical" >> "$GITHUB_STEP_SUMMARY"
          echo "" >> "$GITHUB_STEP_SUMMARY"
          echo '```' >> "$GITHUB_STEP_SUMMARY"
          rsync \
            --dry-run \
            --checksum \
            --recursive \
            --itemize-changes \
            --no-perms --no-owner --no-group \
            --exclude-from=.deployignore \
            --exclude='.git' \
            ./ \
            "${{ vars.DEPLOY_USER }}@${{ vars.DEPLOY_HOST }}:${{ vars.DEPLOY_PATH }}/" \
            2>&1 | tee forward_drift.txt
          cat forward_drift.txt >> "$GITHUB_STEP_SUMMARY"
          echo '```' >> "$GITHUB_STEP_SUMMARY"
        env:
          RSYNC_RSH: "ssh -p ${{ vars.DEPLOY_PORT }}"

      - name: Reverse drift — files on production not in repo branch
        run: |
          echo "" >> "$GITHUB_STEP_SUMMARY"
          echo "## Reverse drift: files on production not tracked in \`${{ github.event.inputs.branch }}\`" >> "$GITHUB_STEP_SUMMARY"
          echo "" >> "$GITHUB_STEP_SUMMARY"
          echo "These files exist on production but are absent from the repo. May indicate cowboy additions." >> "$GITHUB_STEP_SUMMARY"
          echo "" >> "$GITHUB_STEP_SUMMARY"
          echo '```' >> "$GITHUB_STEP_SUMMARY"
          rsync \
            --dry-run \
            --checksum \
            --recursive \
            --itemize-changes \
            --no-perms --no-owner --no-group \
            --exclude-from=.deployignore \
            --exclude='.git' \
            "${{ vars.DEPLOY_USER }}@${{ vars.DEPLOY_HOST }}:${{ vars.DEPLOY_PATH }}/" \
            ./ \
            2>&1 | grep '^>f' | tee reverse_drift.txt
          cat reverse_drift.txt >> "$GITHUB_STEP_SUMMARY"
          echo '```' >> "$GITHUB_STEP_SUMMARY"
        env:
          RSYNC_RSH: "ssh -p ${{ vars.DEPLOY_PORT }}"

      - name: Summary counts
        id: counts
        run: |
          FORWARD=$(grep -c '^[>c<]' forward_drift.txt || true)
          REVERSE=$(wc -l < reverse_drift.txt)
          echo "forward=$FORWARD" >> "$GITHUB_OUTPUT"
          echo "reverse=$REVERSE" >> "$GITHUB_OUTPUT"
          echo "**Forward drift** (production differs from repo): $FORWARD file(s)" >> "$GITHUB_STEP_SUMMARY"
          echo "**Reverse drift** (on production, absent from repo): $REVERSE file(s)" >> "$GITHUB_STEP_SUMMARY"
          if [ "$FORWARD" -gt 0 ] || [ "$REVERSE" -gt 0 ]; then
            echo "::warning::Drift detected — review the job summary before deploying to production."
          fi

      - name: Report drift via GitHub issue
        if: steps.counts.outputs.forward != '0' || steps.counts.outputs.reverse != '0'
        env:
          GH_TOKEN: ${{ github.token }}
        run: |
          FORWARD=${{ steps.counts.outputs.forward }}
          REVERSE=${{ steps.counts.outputs.reverse }}
          BRANCH="${{ github.event.inputs.branch }}"
          RUN_URL="${{ github.server_url }}/${{ github.repository }}/actions/runs/${{ github.run_id }}"
          DATE=$(date -u '+%Y-%m-%d %H:%M UTC')

          FORWARD_BODY=$(head -100 forward_drift.txt)
          REVERSE_BODY=$(head -100 reverse_drift.txt)
          FORWARD_LINES=$(wc -l < forward_drift.txt)
          REVERSE_LINES=$(wc -l < reverse_drift.txt)
          [ "$FORWARD_LINES" -gt 100 ] && FORWARD_NOTE="*(truncated — $FORWARD_LINES lines total; see run for full output)*" || FORWARD_NOTE=""
          [ "$REVERSE_LINES" -gt 100 ] && REVERSE_NOTE="*(truncated — $REVERSE_LINES lines total; see run for full output)*" || REVERSE_NOTE=""

          cat > /tmp/drift_body.md << BODY
          **Branch compared:** \`${BRANCH}\`
          **Detected:** ${DATE}
          **Run:** ${RUN_URL}

          | Direction | Count |
          |-----------|-------|
          | Forward drift (repo → production) | ${FORWARD} file(s) |
          | Reverse drift (production → repo) | ${REVERSE} file(s) |

          **Forward drift** — files a deployment would overwrite or add on production:

          \`\`\`
          ${FORWARD_BODY}
          \`\`\`
          ${FORWARD_NOTE}

          **Reverse drift** — files on production not tracked in \`${BRANCH}\`:

          \`\`\`
          ${REVERSE_BODY}
          \`\`\`
          ${REVERSE_NOTE}

          ---
          Resolve by backporting cowboy edits to the repo, or confirming the production state is intentional. Re-run this workflow to verify once addressed.
          BODY

          gh label create "production-drift" \
            --color "D93F0B" \
            --description "Production files differ from the repository" \
            --force

          EXISTING=$(gh issue list \
            --label "production-drift" \
            --state open \
            --json number \
            --jq '.[0].number' 2>/dev/null || true)

          if [ -n "$EXISTING" ]; then
            gh issue comment "$EXISTING" --body-file /tmp/drift_body.md
          else
            gh issue create \
              --title "Production drift detected — \`${BRANCH}\` (${DATE})" \
              --body-file /tmp/drift_body.md \
              --label "production-drift"
          fi
```

**Reading the output:**

- **Forward drift** — files the deploy workflow *would* overwrite or add. Lines starting with `>f` indicate content differs from the repo; `*deleting` means the file exists on production but not in the repo (and would be removed by a deploy with `--delete`).
- **Reverse drift** — files present on production that are absent from the repo branch. May indicate cowboy additions or plugins/files installed outside of version control.

When drift is detected, the workflow opens a `production-drift` GitHub issue (or appends a comment to an existing open one). Close the issue once the drift is resolved and a clean run confirms production is in sync.

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
- **Pantheon rejects push** — the environment must be in Git connection mode before accepting pushes. Both the `push-to-pantheon` action and the manual approach call `terminus connection:set` to handle this, but if the environment has uncommitted SFTP changes the mode switch will fail — resolve or discard SFTP changes first.

## Escalation

Ask for user input when:

- The deploy path is unclear and cannot be confirmed via SSH.
- The `.deployignore` does not yet exist — create it (using the template in `wp-production-git-adoption/references/ignore-templates.md`) before proceeding.
- The host uses a custom deployment mechanism (e.g. Kinsta's native push-to-deploy webhook) that doesn't fit the rsync pattern — get confirmation before using it.
- Drift detection reveals a large number of files changed on production — review them together rather than deciding unilaterally which to backport.
