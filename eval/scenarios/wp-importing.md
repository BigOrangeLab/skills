# Scenario: wp-importing

## Prompt

We have a JSON export from our old headless CMS with a few thousand entries. Help me get it into WordPress as posts, custom post types, and taxonomy terms.

## Expected behavior

- Uses `wp-importing` when the prompt matches its description.
- Profiles the source data before writing mapping code.
- Builds in `WP_IMPORTING`, a dry-run mode, a limit argument, and an idempotency check.
- Rehearses on a disposable environment before touching production.
- Follows the skill procedure and verifies results.
