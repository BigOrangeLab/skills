# Security Review Checklist

Expanded checklist for each category in the review procedure. Use alongside the SKILL.md priority order.

---

## SQL safety and prepared queries

- All `$wpdb->query()`, `$wpdb->get_*()`, `$wpdb->prepare()` calls use `%s`/`%d`/`%f` placeholders — no string interpolation or concatenation of user input
- `$wpdb->prepare()` is not called with a pre-formatted string (double-prepare vulnerability)
- No raw SQL built from `$_GET`, `$_POST`, `$_REQUEST`, `$_COOKIE`, or `$_SERVER` values
- Custom table names use `$wpdb->prefix`, not hardcoded strings

## Escaping and output handling

- All HTML output passes through `esc_html()`, `esc_attr()`, `esc_url()`, `esc_js()`, or `wp_kses()`/`wp_kses_post()` as appropriate
- `echo` or `print` of translated strings uses `esc_html_e()` / `esc_attr_e()` — not `_e()` on user-controlled values
- JSON output uses `wp_json_encode()`, not `json_encode()` directly
- No raw `echo $_GET[...]` or equivalent
- **WPCS 3.3.0+:** `wp_kses_allowed_html()` is no longer treated as an escaping function by WPCS sniffs. Code that passes its return value directly into `wp_kses()` is still correct, but code that used it as a standalone escape (relying on WPCS not flagging it) may now produce warnings. Verify that all `wp_kses()` call sites explicitly pass the allowed-tags array or a `wp_kses_allowed_html()` call as the second argument.

## Input sanitization and validation

- `$_GET`, `$_POST`, `$_REQUEST`, `$_COOKIE` values pass through `sanitize_text_field()`, `absint()`, `sanitize_email()`, `sanitize_url()`, or the appropriate typed sanitizer before use
- File paths derived from input pass through `realpath()` and are validated against an allowed base directory
- Array inputs are iterated and each element sanitized — not bulk-cast

## Nonces and capability checks

- Every admin form, AJAX handler, and REST action that mutates state verifies a nonce with `wp_verify_nonce()` or `check_ajax_referer()` before processing
- Nonces are scoped to the action (not reused across unrelated handlers)
- Every privileged action checks `current_user_can()` before executing
- Capability checks use the minimum required capability, not always `manage_options`

## REST API `permission_callback` coverage

- Every `register_rest_route()` call has a `permission_callback` that is not `__return_true` unless truly public
- Public endpoints explicitly document why they are public (comment or README note)
- Schema args include `sanitize_callback` and `validate_callback` where user input flows in

## Block editor Client-Side Abilities (WP 7.0+)

WordPress 7.0 introduced the Client-Side Abilities API, which lets blocks declare editor capabilities via a `permissionCallback` on the server and consume them in JS without exposing raw capability names.

- Every `register_block_type()` call that exposes editor-gated actions defines a `permissionCallback` on the server — not raw `current_user_can()` checks baked into REST routes
- `permissionCallback` closures receive the full `WP_REST_Request` context; verify they don't fall back to `return true` in edge cases (e.g., missing post ID, anonymous request)
- Client-side JS consuming ability results must not assume a `true` result persists — re-check on state changes (post status change, user role switch in multisite)
- JSON schema args on block-registered REST routes follow the same `sanitize_callback` / `validate_callback` rules as hand-registered routes

## File access, upload, and deserialization risks

- Uploaded files are validated with `wp_check_filetype()` and stored outside the web root or with proper access controls when sensitive
- No `unserialize()` on user-controlled input; prefer `json_decode()` for serialized data
- `include`/`require` paths are not derived from user input
- No use of `eval()` or `preg_replace` with `/e` modifier

## Deprecated APIs and compatibility issues

- No calls to removed functions (check against target WordPress version)
- `wp_enqueue_script()` deps array is accurate — no silent `$` / jQuery conflicts
- PHP compatibility: no `match`, `readonly`, named arguments, or other syntax features unsupported by the target PHP floor

## Transients and object cache

- Sensitive data (tokens, API keys, PII) is not stored in transients without encryption — transients live in `wp_options` by default and are world-readable by anyone with DB access
- Transient keys derived from user input pass through `sanitize_key()` / `md5()` to prevent option-table injection
- Cache keys are namespaced to avoid cross-feature collisions when a persistent object cache is present
- Cached values are not trusted as sanitized — re-validate on read if the value will be output or used in a query

## Performance risks

- `WP_Query` loops do not fetch posts with `posts_per_page => -1` without a clear bound
- No `get_option()` / `update_option()` with large serialized blobs set to autoload
- No unbounded hook registrations inside loops (e.g., `add_filter()` called on every post iteration)
- No synchronous HTTP requests (`wp_remote_get()`) in the critical path without caching
