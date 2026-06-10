# Block Directory Guidelines

Additional rules for Block Plugins submitted to the WordPress.org Block Directory.
Source: [wporg-plugin-guidelines/blocks.md](https://github.com/WordPress/wporg-plugin-guidelines/blob/trunk/blocks.md)

These apply **only** to plugins that are single-purpose Block Plugins (one top-level block, no UI outside the editor). All standard Plugin Directory Guidelines also apply.

---

## What qualifies as a Block Plugin

A Block Plugin:

- Contains only a single top-level block (with optional child blocks where a parent/child relationship is necessary)
- Has no UI outside the editor (no `wp-admin` menus, no options pages)
- Contains a minimum of server-side PHP code

If a plugin contains blocks plus other functionality, it does NOT qualify for the Block Directory — submit it to the main Plugin Directory instead.

---

## Block Directory checklist

### BD-1: Editor only, no admin UI

- No `add_menu_page()`, `add_options_page()`, `add_submenu_page()`, or equivalent
- No options pages, settings screens, or custom admin screens

```bash
grep -rn "add_menu_page\|add_options_page\|add_submenu_page\|add_dashboard_page" --include="*.php" . | grep -v "\.git"
```

### BD-2: Single block (or necessary parent/child)

- `block.json` (or `registerBlockType()`) should register only one top-level block
- If multiple blocks are registered, verify they represent a genuine parent/child dependency

```bash
find . -name "block.json" | grep -v node_modules | grep -v "\.git"
grep -rn "registerBlockType\|register_block_type" --include="*.php" --include="*.js" . | grep -v "\.git"
```

### BD-3: Plugin and block names reflect purpose

- Plugin title and block title should be identical or very similar
- Names must be descriptive of what the block does, not the company name
- Same trademark restrictions as standard plugin slugs apply

### BD-3a: Block name is unique and namespaced

- `block.json` `name` field must be properly namespaced (e.g., `my-plugin/my-block`)
- Namespace must reflect the plugin author or plugin slug
- Must not use reserved namespaces: `core`, `wordpress`

```bash
# Check block name/namespace
cat */block.json | grep '"name"'
# or
find . -name "block.json" -exec grep '"name"' {} \;
```

### BD-4: block.json is present and complete

Required fields in `block.json`:

- `name`
- `title`
- At least one of: `script`, `editorScript`
- At least one of: `style`, `editorStyle`

```bash
find . -name "block.json" | grep -v node_modules | grep -v "\.git" | head -5
# Then read each one to verify required fields
```

### BD-5: Works independently

- No hard dependency on another plugin or theme to function at all
- Optional enhancements from other plugins are acceptable

### BD-6: Works seamlessly (no friction on install)

- No account sign-up required before the block is usable
- No activation key or login step on first use
- Free to use — no payment required for the block itself
- Can use external API if usable without login or key

### BD-7: Minimal server-side PHP

- PHP code should be limited to block registration and metadata
- Business logic should use the REST API or client-side JS where possible
- Any server-side code must be clearly written and well-documented

### BD-8: No ads or promotional notices

- No `admin_notices`, dashboard widgets, or alerts unrelated to the block's purpose
- No upsell notices in the block editor

```bash
grep -rn "admin_notices\|add_action.*admin_notices" --include="*.php" . | grep -v "\.git"
```
