---
name: wp-importing
description: "Use when building a custom, one-off migration that pulls content from an external source — a headless CMS export, legacy database, CSV/JSON dump, scraped archive, or another WordPress site — into WordPress. Covers profiling the source data, mapping it onto post types/taxonomies/meta/users, writing an idempotent resumable import script, WP_IMPORTING and side-effect suppression, dry runs, throttling, media handling, the production run, and post-import verification."
compatibility: "WordPress 5.9+ with WP-CLI 2.x to run the import (`wp eval-file` or a custom `WP_CLI_Command`). PHP 7.4+. Assumes a local or staging environment to rehearse on; production instructions cover both SSH and non-SSH hosts."
license: MIT
metadata:
    author: georgestephanis
    version: "1.0"
    written: "2026-08-25"
    written_against:
        wordpress: "7.1"
        wp-cli: "2.12.0"
---

# WordPress Custom Data Importing

## When to use

Use this skill when content has to be moved into WordPress and no off-the-shelf importer fits — which is most of the time for anything other than a WXR file. Typical triggers:

- A headless CMS export (Contentful, Sanity, Prismic, Strapi, Drupal, Craft) where the source structures are bespoke to that customer's space, so no generic mapping exists.
- A legacy application database, spreadsheet, CSV, or JSON dump that needs to become posts, taxonomy terms, users, or WooCommerce products.
- A partial or merge import: pulling a subset of one WordPress site into another, where post IDs will not survive the trip.
- A scraped or archived site being rebuilt in WordPress.

Related skills:

- WXR files from another WordPress site — use the WordPress Importer plugin (`wp import`) first, then this skill only for the cleanup and re-mapping work.
- Reading content out of a WordPress site over the REST API — use `content-pull`.
- Search-replace or serialised-data work after the import — use WP-CLI (`wp search-replace --dry-run` first).

Do NOT reach for a script reflexively. If the data set is small (tens of items), or the mapping needs human judgement per item, entering it by hand in wp-admin is often faster than the four hours it takes to write, debug, and rehearse an importer. Say so out loud before writing code.

## Inputs required

- **The source export**, on disk and readable. Note its format (JSON, CSV, XML, SQL dump, API endpoint) and total size.
- **A disposable target environment** — Local, Studio, Playground, or a staging site with a database you can freely destroy and restore. Never develop an importer against production.
- **A decision-maker** for the content model questions in Phase 2. Which source structures become post types vs. taxonomies vs. meta is a product decision, not an implementation detail.
- **Production access details** for the final run: SSH host/user/path, or the alternative trigger mechanism if the host has no shell.
- **A known-good database backup procedure** and, critically, a rehearsed restore procedure.

## Procedure

### Phase 1: Profile the source data

Do not read the export by eye and start writing `wp_insert_post()` calls. Write throwaway scripts that aggregate the data and tell you what is actually in it — real exports always contain shapes the documentation and the client did not mention.

Answer, in order:

1. **What top-level item types exist, and how many of each?** Count them and sort by frequency.
2. **For each type, what fields appear, and on how many items?** A field present on 523 of 523 items is structural; one on 172 of 523 is optional and your script must tolerate its absence.
3. **For each field, what shapes do the values take?** Strings, nested objects, arrays, links/references to other items, localised value maps, embedded HTML, Markdown, or serialised blobs.
4. **How are relationships expressed?** Reference IDs, slugs, embedded objects, or join tables — this determines whether you need a two-pass import.

See `references/profiling-source-data.md` for ready-to-adapt aggregation snippets (PHP and `jq`) covering type frequency, field inventory, value-shape sampling, and reference-graph extraction.

Record the output of this phase somewhere durable (a Markdown file in the repo). It is the specification the rest of the work is checked against.

### Phase 2: Design the content model mapping

For every source type and field, decide its WordPress home. The four candidate destinations, and the questions that choose between them:

| Destination      | Choose it when                                                                                                 |
| ---------------- | -------------------------------------------------------------------------------------------------------------- |
| Post type        | The item is a standalone piece of content with its own URL, title, and editorial lifecycle.                    |
| Taxonomy term    | The value is shared across many items and users will want an archive page of everything carrying it.           |
| Post meta        | The value is descriptive, belongs to exactly one item, and is only rendered on that item's own page.           |
| User (or author) | The item is a person who logs in or is credited. Multiple credited authors per post needs a co-authors plugin. |

