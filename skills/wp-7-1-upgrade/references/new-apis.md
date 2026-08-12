# WordPress 7.1 — New APIs

Adoption reference. Everything here is additive: nothing breaks if you ignore it. Prioritize adopting an API when it lets you delete hand-rolled code.

---

## SVG Icon API

Replaces custom icon registries, inline SVG constants, and ad-hoc icon sprite plumbing.

### Collections

Icons live in named collections; the collection name is the prefix, so `core/plus` and `my-plugin/plus` coexist. WordPress ships one collection, `core`. Names must begin and end with a lowercase letter or digit; the interior may also contain hyphens and underscores.

```php
function my_plugin_register_icon_collection() {
	wp_register_icon_collection(
		'my-plugin',
		array(
			'label'       => __( 'My Plugin Icons', 'my-plugin' ),
			'description' => __( 'Icons provided by My Plugin.', 'my-plugin' ),
		)
	);
}
add_action( 'init', 'my_plugin_register_icon_collection' );
```

`wp_unregister_icon_collection( $name )` drops the collection and everything in it, so per-icon cleanup is unnecessary. Hook it to `init` at a later priority than registration.

### Icons

`wp_register_icon( $name, $args )` takes a `label` plus **exactly one** of `content` (an inline SVG string) or `file_path` (an absolute path to a `.svg`). Returns `true`/`false`, with a `_doing_it_wrong()` notice on failure. It fails on a bad or un-namespaced name, an unregistered collection, a duplicate icon, a missing label, unsupported keys, or supplying neither `content` nor `file_path` — or both.

```php
function my_plugin_register_icons() {
	wp_register_icon_collection( 'my-plugin', array( 'label' => __( 'My Plugin Icons', 'my-plugin' ) ) );

	// From an inline SVG string.
	wp_register_icon(
		'my-plugin/star',
		array(
			'label'   => __( 'Star', 'my-plugin' ),
			'content' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12 2l2.9 6.9 7.1.6-5.4 4.7 1.6 7L12 18l-6.2 3.2 1.6-7L2 9.5l7.1-.6z" /></svg>',
		)
	);

	// From an .svg file shipped with the plugin.
	wp_register_icon(
		'my-plugin/heart',
		array(
			'label'     => __( 'Heart', 'my-plugin' ),
			'file_path' => plugin_dir_path( __FILE__ ) . 'icons/heart.svg',
		)
	);
}
add_action( 'init', 'my_plugin_register_icons' );
```

`file_path` is resolved lazily — the file is read only when content is needed, so a bad path registers successfully and later yields empty content rather than an error. Test the rendered output, not just the return value.

### Sanitization

Content runs through `wp_kses` against a narrow allowlist: only `<svg>`, `<path>`, and `<polygon>` survive, each with a fixed attribute set. Other elements, inline styles, scripts, and event handlers are stripped. This is deliberately conservative and may be broadened later.

**Consequence:** `stroke` is banned entirely, so stroke-based artwork loses its stroke — use fills. And `fill` is permitted only on `<path>` and `<polygon>`, never on the outer `<svg>`.

### Rendering

```php
// Decorative icon at the default 24px.
echo wp_get_icon( 'core/plus' );

// 32px, with an accessible label and an extra class.
echo wp_get_icon(
	'my-plugin/star',
	array(
		'size'  => 32,
		'label' => __( 'Featured', 'my-plugin' ),
		'class' => 'my-plugin-star',
	)
);
```

`size` defaults to `24`; pass `null` to preserve the SVG's intrinsic size. Supplying `label` exposes the icon to screen readers; omitting it marks the icon decorative. An unregistered name returns an empty string.

### Color

Because the allowlist bans `fill` on the outer `<svg>`, `wp_get_icon()` output does not inherit `currentColor` on its own. Inside the Icon block it works because the block stylesheet applies `fill: currentColor` to `.wp-block-icon svg`; a standalone call renders in the SVG's own fill (black). Two fixes:

```php
// Option 1 — your own CSS on a passed class; fill inherits down to the shapes.
echo wp_get_icon( 'my-plugin/star', array( 'class' => 'my-icon' ) );
```

