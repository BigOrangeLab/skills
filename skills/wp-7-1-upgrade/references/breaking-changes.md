# WordPress 7.1 — Breaking and Behavior Changes

Ordered by likelihood of breaking a real codebase. Each entry states what changed, how to detect it, and how to fix it.

---

## 1. The post editor is always iframed

**Severity: high.** The single largest source of 7.1 breakage.

### What changed

The site editor, template editor, and device previews have been iframed for several releases. The post editor was conditional: in 7.0 it was iframed only when every block in the content was Block API version 3 or higher, and dropped the iframe otherwise. Gutenberg 22.6+ forced it on when the plugin was active.

In 7.1 the post editor is **always** iframed — regardless of theme type, of the API versions of registered blocks, and of the API versions of blocks in the content. There is no opt-out.

### Why code breaks

The iframe has its own `document` and `window`, separate from the admin page where editor scripts execute. Any code that reaches for the global `document` or `window` to touch the canvas targets the wrong document, so queries return `null` and listeners never fire.

### Detection

Search editor-enqueued JS for global DOM access:

```bash
grep -rnE "document\.(querySelector|getElementById|getElementsBy|addEventListener)" \
  --include="*.js" --include="*.jsx" --include="*.ts" --include="*.tsx" src/
grep -rnE "jQuery\( *document|\\\$\( *document" --include="*.js" src/
grep -rn "window.getComputedStyle" --include="*.js" src/
```

A hit is only a problem if the target lives inside the editor canvas. Admin-side chrome (sidebars, modals, the toolbar) is outside the iframe and unaffected.

### Fix

Derive the canvas document from an element already inside it:

```js
// Wrong — global document is the admin page, not the canvas.
const block = document.querySelector(".wp-block-my-plugin-thing");

// Right — ownerDocument is whichever document the node actually lives in.
const doc = element.ownerDocument;
const view = doc.defaultView;
const block = doc.querySelector(".wp-block-my-plugin-thing");
```

For listeners on canvas elements, use `useRefEffect` so attachment and cleanup follow the node across re-renders and iframe remounts:

```js
import { useRefEffect } from "@wordpress/compose";

const ref = useRefEffect((node) => {
	const doc = node.ownerDocument;
	const onScroll = () => {
		/* … */
	};

	doc.addEventListener("scroll", onScroll);
	return () => doc.removeEventListener("scroll", onScroll);
}, []);
```

Also watch for: measuring with `window.innerWidth` instead of the iframe's `defaultView.innerWidth`; injecting `<style>` into `document.head` instead of the canvas document; and third-party libraries that capture `document` at module load.

Further reading: "Technical considerations for the iframe editor" in the Block API versions documentation; `WordPress/gutenberg#74042`.

---

## 2. Removed `@wordpress/components` APIs

**Severity: high.** Deprecated in 6.8, removed in 7.1 — imports now fail.

| Removed                                                                                                            | Replacement                                               |
| ------------------------------------------------------------------------------------------------------------------ | --------------------------------------------------------- |
| `Navigation` and all subcomponents (`NavigationMenu`, `NavigationItem`, `NavigationGroup`, `NavigationBackButton`) | `Navigator`                                               |
| `__experimentalApplyValueToSides`                                                                                  | No direct replacement. `BoxControl` itself is unaffected. |

```bash
grep -rnE "Navigation(Menu|Item|Group|BackButton)?[,} ]" --include="*.js" --include="*.jsx" src/
grep -rn "__experimentalApplyValueToSides" --include="*.js" src/
```

Filter `Navigation` hits — the string also matches the core Navigation _block_, which is unrelated. Only imports from `@wordpress/components` matter.

---

## 3. `__next40pxDefaultSize` is now inert

**Severity: medium.** Silent visual change rather than an error.

Form controls in `@wordpress/components` render at 40px height unconditionally. The opt-in prop, introduced in 6.7 and soft-deprecated in 6.8, has no runtime effect — including `__next40pxDefaultSize={ false }`, which no longer restores the old 36px height.

