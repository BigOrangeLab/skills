# Profiling the source data

The goal of this phase is to replace assumptions with counts. Every aggregation below is throwaway code — write it, read the output, keep the output, delete the script.

## 1. What item types exist, and how many of each?

For a JSON export with a top-level collection where each entry declares its type:

```php
<?php
$json    = json_decode( file_get_contents( __DIR__ . '/export.json' ) );
$entries = $json->entries;

$types = array_map(
	function ( $entry ) {
		// Adjust this path to wherever the source stores its type identifier.
		return $entry->sys->contentType->sys->id;
	},
	$entries
);

$frequency = array_count_values( $types );
arsort( $frequency );
print_r( $frequency );
```

```text
$ php ./summarize.php
Array
(
    [blog]        => 523
    [meal]        => 478
    [ingredient]  => 382
    [productPage] => 28
    [author]      => 12
)
```

The `jq` equivalent, for large files where you would rather not load the whole thing in PHP:

```bash
jq -r '.entries[].sys.contentType.sys.id' export.json | sort | uniq -c | sort -rn
```

For CSV sources, the "type" is usually the file itself; count rows instead:

```bash
for f in *.csv; do printf '%-30s %s\n' "$f" "$(( $(wc -l < "$f") - 1 ))"; done
```

For a legacy SQL dump, load it into a scratch database and count per table:

```sql
SELECT TABLE_NAME, TABLE_ROWS FROM information_schema.TABLES
WHERE TABLE_SCHEMA = 'legacy' ORDER BY TABLE_ROWS DESC;
```

## 2. What fields does each type carry, and how often?

```php
<?php
$json    = json_decode( file_get_contents( __DIR__ . '/export.json' ) );

// Filter to a single type.
$items = array_filter(
	$json->entries,
	function ( $entry ) {
		return 'blog' === $entry->sys->contentType->sys->id;
	}
);

$all_fields = array_column( $items, 'fields' );          // one object per item
$all_fields = array_map( 'get_object_vars', $all_fields ); // objects -> arrays
$keys_only  = array_map( 'array_keys', $all_fields );      // arrays of key names
$unified    = array_merge( ...array_values( $keys_only ) );

$frequency = array_count_values( $unified );
arsort( $frequency );
print_r( $frequency );
```

```text
$ php ./list-blog-fields.php
Array
(
    [blogTitle]       => 523
    [slug]            => 523
    [categories]      => 523
    [blogHeroImage]   => 523
    [author]          => 522
    [publishDate]     => 489
    [blogBody]        => 476
    [similarArticles] => 208
    [furtherReading]  => 172
)
```

Read this table carefully. Anything below the item count is optional, and the script must handle its absence without notices or fatals. `author => 522` out of 523 means exactly one post has no author — find it now, not at 3am during the production run.

`jq` equivalent:

```bash
jq -r '.entries[] | select(.sys.contentType.sys.id=="blog") | .fields | keys[]' export.json \
  | sort | uniq -c | sort -rn
```

## 3. What shape do the values take?

Print a few real values per field rather than guessing from the name:

```bash
jq '[.entries[] | select(.sys.contentType.sys.id=="blog") | .fields.blogHeroImage] | .[0:3]' export.json
```

Watch for these shapes, each of which changes the mapping:

- **Locale-keyed maps** — `{"blogBody": {"en-US": "…", "fr": "…"}}`. Every field access needs a locale unwrap helper. Decide the target site's locale policy in Phase 2.
- **Link/reference objects** — `{"sys": {"type": "Link", "linkType": "Entry", "id": "…"}}`. These are relationships; resolve them in a second pass after all targets exist.
- **Asset objects** with a `url` plus dimensions/format metadata — media to sideload or repair later.
- **Markdown** in what looks like an HTML field, or vice versa. Convert deliberately.
- **Base64 blobs**, embedded JSON strings, and other doubly-encoded values.
- **Date strings with offsets** (`2021-07-28T00:00-08:00`). WordPress wants site-local `post_date` and UTC `post_date_gmt`; get the conversion right once, in a helper.

## 4. Map the reference graph

List which types point at which, and in which direction:

```bash
jq -r '.entries[] as $e | $e.fields | to_entries[]
       | select((.value|type)=="object" and (.value.sys.linkType? == "Entry"))
       | "\($e.sys.contentType.sys.id) -> \(.key)"' export.json | sort | uniq -c | sort -rn
```

Whatever the source format, the output you want is a list like:

```text
blog -> author        (522)
blog -> categories    (523, arrays of 1-4)
meal -> ingredient    (478, arrays of 3-19)
```

This tells you the import order: types that are only pointed _at_ (authors, ingredients, categories) import first; types that point _out_ (blogs, meals) import second and resolve their references against what already landed.

## 5. Write it down

Save the aggregation output into the repository — `docs/import-source-profile.md` or similar. Phase 8 verification compares final WordPress counts against these numbers, and future-you will want to know why the mapping decisions were made.