```css
.my-icon {
	fill: currentColor;
}
```

```php
// Option 2 — bake it into the shape at registration so it survives sanitization.
wp_register_icon(
	'my-plugin/star',
	array(
		'label'   => __( 'Star', 'my-plugin' ),
		'content' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path fill="currentColor" d="M12 2l2.9 6.9 …z" /></svg>',
	)
);
```

For React icons from `@wordpress/icons` 15.0.0+, each icon carries `fill="currentColor"` on its outer `<svg>`, so override with `color`, not `fill`:

```jsx
import { Icon, plus } from "@wordpress/icons";

<Icon icon={plus} style={{ color: "#3858e9" }} />;
```

### REST

All routes are GET-only and read-only under `wp/v2`. Access requires an authenticated user with `edit_posts`, or the equivalent edit capability for any `show_in_rest` post type.

- `GET /wp/v2/icon-collections` — all collections (fields: slug, label, description)
- `GET /wp/v2/icon-collections/<collection>`
- `GET /wp/v2/icons` — all icons; 7.1 adds the `collection` field and parameter
- `GET /wp/v2/icons/<collection>` — new in 7.1
- `GET /wp/v2/icons/<collection>/<name>`

List-route query params: `search` (matches `name` or `label`) and `collection`, e.g. `GET /wp/v2/icons?collection=my-plugin&search=star`.

### Icon block changes

The picker now groups icons per collection with a tab each plus an "All" tab; search is scoped to the active tab, and the query survives tab switches. Toolbar gains flip-horizontal, flip-vertical, and 90-degree rotate. New blocks default to `core/info`. Server-side render now goes through `wp_get_icon()`, so block output and manual output share one code path.

---

## Accessible tooltips and toggletips

Replaces `title` attributes and pointer-only help affordances.

- **`wp_get_tooltip()`** gives a visible name to a control represented only by an icon.
- **`wp_get_toggletip()`** renders a trigger button that reveals extended help about nearby controls.

Core uses tooltips on post meta box controls (move up, move down, show/hide) and a toggletip on the login screen's "Remember Me" checkbox.

### Enqueuing

CSS loads globally; JS loads by default only on meta box screens and the login screen. Elsewhere, enqueue manually:

```php
wp_enqueue_style( 'wp-tooltip' );
wp_enqueue_script( 'wp-tooltip' );
```

### Arguments

Both take `string $content` (plain text) plus an `$args` array. Only `$content` is required.

| Arg           | Purpose                                                                     |
| ------------- | --------------------------------------------------------------------------- |
| `id`          | Popover element ID; defaults to a generated unique ID                       |
| `button`      | Existing `button`/`a` markup to use instead of a generated one              |
| `label`       | Accessible label for the toggle button, default `Help` — **toggletip only** |
| `close_label` | Close button label, default `Close` — toggletip only                        |
| `icon`        | Dashicons class; default `dashicons-editor-help`                            |
| `class`       | Extra classes                                                               |

Supplied `button` markup is run through `WP_HTML_Tag_Processor`, which adds the required attributes — this is the closest thing to a migration path from an existing custom control.

### Examples

```php
wp_get_tooltip(
	__( 'Show/Hide Menu', 'my-text-domain' ),
	array( 'icon' => 'dashicons-menu' )
);
```

<!-- prettier-ignore -->
```html
<span class="wp-tooltip wp-is-tooltip">
    <button class="wp-tooltip__toggle" type="button" aria-label="Show/Hide Menu">
        <span class="dashicons dashicons-menu" aria-hidden="true"></span>
    </button>
    <span popover="hint" id="wp-tooltip-1" class="wp-tooltip__bubble" role="tooltip">
        <span id="wp-tooltip-1-text" class="wp-tooltip__text">Show/Hide Menu</span>
    </span>
</span>
```

```php
wp_get_toggletip(
	__( 'Selecting "Remember Me" reduces the number of times you&#8217;ll be asked to log in using this device.', 'my-text-domain' ),
	array(
		'id'    => 'rememberme-help-toggletip',
		'label' => 'Learn More',
		'icon'  => 'dashicons-welcome-learn-more',
	)
);
```

