---
name: newspack
description: "Use when building, configuring, or maintaining a Newspack-powered WordPress news site. Covers onboarding, theme setup, publishing, revenue (advertising, reader revenue, sponsored content), engagement (campaigns, newsletters, content gating, audience management), analytics, optimization, and plugin management."
compatibility: "Newspack-managed WordPress sites on WordPress.com infrastructure. Requires Newspack plugin suite, Jetpack Complete, and WooCommerce for reader revenue features. Some features require coordination with a Newspack Technical Account Manager (TAM)."
license: MIT
metadata:
    author: georgestephanis
    version: "1.0"
    written: "2026-06-03"
    written_against:
        newspack-plugin: "v6.42.1"
        newspack-blocks: "v4.26.3"
        newspack-ads: "v3.11.2"
        newspack-campaigns: "v3.12.0"
        newspack-newsletters: "v3.33.4"
        newspack-sponsors: "v2.2.0"
        newspack-listings: "v3.6.1"
        newspack-theme: "v2.22.2"
        newspack-block-theme: "v1.28.1"
        wordpress: "6.x"
        woocommerce: "10.8.1"
        jetpack: "15.8"
    docs_fetched: "2026-06-03"
---

# Newspack Platform

## When to use

Use this skill when:

- Setting up a new Newspack site from scratch (onboarding, domain transfer, Jetpack connection, data migration).
- Selecting or customizing a Newspack theme.
- Configuring advertising (Google Ad Manager, Broadstreet) or reader revenue (Stripe, WooCommerce donations and subscriptions).
- Setting up engagement features: campaign prompts, email newsletters, content gating, or the Reader Activation System (RAS).
- Wiring up Google Analytics 4 custom event tracking and custom dimensions.
- Improving Core Web Vitals, security hardening, or auditing third-party plugins.
- Working with Newspack Listings, sponsored content, multibranded sites, or the Newspack Network.
- Adding or reviewing a third-party plugin against the Newspack-approved list.

Do NOT use this skill for generic WordPress or WooCommerce tasks unrelated to the Newspack plugin suite — reach for `wp-block-development`, `wp-plugin-development`, or other targeted skills for those.

## Inputs required

- **Newspack dashboard access** — `Dashboard > Newspack` in the WordPress admin. Most configuration flows through the Newspack Wizards.
- **Technical Account Manager (TAM) contact** — required for domain transfer coordination, WooCommerce Memberships setup, content gating strategy, data migration, and RAS enablement.
- **Hosting tier** — whether the site is on a paid Newspack plan (affects availability of WooCommerce Name Your Price and WooCommerce Subscriptions premium plugins).
- **Ad server choice** — Google Ad Manager or Broadstreet (must be determined before activating the Newspack Ads plugin).
- **Payment gateway** — Stripe (primary, not available in all countries) or an alternative WooCommerce-compatible gateway.
- **Email service provider (ESP)** — Mailchimp or ActiveCampaign for Reader Activation System; one of Mailchimp, ActiveCampaign, Campaign Monitor, or Constant Contact for Newspack Newsletters.
- **GA4 property** — Measurement ID (G-XXXXXXXX) and Measurement Protocol API secret for custom event tracking.

---

## Platform overview

Newspack is a WordPress-based publishing platform developed by Automattic for news organizations. It combines a curated suite of first-party plugins, a set of themed child themes, and managed hosting on WordPress.com infrastructure with Jetpack providing backup, CDN, and SSO.

### Core plugin suite

