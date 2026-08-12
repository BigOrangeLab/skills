# WordPress 7.1 — Source Dev Notes

Every claim in this skill traces to one of these. Consult the primary source when a decision hinges on an exact signature or edge case.

**Field Guide:** [WordPress 7.1 Field Guide](https://make.wordpress.org/core/2026/08/05/wordpress-7-1-field-guide/) — Milana Cap, 5 August 2026.

**By the numbers:** 310+ Core Trac tickets (100+ enhancements, 180+ bug fixes), 40+ Editor tickets, ~600 Gutenberg enhancements and 630+ Gutenberg bug fixes. Focus areas: accessibility (46), UI (40), administration (28). Diff from 7.0.2 to 7.1-beta4: 20 new hooks (19 filters, 1 action), 1,480 files changed, 88,163 insertions, 18,601 deletions.

## Media

| Dev note                                                                                                                                                                              | Covers                                                                                   |
| ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------- |
| [Client-Side Media Processing in WordPress 7.1](https://make.wordpress.org/core/2026/07/22/client-side-media-processing-in-wordpress-7-1/)                                            | WASM upload pipeline, cross-origin isolation, new REST endpoints, HEIC/AVIF/GIF handling |
| [Media Library infinite scrolling enabled by default](https://make.wordpress.org/core/2026/07/23/media-library-infinite-scrolling-is-now-enabled-by-default-with-a-per-user-opt-out/) | `media_library_infinite_scrolling` default flip, per-user opt-out, precedence            |

Related tickets: [#64798](https://core.trac.wordpress.org/ticket/64798) (sideload dimension validation), [#65262](https://core.trac.wordpress.org/ticket/65262) (size-aware encode quality), [#65481](https://core.trac.wordpress.org/ticket/65481) (one sideloaded file under multiple sizes), [#65053](https://core.trac.wordpress.org/ticket/65053), [#65315](https://core.trac.wordpress.org/ticket/65315).

## Accessibility

| Dev note                                                                                                                                                  | Covers                                                           |
| --------------------------------------------------------------------------------------------------------------------------------------------------------- | ---------------------------------------------------------------- |
| [Post list tables row headers changed](https://make.wordpress.org/core/2026/08/03/post-list-tables-row-headers-changed/)                                  | `<th scope="row">` moved to the title column; selector migration |
| [Introducing name and informational tool tips](https://make.wordpress.org/core/2026/08/03/introducing-name-and-informational-tool-tips-in-wordpress-7-1/) | `wp_get_tooltip()`, `wp_get_toggletip()`, markup, enqueuing      |

Tooltip tickets: [#51006](https://core.trac.wordpress.org/ticket/51006) (the core mechanism — note the published post originally cited #55105 in error), [#55343](https://core.trac.wordpress.org/ticket/55343), [#50921](https://core.trac.wordpress.org/ticket/50921).

Other a11y tickets: [#64932](https://core.trac.wordpress.org/ticket/64932), [#65027](https://core.trac.wordpress.org/ticket/65027), [#65250](https://core.trac.wordpress.org/ticket/65250), [#65382](https://core.trac.wordpress.org/ticket/65382), [#65454](https://core.trac.wordpress.org/ticket/65454), [#47670](https://core.trac.wordpress.org/ticket/47670), [#65419](https://core.trac.wordpress.org/ticket/65419), [#65530](https://core.trac.wordpress.org/ticket/65530), [#65532](https://core.trac.wordpress.org/ticket/65532), [#65630](https://core.trac.wordpress.org/ticket/65630).

## Abilities API

| Dev note                                                                                                                                                                      | Covers                                                                             |
| ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------- |
| [Filtering registered abilities with `wp_get_abilities()`](https://make.wordpress.org/core/2026/08/05/filtering-registered-abilities-with-wp_get_abilities-in-wordpress-7-1/) | `$args`, callbacks, two global filters, REST discovery params                      |
| [New execution lifecycle filters](https://make.wordpress.org/core/2026/07/29/new-execution-lifecycle-filters-for-the-abilities-api-in-wordpress-7-1/)                         | The four execution filters, pipeline order, `WP_Filter_Sentinel`                   |
| [A unified public exposure flag](https://make.wordpress.org/core/2026/08/04/a-unified-public-exposure-flag-for-abilities-in-wordpress-7-1/)                                   | `public` meta flag and channel precedence                                          |
| [Abilities API improvements](https://make.wordpress.org/core/2026/07/31/abilities-api-improvements-in-wordpress-7-1/)                                                         | Validation filters, `wp_ability_invoked`, core ability changes, REST type coercion |
| [JSON Schema preparation for client compatibility](https://make.wordpress.org/core/2026/07/31/json-schema-preparation-for-client-compatibility-in-wordpress-7-1/)             | Schema normalization for external clients                                          |

Tickets: [#64990](https://core.trac.wordpress.org/ticket/64990), [#64989](https://core.trac.wordpress.org/ticket/64989), [#65568](https://core.trac.wordpress.org/ticket/65568), [#64311](https://core.trac.wordpress.org/ticket/64311), [#65248](https://core.trac.wordpress.org/ticket/65248), [#65234](https://core.trac.wordpress.org/ticket/65234), [#65355](https://core.trac.wordpress.org/ticket/65355), [#65504](https://core.trac.wordpress.org/ticket/65504).

## Global Styles

| Dev note                                                                                                                                                              | Covers                                                                                |
| --------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------- |
| [Responsive block styles and configurable viewports](https://make.wordpress.org/core/2026/08/05/responsive-block-styles-and-configurable-viewports-in-wordpress-7-1/) | `@mobile`/`@tablet`, `settings.viewport`, `responsiveEditingEnabled`                  |
| [Pseudo and custom style states](https://make.wordpress.org/core/2026/08/05/pseudo-and-custom-style-states-in-wordpress-7-1/)                                         | `:hover`/`:focus`/`:focus-visible`/`:active`, `-current`, `blockStatesEditingEnabled` |
| [Text Shadow Support in Global Styles](https://make.wordpress.org/core/2026/07/23/text-shadow-support-in-global-styles/)                                              | `styles.typography.textShadow`                                                        |

## Editor and blocks

| Dev note                                                                                                                                                         | Covers                                                                                                                                            |
| ---------------------------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------- |
| [Iframed Editor Changes in WordPress 7.1](https://make.wordpress.org/core/2026/08/03/iframed-editor-changes-in-wordpress-7-1/)                                   | Always-iframed post editor; `ownerDocument`/`defaultView`/`useRefEffect`                                                                          |
| [Editor components updates](https://make.wordpress.org/core/2026/07/23/editor-components-updates-in-wordpress-7-1/)                                              | 40px controls, Emotion→SCSS migration, `Navigation` and `__experimentalApplyValueToSides` removal                                                 |
| [Miscellaneous block editor changes](https://make.wordpress.org/core/2026/08/04/miscellaneous-block-editor-changes-in-wordpress-7-1/)                            | Nav font-size, variation transforms, stabilized functions, marked, preset specificity, core-data pagination, `nux`/`reusable-blocks` deprecations |
| [New Block Support: Background Gradient](https://make.wordpress.org/core/2026/07/26/new-block-support-in-wordpress-7-1-background-gradient-background-gradient/) | `supports.background.gradient`                                                                                                                    |
| [New Block Support: Minimum Width](https://make.wordpress.org/core/2026/07/26/new-block-support-in-wordpress-7-1-minimum-width/)                                 | `supports.dimensions.minWidth`                                                                                                                    |
| [Editable blocks inside the Custom HTML block](https://make.wordpress.org/core/2026/07/23/editable-blocks-inside-the-custom-html-block/)                         | `innerContent` on `core/html` variations                                                                                                          |
| [Registering and rendering SVG icons](https://make.wordpress.org/core/2026/07/24/registering-and-rendering-svg-icons-in-wordpress-7-1/)                          | `wp_register_icon()`, `wp_get_icon()`, REST routes, sanitization allowlist                                                                        |
| [Filtering Site Editor Screens](https://make.wordpress.org/core/2026/07/31/filtering-site-editor-screens-in-wordpress-7-1/)                                      | The four `get_entity_view_config_posttype_*` filters                                                                                              |
| [Design System Theming](https://make.wordpress.org/core/2026/07/31/design-system-theming-in-wordpress-7-1/)                                                      | `wp-theme` stylesheet/script, `--wpds-*` tokens, `ThemeProvider`                                                                                  |
| [Consistent navigation with persistent toolbar](https://make.wordpress.org/core/2026/07/13/consistent-navigation-in-wordpress-7-1-with-persistent-toolbar/)      | Toolbar in Post and Site Editors; hiding nodes per screen                                                                                         |

Editor tickets: [#64838](https://core.trac.wordpress.org/ticket/64838), [#65039](https://core.trac.wordpress.org/ticket/65039), [#65373](https://core.trac.wordpress.org/ticket/65373), [#65091](https://core.trac.wordpress.org/ticket/65091), [#65088](https://core.trac.wordpress.org/ticket/65088), [#32892](https://core.trac.wordpress.org/ticket/32892).

## External libraries and other

| Dev note                                                                                                                                                                              | Covers                                                               |
| ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | -------------------------------------------------------------------- |
| [jQuery UI updated to 1.14.2](https://make.wordpress.org/core/2026/07/29/jquery-ui-updated-to-1-14-2-in-wordpress-7-1/)                                                               | Removed APIs, `uiBackCompat`, dropped IE/Edge Legacy                 |
| [The `notify_post_author` filter now has the final say](https://make.wordpress.org/core/2026/08/05/the-notify_post_author-filter-now-has-the-final-say-on-post-author-notifications/) | Approval-aware default, strict boolean, invalid-comment early return |

Other tickets: [#42517](https://core.trac.wordpress.org/ticket/42517) (`get_file_data()` and `<?` prefixes), [#65506](https://core.trac.wordpress.org/ticket/65506) (multisite SSL URLs), [#44498](https://core.trac.wordpress.org/ticket/44498) and [#44723](https://core.trac.wordpress.org/ticket/44723) (privacy), [#65536](https://core.trac.wordpress.org/ticket/65536) (XML-RPC), [#65670](https://core.trac.wordpress.org/ticket/65670) (attachment filesize), [#42513](https://core.trac.wordpress.org/ticket/42513) (`get_post_templates()` performance), [#64848](https://core.trac.wordpress.org/ticket/64848) (`to_ruleset()` coercion), [#65049](https://core.trac.wordpress.org/ticket/65049), [#65392](https://core.trac.wordpress.org/ticket/65392), [#62757](https://core.trac.wordpress.org/ticket/62757) (jQuery UI).

## Deferred to a later release

| Dev note                                                                                                                                                                                                                                                                                 | Outcome                                                              |
| ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | -------------------------------------------------------------------- |
| [Hiding the Classic block from the inserter](https://make.wordpress.org/core/2026/06/23/hiding-the-classic-block-from-the-inserter-in-wordpress-7-1/) → [The Classic block stays](https://make.wordpress.org/core/2026/07/07/the-classic-block-stays-in-the-inserter-for-wordpress-7-1/) | Reverted; Classic block remains                                      |
| [React 19: punted beyond WordPress 7.1](https://make.wordpress.org/core/2026/07/24/react-19-punted-beyond-wordpress-7-1-experiment-in-gutenberg/)                                                                                                                                        | Continues as a Gutenberg experiment                                  |
| [Collaborative editing outreach effort](https://make.wordpress.org/core/2026/06/03/announcing-a-collaborative-editing-outreach-effort-for-7-1/)                                                                                                                                          | Tested but not enabled                                               |
| "On This Day" widget                                                                                                                                                                                                                                                                     | Excluded; see [#65801](https://core.trac.wordpress.org/ticket/65801) |
| [Merge Proposal: Guidelines built on Knowledge](https://make.wordpress.org/core/2026/06/22/merge-proposal-guidelines-built-on-knowledge/) · [Merge Proposal: Design System Theming](https://make.wordpress.org/core/2026/07/07/merge-proposal-design-system-theming/)                    | Foundations landed; broader proposals still evolving                 |

Also useful: [Roadmap to 7.1](https://make.wordpress.org/core/2026/06/19/roadmap-to-7-1/).

## Reported but undocumented

Raised in the Field Guide comments by Jason LeMahieu (MadtownLems) on 10 August 2026 and **not** covered by any dev note: block CSS output timing changed so that "the CSS for Cover Blocks is now only output when the page contains a Cover Block." Flagged as a possible breaking change for sites pulling content remotely — for example, fetching post content over REST and rendering it on another site where the block's stylesheet is no longer emitted. Verify independently before relying on either the old or new behavior.
