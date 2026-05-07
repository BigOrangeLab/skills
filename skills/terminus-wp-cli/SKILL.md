---
name: terminus-wp-cli
description: "Run WP-CLI commands on Pantheon environments via Terminus. Use when SSH WP-CLI is unavailable and you need to manage WordPress on dev/test/live/multidev."
compatibility: "Pantheon-hosted WordPress sites. Requires Terminus ≥3, PHP 8.2+, OpenSSH 7.8+."
---

# Terminus WP-CLI

Pantheon does not expose direct SSH WP-CLI access. All remote WP-CLI work goes through **Terminus**, which relays commands to the target environment.

## When to use

- Running WP-CLI against any Pantheon environment (dev, test, live, multidev)
- Flushing caches, managing plugins/users/options, running cron, or importing/exporting databases on Pantheon
- Scripting WordPress operations in CI/CD pipelines targeting Pantheon

Do NOT use for self-hosted or non-Pantheon WordPress installs — use `wp` directly there.

## Inputs required

- **Site machine name** — found in the Pantheon dashboard URL (`dashboard.pantheon.io/sites/<name>`) or from the project's README
- **Target environment** — `dev`, `test`, `live`, or a multidev branch name
- **WP-CLI command** — the command to run (without the leading `wp`)
- **Terminus installed and authenticated** — see Procedure step 1 if not

## Procedure

### 1. Verify Terminus is installed and authenticated

```bash
terminus --version      # expect 3.x
terminus auth:whoami    # should print your Pantheon email
```

If not installed, see [references/installation.md](references/installation.md).

If not authenticated:

```bash
terminus auth:login --machine-token=<TOKEN> --email=<you@example.com>
```

Generate a machine token at **Pantheon dashboard → Personal Settings → Machine Tokens**.

### 2. Confirm SSH key is loaded (avoids password prompts)

```bash
ssh-add -l    # should list at least one key
```

If empty: `ssh-add ~/.ssh/id_ed25519` (or your key path). Add the public key to **Pantheon dashboard → Personal Settings → SSH Keys** if not already present.

### 3. Run the WP-CLI command

```bash
terminus remote:wp <site>.<env> -- <wp-cli-command>
```

Everything after `--` is passed verbatim to WP-CLI on the remote environment.

#### Environment notes

| `<env>` | Pantheon environment | Notes |
|---|---|---|
| `dev` | Development | File writes allowed; safe for testing |
| `test` | Staging | `DISALLOW_FILE_MODS=true`; no UI installs, but WP-CLI works |
| `live` | Production | `DISALLOW_FILE_MODS=true`; proceed carefully |
| `<branch>` | Multidev | Any multidev by its branch name |

For common command patterns, see [references/commands.md](references/commands.md).
For Pantheon-specific WP-CLI commands (page cache, sessions), see [references/pantheon-specific.md](references/pantheon-specific.md).

### 4. For scripted / non-interactive use, add flags

```bash
terminus remote:wp <site>.<env> --yes -- <command> --yes
```

Pass `--yes` to both Terminus (before `--`) and WP-CLI (after `--`) to suppress all confirmation prompts.

## Verification

- The command exits 0 and prints expected output
- For cache flushes: `Success: Cache flushed.`
- For option updates: `Success: Updated 'option_name' option.`
- For cron: `Success: Executed the cron event ...`

## Failure modes

**`Error: The [site].[env] environment does not exist`**
— Double-check the site machine name and environment name. List available sites with `terminus site:list`.

**`SSH permission denied` or connection timeout**
— SSH key not added to Pantheon or not loaded locally. Repeat step 2 and ensure the public key is in the Pantheon dashboard.

**`Error: This environment is in Git mode`**
— Switch to SFTP mode in the Pantheon dashboard (dev environments only) if the command needs to write to the filesystem. WP-CLI commands that don't modify files work in either mode.

**`Command not found: terminus`**
— Terminus not installed or not on `$PATH`. See [references/installation.md](references/installation.md).

**`DISALLOW_FILE_MODS` error from WordPress**
— Plugin/theme install or update via WordPress's built-in mechanisms is blocked on test/live. Use `terminus remote:wp` — WP-CLI bypasses this restriction.

**Authentication expired**
— Run `terminus auth:login --email=<you@example.com>` to re-authenticate.

## Escalation

- If Terminus consistently times out or returns 5xx errors, check [Pantheon status](https://status.pantheon.io) for platform incidents.
- If SSH keys refuse to authenticate despite being added, open a Pantheon support ticket — key propagation can take a few minutes but persistent failures require support.
- For multidev environment issues (missing environment, deploy failures), use `terminus multidev:list <site>` to inspect state before escalating.
