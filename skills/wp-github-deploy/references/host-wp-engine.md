# WP Engine Deployment

Uses WP Engine's official GitHub Action, which wraps their SSH Git Push feature. Simplest setup of all the supported hosts.

## Workflow

```yaml
name: Deploy to WP Engine

on:
  push:
    branches:
      - trunk   # change to match the repo's default branch

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

## Required secrets/variables

| Name | Type | Value |
|------|------|-------|
| `WPE_SSHG_KEY_PRIVATE` | Secret | WP Engine SSH Gateway private key |
| `WPE_ENV` | Variable | WP Engine environment name (install slug, e.g. `mysiteprod`) |

## Notes

- **Key authorisation**: The SSH Gateway key's *public* half must be added to the WP Engine portal under **Users → Your Profile → SSH Keys** — adding it only to `authorized_keys` on the server is not sufficient.
- **Parameter names change between major versions** — verify `WPE_ENV`, `SRC_PATH`, `REMOTE_PATH`, and `EXCLUDES` against the action's README for the pinned version before use. See [WP Engine's official docs](https://wpengine.com/support/github-action-deploy/) for key generation and the current parameter reference.
- **`REMOTE_PATH`** should be `wp-content/` for a wp-content-rooted repo, or `./` for a WordPress-root repo.
- **Staging environments**: Change `WPE_ENV` to the staging install slug and target a different branch for a staging deploy workflow.
