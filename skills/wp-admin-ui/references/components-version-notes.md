# `@wordpress/components` Version Notes

Breaking changes in `@wordpress/components` between 33.1.0 and 38.0.0 that affect admin screens built with this skill. Most releases in this range only removed already-deprecated APIs this skill never recommended — the notes below are the ones that change behavior for code written against the current patterns.

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

Emotion-internals changes (36.0.0, `css()` fragment handling for `Divider`), `BoxControl`'s removed experimental export (34.0.0), and the temporary React 19→18 revert (35.0.0) don't change any pattern this skill recommends.
