---
name: wp-plugin-directory-review
description: "Review a WordPress plugin's code against the WordPress.org Plugin Directory Guidelines before submission or to address review feedback."
compatibility: "WordPress plugins targeting WordPress.org hosting. Requires filesystem access to plugin source. Applicable both pre-submission and when responding to a review team rejection."
license: MIT
metadata:
    author: georgestephanis
    version: "1.0"
    written: "2026-06-10"
    written_against:
        guidelines: "https://github.com/WordPress/wporg-plugin-guidelines"
---

# WordPress.org Plugin Directory Review

## When to use

Use this skill when:

- A developer wants to review a plugin before submitting it to the WordPress.org Plugin Directory
- A plugin has been rejected or flagged by the plugin review team and the developer needs to address specific guideline violations
- An existing plugin needs an audit to stay in compliance after significant changes

Do NOT use as a substitute for running Plugin Check (`plugin-check` plugin or `wp plugin check` via WP-CLI) — run that first and supply its output as additional input. This skill covers policy and pattern checks that automated tools miss.

## Inputs required

- Path to the plugin root (the directory containing the main plugin file and `readme.txt`)
- Any rejection or review feedback email/ticket from the WordPress.org review team (if responding to a flag)
- Plugin Check output (if available)
- Whether the plugin targets the Block Directory (stricter additional rules apply)

## Procedure

Work through each category below. Every finding must cite the specific guideline number and the file:line where the issue appears. Classify each finding as:

- **Blocker** — will cause rejection or removal
- **Warning** — likely to cause rejection
- **Recommendation** — may be noted by the review team; fix before submission

For the full per-guideline checklist with grep patterns and examples, see [references/guidelines-checklist.md](references/guidelines-checklist.md).

### 1. Licensing (Guideline 1 — GPL compatibility)

- Confirm the main plugin file header includes a `License:` tag, e.g., `License: GPLv2 or later`
- Check all bundled third-party libraries for license files and confirm GPL compatibility
- Look for commercial or restrictive licenses (MIT and Apache 2.0 are compatible; LGPL is compatible; proprietary / CC-NC are not)
- Flag any bundled library that lacks a visible license file

### 2. Readme and plugin header completeness (Guidelines 3, 15, 16)

- Confirm `readme.txt` exists and has required headers: `Plugin Name`, `Description`, `Requires at least`, `Tested up to`, `Stable tag`
- `Stable tag` must match the `Version:` header in the main plugin PHP file
- `Tested up to` must be a released WordPress version, not a future one
- Confirm the plugin has actual functional code — stubs, placeholders, or "coming soon" plugins are rejected (Guideline 16)

### 3. Code readability and obfuscation (Guideline 4)

- Scan for packer/uglifier output: minified single-line PHP, `base64_decode` + `eval` execution, `${'_'.$_}` variable-variable tricks
- Flag any file where the majority of logic is non-human-readable
- If build artifacts (minified JS/CSS) are present, confirm a link to source or the source itself is included

### 4. No trialware or feature-locking (Guideline 5)

- Grep for license key checks that disable core functionality after a trial period or quota
- Distinguish acceptable patterns: upsells to paid add-ons hosted off WordPress.org are fine; disabling included features after a period is not
- Flag `wp_die()` or redirect-on-activation patterns gated on license validation

### 5. External service dependencies (Guideline 6)

- For any external HTTP calls (`wp_remote_get`, `wp_remote_post`, `wp_safe_remote_*`): confirm the readme documents the service and links to its Terms of Use
- Flag service wrappers whose sole purpose is license or key validation (prohibited)
- Flag plugins that are pure storefronts with no local functionality beyond purchase links

### 6. User tracking and data collection (Guideline 7)

- Grep for external HTTP calls on plugin activation, admin init, or front-end page loads that fire without a user opt-in
- Flag any analytics, telemetry, or usage-reporting calls that run by default
- Flag offloaded assets (scripts, images, fonts from third-party domains) unrelated to a declared service
- Confirm that any opt-in UI is clear, explicit, and not buried in settings

### 7. No remote code execution (Guideline 8)

