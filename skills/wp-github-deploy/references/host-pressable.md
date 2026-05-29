# Pressable Deployment

Pressable (an Automattic-owned managed WordPress host) supports SSH access and standard rsync deployment. There is no official GitHub Action — use the generic rsync pattern with Pressable-specific connection details.

SSH credentials are found in the Pressable control panel under **My Sites → [site] → SSH/SFTP Details**.

## Workflow

```yaml
name: Deploy to Pressable

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

## Required secrets/variables

| Name | Type | Value |
|------|------|-------|
| `DEPLOY_SSH_KEY` | Secret | Private key whose public half is added to Pressable (see below) |
| `DEPLOY_HOST` | Variable | SSH hostname from the Pressable dashboard (e.g. `ssh.pressable.com`) |
| `DEPLOY_PORT` | Variable | SSH port from the dashboard (commonly `22`) |
| `DEPLOY_USER` | Variable | SSH username from the dashboard |
| `DEPLOY_PATH` | Variable | Absolute path to deploy into — typically `/srv/htdocs/wp-content/` for a wp-content-rooted repo, or `/srv/htdocs/` for a WordPress-root repo |

## Adding the deploy key to Pressable

Pressable does not currently support adding SSH public keys via the dashboard for automated deployments in the same way WP Engine does. Options:

1. **Use the site's existing SSH password auth** — set the `DEPLOY_SSH_KEY` secret to a key pair you've manually authorised via the Pressable support team or through their SSH key management if available on your plan.
2. **Confirm current key management UI** — Pressable's control panel changes; check the dashboard's SSH/SFTP section or contact Pressable support to confirm how to authorise a deploy-specific public key before provisioning.

## Notes

- **Deploy path**: Confirm by SSHing in and running `pwd` from the WordPress root — do not assume `/srv/htdocs/`.
- **Pressable's managed environment**: Pressable manages WordPress core upgrades and some plugin/theme auto-updates independently of deployments. After deploying, verify that no managed update has overwritten deployed files.
- **`--delete` caveat**: Pressable may place managed files (cache, object-cache drops) in `wp-content/` directories. Review what `--delete` would remove before using it; consider excluding those directories explicitly.