| Plugin                      | Repo                                                                             | Purpose                                                                                                                                                                        |
| --------------------------- | -------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| **Newspack Plugin**         | [newspack-plugin](https://github.com/Automattic/newspack-plugin)                 | Central installer and unified configuration wizard dashboard                                                                                                                   |
| **Newspack Ads**            | [newspack-ads](https://github.com/Automattic/newspack-ads)                       | Connects to Google Ad Manager or Broadstreet for display advertising                                                                                                           |
| **Newspack Blocks**         | [newspack-blocks](https://github.com/Automattic/newspack-blocks)                 | Custom Gutenberg blocks: Content Loop, Post Carousel, Donate, Checkout Button, Ad, Newsletter Subscription Form, Author Profile, Reader Registration, YouTube Playlist, Iframe |
| **Newspack Campaigns**      | [newspack-popups](https://github.com/Automattic/newspack-popups)                 | Overlay, inline, and above-header prompt/CTA management (formerly Newspack Popups)                                                                                             |
| **Newspack Listings**       | [newspack-listings](https://github.com/Automattic/newspack-listings)             | Directory pages for Events, Generic, Marketplace, and Places content types                                                                                                     |
| **Newspack Media Partners** | [newspack-media-partners](https://github.com/Automattic/newspack-media-partners) | Partner logo display on collaborative posts                                                                                                                                    |
| **Newspack Newsletters**    | [newspack-newsletters](https://github.com/Automattic/newspack-newsletters)       | Block-based email composition and send via ESP integrations                                                                                                                    |
| **Newspack Sponsors**       | [newspack-sponsors](https://github.com/Automattic/newspack-sponsors)             | Sponsored and underwritten content management with visual attribution                                                                                                          |
| **Newspack Supporters**     | [newspack-supporters](https://github.com/Automattic/newspack-supporters)         | Managing and displaying site supporters                                                                                                                                        |

All first-party repos: [github.com/automattic/?q=newspack](https://github.com/automattic/?q=newspack)

### Theme suite

One parent theme plus five named child themes ([newspack-theme](https://github.com/Automattic/newspack-theme)):

| Theme                  | Distinguishing characteristics                                                                 |
| ---------------------- | ---------------------------------------------------------------------------------------------- |
| **Newspack** (parent)  | System fonts, underlined Content Loop headers, button-style tertiary nav                       |
| **Newspack Joseph**    | Old Standard TT + EB Garamond, hairline borders, classic broadsheet feel                       |
| **Newspack Katharine** | Barlow typeface, rectangular section accents, dotted borders, overlapping featured images      |
| **Newspack Nelson**    | Montserrat headers, overlap header layout, prominent pullquotes and separators                 |
| **Newspack Sacha**     | IBM Plex Serif headers, serif system body, centered Content Loop headers with flanking borders |
| **Newspack Scott**     | Fira Sans Condensed headers, boxy/blocky aesthetic, square avatars                             |

A separate **Newspack Block Theme** supports full-site editing (FSE) with block-based templates — it uses a different customization model from the classic child themes and is documented separately.

Newspack Sponsors requires a Newspack theme to display sponsor attribution. It will not render on non-Newspack themes.

### Infrastructure dependencies

- **Jetpack Complete** — required for backups, CDN, SSO, and 2FA enforcement. Also enables social sharing (Publicize), enhanced search, and map blocks for Listings.
- **WooCommerce** — required for reader revenue (donations and subscriptions) and Self-Serve Listings.
- **WooCommerce Name Your Price** — flexible donation amounts. Premium plugin; paid Newspack plans only.
- **WooCommerce Subscriptions** — recurring monthly/annual payments. Premium plugin; paid Newspack plans only.
- **WooCommerce Memberships** — content gating (registration walls, paywalls). Must be configured by the Newspack support team, not self-service.

---

## Procedure

### Onboarding and prelaunch

_Docs: [help.newspack.com/onboarding/](https://help.newspack.com/onboarding/) · [help.newspack.com/onboarding/prelaunch-tasks/](https://help.newspack.com/onboarding/prelaunch-tasks/)_

Full onboarding is a collaborative process with a Newspack Technical Account Manager. The sequence is:

#### 1. Domain transfer

Transfer the domain registration to WordPress.com (registration only — not content). See [references/optimization-onboarding.md](references/optimization-onboarding.md#2-domain-transfer-procedure) for the full step-by-step.

Key prerequisites before initiating:

- Domain must not be under a 60-day ICANN post-registration or post-transfer lock.
- Domain must not be at its maximum renewal term (typically 10 years).
- Registrar lock must be disabled at the current registrar.
- Obtain the EPP/authorization code (case-sensitive — copy-paste, do not retype).

The transfer takes 5–7 business days. Do NOT cancel the domain at the old registrar until transfer completes — cancellation causes immediate ownership loss.

#### 2. Jetpack connection

Install Jetpack on the existing WordPress site (not the new Newspack site), connect to a WordPress.com account, and select the **Complete** plan using the Newspack-provided coupon code. Notify the TAM of the connected email address — Newspack uses the resulting backup to create the staging environment.

#### 3. Data migration preparation

Export non-WordPress content as structured CSV files. Two files are required: see [references/optimization-onboarding.md](references/optimization-onboarding.md#csv-data-migration) for field validation rules and format requirements.

- `posts.csv` — required fields: GUID, Post Title, Post Body, Publish Date. Post Body accepts only `<p>`, `<b>`, `<i>` HTML tags.
- `users.csv` — required fields: GUID, Email Address. Username must be alphanumeric only (A-Z, 0-9).
- All dates must use `YYYY-MM-DD HH:MM:SS` format.
- Use `fputcsv()` in PHP for generation.

**4. Launch checklist** (after migration)

Execute these in order after the site is migrated:

1. **Admin email**: `Settings > General` — change from `newspack@a8c.com` to a publisher-domain address.
2. **Google Site Kit**: Connect GA4 and Search Console. Grant `newspack@a8c.com` Editor Access to Analytics and Full User Access to Search Console.
3. **reCAPTCHA**: Set up reCAPTCHA v2. The version must match exactly in both the reCAPTCHA account and `Newspack > Settings`. A v2/v3 mismatch silently breaks spam prevention.
4. **Yoast SEO**: Open `Yoast SEO > General` in the toolbar and complete the First-time configuration wizard.
5. **Google Ad Manager** (if using): `Newspack > Settings > Connections > API` — connect using the email that is Admin in GAM.
6. **Jetpack social sharing**: `Jetpack > Settings > Social Sharing` — connect social accounts.

Allow 48–72 hours after GA4 custom dimension setup before data populates (see Analytics section).

---

### Theme setup and appearance

_Docs: [help.newspack.com/publishing-appearance/themes/](https://help.newspack.com/publishing-appearance/themes/) · [help.newspack.com/plugins-themes/newspack-themes/](https://help.newspack.com/plugins-themes/newspack-themes/)_

#### Selecting a theme

Choose a child theme from the six available options. Theme selection affects typography, block styling, and section accents across all existing content — switching themes after launch will alter appearance sitewide. Preview each theme on the live demo staging sites before committing.

#### Customization entry point

All shared customization options are accessed through the WordPress Customizer (`Appearance > Customize`):

- Typography (Google Fonts defaults, overridable per theme)
- Color palettes
- Header configuration (including the overlap header in Nelson)
- Custom menus (including button-style tertiary nav in the parent theme)
- Featured image behavior
- Widget areas
- Footer settings
- Post, page, and archive templates
- Custom CSS
- WooCommerce display options
- Sponsored content label colors (`Appearance > Customize > Sponsored Content`)

#### Newspack Block Theme

The Block Theme uses FSE with block-based templates and the Site Editor (`Appearance > Editor`), not the Customizer. It ships 11 style variations, 13 templates, 12 template parts, and a pattern library organized into 6 categories. Customization is done via `theme.json` and style variation JSON files (in a child block theme's `styles/` directory) rather than PHP or the Customizer. See [references/publishing-appearance.md](references/publishing-appearance.md) for the full template inventory, theme.json defaults, and child theme structure. Repo: [github.com/Automattic/newspack-block-theme](https://github.com/Automattic/newspack-block-theme).

---

### Publishing and content

_Docs: [help.newspack.com/publishing-appearance/blocks/](https://help.newspack.com/publishing-appearance/blocks/) · [help.newspack.com/publishing-appearance/homepage-management/](https://help.newspack.com/publishing-appearance/homepage-management/) · [help.newspack.com/publishing-appearance/guest-contributors/](https://help.newspack.com/publishing-appearance/guest-contributors/)_

#### Content Loop block (homepage and listings)

The Content Loop block is the primary tool for homepage curation. Insert with `/content loop` in the block editor, or search "Content" in the block selector and navigate to the Newspack section.

Key configuration decisions:

- **Dynamic mode** — auto-queries posts by category, author, or tag. New posts appear automatically.
- **Static mode** — hand-picked posts in a fixed order.
- **Filter logic** — multiple selections within one filter type use OR; filters of different types combine with AND. Combining category and tag filters can produce unexpected results.
- **Exclusion rules** — use to keep sponsored content out of main editorial feeds (integrates with Newspack Sponsors custom taxonomies).
- **Duplicate story prevention** — must be explicitly toggled on; not enabled by default.
- **Pagination** — "Load more posts" button or infinite scroll; both configurable in the editor.

Display options: list or grid (2–6 columns), four featured image aspect ratios (Landscape 4:3, Portrait 3:4, Square, Uncropped), media positioning (top/left/right/behind), excerpt with configurable word count (default 55), individual metadata toggles (category, subtitle, author, avatar, date), font size scale 1–10, text color, section header `accent-header` CSS class.

#### Guest contributors

Newspack uses two constructs for authors without full backend access:

- **Guest Contributor role** — no password, no backend access, byline-only. Create at `Users > Add New`, selecting Guest Contributor from the Role dropdown.
- **Co-Author assignment** — elevates existing subscribers/WooCommerce customers to co-author status without changing their role. Enable in the user's profile under the Co-Authors Plus section. Note: co-authors lose the ability to delete their own account or edit their own profile after this flag is set.

Migration from Co-Authors Plus Guest Author records to Guest Contributor users must be coordinated with the Newspack support team and executed via WP-CLI. Test on staging first. The migration preserves author archive URLs (SEO-safe), metadata, and profile images. No maintenance mode is required. Typical migration window is under one hour, but author-related editing must be paused during the window.

#### Federated sites

See [references/publishing-appearance.md](references/publishing-appearance.md#5-federated-sites) for Multibranded Site and Newspack Network configuration. Key behavioral constraints:

- The site homepage always shows the default brand regardless of content brand assignments.
- Editorial users (authors, editors, admins) are NOT auto-propagated across the Newspack Network — only readers with the `network_reader` role sync automatically.
- Images in distributed post bodies are not imported to destination site media libraries.
- Mode selection in the Curated List block (Specific vs. Query) is permanent at block creation — switching requires deleting the block.

---

### Revenue: advertising

_Docs: [help.newspack.com/revenue/advertising/](https://help.newspack.com/revenue/advertising/) · [help.newspack.com/revenue/sponsored-content/](https://help.newspack.com/revenue/sponsored-content/)_

#### Activation

Navigate to `Dashboard > Newspack > Advertising` and activate the Newspack Ads plugin. Select an ad server integration: **Google Ad Manager** (primary, most documented) or **Broadstreet** (alternative for community/local news publishers). See [references/revenue.md](references/revenue.md#1-advertising-setup) for detailed placement and suppression guidance.

Newspack does not support third-party ad plugins such as Advanced Ads. If a publisher installs one, Newspack support is limited to turning ads off and checking PHP errors — no configuration assistance is provided.

#### Google Ad Manager setup

1. Sign up for a GAM account using the provided guide before connecting.
2. Connect via `Newspack > Settings > Connections > API` using the GAM Admin email.
3. Configure global placements (sitewide ad positions) via the Advertising Wizard.
4. Place individual ad units using the **Newspack Ad Block** (Gutenberg block) within content, or the **Ad Unit Widget** for sidebar/widget areas.
5. Configure in-content ad insertion to automatically insert ads within article body content.
6. Set up Ad Block Recovery messages for readers with ad blockers.
7. Configure Ad Refresh Control to manage impression refresh timing.
8. Apply Ad Suppression rules to exclude ads from specific posts, pages, or content types.

For Newspack Sponsors sites using GAM: an automatic `site` key-value pair is created in GAM for site-level targeting on Newspack Network configurations.

#### Sponsored content

Newspack Sponsors creates two sponsorship types with distinct attribution display:

- **Native Content** — third-party authored; receives sponsor flag, byline, disclaimer, and logo on single posts, archive pages, and search results.
- **Underwritten Content** — editorial-authored, sponsor-funded; shows only a small attribution block at the top of single post views.

Create sponsors at `Advertising > Sponsors > Add New`. Each sponsor record supports: name, URL, flag override, disclaimer override (with tooltip), byline prefix, and logo from Media Library.

Configure defaults under `Advertising > Sponsors > Settings`. Set label colors via `Appearance > Customize > Sponsored Content`.

Assign sponsors to content in two ways:

1. Assign categories or tags to the sponsor (applies treatment to all posts in those taxonomies automatically).
2. Assign individual posts directly for targeted sponsorship.

Use the "Show on posts only if direct sponsor?" toggle to restrict a category/tag-level sponsor to only explicitly assigned posts.

Gotchas:

- When multiple sponsors are associated with a single post, all logos are shown but only the **first** sponsor's disclaimer and flag label are rendered — secondary sponsor disclaimers are suppressed.
- Newspack Sponsors requires a Newspack theme to render — it will not display on non-Newspack themes.

---

### Revenue: reader revenue

_Docs: [help.newspack.com/revenue/reader-revenue/](https://help.newspack.com/revenue/reader-revenue/)_

Publishers choose between two platform paths:

#### Path A: Newspack-native (WooCommerce-based)

Setup in three stages:

1. **Payment gateway** — enable Stripe via `Dashboard > Newspack > Reader Revenue > Stripe` tab. Stripe is not available in all countries; use an alternative WooCommerce-compatible gateway if needed.
2. **Salesforce CRM** (optional) — create a Connected App in Salesforce, obtain Consumer Key and Consumer Secret, enter both in Newspack settings. This is non-trivial and documented separately.
3. **Donations block and landing page** — configure default donation amounts and tiers in the Donations block settings, then publish a donations landing page.

Premium plugins required for full functionality (paid Newspack customers only):

- **WooCommerce Name Your Price** — flexible/custom donation amounts.
- **WooCommerce Subscriptions** — recurring monthly/annual payments.

#### Path B: News Revenue Hub

Enter organization details, Salesforce Campaign ID, and donor landing page selection in the Newspack settings to activate. Configuration is minimal; all processing is handled externally by News Revenue Hub (fundjournalism.org).

#### Post-donation/subscription flows

The following sub-topics are documented in [references/revenue.md](references/revenue.md#2-reader-revenue-donations-and-subscriptions):

- Modal checkout flow
- Subscription confirmation and custom email receipts
- Gift subscriptions
- What happens after a donation/subscription is created
- Manual subscription payment management
- Subscription payment retries and expiration handling

---

### Engagement: campaigns

_Docs: [help.newspack.com/engagement/campaigns/](https://help.newspack.com/engagement/campaigns/) · [references/engagement.md](references/engagement.md#campaigns-and-popups)_

Newspack Campaigns manages calls-to-action (CTAs) displayed as prompts across the site.

#### Concepts

- **Prompt** — an individual CTA element displayed as an overlay (pop-up), inline block, or above-header banner.
- **Campaign** — an organizational container grouping related prompts. Campaign names are internal only; readers never see them.

#### Prompt types

- **Overlay** — positioned top/bottom/center; triggered by time-on-page or scroll-depth threshold.
- **Inline** — embedded in article content at a position calculated by block count or content percentage.
- **Above-header** — persistent banner on every page until frequency conditions are met.

**Access**: `Newspack Dashboard > Campaigns`

#### Audience targeting

Prompts can be targeted or suppressed based on:

- Subscription status, donation history, article consumption volume
- Reader segmentation (top/mid/lower funnel)
- Frequency controls

#### Reader Activation Defaults

For publishers using RAS: switch the dropdown from "Active Prompts" to "Reader Activation Defaults" to activate a curated pre-configured prompt set aligned with Newspack's recommended engagement strategy.

#### Email suppression

Append `utm_suppression=[value]` to newsletter links to exclude newsletter-sourced traffic from newsletter signup prompts. This must be added manually to links — it is not automatic.

#### Campaign management operations

| Operation                                          | Location                       |
| -------------------------------------------------- | ------------------------------ |
| Bulk activate/deactivate all prompts in a campaign | Three-dot menu on the campaign |
| Duplicate, rename, archive, or delete a campaign   | Three-dot menu on the campaign |

Deleting a campaign does NOT delete its prompts — prompts become unassigned, not removed.

Publishers not collecting donations must immediately disable donation prompts in `Newspack > Campaigns` to avoid presenting irrelevant CTAs.

---

### Engagement: newsletters

_Docs: [help.newspack.com/engagement/newsletters/](https://help.newspack.com/engagement/newsletters/)_

Newspack Newsletters provides block-based email composition and sending from within WordPress.

#### Supported providers

| Provider         | Credentials needed                               |
| ---------------- | ------------------------------------------------ |
| Mailchimp        | API key                                          |
| ActiveCampaign   | API URL + API key                                |
| Constant Contact | API key, secret, redirect URIs (OAuth2)          |
| Manual           | No credentials; copy generated HTML into any ESP |

Configure at `Newsletters > Settings`.

#### Creating and sending

1. `Newsletters > All Newsletters > Add New Newsletter`
2. Choose a layout (9 pre-built options) or Blank Newsletter.
3. Edit content using the block-based email editor.
4. In the settings panel: set campaign name, subject line, preview text, From sender name and email.
5. **Mailchimp and Constant Contact only**: click "Update Sender" after filling in From fields — sending will be blocked if this step is skipped.
6. Select audience/list/segment in Send To.
7. Send a test email via the Testing panel.
8. Click Send.

For the Manual provider: copy the generated HTML, paste into the external ESP, then click "Mark as sent."

#### Critical gotchas

- The Post Inserter block only includes posts published before the newsletter was created — posts published after creation date will not appear, even if the newsletter is scheduled.
- ActiveCampaign segments must be created manually in the ActiveCampaign dashboard; the plugin cannot create them automatically.
- Newsletters drafted in Newspack cannot be further edited in the ActiveCampaign dashboard after syncing.
- Mailchimp does not support Advanced Segments in automations and external integrations.
- Mailchimp custom footers require specific merge tag placeholders: `*|EMAIL|*`, `*|UNSUB|*`, `*|LIST:ADDRESSLINE|*`, etc. — missing these causes compliance failures.
- Constant Contact's List and Segment targeting options are mutually exclusive.
- Image blocks cannot use left/right alignment.
- Columns and Group blocks support only one level of nesting.
- Custom CSS rendering is not guaranteed to be consistent across email clients.

#### UTM parameters

All outbound newsletter links automatically receive `utm_campaign`, `utm_source`, and `utm_medium=email` derived from the campaign name and send list. Third-party integrations may overwrite these.

---

### Engagement: content gating

_Docs: [help.newspack.com/engagement/content-gating/](https://help.newspack.com/engagement/content-gating/) · [references/engagement.md](references/engagement.md#content-gating)_

Content gating is built on RAS and requires WooCommerce Memberships and Subscriptions. **Contact the Newspack TAM via Slack (`#newspack-help`) before configuring content gating** to align business model with technical options. WooCommerce Memberships must be configured by the support team — it is not a self-service initial configuration.

#### Five block pattern configurations

| Pattern                        | When to use                                 |
| ------------------------------ | ------------------------------------------- |
| Registration Wall              | Require email sign-up to access content     |
| Donation Wall                  | Prompt readers to donate via modal checkout |
| Pay Wall — One Tier            | Single subscription product                 |
| Pay Wall — Two Tiers           | Multiple subscription options               |
| Pay Wall — One Tier + Metering | Subscription combined with view-count limit |

Publishers with three or more subscription plans should highlight only one or two to avoid reader choice paralysis.

#### Gate placement and display

- Default method: paragraph count setting controls how much content shows before the gate activates.
- Per-article override: place a WordPress More block inside individual posts.
- Display styles: **inline** (appears in article flow, optional gradient fade on the final paragraph) or **overlay modal** (configurable sizing from Extra Small to Full Width, adjustable positioning).

#### Metering

Set separate view limits for anonymous and authenticated readers, plus a reset frequency.

- Anonymous metering: tracked via browser local storage. **Can be bypassed by readers using incognito/private browsing mode.**
- Authenticated metering: stored as secure backend user metadata. Cannot be bypassed by incognito mode.
- Once a metered reader has accessed content within their allowed limit, that content remains accessible for the remainder of the metering period even after the limit is reached.

#### Developer hooks

```php
// Check if a post is currently restricted
Newspack\Content_Gate::is_post_restricted();

// Check if metering is active for the current context
Newspack\Metering::is_metering();
```

JavaScript: anonymous metering fires a `metering_restricted` RAS activity event carrying the post ID and expiration timestamp.

---

### Engagement: audience management (Reader Activation System)

_Docs: [help.newspack.com/engagement/audience-management/](https://help.newspack.com/engagement/audience-management/)_

The Reader Activation System (RAS) is the full audience engagement stack. It introduces frictionless authentication — readers provide only an email address to register; passwords are required only for account management. Authentication methods: email + password, one-time password code, or magic link.

**RAS setup wizard** (in order)

1. Designate legal pages (privacy policy and terms of service) — must be published and designated before enabling RAS.
2. Connect an ESP (Mailchimp or ActiveCampaign) via API key and enable at least one subscription list.
3. Configure transactional email sender name, sender address, and contact address.
4. Set up reCAPTCHA v3 under `Newspack > Connections`.
5. Ensure Reader Revenue is configured (WooCommerce + Stripe).
6. Customize default campaign prompts (registration, newsletter, donation overlays and inline variants).
7. Click **Enable Reader Activation**.

**Eight transactional email templates** with placeholder variables:

- Universal: `SITE_TITLE`, `SITE_CONTACT`, `SITE_URL`
- Template-specific: `MAGIC_LINK_URL`, `VERIFICATION_URL`, `PASSWORD_RESET_LINK`, `DELETION_LINK`

**Advanced settings** (`Newspack > Engagement > Reader Activation > Show Advanced Settings`)

- Force registration at WooCommerce checkout
- Post-checkout newsletter subscription list presentation
- ESP-specific sync behavior:
    - Mailchimp: audience ID, default reader status, metadata field prefix
    - ActiveCampaign: master list selection
- Metadata field prefix for ESP sync defaults to `NP_`

#### Gotchas

- RAS UI components use independent styling and will NOT inherit site colors or typography from the Newspack Theme customizer — custom CSS overrides may be needed.
- To automatically enroll new registrants in a single newsletter list, that list must be the only one enabled; having multiple lists enabled prevents single-list auto-enrollment.
- If users need to create a password at checkout, the password setup link email can be toggled off under `WooCommerce > Settings > Accounts & Privacy`.
- Registration wall block patterns auto-enable newsletter subscriptions by default — review this if opt-in behavior should differ.

---

### Analytics setup

_Docs: [help.newspack.com/analytics/](https://help.newspack.com/analytics/) · [help.newspack.com/analytics/getting-started/](https://help.newspack.com/analytics/getting-started/)_

Newspack analytics require GA4 connected via Google Site Kit. Other GA connection methods are not supported.

#### Activating Newspack custom events

1. In GA Admin: `Data collection and modification > Data Streams > [site stream] > Measurement Protocol API secrets > Create new secret`. Copy the API secret value and the Measurement ID (G-XXXXXXXX format).
2. In WordPress: `Newspack > Settings > Connections > Activate Newspack Custom Events`. Paste the API secret and Measurement ID.
3. **Wait 24–72 hours** before creating custom dimensions — parameters do not appear in the GA4 dropdown until events have actually fired on the live site.

#### Creating custom dimensions

`GA Admin > Data Display > Custom Definitions > Create custom dimension`. Select the event parameter from the dropdown, name the dimension to match the parameter name, keep scope as Event.

Recommended parameters to create dimensions for: `action`, `action_type`, `amount`, `author`, `category`, `donation_amount`, `donation_recurrence`, `is_reader`, `is_newsletter_subscriber`, `is_subscriber`, `is_donor`, `lists`, `logged_in`, `newsletter_subscription_method`, `popup_id`, `prompt_frequency`, `prompt_id`, `prompt_placement`, `prompt_title`, `product_id`, `range`, `recurrence`, `referrer`, `registration_method`

Custom dimension parameters only appear after the corresponding events have fired on the live site — perform test actions (a test donation, registration, etc.) to populate them before attempting to create dimensions.

#### Newspack auto-collected custom events

| Event                           | Fires when                                                                                  |
| ------------------------------- | ------------------------------------------------------------------------------------------- |
| `np_reader_registered`          | New account creation via Registration block, Newsletter Subscription Form, or account modal |
| `np_reader_logged_in`           | Successful login via Sign In modal, magic link, or Google Sign-in                           |
| `np_newsletter_subscribed`      | Newsletter subscription completion                                                          |
| `np_prompt_interaction`         | Campaign prompt loaded/seen/dismissed/clicked                                               |
| `np_gate_interaction`           | Content gate seen/dismissed/form_submission                                                 |
| `np_modal_checkout_interaction` | Modal checkout transactions (replaces the removed `np_donation_new` event as of May 2024)   |

All events carry universal reader-state parameters: `logged_in`, `is_reader`, `is_newsletter_subscriber`, `is_subscriber`, `is_donor`, plus content-context parameters `categories` and `author`.

#### May 2024 event tracking refactor

The events `np_donation_new` and `np_donation_subscription_cancelled` were removed. Donation and subscription conversions now flow through `np_modal_checkout_interaction` filtered on `action = form_submission`. Any existing GA4 Explorations or Looker Studio dashboards using the old events must be manually updated.

#### Building GA4 reports

Use GA4 Explorations to answer specific questions:

| Report goal                      | Filter                                                                                                   |
| -------------------------------- | -------------------------------------------------------------------------------------------------------- |
| Registration gate views          | `np_gate_interaction` where `action=seen` and `gate_has_registration_block=true`                         |
| Paywall views                    | `np_gate_interaction` where `action=seen` and `gate_has_checkout_button=true`                            |
| Completed registrations via gate | `np_reader_registered` where `gate_post_id` is present                                                   |
| Completed checkouts via gate     | `np_modal_checkout_interaction` where `action=form_submission_success` and `action_type=checkout_button` |
| Abandoned checkouts              | `np_modal_checkout_interaction` where `action=continue` without a matching form_submission               |

#### Additional analytics tools

- **Google Tag Manager** — for custom granular event tracking beyond Newspack defaults. Must be implemented exclusively through Site Kit to avoid conflicts with the existing GA connection.
- **Newspack Data Dashboard** — paid add-on (Newspack customers only) aggregating multi-source data into Google BigQuery for benchmarking. Access at newspack.com/data-dashboard. Pricing is tiered by annual revenue; the standalone Dashboard-only tier is not available for publishers under $300K/year (bundle-only at that tier).
- **Metorik** — WooCommerce-focused analytics platform. Newspack builds customized Metorik dashboards during onboarding for reader revenue reporting. Publishers build additional custom reports beyond the initial dashboards themselves.

---

### Optimization and performance

_Docs: [help.newspack.com/optimization/](https://help.newspack.com/optimization/) · [help.newspack.com/optimization/core-web-vitals/](https://help.newspack.com/optimization/core-web-vitals/)_

#### Core Web Vitals testing

Use **Google PageSpeed Insights** (pagespeed.web.dev) for testing. Test multiple article pages — not just the homepage — since articles vary significantly in performance due to differing embeds and image counts. Establish a performance baseline before any significant change and re-test after.

Do NOT use Lighthouse without careful configuration; it often provides inaccurate readings.

#### Image guidelines

- Resize to 1200–2560px wide before uploading.
- Keep file size under 2 MB.
- Use JPG rather than PNG — PNG files do not optimize effectively through the WordPress/Jetpack CDN.
- Allow WordPress/Jetpack CDN to handle further optimization.

#### Tag management

Limit post tags to approximately 6 per post. Excess tags can degrade SEO and performance.

#### Embed strategy

Interactive data visualizations (Flourish, Infogram, Datawrapper, Tableau) each load their own scripts and can significantly degrade page performance. Mitigations:

- Break content across multiple pages.
- Substitute static images for interactive visualizations.
- Link to data sources rather than embedding.

#### Plugin load overhead

Every activated plugin adds page-load overhead. Best practices:

- Audit plugins regularly at `Plugins > Installed Plugins`.
- Deactivate unused Newspack-managed plugins.
- **Remove** (not just deactivate) unused third-party plugins — inactive plugins still present exploitable attack surface.
- Check the Newspack approved plugins spreadsheet before installing any non-native plugin.
- Submit unlisted plugins for review via the Google Form at `https://forms.gle/ofFmGKqoaPWJm2bm8`. Reviews turn around weekly.
- Do not submit plugins showing "hasn't been tested with the latest X major releases of WordPress" — wait for the plugin to be updated first.
- Plugin ratings: Red (blocked, alternatives suggested), Yellow (provisionally approved), Green (fully approved).

#### Homepage performance

- Limit third-party scripts; evaluate each for genuine business necessity.
- Avoid autoplay video, live weather widgets, and live social feed widgets — high risk for CLS and FID scores.
- Optimize logos and large graphics before upload, especially PNG files.
- Test before implementing major layout or widget additions.

#### Blocking AI crawlers

Add blocking directives to `robots.txt` via `Yoast SEO > Tools > File editor`. Crawlers to block:

```text
User-agent: GPTbot
Disallow: /

User-agent: ChatGPT-User
Disallow: /

User-agent: CCBot
Disallow: /

User-agent: Google-Extended
Disallow: /

User-agent: PerplexityBot
Disallow: /
```

Blank lines between agent blocks are required for correct parsing. `robots.txt` is advisory only — crawler compliance is not guaranteed.

Yoast Premium users: use `Yoast SEO > Settings > Advanced > Crawl optimization` toggles instead of manual editing.

#### Security hardening

- Enforce passwords of 16+ characters (Newspack includes a built-in password strength meter).
- Recommend 1Password (free for journalists; discounted for journalism organizations) or Bitwarden as password managers.
- Enforce 2FA: `Newspack > Connections > Jetpack SSO > Force two-factor authentication`. Can be applied per role (Administrator, Editor, Author, Contributor).
- Enable generic "not found" error messages on login to prevent username enumeration.
- Keep Administrator-level accounts to the minimum necessary; most editorial staff need Editor or below.
- Audit user accounts regularly; remove stale or unnecessary accounts.

---

### Plugin management

_Docs: [help.newspack.com/plugins-themes/newspack-plugins/](https://help.newspack.com/plugins-themes/newspack-plugins/) · [help.newspack.com/plugins-themes/third-party-services-integrations/](https://help.newspack.com/plugins-themes/third-party-services-integrations/)_

Before installing any third-party plugin:

1. Check the approved plugins spreadsheet (link via TAM or Newspack dashboard).
2. If unlisted, submit via `https://forms.gle/ofFmGKqoaPWJm2bm8`.
3. Wait for the weekly review (approximately 7 days) for a Red/Yellow/Green rating.
4. Install only on Yellow (provisional) or Green (approved) ratings.
5. Establish a performance baseline before activation; re-test after configuring.

**Third-party data flows** (relevant for privacy policy and GDPR assessments):

| Service           | Data sent by Newspack                                                     |
| ----------------- | ------------------------------------------------------------------------- |
| Mailchimp         | Reader contact data; newsletter interactions                              |
| ActiveCampaign    | Reader contact data prefixed with `NP_` metadata; newsletter interactions |
| Campaign Monitor  | Reader contact data; newsletter interactions                              |
| Constant Contact  | Reader contact data; newsletter interactions                              |
| Google Analytics  | Custom events from Newspack Campaigns and News Tagging Guide              |
| Parse.ly          | Auto-configured basic setup if plugin is installed but unconfigured       |
| Google Ad Manager | Ad impressions, clicks (via Newspack Ads)                                 |
| Broadstreet       | Ad data (via Newspack Ads)                                                |
| Stripe            | Payment events via webhooks → triggers WooCommerce order + GA event       |
| Salesforce        | Donation data from WooCommerce transactions                               |

Parse.ly auto-configuration gotcha: if the Parse.ly WordPress plugin is installed but unconfigured, Newspack automatically applies basic settings. Publishers who installed Parse.ly independently should configure it before Newspack touches it.

ActiveCampaign metadata conflict gotcha: Newspack sends all metadata with an `NP_` prefix. Existing fields with that prefix in an ActiveCampaign account could be overwritten.

---

## Sub-skills (reference files)

The following reference files provide detailed procedures and configuration guidance for Newspack:

| File                                                                           | Contents                                                                                                                                                                   |
| ------------------------------------------------------------------------------ | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| [references/optimization-onboarding.md](references/optimization-onboarding.md) | Prelaunch checklist (reCAPTCHA, GAM, Jetpack), domain transfer procedure, and Core Web Vitals optimization.                                                                |
| [references/publishing-appearance.md](references/publishing-appearance.md)     | Detailed theme customization (Customizer vs. Block Theme), template inventory, and federated/network site configuration.                                                   |
| [references/revenue.md](references/revenue.md)                                 | Comprehensive advertising setup (GAM/Broadstreet), sponsored content treatment, and reader revenue flows (donations, subscriptions, and checkout).                         |
| [references/engagement.md](references/engagement.md)                           | Campaign prompt management, newsletter composition and ESP integration, content gating (metering/paywalls), and Reader Activation System (RAS) setup.                      |
| [references/analytics.md](references/analytics.md)                             | Full GA4 custom event tracking setup, custom dimension mapping, and integration with third-party tools like Metorik and Parse.ly.                                          |
| [references/additional-plugins.md](references/additional-plugins.md)           | Guidance for Newspack-specific plugins beyond the core suite: migration tools, electoral/sports specializations, and third-party integrations (Salesforce, Hubspot, etc.). |

### Planned reference split-outs (not yet in repo)

If this skill grows, the following reference files are good candidates to split out into dedicated docs. These files do not exist yet (intentionally no links).

| Planned file name                           | Notes / current home                                                                           |
| ------------------------------------------- | ---------------------------------------------------------------------------------------------- |
| `references/onboarding-domain.md`           | Domain transfer guidance lives in `references/optimization-onboarding.md` today.               |
| `references/onboarding-jetpack.md`          | Jetpack connection guidance lives in `references/optimization-onboarding.md` today.            |
| `references/onboarding-csv-migration.md`    | CSV migration constraints live in `references/optimization-onboarding.md` today.               |
| `references/advertising-gam.md`             | GAM setup, placements, and suppression guidance live in `references/revenue.md` today.         |
| `references/reader-revenue.md`              | Reader revenue donation/subscription guidance lives in `references/revenue.md` today.          |
| `references/ras-setup.md`                   | RAS setup details live in `references/engagement.md` today.                                    |
| `references/analytics-custom-dimensions.md` | GA4 custom dimensions and reporting guidance live in `references/analytics.md` today.          |
| `references/federated-sites.md`             | Federated/Network configuration guidance lives in `references/publishing-appearance.md` today. |
| `references/content-gating.md`              | Content gating and metering guidance live in `references/engagement.md` today.                 |
| `references/listings.md`                    | Listings guidance lives in `references/engagement.md` today.                                   |

---

## Verification

After completing any major configuration area, verify:

- **Advertising**: Navigate to a published article and confirm an ad unit renders in the configured placement. Check browser DevTools for GAM or Broadstreet script loading.
- **Reader revenue**: Complete a test donation using Stripe test mode. Confirm the order appears in WooCommerce and a receipt email is delivered.
- **Campaigns**: Activate a test prompt and visit the site as a logged-out user to confirm it displays at the expected trigger point.
- **Newsletters**: Send a test email via the Testing panel to a known address before sending to the full list.
- **RAS**: Register a test account via the Registration block. Confirm the user appears in WordPress users, receives the verification email, and syncs to the ESP.
- **Content gating**: Visit a gated article as a logged-out user and confirm the gate renders at the expected paragraph count. Test that the gate does not appear after authenticating.
- **Analytics**: After the 24–72 hour wait, visit `GA4 > Reports > Realtime` while performing a test action (registration, prompt click) to confirm events are firing and parameters are visible.
- **robots.txt**: Visit `https://yoursite.com/robots.txt` and confirm the AI crawler blocking entries are present and correctly formatted.
- **Admin email**: Confirm `Settings > General` does not show `newspack@a8c.com`.
- **2FA enforcement**: Log in with a role-scoped test account and confirm 2FA is prompted.

---

## Failure modes

- **Ads not rendering** — Newspack Ads plugin may not be activated. Confirm at `Dashboard > Newspack > Advertising`. If a third-party ad plugin is also installed, it may conflict; Newspack support will not diagnose third-party ad plugin issues.
- **Sponsor attribution not displaying** — Newspack Sponsors requires a Newspack theme. If the active theme is not a Newspack theme, attribution will not render regardless of configuration.
- **Stripe not available** — Stripe is not supported in all countries. Use an alternative WooCommerce payment gateway and document the substitution for the TAM.
- **WooCommerce Name Your Price or Subscriptions missing** — these are premium plugins included only for paid Newspack customers. Free-tier sites cannot use flexible amounts or recurring subscriptions.
- **Content gating not working** — WooCommerce Memberships must be configured by the Newspack support team before content gating can be used. Self-service initial setup is not supported.
- **Anonymous metering bypass** — readers in incognito/private browsing mode will bypass anonymous metering limits. This is a design constraint of browser local storage tracking; it cannot be remedied without requiring registration.
- **GA4 custom dimensions empty** — custom dimensions must be created in GA4 after the 24–72 hour wait for events to populate the parameter dropdown. Creating dimensions too early means no parameters will appear to select. Also confirm the Measurement Protocol API secret and Measurement ID are correctly entered in `Newspack > Settings > Connections`.
- **GA4 custom event parameters invisible in reports** — parameters are collected but not visible until a matching custom dimension is created for each parameter. Collection and visibility are independent steps.
- **np_donation_new event showing no data** — this event was removed in the May 2024 refactor. Update reports to use `np_modal_checkout_interaction` with `action = form_submission`.
- **Newsletters blocked from sending** — Mailchimp and Constant Contact require clicking "Update Sender" after filling in From fields. If this step was missed, re-enter the From name and email, click Update Sender, then retry sending.
- **reCAPTCHA silently not working** — the reCAPTCHA version (v2 vs v3) must match exactly in both the reCAPTCHA account and `Newspack Settings`. A version mismatch does not produce an error — spam prevention simply stops working.
- **Domain transfer fails** — most failures are caused by an incorrect EPP auth code (case-sensitive) or the domain being under an ICANN 60-day lock. Check both before re-attempting.
- **RAS UI styling not matching site theme** — RAS components use independent styling and do not inherit the site's theme colors or typography. Custom CSS overrides are required.
- **Parse.ly receiving unexpected data** — if the Parse.ly plugin is installed but unconfigured, Newspack automatically applies a basic configuration. This is intentional behavior but can surprise publishers who installed the plugin independently.
- **ActiveCampaign metadata conflicts** — Newspack uses the `NP_` prefix for all metadata fields. Pre-existing fields in the publisher's ActiveCampaign account with that prefix may be overwritten.

---

## Escalation

Escalate to the Newspack Technical Account Manager (TAM) via Slack (`#newspack-help`) when:

- Initiating domain transfer — the TAM must coordinate timing with the migration.
- Setting up content gating — WooCommerce Memberships cannot be self-configured and strategy alignment is required.
- Enabling RAS — the TAM should review business model fit before activation.
- Migrating Co-Authors Plus Guest Authors to Guest Contributors — must be executed by the Newspack support team.
- The site is in a country where Stripe is not available and an alternative payment gateway is needed.
- A third-party plugin has been submitted for review and the traffic-light rating is Red — Newspack will suggest alternatives.
- Drift detection (via `wp-github-deploy`) reveals unexpected files on production.
- A plugin or theme is not on the approved list and the weekly review result is needed urgently.
- Any situation where WooCommerce Subscriptions, Memberships, or Name Your Price behavior is unexpected — these premium plugins require Newspack support involvement.
- Data Dashboard access or onboarding is needed — signup is at `newspack.com/analytics-bundle/`.
