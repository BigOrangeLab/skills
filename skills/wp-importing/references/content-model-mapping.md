# Mapping source structures onto WordPress

## The four destinations

For each source type and each field on it, choose one:

### Post type

The item is standalone content: it has a title, a body, an author, a publish date, and its own URL. Built-in `post` and `page` first; a custom post type when it needs distinct fields, archives, or capabilities.

```php
add_action(
	'init',
	function () {
		register_post_type(
			'meal',
			array(
				'labels'       => array(
					'name'          => __( 'Meals', 'textdomain' ),
					'singular_name' => __( 'Meal', 'textdomain' ),
				),
				'public'       => true,
				'has_archive'  => true,
				'show_in_rest' => true, // Required for the block editor.
				'supports'     => array( 'title', 'editor', 'excerpt', 'thumbnail', 'author', 'custom-fields' ),
				'rewrite'      => array( 'slug' => 'meals' ),
			)
		);
	}
);
```

### Taxonomy

The value is shared across many items and users benefit from an archive of everything carrying it. Ingredients, categories, regions, product lines, difficulty levels.

```php
register_taxonomy(
	'ingredient',
	array( 'meal' ),
	array(
		'labels'            => array( 'name' => __( 'Ingredients', 'textdomain' ) ),
		'public'            => true,
		'hierarchical'      => false, // true behaves like categories, false like tags.
		'show_in_rest'      => true,
		'show_admin_column' => true,
	)
);
```

### Post meta

The value describes exactly one item and is rendered only on that item's page. Prep time, ISBN, external ID, source URL.

```php
register_post_meta(
	'meal',
	'prep_minutes',
	array(
		'type'         => 'integer',
		'description'  => __( 'Preparation time in minutes.', 'textdomain' ),
		'single'       => true,
		'default'      => null,
		'show_in_rest' => true,
		'auth_callback' => function () {
			return current_user_can( 'edit_posts' );
		},
	)
);
```

Meta keys starting with `_` are hidden from the custom-fields UI — right for import bookkeeping (`_original_post_id`), wrong for editorial fields someone needs to edit.

### User

The item is a person. If each post has exactly one credit and those people never log in, post meta or a guest-author plugin is lighter than creating real user accounts. If any item has two or more credited authors, WordPress core cannot represent that — pick a co-authors plugin before writing the importer, because it changes where the data goes.

## The questions to settle before writing code

**Taxonomy or meta?** "Will anyone want a page listing everything with this value?" Yes → taxonomy. No → meta. Getting this wrong is the most expensive mistake in the mapping, because converting later means a second migration.

**What becomes `post_content`?**

- Source stores HTML → sanitise and use it; consider whether it should become blocks.
- Source stores Markdown → convert during import (a Markdown library, or `wp_kses_post()` over parsed output). Do not store raw Markdown in `post_content` and hope a plugin renders it.
- Source stores a structured rich-text tree (Contentful Rich Text, Portable Text, ProseMirror JSON) → this is its own converter, with its own tests. Budget for it separately.
- Source has no body at all (pure structured records) → `post_content` may legitimately stay empty, with a template rendering the meta.

**Slugs and permalinks.** Preserve source slugs where they exist; it is free and it avoids a redirect map. Where the URL structure changes, capture old → new pairs during the import into a log or option so redirects can be generated afterwards.

**Dates.** Set both `post_date` and `post_date_gmt`, and set `edit_date => true` when updating existing posts or WordPress may quietly ignore the date change.

**Localisation.** If every field is locale-keyed, decide: single-locale target (pick one, discard the rest, record that decision), or multilingual (choose the plugin first — WPML, Polylang, and multisite each want the data in a completely different shape).

**Status.** `publish` immediately, or `draft` for review and a bulk publish later? Drafts are safer for large imports and give you a spot-check window.

**What is not being imported.** Write the exclusions down. Unimported fields, deprecated types, spam comments, test entries.

## Register before you import

All of the above must be registered and loaded when the import runs. Confirm:

```bash
wp post-type list --field=name
wp taxonomy list --field=name
wp eval 'var_dump( array_keys( get_registered_meta_keys( "post", "meal" ) ) );'
```

Unregistered meta still writes to the database, but it will not appear in REST responses or round-trip through the block editor — a failure that surfaces weeks later.
