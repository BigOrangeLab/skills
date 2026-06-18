# Playground CLI command & flag reference

For `@wp-playground/cli` v3.x. Always confirm the current set with `npx @wp-playground/cli@latest <command> --help`. Source: the package README in [WordPress/wordpress-playground](https://github.com/WordPress/wordpress-playground/tree/trunk/packages/playground/cli).

## Top-level commands

| Command          | Purpose                                                                                                               |
| ---------------- | --------------------------------------------------------------------------------------------------------------------- |
| `start`          | Local server with automatic project detection, site persistence, and browser opening. Recommended for day-to-day dev. |
| `server`         | Local server with full manual control over configuration. For mounts, automation, and CI.                             |
| `run-blueprint`  | Execute a Blueprint file without starting a web server.                                                               |
| `build-snapshot` | Build a ZIP snapshot of a WordPress site from a Blueprint.                                                            |
| `php`            | Run a PHP script.                                                                                                     |

## Requirements & defaults

- **Node.js ≥ 20.18** (LTS).
- Default WordPress: **latest stable**. Default PHP: **8.3** (chosen for performance).
- Default port: **9400** (when available).
- `start` persists the site under `~/.wordpress-playground/sites/<path-hash>/`. `server` uses temporary storage unless you mount it yourself.

## `start` flags (common)

| Flag                 | Description                                                                                                                      |
| -------------------- | -------------------------------------------------------------------------------------------------------------------------------- |
| `--path=<path>`      | Project directory. Defaults to the current working directory. (Note: does not change which directory the saved site belongs to.) |
| `--wp=<version>`     | WordPress version. Defaults to latest.                                                                                           |
| `--php=<version>`    | PHP version. Defaults to 8.3.                                                                                                    |
| `--port=<port>`      | Server port. Defaults to 9400 when available.                                                                                    |
| `--blueprint=<path>` | JSON Blueprint file to execute on boot.                                                                                          |
| `--login`            | Auto-login as administrator. Defaults to **true**.                                                                               |
| `--skip-browser`     | Do not open the default browser.                                                                                                 |
| `--reset`            | Delete the stored site directory and start fresh.                                                                                |
| `--no-auto-mount`    | Disable automatic project detection/mounting.                                                                                    |

## `server` flags (common)

| Flag                                          | Description                                                                                                                                                    |
| --------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `--port=<port>`                               | Server port. Defaults to 9400.                                                                                                                                 |
| `--wp=<version>`                              | WordPress version. Defaults to latest.                                                                                                                         |
| `--php=<version>`                             | PHP version. Defaults to 8.3.                                                                                                                                  |
| `--auto-mount`                                | Auto-mount the current directory (plugin, theme, wp-content, etc.).                                                                                            |
| `--mount=<host:vfs>`                          | Manually mount a directory (repeatable). Format `/host/path:/vfs/path`.                                                                                        |
| `--mount-before-install=<host:vfs>`           | Mount before WordPress installs (repeatable) — useful when the mounted dir already contains a WP site.                                                         |
| `--mount-dir "<host>" "<vfs>"`                | Mount a directory (repeatable), space-separated form.                                                                                                          |
| `--mount-dir-before-install "<host>" "<vfs>"` | Same, applied before install.                                                                                                                                  |
| `--blueprint=<path>`                          | JSON Blueprint to execute.                                                                                                                                     |
| `--blueprint-may-read-adjacent-files`         | Consent flag: let "bundled" resources in a local blueprint read sibling files.                                                                                 |
| `--login`                                     | Auto-login as administrator.                                                                                                                                   |
| `--wordpress-install-mode <mode>`             | How WP is prepared. Default `download-and-install`. Also: `install-from-existing-files`, `install-from-existing-files-if-needed`, `do-not-attempt-installing`. |
| `--skip-sqlite-setup`                         | Don't set up the SQLite database integration.                                                                                                                  |
| `--verbosity <level>`                         | `quiet` \| `normal` (default) \| `debug`.                                                                                                                      |
| `--debug`                                     | Print the PHP error log if boot fails.                                                                                                                         |
| `--follow-symlinks`                           | Follow symlinks in mounted dirs. ⚠️ Exposes files outside mounts — security risk.                                                                              |
| `--workers=<n\|auto>`                         | Request-handling worker threads. `auto` = one per CPU core minus one. Default `min(6, cpus-1)`.                                                                |
| `--phpmyadmin[=<path>]`                       | Install phpMyAdmin (URL printed after boot; default path `/phpmyadmin`).                                                                                       |
| `--internal-cookie-store`                     | Use Playground's internal cookie handling/persistence.                                                                                                         |
| `--php-extension=<manifest>`                  | Load a custom PHP.wasm extension manifest (repeatable; local path, `file:`, or `http(s):`). External extensions are JSPI-only → Node.js 23+.                   |

`--experimental-multi-worker` is **deprecated** (ignored); use `--workers` instead.

## `--auto-mount` detection rules

- **Plugin** — a PHP file with a `Plugin Name:` header → mounted into `wp-content/plugins`.
- **Theme** — `style.css` with a `Theme Name:` header → mounted into `wp-content/themes`.
- **wp-content** — presence of `plugins`, `themes`, `mu-plugins`, or `uploads` subdirs.
- **WordPress** — a complete WP install → mounted at root `/wordpress`.

## Examples

```bash
# Run a blueprint without a server (CI validation)
npx @wp-playground/cli@latest run-blueprint --blueprint=./blueprint.json

# Build a shareable snapshot ZIP
npx @wp-playground/cli@latest build-snapshot --blueprint=./blueprint.json --outfile=./site.zip

# Manual single-plugin mount
npx @wp-playground/cli@latest server --mount=.:/wordpress/wp-content/plugins/my-plugin

# Boot from an existing local WordPress tree
npx @wp-playground/cli@latest server --mount-before-install=./my-local-site:/wordpress

# Load a custom PHP extension (Node 23+)
npx @wp-playground/cli@latest server --php=8.4 --php-extension=./dist/spx/manifest.json
```

## Programmatic use

```javascript
import { runCLI } from "@wp-playground/cli";

const cliServer = await runCLI({
	command: "server",
	php: "8.3",
	wp: "latest",
	login: true,
});
```

See also: the upstream **`wp-playground`** skill for browser workflows, blueprint depth, snapshots, and Xdebug; the **`blueprint`** skill for authoring Blueprint JSON.
