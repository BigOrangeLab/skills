# Pull Requests

## Create a PR

```bash
gh pr create
```

Interactive by default — prompts for title, body, base branch, and reviewers. Pass flags to skip prompts:

```bash
gh pr create --title "Fix login bug" --body "Closes #42" --base main --reviewer alice,bob
gh pr create --draft
gh pr create --fill    # use commit messages as title/body
```

## List PRs

```bash
gh pr list
gh pr list --state open          # open (default), closed, merged, all
gh pr list --author @me
gh pr list --label "bug"
gh pr list --base main
```

## View a PR

```bash
gh pr view          # current branch's PR
gh pr view 123
gh pr view 123 --web    # open in browser
```

## Check out a PR locally

```bash
gh pr checkout 123
```

## Review and comment

```bash
gh pr review 123 --approve
gh pr review 123 --request-changes --body "Please add tests"
gh pr review 123 --comment --body "Looks good, one nit"
gh pr comment 123 --body "LGTM"
```

## Check CI status

```bash
gh pr checks 123
gh pr checks 123 --watch    # poll until all checks complete
```

## Merge a PR

```bash
gh pr merge 123
gh pr merge 123 --merge       # create a merge commit
gh pr merge 123 --squash      # squash and merge
gh pr merge 123 --rebase      # rebase and merge
gh pr merge 123 --auto        # enable auto-merge (merges when checks pass)
gh pr merge 123 --delete-branch
```

## Close / reopen

```bash
gh pr close 123
gh pr reopen 123
```

## Edit a PR

```bash
gh pr edit 123 --title "New title"
gh pr edit 123 --add-label "needs-review" --remove-label "wip"
gh pr edit 123 --add-reviewer alice --add-assignee bob
```
