---
name: wp-production-git-adoption
description: "Use when connecting an existing WordPress production site to a GitHub repository for the first time — or re-aligning a local snapshot of production to its remote after the two have diverged. Covers remote detection, git root pattern identification, safe index alignment without overwriting files, PLUGINS.md inventory generation, ignore rules, divergence review, and optionally backdated commits from file timestamps."
compatibility: "WordPress sites on any host (Kinsta, WP Engine, Pantheon, etc.) with SSH access. Requires gh CLI locally, and WP-CLI available on the production server."
license: MIT
metadata:
    author: georgestephanis
    version: "1.0"
    written: "2026-05-29"
    written_against:
        git: "2.49.0"
        gh: "2.92.0"
        wp-cli: "2.11.0"
---

# WordPress Production Git Adoption

## When to use

Use this skill when:

- A live WordPress site has an existing GitHub repo but the local copy is not yet a git repository (or has no remote connected).
- Production files have drifted from the repo and need to be reconciled.
- The goal is to review divergences and eventually configure deployment from GitHub to the host.

Do NOT use for greenfield repos or sites with an already-healthy local git setup. Use `wp-client-repo-setup` if the goal is adding dev tooling to an existing repo rather than adopting a production site.

## Inputs required

- **Local snapshot of the site** — the production files, pulled via SSH/tar/rsync. If `wp-content/` is empty, the snapshot was pulled from the wrong directory (see Phase 1).
- **GitHub repo slug** — `owner/repo`, accessible via the `gh` CLI.
- **SSH connection details** — host, user, port for the production server. These are site-specific and must be provided per project.
- **WP-CLI path on the server** — usually just `wp`, but some hosts require an absolute path or `--path=` argument.

## Procedure

### Phase 0: Orient — detect remote branch and git root pattern

Always check the remote before touching anything locally.

```bash
gh repo view <owner/repo> --json defaultBranchRef,url
```

This gives you the canonical default branch name (commonly `trunk`, `main`, or `master`). Do not assume — use whatever the remote reports.

Then inspect the remote file tree to determine the **git root pattern**:

```bash
git ls-tree -r --name-only origin/<branch> | head -30
# Also check the remote .gitignore:
git show origin/<branch>:.gitignore | head -40
```

Two patterns exist — see [references/git-root-patterns.md](references/git-root-patterns.md) for the full detection table and commands. Getting this wrong misaligns all tracked paths with the remote history. Confirm before proceeding.

### Phase 1: Acquire a complete local snapshot

Pull the production files via SSH. The critical mistake is pulling from the wrong directory — many hosts (Kinsta, WP Engine) keep the WordPress root inside a `public/` or `httpdocs/` subdirectory, not directly in the home directory. For Pantheon-hosted sites, use the `terminus-wp-cli` skill for SSH and WP-CLI access rather than raw SSH.

```bash
# On the production server — verify the WordPress root first:
ls ~/          # look for public/, httpdocs/, www/, etc.
ls ~/public/   # confirm wp-config.php, wp-content/, etc. are here

# Then tar from the correct path:
tar -cvzf site.tgz \
  --exclude='public/wp-content/uploads' \
  --exclude='public/.git' \
  public
```

If the host uses symlinks for plugins or themes, add `-h`/`--dereference` to follow them:

```bash
tar -hcvzf site.tgz --exclude='public/wp-content/uploads' public
```

Pull the archive locally and extract to your working directory.

**Verify the snapshot is complete** before proceeding:

```bash
ls wp-content/   # must show plugins/, themes/, mu-plugins/
```

If `wp-content/` is sparse, you pulled from the wrong directory. Re-pull.

### Phase 2: Initialize git and align to the remote

Run `git init` inside the correct root (per Phase 0), specifying the branch name from the remote:

```bash
git init -b <branch>
git remote add origin <repo-url>
git fetch
```

`git fetch` downloads all remote history and refs **without modifying any file on disk**.

Next, align the index to the remote branch using a soft reset — this tells git what the remote's state was, without overwriting local files:

```bash
git reset --soft origin/<branch>
```

Before staging anything, restore the `.gitignore` from the remote so ignore rules are in effect:

```bash
git checkout origin/<branch> -- .gitignore
```

Open the repo in GitHub Desktop now so you can watch progress as subsequent phases run:

```bash
open -a 'Github Desktop' .
```

