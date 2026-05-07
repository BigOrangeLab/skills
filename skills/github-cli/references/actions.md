# GitHub Actions

## Workflows

### List workflows

```bash
gh workflow list
gh workflow list --all    # include disabled workflows
```

### View a workflow

```bash
gh workflow view "CI"
gh workflow view ci.yml
gh workflow view 12345    # by ID
```

### Trigger a workflow run

```bash
gh workflow run "Deploy"
gh workflow run deploy.yml
gh workflow run deploy.yml --ref my-branch
gh workflow run deploy.yml --field environment=production
```

### Enable / disable a workflow

```bash
gh workflow enable "CI"
gh workflow disable "Nightly Build"
```

## Workflow runs

### List runs

```bash
gh run list
gh run list --workflow CI
gh run list --branch main
gh run list --status failure      # queued, in_progress, completed, success, failure, cancelled
gh run list --limit 20
```

### View a run

```bash
gh run view                       # most recent run on current branch
gh run view 9876543210
gh run view 9876543210 --log      # full logs
gh run view 9876543210 --log-failed   # logs for failed steps only
```

### Watch a run until it completes

```bash
gh run watch 9876543210
gh run watch 9876543210 --exit-status   # exit non-zero if run fails (useful in scripts)
```

### Re-run a failed run

```bash
gh run rerun 9876543210
gh run rerun 9876543210 --failed        # re-run only failed jobs
```

### Cancel a run

```bash
gh run cancel 9876543210
```

### Download artifacts

```bash
gh run download 9876543210
gh run download 9876543210 --name my-artifact --dir ./artifacts
```
