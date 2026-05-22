---
name: github-cli
description: "Use the gh CLI to manage GitHub pull requests, issues, releases, and workflows from the terminal. Use when interacting with GitHub without leaving the shell."
compatibility: "Any platform with gh installed. Supports GitHub.com and GitHub Enterprise Server."
license: MIT
metadata:
    author: georgestephanis
    version: "1.0"
    written: "2026-05-07"
    written_against:
        gh: "2.x"
---

# GitHub CLI (`gh`)

`gh` is GitHub's official CLI — it covers the full GitHub workflow (PRs, issues, repos, releases, Actions) directly from the terminal and supports JSON output for scripting.

## When to use

- Creating, reviewing, merging, or checking out pull requests
- Filing, triaging, or closing issues
- Triggering or monitoring GitHub Actions workflow runs
- Scripting GitHub operations in CI/CD or automation contexts
- Calling the GitHub REST API without managing auth manually (`gh api`)

Do NOT use for git operations themselves — use `git` directly for commits, branches, rebasing, etc.

## Inputs required

- **`gh` installed and authenticated** — see Procedure step 1 if not
- **Repo context** — most commands infer the repo from the current directory's git remote; pass `--repo owner/name` to override
- **Target** — PR number, issue number, branch name, or workflow name as appropriate

## Procedure

### 1. Verify gh is installed and authenticated

```bash
gh --version
gh auth status
```

If not installed, see [references/installation.md](references/installation.md).

If not authenticated:

```bash
gh auth login
```

Follow the interactive prompts — it will open a browser for OAuth or accept a token. For CI/non-interactive environments, set the `GH_TOKEN` environment variable instead.

For GitHub Enterprise Server, set `GH_HOST` before authenticating:

```bash
GH_HOST=github.mycompany.com gh auth login
```

### 2. Run the appropriate command

Commands are grouped by area — see the reference docs for full options:

- Pull requests → [references/pull-requests.md](references/pull-requests.md)
- Issues → [references/issues.md](references/issues.md)
- Repos, releases, and forks → [references/repos-and-releases.md](references/repos-and-releases.md)
- Workflows and Actions → [references/actions.md](references/actions.md)
- Scripting and the API → [references/scripting.md](references/scripting.md)

### 3. Scripting and non-interactive use

Add `--json <fields>` to any list/view command to get structured output. Chain with `--jq <filter>` to process inline:

```bash
gh pr list --json number,title,author --jq '.[] | "\(.number): \(.title) (\(.author.login))"'
```

For automation, suppress prompts with `--yes` where available, and authenticate via `GH_TOKEN`.

## Verification

- `gh auth status` shows your username and the scopes granted
- `gh pr list` / `gh issue list` return results (or an empty list) without errors
- Commands that create or mutate resources print a URL to the created/updated object on success

## Failure modes

**`gh: command not found`**
— Not installed or not on `$PATH`. See [references/installation.md](references/installation.md).

**`You are not logged into any GitHub hosts. Run gh auth login to authenticate.`**
— Run `gh auth login` or set `GH_TOKEN`.

**`HTTP 403: Resource not accessible by integration`**
— The token lacks the required scope. Re-run `gh auth refresh --scopes repo,workflow` (or whatever scope is needed).

**`HTTP 404: Not Found` on a valid repo**
— The repo may be private and the token doesn't have access, or `--repo` is pointing at the wrong owner/name.

**`Could not resolve to a Repository`**
— No git remote found in the current directory. Either `cd` into a cloned repo or pass `--repo owner/name` explicitly.

**Rate limiting**
— The GitHub API allows 5,000 requests/hour per authenticated user. For high-volume scripts, add `--paginate` carefully and consider caching responses. Check `gh api rate_limit` to see remaining quota.

## Escalation

- If authentication keeps failing, check [GitHub status](https://githubstatus.com) for platform incidents.
- For Enterprise Server issues, confirm `GH_HOST` is set correctly and that your machine can reach the host.
- For missing commands or behaviors, run `gh extension list` — some functionality ships as extensions.
- Full command reference: `gh help <command>` or [cli.github.com/manual](https://cli.github.com/manual)
