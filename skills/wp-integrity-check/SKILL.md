---
name: wp-integrity-check
description: "Use when auditing a WordPress site's plugins, themes, and core for unexpected modifications. Verifies wordpress.org-hosted code against the official checksums API, compares GitHub-hosted open-source plugins against upstream tags, and generates reference checksum snapshots for premium and unknown-source plugins so future changes can be detected."
compatibility: "WordPress sites with SSH access and WP-CLI available on the server. Local git checkout assumed. Requires curl and sha256sum (standard on Linux runners and macOS)."
license: MIT
metadata:
    author: georgestephanis
    version: "1.0"
    written: "2026-05-29"
    written_against:
        wp-cli: "2.11.0"
        gh: "2.92.0"
---

# WordPress Integrity Check

## When to use

Use this skill when:

- Adopting a production site into version control (run after `wp-production-git-adoption`) to baseline what third-party code looks like before making any changes.
- Preparing for a deployment, to detect cowboy edits to third-party plugins or core on the live server.
- Investigating a suspected compromise or unexplained site behaviour — modified third-party files are a common indicator.
- A plugin or theme update ran on the server and you want to confirm only expected files changed.

Do NOT use this as a substitute for monitoring — schedule regular runs (see Phase 6) rather than only running reactively.

## Inputs required

- **PLUGINS.md** — the site's plugin inventory, with Source column filled in. Generate it via `wp-production-git-adoption` Phase 3 if absent.
- **SSH connection** — host, user, port to reach the production server.
- **WP path on server** — absolute path to the WordPress root (one level above `wp-content/`).
- **Local git checkout** — the repo root, to compare flagged files against tracked versions.

## Concepts

### Verification tiers

| Tier | Source type | Method |
|------|-------------|--------|
| **1 — Auto-verify** | `wordpress.org` plugins, themes, and core | WP-CLI checksums; falls back to the checksums API directly |
| **2 — Upstream compare** | `github.com/*` open-source plugins/themes | Clone/download the tagged release and diff |
| **3 — Baseline snapshot** | Premium, CodeCanyon, SaaS plugins, `unknown ⚠️` | Generate local SHA-256 checksums as a reference; re-run to detect future drift |
| **4 — Skip** | `first-party` (in-repo) code | Covered by git history; no external reference needed |

### What counts as a "modification"

WP-CLI (and the checksums API) flag three conditions:

- **File does not verify** — the file exists but its hash does not match the published version. This is the most important flag — it means the file's content changed.
- **File is missing** — a file that should be present (per the official release) is absent. May indicate intentional removal or deletion by an attacker.
- **File should not exist** — a file present in the installation that does not appear in the official release. May be added by an update process, a plugin, or an attacker.

Not every flag is malicious. Legitimate causes include: a host-applied patch, a local customization tracked in git, or a cache/log file. Each flag needs human review.

## Procedure

### Phase 1: Categorize plugins by tier

Read PLUGINS.md and sort each plugin into a tier (see Concepts above). If PLUGINS.md is missing the Source column, fill it in first — the tier assignment depends on it.

Flag any `unknown ⚠️` entries and resolve them before proceeding (ask the client or check the plugin header). Unknown-source plugins are treated as Tier 3.

### Phase 2: Tier 1 — Verify wordpress.org code via WP-CLI

Run checksum verification over SSH for all plugins, themes, and core in one pass:

```bash
# Plugins (--all covers everything WP-CLI knows about, skip unrecognized ones gracefully)
ssh <user>@<host> -p <port> \
  "wp --path=<wp-path> plugin verify-checksums --all"

# Themes
ssh <user>@<host> -p <port> \
  "wp --path=<wp-path> theme verify-checksums --all"

# WordPress core
ssh <user>@<host> -p <port> \
  "wp --path=<wp-path> core verify-checksums"
```

WP-CLI silently skips plugins it cannot find checksums for (premium, first-party, etc.) — so `--all` is safe to run without filtering first.

For machine-readable output (useful for scripting or CI):

```bash
wp --path=<wp-path> plugin verify-checksums --all --format=json 2>&1
```

#### Fallback: checksums API directly

If WP-CLI is unavailable, query the API directly for a specific plugin:

