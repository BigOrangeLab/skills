---
name: wp-plugin-version-check
description: "Use when setting up or maintaining automated plugin version tracking for a WordPress site. Sets up a GitHub Actions workflow that compares installed plugin versions against wordpress.org, GitHub releases, and premium vendor APIs, then opens a PR updating PLUGINS.md when versions change."
compatibility: "WordPress sites with a GitHub repo, SSH access to production, and WP-CLI on the server. Requires PLUGINS.md with a Source column (see wp-production-git-adoption Phase 3)."
license: MIT
metadata:
    author: georgestephanis
    version: "1.0"
    written: "2026-05-29"
    written_against:
        wp-cli: "2.11.0"
        check-jsonschema: "0.29"
---

# WordPress Plugin Version Check

## When to use

Use this skill when:

- PLUGINS.md exists and has Source/License columns populated (from `wp-production-git-adoption` Phase 3).
- You want the repo to automatically surface plugin updates without manually checking vendor dashboards.
- A site has premium plugins whose update APIs can be reverse-engineered from the plugin code.

Do NOT use before PLUGINS.md is set up — the workflow reads from it and the cache, both of which must exist. Run `wp-production-git-adoption` first if the site is not yet in version control.

## Outputs

- `.github/plugin-versions-cache.json` — machine-readable version store; one entry per tracked plugin.
- `.github/plugin-versions-cache-schema.json` — JSON Schema for the cache; enables editor validation and CI checks.
- `.github/workflows/plugin-version-check.yml` — manually triggered workflow (`workflow_dispatch`); add a `schedule:` trigger to enable periodic runs. Opens a PR when versions change. Copy the template from [references/workflow.yml](references/workflow.yml).
- `PLUGINS.md` — updated `Version` column and `## Available Updates` section (via PR).

## How version checking works

Plugins are checked by source type:

| Source type                             | Method                                           | What you get                                                           |
| --------------------------------------- | ------------------------------------------------ | ---------------------------------------------------------------------- |
| `wporg`                                 | `api.wordpress.org/plugins/info/1.0/{slug}.json` | Latest available version                                               |
| `github`                                | GitHub releases API, falling back to tags        | Latest release/tag                                                     |
| `premium` / `saas` with `update_api`    | Vendor API (EDD, ACF REST, TGM — see Phase 3)    | Latest version if API responds                                         |
| `premium` / `saas` without `update_api` | Production WP-CLI scan only                      | Detects installs/updates on server; cannot check for available updates |

The workflow always detects installed-version changes on production (via WP-CLI over SSH), regardless of source type. The source type determines only whether it can additionally check for newer available versions.

## Procedure

### Phase 1: Bootstrap the cache

The cache JSON must exist before the workflow runs. Bootstrap it by classifying each entry in PLUGINS.md into a source type:

```text
source_type  when to use
-----------  -----------
wporg        Plugin slug resolves at api.wordpress.org/plugins/info
github       Plugin hosted on GitHub; set github_repo to "owner/repo"
premium      Commercial plugin with a vendor update API or dashboard
saas         Free plugin requiring an external paid service
```

Copy the schema into the repo's `.github/` directory. The canonical copy lives in the skill's own `references/` folder:

```bash
# Run from the repo root; adjust the source path to wherever the skill is installed:
cp /path/to/skills/wp-plugin-version-check/references/cache-schema.json \
   .github/plugin-versions-cache-schema.json
```

Create `.github/plugin-versions-cache.json` with a `$schema` pointer and a `plugins` object. The `$schema` path is relative to the cache file itself — since both files are in `.github/`, the value is just the filename:

```json
{
	"$schema": "./plugin-versions-cache-schema.json",
	"_meta": {
		"generated": "YYYY-MM-DD",
		"description": "Plugin version tracking cache."
	},
	"plugins": {
		"classic-editor": {
			"title": "Classic Editor",
			"status": "active",
			"source_type": "wporg",
			"installed_version": "1.6.7",
			"latest_version": null,
			"latest_checked": null
		}
	}
}
```

