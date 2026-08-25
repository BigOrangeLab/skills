# Import script skeleton

Two shapes, same logic. Prefer the WP-CLI command: real flag parsing, progress output, and `WP_CLI::confirm()` for free.

## Shape A — a WP-CLI command (preferred)

Drop this in `wp-content/mu-plugins/` (or a normal plugin) on the target site. It registers nothing in wp-admin and exits outside WP-CLI.

```php
<?php
/**
 * Plugin Name: Acme Content Importer
 * Description: One-off migration command. Remove after the migration completes.
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

class Acme_Importer_Command {

	/**
	 * Identifies this migration in post meta. Change per migration, never reuse.
	 */
	const ORIGIN = 'acme-2026-06';

	/**
	 * Cache of term name => term_id, to avoid re-querying terms per item.
	 *
	 * @var array
	 */
	private $term_cache = array();

	private $dry_run = false;

	private $last_sleep = 0;

	/**
	 * Import entries from the source export.
	 *
	 * ## OPTIONS
	 *
	 * <file>
	 * : Path to the source export file.
	 *
	 * [--type=<type>]
	 * : Only import entries of this source type.
	 *
	 * [--limit=<number>]
	 * : Stop after this many entries. Use 1, then 2, then 10, before the full run.
	 *
	 * [--offset=<number>]
	 * : Skip this many entries first. Use to resume an interrupted run.
	 *
	 * [--status=<status>]
	 * : Post status for created posts. Default: draft.
	 *
	 * [--dry-run]
	 * : Log what would happen without writing anything.
	 *
	 * ## EXAMPLES
	 *
	 *     wp acme-import run ./export.json --dry-run
	 *     wp acme-import run ./export.json --limit=1
	 *     wp acme-import run ./export.json | tee import.log
	 */
	public function run( $args, $assoc_args ) {
		// The single most important line in this file. Suppresses pingbacks,
		// term-count recalculation, sync queues, and notification emails in
		// core and in any plugin that checks it.
		if ( ! defined( 'WP_IMPORTING' ) ) {
			define( 'WP_IMPORTING', true );
		}

		// Belt and braces: nothing emails anyone during a migration.
		add_filter( 'pre_wp_mail', '__return_false' );

		wp_defer_term_counting( true );
		wp_defer_comment_counting( true );

		$file          = $args[0];
		$this->dry_run = (bool) \WP_CLI\Utils\get_flag_value( $assoc_args, 'dry-run', false );
		$limit         = (int) \WP_CLI\Utils\get_flag_value( $assoc_args, 'limit', 0 );
		$offset        = (int) \WP_CLI\Utils\get_flag_value( $assoc_args, 'offset', 0 );
		$status        = \WP_CLI\Utils\get_flag_value( $assoc_args, 'status', 'draft' );
		$type          = \WP_CLI\Utils\get_flag_value( $assoc_args, 'type', null );

		if ( ! is_readable( $file ) ) {
			WP_CLI::error( "Cannot read {$file}" );
		}

		$json    = json_decode( file_get_contents( $file ) );
		$entries = $json->entries;

		if ( $type ) {
			$entries = array_values(
				array_filter(
					$entries,
					function ( $entry ) use ( $type ) {
						return $type === $entry->sys->contentType->sys->id;
					}
				)
			);
		}

		$entries = array_slice( $entries, $offset, $limit > 0 ? $limit : null );
		$total   = count( $entries );

		WP_CLI::log(
			sprintf(
				'%s Preparing to import %s entries%s.',
				$this->dry_run ? '🟡 DRY RUN —' : '🟢',
				number_format( $total ),
				$offset ? " (starting at offset {$offset})" : ''
			)
		);

		$created = 0;
		$updated = 0;
		$skipped = 0;

		foreach ( $entries as $i => $entry ) {
			WP_CLI::log(
				sprintf(
					'[%d/%d · %.1f%%] %s',
					$i + 1,
					$total,
					( ( $i + 1 ) / max( $total, 1 ) ) * 100,
					$entry->sys->id
				)
			);

			$result = $this->import_entry( $entry, $status );

			if ( 'created' === $result ) {
				++$created;
			} elseif ( 'updated' === $result ) {
				++$updated;
			} else {
				++$skipped;
			}

			$this->maybe_throttle();
			$this->maybe_free_memory( $i );
		}

		wp_defer_term_counting( false );
		wp_defer_comment_counting( false );

		WP_CLI::success(
			sprintf( 'Created %d · updated %d · skipped %d.', $created, $updated, $skipped )
		);
	}

	/**
	 * Import one source entry. Returns 'created', 'updated', or 'skipped'.
	 */
	private function import_entry( $entry, $status ) {
		$source_id = $entry->sys->id;
		$fields    = $entry->fields;

		$existing = $this->find_existing( $source_id );

		$postarr = array(
			'post_type'     => 'meal',
			'post_status'   => $status,
			'post_title'    => $this->localised( $fields, 'title', '' ),
			'post_name'     => sanitize_title( $this->localised( $fields, 'slug', '' ) ),
			'post_content'  => $this->to_post_content( $this->localised( $fields, 'body', '' ) ),
			'post_excerpt'  => $this->localised( $fields, 'summary', '' ),
			'post_date'     => $this->to_site_time( $this->localised( $fields, 'publishDate', null ) ),
			'post_date_gmt' => $this->to_gmt( $this->localised( $fields, 'publishDate', null ) ),
			'meta_input'    => array(
				'_original_import_origin' => self::ORIGIN,
				'_original_post_id'       => $source_id,
				// Keep the raw payload: nearly every follow-up fix becomes a
				// database loop instead of a re-parse of the export.
				'_original_import_data'   => wp_json_encode( $entry ),
				'prep_minutes'            => (int) $this->localised( $fields, 'prepMinutes', 0 ),
			),
		);

		if ( $existing ) {
			$postarr['ID']        = $existing;
			$postarr['edit_date'] = true; // Or WordPress may ignore the date change.
		}

		if ( $this->dry_run ) {
			WP_CLI::log(
				sprintf(
					'    %s "%s"',
					$existing ? 'Would update' : 'Would create',
					$postarr['post_title']
				)
			);
			return $existing ? 'updated' : 'created';
		}

		$post_id = wp_insert_post( $postarr, true );

		if ( is_wp_error( $post_id ) ) {
			WP_CLI::warning( "    ‼️ {$source_id}: " . $post_id->get_error_message() );
			return 'skipped';
		}

		$this->assign_terms( $post_id, $fields );

		WP_CLI::log( sprintf( '    ✅ #%d %s', $post_id, $existing ? '(updated)' : '(created)' ) );

		return $existing ? 'updated' : 'created';
	}

	/**
	 * Idempotency: has this source entry already landed here?
	 */
	private function find_existing( $source_id ) {
		$found = get_posts(
			array(
				'post_type'      => 'any',
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
				'meta_query'     => array(
					'relation' => 'AND',
					array(
						'key'   => '_original_import_origin',
						'value' => self::ORIGIN,
					),
					array(
						'key'   => '_original_post_id',
						'value' => $source_id,
					),
				),
			)
		);

		return $found ? (int) $found[0] : 0;
	}

	/**
	 * Resolve terms once and cache; term lookups dominate runtime otherwise.
	 */
	private function assign_terms( $post_id, $fields ) {
		$source_terms = $this->localised( $fields, 'ingredients', array() );
		$term_ids     = array();

		foreach ( (array) $source_terms as $source_term ) {
			$name = is_object( $source_term ) ? $source_term->name : (string) $source_term;

			if ( ! isset( $this->term_cache[ $name ] ) ) {
				$term = term_exists( $name, 'ingredient' );

				if ( ! $term ) {
					$term = wp_insert_term( $name, 'ingredient' );
				}

				if ( is_wp_error( $term ) ) {
					WP_CLI::warning( "    ⁉️ term '{$name}': " . $term->get_error_message() );
					continue;
				}

				$this->term_cache[ $name ] = (int) $term['term_id'];
			}

			$term_ids[] = $this->term_cache[ $name ];
		}

		if ( $term_ids ) {
			wp_set_object_terms( $post_id, $term_ids, 'ingredient', false );
		}
	}

	/**
	 * Unwrap a locale-keyed field. Adjust or delete for non-localised sources.
	 */
	private function localised( $fields, $key, $default = null, $locale = 'en-US' ) {
		if ( ! isset( $fields->$key ) ) {
			return $default;
		}

		$value = $fields->$key;

		if ( is_object( $value ) && isset( $value->$locale ) ) {
			return $value->$locale;
		}

		return $value;
	}

	private function to_post_content( $raw ) {
		// Markdown -> HTML, rich-text tree -> blocks, or straight passthrough.
		return wp_kses_post( (string) $raw );
	}

	private function to_gmt( $date ) {
		return $date ? gmdate( 'Y-m-d H:i:s', strtotime( $date ) ) : '';
	}

	private function to_site_time( $date ) {
		return $date ? get_date_from_gmt( $this->to_gmt( $date ) ) : '';
	}

	/**
	 * Yield to real traffic: sleep 3s whenever 5s have passed since the last sleep.
	 * Drop this entirely when importing into a quiet local environment.
	 */
	private function maybe_throttle() {
		if ( time() - $this->last_sleep >= 5 ) {
			sleep( 3 );
			$this->last_sleep = time();
		}
	}

	private function maybe_free_memory( $i ) {
		if ( 0 !== $i % 100 ) {
			return;
		}

		if ( function_exists( 'vip_inmemory_cleanup' ) ) {
			vip_inmemory_cleanup();
		} elseif ( function_exists( 'wp_cache_flush_runtime' ) ) {
			wp_cache_flush_runtime();
		}
	}
}

WP_CLI::add_command( 'acme-import', 'Acme_Importer_Command' );
```

