# Pantheon Deployment

Pantheon uses a **Git-push model** — deployments push commits to Pantheon's internal Git remote; Pantheon then applies the code to the environment. This is fundamentally different from rsync-based hosts.

Two approaches are available. Use the `push-to-pantheon` action unless you need fine-grained control over the Terminus commands.

Use the `terminus-wp-cli` skill for broader Pantheon context before setting this up.

---

## Approach A — `push-to-pantheon` action (recommended)

The official Pantheon GitHub Action. Handles both branch deploys (to the Pantheon Dev environment) and pull request Multidev creation in a single step.

> **Early Access**: Pin to a specific version tag (e.g. `@0.9.2`). Behaviours may change before 1.0.0. Check [releases](https://github.com/pantheon-systems/push-to-pantheon/releases) for the current stable tag.

### Workflow

```yaml
name: Deploy to Pantheon

on:
  push:
    branches:
      - trunk   # change to match the repo's default branch — deploys to Pantheon Dev
  pull_request:
    types: [opened, synchronize, reopened]   # creates/updates a Multidev environment

jobs:
  push:
    permissions:
      deployments: write
      contents: read
      pull-requests: read
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
        with:
          fetch-depth: 0

      - name: Push to Pantheon
        uses: pantheon-systems/push-to-pantheon@0.9.2
        with:
          ssh_key: ${{ secrets.PANTHEON_SSH_KEY }}
          machine_token: ${{ secrets.PANTHEON_MACHINE_TOKEN }}
          site: ${{ vars.PANTHEON_SITE }}
```

### Required secrets/variables

| Name | Type | Value |
|------|------|-------|
| `PANTHEON_SSH_KEY` | Secret | Private SSH key whose public half is added to your Pantheon account (Dashboard → Account → SSH Keys) |
| `PANTHEON_MACHINE_TOKEN` | Secret | Pantheon machine token (Dashboard → Account → Machine Tokens) |
| `PANTHEON_SITE` | Variable | Pantheon site machine name (from `terminus site:list` or the dashboard URL slug) |

### Notes

- The action creates a Multidev environment named after the PR branch for pull requests, and deploys to Dev on direct pushes to the default branch.
- `fetch-depth: 0` is required — Pantheon's git remote needs full history.
- Pin the action version before the 1.0.0 release; check the repo's CHANGELOG before upgrading.

---

## Approach B — Manual Terminus (advanced / fallback)

Use this if `push-to-pantheon` doesn't support a workflow you need (e.g. deploying directly to a specific non-default environment, or scripting around Terminus commands).

### Workflow

```yaml
name: Deploy to Pantheon (manual)

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

      - name: Push to Pantheon Dev
        run: |
          terminus connection:set ${{ vars.PANTHEON_SITE }}.dev git
          git remote add pantheon $(terminus connection:info ${{ vars.PANTHEON_SITE }}.dev --field=git_url)
          git push pantheon HEAD:master
```

### Required secrets/variables

| Name | Type | Value |
|------|------|-------|
| `PANTHEON_MACHINE_TOKEN` | Secret | Pantheon machine token from your account dashboard |
| `PANTHEON_SITE` | Variable | Pantheon site name slug |

### Notes

- `terminus connection:info --field=git_url` retrieves the correct remote URL dynamically, avoiding the need to hardcode the site UUID.
- Pantheon's internal branch is always `master` regardless of your GitHub branch name.
- To push to a Multidev: replace `.dev` with `.multidev-name` and push to `HEAD:multidev-name`.

---

## Both approaches — shared constraints

- **`.deployignore` does not apply** — Pantheon receives the full git history. Use `.gitignore` (committed to the repo) to keep files out of the deployment, or Pantheon's `pantheon.yml` build configuration.
- **Connection mode**: Pantheon must be in Git mode before accepting pushes. If the environment is actively receiving SFTP edits, switching to Git mode discards any uncommitted SFTP changes — confirm with the user first.
- **Drift detection**: The rsync workflow in the parent skill does not apply to Pantheon. Use `terminus env:diffstat ${{ vars.PANTHEON_SITE }}.dev` to surface files changed via SFTP since the last commit.

---

## Pantheon GitHub Application (private beta)

Pantheon also has a native GitHub integration (similar to Pressable's) that auto-deploys on push without a workflow file. It is currently in private beta and requires Pantheon-specific configuration files in the repository. Not yet suitable for general client projects — revisit when it reaches GA.
