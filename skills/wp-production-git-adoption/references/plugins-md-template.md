# PLUGINS.md Template

Place this file at the git root (whether `wp-content/` or the WordPress installation root). Add it to `.deployignore` — it is a dev artifact and should not be deployed to production.

The Source column uses [shields.io](https://shields.io) badges for visual scanning. If the `wp-plugin-version-check` workflow is set up for this repo, it will regenerate Source badges automatically on each run. Otherwise, follow the badge conventions below when populating the column manually.

```markdown
# Plugins

Living document. Update when a plugin is added, removed, or updated.

To regenerate from the live server:
    wp plugin list --fields=name,title,status,version --format=table

**Source badge colors:** ![WordPress.org](https://img.shields.io/badge/WordPress.org-21759B?style=flat-square&logo=wordpress&logoColor=white) wp.org-hosted · ![GitHub](https://img.shields.io/badge/GitHub-24292e?style=flat-square&logo=github&logoColor=white) open-source GitHub · ![first-party](https://img.shields.io/badge/first--party-22C55E?style=flat-square) in-house/tracked · ![premium](https://img.shields.io/badge/vendor-FF6900?style=flat-square) commercial · ![saas](https://img.shields.io/badge/vendor-7C3AED?style=flat-square) free plugin + paid service  
**License conventions:** `Free` · `Open source` · `Free (SaaS account)` · `Premium — held by [name], billed [cycle]` · `—` (first-party) · ⚠️ unknown

---

## Active Plugins

| Title | Slug | Version | In Repo | Source | License |
|-------|------|---------|---------|--------|---------|
| Example Free Plugin | example-free | 3.1 | | [![WordPress.org](https://img.shields.io/badge/WordPress.org-21759B?style=flat-square&logo=wordpress&logoColor=white)](https://wordpress.org/plugins/example-free/) | Free |
| Example Premium Plugin | example-premium | 2.0 | | [![example.com](https://img.shields.io/badge/example.com-FF6900?style=flat-square)](https://example.com) | Premium — held by [name], billed [annually/monthly] |
| Example GitHub Plugin | example-gh-plugin | 1.5 | | [![GitHub](https://img.shields.io/badge/GitHub-24292e?style=flat-square&logo=github&logoColor=white)](https://github.com/org/example-gh-plugin) | Open source |
| My Custom Plugin | my-custom-plugin | 1.2 | ✓ | ![first-party](https://img.shields.io/badge/first--party-22C55E?style=flat-square) | — |

## Inactive Plugins

| Title | Slug | Version | In Repo | Source | License |
|-------|------|---------|---------|--------|---------|

## Must-Use Plugins

| Title | Slug | Version | In Repo | Source | License |
|-------|------|---------|---------|--------|---------|
```

## Badge conventions

Source badges use shields.io static badges for visual scanning. The color signals the plugin's relationship at a glance:

| Color | Hex | Meaning |
|-------|-----|---------|
| Blue (WordPress logo) | `21759B` | Hosted on wordpress.org — free, publicly downloadable |
| Dark (GitHub logo) | `24292e` | Open-source GitHub project — free, publicly downloadable |
| Green | `22C55E` | First-party — developed in-house, tracked in this repo |
| Orange | `FF6900` | Commercial / premium — requires a paid license |
| Purple | `7C3AED` | SaaS — free plugin, but requires a paid external service account |
| Gray | `6B7280` | Host-managed — installed and maintained by the hosting provider |

### Badge format by source type

**WordPress.org** — link to the plugin's own page:
```markdown
[![WordPress.org](https://img.shields.io/badge/WordPress.org-21759B?style=flat-square&logo=wordpress&logoColor=white)](https://wordpress.org/plugins/{slug}/)
```

**GitHub** — link to the specific repository:
```markdown
[![GitHub](https://img.shields.io/badge/GitHub-24292e?style=flat-square&logo=github&logoColor=white)](https://github.com/{owner}/{repo})
```

**First-party** — no link (it is this repo):
```markdown
![first-party](https://img.shields.io/badge/first--party-22C55E?style=flat-square)
```

**Premium vendor** — link to vendor homepage; use the domain as the badge label. Note: hyphens in the domain must be doubled (`--`) in the badge URL path:
```markdown
[![vendor.com](https://img.shields.io/badge/vendor.com-FF6900?style=flat-square)](https://vendor.com)
```

**SaaS vendor** — same pattern as premium, purple instead of orange:
```markdown
[![vendor.com](https://img.shields.io/badge/vendor.com-7C3AED?style=flat-square)](https://vendor.com)
```

**Host-managed** (must-use plugins installed by the host):
```markdown
![Host managed](https://img.shields.io/badge/Host_managed-6B7280?style=flat-square)
```

**Unknown source** — mark with a warning badge until resolved:
```markdown
![unknown](https://img.shields.io/badge/unknown_%E2%9A%A0%EF%B8%8F-red?style=flat-square)
```

## License column conventions

- `Free` — free/open-source, no license key required
- `Open source` — GitHub-hosted OSS
- `Free (SaaS account)` — free plugin but requires a paid external service
- `Premium — held by [name], billed [annually/monthly]` — paid license; document who holds it and the billing cycle
- `—` — not applicable (first-party, or free with no separate license)

## When source is unknown

If you cannot identify a plugin's source from its slug, title, or a quick search, mark it with the unknown badge and **ask the client or developer who set up the site** before finalising the document. An unknown source may indicate:

- An unlicensed copy of a premium plugin
- An abandoned fork or custom build without any internal documentation
- A plugin installed by a previous agency that is no longer supported

Do not leave plugins with unknown sources permanently. Resolve every entry before PLUGINS.md is considered complete.

## Keeping it current

Update PLUGINS.md whenever:
- A plugin is installed, activated, deactivated, or deleted
- A premium plugin license is transferred, renewed, or lapses
- A plugin's version advances significantly (major version bumps are worth noting)

If the `wp-plugin-version-check` workflow is active, it will update the Version column and regenerate Source badges automatically. Otherwise, regenerate the full list from the server periodically and diff it against the document.
