# WordPress 7.2 — New APIs Worth Adopting

Adopt only where the API deletes or hardens code the repo already has. Everything below is **In Beta 1** and checked against the Beta 1 source; signatures can still change before RC1 (17 November 2026), so guard new calls with `function_exists()` in code that also runs on 7.1.

---

## Secrets API

[#66187](https://core.trac.wordpress.org/ticket/66187), [r64337](https://core.trac.wordpress.org/changeset/64337). [Proposal](https://make.wordpress.org/core/2026/08/25/proposal-a-secrets-api-for-wordpress-7-2/).

Encrypted storage for API keys and tokens that plugins currently keep in plaintext options. Values are encrypted with libsodium and stored in the options table by default; the key comes from a `WP_SECRETS_KEY` constant in `wp-config.php`, or is derived from `LOGGED_IN_KEY` + `LOGGED_IN_SALT` when that is not defined. Hosts can swap storage or key source with a `wp-content/secrets.php` drop-in.

| Function                                                                      | Returns                          |
| ----------------------------------------------------------------------------- | -------------------------------- |
| `wp_set_secret( string $name, string $value )`                                | `true\|WP_Error`                 |
| `wp_get_secret( string $name, string $version = WP_Secret_Version::CURRENT )` | `WP_Secret\|null\|WP_Error`      |
| `wp_delete_secret( string $name )`                                            | `true\|WP_Error`                 |
| `wp_list_secrets( string $namespace = '' )`                                   | Names and metadata, never values |
| `wp_import_option_as_secret( string $option, string $name )`                  | `true\|WP_Error`                 |
| `wp_retire_secret_version( string $name )`                                    | Clears the previous version      |
| `wp_*_network_secret()` variants                                              | Network-scope equivalents        |

- Names are `plugin-slug/secret-name` (lowercase alphanumerics, hyphens, a single slash). Validate with `wp_secrets_validate_name()`.
- `wp_get_secret()` has three distinct outcomes: a `WP_Secret` (call `->reveal()` for the plaintext, which can itself be a `WP_Error` if the provider withholds values), `null` (doesn't exist), or `WP_Error` (exists, couldn't be read). Don't collapse them.
- **No capability checks inside these functions** — they must work from cron and REST. Check `manage_secrets` (new, granted to administrators) at your settings screen or CLI boundary.
- Each secret keeps its previous version, so a rotation doesn't break readers mid-change.
- `wp_import_option_as_secret()` copies (doesn't delete) the option and flags the secret `needs_rotation`; delete the old option yourself after migrating.
- No admin screen in 7.2 (deferred to 7.3); WP-CLI commands stay in the feature plugin.

```php
// Migrate a plaintext API key once, then read it through the Secrets API.
if ( function_exists( 'wp_set_secret' ) ) {
	if ( false !== get_option( 'myplugin_api_key', false ) && null === wp_get_secret( 'myplugin/api-key' ) ) {
		$result = wp_import_option_as_secret( 'myplugin_api_key', 'myplugin/api-key' );
		if ( true === $result ) {
			delete_option( 'myplugin_api_key' );
		}
	}

	$secret  = wp_get_secret( 'myplugin/api-key' );
	$api_key = $secret instanceof WP_Secret ? $secret->reveal() : '';
	if ( is_wp_error( $api_key ) ) {
		$api_key = '';
	}
}
```

---

## Entity fields and actions registries

[r64517](https://core.trac.wordpress.org/changeset/64517) (actions), [r64528](https://core.trac.wordpress.org/changeset/64528) (fields), [r64566](https://core.trac.wordpress.org/changeset/64566) and [r64581](https://core.trac.wordpress.org/changeset/64581) (core's own registrations); tracking issue [gutenberg#81228](https://github.com/WordPress/gutenberg/issues/81228).

Server-side registration of DataViews/DataForm fields and actions for an entity (`kind` + `name`, e.g. `postType` / `page`), exposed through `/wp/v2/entity-fields`, `/wp/v2/entity-actions`, and the view-config endpoint. Register only inside the matching init action; outside it the call returns an empty array.

```php
add_action( 'wp_entity_fields_api_init', function () {
	wp_register_entity_fields( 'my-plugin', 'postType', 'book', array(
		array(
			'id'            => 'isbn',
			'type'          => 'text',
			'label'         => __( 'ISBN', 'my-plugin' ),
			'enableSorting' => false,
		),
	) );
} );

add_action( 'wp_entity_actions_api_init', function () {
	wp_register_entity_actions( 'my-plugin', 'postType', 'book', $actions, '@my-plugin/book-actions' );
} );
```

- Field definitions use DataViews field properties (`type`, `label`, `placeholder`, `Edit`, `enableSorting`, `enableHiding`, `filterBy`, …). Anything that needs JavaScript (render or edit components, callbacks) comes from the optional script module. For actions, a JS-only property passed from PHP is reported and removed; a field whose `type` DataViews doesn't provide is reported but still registered.
- A field or action id that is already registered is reported and skipped. Use `wp_update_entity_fields()` to change one, or `wp_unregister_entity_fields()` / `wp_unregister_entity_actions()` to replace it.
- Directory-based collections: `wp_register_entity_field_collection( $dir )` reads `*/field.php` files that each `return array( ... )`.
- Actions support the `list` context only; `single` was removed before release and is reported with `_doing_it_wrong()`.
- Core's own post fields are registered this way, which is how the post-editor inspector is moving to a DataForm (roadmap; `PluginPostExcerpt` may not survive that move).

---

## Interactivity API: `data-wp-html`

`@wordpress/interactivity` 7.0.0 ([#83092](https://github.com/WordPress/gutenberg/pull/83092)); server side `wp_interactivity_as_dangerous_html()`.

Renders trusted HTML from reactive state as an element's content. It renders **only** values explicitly marked trusted: `asDangerousHTML()` on the client, `wp_interactivity_as_dangerous_html()` on the server. Plain strings are not rendered (a later iteration may add sanitized strings).

```php
// render.php — sanitize first; the function does not.
wp_interactivity_state( 'my-plugin', array(
	'summary' => wp_interactivity_as_dangerous_html( wp_kses_post( $summary ) ),
) );
?>
<div data-wp-interactive="my-plugin" data-wp-html="state.summary"></div>
```

```js
import { store, asDangerousHTML } from "@wordpress/interactivity";

store("my-plugin", {
	state: {
		get summary() {
			return asDangerousHTML(buildTrustedMarkup());
		},
	},
});
```

- Replaces `data-wp-watch` callbacks that set `element.innerHTML`.
- Never create a token from `context` or from user input; context is client-controlled.

---

## Block supports and `theme.json`

| Support                                         | How to use                                                                                                                                                                                                                                                                        | Replaces                                    |
| ----------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------- |
| `typography.textShadow`                         | `"supports": { "typography": { "textShadow": true } }`; on by default for Paragraph and Heading. Theme presets in `settings.typography.textShadowPresets` (`defaultTextShadowPresets` to hide core's) generate `--wp--preset--text-shadow--{slug}` and `.has-{slug}-text-shadow`. | Custom text-shadow CSS and style variations |
| `background.backgroundClip`                     | Values `border-box`, `padding-box`, `content-box`, `text`. `text` with a gradient gives gradient text. A color clipped to text no longer adds `has-background`. KSES allows `background-clip`. ([r64590](https://core.trac.wordpress.org/changeset/64590))                        | Hand-rolled gradient-text CSS               |
| Responsive `position`                           | Position can vary by viewport alongside the 7.1 responsive states.                                                                                                                                                                                                                | Media-query CSS for sticky/fixed blocks     |
| Core blocks gained existing supports (~140 PRs) | Background images, gradients, shadows, link/button colors, borders, text columns and alignment — now styleable from `theme.json` `styles.blocks`.                                                                                                                                 | Theme CSS targeting core block classes      |

JS: `getStyleForState()` / `setStyleForState()` from `@wordpress/block-editor`, and the `getSelectedBlockStyleState()` / `hasSelectedBlockStyleState()` selectors, let third-party blocks with custom controls write responsive and pseudo-state styles ([#82741](https://github.com/WordPress/gutenberg/pull/82741)).

---

## Icons

- The `add_menu_page()` icon argument accepts a **namespaced icon name** for a registered icon, rendered as inline SVG that follows the admin color scheme:

    ```php
    add_action( 'init', function () {
    	wp_register_icon_collection( 'my-plugin', array(
    		'label'  => __( 'My Plugin', 'my-plugin' ),
    		'public' => false, // Admin-only: hidden from REST and the Icon block.
    	) );
    	wp_register_icon( 'my-plugin/logo', array(
    		'label'     => __( 'My Plugin', 'my-plugin' ),
    		'file_path' => __DIR__ . '/assets/logo.svg',
    	) );
    } );

    add_menu_page( 'My Plugin', 'My Plugin', 'manage_options', 'my-plugin', 'my_plugin_page', 'my-plugin/logo' );
    ```

    On 7.1 and earlier the same string would be treated as an image URL, so keep a `dashicons-*` or base64 SVG fallback when the plugin supports both (`function_exists( 'wp_is_valid_icon_name' )`).

- `wp_register_icon()` accepts `keywords` for the Icon block search. Collections take `public` (default `true`); `core` and `core-admin` are reserved.
- No public API yet for SVG icons on toolbar nodes (`_wp_admin_bar_icon()` is private).

---

## Smaller additions

| API                                                                     | Use                                                                                           |
| ----------------------------------------------------------------------- | --------------------------------------------------------------------------------------------- |
| `pre_wp_count_posts`, `pre_wp_count_attachments` filters                | Short-circuit expensive counts on large sites with a precomputed value                        |
| `wp_ai_client_embedding()`, `WP_AI_Client_Embedding_Builder`            | Embedding vectors through the bundled PHP AI Client (1.5.0); `wp_ai_client_prevent_embedding` |
| Abilities `core/content-query`, `core/settings-get`, `core/users-query` | Read-only content, settings, and user abilities for agents and MCP                            |
| `register_site_meta()` / `unregister_site_meta()`                       | Registered meta for multisite sites (wraps `register_meta( 'blog', ... )`)                    |
| `wp_truncate_slug()`                                                    | Replaces `_truncate_post_slug()`                                                              |
| `wp_is_meta_box_reordering_enabled()`                                   | Respect the new per-user "disable meta box rearranging" screen option in custom meta box UIs  |
| `wp_change_password_url` filter                                         | Point `/.well-known/change-password` at a custom account screen                               |
| `wp_feed_namespaces` filter, `wp_get_feed_namespaces()`                 | Add XML namespaces to feeds without echoing into the root element                             |
| `wp_generate_animated_image_subsizes` filter (default `true`)           | Keep or drop animation in GIF/WebP sub-sizes                                                  |
| `auto_approve_ping` filter                                              | Control same-site pingback auto-approval                                                      |
