# Legacy PHP Admin Patterns

These conventions remain canonical for PHP-rendered admin screens. New plugin screens should follow them so they feel native to wp-admin. They will remain valid until DataViews/React replaces the individual surface.

---

## Page wrapper

```html
<div class="wrap">
	<h1 class="wp-heading-inline">Screen Title</h1>
	<a href="#" class="page-title-action">Add New</a>
	<hr class="wp-header-end" />
	<!-- WordPress injects admin notices immediately after .wp-header-end -->
</div>
```

`<hr class="wp-header-end">` is the marker WordPress uses to inject admin notices in a stable, predictable position. It must be present for notices not to appear at the very top of the page.

## Notices

```html
<div class="notice notice-success is-dismissible"><p>Saved.</p></div>
<div class="notice notice-error"><p>Something went wrong.</p></div>
<div class="notice notice-warning is-dismissible"><p>Warning.</p></div>
<div class="notice notice-info"><p>Note.</p></div>
```

Add `.inline` for notices rendered inline (not top-of-page). Programmatically add notices via `add_settings_error()` / `settings_errors()` when using the Settings API, rather than echoing HTML directly.

## Settings form

```html
<form method="post" action="options.php">
	<?php settings_fields( 'my_options_group' ); ?> <?php do_settings_sections(
	'my_page_slug' ); ?>
	<table class="form-table" role="presentation">
		<tr>
			<th scope="row"><label for="my_field">Field Label</label></th>
			<td>
				<input
					id="my_field"
					name="my_field"
					type="text"
					class="regular-text"
					value="<?php echo esc_attr( $value ); ?>"
				/>
				<p class="description">Help text for this field.</p>
			</td>
		</tr>
	</table>
	<?php submit_button(); ?>
</form>
```

## Input width classes

| Class           | Width     | Use                          |
| --------------- | --------- | ---------------------------- |
| `.regular-text` | 25em      | Standard single-line input   |
| `.small-text`   | 5em       | Short numeric or code fields |
| `.large-text`   | 100%      | Textarea                     |
| `.code`         | monospace | Code input                   |

## Button classes

| Class               | Appearance         | Use                                        |
| ------------------- | ------------------ | ------------------------------------------ |
| `.button`           | Secondary (base)   | Default action                             |
| `.button-primary`   | Accent color       | Primary submit/save                        |
| `.button-secondary` | Explicit secondary | Alternate action (same as `.button` alone) |
| `.button-link`      | Inline text link   | Destructive or low-emphasis                |
| `.button-large`     | Larger             | Prominent CTAs                             |
| `.button-small`     | Smaller            | Dense toolbars                             |

`.button` wires up admin color scheme integration automatically — do not override its accent color with hardcoded values.

## Containers

| Class   | Use                                                      |
| ------- | -------------------------------------------------------- |
| `.wrap` | Outer page container (required)                          |
| `.card` | Modern surface/panel (white background, padding, radius) |

## Accessibility helpers

| Class                 | Behavior                                        |
| --------------------- | ----------------------------------------------- |
| `.screen-reader-text` | Visually hidden but available to screen readers |
| `.hidden`             | `display: none`                                 |

Never apply `aria-hidden` to focusable elements.

## Body state classes

Applied to `<body>` by WordPress core. Useful for scoping styles that respond to chrome state:

| Class                 | Condition                                   |
| --------------------- | ------------------------------------------- |
| `.folded`             | Admin menu manually collapsed               |
| `.auto-fold`          | Admin menu auto-collapsed (narrow viewport) |
| `.wp-responsive-open` | Mobile menu open                            |
| `.no-js` / `.js`      | JavaScript availability                     |

## Admin bar offset

`#wpadminbar` is **32px** tall on desktop and **46px** on mobile (viewport ≤ 782px). Sticky or fixed admin UI must account for both — use the existing core CSS rather than hardcoding the value.
