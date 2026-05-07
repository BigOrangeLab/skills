# Scripting with gh

## JSON output

Add `--json <fields>` to any `list` or `view` command. Specify only the fields you need:

```bash
gh pr list --json number,title,state,author
gh issue list --json number,title,labels,assignees
gh run list --json databaseId,status,conclusion,headBranch
```

Run `gh pr list --json` (no fields) to print the full list of available fields for that command.

## Filtering with --jq

Chain `--jq <filter>` with `--json` to apply a jq expression inline:

```bash
# Print "number: title" for all open PRs
gh pr list --json number,title --jq '.[] | "\(.number): \(.title)"'

# Get just the numbers of PRs by a specific author
gh pr list --json number,author --jq '.[] | select(.author.login == "alice") | .number'

# Count open issues by label
gh issue list --json labels --jq '[.[].labels[].name] | group_by(.) | map({label: .[0], count: length})'
```

## Calling the GitHub REST API directly

`gh api` sends authenticated requests to the GitHub API without managing tokens manually:

```bash
gh api /repos/owner/repo
gh api /repos/owner/repo/issues --paginate    # follow pagination automatically
gh api /user
gh api /rate_limit    # check remaining quota
```

With methods and body:

```bash
gh api --method POST /repos/owner/repo/labels \
  --field name="priority:high" \
  --field color="e11d48"

gh api --method PATCH /repos/owner/repo/issues/42 \
  --field state="closed"
```

With GraphQL:

```bash
gh api graphql --field query='
  query($owner:String!, $name:String!) {
    repository(owner:$owner, name:$name) {
      pullRequests(first:5, states:OPEN) {
        nodes { number title }
      }
    }
  }
' --field owner=myorg --field name=myrepo
```

## Environment variables

| Variable | Purpose |
|---|---|
| `GH_TOKEN` / `GITHUB_TOKEN` | Token for authentication (overrides stored credentials) |
| `GH_HOST` | GitHub hostname (for Enterprise Server) |
| `GH_ENTERPRISE_TOKEN` | Token for the Enterprise Server host |
| `GH_REPO` | Override the inferred `owner/repo` |
| `GH_PAGER` | Pager for output (set to `cat` to disable paging) |
| `NO_COLOR` | Disable color output |

## Aliases

Create shortcuts for common commands:

```bash
gh alias set prc 'pr create --fill'
gh alias set prl 'pr list --assignee @me'
gh alias set co 'pr checkout'
```

List and delete aliases:

```bash
gh alias list
gh alias delete prc
```

## Practical scripting patterns

### Wait for CI to pass before merging

```bash
gh pr checks --watch && gh pr merge --squash --delete-branch
```

### Bulk-close stale issues

```bash
gh issue list --state open --label "stale" --json number --jq '.[].number' \
  | xargs -I{} gh issue close {} --comment "Closing as stale."
```

### Get PR number for current branch

```bash
gh pr view --json number --jq '.number'
```

### Open the current repo in the browser

```bash
gh repo view --web
```