Run it:

```bash
wp acme-import run ./export.json --dry-run | tee dryrun.log
wp acme-import run ./export.json --limit=1
wp acme-import run ./export.json --limit=10 --status=draft
wp acme-import run ./export.json | tee import.log
```

## Shape B — `wp eval-file`

For hosts where you cannot add a plugin file, or for something genuinely throwaway. Extra arguments after the filename arrive in `$args`.

```php
<?php
// import.php — run with: wp eval-file ./import.php --dry-run --limit=10

if ( ! defined( 'WP_IMPORTING' ) ) {
	define( 'WP_IMPORTING', true );
}

add_filter( 'pre_wp_mail', '__return_false' );

$dry_run = in_array( '--dry-run', (array) $args, true );
$limit   = 0;

foreach ( (array) $args as $arg ) {
	if ( 0 === strpos( $arg, '--limit=' ) ) {
		$limit = (int) substr( $arg, strlen( '--limit=' ) );
	}
}

// …same logic as above, with printf() in place of WP_CLI::log().
```

With no shell at all, the last resort is an authenticated, capability-checked admin endpoint that runs in batches — but treat needing that as a reason to go find shell access first.

## When `WP_IMPORTING` is not enough

`WP_IMPORTING` only helps for code that checks it at run time. A plugin that registers its side-effecting hooks (or opens a sync connection) at load time will not notice a constant defined later inside a command. If the single-item test shows something still firing:

