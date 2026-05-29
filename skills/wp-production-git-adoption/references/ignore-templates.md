# Ignore File Templates

## .gitignore additions

Common entries missing from generated WordPress gitignores. Append to the existing `.gitignore` as needed:

```gitignore
# WordPress runtime directories
jetpack-waf/
upgrade-temp-backup/
np-newsletters-pixel.php
index.php                  # the "silence is golden" stub WP drops everywhere

# Artifacts
themes/*.zip
*.swp
*.swo

# WordPress Studio / SQLite (if applicable)
/database/
db.php
```

## .deployignore template

Files that belong in the repo but must not be deployed to production. Create `.deployignore` at the git root if it does not already exist:

```
## Files
AGENTS.md
LICENSE
README.md
PLUGINS.md
.editorconfig
.stylelintrc.json
.phpcs.xml
.phpmd.xml
.phpstan.neon
postcss.config.js

## GitHub / tooling
.github
.github/**
.gitignore

## Composer / Node
composer.json
composer.lock
package.json
package-lock.json
```

After editing ignore files, unstage any files that should now be ignored:

```bash
git rm --cached <file>
# or for directories:
git rm --cached -r <directory>/
```