### Markup differences

|               | Tooltip           | Toggletip                                                    |
| ------------- | ----------------- | ------------------------------------------------------------ |
| Wrapper class | `wp-is-tooltip`   | `wp-is-toggletip`                                            |
| Popover type  | `popover="hint"`  | `popover="auto"`                                             |
| Bubble role   | `role="tooltip"`  | `role="dialog"` + `aria-label`, `tabindex="-1"`, `autofocus` |
| Trigger attrs | `aria-label` only | `aria-haspopup="dialog"` + `popovertarget`                   |
| Close button  | none              | `.wp-tooltip__close` with `popovertargetaction="hide"`       |

Shared classes: `wp-tooltip`, `wp-tooltip__toggle`, `wp-tooltip__bubble`, `wp-tooltip__text`, `wp-tooltip__close`. Text nodes get an ID of `{id}-text`. Everything is a `span` so the control can be validly inserted inside any element, including a paragraph.

**Accessibility guidance from the dev note:** these are gap-fillers, not a default. "It is always preferable for interface controls to have visible, persistent text labels." Justified when space is genuinely constrained, or when help text does not map to a single field so `aria-describedby` is impractical.

---

## Abilities API

7.1 substantially expands the API introduced in 6.9.

### Filtering with `wp_get_abilities( $args )`

Replaces hand-rolled `array_filter()` over the registry. Arguments combine with AND logic.

```php
$abilities = wp_get_abilities(
	array(
		'category'  => 'data-export',   // exact match, single string only
		'namespace' => 'my-plugin',     // trailing slash optional; matches the delimiter
		'meta'      => array( 'public' => true ),
	)
);
```

Metadata comparisons are **strict** — `true` does not match `1`, `false` does not match `0`. Nested metadata is supported. An ability may carry extra metadata beyond what you query.

Per-call callbacks:

```php
wp_get_abilities( array(
	'item_include_callback' => function ( WP_Ability $ability ): bool { /* … */ return true; },
	'result_callback'       => function ( array $abilities ): array { /* sort/slice */ return $abilities; },
) );
```

`result_callback` runs after all per-item matching. When sorting or slicing, preserve the array keys — results are keyed by ability name and downstream code may depend on it.

### New global filters

- `wp_get_abilities_item_include` — `( bool $include, WP_Ability $ability, array $args )`. Cannot _add_ an ability that already failed declarative matching.
- `wp_get_abilities_result` — `( array $abilities, array $args )`.

Use these only for site-wide behavior; prefer the per-call callbacks for one operation.

**Pipeline order:** category → namespace → meta → `item_include_callback` → `wp_get_abilities_item_include` → collect → `result_callback` → `wp_get_abilities_result`.

**Important:** the two global filters run **even when `wp_get_abilities()` is called with no arguments.** A bare `wp_get_abilities()` now means "retrieve through the standard filtering pipeline," not "retrieve the raw registry." For genuinely raw state:

```php
$registry  = WP_Abilities_Registry::get_instance();
$abilities = $registry->get_all_registered();
```

**Filtering is not authorization.** It controls discovery only. `permission_callback` remains the sole security boundary, and an ability returned by `wp_get_abilities()` is not necessarily executable by the current user.

### REST discovery

```text
GET /wp-json/wp-abilities/v1/abilities?namespace=my-plugin
GET /wp-json/wp-abilities/v1/abilities?category=my-plugin-content
GET /wp-json/wp-abilities/v1/abilities?meta[annotations][readonly]=true
```

Every collection request internally forces `meta.show_in_rest = true`, so no metadata query can reveal a REST-hidden ability. Custom metadata needs a REST parameter schema for query values to be coerced before strict comparison — without one, the string `'true'` never matches boolean `true`:

```php
add_filter(
	'rest_abilities_collection_params',
	static function ( array $params ): array {
		$params['meta']['properties']['my_plugin'] = array(
			'type'       => 'object',
			'properties' => array( 'enabled' => array( 'type' => 'boolean' ) ),
		);
		return $params;
	}
);
```