On `BorderBoxControl`, `BorderControl`, `FontSizePicker`, and `ToggleGroupControl`, the `size` prop is **also** deprecated and has no effect. If `size="__unstable-large"` was passed purely to get 40px, remove it too.

**Affected — `@wordpress/components`:** `BorderBoxControl`, `BorderControl`, `BoxControl`, `ComboboxControl`, `CustomSelectControl`, `FontSizePicker`, `FormFileUpload`, `FormTokenField`, `FocalPointPicker`, `InputControl`, `NumberControl`, `QueryControls`, `Radio`, `RangeControl`, `SearchControl`, `SelectControl`, `TextControl`, `ToggleGroupControl`, `TreeSelect`, `UnitControl`.

**Affected — `@wordpress/block-editor`:** `FontAppearanceControl`, `FontFamilyControl`, `LetterSpacingControl`, `LineHeightControl`.

`Button` is **not** affected and still uses the opt-in prop.

Fix: delete `__next40pxDefaultSize` from usage; no replacement prop is needed. Then re-check any custom CSS that compensated for the 36px height.

---

## 4. Post list table row headers moved

**Severity: medium.** Breaks CSS and JS selectors, not PHP.

The primary `<th scope="row">` moved from the first column (the selection checkbox) to the second column (the post title and row actions). This makes screen readers announce the post name rather than a checkbox that may not even be present.

**Before:**

<!-- prettier-ignore -->
```html
<tr>
  <th scope="row" class="check-column">
    <input type="checkbox" name="post[]" value="123">
  </th>
  <td class="title column-title column-primary page-title">
    <a class="row-title" href="...">Hello world!</a>
  </td>
  <td class="author column-author">admin</td>
</tr>
```

**After:**

<!-- prettier-ignore -->
```html
<tr>
  <td class="check-column">
    <input type="checkbox" name="post[]" value="123">
  </td>
  <th scope="row" class="title column-title column-primary page-title" aria-label="Hello world!">
    <a class="row-title" href="...">Hello world!</a>
  </th>
  <td class="author column-author">admin</td>
</tr>
```

Fix: selectors targeting the checkbox should match `th.check-column` **or** `td.check-column` — or better, `.check-column` / `th input[type="checkbox"], td input[type="checkbox"]`. Selectors targeting titles or row actions should match `td.title, td.column-title, td.page-title, td.column-primary, td .row-title, td .post-state, td .row-actions` **and** their `th` equivalents.

Retaining both `td` and `th` selectors keeps compatibility with pre-7.1 WordPress, which matters for plugins supporting a version range.

Also note: CSS for collapsed table cells in the responsive viewport now uses flex layout.

---

## 5. jQuery UI updated to 1.14.2

**Severity: medium**, low likelihood.

Bumped from 1.13.3. Drops support for all Internet Explorer versions and Edge Legacy, matching WordPress's browser support policy. WordPress sets `jQuery.uiBackCompat = true`, so code targeting the older jQuery 1.11 API keeps working.

Removed APIs — core does not use any of these, but plugins might:

- `$.fn._form`
- `$.ui.ie`
- `$.ui.safeActiveElement`
- `$.ui.safeBlur`

```bash
grep -rnE "\\\$\.(fn\._form|ui\.(ie|safeActiveElement|safeBlur))" --include="*.js" .
```

---

## 6. `media_library_infinite_scrolling` default flipped to `true`

**Severity: medium.** Behavior change with no error.

Since 5.8 this filter defaulted to `false`, so infinite scrolling was effectively off everywhere unless a plugin opted in. In 7.1 it defaults to `true` for both the Media Library grid view and the Media Modal, and a per-user opt-out appears on the profile screen.

**Precedence, highest first:** the filter (a hooked callback always wins) → the user's opt-out preference → the default (`true`).

```php
// Restore the 5.8–7.0 behavior for all users.
add_filter( 'media_library_infinite_scrolling', '__return_false' );

// Force ON for all users, ignoring the per-user opt-out.
add_filter( 'media_library_infinite_scrolling', '__return_true' );
```

Because the filter runs after the per-user preference is read, adding any callback overrides every user's individual choice — including `__return_true`, which takes the opt-out away from them.