The taxonomy-vs-meta question is the one most often gotten wrong. "Would someone want to browse all the recipes containing butternut squash?" means taxonomy. "Is this just the prep time we print under the title?" means meta.

Other decisions to settle here:

- **What becomes `post_content`?** If the source stores Markdown or a structured rich-text tree, converting it to blocks or HTML is its own sub-project — decide whether it happens in the importer or as a separate pass.
- **Slugs and permalinks.** If the site keeps its domain, matching old URLs avoids a redirect map. If it cannot, plan the redirects now.
- **Localisation.** Many headless CMSes key every field by locale. Decide whether the target is a single-language site (pick a locale, drop the rest) or multilingual (which plugin, and what it expects).
- **What is deliberately not imported.** Write it down so nobody re-litigates it mid-run.

See `references/content-model-mapping.md` for the full decision checklist, plus the `register_post_type` / `register_taxonomy` / `register_post_meta` declarations that must land in the theme or a plugin _before_ the import runs.

### Phase 3: Register the target structures

Custom post types, taxonomies, and registered meta must exist and be loaded when the import runs, or the data lands somewhere invisible. Add them to the site's plugin or theme, activate, and confirm:

```bash
wp post-type list --field=name
wp taxonomy list --field=name
```

For meta you want exposed to the REST API and the block editor, `register_post_meta()` with `show_in_rest => true` — unregistered meta still saves but will not round-trip through the editor.

### Phase 4: Write the import script

Build the safety rails before the mapping logic. In order of importance:

1. **`define( 'WP_IMPORTING', true );` as the first executable line.** This is the single most important line in the file. Core and most well-behaved plugins check it to suppress pingbacks, term-count recalculation, sync queues, and notification emails. If a plugin bails at load time rather than run time, define it in `wp-config.php` for the duration of the import instead.
2. **A `--dry-run` mode** that logs exactly what would be written and touches nothing.
3. **A `--limit` (and offset/resume) argument** so you can run 1, then 2, then 10, then all.
4. **Idempotency.** Before inserting, look up whether this source item already landed. Store `_original_import_origin`, `_original_post_id`, and `_original_import_url` meta on everything you create — the same keys the WordPress Importer Fixers WP-CLI tooling expects, so its media and gallery repair commands work afterwards.
5. **Progress output.** `Item 47 of 1,234 (3.8%)` on every iteration. Nothing is worse than a silent long-running process.
6. **Stash the raw source payload** for each item in meta (for example `_original_import_data`). Nearly every follow-up cleanup task becomes a simple loop over the database instead of a re-parse of the export, and you can drop the meta with one query when you are certain it is done.

Belt-and-braces on side effects — even with `WP_IMPORTING` defined, consider adding to the script:

```php
add_filter( 'pre_wp_mail', '__return_false' ); // Nothing emails anyone mid-import.
wp_defer_term_counting( true );
wp_defer_comment_counting( true );
```

…and turning both deferrals off at the end so the counts recalculate once.

See `references/import-script-skeleton.md` for a complete annotated skeleton — as a `WP_CLI_Command` subclass (preferred: real flag parsing, progress bars, `WP_CLI::confirm`) and as a plain `wp eval-file` script for hosts where a plugin file cannot be added.

### Phase 5: Rehearse — dry run, then 1, 2, 10, all

On the disposable environment, in this exact order:

1. `--dry-run` over the whole file. Read the log. Does the mapping look right?
2. Import **one** item. Open it in wp-admin. Check the content, the terms, the meta, the featured image, the author, the dates. Check that no email went out and no webhook fired.
3. Import two, then ten. Time the ten-item run — multiply out for a rough estimate of the full run, and take that number seriously if it is measured in hours.
4. Full run on the disposable environment. Then diff the resulting post counts against the Phase 1 aggregation output.
5. **Run the full import a second time on the same database.** Nothing should be duplicated. If anything is, idempotency is broken and the production run has no safety net.

Importing as `draft` rather than `publish` is worth considering for large or slow imports: it lets you spot-check before anything is public and publish everything at once. If you do, make sure `WP_IMPORTING` is defined during the bulk publish too, or the status transition fires every notification you just spent a phase suppressing.

### Phase 6: Tune for scale

Only once correctness is settled. The common levers:

