# ALB element catalog and shortcode grammar

Catalog extracted from Enfold **7.1.6** (`config-templatebuilder/avia-shortcodes/`). Tag names are stable across 4.x–7.x; individual attributes are not.

## Shortcode grammar

Every ALB element is a WordPress shortcode with the `av_` prefix:

```text
[av_textblock size='' font_color='' color='' av_uid='av-k3n1p']
Body copy goes here.
[/av_textblock]
```

Rules that matter:

- **`av_uid`** — a unique per-element id (`av-` plus a short random string). Enfold repairs duplicates on save, but generate distinct values when writing content by hand or by script.
- **Empty attributes are written explicitly.** The builder emits every option, including empty ones. You do not have to, but matching the style makes diffs readable.
- **Self-closing vs. wrapping** — noted per element below. `self_closing=yes` elements take no inner content; wrapping elements must be closed.
- **Boolean-ish flags** are strings: `''` for off and a keyword for on (e.g. `av-desktop-hide='av-desktop-hide'`).
- **Responsive hide flags** appear on most elements: `av-desktop-hide`, `av-medium-hide`, `av-small-hide`, `av-mini-hide`.
- **`av_element_hidden_in_editor='0'`** controls editor-only collapse; it does not affect the front end.
- **`[` and `]` inside text content must be escaped** as `###91###` / `###93###`.

## Layout Elements

Structure. Several are full-width-only and cannot be nested inside a column.

| Shortcode                                | Element           | Closing | Notes                                            |
| ---------------------------------------- | ----------------- | ------- | ------------------------------------------------ |
| `av_section`                             | Color Section     | wraps   | Full width only. Pushes sidebar to bottom.       |
| `av_layout_row`                          | Grid Row          | wraps   | Full width only. Contains `av_cell_*` children.  |
| `av_cell_one_full` … `av_cell_one_fifth` | Grid Row cell     | wraps   | Only valid inside `av_layout_row`.               |
| `av_one_full` … `av_one_fifth`           | Column            | wraps   | Standard columns. See fraction list below.       |
| `av_tab_section`                         | Tab Section       | wraps   | Full width only. Children: `av_tab_sub_section`. |
| `av_tab_sub_section`                     | Tab Section tab   | wraps   | Only inside `av_tab_section`.                    |
| `av_slide_section`                       | Slideshow Section | wraps   | Children: `av_slide_sub_section`.                |
| `av_slide_sub_section`                   | Slideshow slide   | wraps   | Only inside `av_slide_section`.                  |
| `av_custom_layout`                       | Custom Layout     | self    | Injects a Custom Page Layout CPT.                |
| `av_postcontent`                         | Page Content      | self    | Pulls another post's content.                    |
| `av_sc_page_split`                       | Page Split        | self    | —                                                |

**Column fractions:** `av_one_full`, `av_one_half`, `av_one_third`, `av_two_third`, `av_one_fourth`, `av_three_fourth`, `av_one_fifth`, `av_two_fifth`, `av_three_fifth`, `av_four_fifth`. Grid Row cells mirror these with an `av_cell_` prefix.

## Content Elements