Now stage everything:

```bash
git add .
```

At this point `git status` shows the real divergences:
- **`deleted:`** — files in the remote but not on disk (often dev-only tooling never deployed to production)
- **`modified:`** — files changed on production since the last commit
- **`new file:`** — files on disk not yet in the repo

Restore dev-only files that should exist locally but were never deployed:

```bash
git checkout origin/<branch> -- .deployignore .editorconfig README.md composer.json package.json
# ...and any other tooling files shown as deleted
```

Then re-stage:

```bash
git add .
```

Set tracking so `git push`/`git pull` default to the right remote:

```bash
git branch --set-upstream-to=origin/<branch> <branch>
```

### Phase 3: Generate PLUGINS.md

Create a living inventory of all installed plugins before making any commits. This documents third-party plugins (commercial or free) even if they are not tracked in the repo.

Generate the list via WP-CLI over SSH:

```bash
ssh <user>@<host> -p <port> "wp --path=<absolute-wp-path> plugin list --fields=name,title,status,version --format=table"
```

The `--path` argument usually needs to be an absolute path (e.g. `/www/sitename_123/public`). Find it by SSHing in and running `pwd` from the WordPress root.

WP-CLI provides `name`, `title`, `status`, and `version`. The **Source** and **License** columns require manual annotation — WP-CLI has no knowledge of commercial licensing or billing details.

Create `PLUGINS.md` at the git root (whether that is `wp-content/` or the WordPress root) with three sections: **Active**, **Inactive**, and **Must-Use**. See [references/plugins-md-template.md](references/plugins-md-template.md) for the full table format, column conventions, and `.deployignore` placement note.

#### Annotate sources and licenses

For each plugin, fill in:
- **In Repo** — `✓` if the plugin is tracked in this repository, blank otherwise.
- **Source** — where the plugin comes from: `wordpress.org`, the vendor's site (e.g. `gravityforms.com`), or `first-party` for in-house code.
- **License** — for premium plugins: who holds the license and the billing cycle. For free plugins: `Free`. For first-party: `—`.

If the source of a plugin is not recognizable from its slug or title, mark it `unknown ⚠️` and **ask the user** rather than guessing. Do not leave any entry at `unknown ⚠️` permanently — it may indicate an unlicensed premium copy or an abandoned fork.

Once PLUGINS.md is committed and Source annotations are complete, the `wp-plugin-version-check` skill can automate version tracking going forward.

### Phase 4: Review and update ignore rules

Check `.gitignore` for WordPress-specific omissions and `.deployignore` for dev-only files that should never be deployed. See [references/ignore-templates.md](references/ignore-templates.md) for both templates and the `git rm --cached` commands to unstage newly-ignored files.

### Phase 5: Review divergences

Use `git status` and `git diff` to understand what changed on production. Group changes into logical batches before committing. Key things to look for:

- **Deleted mu-plugins** — plugins removed from production but still in the repo. Commit the deletion with an explanatory message.
- **New template files** — additions to themes that represent feature work done directly on production.
- **Modified plugin/theme files** — production edits. Review the diff to understand what changed and why before committing.
- **Auto-generated files** — compiled CSS maps, WAF rules, email pixel scripts. Add to `.gitignore` rather than committing.

#### Distinguish production edits from undeployed repo commits

Before staging modified files, check whether the production version of a file matches an *older* commit — meaning the repo advanced but production was never updated:

```bash
# Review commit history for a specific file
git log --oneline origin/<branch> -- <file>

# Compare working tree (production) against one or two commits back
git diff origin/<branch>~1 -- <file>
git diff origin/<branch>~2 -- <file>
```

If the working-tree version matches an earlier commit, the divergence is a **stale deployment** (repo is ahead of production), not a production edit. Decide:

- **Leave the production state** — stage and commit the working-tree version. The gap between the undeployed commit and this snapshot becomes visible in history. Note in the commit message that this reflects the actual deployed state, not the desired one.
- **Walk it back** — restore the repo version with `git checkout origin/<branch> -- <file>` before staging, treating the undeployed change as still-unreleased work.

Escalate to the user for any stale-deployment divergence — the right call depends on whether the undeployed commit was intentional, a failed deploy, or a dev-only change that should never have shipped.

### Phase 6: Commit — optionally backdated

Check when modified files were last touched:

