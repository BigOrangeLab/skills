# Git Root Patterns

Two patterns exist for where the git root lives relative to a WordPress installation.

| Pattern | Indicator | `git init` location |
|---------|-----------|---------------------|
| **wp-content-rooted** | `plugins/`, `themes/`, `mu-plugins/` at root; `.gitignore` references `uploads/*` | Inside `wp-content/` |
| **WordPress-root** | `wp-content/` directory present in tree; `.gitignore` references `wp-content/uploads/*` | At the WordPress installation root |

## Detection commands

After fetching the remote, inspect its tree and ignore file:

```bash
git ls-tree -r --name-only origin/<branch> | head -30
git show origin/<branch>:.gitignore | head -40
```

If both a `wp-content/` directory and top-level `plugins/` appear in the remote tree, escalate to the user — the pattern is ambiguous.