```bash
SLUG=classic-editor
VERSION=1.6.7
curl -s "https://downloads.wordpress.org/plugin-checksums/${SLUG}/${VERSION}.json" \
  | jq -r '.files | to_entries[] | "\(.value.md5)  \(.key)"' \
  > /tmp/expected.md5

# Then on the server, or locally against the plugin directory:
(cd wp-content/plugins/$SLUG && md5sum $(cat /tmp/expected.md5 | awk '{print $2}')) \
  | diff /tmp/expected.md5 -
```

For themes, replace the URL path with `theme-checksums`:

```bash
curl -s "https://downloads.wordpress.org/theme-checksums/${SLUG}/${VERSION}.json"
```

For core, the endpoint is:

```bash
curl -s "https://api.wordpress.org/core/checksums/1.0/?version=<version>&locale=en_US"
```

#### Reconcile flagged files with git

For every file WP-CLI flags as modified, check whether the modification is already tracked in the repo:

```bash
# Is the file tracked?
git ls-files --error-unmatch wp-content/plugins/<slug>/<file>

# Is the local version the same as what's in git?
git diff HEAD -- wp-content/plugins/<slug>/<file>
```

Three outcomes:

| git state | Implication |
|-----------|-------------|
| Tracked, matches HEAD | Intentional modification — committed and documented. Safe. |
| Tracked, differs from HEAD | Production diverged from the repo. Cowboy edit or failed deploy. Escalate. |
| Not tracked | Production-only modification of a third-party file. Review carefully. |

Any file in the third category — modified third-party code not tracked in git — should be reviewed line-by-line before being committed or discarded.

### Phase 3: Tier 2 — Compare GitHub-hosted plugins against upstream

For each open-source plugin from GitHub (e.g. `github.com/alleyinteractive/photonfill`):

1. Determine the version from the plugin header (`Version:` field).
2. Download the matching tagged release.
3. Diff against the local copy.

```bash
SLUG=photonfill-master
REPO=alleyinteractive/photonfill
VERSION=0.2.1

# Download upstream tag
curl -sL "https://github.com/${REPO}/archive/refs/tags/${VERSION}.tar.gz" \
  | tar -xz -C /tmp/

# Diff (adjust the unpacked directory name if it differs)
diff -rq \
  "/tmp/photonfill-${VERSION}/" \
  "wp-content/plugins/${SLUG}/" \
  --exclude="*.DS_Store"
```

If no versioned tag exists (common for `-master` snapshots), compare against `HEAD` of the default branch — but note that the upstream may have advanced past the installed copy:

```bash
git clone --depth=1 "https://github.com/${REPO}.git" /tmp/plugin-ref
diff -rq /tmp/plugin-ref/ wp-content/plugins/${SLUG}/
```

Differences found this way may be:
- **Intentional local patches** — if tracked in git, they are documented. Acceptable.
- **Upstream updates not yet installed** — the local version is behind. Note but do not change.
- **Untracked modifications** — same as Tier 1: review line-by-line.

### Phase 4: Tier 3 — Snapshot premium and unknown-source plugins

For any plugin or theme you cannot verify against an upstream source, generate a SHA-256 checksum manifest and commit it to the repo as a future reference baseline.

```bash
SLUG=advanced-custom-fields-pro
VERSION=6.8.2   # read from plugin header

find "wp-content/plugins/${SLUG}" -type f | sort | \
  xargs sha256sum | \
  sed "s|wp-content/plugins/${SLUG}/||" \
  > "checksums/${SLUG}-${VERSION}.sha256"
```

Store all checksum files under `checksums/` at the git root. Add `checksums/` to `.deployignore` — it is a dev artifact.

**Re-checking a baseline in the future:**

```bash
SLUG=advanced-custom-fields-pro
VERSION=6.8.2

# Regenerate and compare
find "wp-content/plugins/${SLUG}" -type f | sort | \
  xargs sha256sum | \
  sed "s|wp-content/plugins/${SLUG}/||" \
  | diff "checksums/${SLUG}-${VERSION}.sha256" -
```

Any lines in the diff output indicate files that changed since the baseline was taken. This does not tell you *whether* the change is malicious, only that something changed — but it gives you a concrete diff to investigate.

**When a plugin updates:** regenerate the manifest under the new version slug (`plugin-name-X.Y.Z.sha256`) and commit alongside the PLUGINS.md update. Retain the previous version's file in git so history is preserved.

