# Performance, throttling, and long-running imports

Tune only after correctness is settled. A fast importer that inserts the wrong data is worse than a slow one.

## Measure first

Time the ten-item rehearsal run and extrapolate:

```bash
time wp acme-import run ./export.json --limit=10
```

If ten items take 12 seconds, 5,000 items is roughly 100 minutes — which is long enough to need `screen`, a maintenance window, and resume support. If ten items take 0.4 seconds, skip most of this document.

## Memory

Long loops exhaust memory because object caches grow with every `get_post()`, `get_term()`, and `get_user_by()` call.

```php
// Every 100 iterations:
if ( function_exists( 'vip_inmemory_cleanup' ) ) {
	vip_inmemory_cleanup();          // WordPress VIP
} elseif ( function_exists( 'wp_cache_flush_runtime' ) ) {
	wp_cache_flush_runtime();        // WP 6.0+, clears the in-process cache only
}
```

Also worth doing in the loop body:

- Do not accumulate results in arrays you never read. Log and discard.
- Prefer `'fields' => 'ids'` on `get_posts()`/`WP_Query` lookups.
- Pass `'no_found_rows' => true` and `'update_post_term_cache' => false` where you do not need them.
- `unset()` large parsed payloads once written.

For very large JSON exports that will not fit in memory at all, stream them (`JsonMachine`, `ext-json` incremental parsers) or split the file first with `jq`:

```bash
jq -c '.entries[]' export.json | split -l 500 - chunk-
```

## Term counting and comment counting

Recalculating term counts on every `wp_set_object_terms()` call is one of the most expensive things a naive importer does.

```php
wp_defer_term_counting( true );
wp_defer_comment_counting( true );

// …the whole import loop…

wp_defer_term_counting( false );   // flushes and recalculates once
wp_defer_comment_counting( false );
```

For runs long enough that you want intermediate accuracy, toggle it off and on again every few hundred items instead of only at the end.

On WordPress VIP, the platform provides `start_bulk_operation()` / `end_bulk_operation()`, which wrap deferral together with suspending sync and other platform side effects — prefer those there.

## Cache invalidation

`wp_suspend_cache_invalidation( true )` gives a further speedup, but it is sharp-edged: while suspended, code that reads back what it just wrote can see stale data. Only use it when the loop does not re-read its own writes, and always:

```php
wp_suspend_cache_invalidation( false );
wp_cache_flush();
```

before verification.

## Timeouts

```php
set_time_limit( 30 ); // Called at the top of each iteration, resets the clock.
```

WP-CLI usually runs without a time limit, but hosts, containers, and `php-fpm`-triggered runs may impose one. SSH sessions dropping is a separate problem — solved with `screen`/`tmux`, not `set_time_limit()`.

## Throttling against a live database

An import running flat out will happily starve real visitors. Yield periodically:

```php
private $last_sleep = 0;

private function maybe_throttle() {
	if ( time() - $this->last_sleep >= 5 ) {
		sleep( 3 );          // or usleep( 250000 ) for a gentler tick
		$this->last_sleep = time();
	}
}
```

Tune the ratio to the host. On a quiet local environment, remove it entirely — it triples the wall-clock time for no benefit.

Watch the database while a production run is in flight (`wp db query "SHOW FULL PROCESSLIST;"`, or the host's monitoring). If replication lag or slow queries start climbing, stop the run — resume support is what makes that a safe decision.

## Resuming

Two mechanisms, both cheap:

- **Offset** — `--offset=N` combined with a stable source ordering. Simple, but breaks if the export is re-generated.
- **Skip-if-present** — the idempotency check already skips anything already imported, so re-running the whole file resumes naturally. Slower (it queries per item) but correct even if the export changed.

Prefer skip-if-present as the correctness guarantee and offset as the speed optimisation.

## After the run

Flush deliberately, not reflexively — rebuilding caches costs the site real performance:

```bash
wp term recount <taxonomy>    # if term counting was deferred and interrupted
wp rewrite flush              # only if post types, taxonomies, or slugs changed
wp cache flush                # object cache; skip on a busy site unless needed
```

Page/CDN caches: purge only the URLs the import affected where the host supports targeted purges.
