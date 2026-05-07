# Common Terminus WP-CLI Commands

All commands follow the pattern:

```bash
terminus remote:wp <site>.<env> -- <wp-cli-command>
```

## Site info

```bash
terminus remote:wp <site>.dev -- --info          # WP-CLI version and environment
terminus remote:wp <site>.live -- core version   # WordPress version
```

## Cache

```bash
terminus remote:wp <site>.live -- cache flush
```

## Plugins

```bash
terminus remote:wp <site>.dev -- plugin list
terminus remote:wp <site>.dev -- plugin activate <slug>
terminus remote:wp <site>.dev -- plugin deactivate <slug>
terminus remote:wp <site>.live --yes -- plugin update --all
```

## Options

```bash
terminus remote:wp <site>.dev -- option get siteurl
terminus remote:wp <site>.dev -- option update blogname "New Site Title"
```

## Users

```bash
terminus remote:wp <site>.live -- user list
terminus remote:wp <site>.dev -- user create newuser newuser@example.com --role=editor
terminus remote:wp <site>.dev -- user get newuser --field=ID
```

## Search-replace (e.g. after a database clone)

```bash
terminus remote:wp <site>.dev -- search-replace 'https://example.com' 'https://dev-example.pantheonsite.io' --all-tables
```

Always run with `--dry-run` first to preview changes:

```bash
terminus remote:wp <site>.dev -- search-replace 'https://example.com' 'https://dev-example.pantheonsite.io' --all-tables --dry-run
```

## Database export / import

```bash
# Export to the environment's /tmp directory
terminus remote:wp <site>.dev -- db export --add-drop-table /tmp/backup.sql

# Import
terminus remote:wp <site>.dev -- db import /tmp/backup.sql
```

## Cron

WP Cron is disabled on Pantheon (`DISABLE_WP_CRON = true`) and run by Pantheon's scheduler. Manual triggers are useful for debugging.

```bash
terminus remote:wp <site>.live -- cron event list
terminus remote:wp <site>.live -- cron event run --due-now
terminus remote:wp <site>.live -- cron event run <hook-name>
```

## Useful flags

| Flag | Purpose |
|---|---|
| `--yes` | Auto-confirm all WP-CLI prompts |
| `--no-interaction` | Suppress all interactive questions |
| `--progress` | Show a progress bar (requires TTY) |
| `--retry=N` | Retry on failure up to N times |
| `-v` / `-vv` / `-vvv` | Increase verbosity for debugging |

Example combining Terminus and WP-CLI flags:

```bash
terminus remote:wp <site>.live --yes -- plugin update --all --no-interaction
```

## Further reading

- [WP-CLI on Pantheon](https://docs.pantheon.io/guides/wp-cli)
- [Terminus Commands Reference](https://docs.pantheon.io/terminus/commands)