The preference is stored as the user option `infinite_scrolling`, as the **string** `'true'` or `'false'`, matching how `syntax_highlighting` and `rich_editing` are persisted. Note the direction:

```php
// 'false' means the user has DISABLED infinite scrolling.
$disabled = 'false' === get_user_option( 'infinite_scrolling', $user_id );
```

The option is only shown to users with the `upload_files` capability.

---

## 7. `notify_post_author` filter now has the final say

**Severity: medium.** Can cause unwanted email, including for spam.

`wp_new_comment_notify_postauthor()` now checks a comment's approval status **before** applying the filter rather than after.

**Previously:** the filter received a default derived only from the `comments_notify` option (or `wp_notes_notify` for notes). Approval was checked afterward, so the filter saw a misleading `true` for unapproved comments, and returning `true` could not force a notification — the return value was silently discarded.

**Now:**

- The default is `false` for comments that are not approved, including held-for-moderation, spam, and trashed.
- The default for approved comments still follows `comments_notify`; notes still follow `wp_notes_notify` regardless of approval.
- Returning `true` now **sends** the notification, even for an unapproved comment.
- The default passed to the filter is now always a strict boolean. Previously the raw option value (e.g. the string `'1'`) could pass through, so callbacks doing strict comparison should compare against `true`/`false`.
- When the comment ID does not resolve to a valid comment, the function returns `false` immediately without applying the filter at all.

**Who breaks:** anything hooking `__return_true` onto `notify_post_author` now emails the author for moderated, spam, and trashed comments.

```php
add_filter(
	'notify_post_author',
	function ( $maybe_notify, $comment_id ) {
		$comment = get_comment( $comment_id );

		// Only force notifications for approved comments.
		if ( $comment && '1' === $comment->comment_approved ) {
			return true;
		}

		return $maybe_notify;
	},
	10,
	2
);
```

Callbacks that only suppress notifications (returning `false`) are unaffected.

---

## 8. `getEntityRecords()` returns all records for non-paginated entities

**Severity: medium.** Previously-truncated lists silently get longer.

Several REST endpoints ignore `page` and `per_page` and always return the whole collection. `getEntityRecords()` nonetheless applied client-side pagination to _every_ entity, slicing to the default `per_page` of 10 and discarding the rest. That was a bug.

Slicing now happens only for entities declaring `supportsPagination: true`; everything else returns the full collection. The common `per_page: -1` workaround is no longer necessary, though passing it stays harmless.

**What to check:** anywhere the result is rendered or looped without your own limit — a list that used to cap at 10 may now render hundreds of rows. Custom entities backed by a non-paginated REST route should declare `supportsPagination: false`.

---

## 9. Navigation block no longer propagates font-size to children

**Severity: low–medium.** Front-end typography can change.

The Navigation block no longer forcefully applies its font-size to `core/navigation-link`, `core/navigation-submenu`, `core/page-list`, and `core/home-link`. Relative units multiplied against each parent's computed size, compounding badly in nested dropdowns (1.5em → 2.25em → 3.375em). It now relies on standard CSS inheritance, which also fixes editor/front-end mismatch.

Themes targeting `has-{slug}-font-size` on nav items directly will see those classes disappear. To restore the legacy behavior:

```php
/**
 * Restores font size classes on Navigation child blocks.
 * Use this if your theme targets has-{slug}-font-size on nav items directly.
 */
function restore_nav_item_font_size( $block_content, $parsed_block, $block ) {
	$context = $block->context;

	$has_named_font_size  = array_key_exists( 'fontSize', $context );
	$has_custom_font_size = isset( $context['style']['typography']['fontSize'] );

	if ( ! $has_named_font_size && ! $has_custom_font_size ) {
		return $block_content;
	}

	$target_tag = 'core/page-list' === $block->name ? 'UL' : 'LI';

	$processor = new WP_HTML_Tag_Processor( $block_content );

	if ( ! $processor->next_tag() || $target_tag !== $processor->get_tag() ) {
		return $block_content;
	}

	if ( $has_named_font_size ) {
		$processor->add_class( sprintf( 'has-%s-font-size', $context['fontSize'] ) );
	} elseif ( $has_custom_font_size ) {
		$existing_style  = $processor->get_attribute( 'style' ) ?? '';
		$font_size_style = sprintf(
			'font-size: %s;',
			wp_get_typography_font_size_value(
				array( 'size' => $context['style']['typography']['fontSize'] )
			)
		);
		$processor->set_attribute( 'style', $existing_style . $font_size_style );
	}

	return $processor->get_updated_html();
}

add_filter( 'render_block_core/navigation-link', 'restore_nav_item_font_size', 10, 3 );
add_filter( 'render_block_core/navigation-submenu', 'restore_nav_item_font_size', 10, 3 );
add_filter( 'render_block_core/home-link', 'restore_nav_item_font_size', 10, 3 );
add_filter( 'render_block_core/page-list', 'restore_nav_item_font_size', 10, 3 );
```