The standard `readonly`, `destructive`, and `idempotent` annotations already have core-declared schemas.

### Execution lifecycle filters

The previous `wp_before_execute_ability` / `wp_after_execute_ability` actions could observe execution but not change it. Four new filters can:

```text
wp_pre_execute_ability
        ├── short-circuit when an override is returned
        ↓
WP_Ability::normalize_input()  →  wp_ability_normalize_input
        ↓
WP_Ability::validate_input()
        ↓
WP_Ability::check_permissions()  →  wp_ability_permission_result
        ↓
wp_before_execute_ability  →  execute callback  →  wp_ability_execute_result
        ↓
WP_Ability::validate_output()  →  wp_after_execute_ability  →  return
```

| Filter                         | Purpose                                               |
| ------------------------------ | ----------------------------------------------------- |
| `wp_pre_execute_ability`       | Short-circuit the entire pipeline                     |
| `wp_ability_normalize_input`   | Transform normalized input before validation          |
| `wp_ability_permission_result` | Modify or override the permission result              |
| `wp_ability_execute_result`    | Transform or recover results before output validation |

Input and output transformations run **before** their respective schema validation, so transformed values must still satisfy the registered schemas.

`wp_pre_execute_ability` uses a unique internal sentinel as its default, so any PHP value — `null`, `false`, an object — is a legitimate short-circuit result. Return `$pre` unchanged to continue. The new `WP_Filter_Sentinel` marker class exists for this; you never need to instantiate it.

```php
add_filter(
	'wp_pre_execute_ability',
	function ( $pre, $ability_name, $input, $ability ) {
		if ( 'my-plugin/sync-catalog' !== $ability_name ) {
			return $pre;
		}
		if ( ! get_option( 'my_plugin_maintenance_mode', false ) ) {
			return $pre;
		}
		return new WP_Error(
			'ability_temporarily_unavailable',
			__( 'This operation is temporarily unavailable due to maintenance.', 'my-plugin' ),
			array( 'status' => 503 )
		);
	},
	10,
	4
);
```

Because it runs before permission checks and validation, keep its decisions narrow.

`wp_ability_permission_result` may return `true`, `false`, or a `WP_Error`; anything else converts to `false`. It also applies when permissions are checked independently of `execute()`, including REST and WP-CLI. **Use with care — returning `true` overrides a denial from the ability's own `permission_callback`.**

Returning a `WP_Error` from `wp_ability_normalize_input` stops execution before validation, permission checks, and the callback. Over REST it is propagated by the controller, defaulting to HTTP 400 unless the error specifies otherwise (422, 429, …).

### Custom validation and observation

`wp_ability_validate_input` and `wp_ability_validate_output` receive `( true|WP_Error $is_valid, mixed $value, string $name )` and let you enforce rules JSON Schema cannot express. Preserve incoming `WP_Error`s. Returning `false` fails validation but is converted to a generic `WP_Error` — return your own for a useful message.

REST-style `validate_callback` and `sanitize_callback` schema keywords are **not** executed by the Abilities API; use these filters instead.

`wp_ability_invoked` fires at the very start of `WP_Ability::execute()`, before normalization, validation, permission checks, and the short-circuit filter — so it runs for _every_ invocation, including invalid, denied, short-circuited, cached, and approval-pending calls. Suited to auditing and telemetry. It receives **raw, unnormalized** input, which may contain credentials or personal data; do not log it indiscriminately.

`wp_before_execute_ability` and `wp_after_execute_ability` now also receive the `WP_Ability` instance as a final argument. Existing callbacks keep working; update the signature and `$accepted_args` to receive it.

### The unified `public` flag

A single high-level flag marking an ability as intended for external clients (REST, MCP adapters, AI agents). Setting `public => true` makes `show_in_rest` default to `true`. Channel-specific settings take precedence:

```php
$show_in_rest = $meta['show_in_rest'] ?? $meta['public'] ?? false;
```

