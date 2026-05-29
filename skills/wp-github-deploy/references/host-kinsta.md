# Kinsta Deployment

Kinsta's recommended approach is a **server-side git pull** model using `appleboy/ssh-action`. The GitHub Actions runner SSHs into the Kinsta server and runs `git fetch && git reset --hard` — the server pulls from GitHub rather than CI pushing files to the server.

This differs from the generic rsync approach: the Kinsta server must have the repo cloned at the deploy path, and the server itself needs a deploy key authorised on GitHub to pull commits.

SSH credentials are found in **MyKinsta → Sites → [site] → Info → SFTP/SSH credentials**.

## Credential naming

Use the shared `DEPLOY_*` naming for all connection details. Both the deploy workflow and the drift-detection workflow can then reference the same secrets and variables without duplication.

Two separate SSH key pairs are involved — they serve different trust relationships:

| Key pair          | Private key lives                        | Public key lives                                   | Purpose                                                   |
| ----------------- | ---------------------------------------- | -------------------------------------------------- | --------------------------------------------------------- |
| CI runner key     | GitHub secret `DEPLOY_SSH_KEY`           | Kinsta server `~/.ssh/authorized_keys`             | Lets the GitHub Actions runner SSH into the Kinsta server |
| Server deploy key | Kinsta server `~/.ssh/id_ed25519_github` | GitHub → repo → Settings → Deploy keys (read-only) | Lets the Kinsta server `git fetch` from GitHub            |

**Private key → GitHub secret. Public key → the other end of each connection.**

The server-side deploy key needs only **read-only** access on GitHub — the server only fetches, never pushes.

## One-time server setup (before the workflow runs)

### Step 1 — Generate the CI runner key (on your local machine)

```bash
ssh-keygen -t ed25519 -C "github-actions@<sitename>" -f ~/.ssh/deploy_<sitename>
# Leave the passphrase empty — deploy keys must be unencrypted
```

Add the **public** half to the Kinsta server's authorized_keys:

```bash
# Option A — ssh-copy-id (if available)
ssh-copy-id -i ~/.ssh/deploy_<sitename>.pub -p <port> <user>@<server-ip>

# Option B — manual append
cat ~/.ssh/deploy_<sitename>.pub | ssh -p <port> <user>@<server-ip> \
  "mkdir -p ~/.ssh && cat >> ~/.ssh/authorized_keys"
```

Add the **private** half to GitHub:

```bash
gh secret set DEPLOY_SSH_KEY < ~/.ssh/deploy_<sitename> --repo <owner>/<repo>
```

### Step 2 — On the Kinsta server

SSH in using MyKinsta credentials, then:

```bash
# 1. Clone the repo into the deploy path (first time only)
git clone git@github.com:<owner>/<repo>.git <deploy-path>

# 2. Generate the server-to-GitHub deploy key
ssh-keygen -t ed25519 -C "kinsta-server@<sitename>" -f ~/.ssh/id_ed25519_github
# Leave the passphrase empty

# 3. Print the public key — you will add this to GitHub in the next step
cat ~/.ssh/id_ed25519_github.pub

# 4. Tell git to use this key for github.com connections
echo 'Host github.com
  IdentityFile ~/.ssh/id_ed25519_github' >> ~/.ssh/config
```

Add the printed public key in **GitHub → repo → Settings → Deploy keys** (read-only access is sufficient).

## Provision credentials via gh

Run these before committing or pushing the workflow file. They create placeholder variables that can be filled in via **GitHub → Settings → Variables → Actions** or updated with `gh variable set`.

```bash
# Variables (visible in logs — non-sensitive connection details only)
gh variable set DEPLOY_HOST --body "PLACEHOLDER" --repo <owner>/<repo>
gh variable set DEPLOY_PORT --body "PLACEHOLDER" --repo <owner>/<repo>
gh variable set DEPLOY_USER --body "PLACEHOLDER" --repo <owner>/<repo>
gh variable set DEPLOY_PATH --body "PLACEHOLDER" --repo <owner>/<repo>

# Secret (encrypted, never shown after creation)
gh secret set DEPLOY_SSH_KEY < ~/.ssh/deploy_<sitename> --repo <owner>/<repo>

# Confirm everything is present
gh variable list --repo <owner>/<repo>
gh secret list --repo <owner>/<repo>
```

Fill in the variable values from **MyKinsta → Sites → [site] → Info → SFTP/SSH**:

| Variable      | Value                                                                                     |
| ------------- | ----------------------------------------------------------------------------------------- |
| `DEPLOY_HOST` | Server IP address                                                                         |
| `DEPLOY_PORT` | SSH port                                                                                  |
| `DEPLOY_USER` | SSH username                                                                              |
| `DEPLOY_PATH` | Absolute path to the git root on the server (e.g. `/www/<site-folder>/public/wp-content`) |

## Workflow

