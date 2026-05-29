# Pressable Deployment

Pressable has a **native GitHub integration** managed entirely through their control panel. There is no workflow YAML to write and no GitHub Actions runner involved — Pressable synchronises your repo to the server automatically when you push to the configured branch.

## Setup

1. **Commit `.deployignore` to the repo first** — it must exist in the repository *before* you activate the integration, or files that should be excluded will be deleted from the server on first sync.

2. In the Pressable control panel, go to **My Sites → [site] → Advanced → GitHub Integration**.

3. Connect your GitHub account and select the repository.

4. Choose the deployment branch (`trunk`, `main`, `master`, etc.).

5. Configure the deployment paths:
   - **Repository Subdirectory** — the folder inside the repo to deploy *from* (e.g. `wp-content/` for a wp-content-rooted repo, or leave blank for the repo root).
   - **Destination Path** — where on the server to deploy *to*, relative to `/htdocs` (e.g. `wp-content/` or leave blank for the site root).

6. Create a site backup before proceeding.

7. Click **Set and Deploy** to trigger the initial sync.

## Required secrets/variables

None — the integration is authenticated by Pressable directly via their GitHub OAuth connection. No GitHub Actions secrets or workflow files are needed.

## Important constraints

- **WordPress core files cannot be deployed** — they are symlinked read-only by Pressable. Attempting to include core in the repo will fail or be ignored.
- **Files not in the repo are deleted from the server by default** — this includes any files previously deployed that have since been removed from the repo.
- **Protected paths**: Pressable automatically prevents deletion of `wp-config.php`, `wp-content/uploads/`, `wp-content/cache/`, and default bundled themes, regardless of repo contents.
- **`.deployignore` must be committed before activation** — if the file is added after the integration is already active, run a manual re-sync from the Pressable dashboard to apply it.

## Drift detection

The native integration has no built-in drift detection. If you need to check whether files have been edited directly on the server since the last deploy, use Pressable's SSH access (credentials available in **My Sites → [site] → SSH/SFTP Details**) with the standard rsync drift detection workflow from the parent skill — the SSH variables required are the same as `host-generic-ssh.md`, even though the deployment itself does not use rsync.