### Phase 5: Document findings

Update PLUGINS.md with the verification outcome for each plugin. A simple addition to the notes or a separate `## Verification Log` section works:

```markdown
## Verification Log

Last run: 2026-05-29

| Slug | Tier | Outcome | Notes |
|------|------|---------|-------|
| classic-editor | 1 | ✓ Clean | |
| advanced-custom-fields-pro | 3 | Baselined | checksums/advanced-custom-fields-pro-6.8.2.sha256 |
| photonfill-master | 2 | ✓ Clean | Compared against alleyinteractive/photonfill v0.2.1 |
| gravityforms | 3 | Baselined | checksums/gravityforms-2.10.2.sha256 |
| some-plugin | 1 | ⚠️ Modified | wp-content/plugins/some-plugin/some-file.php — tracked in git, intentional |
```

Commit the verification log and any new checksum files together:

```bash
git add PLUGINS.md checksums/
git commit -m "Integrity check: baseline checksums for premium plugins, all wp.org plugins verified clean."
```

### Phase 6: Integrate into CI (optional)

Add a WP-CLI checksum step to the drift-detection workflow (see `wp-github-deploy` skill) to catch modified third-party files on production before deploying:

```yaml
- name: Verify plugin/theme checksums on production
  run: |
    ssh -p ${{ vars.DEPLOY_PORT }} ${{ vars.DEPLOY_USER }}@${{ vars.DEPLOY_HOST }} \
      "wp --path=${{ vars.WP_PATH }} plugin verify-checksums --all && \
       wp --path=${{ vars.WP_PATH }} theme verify-checksums --all && \
       wp --path=${{ vars.WP_PATH }} core verify-checksums"
```

A checksum failure in CI is informational, not a deploy blocker — it should trigger a human review, not an automated halt. Use `continue-on-error: true` and write the result to `$GITHUB_STEP_SUMMARY` so it is visible in the run summary alongside the rsync drift output.

#### Admin UI alternative

For sites without SSH or CI access, [Core Checksum Verifier](https://wordpress.org/plugins/core-checksum-verifier/) runs equivalent checks from the WordPress admin. It uses the same wordpress.org checksums API. Install it temporarily for a one-off audit and remove it after — it is a diagnostic tool, not a permanent fixture.

## Verification checklist

- [ ] All `wordpress.org` plugins and themes checked via `wp plugin verify-checksums --all` and `wp theme verify-checksums --all`.
- [ ] WordPress core checked via `wp core verify-checksums`.
- [ ] Every flagged file reconciled against git (intentional modification, cowboy edit, or missing file — each classified and documented).
- [ ] All GitHub-hosted open-source plugins diffed against the corresponding upstream tag.
- [ ] All premium and unknown-source plugins have a checksum baseline committed to `checksums/`.
- [ ] `checksums/` is listed in `.deployignore`.
- [ ] PLUGINS.md updated with a verification log entry.

## Failure modes

- **WP-CLI skips a plugin silently** — expected for premium/first-party plugins. Not an error. Confirm with `wp plugin list --fields=name,status` to see what was actually checked.
- **Checksum mismatch on a file tracked in git** — cross-check: does the git version match the checksums API's expectation? If yes, the production file diverged from both. If the git version also mismatches the API, the repo itself has the modified file (possibly intentional; check the commit message).
- **`checksums/` grows large** — only keep the current version's manifest plus one prior version per plugin. Delete older baselines via git once they are no longer useful.
- **GitHub-hosted plugin has no version tags** — the install came from a branch snapshot (`-master` suffix is a common indicator). Note this in the PLUGINS.md Source column and baseline with Phase 4 instead of Phase 3.
- **API returns 404 for a wordpress.org plugin** — the plugin may have been removed from the directory (common for abandoned or guideline-violating plugins). Treat as Tier 3 and generate a local baseline.

## Escalation

Escalate to the user (or client) when:

- A Tier 1 file is modified, not tracked in git, and the modification is not obviously a log/cache file. Do not commit or discard without review.
- Core files are modified — this is a high-severity finding requiring immediate attention.
- A plugin's source cannot be determined after checking the plugin header, the wp.org directory, and a web search. It may be an unlicensed premium copy.
- The diff for a premium plugin's checksum update is larger than expected for a routine version bump — inspect it before committing the new baseline.
