# Cove command reference

Complete command list for Cove v1.10. Run `cove <command>` with no/invalid args to see inline usage. Source: [github.com/anchorhost/cove](https://github.com/anchorhost/cove) (`commands/` directory).

## Site management

| Command                        | Description                                                                                                                                                                                     |
| ------------------------------ | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `cove add <name> [--plain]`    | Create a new site at `<name>.localhost`. WordPress by default; `--plain` makes a static site (no DB, branded landing page). Provisions DB `cove_<name>`, installs WP, prints admin credentials. |
| `cove delete <name> [--force]` | Delete the site's directory and database. `--force` skips the confirmation prompt.                                                                                                              |
| `cove rename <old> <new>`      | Rename the site, its directory, and database, then run `wp search-replace` so `siteurl`, `home`, and serialized content update to the new domain.                                               |
| `cove list [--totals]`         | List all managed sites. `--totals` shows per-site disk usage.                                                                                                                                   |
| `cove login <site> [<user>]`   | Generate a one-time admin login URL (defaults to the admin user; pass a username to log in as someone else).                                                                                    |
| `cove path <name>`             | Print the absolute filesystem path to the site's `public/` directory.                                                                                                                           |
| `cove url <name>`              | Print the full HTTPS URL for the site.                                                                                                                                                          |
| `cove log [<site>] [-f]`       | Show error logs; `-f` follows in real time.                                                                                                                                                     |

## Services & status

| Command        | Description                                                                                                                                             |
| -------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `cove enable`  | Start Caddy, MariaDB, and Mailpit background services. On Linux, also installs a `cove.service` systemd unit so the stack survives reboot.              |
| `cove disable` | Stop Cove's background services.                                                                                                                        |
| `cove status`  | Check whether background services are running.                                                                                                          |
| `cove reload`  | Reload Caddy configuration.                                                                                                                             |
| `cove trust`   | Install Cove's local root CA ("Cove Local Authority") into OS and browser (NSS/Firefox/Chromium) trust stores. Idempotent; fixes "Not Secure" warnings. |
| `cove install` | Install/repair dependencies (runs as part of first setup).                                                                                              |
| `cove upgrade` | Upgrade Cove, FrankenPHP, and Adminer to the latest versions.                                                                                           |
| `cove version` | Print the Cove version.                                                                                                                                 |

## Database

| Command          | Description                                                                   |
| ---------------- | ----------------------------------------------------------------------------- |
| `cove db backup` | Write a timestamped `.sql` snapshot of every database.                        |
| `cove db list`   | Show database credentials for all WordPress sites (and shared MariaDB creds). |

Adminer (web DB UI) is linked from the dashboard at `https://cove.localhost`. Mailpit inbox is at `https://mailpit.localhost`.

## Migration (SSH)

| Command                       | Description                                                                                                                                                                                                                                                                                                                                                                  |
| ----------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `cove pull [--proxy-uploads]` | Interactively pull a remote WordPress site into Cove over SSH. Prompts for the SSH connection (`user@host -p PORT`) and the remote WordPress root path. Uses a CaptainCore backup/migrate helper and rewrites URLs to the local domain. `--proxy-uploads` skips downloading `wp-content/uploads` and instead adds a Caddy directive that proxies uploads from the live site. |
| `cove push`                   | Push a local Cove site up to a remote WordPress host over SSH. **Writes to the remote — confirm the target with the user first.**                                                                                                                                                                                                                                            |

`pull` uses `ssh` with `StrictHostKeyChecking=no` and a shared ControlMaster connection for the session. The SSH user must have `wp-cli` available on the remote.

## Ports

| Command                             | Description                                                                                                                          |
| ----------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------ |
| `cove ports`                        | Interactive menu to keep/reset/customize HTTP and HTTPS ports.                                                                       |
| `cove ports --http <N> --https <N>` | Set ports non-interactively (e.g. `--http 80 --https 443` for defaults, `--http 8090 --https 8453` to coexist with Local/DevKinsta). |
| `cove ports --dry-run`              | Preview the effect of a port change without applying it.                                                                             |
| `cove ports --skip-urls`            | Change ports **without** rewriting WordPress URLs (leaves `siteurl`/`home` on the old port — only use if you'll fix them manually).  |

When the HTTPS port changes, Cove walks every WordPress site under `~/Cove/Sites/` and runs `wp search-replace` to keep stored URLs working. Non-WordPress sites are skipped.

## Caddy directives, mappings, proxy

| Command                                             | Description                                   |
| --------------------------------------------------- | --------------------------------------------- |
| `cove directive <add\|update\|delete\|list> [site]` | Manage raw Caddyfile directives for a site.   |
| `cove mappings <site> [add\|remove] [domain]`       | Manage additional hostnames mapped to a site. |
| `cove proxy <add\|list\|delete>`                    | Manage reverse-proxy entries.                 |

Example — expose a local service (e.g. an OpenCode web UI on port 4096) behind trusted HTTPS:

```bash
cove add opencode --plain
cove directive add opencode.localhost "reverse_proxy 127.0.0.1:4096"
# now https://opencode.localhost proxies to 127.0.0.1:4096
cove directive delete opencode.localhost   # remove later
```

## Sharing & network access

| Command                                            | Description                                                                                                                                                                                                       |
| -------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `cove share [site]`                                | Create a public HTTPS URL via a Cloudflare tunnel. Installs `cloudflared` on demand. Good for short-lived WIP sharing.                                                                                            |
| `cove tailscale <enable\|disable\|status>`         | Expose sites across your Tailscale network via port-based routing. Auto-detects your tailnet hostname; each site gets a unique port (e.g. `https://your-laptop.tail1234.ts.net:9001`). Longer-lived than `share`. |
| `cove lan <enable\|disable\|status\|trust> [site]` | Expose sites on the local network via Bonjour/mDNS (e.g. test on a phone). `trust` helps install the CA on the LAN device.                                                                                        |
| `cove wsl-hosts`                                   | Print a PowerShell snippet to add Cove hostnames to the Windows hosts file so `*.localhost` resolves from Windows browsers under WSL2.                                                                            |

## PHP memory

| Command                   | Description                                                                                                                                                                                                                                      |
| ------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| `cove memory`             | Report `memory_limit` across Cove's `~/Cove/php.ini`, the Caddyfile FrankenPHP block, every `php` on `PATH`, and wp-cli's effective PHP.                                                                                                         |
| `cove memory set <value>` | Set `memory_limit`, `upload_max_filesize`, and `post_max_size` in Cove's ini (e.g. `512M`, `2G`, or `-1` for unlimited), regenerate the Caddyfile, and offer to update external inis. Add `--yes` to accept all writable inis non-interactively. |

## Key paths

- `~/Cove/Sites/<name>.localhost/public/` — site web root (run `wp` from here).
- `~/Cove/Sites/<name>.localhost/logs/` — per-site logs.
- `~/Cove/config` — global config including `DB_USER` / `DB_PASSWORD`.
- `~/Cove/php.ini` — Cove's PHP ini (source of truth for memory limits).
