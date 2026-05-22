# Terminus 3 Compatibility Notes

Use this reference when working with a site or environment still running Terminus 3.x locally. Terminus 4 is the current release; upgrade when possible.

## Version identification

```bash
terminus --version    # 3.x.y for Terminus 3
```

## PHP requirement

Terminus 3.x requires PHP **7.4 or later** on the local machine running it (as opposed to Terminus 4's PHP 8.2+ requirement). PHP 8.1, 8.2, and 8.3 are supported by Terminus 3.3.0+; PHP 8.4 is not supported on any Terminus 3.x release.

## Authentication

### Machine token login (unchanged in Terminus 4)

```bash
terminus auth:login --machine-token=<TOKEN> --email=<you@example.com>
```

In Terminus 3, `--email` was commonly specified alongside `--machine-token`. In Terminus 4, `--email` is optional when using a machine token.

### SSH key authentication

In Terminus 3, SSH password authentication was still functional until Pantheon removed it platform-wide in **April 2024**. After that date, SSH keys are mandatory even on Terminus 3.

**Ed25519 keys were never supported on Pantheon** — this applies to both Terminus 3 and 4. Use RSA or ECDSA.

## Removed command: `terminus service-level:set`

Terminus 3.x still included the long-deprecated `terminus service-level:set <site> <level>` command. This was removed in Terminus 4.

**Terminus 4 replacement:** `terminus plan:set <site> <plan>`

## Plugin compatibility

Terminus 3 and Terminus 4 use different plugin APIs. Terminus 3 plugins are not compatible with Terminus 4. If a CI environment or local install uses Terminus plugins, verify they have Terminus 4–compatible releases before upgrading.

## Upgrade path

```bash
# macOS (Homebrew)
brew upgrade pantheon/pantheon/terminus

# Linux/Windows (PHAR)
terminus self:update
```

Verify after upgrade:

```bash
terminus --version    # should show 4.x
```