Resolved metadata for every ability now includes a boolean `public` property, defaulting to `false`. Integrations should follow the same precedence: use an explicit channel-specific value when present, otherwise inherit `public`. Use a channel-specific flag only when an ability should be exposed through just that channel, needs to opt out of one channel despite being generally public, or the channel offers behavior the general flag cannot express.

### Core ability changes

- `core/get-user-info` returns five new fields: `first_name`, `last_name`, `nickname`, `description`, `user_url`. `roles` is now normalized with `array_values()` so it always encodes as a JSON array. It is now exposed over REST and declares `public`.
- `core/get-user-info` and `core/get-environment-info` accept an optional `fields` input to request a subset; unknown names return `ability_invalid_input` before the callback runs.
- `core/get-site-info`, `core/get-user-info`, and `core/get-environment-info` now declare translatable Title Case titles and descriptions on every output property. **Use the schemas for discovery rather than assuming a fixed response shape.**
- REST `run` requests over GET/DELETE now coerce input to the types declared in `input_schema` before the ability runs, registered as the input argument's `sanitize_callback`. So `?input[limit]=10&input[featured]=true&input[ids]=1,2,3` arrives as `array( 'limit' => 10, 'featured' => true, 'ids' => array( 1, 2, 3 ) )`. Coercion never widens what validation accepts.

---

## Responsive block styles

Styles for Tablet and Mobile viewports, in Global Styles per block type and on individual block instances. Applies to any block using core supports: typography, color, background, border, dimensions, spacing, layout.

The default style is the base and applies at every viewport. There is **no `@desktop` key** — the base _is_ desktop, and it continues to apply at smaller sizes for any property not overridden.

```json
"styles": {
	"blocks": {
		"core/group": {
			"spacing": { "padding": { "top": "3rem", "right": "3rem", "bottom": "3rem", "left": "3rem" } },
			"@mobile": {
				"spacing": { "padding": { "top": "1rem", "right": "1rem", "bottom": "1rem", "left": "1rem" } }
			}
		}
	}
}
```

Block instances use the same keys inside the existing `style` attribute:

```html
<!-- wp:paragraph {"style":{"@mobile":{"typography":{"fontSize":"1rem"}}}} -->
<p>Text with a responsive font size.</p>
<!-- /wp:paragraph -->
```

Default breakpoints:

| State     | Media query                       |
| --------- | --------------------------------- |
| `@mobile` | `@media (width <= 480px)`         |
| `@tablet` | `@media (480px < width <= 782px)` |

Themes can override them with the new top-level `settings.viewport`:

```json
"settings": { "viewport": { "mobile": "30rem", "tablet": "45rem" } }
```

Values must be non-negative numeric lengths in `px`, `em`, or `rem`. CSS functions, percentages, unitless values, and other units are ignored. If only one is valid it keeps its name and uses a single max-width query. If neither is valid, defaults apply. If both are valid but Tablet ≤ Mobile, only Mobile is used. `settings.viewport` is top-level only — it cannot be set per block type.

On the front end, WordPress generates media-query-scoped CSS and adds a stable generated class. Non-layout per-instance state declarations are marked `!important` so they override the block's default inline styles. Responsive layout values and `blockGap` go through the existing layout support, scoped with the block's generated container class.

### Opting out of responsive editing

```php
function example_disable_responsive_editing( $settings ) {
	$settings['responsiveEditingEnabled'] = false;
	return $settings;
}
add_filter( 'block_editor_settings_all', 'example_disable_responsive_editing' );
```

Removes the "Responsive styles" toggle in the View menu and the "Viewport" group in the Global Styles "States" dropdown. Device previews and pseudo states remain. **This governs the editing interface only** — already-saved responsive styles still render everywhere.

---

## Pseudo and custom style states

### Pseudo states

`:hover`, `:focus`, `:focus-visible`, and `:active`, currently on the Button and Navigation Link blocks. Always prefixed with `:`.

```json
"styles": {
	"blocks": {
		"core/button": {
			"color": { "background": "black", "text": "white" },
			":hover": { "color": { "background": "blue" } },
			":focus": { "color": { "background": "purple" } }
		}
	}
}
```

Pseudo states nest **inside** responsive states (viewport outermost):