- Flag any `eval()`, `assert()` used as a function, `preg_replace` with `/e` flag, or `create_function()`
- Flag plugins that fetch and execute PHP from a remote URL
- Flag JS/CSS loaded from third-party CDNs not part of a declared service (all non-service assets must be local)
- Flag iframes used to render admin pages (API integration should be used instead)
- Flag self-update mechanisms that bypass WordPress.org

### 8. Ethics and honesty (Guideline 9)

- Review readme for keyword stuffing, misleading capability claims ("guarantees legal compliance", "makes your site unhackable")
- Flag any built-in review solicitation that pressures, compensates, or misrepresents reviews
- Flag use of another developer's plugin presented as original work (check headers, file structure, and authorship)

### 9. Front-end credit links (Guideline 10)

- Grep for `echo` of "Powered by", footer links, or credit badges output on public-facing pages
- Confirm any such output defaults to off and requires explicit user opt-in
- Flag any plugin that refuses to function unless a credit link is active

### 10. Admin dashboard behavior (Guideline 11)

- Check all `admin_notices` hooks: site-wide notices must be dismissible
- Look for dashboard widgets added unconditionally — must be dismissible or user-configurable
- Grep for persistent admin notices that do not check a dismiss transient/option
- Flag in-plugin advertising for third-party products

### 11. Readme spam (Guideline 12)

- Count tags in `readme.txt` — must not exceed 12
- Check tags for competitor plugin names (e.g., using a competitor's slug as a tag)
- Check for affiliate links: must be disclosed and must link directly (no redirect/cloaking)
- Review the description for keyword stuffing and SEO manipulation

### 12. Bundled libraries (Guideline 13)

- Grep for bundled copies of libraries WordPress ships: jQuery, Backbone, Underscore, PHPMailer, SimplePie, Moment.js, etc.
- For a full list, see [Default Scripts Included and Registered by WordPress](https://developer.wordpress.org/reference/functions/wp_enqueue_script/#notes)
- Flag any such library included in `/assets/`, `/vendor/`, `/lib/`, or `/js/` — they must be removed and the WP-bundled version used via `wp_enqueue_script()`

### 13. Trademark and naming (Guideline 17)

- Check that the plugin slug does not begin with a trademarked term (WordPress, WooCommerce, Jetpack, etc.) unless the developer is the legal owner
- If the plugin integrates with a third-party product, the product name should not be the leading term in the slug (use "my-feature-for-woocommerce" not "woocommerce-my-feature")

### 14. Block Directory additional rules (if applicable)

If the plugin targets the Block Directory, also check the requirements in [references/block-directory-guidelines.md](references/block-directory-guidelines.md).

### 15. Security baseline (not a stated guideline but triggers rejections)

The review team rejects plugins with obvious security issues. Check:

- All `$_GET`/`$_POST` inputs are sanitized before use
- All output is escaped (`esc_html()`, `esc_attr()`, `esc_url()`, etc.)
- Nonces protect all form submissions and AJAX handlers that mutate state
- `$wpdb->prepare()` is used for all dynamic SQL

For a complete security checklist, see the `wp-client-repo-review` skill's [security checklist](../wp-client-repo-review/references/security-checklist.md).

## Verification

- Every finding cites the specific guideline number and file:line
- Findings are ordered: blockers → warnings → recommendations
- Security issues are called out separately from policy issues
- The `readme.txt` has been validated (use `wp plugin check` or the wporg MCP readme validator)
- Plugin Check output has been reviewed and all errors resolved

## Failure modes

- Treating Plugin Check output as a complete review — it catches some issues but misses policy and behavioral checks
- Missing bundled libraries nested in `/vendor/` or renamed
- Overlooking opt-in requirements for tracking because the opt-in UI exists but fires after data is already sent
- Approving hard-coded CDN asset loading because "it's just a font"
- Missing trialware patterns that use server-side license checks rather than local expiry logic

## Escalation

Ask for user input when:

- It is unclear whether an external service qualifies as "SaaS with substance" (Guideline 6) or a prohibited license-validator
- A bundled library's license cannot be identified — do not guess compatibility; contact the library author or `plugins@wordpress.org`
- A trademark question requires legal verification

Official review feedback should be mapped back to guidelines using [references/guidelines-checklist.md](references/guidelines-checklist.md) — the review team often uses shorthand phrases like "Generic Activation", "Calling Home", or "Dashboard Hijacking" that correspond to specific guidelines.