**What to omit from the cache:** first-party plugins (in-repo; git history is the reference) and host-managed mu-plugins (e.g. Kinsta's). Everything else — free, premium, GitHub-hosted — should have an entry.

Validate the bootstrapped file before committing:

```bash
pip install check-jsonschema
check-jsonschema --schemafile .github/plugin-versions-cache-schema.json .github/plugin-versions-cache.json
```

### Phase 2: Set up the workflow

Copy the workflow template from [references/workflow.yml](references/workflow.yml) into `.github/workflows/plugin-version-check.yml`. The template includes the Python version-checking script inline. The workflow requires these GitHub repository settings:

**Variables** (Settings → Variables — visible in logs):

| Name          | Required | Value                                                                                                                                                                         |
| ------------- | -------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `DEPLOY_HOST` | Yes      | Server hostname or IP                                                                                                                                                         |
| `DEPLOY_PORT` | Yes      | SSH port                                                                                                                                                                      |
| `DEPLOY_USER` | Yes      | SSH username                                                                                                                                                                  |
| `WP_PATH`     | Yes      | Absolute path to the WordPress root on the server (parent of `wp-content/` — same as `wp-integrity-check`)                                                                    |
| `BASE_BRANCH` | No       | Branch automated PRs target. Defaults to `trunk` if unset. Set to a dev/staging branch (e.g. `dev`) to stage automated changes there before manually promoting them to trunk. |

**`BASE_BRANCH` and the intermediary branch pattern**

If the repo uses a dev branch as a staging layer before trunk, set `BASE_BRANCH` to that branch name:

```bash
gh variable set BASE_BRANCH --body "dev"
```

The workflow will then:

1. Check out from `BASE_BRANCH` (so the feature branch diverges from dev, not trunk)
2. Open or update the `update/plugin-versions` PR targeting `BASE_BRANCH`

A human then reviews the PR, merges it into `dev`, and separately creates a PR from `dev` → `trunk` when ready to deploy. This keeps automated changes off the production branch until they've been reviewed.

If `BASE_BRANCH` is not set, all behaviour defaults to `trunk`.

**Secrets** (Settings → Secrets — encrypted):

| Name             | Value                                                      |
| ---------------- | ---------------------------------------------------------- |
| `DEPLOY_SSH_KEY` | Private key for SSH (shared with drift-detection workflow) |

The workflow is triggered manually (`workflow_dispatch`). A `schedule:` trigger is intentionally omitted until the workflow has been tested and is ready for unattended runs — add one (e.g. `cron: '0 9 * * 1'` for Mondays at 9am UTC) when ready. When it finds changes, it force-pushes to an `update/plugin-versions` branch and opens a PR against `BASE_BRANCH` (or comments on the existing open one).

### Phase 3: Research and add premium vendor APIs

Many premium plugins phone home to check for updates. The update endpoint is often findable by grepping the plugin code. Look for:

```bash
# Entry points: filters that inject update data
grep -rn 'pre_set_site_transient_update_plugins\|site_transient_update_plugins' plugin-dir/

# HTTP calls
grep -rn 'wp_remote_get\|wp_remote_post\|wp_remote_request' plugin-dir/ | grep -v test

# EDD Software Licensing (most common premium pattern)
grep -rn 'edd_action\|EDD_SL\|edd-sl-api' plugin-dir/

# Common URL constants
grep -rn 'api_url\|update_url\|store_url\|remote_url\|API_URL' plugin-dir/ | grep -i 'http'
```

#### Common API patterns

**EDD Software Licensing** — used by Yoast, Gravity Wiz, many others:

```text
GET {store_url}?edd_action=get_version&item_name={item_name}&license={key}&version={current}&url={site_url}
```

Response JSON contains `new_version`. Try unauthenticated first (empty `license=`) — some stores return the version without a valid key.

Cache entry:

```json
"update_api": {
  "type": "edd",
  "endpoint": "https://vendor.com/edd-sl-api/",
  "item_name": "Plugin Name As Registered In Store",
  "item_name_note": "Verify against vendor docs if API returns item_not_found",
  "version_field": "new_version",
  "license_secret": "VENDOR_LICENSE_KEY"
}
```

The `item_name` must match the EDD product name exactly. If the API returns `{"error": "item_not_found"}`, the name is wrong — check the plugin changelog or support documentation for the exact string.

**ACF-style custom REST** — used by Advanced Custom Fields PRO:

```text
GET https://connect.advancedcustomfields.com/v2/plugins/get-info?p=pro
Header: X-ACF-License: {key}
```

Response JSON contains `version`. The `get-info` endpoint may respond without a key; `update-check` requires one.

Cache entry:

```json
"update_api": {
  "type": "acf_rest",
  "endpoint": "https://connect.advancedcustomfields.com/v2/plugins/get-info",
  "params": {"p": "pro"},
  "license_header": "X-ACF-License",
  "version_field": "version",
  "license_secret": "ACF_LICENSE_KEY",
  "auth_note": "get-info may respond without a key; verify on first run"
}
```

**TGM-style** — used by Easy WP SMTP Pro and others:

```text
GET {endpoint}?action={action}&tgm-updater-plugin={slug}&tgm-updater-key={key}
```

Cache entry:

```json
"update_api": {
  "type": "tgm",
  "endpoint": "https://vendor-api.com/license/v1",
  "action": "get-plugin-update",
  "plugin_slug": "plugin-slug",
  "key_param": "tgm-updater-key",
  "version_field": "new_version",
  "license_secret": "VENDOR_LICENSE_KEY"
}
```

#### Adding license keys as secrets

For any plugin whose API requires a key, add the key to GitHub Secrets using the name stored in `license_secret`:

```bash
gh secret set YOAST_LICENSE_KEY --body "your-key-here"
gh secret set GRAVITY_PERKS_LICENSE_KEY --body "your-key-here"
gh secret set ACF_LICENSE_KEY --body "your-key-here"
```

The workflow always tries unauthenticated first. If the unauthenticated call returns a version, the secret is not needed. If it returns nothing, the workflow retries with the key (if the secret is set) and logs a note if the key is also absent.

#### When you can't find the endpoint

If grepping the plugin code doesn't reveal a clear update URL, check:

1. The network tab in your browser while visiting Plugins → Updates in wp-admin (the update check fires when you load the page).
2. The plugin's changelog or release notes page — vendors sometimes document their update API.
3. The WordPress.org plugin page — some nominally "premium" plugins still have their free version on wp.org and can be version-checked that way.

If none of these work, leave `update_api` absent in the cache entry. The workflow will still detect installed-version changes from production.

### Phase 4: Maintain the cache

**When a new plugin is installed on production:** add an entry to the cache before the next workflow run, or the workflow will silently ignore it (it only processes slugs already in `plugins`). The `installed_version` will be updated on first run.

**When a plugin is removed:** delete its entry from the cache and remove the row from PLUGINS.md. Do not leave stale entries — they generate noise in the workflow output.

**When a plugin updates its update mechanism:** the API call will start failing. Check the plugin's changelog for API migration notes, update the `update_api` entry, and re-run the workflow manually.

**When a license key is renewed or transferred:** update the GitHub secret. The cache entry does not need to change.

**When a plugin's EDD `item_name` changes:** the API will return `item_not_found`. Update `item_name` in the cache entry.

## Source column badges

The workflow maintains shields.io badges in the PLUGINS.md Source column on every run, replacing plain-text values with colour-coded badges that link directly to the plugin's source. The colour signals the plugin's relationship at a glance:

| Color                 | Hex      | Meaning                                                     |
| --------------------- | -------- | ----------------------------------------------------------- |
| Blue (WordPress logo) | `21759B` | Hosted on wordpress.org — links to the plugin's own page    |
| Dark (GitHub logo)    | `24292e` | Open-source GitHub project — links to the specific repo     |
| Green                 | `22C55E` | First-party — developed in-house, tracked in this repo      |
| Orange                | `FF6900` | Commercial / premium — links to vendor homepage             |
| Purple                | `7C3AED` | SaaS — free plugin, paid external service — links to vendor |
| Gray                  | `6B7280` | Host-managed — installed by the hosting provider            |

The `source_badge(slug, source_type, vendor_url, github_repo)` function in the workflow script generates the correct badge for each plugin using data from the cache. It is called for:

1. The Source column of every tracked plugin in PLUGINS.md (updated each run).
2. The `## Available Updates` section rows (generated fresh each run).

**Adding a new source type:** if a plugin has a source that doesn't fit any of the above, add handling to `source_badge()` and a new colour row to this table. Return `None` to leave the cell unchanged (fallback for unknown/unmapped types).

**For the initial PLUGINS.md** (before the workflow has run): populate Source cells with the full badge markdown manually, following the patterns in `wp-production-git-adoption/references/plugins-md-template.md`. The workflow will maintain them from that point on.

## Verification

- `check-jsonschema` passes cleanly on the cache file.
- The workflow runs without errors on `workflow_dispatch`.
- wp.org plugins show `latest_version` populated after the first run.
- Premium plugins with `update_api` entries show either a `latest_version` or a `[info]` note in the workflow log explaining why the version was not returned.
- The PR, when opened, correctly updates `Version` cells in PLUGINS.md and the `## Available Updates` section.

## Failure modes

- **`item_not_found` from an EDD endpoint** — the `item_name` doesn't match the vendor's registered product name exactly. Check the vendor's documentation or changelog. Some vendors use the full product title; others use a short slug.
- **`check-jsonschema` fails on cache file** — the cache has an entry missing a required field, an invalid `source_type` enum value, or a `github` entry with no `github_repo`. Fix the offending entry before the workflow will proceed.
- **WP-CLI scan returns no data** — SSH connection failed (wrong host/port/key) or `wp` binary not in PATH on the server. Test manually: `ssh -p PORT USER@HOST "wp --path=WP_PATH plugin list --format=json"`.
- **PR is not created despite changes** — the `contents: write` and `pull-requests: write` permissions must be set at the workflow level. Check the workflow YAML.
- **EDD call returns a version unauthenticated on first run, then stops** — the vendor may have tightened their API. Set the `license_secret` and re-run.
- **`github_repo` tag/release format doesn't parse to a clean version** — some repos use `plugin-name-1.2.3` as their tag format instead of `v1.2.3` or `1.2.3`. The workflow strips a leading `v` by default. Set `github_tag_strip` in the cache entry to a different prefix string to strip (e.g. `"plugin-name-"`) — this field is defined in the schema.

## Escalation

Ask the user when:

- A premium plugin's update API requires a license key and the key is not available — leave `update_api` absent until the key can be added, rather than adding a broken entry.
- The installed version from WP-CLI doesn't match what's in the cache for multiple plugins simultaneously — this may mean the SSH connection is hitting a different environment (staging vs. production). Confirm `WP_PATH` points to the correct WordPress root.
- The `update/plugin-versions` branch has a merge conflict with trunk — do not auto-resolve; surface it in the PR and let the developer decide.