```json
"core/button": {
	"@mobile": {
		":hover": { "color": { "background": "var:preset|color|contrast" } }
	}
}
```

Block instances use the same shape inside `style`:

```html
<!-- wp:button {"backgroundColor":"accent-3","style":{":hover":{"color":{"background":"var:preset|color|accent-2"}}}} -->
```

### Custom states

Early feature, `theme.json`-only, no user-facing UI, currently only the Navigation Link block — which uses it to style the current menu item. Always prefixed with `-`.

```json
"core/navigation-link": {
	"-current": {
		"color": { "text": "var:preset|color|contrast" },
		":hover": { "color": { "text": "var:preset|color|accent-1" } }
	}
}
```

Custom states generate CSS targeting a class selector, declared on the block via `block.json` `selectors`:

```json
"selectors": {
	"states": { "-current": ".wp-block-navigation-link .current-menu-item" }
}
```

### Opting out

```php
function example_disable_block_states_editing( $settings ) {
	$settings['blockStatesEditingEnabled'] = false;
	return $settings;
}
add_filter( 'block_editor_settings_all', 'example_disable_block_states_editing' );
```

Hides the state dropdown in the block inspector's block card and the pseudo-state options in Global Styles. Does **not** affect viewport states — those are governed separately by `responsiveEditingEnabled`. Set both to `false` to remove state editing entirely. Saved state styles still render.

---

## `textShadow` in Global Styles

A `textShadow` property under `styles.typography`, mapping directly to the CSS `text-shadow` property, so any valid value works including multiple comma-separated shadows. Supported at global typography, per-block, and element level (including states like `:hover`).

```json
{
	"$schema": "https://schemas.wp.org/trunk/theme.json",
	"version": 3,
	"styles": {
		"typography": {
			"textShadow": "1px 1px 2px red, 0 0 1em blue, 0 0 0.2em blue"
		},
		"blocks": {
			"core/paragraph": {
				"typography": { "textShadow": "1px 1px 2px red, 0 0 1em red" }
			}
		},
		"elements": {
			"link": { ":hover": { "typography": { "textShadow": "none" } } }
		}
	}
}
```

**`theme.json` only in this release** — no block inspector control, no Global Styles UI, no presets, and no `supports.typography.textShadow` block support. Those are planned for the next release. When a global shadow is set, it is removed from the empty rich-text placeholder so the "Type / to choose a block" prompt stays readable; actual content still renders with the shadow.

---

## New block supports

### `background.gradient`

Unlike the existing `color.gradient`, this one **combines with a background image**. `color.gradient` stores at `style.color.gradient` and renders as the `background` shorthand, which resets `background-image` — so a block could show a gradient or an image, never both. The new support stores at `style.background.gradient` and renders through the `background-image` longhand:

```css
background-image:
	linear-gradient(135deg, #000 0%, #fff 100%),
	url("https://example.com/image.jpg");
```

```json
{
	"supports": {
		"background": {
			"backgroundImage": true,
			"gradient": true,
			"__experimentalDefaultControls": {
				"backgroundImage": true,
				"gradient": true
			}
		}
	}
}
```

```json
{
	"styles": {
		"background": {
			"gradient": "linear-gradient( 135deg, #000 0%, #fff 100% )"
		},
		"blocks": {
			"core/group": {
				"background": {
					"gradient": "var:preset|gradient|vivid-cyan-blue"
				}
			}
		}
	}
}
```

When `background.gradient` is enabled for a block, the gradient tab in the Color panel is suppressed to avoid duplicate controls. Core adopters in 7.1: `core/group`, `core/accordion`, `core/pullquote`, `core/post-content`, `core/quote`.

`safecss_filter_attr()` was updated to allow combined gradient + `url()` values, so no additional filter is required. `color.gradient` is unchanged and existing blocks using it keep working; a future migration is anticipated but is not part of this change.

### `dimensions.minWidth`

Follows the existing `minHeight` pattern, applied as CSS `min-width`, with `dimensionSizes` preset support.

