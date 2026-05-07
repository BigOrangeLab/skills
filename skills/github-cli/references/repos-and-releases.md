# Repos and Releases

## Repositories

### View

```bash
gh repo view                     # current repo
gh repo view owner/repo
gh repo view owner/repo --web
```

### Clone

```bash
gh repo clone owner/repo
gh repo clone owner/repo my-dir
```

### Create

```bash
gh repo create my-new-repo
gh repo create my-new-repo --public --clone
gh repo create my-new-repo --private --description "My private repo"
gh repo create --template owner/template-repo
```

### Fork

```bash
gh repo fork owner/repo
gh repo fork owner/repo --clone       # clone the fork after forking
gh repo fork owner/repo --remote      # add the original as an upstream remote
```

### Sync a fork with upstream

```bash
gh repo sync                          # sync current fork's default branch
gh repo sync owner/fork --branch main
```

### Rename / delete

```bash
gh repo rename new-name
gh repo delete owner/repo --yes
```

## Releases

### Create a release

```bash
gh release create v1.2.0
gh release create v1.2.0 --title "Version 1.2.0" --notes "Bug fixes and improvements"
gh release create v1.2.0 ./dist/*.zip     # attach assets
gh release create v1.2.0 --draft
gh release create v1.2.0 --prerelease
gh release create v1.2.0 --generate-notes  # auto-generate notes from merged PRs
```

### List releases

```bash
gh release list
```

### View a release

```bash
gh release view v1.2.0
gh release view v1.2.0 --web
```

### Download release assets

```bash
gh release download v1.2.0
gh release download v1.2.0 --pattern "*.zip"
gh release download v1.2.0 --dir ./downloads
```

### Upload assets to an existing release

```bash
gh release upload v1.2.0 ./dist/app.zip
```

### Delete a release

```bash
gh release delete v1.2.0 --yes
gh release delete v1.2.0 --cleanup-tag --yes    # also delete the git tag
```
