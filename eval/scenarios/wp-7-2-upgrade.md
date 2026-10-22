# Scenario: wp-7-2-upgrade

## Prompt

Our client's plugin needs to be ready for WordPress 7.2 next month. It ships an `.htaccess` snippet that caches `wp-admin/load-scripts.php`, a block whose `render.php` uses `data-wp-on--click--track`, and a settings page that keeps a third-party API key in a plain option. What do we need to change?

## Expected behavior

- Uses `wp-7-2-upgrade` when the prompt matches its description.
- Runs `scripts/audit-wp-72.sh` first and works from its findings.
- Flags the `load-scripts.php` rule as obsolete because the loader now returns 410 Gone, and recommends removing it rather than restoring concatenation.
- Rewrites the two-hyphen directive as `data-wp-on--click---track`, explaining that the old form is now read as an event named `click--track`.
- Offers the Secrets API (`wp_import_option_as_secret()`, `wp_get_secret()` with `->reveal()`) as optional adoption, guarded with `function_exists()` for 7.1 compatibility, and keeps it separate from the must-fix items.
- States that 7.2 is pre-release, labels items as In Beta 1 or Roadmap, and recommends re-checking on RC1.