| Layer                | Key                                                  |
| -------------------- | ---------------------------------------------------- |
| `block.json` support | `supports.dimensions.minWidth`                       |
| `theme.json` setting | `settings.dimensions.minWidth`                       |
| `theme.json` style   | `styles.dimensions.minWidth`                         |
| CSS property         | `min-width`                                          |
| Preset source        | `dimensionSizes` (`--wp--preset--dimension--{slug}`) |

In the block inspector the control is hidden by default unless the block opts in through `__experimentalDefaultControls`; otherwise it is revealed from the Dimensions panel's three-dot options menu. In Global Styles it is shown by default.

---

## Design System theming

A `wp-theme` **stylesheet** and a `wp-theme` **script** handle are registered by default and available as dependencies for plugins.

The stylesheet provides semantic design tokens as CSS custom properties for building wp-admin UI:

```css
.card {
	background-color: var(--wpds-color-background-surface-neutral-strong);
	color: var(--wpds-color-foreground-content-neutral);
	border: var(--wpds-border-width-xs) solid
		var(--wpds-color-stroke-surface-neutral-weak);
	border-radius: var(--wpds-border-radius-lg);
	padding: var(--wpds-dimension-padding-2xl);
}
```

The script provides a `ThemeProvider` React component that overrides token values for a subtree:

```jsx
import { ThemeProvider } from "@wordpress/theme";
import { Card } from "@wordpress/ui";

function Application() {
	return (
		<ThemeProvider
			color={{ primary: "#3858e9", background: "#11004d" }}
			cornerRadius="pronounced"
		>
			<Card.Root>
				<Card.Content>…</Card.Content>
			</Card.Root>
		</ThemeProvider>
	);
}
```

| Prop               | Description                                                                                            |
| ------------------ | ------------------------------------------------------------------------------------------------------ |
| `color.primary`    | Primary seed color. A fully opaque sRGB-parseable string: hex, `rgb()`/`rgba()`, or a CSS named color. |
| `color.background` | Background seed color, same accepted formats.                                                          |
| `cursor.control`   | Cursor for non-link interactive controls. Inherits from the parent provider; falls back to `pointer`.  |
| `cornerRadius`     | Roundness preset: `none`, `subtle`, `moderate`, `pronounced`. Inherits; falls back to `subtle`.        |
| `isRoot`           | Applies theming to the root document element too. Render at most one root provider per document.       |

Given primary and background seeds, the component generates a harmonious color ramp with contrast between foreground/background and background/border. **The algorithm aims for accessible contrast targets but cannot guarantee them for every combination — verify your specific colors.**

Prefer using `@wordpress/ui` components (which are built on these tokens) over consuming tokens directly. In the block or site editor, use the `Card` React component rather than hand-rolling one. This is a foundation that expands to more of the admin in later releases; in 7.1 it is used to apply the user's preferred color scheme to the Site Editor.

---

## Site Editor screen configuration

Four filters configure the DataViews and DataForm components powering Site Editor screens:

| Page      | Filter                                             |
| --------- | -------------------------------------------------- |
| Pages     | `get_entity_view_config_posttype_page`             |
| Templates | `get_entity_view_config_posttype_wp_template`      |
| Parts     | `get_entity_view_config_posttype_wp_template_part` |
| Patterns  | `get_entity_view_config_posttype_wp_block`         |

Each configures `default_view` (visible fields, default sort), `default_layouts` (which layouts users can choose), `view_list` (the preconfigured sidebar views: All, Published, Drafts…), and `form` (the DataForm used for Quick Edit).

Callbacks receive an object with methods for working with the config:

```php
function example_filter_page_view_config( $data ) {
	$patch = array(
		'default_view' => array(
			'type'   => 'grid',
			'sort'   => array( 'field' => 'title', 'direction' => 'asc' ),
			'fields' => array( 'date' ),
		),
	);
	$data->merge( $patch, 1 );

	return $data;
}
add_filter( 'get_entity_view_config_posttype_page', 'example_filter_page_view_config' );
```

---

## Editable blocks inside the Custom HTML block

The Custom HTML block now supports interleaving static HTML with regular editable blocks:

