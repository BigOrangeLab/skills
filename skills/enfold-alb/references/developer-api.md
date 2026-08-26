# ALB developer API

All snippets belong in a **child theme** `functions.php`. Verified against Enfold 7.1.6.

## Post meta and status API

| Meta key                       | Value                                         |
| ------------------------------ | --------------------------------------------- |
| `_aviaLayoutBuilder_active`    | `'active'` or `''`                            |
| `_aviaLayoutBuilderCleanData`  | Canonical ALB shortcode source                |
| `_avia_builder_shortcode_tree` | Cached parsed shortcode tree, rebuilt on save |

Prefer the builder API to raw meta writes:

```php
global $avia_config;
$builder = $avia_config['builder'];              // AviaBuilder instance

$builder->get_alb_builder_status( $post_id );     // 'active' | ''
$builder->set_alb_builder_status( 'active', $post_id );
$builder->get_posts_alb_content( $post_id );      // filtered via 'avf_posts_alb_content'
$builder->save_posts_alb_content( $post_id, $shortcode_string );
```

`AviaHelper::builder_status( $post_id )` is the convenience wrapper used throughout the theme.

### Writing ALB content programmatically

Keep `post_content` and `_aviaLayoutBuilderCleanData` identical, and let the builder normalise the shortcodes rather than trusting your own string:

```php
$content = ShortcodeHelper::clean_up_shortcode( $raw, 'balance_only' );

wp_update_post( [ 'ID' => $post_id, 'post_content' => $content ] );
update_post_meta( $post_id, '_aviaLayoutBuilderCleanData', $content );
update_post_meta( $post_id, '_aviaLayoutBuilder_active', 'active' );
delete_post_meta( $post_id, '_avia_builder_shortcode_tree' );   // force a rebuild
```

`clean_up_shortcode()` balances opening/closing tags; it does not invent missing attributes. Generate a distinct `av_uid` for every element you emit.

Back up the database before any bulk rewrite. There is no undo.

## Builder mode

```php
add_action( 'avia_builder_mode', fn() => 'debug' );
```

Returning `'debug'` adds a live shortcode textarea below the builder. Editing it is unvalidated — a stray bracket destroys the layout on save. Leave debug off on production.

## Enabling ALB on custom post types

```php
add_filter( 'avf_alb_supported_post_types', function ( array $types ) {
    $types[] = 'my_cpt';
    return $types;
} );

// Layout meta box (sidebar/layout controls) on the same CPT
add_filter( 'avf_metabox_layout_post_types', function ( array $types ) {
    $types[] = 'my_cpt';
    return $types;
} );
```

## Forcing ALB and hiding the editor switch

```php
add_filter( 'avf_force_alb_usage', function ( $force_alb, $post ) {
    if ( ! $post instanceof WP_Post ) {
        return $force_alb;
    }
    if ( 'post' === $post->post_type ) {
        $force_alb = true;
    }
    return $force_alb;
}, 10, 2 );
```

Forced posts get the `avia-force-alb` class and the switch button is hidden with CSS. This also applies under the block editor. **Existing classic-editor posts of that type will appear empty** — gate on something narrower (a meta flag, a date, an existing `_aviaLayoutBuilder_active`) when the post type has legacy content.

