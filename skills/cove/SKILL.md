---
name: cove
description: "Spin up and manage local WordPress (and static) sites with the Cove CLI — automatic HTTPS, one-click admin login, shared MariaDB, Mailpit, no Docker. Use when an agent needs a fast disposable or persistent local WordPress dev site on macOS, Linux, or WSL2."
compatibility: "Cove v1.10 on macOS, Linux (Ubuntu/Debian/Fedora/RHEL), or WSL2 (systemd required). Bundles Caddy, FrankenPHP (PHP + WP-CLI), MariaDB, Mailpit, Adminer, whoops. MIT licensed."
license: GPL-2.0-or-later
metadata:
    author: georgestephanis
    version: "1.0"
    written: "2026-06-18"
    written_against:
        cove: "1.10"
---

# cove

[Cove](https://cove.run) ([anchorhost/cove](https://github.com/anchorhost/cove)) is a CLI from Anchor Hosting for running local WordPress sites on the "Franken stack": **Caddy** (auto-HTTPS via a local root CA), **FrankenPHP** (one binary powers both the web server and `wp-cli` — no separate PHP install), **MariaDB** (one shared instance), **Mailpit** (catches outgoing mail), plus Adminer and whoops. Every site is served at `https://<name>.localhost`.

It is CLI-first and lightweight. If you know `cove add`, `cove list`, and `cove login`, you can get someone a working site. It coexists with Local, Studio, and DevKinsta (the installer detects port conflicts and offers `8090`/`8453`).

## When to use

- Standing up a quick local WordPress site for development, testing, or a demo
- Creating a plain/static local site served over HTTPS (`--plain`)
- Pulling a remote WordPress site down locally, or pushing a local site up, over SSH
- Sharing a work-in-progress site via a public tunnel (Cloudflare) or across your own devices (Tailscale/LAN)
- Reverse-proxying any local service (e.g. a dev server on a port) behind trusted HTTPS

Prefer Cove when the user wants a real PHP/MariaDB stack with WP-CLI and persistent sites. For throwaway in-browser sandboxes, WordPress Playground (the `wp-playground` skill) may be a better fit; for the user's existing Local-by-WPEngine sites, see `local-wp-db`.

## Inputs required

- **Cove installed** — verify with `cove version`; if missing, see [Installation](#1-verify-or-install-cove).
- **Site name** — lowercase letters, numbers, and hyphens only; cannot start or end with a hyphen. Becomes `<name>.localhost`.
- **For `pull`/`push`** — SSH connection string (`user@host -p PORT`) and the remote WordPress root path.
- **Background services running** — `cove status`; start with `cove enable` if needed.

## Procedure

### 1. Verify or install Cove

```bash
cove version           # confirms it's installed
cove status            # confirms Caddy, MariaDB, Mailpit are running
```

If not installed (macOS / Linux / WSL2):

```bash
bash <(curl -sL https://cove.run/install-cove.sh)
```

On macOS the installer offers to install Homebrew first if absent. If ports 80/443 are taken (e.g. by Local), pick **"Use alternative ports (8090 / 8453)"** — Caddy's auto-HTTPS handles non-default ports transparently. See [references/installation.md](references/installation.md) for WSL2 setup and trust-store details.

### 2. Create a site

```bash
cove add myblog            # WordPress at https://myblog.localhost
cove add docs --plain      # static/plain site (no WordPress, no DB)
```

`cove add` creates the directory under `~/Cove/Sites/myblog.localhost/`, provisions a database (`cove_myblog`), installs WordPress, and prints admin credentials. WP-CLI runs through FrankenPHP automatically.

### 3. Log in and work

```bash
cove login myblog              # one-time admin login URL (default user)
cove login myblog editor       # log in as a specific user
cove path myblog               # absolute path to the site's public/ dir
cove url myblog                # full HTTPS URL
cove list                      # all sites; add --totals for disk usage
```

Run WP-CLI from inside the site's `public/` directory (Cove's FrankenPHP provides `wp`). Get there with:

```bash
cd "$(cove path myblog)"
wp plugin list
```

### 4. Inspect mail, database, errors

- **Dashboard**: `https://cove.localhost` — filter (`/`), sort, one-click login, disk usage, links to Adminer & Mailpit.
- **Mailpit** inbox: `https://mailpit.localhost` — catches all outgoing mail.
- **Adminer** (DB UI) and credentials: `cove db list`.
- **Logs**: `cove log myblog -f` follows the site error log live.

### 5. Back up, rename, delete

```bash
cove db backup                 # timestamped .sql snapshot of every database
cove rename old new            # renames dir, DB, and runs wp search-replace on URLs
cove delete myblog             # removes directory + database (--force to skip prompt)
```

For migration (`pull`/`push`), sharing (`share`/`tailscale`/`lan`), ports, proxying, and memory tuning, see [references/commands.md](references/commands.md).

## Verification

- `cove list` shows the new site.
- `cove url <name>` opens in a browser without a certificate warning. If it warns "Not Secure," run `cove trust` to install Cove's root CA into the OS/browser trust stores.
- `cove login <name>` lands you in `wp-admin` without a password prompt.
- Outgoing test mail appears in Mailpit (`https://mailpit.localhost`), not a real inbox.

## Failure modes

- **`cove add` fails with a database error** — MariaDB isn't running or config is missing. Run `cove status`, then `cove enable`. On macOS, `brew services restart mariadb` then re-run `cove enable`. Check `~/Cove/config` has valid `DB_USER` / `DB_PASSWORD`.
- **Browser shows "Not Secure" / "Not private"** — the local root CA isn't trusted. Run `cove trust` (idempotent).
- **Invalid site name** — names allow only lowercase letters, numbers, hyphens, and cannot begin/end with a hyphen. Some names are reserved (e.g. `cove`, `mailpit`).
- **Port conflict with Local/Studio/DevKinsta** — switch with `cove ports --http 8090 --https 8453`; Cove rewrites existing WordPress URLs via `wp search-replace`. Use `--dry-run` to preview. Never use `--skip-urls` unless you intend to fix `siteurl`/`home` manually afterward.
- **WSL2: `myblog.localhost` unreachable from Windows** — run `cove wsl-hosts` inside WSL and apply the printed PowerShell snippet to the Windows hosts file. WSL2 also requires `systemd=true` in `/etc/wsl.conf`.
- **Site exists already** — `cove add` refuses to overwrite; pick another name or `cove delete` first.

## Escalation

- Behavior not covered here: read the in-tool help and the source ([github.com/anchorhost/cove](https://github.com/anchorhost/cove), `commands/` directory).
- Destructive operations against a real/remote site (`cove push`, `cove pull` overwriting an existing local site, `cove delete --force`): confirm the target environment with the user first — `push` writes to a remote host over SSH.
- Bugs or missing features: file upstream at [github.com/anchorhost/cove/issues](https://github.com/anchorhost/cove/issues).
