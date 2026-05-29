# PLUGINS.md Template

Place this file at the git root (whether `wp-content/` or the WordPress installation root). Add it to `.deployignore` — it is a dev artifact and should not be deployed to production.

```markdown
# Plugins

Living document. Update when a plugin is added, removed, or updated.

To regenerate from the live server:
    wp plugin list --fields=name,title,status,version --format=table

## Active Plugins

| Title | Slug | Version | In Repo | Source | License |
|-------|------|---------|---------|--------|---------|
| Example Free Plugin | example-free | 3.1 | | wordpress.org | Free |
| Example Premium Plugin | example-premium | 2.0 | | example.com | Premium — held by [name], billed [annually/monthly] |
| My Custom Plugin | my-custom-plugin | 1.2 | ✓ | first-party | — |

## Inactive Plugins

| Title | Slug | Version | In Repo | Source | License |
|-------|------|---------|---------|--------|---------|

## Must-Use Plugins

| Title | Slug | Version | In Repo | Source | License |
|-------|------|---------|---------|--------|---------|
```

## Column conventions

**Source:**
- `wordpress.org` — free plugin from the plugin directory
- `[vendor site]` (e.g. `gravityforms.com`, `yoast.com`) — commercial or premium plugin
- `first-party` — developed in-house and tracked in this repo
- `unknown ⚠️` — source could not be determined; requires follow-up (see below)

**License:**
- `Free` — free/open-source, no license key required
- `Premium — held by [name], billed [annually/monthly]` — paid license; document who holds it and the billing cycle so the client and any future developer can locate and renew it
- `—` — not applicable (first-party, or free plugin with no separate license)

## When source is unknown

If you cannot identify a plugin's source from its slug, title, or a quick search, mark it `unknown ⚠️` and **ask the client or developer who set up the site** before finalising the document. An unknown source may indicate:

- An unlicensed copy of a premium plugin
- An abandoned fork or custom build without any internal documentation
- A plugin installed by a previous agency that is no longer supported

Do not leave plugins at `unknown ⚠️` permanently. Resolve every entry before the PLUGINS.md is considered complete.

## Keeping it current

Update PLUGINS.md whenever:
- A plugin is installed, activated, deactivated, or deleted
- A premium plugin license is transferred, renewed, or lapses
- A plugin's version advances significantly (major version bumps are worth noting)

Regenerate the full list from the server periodically and diff it against the document to catch anything that changed outside of a tracked deploy.