Related: `avf_builder_active` filters the resolved status for a single post (used by Enfold's own WooCommerce config to suppress ALB on product pages).

## Developer options (custom class, ID, heading tag, ARIA label)

Toggle globally at _Enfold → Layout Builder → General Builder Options_. Per-setting control since 4.6.4:

```php
add_filter( 'avf_alb_get_developer_settings', function ( $value, $setting, $option_value ) {
    switch ( $setting ) {
        case 'custom_css':   $value = 'developer_options';         break;
        case 'custom_id':    $value = 'developer_id_attribute';    break;
        case 'heading_tags': $value = 'developer_seo_heading_tags'; break;
        case 'aria_label':   $value = 'developer_aria_label';      break;
        case 'alb_desc_id':  $value = 'hide';                      break;
        default:             $value = false;
    }
    return $value;
}, 10, 3 );
```

Return values: the named constant shows the field and uses its value; `'hide'` hides the field but still honours stored values; `'deactivate'` hides _and_ discards them.

On Enfold before 4.1 the custom-class field requires `add_theme_support( 'avia_template_builder_custom_css' );`.

## Adding or overriding an element

### 1. Register a child-theme shortcodes directory

```php
add_filter( 'avia_load_shortcodes', 'avia_include_shortcode_template', 15, 1 );
function avia_include_shortcode_template( $paths ) {
    array_unshift( $paths, get_stylesheet_directory() . '/shortcodes/' );
    return $paths;
}
```

`array_unshift` puts the child path first, so a file of the same name **replaces** the parent's element. Copy the folder you want to change out of `enfold/config-templatebuilder/avia-shortcodes/` into `<child>/shortcodes/` and edit there.

### 2. Element class contract

```php
class my_sc_widget extends aviaShortcodeTemplate {

    protected function shortcode_insert_button() {
        $this->config['version']      = '1.0';
        $this->config['self_closing'] = 'no';          // 'yes' for content-less elements
        $this->config['base_element'] = 'yes';
        $this->config['name']         = __( 'My Widget', 'avia_framework' );
        $this->config['tab']          = __( 'Content Elements', 'avia_framework' );
        $this->config['icon']         = AviaBuilder::$path['imagesURL'] . 'sc-text_block.png';
        $this->config['order']        = 90;            // sort position within the tab
        $this->config['target']       = 'avia-target-insert';
        $this->config['shortcode']    = 'my_widget';   // the shortcode tag
        $this->config['tooltip']      = __( 'Does a thing', 'avia_framework' );
    }

    protected function popup_elements() {
        // Each entry's 'id' becomes a shortcode attribute; 'std' is its default.
        $this->elements = [
            [
                'name' => __( 'Label', 'avia_framework' ),
                'id'   => 'label',
                'type' => 'input',
                'std'  => 'Click me',
            ],
        ];
    }

    public function shortcode_handler( $atts, $content = '', $shortcodename = '', $meta = '' ) {
        $atts = shortcode_atts( [ 'label' => '' ], $atts, $this->config['shortcode'] );
        return '<div class="my-widget">' . esc_html( $atts['label'] ) . '</div>';
    }
}
```

Defining `popup_elements()` is what gives the element an edit button and modal. Omit it for an element with no options.

For nested elements, declare children with `$this->config['shortcode_nested'] = [ 'my_widget_item' ];`.

Escape output in `shortcode_handler()` — it runs on the front end with unvalidated attribute values.

## Custom Element Templates (CET)

Predefined element presets, managed under _Enfold → Custom Elements_. A CET is a normal element whose default attributes have been overridden.

- Create: _Custom Elements_ tab → **Add New Custom Element** → pick a base element, name it, set options, save. It then appears in the builder as a draggable element (highlighted sky blue on the canvas).
- Editing a CET affects only **newly added** instances — unless the option is **locked**.
- **Locking** an option (the padlock beside it in the CET modal) removes it from the per-instance modal and propagates its value to every existing instance on next page load. This is the mechanism for site-wide consistency.
- Settings: _Custom Elements Management_ controls who may edit; _Custom Elements Locked Options_ controls whether locked values are visible.

Clear the server cache after changing a locked option — existing pages re-render from cached CSS otherwise.

## Custom Layout and Dynamic Content

Enable at _Enfold → Layout Builder → Custom Layout And Dynamic Content_.

**Custom Page Layout** is a CPT holding a reusable ALB layout, injected into posts via the `av_custom_layout` element. Dynamic fields inside it resolve against the _host_ post. The host post type must support ALB and the post must actually use ALB. A Custom Page Layout may not contain another one, but a post may contain several.

Restrict who can create them with `avf_custom_layout_show_wp_menus`; filter the post-type dropdown with `avf_custom_layout__post_types`.

### Dynamic content syntax

Full form, usable in `the_content` areas and ALB shortcode content:

```text
[av_dynamic_el src="" key="" default="" link="" linktext="" format=""]
```

**Inside an ALB modal input field, use curly braces instead** — square brackets break the builder:

```text
{av_dynamic_el src="wp_custom_field" key="my_link" default="/" link="blank" linktext="Homepage" format="link"}
```

Shorthand, resolved at page load:

```text
{wp_post_ID}   {wp_post_title}   {wp_post_date}
{wp_custom_field:attribute_color}
```

Shorthand returns a raw string. Use the full shortcode form (or the `avf_custom_field_format` filter) when you need formatting, links, or fallbacks.

Valid `src` values are the keys of `$this->shortcodes` in
`enfold/config-templatebuilder/avia-template-builder/php/class-dynamic-content.php` → `register_dynamic_data_sources()`. Extend the list with `avf_register_dynamic_data_sources`.

Constraints:

- `post_content` is unavailable as a dynamic source (circular reference).
- A custom field used as an image must contain a valid attachment ID, and a fallback image must be selected.
- A custom field used as a gallery must contain a comma-separated list of attachment IDs.
- Shortcodes stored inside custom fields can produce illegal same-name nesting. Avoid.
- In the Table element, dynamic data requires the **Dynamic Data Row** option.

## Useful filters

| Filter                              | Purpose                                        |
| ----------------------------------- | ---------------------------------------------- |
| `avia_builder_mode`                 | `'debug'` to expose the raw shortcode textarea |
| `avia_load_shortcodes`              | Register child-theme element directories       |
| `avf_alb_supported_post_types`      | Add post types the builder may run on          |
| `avf_metabox_layout_post_types`     | Add post types getting the layout meta box     |
| `avf_force_alb_usage`               | Force ALB and hide the editor switch           |
| `avf_builder_active`                | Filter resolved ALB status for one post        |
| `avf_posts_alb_content`             | Filter the stored ALB source when read         |
| `avf_template_builder_content`      | Filter builder content during render           |
| `avf_alb_get_developer_settings`    | Per-field control of developer options         |
| `avf_register_dynamic_data_sources` | Add dynamic-content sources                    |
| `avf_custom_layout__post_types`     | Restrict Custom Page Layout target post types  |
| `avia_builder_precompile`           | Rewrite shortcode attributes before render     |
| `avf_alb_element_animation`         | Adjust element animation handling              |

Per-element filters follow `avf_<element>_<thing>` (`avf_default_container_tag_textblock`, `avf_content_slider_defaults`, `avf_chartjs_config_object`, …). Enumerate them for a given element with:

```bash
grep -rhoE "apply_filters\( *'avf_[a-z_0-9]*'" \
  wp-content/themes/enfold/config-templatebuilder/avia-shortcodes/<element>/
```