Prefer fixing the theme CSS to rely on inheritance. The shim reinstates the compounding bug.

---

## 10. Block-level preset classes dropped to root-level specificity

**Severity: low.** Only affects `!important` author CSS.

Preset utility classes (`.has-*-color`, `-background-color`, `-border-color`, `-gradient-background`, `-font-size`, `-font-family`) generated for **block-level** presets — those from `theme.json` `settings.blocks.<block>` or the `wp_theme_json_data_*` filters — used to prepend the block selector, raising specificity above top-level presets. The block selector is now wrapped in `:where()`, contributing no specificity.

**Before:**

```css
.has-accent-color {
	color: … !important;
} /* top level:   0-1-0 */
p.has-accent-color {
	color: … !important;
} /* block level: 0-1-1 */
.wp-block-group.has-accent-color {
	color: … !important;
} /* block level: 0-2-0 */
```

**After:**

```css
:where(p).has-accent-color {
	color: … !important;
} /* 0-1-0 */
:where(.wp-block-group).has-accent-color {
	color: … !important;
} /* 0-1-0 */
```

Scoping is unchanged — the rule still only matches within that block. Top-level presets are unchanged.

**Why:** block-level presets out-ranking top-level ones was inconsistent and broke the responsive style states also landing in 7.1. Both use `!important`, so specificity decided the winner, and a block-level Desktop color beat a Mobile one. At equal specificity the responsive rule now wins on source order, as intended.

**Who breaks:** themes and plugins with `!important` author CSS written to slot _between_ a top-level and a block-level preset. Those rules now tie block-level presets at 0-1-0. Do not raise specificity to win it back — that reintroduces the responsive-states bug.

---

## 11. `pasteHandler()` Markdown parser swapped

**Severity: low.**

`@wordpress/blocks` replaced showdown with marked for parsing pasted Markdown. The parser is internal to `pasteHandler()` and was never exported, so no code changes are required. Output should be equivalent, but edge cases now follow the CommonMark and GFM specs. Code calling `pasteHandler()` directly should re-test representative Markdown against the blocks it produces.

---

## 12. Deprecated editor packages

**Severity: low.** Console warnings now, removal later.

- **`@wordpress/nux`** is now a no-op compatibility package. Deprecated since 5.4; imports and script dependencies still resolve, but it no longer displays tips or guides. Migrate to the `Guide` component from `@wordpress/components`.
- **`@wordpress/reusable-blocks`** public component and data APIs now log deprecation warnings. The package only ever exposed experimental APIs and core has not used it since 2023. To fetch or update Synced Patterns client-side, use the standard core entity methods. A no-op update like `nux` is planned.

---

## 13. Stabilized block functions

**Severity: low.** Console warnings.

`__experimentalCloneSanitizedBlock` and `__experimentalSanitizeBlockAttributes` in `@wordpress/blocks` / `wp.blocks` still work in 7.1 but now log deprecation messages. Rename to `cloneSanitizedBlock` and `sanitizeBlockAttributes`.

---

## 14. Cross-origin isolation on editor screens

**Severity: low**, but high impact where it lands.