```yaml
name: Deploy to Production

# Manually triggered. SSHs into the Kinsta server and runs a git pull —
# the server fetches and hard-resets to the selected branch from GitHub.
#
# Prerequisites (one-time, done on the server before this workflow runs):
#   1. Repo must be cloned at DEPLOY_PATH on the Kinsta server.
#   2. A deploy key must exist on the server (~/.ssh/id_ed25519_github)
#      and its public half must be added to GitHub → repo → Settings → Deploy keys.
#   3. ~/.ssh/config on the server must point github.com at that key.
#   4. The Kinsta IP allowlist must be disabled so the runner can reach the server.
#
# Shared GitHub repository variables (Settings → Variables):
#   DEPLOY_HOST   — server IP          (MyKinsta → Sites → Info → SFTP/SSH)
#   DEPLOY_PORT   — SSH port           (MyKinsta → Sites → Info → SFTP/SSH)
#   DEPLOY_USER   — SSH username       (MyKinsta → Sites → Info → SFTP/SSH)
#   DEPLOY_PATH   — absolute path to the git root on the server
#
# Shared GitHub repository secrets (Settings → Secrets):
#   DEPLOY_SSH_KEY — CI runner private key (public half is in server's authorized_keys)

on:
    workflow_dispatch:
        inputs:
            branch:
                description: "Branch to deploy to production."
                required: true
                default: "trunk"

permissions:
    contents: read

jobs:
    deploy:
        runs-on: ubuntu-latest
        steps:
            - name: Deploy via SSH (git pull on Kinsta server)
              uses: appleboy/ssh-action@v1
              with:
                  host: ${{ vars.DEPLOY_HOST }}
                  username: ${{ vars.DEPLOY_USER }}
                  key: ${{ secrets.DEPLOY_SSH_KEY }}
                  port: ${{ vars.DEPLOY_PORT }}
                  script: |
                      cd ${{ vars.DEPLOY_PATH }}
                      git fetch origin ${{ github.event.inputs.branch }}
                      git reset --hard origin/${{ github.event.inputs.branch }}

            - name: Remove deployignore files from server
              uses: appleboy/ssh-action@v1
              with:
                  host: ${{ vars.DEPLOY_HOST }}
                  username: ${{ vars.DEPLOY_USER }}
                  key: ${{ secrets.DEPLOY_SSH_KEY }}
                  port: ${{ vars.DEPLOY_PORT }}
                  script: |
                      cd ${{ vars.DEPLOY_PATH }}
                      grep -vE '^[[:space:]]*(#|$)' .deployignore \
                        | sed 's|/\*\*$||' \
                        | sort -u \
                        | while IFS= read -r path; do
                            rm -rf "$path"
                          done
                      rm -f .deployignore
```

## Deployignore cleanup

`git reset --hard` deploys every file tracked in the repo, including dev-only files like `.github/`, `composer.json`, `package.json`, and documentation — files you may have listed in `.deployignore` to exclude from rsync deploys. A second SSH step reads `.deployignore` from the server immediately after the pull and removes those paths.

**How the cleanup script works:**

```bash
grep -vE '^[[:space:]]*(#|$)' .deployignore \   # strip blank lines and comments
  | sed 's|/\*\*$||' \                           # convert dir/** → dir
  | sort -u \                                    # deduplicate (dir and dir/** both reduce to dir)
  | while IFS= read -r path; do
      rm -rf "$path"
    done
rm -f .deployignore                              # remove the deployignore itself — CI artifact
```

`.deployignore` is restored by `git reset --hard` on every deploy, read by the cleanup step, then removed. The server never holds it between deployments.

**Before running the first deploy**, check that every path in `.deployignore` that you want excluded is actually tracked in git:

```bash
# From the repo root — lists .deployignore entries that are tracked in git
grep -vE '^[[:space:]]*(#|$)' .deployignore \
  | sed 's|/\*\*$||' | sort -u \
  | xargs git ls-files
```

Any path listed there will be deployed then immediately removed. Paths not listed by `git ls-files` were never in the repo and don't need cleanup.

## Notes

- **IP allowlist conflict**: If the Kinsta site has an IP allowlist configured, GitHub Actions runner IPs will be blocked. Disable the allowlist before the workflow can succeed — this is the most common failure mode.
- **Pull model, not push**: There is no rsync step. The server fetches and hard-resets to the pushed branch. Files not tracked in git (uploads, cache) are untouched.
- **`appleboy/ssh-action` version**: Pin to a specific version tag. Check the [action's releases](https://github.com/appleboy/ssh-action/releases) for the current stable tag.

## Drift detection

Two options, depending on whether the IP allowlist is active:

### Option A — rsync from GitHub Actions (requires no IP allowlist; key auth already set up)

With key-based auth configured (the CI runner key from the one-time setup above), the standard drift-detection workflow can connect rsync over SSH to Kinsta. The same `DEPLOY_*` secrets and variables used for deployment apply directly — no additional credential setup needed.

Prerequisite: the Kinsta IP allowlist must be disabled, or GitHub Actions runner IP ranges must be explicitly permitted.

If both conditions are met, follow the standard drift-detection template in the parent skill — it already uses `DEPLOY_SSH_KEY`, `DEPLOY_HOST`, `DEPLOY_PORT`, `DEPLOY_USER`, and `DEPLOY_PATH`.

### Option B — git status on the server (IP allowlist active, git-pull deployment model)

SSH directly into the Kinsta server and check git state:

```bash
ssh -p <port> <user>@<server-ip>

# Show files changed directly on the server vs. the last deployed commit
cd <deploy-path>
git status
git diff --stat
```

`git status` shows files modified since the last `git reset --hard`. These are cowboy edits that would be overwritten on next deploy. `git diff` shows the actual changes line by line.

This approach only works if the git-pull deployment model is in use (i.e. the repo is cloned on the server). If the one-time server setup has not been run, there is no `.git` folder on the server and this option is unavailable.
