# Media handling

Media is where imports most often go wrong, because attachment IDs never survive a migration and content references them by both ID and URL.

## Decide the strategy up front

**Option A — sideload during the import.** Each item downloads its images as it is created, and the post is written with local URLs. Simplest to reason about, slowest to run, and the run becomes network-bound.

**Option B — import content first, fix media afterwards.** Posts land with the source site's URLs still in them; a second pass sideloads and rewrites. Faster to iterate on, and the rewrite pass is re-runnable in isolation.

Option B is usually the better default, especially when the source site is still online, because the [WordPress Importer Fixers](https://github.com/a8cteam51/wordpress-importer-fixers) WP-CLI plugin already implements the fix-up pass.

## Sideloading during the import (Option A)

```php
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

/**
 * Sideload a remote file once, returning the attachment ID (or WP_Error).
 */
function acme_sideload_once( $url, $post_id, $origin, $source_asset_id ) {
	// Dedupe: has this exact URL already been imported?
	$existing = get_posts(
		array(
			'post_type'      => 'attachment',
			'post_status'    => 'any',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'meta_query'     => array(
				array(
					'key'   => '_original_import_url',
					'value' => $url,
				),
			),
		)
	);

	if ( $existing ) {
		return (int) $existing[0];
	}

	$attachment_id = media_sideload_image( $url, $post_id, null, 'id' );

	if ( is_wp_error( $attachment_id ) ) {
		return $attachment_id;
	}

	// These three keys are what makes later re-mapping possible.
	update_post_meta( $attachment_id, '_original_import_origin', $origin );
	update_post_meta( $attachment_id, '_original_import_url', $url );
	update_post_meta( $attachment_id, '_original_post_id', $source_asset_id );

	return (int) $attachment_id;
}
```

The dedupe check is not optional. Without it, every re-run re-downloads every image and the media library fills with duplicates.

Featured images:

```php
set_post_thumbnail( $post_id, $attachment_id );
// And record the source's own ID so a later pass can verify the mapping:
update_post_meta( $post_id, '_original_thumbnail_id', $source_thumbnail_id );
```

## Fixing media afterwards (Option B)

Install [`a8cteam51/wordpress-importer-fixers`](https://github.com/a8cteam51/wordpress-importer-fixers) as a plugin on the target site. It registers `wp import-fixer` subcommands and has no admin UI — it exits outside WP-CLI.

```bash
# List the external image domains still referenced in post content.
wp import-fixer import-external-images --list

# Sideload images from one domain and rewrite the URLs in content.
wp import-fixer import-external-images --domain=old-site.example --protocol=https

# Or every external domain found.
wp import-fixer import-external-images --all-domains --post_type=any

# Undo: reverses replacements and deletes attachments this command created.
wp import-fixer import-external-images --rewind
```

Once attachments exist, re-map the references that carry stale IDs:

```bash
# Featured images: re-point _original_thumbnail_id at the new attachment IDs.
wp import-fixer fix-thumbnails-contextually --origin=acme-2026-06

# [gallery ids="…"] shortcodes: rewrite to the new attachment IDs
# (the original shortcode is preserved in _old_gallery_N meta).
wp import-fixer fix-galleries-contextually --origin=acme-2026-06

# Source-site media URLs in content: swap for the local attachment URLs,
# including WordPress -123x456 dimension variants.
wp import-fixer fix-media-urls --origin=acme-2026-06
```

These commands read exactly the meta the skeleton writes:

| Meta key                  | Written on             | Used by                                       |
| ------------------------- | ---------------------- | --------------------------------------------- |
| `_original_import_origin` | posts and attachments  | all commands, to scope a run to one migration |
| `_original_post_id`       | posts and attachments  | thumbnail and gallery re-mapping              |
| `_original_thumbnail_id`  | posts with a thumbnail | `fix-thumbnails-contextually`                 |
| `_original_import_url`    | attachments            | `fix-media-urls`, and sideload dedupe         |

Write them during the import even if you are not sure you will need the fixers. Adding them afterwards means re-parsing the export.

## Gotchas

- **`media_sideload_image()` needs the three admin includes** above; without them it fatals with an undefined-function error.
- **Large media over a slow link** will dominate the run. Consider pulling the uploads directory with `rsync` and re-registering attachments instead of downloading via HTTP.
- **The source URLs must still resolve.** If the old site is being torn down, mirror the uploads directory before it goes away.
- **Hotlinked and CDN-transformed URLs** (`/f_auto/q_auto/` style transforms) may return a different format than the extension suggests. Check what actually landed.
- **SVGs and PDFs** may be blocked by upload filters; decide policy rather than silently losing them.
- **Alt text, captions, and credits** live in different places (`_wp_attachment_image_alt` meta, `post_excerpt`, `post_content` of the attachment). Map them explicitly — they are easy to drop.
- **Re-running after a partial media pass** is safe only with the dedupe check in place.
