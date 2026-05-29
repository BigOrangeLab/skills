# PLUGINS.md Template

Place this file at the git root (whether `wp-content/` or the WordPress installation root). Add it to `.deployignore` — it is a dev artifact and should not be deployed to production.

```markdown
# Plugins

Living document. Update when a plugin is added, removed, or updated.

To regenerate from the live server:
    wp plugin list --fields=name,title,status,version --format=table

## Active Plugins

| Title | Slug | Version | In Repo |
|-------|------|---------|---------|
| ...  | ...  | ...     |         |

## Inactive Plugins

| Title | Slug | Version | In Repo |
|-------|------|---------|---------|

## Must-Use Plugins

| Title | Slug | Version | In Repo |
|-------|------|---------|---------|
```
