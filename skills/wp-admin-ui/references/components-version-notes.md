# `@wordpress/components` Version Notes

Breaking changes and deprecations in `@wordpress/components` between 33.1.0 and 43.0.0 that affect admin screens built with this skill. Most releases in this range only removed already-deprecated APIs this skill never recommended — the notes below are the ones that change behavior for code written against the current patterns. Notes for 39.0.0–43.0.0 come first; the 33.1.0–38.0.0 notes follow and still apply to screens upgrading from that range.

---

## React 19 (42.0.0)

42.0.0 moved the package to React 19 for good (the 35.0.0 revert to React 18 noted below no longer applies). The peer range stays `react` `^18 || ^19`, and screens that enqueue the core `wp-element` / `wp-components` handles get whatever React core ships. Code that bundles its own React needs to match, and anything still using APIs React 19 removed breaks: `ReactDOM.render` / `hydrate` / `unmountComponentAtNode` (use `createRoot` as in the main procedure), `findDOMNode`, string refs, and `propTypes` / `defaultProps` on function components.

## Components marked "not recommended" in favor of `@wordpress/ui` (41.0.0–42.0.0)

These still work and are **not** deprecated, but the docs now steer WordPress screens to `@wordpress/ui` equivalents:

| `@wordpress/components`       | `@wordpress/ui` replacement |
| ----------------------------- | --------------------------- |
| `SelectControl` (41.0.0)      | `SelectControl`             |
| `CheckboxControl` (42.0.0)    | `CheckboxControl`           |
| `RadioControl` (42.0.0)       | `RadioGroupControl`         |
| `ToggleControl`, `FormToggle` | `SwitchControl`, `Switch`   |
| `ProgressBar` (42.0.0)        | `Progress.*` (compound)     |
| `Notice` (42.0.0)             | `Notice.*` (compound)       |

`Notice` and `Progress` in `@wordpress/ui` are compound namespaces (`Notice.Root`, `Notice.Title`, `Notice.Description`, `Notice.Actions`; `Progress.Track`, `Progress.Indicator`, `Progress.Label`, `Progress.Value`), not drop-in single components. Checked against `@wordpress/ui` 0.25.0, which is pre-1.0, so expect API churn.

`@wordpress/ui` is **not** a core script handle (`wpScript: false`; there is no `wp-ui` to enqueue), so using it means bundling it into the plugin's build. For a screen that enqueues `wp-components` from core, keep using the components above. Reach for `@wordpress/ui` when the screen already bundles its dependencies and the extra weight is acceptable. Several of these components also restyled to match `@wordpress/ui` colors (hover borders, disabled states, toggle track), so check visual snapshots after upgrading.

## Deprecated, planned for removal in WordPress 7.4 (39.0.0–43.0.0)

`Animate`, `Surface` (39.0.0), `Divider`, `Elevation`, `Scrollable`, `ZStack`, `ResponsiveWrapper` (41.0.0), and `Tip` (43.0.0, use `Notice` from `@wordpress/ui`) now log deprecation warnings. Replace them with plain CSS (`aspect-ratio` for `ResponsiveWrapper`, `overflow` for `Scrollable`, an `<hr>` or border for `Divider`) before WordPress 7.4. The `Elevation` _component_ is unrelated to the `$elevation-*` SCSS tokens in [design-tokens.md](design-tokens.md), which are unaffected.

## Emotion removed (39.0.0–43.0.0)

Components moved off Emotion over several releases and 43.0.0 removed it entirely, including `StyleProvider`'s cross-document Emotion cache. This only matters to screens that style with `@emotion/react` / `@emotion/styled` themselves: compose source-order-dependent fragments in a single `css()` call, and provide your own `CacheProvider` for iframes. Screens styled with plain CSS or SCSS, as this skill recommends, are unaffected.

## Build tooling needs Node.js 22.13+ (43.0.0)

43.0.0 raised `engines.node` to `>=22.13.0` (for stable `require(esm)`). This affects the plugin's build only, not the browser. Pin `@wordpress/components@^42` if the build cannot move to Node 22.13+.

---

## `__next40pxDefaultSize` is now the default (36.0.0+)

Sizeable controls (`Button`, `TextControl`, `SelectControl`, and similar form controls) used to default to a compact 36px size unless you opted in to the 40px size with `__next40pxDefaultSize`. As of `@wordpress/components` 36.0.0, the 40px size **is the default** and the prop is a no-op kept only for API compatibility.

If an existing screen relied on the old 36px default and never passed the prop, its controls will render larger after upgrading. Check screens with dense forms or tight layouts for overflow after bumping `@wordpress/components` past 35.x.

## `ExternalLink` no longer sets `rel` automatically (37.0.0)

`ExternalLink` used to add `rel="external noreferrer noopener"` by default. As of 37.0.0, callers must pass `rel` explicitly if they need it. Audit any admin screen using `ExternalLink` for outbound links and add the attribute back where the `noopener`/`noreferrer` protection matters.

## `Notice` internal DOM structure changed (34.0.0)

The `Notice` component (the React component, not core's PHP-rendered `.notice` markup covered in the main procedure) reworked its internal DOM and dropped the `is-dismissible` class, moving the actions wrapper to be a sibling of the content rather than a child. Custom CSS that targeted `.components-notice.is-dismissible` or drilled into the old child structure needs updating.

## `Navigation` component family removed (34.0.0)

The deprecated `Navigation` / `NavigationMenu` components were fully removed in 34.0.0. If a legacy screen still imports them, migrate to the current sidebar/menu patterns before upgrading past 33.x.

## Not relevant to this skill

`BoxControl`'s removed experimental export (34.0.0), the removed private `DateCalendar` / `DateRangeCalendar` / `TZDate` / `ValidatedTextControl` exports (39.0.0), and the temporary React 19→18 revert (35.0.0, superseded by 42.0.0) don't change any pattern this skill recommends.
