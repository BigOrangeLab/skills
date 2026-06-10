# WordPress.org Plugin Directory Guidelines Checklist

Quick reference for all 18 guidelines with grep patterns and reviewer shorthand phrases.
Source: [WordPress/wporg-plugin-guidelines](https://github.com/WordPress/wporg-plugin-guidelines)

---

## Guideline 1 — GPL Compatibility

**Reviewer shorthand:** "License", "Incompatible License"

Every file, asset, and bundled library must be GPLv2 or later compatible.

Checks:

- Main plugin file `License:` header is present and GPL-compatible
- Each bundled library has a `LICENSE` or `license.txt` file — read it
- Compatible: MIT, Apache 2.0 (Apache 2.0 is compatible with GPLv3 but NOT GPLv2; flag if the plugin uses `GPL-2.0-only`), BSD-2-Clause, BSD-3-Clause, LGPL, ISC, MPL-2.0
- Incompatible: CC-BY-NC-_, CC-BY-ND-_, proprietary EULA, BSL-1.0
- Ambiguous: JSON license ("The Software shall be used for Good, not Evil") — the review team has rejected this before; swap for a clean MIT copy

```bash
# Find all LICENSE files in the plugin
find . -iname "license*" -o -iname "copying*" | sort

# Check main plugin header
grep -i "license" *.php | head -5
```

---

## Guideline 2 — Developer responsibility

**Reviewer shorthand:** (rarely cited directly; underlies all other violations)

Developers are responsible for all included code, including third-party libraries and API terms.
No separate checks needed — this is the principle behind every other check.

---

## Guideline 3 — Stable version available

**Reviewer shorthand:** "No stable version", "readme Stable tag mismatch"

- `Stable tag` in `readme.txt` must match `Version:` in the main plugin PHP file
- `Stable tag: trunk` is strongly discouraged; use an explicit version number

```bash
grep "Stable tag" readme.txt
grep "Version:" *.php | head -3
```

---

## Guideline 4 — Code must be human-readable

**Reviewer shorthand:** "Obfuscated code", "Minified/encoded PHP"

- No base64-encoded executable PHP
- No packer/obfuscator output (look for long base64 strings assigned to `$_` vars)
- Minified JS/CSS is fine IF source is included or linked from the readme

```bash
# Flag base64+eval patterns
grep -rn "base64_decode\|eval(" --include="*.php" .

# Flag packer-style JS (very long single line)
awk 'length > 500' *.js assets/js/*.js 2>/dev/null | head -5
```

---

## Guideline 5 — No trialware

**Reviewer shorthand:** "Trialware", "Restricted functionality", "Feature lock"

- No `wp_die()` / redirect triggered by a license expiry check on activation or use
- No quota counters that disable functionality after N uses
- Sandbox-only API access counts as trialware

```bash
grep -rn "license\|trial\|expired\|quota\|unlock" --include="*.php" . | grep -v "\.git"
```

Acceptable pattern: link to a paid external add-on, upsell notice (within Guideline 11 limits).

---

## Guideline 6 — SaaS is permitted (with conditions)

**Reviewer shorthand:** "License validator", "Storefront only", "No local functionality"

- External HTTP calls are fine IF the service provides real functionality
- NOT fine: a service that exists solely to validate a local license key
- NOT fine: plugin is purely a buy-now button for external products with no local functionality

```bash
grep -rn "wp_remote_\|wp_safe_remote_" --include="*.php" . | grep -v "\.git"
```

For each hit, confirm the readme documents the service and links to its ToS.

---

## Guideline 7 — No tracking without consent

**Reviewer shorthand:** "Calling Home", "Data collection", "Privacy violation", "Offloaded assets"

- No external HTTP calls on activation, `init`, or page load that fire before opt-in
- Opt-in must be explicit: a clearly labeled checkbox, not a pre-checked one buried in Terms
- Third-party CDN assets (even Google Fonts) count as tracking if there is no consent

```bash
# Find outbound HTTP calls
grep -rn "wp_remote_\|wp_safe_remote_\|curl_init\|file_get_contents" --include="*.php" . | grep -v "\.git"

# Find remote asset enqueues
grep -rn "wp_enqueue_script\|wp_enqueue_style" --include="*.php" . | grep "http"
```

For each hit: is it inside an opt-in callback? Is the service documented in the readme?

---

## Guideline 8 — No executable code via third-party systems

**Reviewer shorthand:** "Remote code execution", "Third-party CDN", "Self-update", "iframe admin"

- No `eval()` of remotely fetched content
- No JS/CSS from third-party CDNs unless they are the core service (e.g., Google Maps for a mapping plugin)
- No custom update mechanisms that install plugins/themes from outside WordPress.org
- No iframes for admin UI — use REST API or admin-ajax

```bash
grep -rn "eval(" --include="*.php" . | grep -v "\.git"
grep -rn "create_function\|assert(" --include="*.php" . | grep -v "\.git"
grep -rn "preg_replace.*\/e" --include="*.php" . | grep -v "\.git"

# Find iframes in admin templates
grep -rn "<iframe" --include="*.php" . | grep -v "\.git"

# Find upgrade/install calls
grep -rn "install_plugin\|upgrader\|Plugin_Upgrader" --include="*.php" . | grep -v "\.git"
```

---

## Guideline 9 — No illegal, dishonest, or morally offensive behavior

**Reviewer shorthand:** "Keyword stuffing", "Fake reviews", "Stolen code", "Botnet"

- Readme description must not claim legal/compliance guarantees
- No in-plugin review gates that pressure users or offer incentives
- No crypto mining or resource hijacking
- No use of another plugin's code presented as original work

```bash
# Review-gate patterns
grep -rn "review\|rating\|5 star" --include="*.php" . | grep -i "redirect\|require\|gate" | grep -v "\.git"
```

Check the plugin header `Author URI` and compare against the source repo if origin is unclear.

---

## Guideline 10 — No default front-end credit links

**Reviewer shorthand:** "Powered by", "Front-end credit"

- Any "Powered by [Plugin Name]" or backlink must default to off
- Must require explicit opt-in (a settings toggle, not opt-out)
- A plugin may not require the credit link to be enabled in order to function

```bash
grep -rn "powered.by\|Powered By\|credit\|attribution" --include="*.php" . -i | grep -v "\.git"
```

---

## Guideline 11 — No admin dashboard hijacking

**Reviewer shorthand:** "Dashboard hijack", "Persistent notice", "Undismissible nag", "Admin advertising"

- Site-wide `admin_notices` must be dismissible (check for nonce + option dismiss pattern)
- Dashboard widgets must be removable
- Upgrade nags must appear only on the plugin's own settings page, not sitewide

```bash
# Find admin_notices registrations
grep -rn "add_action.*admin_notices\|add_action.*network_admin_notices" --include="*.php" . | grep -v "\.git"

# Check if notices check a dismiss option
grep -rn "update_option\|set_transient" --include="*.php" . | grep -i "dismiss\|notice" | grep -v "\.git"

# Dashboard widgets
grep -rn "wp_add_dashboard_widget" --include="*.php" . | grep -v "\.git"
```

---

## Guideline 12 — No readme spam

**Reviewer shorthand:** "Readme spam", "Too many tags", "Competitor tags", "Affiliate links"

- Count tags: `Tags:` line in `readme.txt` must not exceed 12 comma-separated values
- Tags must not include competitor plugin names
- Affiliate links must be disclosed and must not use redirects/cloaking

```bash
grep "^Tags:" readme.txt

# Count tags
grep "^Tags:" readme.txt | tr ',' '\n' | wc -l

# Check for redirect-style affiliate links
grep -i "affiliate\|ref=\|aff=" readme.txt
```

---

## Guideline 13 — Use WordPress bundled libraries

**Reviewer shorthand:** "Bundled library", "jQuery copy", "PHPMailer copy"

Libraries bundled in WordPress that must NOT be re-bundled:

| Library       | WP handle              |
| ------------- | ---------------------- |
| jQuery        | `jquery`               |
| jQuery UI     | `jquery-ui-*`          |
| Underscore.js | `underscore`           |
| Backbone.js   | `backbone`             |
| Moment.js     | `moment`               |
| React         | `react`                |
| ReactDOM      | `react-dom`            |
| SimplePie     | (PHP, bundled in core) |
| PHPMailer     | (PHP, bundled in core) |
| PHPass        | (PHP, bundled in core) |

```bash
# Find jQuery copies
find . \( -name "jquery.js" -o -name "jquery.min.js" \) | grep -v node_modules | grep -v "\.git"

# Find PHPMailer copies
find . -iname "class.phpmailer.php" -o -iname "PHPMailer.php" | grep -v "\.git"

# Find Moment.js copies
find . \( -name "moment.js" -o -name "moment.min.js" \) | grep -v node_modules | grep -v "\.git"
```

---

## Guideline 14 — Avoid frequent SVN commits

**Reviewer shorthand:** (post-approval only; not a pre-submission check)

Relevant only for plugins already in the directory. Commit only release-ready code; use meaningful commit messages.

---

## Guideline 15 — Version numbers must be incremented

**Reviewer shorthand:** "Version mismatch", "Stable tag mismatch"

- `Version:` in main plugin PHP must match `Stable tag:` in `readme.txt`
- Do not bump readme without bumping the PHP header (or vice versa)

```bash
grep "^Version:" *.php
grep "^Stable tag:" readme.txt
```

---

## Guideline 16 — Complete plugin at submission

**Reviewer shorthand:** "Incomplete plugin", "Placeholder code", "Reserved name"

- Plugin must be functional at submission time
- "Coming soon" features, empty function stubs, or TODO-only files are rejected

---

## Guideline 17 — Respect trademarks

**Reviewer shorthand:** "Trademark violation", "Name reservation"

- Plugin slug must not begin with a trademarked term the developer does not own
- Affected terms include: `wordpress`, `woocommerce`, `jetpack`, `akismet`, `yoast`, and any other established product name
- Correct format: `my-feature-for-woocommerce`, not `woocommerce-my-feature`

Check:

- Plugin directory name (slug)
- `Plugin Name:` in the main PHP file header

---

## Guideline 18 — WordPress.org reserves rights

Informational only. The review team may act even for reasons not explicitly listed above.

---

## Common reviewer shorthand → guideline mapping

| Reviewer phrase                   | Guideline                    |
| --------------------------------- | ---------------------------- |
| Calling Home                      | 7                            |
| Generic Activation                | 7 (activation-time tracking) |
| Offloaded assets                  | 7, 8                         |
| Trialware / Feature lock          | 5                            |
| Obfuscated code                   | 4                            |
| Dashboard hijack / Persistent nag | 11                           |
| Powered by / Front-end credit     | 10                           |
| Bundled library                   | 13                           |
| Readme spam / Too many tags       | 12                           |
| Trademark violation               | 17                           |
| License incompatible              | 1                            |
| Remote code execution             | 8                            |
| Storefront only                   | 6                            |
| Self-update mechanism             | 8                            |
