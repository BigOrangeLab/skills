# Generic SSH / Kinsta Deployment

Covers any host reachable via SSH where rsync is available. Kinsta uses this pattern — SSH credentials and port are found in the Kinsta dashboard under **Sites → SSH/SFTP**.

## Workflow

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

## Required secrets/variables

| Name | Type | Value |
|------|------|-------|
| `DEPLOY_SSH_KEY` | Secret | Private key whose public half is authorized on the server |
| `DEPLOY_HOST` | Variable | Server hostname or IP |
| `DEPLOY_PORT` | Variable | SSH port (default 22; Kinsta varies per site — check dashboard) |
| `DEPLOY_USER` | Variable | SSH username |
| `DEPLOY_PATH` | Variable | Absolute path on server to deploy into |

## Notes

- **`--delete`** removes files on the server that were deleted from the repo. Remove it if the host puts files outside version control in the same directory (e.g. uploads, cache).
- **Kinsta "Push to deploy" webhook** exists as an alternative to rsync but gives less control over what is and isn't deployed. rsync with `.deployignore` is preferred.
- For Kinsta, the SSH port is assigned per site — do not assume 22.