When client-side media processing is active, WordPress sends `Document-Isolation-Policy: isolate-and-credentialless` on `load-post.php`, `load-post-new.php`, `load-site-editor.php`, and `load-widgets.php` for Chromium 137+. This is what makes `SharedArrayBuffer` available for the WASM image pipeline. Because DIP is per-document, it avoids the page-wide constraints of COOP/COEP.

Consequences for extenders:

- Cross-origin external scripts automatically receive `crossorigin="anonymous"`, via the server-side `wp_add_crossorigin_attributes()` output buffer plus a client-side `MutationObserver`. `<img>` is excluded, so external image previews are unaffected.
- DIP is skipped on admin pages with an `action` other than `edit`, keeping third-party page builders that rely on same-origin iframe access working.
- **Browser-side `fetch()` of remote media fails.** A cross-origin fetch is subject to CORS and fails in a credentialless isolated document. Plugins importing remote media should POST the image URL to the media endpoint (a new `url` parameter) and let the server download and sideload it, as core's "Upload to Media Library" now does.

To disable the whole feature:

```php
add_filter( 'wp_client_side_media_processing_enabled', '__return_false' );
```

Server-side hooks are unaffected: `wp_generate_attachment_metadata` still fires, once with context `'create'` during the initial upload and again with `'update'` after `POST /wp/v2/media/{id}/finalize`. Write those callbacks idempotently — the same double-fire pattern already applies to big-image uploads. Existing filters including `big_image_size_threshold`, `image_editor_output_format`, `wp_editor_set_quality`, and `jpeg_quality` continue to work.

**Browser support:** Chrome/Edge 137+ (Chrome on Android 146+). Firefox and Safari fall back to server-side processing automatically with no user-facing change — so a Chrome-only test does not exercise the fallback path.

---

## 15. Persistent toolbar in the Site Editor

**Severity: low.**

The admin toolbar now appears in both the Post and Site Editors by default, outside Distraction Free mode. The "W" logo is replaced by a dedicated back chevron, and the site icon (when set) appears in the toolbar.

The toolbar could already appear in the Post Editor with fullscreen mode off, but the Site Editor has no such mode, so a persistent toolbar there is genuinely new. If your plugin adds a toolbar node, verify it renders and behaves correctly in the Site Editor — particularly across client-side navigation, where a node assuming a full page load may not re-initialize.

To hide a node on editor screens:

```php
add_action(
	'admin_bar_menu',
	function ( WP_Admin_Bar $wp_admin_bar ) {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		// Hide the node in the Site Editor only.
		if ( $screen && 'site-editor' === $screen->id ) {
			return;
		}

		// ...or hide it in any block editor.
		if ( $screen && $screen->is_block_editor() ) {
			return;
		}

		$wp_admin_bar->add_node( /* … */ );
	},
	100
);
```

---

## 16. Smaller changes worth knowing

- **`_doing_it_wrong()` context** added to `WP_Block_Type_Registry::register()`, so registration failures now name the offending block.
- **`get_file_data()`** now recognizes headers prefixed by a `<?` tag, which can change which plugin/theme headers are detected in unusual file layouts.
- **`WP_REST_Attachments_Controller::get_attachment_filesize()`** no longer fails on non-integer metadata.
- **Pseudo-state styles no longer leak into the default state** — previously-leaking styles may now be missing from the base state.
- **`WP_Theme_JSON::to_ruleset()`** no longer performs implicit coercion.
- **`WP_Theme::get_post_templates()`** is faster on large themes (performance only).
- **Multisite signup/activation URLs** under SSL are fixed (`#65506`).
- **`_wp_personal_data_cleanup_requests()`** now runs on cron, and `WP_User_Request` returns the user ID as its documented type.
- **Abilities:** `execute_abilities()` now checks `is_ability_call()` before executing.
- **Query Loop** gains an option to exclude the current post.
- **"Show more comments"** is fixed for non-`comment` comment types.

## What did NOT change

Useful for scoping an upgrade — these were proposed for 7.1 and did not land:

- **The Classic block stays in the inserter.** A plan to hide it for new content was reverted.
- **React 19 was punted** beyond 7.1 and continues as a Gutenberg experiment.
- **Real-time collaboration** was heavily tested but is not enabled.
- **The "On This Day" widget** was excluded.
