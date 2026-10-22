# WordPress 7.2 — Sources, Status, and Schedule

Researched 22 October 2026, two days after Beta 1. Every "In Beta 1" claim in this skill was checked against `wordpress-develop` trunk at `7.2-beta1-64606` and Gutenberg at the pinned 24.2.0 commit (`7b95ee96b`), not only against announcements. **Update this file at RC1 and at release**, and when the 7.2 Field Guide is published (it doesn't exist yet).

## Schedule

| Milestone                    | Date                          |
| ---------------------------- | ----------------------------- |
| Beta 1 (feature freeze)      | 20 October 2026 — released    |
| RC1 (string and code freeze) | 17 November 2026              |
| RC2 / RC3                    | 24 November / 1 December 2026 |
| Final release                | 8 December 2026               |

Sources: [WordPress 7.2 release page](https://make.wordpress.org/core/7-2/), [release party schedule](https://make.wordpress.org/core/2026/10/06/wordpress-7-2-release-party-schedule/). The [call for volunteers](https://make.wordpress.org/core/2026/08/12/wordpress-7-2-call-for-volunteers/) and [Help Test WordPress 7.2](https://make.wordpress.org/test/2026/10/20/help-test-wordpress-7-2/) posts say 9 December; trust the release page.

Gutenberg range: 7.2 merges Gutenberg **23.7 through 24.2**. More Gutenberg fixes are backported during the beta/RC period.

## Primary sources

| Source                                                                                                                                                                                                                                                                                                                                                                                                                                                    | Covers                                                              |
| --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------- |
| [Roadmap to 7.2](https://make.wordpress.org/core/2026/09/18/roadmap-to-7-2/)                                                                                                                                                                                                                                                                                                                                                                              | Everything planned, with tracking tickets; explicitly not a promise |
| [What's new for developers? (October 2026)](https://developer.wordpress.org/news/2026/10/whats-new-for-developers-october-2026/)                                                                                                                                                                                                                                                                                                                          | Gutenberg 24.0–24.1 changes, deprecations, kses, block supports     |
| [Dev note: `NO_BACKSLASH_ESCAPES` turned off](https://make.wordpress.org/core/2026/10/09/no_backslash_escapes-sql-mode-is-now-turned-off-by-default-in-wordpress-7-2/)                                                                                                                                                                                                                                                                                    | wpdb SQL mode change, `db.php` opt-out                              |
| [Progress report: `wp_kses()`](https://make.wordpress.org/core/2026/10/07/progress-report-wp_kses/)                                                                                                                                                                                                                                                                                                                                                       | HTML API kses rebuild, `wp_kses_force_legacy_parser`                |
| [Proposal: a Secrets API](https://make.wordpress.org/core/2026/08/25/proposal-a-secrets-api-for-wordpress-7-2/)                                                                                                                                                                                                                                                                                                                                           | Secrets API design                                                  |
| [Replacing Dashicons in the admin bar and menu](https://make.wordpress.org/core/2026/09/04/replacing-dashicons-in-the-admin-bar-and-menu/)                                                                                                                                                                                                                                                                                                                | SVG admin icons (a design proposal; the commit is the authority)    |
| [Call for testing: DataForm editor inspector](https://make.wordpress.org/core/2026/09/17/call-for-testing-dataform-editor-inspector/)                                                                                                                                                                                                                                                                                                                     | Post sidebar moving to DataForm; `PluginPostExcerpt` risk           |
| [Introducing Ipsum](https://make.wordpress.org/core/2026/09/16/introducing-ipsum-the-new-default-theme/)                                                                                                                                                                                                                                                                                                                                                  | New default theme (bundled in Beta 1)                               |
| Gutenberg release notes [23.7](https://github.com/WordPress/gutenberg/releases/tag/v23.7.0) · [23.8](https://github.com/WordPress/gutenberg/releases/tag/v23.8.0) · [23.9](https://github.com/WordPress/gutenberg/releases/tag/v23.9.0) · [24.0](https://github.com/WordPress/gutenberg/releases/tag/v24.0.0) · [24.1](https://github.com/WordPress/gutenberg/releases/tag/v24.1.0) · [24.2](https://github.com/WordPress/gutenberg/releases/tag/v24.2.0) | Editor, components, Interactivity API changes                       |
| [`@wordpress/interactivity` changelog](https://github.com/WordPress/gutenberg/blob/trunk/packages/interactivity/CHANGELOG.md), [`@wordpress/icons` changelog](https://github.com/WordPress/gutenberg/blob/trunk/packages/icons/CHANGELOG.md)                                                                                                                                                                                                              | Directive removals, `data-wp-html`, stroke icons                    |

Core changesets cited in the references link to `core.trac.wordpress.org/changeset/<rev>`. When a claim matters, read the changeset message and the `@since 7.2.0` docblock; both are more precise than the announcement posts.

## Status of roadmap items

### In Beta 1

Script/style concatenation retired · `wp_kses()` on the HTML API (enabled by default) · Secrets API (no admin UI; CLI stays in the feature plugin) · SVG icons in the admin menu and toolbar · application password created email · `NO_BACKSLASH_ESCAPES` stripped · `WP_DEBUG` local default · "On This Day" and the separate Recent Comments widget · meta box reordering screen option · random content redirects · sitemap XSL removal · entity fields/actions registries · `data-wp-html` · text-shadow and background-clip supports · Ipsum default theme · Gutenberg 23.7–24.2.

### Roadmap items not in Beta 1

Still possible before RC1 only if they were already in a Gutenberg release that gets backported; otherwise expect 7.3.

| Item                                                                                          | State at Beta 1                                                                        |
| --------------------------------------------------------------------------------------------- | -------------------------------------------------------------------------------------- |
| Description List block ([gutenberg#4880](https://github.com/WordPress/gutenberg/issues/4880)) | Not in Gutenberg 24.2                                                                  |
| Table of Contents block made stable                                                           | Still `__experimental` in 24.2                                                         |
| React 19                                                                                      | Core still bundles React 18.3.1; roadmap: "unlikely to fully land"                     |
| Sudo mode                                                                                     | Proposal not published                                                                 |
| Safer `default_role` values ([#46744](https://core.trac.wordpress.org/ticket/46744))          | No commit                                                                              |
| SameSite cookies ([#37000](https://core.trac.wordpress.org/ticket/37000))                     | No commit                                                                              |
| Widget accessibility mode removal ([#52399](https://core.trac.wordpress.org/ticket/52399))    | No commit                                                                              |
| Admin notice accessibility ([#50486](https://core.trac.wordpress.org/ticket/50486))           | Committed, then **reverted** on 14 October                                             |
| Secrets API admin screen                                                                      | Deferred to 7.3                                                                        |
| DataForm post inspector                                                                       | Under test in Gutenberg; check the RC before relying on `PluginPostExcerpt` either way |

### Not in 7.2

Real-time collaborative editing (deliberately excluded) and AI features beyond the PHP AI Client update (they stay in the AI plugin, the MCP Adapter, and other feature plugins). No change to minimum PHP (7.4) or MySQL (5.5.5).
