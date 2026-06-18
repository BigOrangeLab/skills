---
name: playground-dev-env
description: "Spin up a disposable local WordPress dev environment with @wp-playground/cli — no Docker, MySQL, or Apache. Use the `start` command for day-to-day plugin/theme work: auto-mount, version pinning, blueprints, persistence, and one-command boot."
compatibility: "@wp-playground/cli v3.x. Requires Node.js 20.18+ (LTS). Runs WordPress in WebAssembly with SQLite; defaults to latest WP and PHP 8.3. macOS, Linux, Windows."
license: GPL-2.0-or-later
metadata:
    author: georgestephanis
    version: "1.0"
    written: "2026-06-18"
    written_against:
        "@wp-playground/cli": "3.1.39"
        node: "20.18"
---

# playground-dev-env

[`@wp-playground/cli`](https://www.npmjs.com/package/@wp-playground/cli) runs WordPress locally on the Playground (WebAssembly + SQLite) runtime — **no Docker, MySQL, or Apache**. It is also the official replacement for the deprecated `@wp-now/wp-now` package.

Two commands matter for development environments:

- **`start`** — day-to-day workflow. Auto-detects whether the current directory is a plugin, theme, `wp-content`, or full WordPress install; persists the site between runs; logs you in as admin; opens the browser.
- **`server`** — low-level/CI mode with explicit mounts, storage, and automation. Uses temporary storage unless you mount it yourself.

This skill focuses on spinning up local dev environments. For browser-only Playground, blueprint authoring depth, snapshots, and Xdebug, see the upstream **`wp-playground`** skill and the **`blueprint`** skill.

## When to use

- Quickly test or develop a plugin/theme against a live WordPress without a full stack.
- Reproduce a bug on a specific WP/PHP version (`--wp` / `--php`).
- Boot WordPress in a pre-configured state from a Blueprint.
- Replace an existing `wp-now` workflow (the package is deprecated; `start` is the successor).

Do **not** use Playground for anything touching production data, native PHP extensions not bundled, or real MySQL behavior — it is ephemeral and SQLite-backed. Fall back to a full stack (`wp-env`/Docker, Local, or Cove) when those are required.

## Inputs required

- **Node.js ≥ 20.18** — verify with `node -v`. `npx` ships with npm; no global install needed.
- **A project directory** — the plugin/theme/`wp-content`/WordPress root to develop against (defaults to the current working directory).
- **Optional**: desired WP version, PHP version (default 8.3), a Blueprint JSON path, a free port (default 9400).

## Procedure

### 1. Confirm Node version

```bash
node -v        # must be >= 20.18
```

### 2. Start a dev environment (the common path)

```bash
cd path/to/my-plugin-or-theme
npx @wp-playground/cli@latest start
```

`start` auto-detects the project type, mounts it, persists the site under `~/.wordpress-playground/sites/<path-hash>/` (hash derived from the working directory, so projects stay isolated), logs in as admin, and opens `http://localhost:9400`.

Common flags (run `npx @wp-playground/cli@latest start --help` for the full list):

```bash
npx @wp-playground/cli@latest start --wp=6.8 --php=8.4   # pin versions
npx @wp-playground/cli@latest start --blueprint=./blueprint.json
npx @wp-playground/cli@latest start --port=9500          # avoid a 9400 conflict
npx @wp-playground/cli@latest start --skip-browser       # don't auto-open
npx @wp-playground/cli@latest start --reset              # wipe stored site, start fresh
npx @wp-playground/cli@latest start --no-auto-mount      # disable project detection
```

Note: `start` ties the persisted site to the **current working directory** (the `<path-hash>`), not to `--path`. To develop against a specific project, `cd` into it first, then run `start`.

### 3. Advanced / CI control with `server`

When you need explicit mounts or scripted setup, use `server`:

```bash
cd my-plugin && npx @wp-playground/cli@latest server --auto-mount
npx @wp-playground/cli@latest server --mount=.:/wordpress/wp-content/plugins/my-plugin
npx @wp-playground/cli@latest server --mount-before-install=./my-local-site:/wordpress
```

Full flag reference (mounts, `--wordpress-install-mode`, `--workers`, `--phpmyadmin`, blueprints, PHP extensions): [references/commands.md](references/commands.md).

## Verification

- The CLI prints a URL (default `http://localhost:9400`) and opens it; the site loads.
- Your plugin/theme appears and can be activated (auto-mount worked).
- After stopping and re-running `start` in the same directory, your changes persist (same `<path-hash>` site).
- For scripted runs, add `--verbosity=debug` to confirm each step (mounts, blueprint steps) executed.

## Failure modes

- **CLI errors about Node version** — upgrade to Node.js ≥ 20.18.
- **Port 9400 already in use** — pass `--port=<free-port>`.
- **Changes don't persist / wrong site loaded** — `start` keys the saved site off the working directory. Run it from the same directory each time; use `--reset` to discard a stale stored site.
- **Auto-mount picked the wrong mode** — plugin needs a `Plugin Name:` header; theme needs `style.css` with `Theme Name:`. Use `server --mount=...` for explicit control, or `--no-auto-mount`.
- **Blueprint can't read adjacent files** — on `server`, add `--blueprint-may-read-adjacent-files`.
- **Expecting real MySQL/native extension behavior** — Playground is SQLite + WASM; some plugins relying on MySQL-specific SQL or PHP extensions won't behave identically. Use a full stack instead.
- **Still calling `@wp-now/wp-now`** — it's deprecated and won't receive updates; switch to `@wp-playground/cli@latest`.

## Escalation

- Deeper Playground topics (browser launches, blueprint authoring, snapshots, Xdebug): use the upstream **`wp-playground`** skill (`vendor/wordpress/skills/wp-playground/`) and the **`blueprint`** skill.
- Full, current flag list: `npx @wp-playground/cli@latest <command> --help`.
- Docs: <https://wordpress.github.io/wordpress-playground/>. Bugs/discussions: <https://github.com/WordPress/wordpress-playground>.