```bash
git diff --name-only --cached | xargs stat -f "%Sm %N" -t "%Y-%m-%d %H:%M:%S" | sort
```

Files often cluster into natural date groups reflecting when work was actually done. If backdating to match production reality, commit each group separately using `GIT_COMMITTER_DATE` and `--date`:

```bash
git add <files-from-this-date>
GIT_COMMITTER_DATE="<YYYY-MM-DDTHH:MM:SS-TZ>" \
  git commit --date="<YYYY-MM-DDTHH:MM:SS-TZ>" \
  -m "<describe what changed and why>"
```

Use the latest timestamp within each group as the commit date. Write commit messages that describe what changed and include a best guess at why — this is archaeological history and context matters.

Commit today's bookkeeping changes (`.gitignore`, `.deployignore`, `PLUGINS.md`) last with the current date.

### Phase 7: Audit and update markdown documentation

Review all tracked markdown files for stale or inaccurate content. The goal is accuracy, not churn — only update things that are genuinely wrong or misleading.

Find tracked markdown files:

```bash
git ls-tree -r --name-only HEAD | grep -i '\.md$'
```

Focus on root-level and project-authored docs — ignore changelogs and READMEs inside third-party plugins and themes.

Common things to check:

- **Plugin/mu-plugin status** — if a plugin is listed in docs as active but is now inactive on production (or vice versa), note it.
- **File references** — any file paths cited in `AGENTS.md`, `copilot-instructions.md`, or `README.md` that no longer exist or have been moved.
- **New first-party files** — significant new theme templates, plugin files, or mu-plugins added on production that should appear in "Useful Starting Files" or architectural notes.
- **Removed code** — references to mu-plugins, helpers, or classes that were deleted on production and should no longer appear in docs.
- **Tooling script names** — verify that any `composer` or `npm run` commands cited in docs still match the actual keys in `composer.json` / `package.json`.
- **PHPCS ruleset paths** — check that `.phpcs.xml` paths cited in docs still exist on disk.

Commit doc-only updates as a separate commit with a clear message:

```
git add AGENTS.md .github/copilot-instructions.md README.md
git commit -m "Update docs to reflect current production state."
```

Do not mix doc updates with code changes.

#### Create or review .github/copilot-instructions.md

GitHub Copilot uses this file for workspace context during code reviews and inline suggestions. It should mirror the architecture and conventions captured in `AGENTS.md`.

- **If the file does not exist**: create a basic one drawing from `AGENTS.md` — codebase scope, key plugin/theme structure, code conventions (naming prefixes, hook-first patterns, text domains), and risk-sensitive paths that require extra care.
- **If the file exists**: review it against the current state of `AGENTS.md` and what you know about the codebase. Update anything stale or missing.

Commit alongside other doc-only updates in the same commit.

### Phase 8: Evaluate dev tooling (wp-client-repo-setup)

After the production state is committed, assess whether the repo has adequate development tooling (PHPCS, WPCS, PHPStan, `@wordpress/scripts`, etc.).

Check for existing tooling:

```bash
ls composer.json package.json phpcs.xml phpcs.xml.dist .phpcs.xml 2>/dev/null
```

**If tooling is already present and functional** — skip this step.

**If tooling is absent or minimal** — decide whether to run the `wp-client-repo-setup` skill now or defer:

- **Run now** if the user wants a complete setup in one session and is ready to review and commit tooling config changes immediately.
- **Defer** if the priority is getting the production state committed and pushed first, or if tooling decisions need more discussion. In that case, note it as a follow-up: "Run `wp-client-repo-setup` once the initial sync is pushed."

Running `wp-client-repo-setup` during this session adds at minimum a `composer.json` with PHPCS/WPCS and a `phpcs.xml.dist` ruleset. Those changes should be committed as a separate, clearly-labelled commit after the production sync commits — not mixed in.

### Phase 9: Configure or pause deployments

Before pushing, clarify the deployment situation to avoid accidentally triggering a deploy to production from an unreviewed state.

**Check for existing GitHub Actions workflows:**

```bash
ls .github/workflows/ 2>/dev/null
```

If deployment workflows exist:

