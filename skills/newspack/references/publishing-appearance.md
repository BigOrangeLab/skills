# Newspack Publishing & Appearance Reference

Comprehensive reference for Newspack publishing features, themes, blocks, homepage management, guest contributors, federated sites, and plugin management. Drawn from official Newspack Publisher Support documentation.

---

## 1. Newspack Themes

_Docs: [help.newspack.com/publishing-appearance/themes/](https://help.newspack.com/publishing-appearance/themes/) · [help.newspack.com/plugins-themes/newspack-themes/](https://help.newspack.com/plugins-themes/newspack-themes/) · Repos: [newspack-theme](https://github.com/Automattic/newspack-theme) (classic) · [newspack-block-theme](https://github.com/Automattic/newspack-block-theme) (FSE)_

### Available Themes

Newspack ships one parent theme and five named child themes. All six live under `Appearance > Themes` and share a common set of customization capabilities. A separate block theme is also available for full-site editing.

| Theme                  | Typography                                         | Distinctive Design Elements                                                           |
| ---------------------- | -------------------------------------------------- | ------------------------------------------------------------------------------------- |
| **Newspack** (parent)  | System fonts (headers and body)                    | Underlined headers on Content Loop blocks; button-style tertiary nav menu             |
| **Newspack Joseph**    | Old Standard TT (headers), EB Garamond (body)      | Hairline border framing; classic broadsheet newspaper aesthetic                       |
| **Newspack Katharine** | Barlow (headers and body)                          | Rectangular accents above section titles; dotted borders; overlapping featured images |
| **Newspack Nelson**    | Montserrat (headers)                               | Overlap-style site header; chunky pullquotes and styled separators                    |
| **Newspack Sacha**     | IBM Plex Serif (headers), serif system font (body) | Centered Content Loop headers with flanking side borders; elegant editorial look      |
| **Newspack Scott**     | Fira Sans Condensed (headers), serif body          | Boxy/blocky accents; square avatars; section title boxy accents                       |

Each child theme is named after a notable journalist or media figure: Joseph Pulitzer, Katharine Graham, Nelson Poynter, Sacha Pfeiffer, and John Scott (Scott Trust / The Guardian).

**Live demo staging sites** are available for each theme — preview before committing. Switching themes after launch alters typography, layout, and block styling sitewide across all existing content.

### Newspack Block Theme (FSE)

_Repo: [github.com/Automattic/newspack-block-theme](https://github.com/Automattic/newspack-block-theme)_

A separate **Newspack Block Theme** supports full-site editing (FSE). It is incompatible with the Customizer-based classic child theme model — do not confuse the two systems.

**Entry point:** `Appearance > Editor` (Site Editor), not `Appearance > Customize`.

#### theme.json defaults

| Setting          | Value                                                                                                                                  |
| ---------------- | -------------------------------------------------------------------------------------------------------------------------------------- |
| Content width    | 632px                                                                                                                                  |
| Wide width       | 1296px                                                                                                                                 |
| Base font        | Inter (UI sans-serif, weights 100–900)                                                                                                 |
| Monospace font   | JetBrains Mono (weights 100–800)                                                                                                       |
| Named colors     | 11 palette entries: Primary (#003DA5), Secondary (#2055B0), Tertiary (#9FB6DD), White, Light/Medium/Dark grays, plus contrast variants |
| Spacing presets  | 7 presets (0.5rem through clamp-based values)                                                                                          |
| Appearance Tools | Enabled                                                                                                                                |

#### Style variations

11 pre-built design variations selectable from `Appearance > Design` (or Site Editor > Styles):

| Slug              | Character                 |
| ----------------- | ------------------------- |
| `01-theme-alt`    | Alternate default palette |
| `02-arc`          | Arc                       |
| `03-arc-alt`      | Arc alternate             |
| `04-bulletin`     | Bulletin                  |
| `05-bulletin-alt` | Bulletin alternate        |
| `06-foundry`      | Foundry                   |
| `07-foundry-alt`  | Foundry alternate         |
| `08-ledger`       | Ledger                    |
| `09-ledger-alt`   | Ledger alternate          |
| `10-nocturne`     | Dark/nocturne palette     |
| `11-nocturne-alt` | Nocturne alternate        |

Each variation overrides colors and typography from `theme.json`. Custom style variations can be added by placing a JSON file in a child theme's `styles/` directory.

#### Templates (13 + subdirectories)

| Template                  | Purpose                                             |
| ------------------------- | --------------------------------------------------- |
| `index`                   | Fallback / default                                  |
| `front-page`              | Static front page                                   |
| `home`                    | Blog/posts index                                    |
| `single`                  | Single post (+ `single/` subdirectory for variants) |
| `page`                    | Static page (+ `page/` subdirectory for variants)   |
| `archive`                 | Generic archive                                     |
| `author`                  | Author archive                                      |
| `search`                  | Search results                                      |
| `404`                     | Not found                                           |
| `single-newspack_nl_cpt`  | Single newsletter post                              |
| `archive-newspack_nl_cpt` | Newsletter archive                                  |

Override templates in a child block theme by placing a same-named file in the child's `templates/` directory.

#### Template parts (12 files)

| Part                                          | Notes                   |
| --------------------------------------------- | ----------------------- |
| `header` / `header-desktop` / `header-mobile` | Responsive header split |
| `footer` / `footer-desktop` / `footer-mobile` | Responsive footer split |
| `post-footer`                                 | Per-post footer content |
| `sidebar-contents`                            | Sidebar region          |
| `comments-contents` / `comments-menu`         | Comments area           |
| `search-contents` / `search-menu`             | Search area             |

#### Patterns

Patterns are registered via PHP and organized into categories:

`author-bio`, `footer`, `header`, `plugins`, `post-header`, `post-meta`

Plus three standalone patterns: `404.php`, `comments.php`, `hidden-no-results-content.php`.

#### Custom subtitle block

The theme registers one custom block — a **subtitle field** tightly coupled to post metadata. It is distinct from the Newspack Blocks plugin's custom blocks. Custom blocks for Newspack should live in the `newspack-plugin` or `newspack-blocks` packages, not in the theme.

#### Customization approach

| Need                        | Method                                                                             |
| --------------------------- | ---------------------------------------------------------------------------------- |
| Colors, typography, spacing | Edit global styles in Site Editor, or override `theme.json` in a child block theme |
| Switch design direction     | Apply a style variation (`Appearance > Design`)                                    |
| Add/edit page layouts       | Edit templates in Site Editor or create files in child `templates/`                |
| Reusable layout sections    | Create or edit patterns in Site Editor or child `patterns/`                        |
| CSS overrides               | Additional CSS in Site Editor, or `style.css` in a child block theme               |

CSS variables and utility classes are documented in cheat sheets in the repository root.

#### Child block theme

Because the Block Theme uses FSE, a child theme is a standard WordPress child block theme:

```text
child-theme/
  style.css        (with Template: newspack-block-theme header)
  theme.json       (overrides only — deep-merged with parent)
  templates/       (override or add templates)
  parts/           (override or add template parts)
  patterns/        (add patterns)
  styles/          (add style variations as JSON)
```

No PHP is required for purely visual customizations.

### Shared Customization Options (Customizer)

All classic themes (parent + five child themes) share these customization options, accessed via `Appearance > Customize`:

| Setting                        | Notes                                                  |
| ------------------------------ | ------------------------------------------------------ |
| Typography                     | Google Fonts defaults per theme; overridable           |
| Color palettes                 | Custom color support across all themes                 |
| Header configuration           | Includes overlap header in Nelson                      |
| Custom menus                   | Includes button-style tertiary nav in the parent theme |
| Featured image behavior        | Display options for featured images                    |
| Widget areas                   | Sidebar and footer widget regions                      |
| Footer settings                | Footer layout and content                              |
| Post templates                 | Template selection per post type                       |
| Page templates                 | Template selection per page                            |
| Archive settings               | Archive page layout options                            |
| Custom CSS                     | Inline CSS override entry point                        |
| WooCommerce display options    | Storefront display configuration                       |
| Sponsored content label colors | `Appearance > Customize > Sponsored Content`           |
| Yoast breadcrumbs              | Breadcrumb display options                             |
| Newspack image sizes           | Custom image size registration                         |

### theme.json (Block Theme)

The Newspack Block Theme exposes settings and styles via `theme.json`. Use the Site Editor (`Appearance > Editor`) for no-code customization. For code-level overrides, edit or extend `theme.json` in a child block theme. The classic child themes do not use `theme.json` — their typography and colors are managed via the Customizer and PHP.

---

## 2. Newspack-Specific Blocks

_Docs: [help.newspack.com/publishing-appearance/blocks/](https://help.newspack.com/publishing-appearance/blocks/)_

All custom blocks are provided by the **Newspack Blocks** plugin. They appear in the block inserter under the Newspack section.

### Content Loop Block

**Purpose**: Primary block for displaying multiple posts in configurable list or grid layouts. The workhorse for homepage and section-front curation.

**Insert**: Search "Content" in the block selector (Newspack section) or type `/content loop` and press Enter.

**Modes**:

- **Dynamic** — auto-queries posts filtered by category, author, or tag. New posts appear automatically as published.
- **Static** — hand-picks specific posts in a fixed order.

**Filter logic**: Multiple selections within one filter type use OR logic. Filters of different types (e.g., category AND tag simultaneously) combine with AND — this can produce unexpectedly narrow results.

**Layout options**:

- View: List or Grid (2–6 columns)
- Featured image aspect ratio: Landscape 4:3, Portrait 3:4, Square, Uncropped
- Media positioning: Top, Left, Right, Behind (as background)
- Image sizing when left/right: S (25%), M (33%), L (50%), XL (75%)
- Minimum height when using background image positioning

**Content display**:

- Post text: suppress entirely, show excerpt (default 55 words, adjustable), or show full text
- Metadata toggles (each independently on/off): category label, article subtitle, author name, author avatar, publish date

**Styling**:

- Block styles: Default or Borders
- Width: Wide Width or Full Width
- Text alignment: left, center, or right
- Font size: scale 1–10 (default 4)
- Custom text color (useful for dark or image-heavy backgrounds)
- Section header CSS class: `accent-header` for theme-consistent styling

**Exclusion rules**: Exclude posts by category, tag, or custom taxonomy — useful with Newspack Sponsors to keep sponsored content out of editorial feeds.

**Pagination**: "Load more posts" button or infinite scroll — both configurable in the editor without code.

**Duplicate story prevention**: Toggle that prevents the same post from appearing in multiple Content Loop blocks on the same page. Not enabled by default — must be explicitly toggled on.

**Ecosystem integrations**:

- Yoast SEO primary category field displays in post metadata
- Newspack Themes provides article subtitle support
- Newspack Newsletters adds newsletter posts as a selectable post type source
- Newspack Sponsors enables custom taxonomy-based exclusions
- Custom post types registered by third-party plugins or themes are supported (requires plugin/theme implementation)

---

### Post Carousel Block

**Purpose**: Displays a scrollable/carousel-style presentation of post collections. Complements the Content Loop block for editorial variety on homepages.

**Use case**: Featured content rotators, trending story carousels, sponsored content showcases.

---

### Donate Block

**Purpose**: Inline reader revenue block embedding a donation form. Supports one-time and recurring donation prompts directly within article or page content.

**Dependencies**: Requires WooCommerce + Stripe (or alternative gateway) to be configured in `Newspack > Reader Revenue`.

---

### Checkout Button Block

**Purpose**: Transactional button for subscription and one-time purchase flows. Triggers the modal checkout experience tied to WooCommerce products (subscriptions, memberships).

---

### Ad Block (Newspack Ad Block)

**Purpose**: Places individual ad units within content. Integrates with the Newspack Ads plugin and Google Ad Manager or Broadstreet.

**Alternative placement**: Ad Unit Widget for sidebar and widget areas (no block required).

---

### Newsletter Subscription Form Block

**Purpose**: Reader email capture for newsletter list sign-ups. Connects to the configured ESP (Mailchimp, ActiveCampaign, Campaign Monitor, or Constant Contact).

---

### Author Profile Block

**Purpose**: Displays author bylines with bio, photo, and profile information. Supports both WordPress user accounts and Guest Contributor records.

---

### Reader Registration Block

**Purpose**: Prompts visitor email registration as part of the Reader Activation System (RAS). Creates a Newspack reader account using only an email address (no password required at registration).

---

### YouTube Video Playlist Block

**Purpose**: Embeds a curated YouTube video playlist within content. Designed for news publishers with video-heavy editorial strategies.

---

### Iframe Block

**Purpose**: General-purpose iframe embed block for custom embedded content that does not have a dedicated WordPress embed handler.

---

### Block Visibility

**Purpose**: Cross-cutting conditional display rules that show or hide blocks based on reader state or other criteria. Applied per-block rather than as a standalone block.

**Example use cases**: Show a donation prompt only to logged-out readers; hide a newsletter CTA from existing subscribers.

---

### Block Styles

**Purpose**: Custom style variants applied to core WordPress blocks — specifically the Columns and Group blocks. Extends core block capabilities with Newspack-specific presentation options.

---

### Newspack Block Patterns

**Purpose**: Pre-built layout templates combining multiple blocks into reusable homepage and section compositions. Allows non-developer editorial teams to apply sophisticated layouts in one click.

**Access**: Block inserter > Patterns tab, or via `Patterns > Explore all patterns` in the editor.

---

## 3. Homepage Management Patterns

_Docs: [help.newspack.com/publishing-appearance/homepage-management/](https://help.newspack.com/publishing-appearance/homepage-management/)_

### Primary Tool: Content Loop Block

The Content Loop block is the standard Newspack approach to building homepage layouts. Editorial teams configure it entirely within the Gutenberg editor — no custom development required.

**Typical homepage architecture**:

1. One full-width Content Loop block at the top (hero stories, 1–2 columns, large featured images)
2. One or more narrower Content Loop blocks below for secondary categories or sections
3. Post Carousel block for featured/trending stories
4. Ad Block placements between content zones
5. Newsletter Subscription Form block in a sidebar or between content zones

**Content curation approaches**:

- **Dynamic curation**: Category-filtered Content Loop blocks update automatically when posts are published. Editorial effort is in tagging content correctly, not in manual block updates.
- **Static curation**: Hand-picked posts for full editorial control over featured stories. Requires manual updates when stories rotate.
- **Hybrid**: Static block at the top for editors' picks; dynamic blocks below for auto-curated category feeds.

### Filter Logic Gotcha

Multiple selections within one filter type (e.g., two categories) use OR — the block shows posts from either category. But if you combine category filters WITH tag filters simultaneously, those combine with AND — showing only posts that match both a selected category AND a selected tag. This can produce unexpectedly empty or narrow results if both filter types are set.

### Duplicate Story Prevention

When multiple Content Loop blocks appear on the same page (common on homepages), the duplicate story prevention toggle prevents the same post from appearing in more than one block. This toggle is OFF by default and must be explicitly enabled on each block where it is desired.

### Pagination

Two options, both configurable in the block settings:

- **Load more posts button** — appends additional posts below the existing list on click.
- **Infinite scroll** — automatically loads additional posts as the reader scrolls.

### Complementary Blocks for Homepage Layouts

- **Post Carousel block** — scrollable article carousel for featured content
- **Block Patterns** — pre-built homepage layout templates for rapid iteration
- **Block Styles** — style variants for Columns and Group blocks used to structure homepage layouts
- **Block Visibility** — conditional display based on reader state (show donation CTA to non-donors only, etc.)

---

## 4. Guest Contributors Setup

_Docs: [help.newspack.com/publishing-appearance/guest-contributors/](https://help.newspack.com/publishing-appearance/guest-contributors/) · Repos: [newspack-guest-authors](https://github.com/Automattic/newspack-guest-authors) · [newspack-co-authors-plus-tools](https://github.com/Automattic/newspack-co-authors-plus-tools)_

### Two Constructs

**Guest Contributor role** — for one-time or infrequent writers who need only a byline. No password, no login, no backend access whatsoever.

**Co-Author assignment** — for existing subscribers or WooCommerce customers who need to appear as co-authors on posts without changing their underlying role. They retain login access.

### Creating a Guest Contributor

1. Go to `Users > Add New`
2. Select **Guest Contributor** from the Role dropdown
3. Complete optional fields: display name, bio, email, profile image
4. Note: certain standard fields become hidden or optional for this role type
5. Save — the user is created with no password and cannot log in

### Making a Subscriber a Co-Author

1. Go to `Users > [target user] > Edit`
2. Locate the **Co-Authors Plus** section in the user profile
3. Enable: "Allow this user to be assigned as a co-author of a post"
4. Save changes
5. The user can now be assigned as a co-author in the post editor without changing their subscriber role

**Trade-off**: Co-authors lose the ability to delete their own account or edit their own profile after the co-author flag is set.

### Migrating from Co-Authors Plus Guest Authors

Newspack is transitioning away from the Co-Authors Plus Guest Author feature toward the native Guest Contributor role. Migration details:

| Aspect                | Detail                                                     |
| --------------------- | ---------------------------------------------------------- |
| Who executes          | Newspack support team (via WP-CLI) — not site operators    |
| Coordination          | Requires a Technical Account Manager (TAM)                 |
| Pre-migration testing | Staging site test run strongly recommended                 |
| Migration window      | Typically under one hour                                   |
| During migration      | Pause all author-related editing to prevent conflicts      |
| Metadata preservation | Names, bios, profile images all preserved                  |
| SEO impact            | Author archive URLs preserved — migration is SEO-safe      |
| Reader-facing impact  | No byline changes visible to readers                       |
| User notifications    | No notifications sent to affected users                    |
| Maintenance mode      | Not required                                               |
| Rollback              | Backup restoration is the rollback path if migration fails |

The migration handles both Guest Author records linked to existing WordPress users and fully standalone Guest Author records.

---

## 5. Federated Sites

_Docs: [help.newspack.com/publishing-appearance/federated-sites/](https://help.newspack.com/publishing-appearance/federated-sites/) · Repo (Newspack Network content distribution): [newspack-distributor](https://github.com/Automattic/newspack-distributor)_

Newspack offers two architectures for multi-publication organizations: Multibranded Site and Newspack Network.

### Multibranded Site

A single WordPress instance presenting multiple visual brand identities based on content context.

**Setup**: `Newspack > Settings > Additional Brands` — create each brand with:

- Name, logo, color palette
- Menus per brand
- Social links per brand
- Homepage display type
- RSS feed (automatically at `/brand/[BRAND-SLUG]/feed`)

**Post assignment**: In the post editor right toolbar > Post section > Brands. When a post is assigned to multiple brands, a **primary brand** must be set — it determines which identity displays on that post.

**Display logic**:

- The site homepage always shows the **default brand** regardless of content brand assignments
- Search results, date archives, and 404 pages all use the default brand
- Posts assigned to multiple categories that map to different brands revert to the default brand
- Campaign prompts assigned to a specific brand never appear in the default brand context
- Unassigned campaign prompts appear across all brand contexts

**Widget visibility per brand**: Add a conditional display rule: "brand taxonomy equals [brand name]"

**Content Loop filtering**: Filter a Content Loop block by brand to create brand-specific homepage sections.

**Ad targeting**: For Newspack Network configurations using GAM, an automatic `site` key-value pair is created in GAM for site-level targeting.

---

### Newspack Network

A hub-and-node architecture connecting fully independent WordPress sites (each with its own domain and database) through a shared network layer.

**Architecture**:

- One site is designated the **hub**
- All other sites are **nodes**
- Reader accounts auto-propagate with the `network_reader` role
- Editorial users (authors, editors, admins) are NOT auto-propagated

**Setup — Hub**:

1. `Newspack Network > Site Role` — designate as hub
2. Add nodes and generate per-node secret keys on the hub

**Setup — Each Node**:

1. Set site role as node
2. Enter Hub URL and the secret key generated for this node
3. Return to hub and click "Link this site"

**Content distribution**:

- Trigger via the blue **antenna icon** in the post editor
- Select target sites, set status on publish, and deploy
- Distributed posts arrive as **linked posts** — locked at destination (only publication status is editable)

**Linked vs. Unlinked posts**:

- **Linked**: Locked. Only status (publish/draft) is editable at the destination site
- **Unlinked**: Fully editable at the destination. Relinking overwrites all local changes — WordPress revisions allow recovery from accidental relinks
- Images in distributed post bodies are NOT imported to destination site media libraries
- Editing gallery blocks on an unlinked post resets the images

**User sync rules**:

- Readers: auto-propagated with `network_reader` role across all nodes
- Editorial users: only synced when a matching email already exists on the destination site, OR when assigned as author on a distributed post
- Guest Authors (Co-Authors Plus): never propagated with distributed posts
- Manual sync: `Admin > user edit screen > "Sync user across network"` button

**Profile fields that sync**: Personal info, social links, WooCommerce billing/shipping addresses, Newspack custom fields, Yoast SEO metadata, Simple Local Avatar data.

**Taxonomy distribution**:

- Brand taxonomy terms: only distributed if they already exist on the destination site
- Categories and tags: always created at the destination if they do not exist

**Canonical URL**: Defaults to the original site's URL. Configurable per distribution.

**Centralized order dashboard**: Hub aggregates WooCommerce subscription and donation orders from all nodes. WooCommerce stores remain independent per site.

**Hub event log**: Centralized log of all network events maintained on the hub.

**Distribution timing**: Distribution and post updates may take a few minutes to propagate across the network.

---

## 6. Plugin List

_Docs: [help.newspack.com/plugins-themes/newspack-plugins/](https://help.newspack.com/plugins-themes/newspack-plugins/) · [help.newspack.com/plugins-themes/third-party-services-integrations/](https://help.newspack.com/plugins-themes/third-party-services-integrations/)_

### First-Party Newspack Plugins

| Plugin                      | Repo                                                                             | Purpose                                                                                                                                                                            |
| --------------------------- | -------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Newspack Plugin**         | [newspack-plugin](https://github.com/Automattic/newspack-plugin)                 | Central installer, configuration wizard dashboard, installs and configures all required and recommended plugins                                                                    |
| **Newspack Ads**            | [newspack-ads](https://github.com/Automattic/newspack-ads)                       | Connects to Google Ad Manager or Broadstreet for display advertising; manages placements, ad units, and in-content ad insertion                                                    |
| **Newspack Blocks**         | [newspack-blocks](https://github.com/Automattic/newspack-blocks)                 | All custom Gutenberg blocks: Content Loop, Post Carousel, Donate, Checkout Button, Ad, Newsletter Subscription Form, Author Profile, Reader Registration, YouTube Playlist, Iframe |
| **Newspack Campaigns**      | [newspack-popups](https://github.com/Automattic/newspack-popups)                 | Overlay, inline, and above-header prompt/CTA management (formerly Newspack Popups); connects to Reader Activation System                                                           |
| **Newspack Listings**       | [newspack-listings](https://github.com/Automattic/newspack-listings)             | Directory pages for Events, Generic, Marketplace, and Places listing content types; includes Curated List block                                                                    |
| **Newspack Media Partners** | [newspack-media-partners](https://github.com/Automattic/newspack-media-partners) | Displays partner logos on collaborative posts                                                                                                                                      |
| **Newspack Newsletters**    | [newspack-newsletters](https://github.com/Automattic/newspack-newsletters)       | Block-based email composition and ESP-integrated send (Mailchimp, ActiveCampaign, Campaign Monitor, Constant Contact)                                                              |
| **Newspack Sponsors**       | [newspack-sponsors](https://github.com/Automattic/newspack-sponsors)             | Sponsored and underwritten content management with visual attribution (sponsor flag, byline, disclaimer, logo)                                                                     |
| **Newspack Supporters**     | [newspack-supporters](https://github.com/Automattic/newspack-supporters)         | Managing and displaying site supporters                                                                                                                                            |

### Notable Additional Plugins

These Newspack-maintained plugins are not part of the core suite but address common publisher needs:

| Plugin                              | Repo                                                                                             | Purpose                                                                                             |
| ----------------------------------- | ------------------------------------------------------------------------------------------------ | --------------------------------------------------------------------------------------------------- |
| **Newspack Image Credits**          | [newspack-image-credits](https://github.com/Automattic/newspack-image-credits)                   | Adds photo credit fields to media; displays credits on images in posts                              |
| **Newspack Guest Authors**          | [newspack-guest-authors](https://github.com/Automattic/newspack-guest-authors)                   | Adds a Guest Author user role with limited publishing and media capabilities                        |
| **Newspack Co-Authors Plus Tools**  | [newspack-co-authors-plus-tools](https://github.com/Automattic/newspack-co-authors-plus-tools)   | Tools for managing Co-Authors Plus data, including migration utilities                              |
| **Newspack Content Converter**      | [newspack-content-converter](https://github.com/Automattic/newspack-content-converter)           | Automated conversion of pre-Gutenberg post content to blocks — useful for legacy content migrations |
| **Newspack Distributor**            | [newspack-distributor](https://github.com/Automattic/newspack-distributor)                       | Tweaks and extensions to the Distributor plugin for Newspack Network content distribution           |
| **Newspack RSS Enhancements**       | [newspack-rss-enhancements](https://github.com/Automattic/newspack-rss-enhancements)             | Customized RSS feeds for syndication partners                                                       |
| **Newspack Scheduled Post Checker** | [newspack-scheduled-post-checker](https://github.com/Automattic/newspack-scheduled-post-checker) | Fixes missed WordPress schedule events (no-nonsense approach)                                       |
| **Newspack Videos**                 | [newspack-videos](https://github.com/Automattic/newspack-videos)                                 | Manage and embed videos                                                                             |
| **Newspack Election Kit**           | [newspack-electionkit](https://github.com/Automattic/newspack-electionkit)                       | Sample ballot tool generator and database manager for local elected officials                       |

### Premium Plugins (Paid Newspack Plans Only)

| Plugin                          | Purpose                                                                                     |
| ------------------------------- | ------------------------------------------------------------------------------------------- |
| **WooCommerce Name Your Price** | Flexible/custom donation amounts                                                            |
| **WooCommerce Subscriptions**   | Recurring monthly/annual payments                                                           |
| **WooCommerce Memberships**     | Content gating (registration walls, paywalls) — must be configured by Newspack support team |

### Infrastructure Dependencies

| Plugin               | Role                                                                                        |
| -------------------- | ------------------------------------------------------------------------------------------- |
| **Jetpack Complete** | Backups, CDN, SSO, 2FA enforcement, social sharing (Publicize), enhanced search, map blocks |
| **WooCommerce**      | Required for reader revenue (donations and subscriptions) and Self-Serve Listings           |
| **Yoast SEO**        | SEO management, breadcrumbs, primary category field, robots.txt editor                      |

### Third-Party Plugin Management

Newspack maintains an approved plugins spreadsheet and a formal review workflow for all non-native plugins.

**Before installing any third-party plugin**:

1. Check the approved plugins spreadsheet (obtain link from TAM or Newspack dashboard)
2. If unlisted, submit for review via Google Form: `https://forms.gle/ofFmGKqoaPWJm2bm8`
3. Wait approximately 7 days for the weekly review cycle
4. Install only on Yellow (provisional) or Green (approved) ratings

**Rating system**:

- **Green** — fully approved
- **Yellow** — provisionally approved
- **Red** — blocked; Newspack will suggest alternatives

**Ineligible for submission**: Plugins showing "hasn't been tested with the latest X major releases of WordPress" on WordPress.org — wait for the plugin to be updated before submitting.

**Why this matters**: Every activated plugin adds page-load overhead and potential security surface. Newspack reviews for security, performance, and reliability. Poor-quality or unreviewed plugins can cause site crashes, security breaches, and plugin conflicts.

### Third-Party Data Flows

| Service           | What Newspack sends                                                                |
| ----------------- | ---------------------------------------------------------------------------------- |
| Mailchimp         | Reader contact data; newsletter interactions                                       |
| ActiveCampaign    | Reader contact data with `NP_` metadata prefix; newsletter interactions            |
| Campaign Monitor  | Reader contact data; newsletter interactions                                       |
| Constant Contact  | Reader contact data; newsletter interactions                                       |
| Google Analytics  | Custom events from Newspack Campaigns and News Tagging Guide; Stripe charge events |
| Parse.ly          | Auto-configured basic setup if plugin is installed but unconfigured                |
| Google Ad Manager | Ad impressions and clicks via Newspack Ads                                         |
| Broadstreet       | Ad data via Newspack Ads                                                           |
| Stripe            | Payment events via webhooks → triggers WooCommerce order creation + GA event       |
| Salesforce        | Donation data from WooCommerce transactions                                        |

---

## 7. Common Gotchas with Themes and Blocks

### Theme Gotchas

**Switching themes is consequential**: Changing the active Newspack child theme after launch alters typography, layout, and block styling across all existing content sitewide. There is no automatic rollback — preview thoroughly on a staging site before switching.

**Newspack Block Theme is a separate, incompatible model**: The Block Theme (FSE) uses block-based templates and the Site Editor — it is not a child theme and does not share the Customizer-based customization model of the classic child themes. Documentation for it lives on its own sub-page. Do not confuse with the classic child theme collection.

**Newspack Sponsors requires a Newspack theme**: Sponsor attribution (flags, disclaimers, logos) will not render on any non-Newspack theme, regardless of plugin configuration. If the site ever switches away from a Newspack theme, all sponsored content attribution becomes invisible.

**Demo staging sites use HTTP**: The individual child theme demo sites use HTTP (not HTTPS) URLs, which may trigger browser security warnings when previewing.

**RAS UI styling is independent**: Reader Activation System UI components (registration modals, gate overlays) do not inherit the site's theme colors or typography from the Customizer. Custom CSS overrides are required to match site branding.

### Content Loop Block Gotchas

**Filter logic is not intuitive**: Multiple selections within one filter type use OR logic (expected), but combining different filter types (category + tag simultaneously) uses AND logic — this frequently produces unexpectedly empty or narrow result sets. Test filter combinations before publishing.

**Duplicate story prevention is off by default**: Posts will appear in multiple Content Loop blocks on the same page unless the toggle is explicitly enabled on each block. On complex homepages with many blocks, every editorial team member needs to know this setting exists.

**Custom post type support is not automatic**: Displaying custom post types in the Content Loop block requires implementation via a plugin or theme. Third-party post types are not supported out of the box without explicit code registration.

**Static mode requires manual maintenance**: Posts in Static mode do not update automatically. When featured stories rotate, an editor must manually update the block.

**Excerpt word count is not visible**: The default excerpt is 55 words. The adjustable word count setting is in the block's sidebar — it is easy to miss, and editors sometimes wonder why excerpts are cut off.

### Block Visibility Gotchas

**Campaigns assigned to a brand never show in the default brand context**: If a prompt is assigned to a specific brand, it will never display on pages that show the default brand (homepage, search, date archives, 404). Prompts not assigned to any brand appear across all contexts including the default brand.

### Federated Sites Gotchas

**Homepage always defaults to the default brand**: No matter what brand content is assigned to posts on the homepage, the homepage itself always displays the default brand identity. This cannot be overridden without custom development.

**Editorial users are not auto-propagated in Newspack Network**: Only readers with the `network_reader` role sync automatically across network nodes. Authors, editors, and admins must be created manually on each node (or will be created when assigned to a distributed post if a matching email exists).

**Images in distributed posts are not imported**: When a post is distributed to another network node, images embedded in the post body are referenced from the source site — they are not copied to the destination site's media library. If the source site's images become unavailable, distributed posts will show broken images.

**Relinking an unlinked post overwrites local changes**: If a distributed post is unlinked for local editing and then relinked to its source, all local changes are overwritten. WordPress revisions exist as a recovery path but must be accessed manually.

**Gallery blocks on unlinked posts reset images**: Editing a gallery block on an unlinked distributed post will reset the images. Avoid editing gallery blocks after unlinking unless you are prepared to re-add images.

**Brand taxonomy terms do not auto-create at nodes**: When distributing a post, brand taxonomy terms are only distributed to destination nodes where those terms already exist. Categories and tags always create. If brand-based display logic matters at a destination node, ensure brand terms are created on that node before distributing.

### Guest Contributor Gotchas

**Co-authors lose profile self-management**: Once a subscriber or WooCommerce customer is given the co-author flag, they lose the ability to delete their own account or edit their own profile. This is a trade-off to be communicated to affected users before enabling.

**Guest Contributor migration requires TAM coordination**: The migration from Co-Authors Plus Guest Authors to the native Guest Contributor role cannot be self-executed. It must be run by the Newspack support team via WP-CLI. Attempting to migrate manually risks data loss and broken author archives.

**Author editing must pause during migration**: Any author record editing during the migration window can cause conflicts. Pause all author-related editorial work while the Newspack team executes the migration.

### Plugin Gotchas

**Parse.ly auto-configuration is silent**: If the Parse.ly WordPress plugin is installed on a Newspack site but has not been configured, Newspack will automatically apply a basic Parse.ly configuration. Publishers who independently installed Parse.ly should fully configure it before Newspack's automatic setup can alter it.

**ActiveCampaign field prefix conflicts**: All metadata Newspack sends to ActiveCampaign uses the `NP_` prefix. Pre-existing fields in a publisher's ActiveCampaign account that already use the `NP_` prefix may be overwritten or behave unexpectedly.

**Multiple sponsors on one post**: When more than one sponsor is associated with a single post, all sponsor logos are displayed. However, only the first sponsor's disclaimer text and flag label are rendered — all secondary sponsor disclaimers are silently suppressed.

**Newspack does not support third-party ad plugins**: If a publisher installs any third-party advertising plugin (e.g., Advanced Ads) alongside Newspack Ads, Newspack support is limited to disabling ads and checking for PHP errors. No configuration assistance is provided for third-party ad plugins.