<!-- prettier-ignore -->
```html
<!-- wp:html -->
<div class="banner"><h1>Static heading</h1><!-- wp:paragraph -->
<p>Editable paragraph</p>
<!-- /wp:paragraph --><footer>Static footer</footer></div>
<!-- /wp:html -->
```

In the editor the static markup renders inert while the inner blocks are editable in place but locked — they cannot be moved, removed, or given siblings. The full markup stays available in the "Edit HTML" modal, and serialization round-trips unchanged, so existing content is unaffected.

Block variations now accept an `innerContent` field: an array of static HTML fragments where each `null` marks the position of the corresponding `innerBlocks` entry. This ships a fixed markup shell with editable slots as its own inserter item, with no custom block and no build step:

```js
wp.blocks.registerBlockVariation("core/html", {
	name: "testimonial-card",
	title: "Testimonial Card",
	icon: "format-quote",
	innerContent: ['<div class="testimonial-card">', null, "</div>"],
	innerBlocks: [["core/paragraph", { content: "An inspiring quote." }]],
});
```

Unlike a pattern, the user can edit only the designated slots, not the structure. `innerContent` applies **only** to `core/html` variations and is ignored elsewhere.

---

## Template parts can opt out of content-only editing

```php
add_filter( 'block_editor_settings_all', function ( $settings ) {
	$settings['disableContentOnlyForTemplateParts'] = true;
	return $settings;
} );
```

Or at runtime:

```js
wp.data.dispatch("core/block-editor").updateSettings({
	disableContentOnlyForTemplateParts: true,
});
```

Restores standard block editing for Template Parts instead of the default content-only mode. When the editor is in template-locked rendering mode, content-only editing for Template Parts is always disabled regardless of this setting. Leaving it unset preserves existing behavior. A command palette entry offers a session-only toggle.

---

## Block variation transforms

Transforms can now target a specific block variation via `variationName`:

```js
transforms: {
	to: [
		{
			type: 'block',
			blocks: [ 'core/group' ],
			variationName: 'group-grid',
			transform: ( attributes, innerBlocks ) =>
				createBlock( 'core/group', { ...attributes, layout: { type: 'grid' } }, innerBlocks ),
		},
	],
}
```

`switchToBlockType()` accepts the target variation name as an optional third argument:

```js
switchToBlockType(blocks, "core/group", "group-grid");
```

---

## Client-side media processing

Opt-out, not opt-in — it is on by default where supported. See [breaking-changes.md](breaking-changes.md#14-cross-origin-isolation-on-editor-screens) for the cross-origin isolation consequences, which are the part most likely to affect an existing codebase.

New PHP surface:

- `wp_is_client_side_media_processing_enabled()` — feature gate, filterable via `wp_client_side_media_processing_enabled`.
- `wp_start_cross_origin_isolation_output_buffer()` — sends `Document-Isolation-Policy` on editor screens.
- `wp_add_crossorigin_attributes()` — adds `crossorigin="anonymous"` to cross-origin scripts.

New JS packages: `@wordpress/upload-media`, `@wordpress/media-utils`, `@wordpress/vips`, `@wordpress/video-conversion`.

REST additions: `generate_sub_sizes` and `convert_format` parameters; `POST /wp/v2/media/{id}/sideload`; `POST /wp/v2/media/{id}/finalize`; a `replace_file` flag for HEIC companion uploads; a `url` parameter for server-side import of external images; and new response fields `exif_orientation`, `missing_image_sizes`, `filename`, `filesize`, and a size-aware `image_quality` (client default 0.82 when absent).

Capabilities worth knowing: HEIC decoded in-browser and converted to JPEG; AVIF accepted without server-side AVIF support (the MIME check is bypassed for client-decoded uploads); UltraHDR gain maps preserved end-to-end, with `image_editor_output_format` conversion deliberately skipped for those files; opaque animated GIFs converted to MP4/WebM companions (`media_details.animated_video` / `animated_video_poster`) with the GIF remaining a single `image/gif` attachment; sub-size uploads as independent, retried requests.

Themes need no changes. `add_image_size()` sizes are generated client-side automatically, and sizes sharing dimensions with built-ins are deduplicated to one physical file.