- **If ready to deploy from GitHub** — review the workflow file to confirm it targets the right environment and branch, then proceed to push. Update any host-specific secrets (SSH keys, deploy tokens) in the GitHub repo's Settings → Secrets if not already present.
- **If NOT ready to deploy yet** — temporarily disable the workflow before pushing:
  - Via GitHub UI: Settings → Actions → disable the specific workflow, or rename the workflow file to `*.yml.disabled` in a commit.
  - Via `gh`: `gh workflow disable <workflow-name>` — see the `github-cli` skill ([references/actions.md](../github-cli/references/actions.md)) for full `gh workflow` usage.

If no deployment workflow exists yet, this is the time to plan one. Use the `wp-github-deploy` skill for host-specific deployment workflow setup and a drift-detection workflow that surfaces files changed directly on production before a deploy overwrites them.

#### Plugin version monitoring (follow-up)

After the production state is committed and pushed, consider setting up the `wp-plugin-version-check` skill. It adds a workflow that detects when plugins are updated on the server, checks wordpress.org and GitHub for newer versions, queries premium vendor APIs where possible, and opens a PR updating PLUGINS.md automatically. It is independent of the deployment workflow and can be added at any point after PLUGINS.md exists.

#### Branch protection (human action required)

Raise this explicitly with the user and require a conscious yes/no answer — do not skip it silently.

> "Branch protection on `<branch>` is not yet configured. Recommended rules for a production-deploy branch: require pull request reviews before merging, require status checks to pass, disallow force-pushes. Would you like to enable branch protection now, defer it, or explicitly skip it for this project?"

- **If yes**: direct them to **GitHub UI → Settings → Branches → Add branch ruleset** (or Rulesets for newer GitHub). Do not configure it automatically — this is a governance decision.
- **If deferred or skipped**: note it as a follow-up item so it surfaces again in the next session.

A conscious "no" or "later" is acceptable. An unreviewed skip is not.

#### Integrity baseline (follow-up)

Once the production state is committed and pushed, run the `wp-integrity-check` skill to verify all third-party plugins, themes, and core against their official checksums and generate baseline snapshots for premium plugins. This is especially important for sites that have been running without version control — modified third-party files are a common indicator of previous compromise or untracked customisation.

## Verification

- `git status` shows a clean working tree.
- `git log --oneline` shows a coherent commit history with the remote's prior commits at the base.
- `git branch -vv` shows `[origin/<branch>]` tracking annotation.
- `PLUGINS.md` exists at the git root and lists all installed plugins with statuses.
- `.deployignore` covers all dev-only files (README, PLUGINS.md, AGENTS.md, tooling configs).
- `.gitignore` excludes runtime directories (uploads, jetpack-waf, upgrade-temp-backup, database).
- `git diff --stat HEAD origin/<branch>` confirms the local branch is ahead with only intentional commits.

## Failure modes

- **Wrong git root** — initializing at the WordPress root when the remote is `wp-content`-rooted (or vice versa) causes all tracked paths to mismatch. Detect from the remote tree before `git init`.
- **Sparse `wp-content/`** — tarring from `~/www` instead of `~/public` (or equivalent) produces an incomplete snapshot. Always verify `ls wp-content/` shows `plugins/`, `themes/`, `mu-plugins/` before proceeding.
- **Overwriting production files** — using `git checkout` or `git pull` after connecting the remote will replace local files with remote content. Use only `git reset --soft` and `git checkout origin/<branch> -- <specific-file>` for targeted restores.
- **Committing `wp-config.php`** — it typically contains live DB credentials and auth keys. Confirm it is in `.gitignore` before the first `git add .`.
- **Symlinked plugins/themes silently missing** — `tar` without `-h` skips symlink targets. Check for symlinks with `ls -la wp-content/` on the server if the snapshot is unexpectedly sparse.
- **Missing dev-only files treated as deleted** — files like `composer.json`, `package.json`, `.editorconfig` may exist only on the developer's machine, not on production. Restore from `git checkout origin/<branch> -- <file>` rather than treating them as intentionally removed.

## Escalation

Ask for user input when:

- The git root pattern is ambiguous (both a `wp-content/` directory and `plugins/` at the root exist in the remote).
- `wp-config.php` is not gitignored and contains live credentials.
- There are open branches on the remote with significant divergence — confirm whether to merge or ignore before committing.
- Production has files that appear to be from a different theme or plugin stack than what the repo tracks, suggesting the site's tech stack changed hosts or was significantly restructured.
- The SSH `wp` command fails to find WordPress — the absolute WP path must be confirmed before generating `PLUGINS.md`.