1. Define `WP_IMPORTING` in `wp-config.php` for the duration of the import, then remove it.
2. Or deactivate the offending integration for the run (`wp plugin deactivate <slug>`) and reactivate afterwards — but only where deactivation does not itself lose queued state.

Usual suspects: search indexers, CRM/marketing sync, activity feeds, cache warmers, social auto-posting, e-commerce lookup-table rebuilders.

## Non-negotiables checklist

- [ ] `WP_IMPORTING` defined before anything else runs.
- [ ] `--dry-run` implemented and actually exercised.
- [ ] `--limit` / `--offset` for the 1 → 2 → 10 → all rehearsal, and for resuming.
- [ ] Existence check before every insert, keyed on a stable source identifier.
- [ ] `_original_import_origin` + `_original_post_id` written on everything created, attachments included, so the importer-fixers commands can re-map afterwards.
- [ ] Raw source payload stashed in meta.
- [ ] Per-item progress output with counts and percentage.
- [ ] Errors warn and continue rather than fataling the whole run — unless the error means the data itself is wrong, in which case stop loudly.
- [ ] Deferred term/comment counting turned back on at the end.

## Two-pass imports

When items reference each other, do not resolve references inline — the target may not exist yet. Walk the source data twice:

1. **Pass one** creates every post, term, and user, recording `_original_post_id` on each.
2. **Pass two** walks the same data, looks up both sides by `_original_post_id`, and writes the relationships: post parents, related-post meta, featured images, term hierarchy.

The same structure handles hierarchical taxonomies — create all terms flat, then set `parent` in the second pass.
