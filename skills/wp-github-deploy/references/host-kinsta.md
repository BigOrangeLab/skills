# Kinsta Deployment

Kinsta's recommended approach is a **server-side git pull** model using `appleboy/ssh-action`. The GitHub Actions runner SSHs into the Kinsta server and runs `git fetch && git reset --hard` — the server pulls from GitHub rather than CI pushing files to the server.

This differs from the generic rsync approach: the Kinsta server must have the repo cloned at the deploy path, and the server itself needs a deploy key authorised on GitHub to pull commits.

SSH credentials are found in **MyKinsta → Sites → [site] → Info → SFTP/SSH credentials**.

## One-time server setup (before the workflow runs)

These steps are done once on the Kinsta server, not in the workflow:

```bash
# 1. SSH into the Kinsta server
ssh <username>@<server-ip> -p <port>

# 2. Clone the repo into the deploy path (first time only)
git clone git@github.com:<owner>/<repo>.git /www/<site-folder>/public

# 3. Generate a deploy key on the server (so the server can pull from GitHub)
ssh-keygen -t ed25519 -C "kinsta-deploy@<sitename>" -f ~/.ssh/id_ed25519_github
# When prompted for a passphrase, leave it empty

# 4. Print the public key — add this to GitHub as a Deploy Key (read-only)
cat ~/.ssh/id_ed25519_github.pub

# 5. Tell git on the server to use this key for GitHub
echo 'Host github.com
  IdentityFile ~/.ssh/id_ed25519_github' >> ~/.ssh/config
```

Add the public key in **GitHub → repo → Settings → Deploy keys** (read-only access is sufficient).

## Workflow

```yaml
name: Deploy to Kinsta

on:
    push:
        branches:
            - trunk # change to match the repo's default branch

jobs:
    deploy:
        runs-on: ubuntu-latest
        steps:
            - name: Deploy via SSH (git pull on server)
              uses: appleboy/ssh-action@v1
              with:
                  host: ${{ secrets.KINSTA_SERVER_IP }}
                  username: ${{ secrets.KINSTA_USERNAME }}
                  password: ${{ secrets.KINSTA_PASSWORD }}
                  port: ${{ secrets.KINSTA_PORT }}
                  script: |
                      cd /www/${{ vars.KINSTA_SITE_FOLDER }}/public
                      git fetch origin ${{ github.ref_name }}
                      git reset --hard origin/${{ github.ref_name }}
```

## Required secrets/variables

| Name                 | Type     | Value                                                                            |
| -------------------- | -------- | -------------------------------------------------------------------------------- |
| `KINSTA_SERVER_IP`   | Secret   | Host IP address from MyKinsta Info tab                                           |
| `KINSTA_USERNAME`    | Secret   | SFTP/SSH username from MyKinsta Info tab                                         |
| `KINSTA_PASSWORD`    | Secret   | SFTP/SSH password from MyKinsta Info tab                                         |
| `KINSTA_PORT`        | Secret   | SSH port from MyKinsta Info tab (varies per site)                                |
| `KINSTA_SITE_FOLDER` | Variable | Site folder name (e.g. `mysitename`); deploy path becomes `/www/<folder>/public` |

## Notes

- **IP allowlist conflict**: If the Kinsta site has an IP allowlist configured, GitHub Actions runner IPs will be blocked. Disable the allowlist before the workflow can succeed — this is the most common failure mode.
- **Pull model, not push**: There is no rsync step. The server fetches and hard-resets to the pushed branch. Files not tracked in git (uploads, cache) are untouched.
- **`.deployignore` does not apply** — this model doesn't use rsync's `--exclude-from`. Use `.gitignore` to keep files out of the repo and therefore out of the deployment.
- **Password vs key auth**: The workflow uses Kinsta's SFTP/SSH password to authenticate the CI runner to the server. The _server's_ deploy key (set up in the one-time step above) handles the server-to-GitHub connection.
- **`appleboy/ssh-action` version**: Pin to a specific version tag. Check the [action's releases](https://github.com/appleboy/ssh-action/releases) for the current stable tag.

## Drift detection

Two options, depending on whether the IP allowlist is active:

### Option A — rsync from GitHub Actions (untested; requires no IP allowlist)

Kinsta provides SSH access on a per-site port. In theory, the standard drift-detection workflow from the parent skill can connect rsync over that SSH tunnel — but this has not been confirmed in practice. Two prerequisites must be met before attempting it:

1. **No IP allowlist**: Kinsta's IP allowlist must be disabled, or GitHub Actions runner IP ranges must be explicitly permitted. This is the most common failure point.
2. **Key-based SSH auth**: The drift-detection workflow uses `webfactory/ssh-agent` for key-based authentication. Kinsta's default credential is a password (shown in MyKinsta). You will need to add an SSH public key to the Kinsta server's `~/.ssh/authorized_keys` to use key auth.

If both conditions are met, use the same secrets/variables as the deployment workflow (`KINSTA_SERVER_IP` → `DEPLOY_HOST`, etc.) and follow the standard drift-detection template.

### Option B — git status on the server (IP allowlist active, git-pull deployment model)

SSH directly into the Kinsta server and check git state:

```bash
ssh <username>@<server-ip> -p <port>

# Show files changed directly on the server vs. the last deployed commit
cd /www/<site-folder>/public
git status
git diff --stat
```

`git status` shows files modified since the last `git reset --hard`. These are cowboy edits that would be overwritten on next deploy. `git diff` shows the actual changes line by line.

This approach only works if the git-pull deployment model is in use (i.e. the repo is cloned on the server). If you have never run the one-time server setup from this file, there is no `.git` folder on the server and this option is unavailable.
