# Installing Cove

Cove runs on macOS, Linux (Ubuntu, Debian, Fedora, RHEL), and WSL2. It is MIT licensed; source and releases live at [github.com/anchorhost/cove](https://github.com/anchorhost/cove). Current version: v1.10.

## Standard install

```bash
bash <(curl -sL https://cove.run/install-cove.sh)
```

- **macOS**: the installer offers to install Homebrew first if it isn't present.
- **Linux**: ensure `curl` is installed before running.
- To preview an unreleased build from the `main` branch (e.g. to verify a fix before it's tagged), pass `--main`:

    ```bash
    bash <(curl -sL https://cove.run/install-cove.sh) --main
    ```

The installer can also be run from source via the GitHub repo (`compile.sh` builds the distributable `cove.sh` from `main` + `commands/`).

## Port conflicts

If ports 80/443 are already in use (commonly by Local or DevKinsta), the installer shows:

```text
Port Conflict
Port 80 is in use by: Local
Port 443 is in use by: Local
❯ Use alternative ports (8090 / 8453) — run alongside other tools
  Pick custom ports
  Proceed with 80/443 anyway
  Cancel installation
```

Pick **Use alternative ports** to install on `8090` / `8453`. Sites are then reached at e.g. `https://myblog.localhost:8453` — Caddy's auto-HTTPS handles the non-default port transparently.

Switch ports any time later without losing work:

```bash
cove ports                       # interactive (Keep / Default / Custom)
cove ports --http 80 --https 443       # back to defaults
cove ports --http 8090 --https 8453    # alternatives
cove ports --dry-run                   # preview only
```

Changing the HTTPS port triggers `wp search-replace` across all WordPress sites so stored URLs keep working.

## HTTPS trust

Cove serves every site over HTTPS using a local root CA. If a browser shows "Not Secure" / "Not private":

```bash
cove trust
```

This installs "Cove Local Authority" into the OS keychain and browser NSS databases (Firefox, Chromium, snap-packaged browsers on Linux). It is idempotent — re-running removes stale entries before re-adding. `cove install` runs it automatically on first setup.

## WSL2 specifics

1. Enable systemd — add to `/etc/wsl.conf`:

    ```ini
    [boot]
    systemd=true
    ```

    Then in PowerShell: `wsl --shutdown`, and restart the WSL session.

2. Make sites resolvable from Windows browsers — WSL2 has its own virtual network, so `myblog.localhost` doesn't resolve from Windows by default. Run inside WSL:

    ```bash
    cove wsl-hosts
    ```

    Apply the printed PowerShell snippet to update the Windows hosts file.

## Upgrading

```bash
cove upgrade     # updates Cove, FrankenPHP, and Adminer to latest
```