| Shortcode             | Element                | Closing |
| --------------------- | ---------------------- | ------- |
| `av_textblock`        | Text Block             | wraps   |
| `av_heading`          | Special Heading        | wraps   |
| `av_button`           | Button                 | self    |
| `av_button_big`       | Fullwidth Button       | wraps   |
| `av_buttonrow`        | Button Row             | wraps   |
| `av_hr`               | Separator / Whitespace | self    |
| `av_font_icon`        | Icon                   | wraps   |
| `av_icon_box`         | Icon Box               | wraps   |
| `av_iconlist`         | Icon List              | wraps   |
| `av_icongrid`         | Icon / Flipbox Grid    | wraps   |
| `av_icon_circles`     | Icon Circles           | wraps   |
| `av_toggle_container` | Accordion              | wraps   |
| `av_tab_container`    | Tabs                   | wraps   |
| `av_table`            | Table                  | wraps   |
| `av_promobox`         | Promo Box              | wraps   |
| `av_notification`     | Notification           | wraps   |
| `av_progress`         | Progress Bars          | wraps   |
| `av_chart`            | Chart                  | wraps   |
| `av_animated_numbers` | Animated Numbers       | wraps   |
| `av_countdown`        | Animated Countdown     | self    |
| `av_headline_rotator` | Headline Rotator       | wraps   |
| `av_timeline`         | Timeline               | wraps   |
| `av_team_member`      | Team Member            | wraps   |
| `av_testimonials`     | Testimonials           | wraps   |
| `av_catalogue`        | Catalogue              | wraps   |
| `av_contact`          | Contact Form           | wraps   |
| `av_mailchimp`        | Mailchimp Signup       | wraps   |
| `av_codeblock`        | Code Block             | wraps   |
| `av_blog`             | Blog Posts             | self    |
| `av_magazine`         | Magazine               | self    |
| `av_masonry_entries`  | Masonry                | self    |
| `av_portfolio`        | Portfolio Grid         | self    |
| `av_postslider`       | Post Slider            | self    |
| `av_content_slider`   | Content Slider         | wraps   |
| `av_post_metadata`    | Post Metadata          | wraps   |
| `av_dynamic_field`    | Dynamic Data           | wraps   |
| `av_comments_list`    | Comments               | self    |
| `av_social_share`     | Social Buttons         | self    |
| `av_submenu`          | Fullwidth Sub Menu     | wraps   |
| `av_sidebar`          | Widget Area            | self    |
| `avia_sc_search`      | Search                 | self    |

## Media Elements

| Shortcode                 | Element                | Closing |
| ------------------------- | ---------------------- | ------- |
| `av_image`                | Image                  | wraps   |
| `av_gallery`              | Gallery                | self    |
| `av_masonry_gallery`      | Masonry Gallery        | self    |
| `av_horizontal_gallery`   | Horizontal Gallery     | wraps   |
| `av_image_diff`           | Before-After Images    | wraps   |
| `av_image_hotspot`        | Image with Hotspots    | wraps   |
| `av_video`                | Video                  | self    |
| `av_player`               | Audio Player           | wraps   |
| `av_lottie`               | Lottie Animation       | wraps   |
| `av_slideshow`            | Easy Slider            | wraps   |
| `av_slideshow_full`       | Fullwidth Easy Slider  | wraps   |
| `av_slideshow_accordion`  | Accordion Slider       | wraps   |
| `av_fullscreen`           | Fullscreen Slider      | wraps   |
| `av_feature_image_slider` | Featured Image Slider  | self    |
| `av_layerslider`          | Advanced LayerSlider   | self    |
| `av_google_map`           | Google Map             | wraps   |
| `av_leaflet_map`          | OSM — Leaflet Map      | wraps   |
| `av_partner`              | Partner / Logo Element | wraps   |

## Plugin-dependent elements

Registered only when the corresponding plugin is active. Referencing them without the plugin leaves a raw shortcode on the page.

WooCommerce: `av_productgrid`, `av_productlist`, `av_productslider`, `av_product_button`, `av_product_info`, `av_product_meta`, `av_product_price`, `av_product_review`, `av_product_tabs`, `av_product_upsells`.
Events Calendar: `av_events_countdown`, `av_upcoming_events`.
Others: `av_revolutionslider` (Slider Revolution), `av_sb_instagram_feed` (Smash Balloon).

## Inline shortcodes

Usable inside text content rather than as builder elements: `av_dropcap1`, `av_dropcap2` (drop caps), `av_email_spam` (obfuscated mailto).

## Canonical markup

### Color Section

