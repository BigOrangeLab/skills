---
name: local-wp-db
description: "Use when querying or inspecting the database of a WordPress site running under Local by WPEngine (formerly Local by Flywheel) on macOS. Covers socket-based MySQL connection, site ID discovery, and WP-CLI availability."
compatibility: "macOS. Requires Local by WPEngine running with the target site started. MySQL client must be installed (Homebrew: brew install mysql-client)."
license: MIT
metadata:
  author: georgestephanis
  version: "1.0"
  written: "2026-05-22"
  written_against:
    local-by-wpengine: "6.x"
    mysql: "8.0"
---

# Local by WPEngine — Database Access

Local runs each site's MySQL instance in an isolated container. Connecting via TCP (`127.0.0.1` or `localhost`) is unreliable — the Local MySQL instance blocks `127.0.0.1`, and `localhost` can silently hit a different MySQL server if another one is running. The correct approach is always the site-specific **Unix socket**.

## When to use

- Running SQL queries against a Local WordPress site for development or debugging
- Inspecting tables, options, post content, or taxonomy data
- Scripting DB operations against a Local site in a shell or CI-like context

Do NOT use for production or staging databases. For Pantheon-hosted sites, use `terminus-wp-cli` instead.

## Inputs required

- **Local site name or slug** — the name shown in the Local UI, or the directory name under `~/Local Sites/`
- **Local must be running** with the target site started (green status in the Local UI)
- **MySQL client** — `mysql` on `$PATH` (`brew install mysql-client` if missing; add `/opt/homebrew/opt/mysql-client/bin` to `$PATH`)

## Procedure

### 1. Look up the site ID

Local stores all site metadata in `~/Library/Application Support/Local/sites.json`. Search by site name or slug:

```bash
rg -n "Acme\|acme" "$HOME/Library/Application Support/Local/sites.json"
```

The matching entry contains the Local internal **site ID** (a short alphanumeric string, e.g. `aBc1d2EFG`).

If `rg` is not available, use `grep`:

```bash
grep -n "Acme\|acme" "$HOME/Library/Application Support/Local/sites.json"
```

### 2. Construct the socket path

```
~/Library/Application Support/Local/run/<SITE_ID>/mysql/mysqld.sock
```

Verify it exists before connecting:

```bash
ls "$HOME/Library/Application Support/Local/run/<SITE_ID>/mysql/mysqld.sock"
```

If the file is missing, the site is not running — start it in Local and retry.

### 3. Set connection variables

```bash
export LOCAL_SOCKET="$HOME/Library/Application Support/Local/run/<SITE_ID>/mysql/mysqld.sock"
export LOCAL_DB="local"
export LOCAL_USER="root"
export LOCAL_PASS="root"
```

Credentials are the same for all Local sites unless explicitly changed in Local's database settings panel.

### 4. Connect

**Interactive SQL prompt:**

```bash
mysql --socket="$LOCAL_SOCKET" -u"$LOCAL_USER" -p"$LOCAL_PASS" -D "$LOCAL_DB"
```

**One-off query:**

```bash
mysql --socket="$LOCAL_SOCKET" -u"$LOCAL_USER" -p"$LOCAL_PASS" -D "$LOCAL_DB" \
  -e "SELECT NOW();"
```

**Useful starting queries:**

```bash
# List all tables
mysql --socket="$LOCAL_SOCKET" -u"$LOCAL_USER" -p"$LOCAL_PASS" -D "$LOCAL_DB" \
  -e "SHOW TABLES;"

# Count published posts by post type
mysql --socket="$LOCAL_SOCKET" -u"$LOCAL_USER" -p"$LOCAL_PASS" -D "$LOCAL_DB" -e "
SELECT post_type, COUNT(*) AS count
FROM wp_posts
WHERE post_status = 'publish'
GROUP BY post_type
ORDER BY count DESC;
"

# Inspect a specific option
mysql --socket="$LOCAL_SOCKET" -u"$LOCAL_USER" -p"$LOCAL_PASS" -D "$LOCAL_DB" \
  -e "SELECT option_name, option_value FROM wp_options WHERE option_name = 'siteurl';"
```

### 5. WP-CLI alternative (when available)

If `wp` is on `$PATH`, run from the site's WordPress root instead:

```bash
wp --path="$HOME/Local Sites/<site-slug>/app/public" db query "SELECT NOW();"
wp --path="$HOME/Local Sites/<site-slug>/app/public" option get siteurl
```

WP-CLI is often not on the shell path in non-interactive environments. Fall back to the socket approach above if `wp: command not found`.

## Verification

- `SELECT NOW();` returns the current timestamp without error
- `SHOW TABLES;` lists the expected `wp_*` tables
- No `ERROR 2002 (HY000)` or `ERROR 1130` messages

## Failure modes

**`ERROR 1130 (HY000): Host '127.0.0.1' is not allowed to connect`**
— TCP was used instead of the socket. Ensure `--socket=` is present and not overridden by a `~/.my.cnf` default host.

**`ERROR 2002 (HY000): Can't connect to local MySQL server through socket`**
— The socket file does not exist. Either the site is not running (start it in Local) or the site ID is wrong (re-check `sites.json`).

**`bash: mysql: command not found`**
— MySQL client not installed or not on `$PATH`. Run `brew install mysql-client` and add `/opt/homebrew/opt/mysql-client/bin` to `$PATH`.

**`bash: wp: command not found`**
— WP-CLI is not available in the current shell. Use the `mysql --socket=...` commands instead.

**`Error establishing a database connection` in the browser**
— The Local site stopped. Start it in the Local UI; the socket file will reappear once the site is running.

**Socket path changed after reinstalling Local or recreating the site**
— The site ID embedded in the path may have changed. Re-run the `sites.json` search (step 1) to get the new ID.

## Escalation

- If `sites.json` does not contain the expected site, open Local and confirm the site appears in its sidebar — it may not have been imported yet.
- If Local is running but the socket never appears, try stopping and restarting the site from the Local UI; the MySQL container sometimes needs a fresh start after a macOS sleep/wake cycle.
- For persistent MySQL container failures, check Local's log at `~/Library/Logs/local-by-flywheel.log` or via Local → Help → View Logs.
