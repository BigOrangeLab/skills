# Issues

## Create an issue

```bash
gh issue create
gh issue create --title "Bug: login fails on mobile" --body "Steps to reproduce..."
gh issue create --label "bug,priority:high" --assignee alice --milestone "v2.0"
```

## List issues

```bash
gh issue list
gh issue list --state open        # open (default), closed, all
gh issue list --label "bug"
gh issue list --assignee @me
gh issue list --milestone "v2.0"
gh issue list --limit 50
```

## View an issue

```bash
gh issue view 42
gh issue view 42 --web    # open in browser
```

## Comment on an issue

```bash
gh issue comment 42 --body "I can reproduce this on v1.3"
```

## Close / reopen

```bash
gh issue close 42
gh issue close 42 --comment "Fixed in #123"
gh issue reopen 42
```

## Edit an issue

```bash
gh issue edit 42 --title "Updated title"
gh issue edit 42 --add-label "confirmed" --remove-label "needs-triage"
gh issue edit 42 --add-assignee bob
gh issue edit 42 --milestone "v2.1"
```

## Transfer or delete

```bash
gh issue transfer 42 owner/other-repo
gh issue delete 42 --yes
```

## Pin / unpin

```bash
gh issue pin 42
gh issue unpin 42
```