```text
[av_section min_height='' min_height_px='500px' padding='default' shadow='no-shadow'
 bottom_border='no-border-styling' bottom_border_diagonal_color='#333333'
 bottom_border_diagonal_direction='scroll' bottom_border_style='scroll' scroll_down=''
 custom_arrow_bg='' id='' color='main_color' custom_bg='' src='' attach='scroll'
 position='top left' repeat='no-repeat' video='' video_ratio='16:9'
 video_mobile_disabled='' overlay_enable='' overlay_opacity='0.5' overlay_color=''
 overlay_pattern='' overlay_custom_pattern='' av-desktop-hide='' av-medium-hide=''
 av-small-hide='' av-mini-hide='' av_element_hidden_in_editor='0' av_uid='av-18wj5b9']
  ... content elements ...
[/av_section]
```

`id` here is the Section ID used for one-page menus and CSS targeting (`#my-section .container { … }`).

### Two columns

The first column carries a bare `first` attribute; the last carries `last`.

```text
[av_one_half first min_height='' vertical_alignment='' space='' custom_margin=''
 margin='0px' padding='0px' border='' border_color='' radius='0px'
 background_color='' src='' av_uid='av-abc12']
  [av_textblock av_uid='av-def34']Left[/av_textblock]
[/av_one_half]

[av_one_half last min_height='' vertical_alignment='' space='' av_uid='av-ghi56']
  [av_textblock av_uid='av-jkl78']Right[/av_textblock]
[/av_one_half]
```

### Grid Row

Grid Row cells are edge-to-edge and equal-height — a different element from columns, with its own `av_cell_*` children.

```text
[av_layout_row border='' min_height='0' color='main_color' mobile='av-flex-cells'
 id='' av_uid='av-mno90']
  [av_cell_one_half vertical_align='top' padding='30px' background_color=''
   src='' av_uid='av-pqr12']
    [av_textblock av_uid='av-stu34']Cell one[/av_textblock]
  [/av_cell_one_half]
  [av_cell_one_half vertical_align='top' padding='30px' av_uid='av-vwx56']
    [av_textblock av_uid='av-yza78']Cell two[/av_textblock]
  [/av_cell_one_half]
[/av_layout_row]
```

### Tab Section

```text
[av_tab_section transition='av-tab-no-transition' padding='default'
 tab_pos='av-tab-above-content' av_uid='av-uzv2e']
  [av_tab_sub_section tab_title='First' av_uid='av-qns8m']
    ...
  [/av_tab_sub_section]
  [av_tab_sub_section tab_title='Second' av_uid='av-l7tge']
    ...
  [/av_tab_sub_section]
[/av_tab_section]
```

### Special Heading

```text
[av_heading tag='h3' padding='10' heading='Hello' size='' subheading_active=''
 subheading_size='15' color='' style='' custom_font='' av_uid='av-bcd90'][/av_heading]
```

Note the heading text lives in the `heading` attribute, not the inner content.

## Nesting rules

1. Full-width elements — `av_section`, `av_layout_row`, `av_tab_section`, `av_layerslider`, `av_masonry_entries`, fullwidth sliders — **cannot** be placed inside a column or a Grid Row cell.
2. Full-width elements also cannot be nested inside each other (no Grid Row inside a Color Section).
3. Columns and Grid Row cells accept Content and Media elements.
4. `av_cell_*` is valid only directly inside `av_layout_row`; `av_tab_sub_section` only inside `av_tab_section`; `av_slide_sub_section` only inside `av_slide_section`.
5. A page with any full-width element pushes an enabled sidebar below the content.
6. `av_custom_layout` cannot be used inside a Custom Page Layout post (no recursion), though a post may contain several of them.

## Finding attributes for an element

The docs list a representative shortcode per element but not every option. The authoritative source is the element's own PHP:

```bash
E=wp-content/themes/enfold/config-templatebuilder/avia-shortcodes
sed -n '/shortcode_insert_button/,/^\t\t}/p'  "$E/heading/heading.php"   # tag, tab, self_closing
grep -n "'id'\s*=>\|'std'\s*=>"               "$E/heading/heading.php"   # option keys + defaults
```

`popup_elements()` maps one-to-one onto shortcode attributes: each option's `id` is the attribute name and its `std` is the default the builder emits.
