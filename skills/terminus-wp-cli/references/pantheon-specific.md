# Pantheon-Specific WP-CLI Commands

These commands are provided by Pantheon's mu-plugins and are available on all Pantheon-hosted WordPress sites.

## Page Cache

Requires the [Pantheon Advanced Page Cache](https://wordpress.org/plugins/pantheon-advanced-page-cache/) plugin.

```bash
# Purge the entire page cache
terminus remote:wp <site>.live -- pantheon cache purge-all

# Purge by surrogate key (comma-separated)
terminus remote:wp <site>.live -- pantheon cache purge-key "post-42,term-7"

# Purge by path
terminus remote:wp <site>.live -- pantheon cache purge-path "/blog/my-post/"

# Set maintenance mode
terminus remote:wp <site>.live -- pantheon cache set-maintenance-mode disabled
# Modes: disabled | anonymous | everyone
```

`purge-key` accepts the surrogate keys Pantheon assigns to cached responses. Common key patterns:
- `post-<ID>` — all pages that include a given post
- `term-<ID>` — all pages that include a given term
- `user-<ID>` — pages associated with a given user

## PHP Sessions

Requires the [WP Native PHP Sessions](https://wordpress.org/plugins/wp-native-php-sessions/) plugin.

```bash
terminus remote:wp <site>.live -- pantheon session list
terminus remote:wp <site>.live -- pantheon session delete
```

## Further reading

- [Pantheon Advanced Page Cache plugin](https://wordpress.org/plugins/pantheon-advanced-page-cache/)
- [WP Native PHP Sessions plugin](https://wordpress.org/plugins/wp-native-php-sessions/)
- [Pantheon pantheon.yml Reference](https://docs.pantheon.io/pantheon-yml)
