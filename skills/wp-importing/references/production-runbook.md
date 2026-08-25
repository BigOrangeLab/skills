# Production runbook

By the time you reach this document the importer should already have run cleanly — dry run, one, two, ten, full, then full again with no duplicates — on a disposable environment. If it has not, go back.

## Before

1. **Pick the window.** Lowest traffic you can get. If something goes wrong, fixing it is far easier when the site is quiet.
2. **Take a database backup.** Then confirm the restore procedure — actually read it, actually know the command. Discovering the restore path is broken while the site is half-migrated is the worst version of this job.
3. **Write down what a restore would cost.** Between the backup and any rollback, real users may place orders, leave comments, register accounts, or submit forms. List those tables, and decide how you would replay them. On an e-commerce site this may make rollback effectively impossible — which changes how cautious the run needs to be.
4. **Confirm the target structures exist in production.** Post types, taxonomies, and registered meta must be deployed and active, or the data lands invisible:

    ```bash
    wp post-type list --field=name
    wp taxonomy list --field=name
    ```

5. **Decide about email.** `WP_IMPORTING` plus `add_filter( 'pre_wp_mail', '__return_false' )` covers the import process itself. A site-wide email-blocking plugin is heavier: it can suppress transactional mail real visitors need, and email-logging plugins add a database write per message. Usually the process-scoped filter is the right level.
6. **Get the file onto the server** (`scp`/`rsync`), somewhere outside the web root. Exports routinely contain unpublished drafts and personal data — do not park them in `wp-content/uploads/`.
7. **Start a persistent session** so a dropped SSH connection does not kill the run:

    ```bash
    screen -S import        # or: tmux new -s import
    # detach with Ctrl-A D (screen) / Ctrl-B D (tmux); reattach with screen -r import
    ```

## During

Same escalation as the rehearsal, on production, with the log kept:

```bash
wp acme-import run /home/user/private/export.json --dry-run | tee ~/import-dryrun.log
```

Read the dry-run log properly. Then:

```bash
wp acme-import run /home/user/private/export.json --limit=1  | tee ~/import-01.log
```

Now stop and check, in wp-admin and on the front end:

- Did the content, terms, meta, author, dates, and featured image all land correctly?
- Did anything email a user, post to social, or fire a webhook?
- Did the search index, CDN, or CRM notice?

Only when that is clean:

```bash
wp acme-import run /home/user/private/export.json --limit=2  | tee ~/import-02.log
wp acme-import run /home/user/private/export.json --limit=10 | tee ~/import-10.log
wp acme-import run /home/user/private/export.json           | tee ~/import-full.log
```

Watch it run. Keep an eye on database load and on the site's own responsiveness. If either degrades, stop the run — the idempotency check means restarting later costs nothing but time.

### Hosts without SSH

Some managed and shared hosts offer no shell. Options, in order of preference:

1. A host-provided WP-CLI console or scheduled-task runner.
2. Running the import locally against a copy of the production database, then deploying the resulting database — only viable if production content is frozen for the window.
3. A capability-checked, nonce-protected admin endpoint that processes a batch per request and reports progress, driven by repeated requests. Chunk it small enough to finish inside the host's PHP timeout.

Never expose an unauthenticated import endpoint, even briefly.

## After

```bash
# Counts, compared against the Phase 1 source aggregation.
wp post list --post_type=meal --post_status=any --format=count
wp term list ingredient --format=count

# Idempotency held?
wp acme-import run /home/user/private/export.json --dry-run | grep -c 'Would create'

# Term counts accurate after deferral.
wp term recount ingredient

# Nothing unexpected queued.
wp cron event list
```

Then:

- Spot-check a random sample per type, including deliberately awkward items: missing optional fields, longest body, most relationships, oldest date.
- Run the media pass (see `media-handling.md`) if it was deferred.
- If the import used `draft`, spot-check and then bulk publish — with `WP_IMPORTING` still defined, or every notification you suppressed fires on the status transition.
- Set up redirects if permalinks changed from the old site.
- Flush only the caches that need it (`wp rewrite flush` if structures changed).
- Move the export file off the server, and store the `tee` logs somewhere durable alongside the source profile from Phase 1.
- Remove the importer plugin/mu-plugin once the migration is genuinely finished.
- Leave the `_original_*` meta in place until the project closes out. When you are certain, export it first, then delete:

    ```bash
    wp db query "SELECT post_id, meta_value FROM wp_postmeta WHERE meta_key = '_original_import_data';" > import-data-backup.tsv
    wp post meta delete --all-posts _original_import_data   # verify the backup first
    ```

## If it goes wrong

Stop the script. Do not improvise fixes against a live half-migrated database at speed — that is how one bad run becomes two.

Then, with a human:

1. Read the log; find the last successfully imported item.
2. Decide: fix forward (the importer is idempotent, so a corrected re-run is often the cheapest path) or restore the backup.
3. If restoring, account for the user-generated data written since the backup — the list you made before starting.
4. Write down what happened before rehearsing again.