- Free memory periodically — object caches grow unboundedly across thousands of `get_post()` calls.
- Defer term counting across the run, or flush in batches of a few hundred.
- `set_time_limit()` per iteration if the environment enforces one.
- Throttle against a live database: sleep briefly whenever the last sleep was more than a few seconds ago, so the import yields to real traffic.
- On WordPress VIP, use the platform's bulk-operation and in-memory-cleanup helpers.

See `references/performance-and-throttling.md` for the specific calls and a throttle implementation.

### Phase 7: Run it in production

Full runbook in `references/production-runbook.md`. The non-negotiable order:

1. Pick a low-traffic window.
2. Take a database backup **and confirm you know how to restore it** — figuring that out under pressure is miserable.
3. Note what user-generated data (orders, comments, registrations) would be lost by a restore, and plan how to capture it.
4. Dry run in production first.
5. One item. Check it. Two. Ten. Check again.
6. Full run inside `screen`/`tmux`, output piped through `tee` to a log file you keep.

```bash
wp eval-file ./import.php --dry-run | tee import-dryrun.log
```

### Phase 8: Verify and clean up

- Compare post/term/user counts against the Phase 1 aggregations.
- Spot-check a random sample across each type, including the awkward ones (missing optional fields, longest content, most relationships).
- Handle media: sideloaded during the import, or repaired afterwards with the WordPress Importer Fixers commands — see `references/media-handling.md`.
- Flush what needs flushing (`wp rewrite flush`, object cache, page cache) and no more; rebuilding caches has its own cost.
- Set up redirects if permalinks changed.
- Archive the import logs somewhere durable.
- Leave `_original_*` meta in place until the project is genuinely finished; export it before deleting.

## Verification

The import is done when all of the following are true:

```bash
# Counts match the source aggregation for every imported type.
wp post list --post_type=<type> --post_status=any --format=count

# Re-running the importer changes nothing (idempotency holds).
wp eval-file ./import.php --dry-run | grep -ci 'would create'   # expect 0

# No orphaned drafts or auto-drafts left behind.
wp post list --post_type=<type> --post_status=auto-draft --format=count  # expect 0

# Term counts are accurate after deferred counting was flushed.
wp term recount <taxonomy>

# No unexpected scheduled events were queued by the import.
wp cron event list
```

Plus, by eye: one imported item of each type opened in wp-admin and confirmed correct, and the front end rendering it.

## Failure modes

- **Emails fired at users.** `WP_IMPORTING` was not defined, or was defined too late for a plugin that checks at load. Caught by the single-item test — which is exactly why the single-item test exists.
- **Duplicates on the second run.** The existence check queries meta that the insert path does not actually write, or writes after a fatal. Verify by running the importer twice on a fresh database.
- **Import dies partway through a long run.** Memory exhaustion or a timeout. Add resume-from support and cleanup calls (Phase 6) rather than restarting from zero.
- **Featured images and galleries point at the wrong attachments.** Source IDs were carried across instead of re-mapped to new attachment IDs. This is what the `_original_post_id` / `_original_thumbnail_id` meta and the importer-fixers commands are for.
- **Media downloaded repeatedly.** Sideloading without recording `_original_import_url` on the attachment, so each run re-fetches every image.
- **Terms created with wrong parents or duplicated with numeric slug suffixes.** Two-pass the hierarchy: create all terms first, then assign relationships.
- **Silent field loss.** A field present on only some items was never noticed in Phase 1, so the mapping ignores it. This is why the field-frequency inventory is mandatory, not optional.
- **Serialised or JSON meta mangled.** Pass arrays to `update_post_meta()` directly — WordPress serialises them — rather than pre-serialising and double-encoding.

## Escalation

Stop and ask a human when:

- The content-model mapping (Phase 2) is ambiguous in a way that changes the site's information architecture — taxonomy vs. meta, one post type vs. three.
- The source data contains personal data, credentials, or payment records whose handling has not been agreed.
- The estimated full-run time (Phase 5) is long enough to need a maintenance window, or the target is a live e-commerce site where a restore would lose orders.
- Idempotency cannot be established because the source has no stable identifier per item.
- The import would overwrite content that has been edited in WordPress since a previous run — that is a merge-conflict policy decision, not a code decision.
- Anything in production goes wrong. Stop the script, do not improvise a fix against the live database, and decide with a human whether to restore the backup.
