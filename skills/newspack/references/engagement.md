# Newspack Engagement Reference

## Table of Contents

- [Campaigns and Popups](#campaigns-and-popups)
- [Newsletters](#newsletters)
- [Content Gating](#content-gating)
- [Audience Management and Reader Segmentation](#audience-management-and-reader-segmentation)
- [Listings](#listings)
- [Social Media Integration](#social-media-integration)
- [Common Configuration Gotchas](#common-configuration-gotchas)

---

## Campaigns and Popups

_Docs: [help.newspack.com/engagement/campaigns/](https://help.newspack.com/engagement/campaigns/)_

### Core Concepts

A **prompt** is the individual CTA element a reader sees. A **campaign** is an internal organizational container that groups related prompts under a shared goal. Campaign names are never visible to readers.

Prompts come in three display modes:

- **Overlay (pop-up):** Appears at the top, bottom, or center of the page. Triggered by time-on-page or scroll-depth thresholds. Can be scoped to specific categories/tags or applied sitewide.
- **Inline:** Embedded within article content. Placement is calculated by block count or content percentage. Supports taxonomy scoping.
- **Above-header:** Persistent banner displayed on every page until frequency conditions are met or the reader falls outside the active audience segment.

### Setup Procedure

1. Install and activate the Newspack Campaigns plugin.
2. Navigate to **Newspack Dashboard > Campaigns**.
3. Click **Add New Campaign**, enter a name. (A campaign can also be created during prompt editing.)
4. Create prompts and assign them to the campaign.
5. Configure overlay prompt triggers (time-on-page or scroll-depth) and positioning (top/bottom/center).
6. Set inline prompt placement by block count or content percentage.
7. Activate prompts individually or bulk-activate all prompts in a campaign via the three-dot menu on the campaign row.

### Audience Targeting and Segmentation

Prompts can be shown or suppressed based on real-time reader signals:

- Subscription status
- Donation history
- Article consumption volume

The dedicated segmentation system allows fine-grained audience definitions. Frequency controls determine how often any given reader sees a prompt.

### Reader Activation Defaults

Publishers using the Reader Activation System (RAS) can shortcut initial setup by switching the campaign selector dropdown from **Active Prompts** to **Reader Activation Defaults**. This activates a curated set of pre-configured prompts aligned with Newspack's recommended engagement strategy. This option only appears for publishers who have Audience Management / RAS configured.

### Email Suppression

Append the `utm_suppression` query parameter to newsletter links to automatically exclude newsletter-sourced traffic from newsletter signup prompts. This prevents showing redundant CTAs to already-subscribed readers. The parameter must be appended manually or via the newsletter platform — it is not automatic.

### Campaign Management Operations

All available from the three-dot menu on a campaign:

- **Duplicate** — clone a campaign and all its prompt assignments
- **Rename** — update the internal label
- **Archive** — hide without deleting
- **Delete** — removes the campaign container only; individual prompts are not deleted and become unassigned

Bulk activation and deactivation of all prompts within a campaign is available from the same three-dot menu.

---

## Newsletters

_Docs: [help.newspack.com/engagement/newsletters/](https://help.newspack.com/engagement/newsletters/)_

### Supported Email Service Providers

- **ActiveCampaign** — requires API URL and API key
- **Constant Contact** — requires API key, secret, redirect URIs, and OAuth2 authorization
- **Mailchimp** — requires API key
- **Manual provider** — generates HTML you copy into any external ESP, then mark as sent

### Initial Setup

1. Navigate to **Newsletters > Settings**.
2. Select your provider.
3. Enter credentials:
    - ActiveCampaign: API URL + API key
    - Constant Contact: API key, secret, redirect URIs
    - Mailchimp: API key
4. Click **Save Settings**.

### Creating a Newsletter

1. Go to **Newsletters > All Newsletters > Add New Newsletter**.
2. Choose one of the nine pre-built layouts or select **Blank Newsletter**.
3. Click **Use Selected Layout**.
4. Edit content in the block editor.

The settings panel covers: campaign name, subject line, preview text, folder organization, sender name and email, audience/list/segment targeting, and a testing panel for sending preview emails.

Toggle **Public Newsletter** to publish the content as a web-accessible page on the site.

### Available Blocks

The editor uses a restricted, email-optimized block set: Paragraph, Heading, List, Quote, Image, Buttons, Columns, Group, Separator, Spacer, Social Icons, Embed, Share, and the **Post Inserter** block.

The **Post Inserter** block is an email-friendly variant of the Content Loop block that pulls published post content into the newsletter. Note: it only includes posts published before the newsletter was created.

Block constraints:

- Image blocks cannot use left/right alignment (only center/none).
- Columns and Group blocks support only one level of nesting.
- Social Icons supports: Bluesky, Facebook, Instagram, LinkedIn, TikTok, Tumblr, Twitter/X, WordPress.

### Sending a Newsletter

1. Finalize content and settings.
2. Select audience/list/segment in the **Send To** field.
3. Fill in From sender fields.
4. For Mailchimp or Constant Contact: click **Update Sender** (required or sending will be blocked).
5. Send a test email via the Testing panel.
6. Click **Send** (top right).

For Manual provider: copy the generated HTML, paste into your ESP, then click **Mark as sent** in Newspack.

### Custom Layouts

- **Save:** With the Newsletter tab active, scroll to the Layout panel, click **Save New Layout**, enter a name.
- **Edit:** Go to **Newsletters > Add New**, select the layout from the Saved Layouts tab, make changes, then click **Update Layout** to overwrite or **Save New Layout** to preserve the original.
- **Reset:** In the Newsletter tab > Layouts panel, click **Reset Layout** (all session changes are discarded).

### Dynamic Content

- **Personalization merge tags** — insert recipient-specific data.
- **Conditional tags** — show/hide content based on recipient data.
- **UTM auto-injection** — all outbound links receive `utm_campaign`, `utm_source`, and `utm_medium=email` derived from the campaign name and send list. Note: third-party integrations may overwrite these.

### Subscription List Management

Go to **Newsletters > Settings > Subscription Lists > Add New**. Enter a title and description, use the Provider dropdown to select an audience/list, click Publish, enable in Subscription Lists settings, then click **Save Subscription Lists**.

### Mailchimp-Specific Requirements

Newspack strongly recommends using a custom footer that includes these required merge tag placeholders:

- `*|EMAIL|*`
- `*|UNSUB|*`
- `*|LIST:ADDRESSLINE|*`

Omitting these placeholders will cause the footer to fail Mailchimp compliance requirements.

---

## Content Gating

_Docs: [help.newspack.com/engagement/content-gating/](https://help.newspack.com/engagement/content-gating/)_

### Prerequisites

- Contact your Newspack Technical Account Manager (TAM) via **Slack (#newspack-help)** before configuring content gating to align your business model with available technical options.
- WooCommerce Memberships must be configured by the Newspack support team — this is not a self-service initial configuration.
- The Reader Activation System (RAS) must be set up first.

### Five Gate Patterns

| Pattern                             | Description                                              |
| ----------------------------------- | -------------------------------------------------------- |
| Registration Wall                   | Requires email sign-up via the Reader Registration block |
| Donation Wall                       | Prompts reader to donate via modal checkout              |
| Pay Wall with One Tier              | Single subscription product                              |
| Pay Wall with Two Tiers             | Multiple subscription options                            |
| Pay Wall with One Tier and Metering | Subscription combined with a view-count limit            |

Insert the chosen block pattern into article content. The gate activates when a reader reaches the configured threshold.

### Controlling Gate Trigger Position

Two methods:

1. **Default paragraph count** — set globally; controls how much content is visible before the gate appears across all gated posts.
2. **WordPress More block** — insert the More block inside an individual post for per-article customization, overriding the global paragraph count.

### Display Styles

- **Inline** — appears within the article flow, with an optional gradient fade on the final visible paragraph.
- **Overlay modal** — configurable sizing from Extra Small to Full Width with adjustable positioning.

### Metering Configuration

Metering adds a time-windowed article view allowance. Set separately for anonymous and authenticated readers. Configure:

- Allowed article count per period
- Reset frequency
- Anonymous limit (tracked via browser local storage — bypassable with incognito mode)
- Authenticated limit (stored as secure backend user metadata — cannot be bypassed)

### Developer Hooks

PHP:

```php
// Check whether a post is currently restricted
Newspack\Content_Gate::is_post_restricted();

// Validate whether metering is active for the current context
Newspack\Metering::is_metering();
```

JavaScript:

- `metering_restricted` RAS activity event — fires when anonymous metering restricts access, carrying the post ID and expiration timestamp

### Registration Wall Details

- Uses the **Reader Registration** block.
- Integrates **reCAPTCHA v3** for spam protection.
- Includes a configurable newsletter subscription opt-in toggle (enabled by default).
- Supports customizable email placeholder text.

---

## Audience Management and Reader Segmentation

_Docs: [help.newspack.com/engagement/audience-management/](https://help.newspack.com/engagement/audience-management/)_

### Reader Activation System (RAS) Overview

RAS is the central suite enabling frictionless authentication, segmentation, content gating, and subscriber conversion. Authentication options:

- Email and password
- One-time password code
- Magic link sent to inbox

Readers provide only an email address to register; a password is required only when managing their account.

### RAS Setup Wizard (in order)

1. Designate legal pages — publish and designate your **Privacy Policy** and **Terms of Service** pages.
2. Connect your ESP (Mailchimp or ActiveCampaign) via API key and enable at least one subscription list.
3. Configure transactional email sender name, sender address, and contact address.
4. Set up **reCAPTCHA v3** under **Newspack > Connections**.
5. Ensure Reader Revenue is configured with WooCommerce and Stripe.
6. Customize default campaign prompts (registration overlay, newsletter overlay, donation overlay, and inline variants).
7. Click **Enable Reader Activation**.

### Transactional Email Templates

Eight templates are available, supporting these universal placeholders:

- `SITE_TITLE`
- `SITE_CONTACT`
- `SITE_URL`

Template-specific placeholders include: `MAGIC_LINK_URL`, `VERIFICATION_URL`, `PASSWORD_RESET_LINK`, `DELETION_LINK`.

### Audience Segmentation

The reader funnel is divided into three tiers:

- **Top funnel** — casual/anonymous readers
- **Mid funnel** — registered readers not yet paying
- **Lower funnel** — subscribers and donors

Campaign prompts can be targeted to each funnel stage. Targeting signals include subscription status, donation history, and article consumption volume.

### Post-Activation Advanced Settings

Navigate to **Newspack > Engagement > Reader Activation > Show Advanced Settings** to configure:

- Forced registration at WooCommerce checkout
- Pre/post-checkout messaging
- Newsletter list ordering and defaults
- ESP-specific sync options:
    - **Mailchimp:** audience ID, default reader status, metadata field prefix (default: `NP_`)
    - **ActiveCampaign:** master list selection

### Styling Note

RAS UI components use independent styling and do **not** inherit the site's colors or typography from the Newspack Theme customizer. Custom CSS overrides may be required to match site branding.

---

## Listings

_Docs: [help.newspack.com/engagement/listings/](https://help.newspack.com/engagement/listings/)_

### Four Listing Types

| Type                 | Use Case                                                                    |
| -------------------- | --------------------------------------------------------------------------- |
| Events               | Date-bound items with optional date sorting                                 |
| Generic Listings     | Editorial content for diverse list formats (roundups, reviews, gift guides) |
| Marketplace Listings | Non-editorial, third-party, or revenue-generating content                   |
| Places               | Location-based content with optional map integration                        |

Each type has its own post type, configurable permalink slug, and optional archive pages.

### Initial Configuration

1. Install and activate the Newspack Listings plugin.
2. Go to **wp-admin > Listings settings**.
3. Configure permalink slugs per listing type and an optional site-wide prefix.
4. Enable listing type archives, category/tag archives, and grid layout in **Automated Directory** settings.

### Creating a Listing

1. Go to **wp-admin > Listings**, select the listing type.
2. Add content using the Gutenberg editor.
3. Optionally apply a **Business Listing** (Places/Marketplace) or **Event** block pattern for pre-formatted layout. Block patterns require Jetpack.
4. Publish. Listings must be in Published status to appear in any list.

### Curated List Block

Insert the **Curated List** block into a post or page from the Newspack block category. Choose the population mode at block creation — **this choice is permanent**; switching modes requires deleting the block and rebuilding.

**Specific mode** (manual/static):

- Search for or select recent listings.
- Order items manually.
- List does not change until hand-edited.

**Query mode** (dynamic/auto-updating):

- Set listing type filter, author, category/tag inclusion/exclusion filters.
- Configure result limit and sort order.
- Enable **Load More** if desired.
- List updates automatically when new matching listings are published.

### Featured Listings

Set featured status per listing via the Featured Listing sidebar panel while editing. Configure:

- Priority on a **1–9 scale** (1 = highest)
- Optional expiration date — expiration countdown begins only after first publication, not when the date is set

### Map Display

Available for Place and Marketplace listing types. Requirements:

- Jetpack must be installed and active.
- A Mapbox API token is required (configured following Jetpack's instructions).
- Capped at **100 locations** for performance.

### Self-Serve Listings (Experimental)

Allows monetization of listings via WooCommerce products. Customers submit via a **Listings: Self-Serve Form** block. Customer restrictions:

- Cannot self-publish — submissions require editor review
- Cannot create categories or tags
- Cannot access the WordPress dashboard
- Cannot see Featured Listing controls
- Cannot view other users' media files

Requires WooCommerce and WooCommerce Subscriptions.

---

## Social Media Integration

_Docs: [help.newspack.com/engagement/social-media/](https://help.newspack.com/engagement/social-media/)_

### Underlying System

Newspack delegates social sharing entirely to **Jetpack Social** (formerly Jetpack Publicize). There is no separate Newspack-built sharing infrastructure.

### Configuration

1. Navigate to **Newspack > Settings** in the WordPress admin dashboard.
2. Select the **Social** tab within the Engagement Wizard.
3. Click the **Configure** link adjacent to the Publicize option.
4. Follow Jetpack's support documentation to enable sharing and link social accounts.

Account linking (OAuth) for individual platforms (Facebook, Twitter/X, LinkedIn, etc.) is performed within Jetpack's own UI. Newspack's documentation does not reproduce those platform-specific steps.

### Post-Connection Options

Once accounts are connected, publishers can customize the snippet text (preview text and metadata) that appears on social platforms when a link is shared. This is done per-post at publish time or through Jetpack's sharing interface.

---

## Common Configuration Gotchas

### Campaigns

- **Deleting a campaign does not delete its prompts.** Prompts become unassigned but remain in the system.
- **Above-header prompts display continuously on every page** until frequency conditions are met or segmentation no longer matches — configure frequency controls carefully to avoid reader fatigue.
- **`utm_suppression` is not automatic.** It must be manually appended to newsletter links or configured in the newsletter platform.
- **Reader Activation Defaults** only appears in the campaign selector for publishers who have Audience Management / RAS configured.

### Newsletter Gotchas

- **Mailchimp and Constant Contact require clicking "Update Sender"** after filling in the From name and email fields. Skipping this step blocks sending.
- **ActiveCampaign segments must be created manually** in the ActiveCampaign dashboard — the plugin cannot create them automatically.
- **Newsletters drafted in Newspack cannot be further edited in the ActiveCampaign dashboard** after syncing.
- **Mailchimp does not support Advanced Segments** in automations and external integrations.
- **Post Inserter only includes posts published before the newsletter was created.** Posts published after that date will not appear, even if the newsletter is scheduled for future delivery.
- **Mailchimp tags/segments are only visible in the ESP dashboard** if at least one contact has been added to them.
- **Custom CSS rendering is not guaranteed to be consistent** across email clients.
- **Third-party ESP integrations may overwrite auto-injected UTM parameters.**
- **Constant Contact's List and Segment targeting options are mutually exclusive.**
- **Mailchimp requires an audience to be selected** before group/segment/tag targeting options appear.

### Content Gating Gotchas

- **Anonymous metering can be bypassed** by readers using incognito or private browsing mode (browser local storage is session-scoped).
- **WooCommerce Memberships must be configured by Newspack support** — it is not a self-service initial setup.
- **Publishers must contact their TAM before configuring content gating** — strategy alignment is required.
- **Publishers with three or more subscription plans** should highlight only one or two to avoid reader choice paralysis.
- Once a metered reader accesses content within their allowed limit, **that content remains accessible for the remainder of the metering period** even after the overall limit is reached.

### Audience Management

- **RAS UI components do not inherit Newspack Theme styles.** Colors and typography must be overridden with custom CSS.
- **Publishers who do not collect donations must immediately disable donation prompts** in Newspack > Campaigns to avoid irrelevant CTAs.
- **To auto-enroll new registrants in a single newsletter list**, that list must be the only one enabled. Multiple enabled lists prevent single-list auto-enrollment.
- **The WooCommerce password setup link email** can be toggled off under **WooCommerce > Settings > Accounts & Privacy** — this is a non-obvious setting that may cause confusion if left enabled unintentionally.
- **The Reader Revenue platform (WooCommerce + Stripe) must be fully configured** before RAS can be enabled.

### Listings Gotchas

- **List mode (Specific vs. Query) is permanent at block creation.** Switching modes requires deleting the Curated List block and rebuilding it.
- **Listings must be in Published status** to appear in any list. Trashing or drafting a listing removes it from all lists.
- **Query mode lists auto-update silently** when new matching listings are published — this can change live published content without an explicit editorial action.
- **Map display is capped at 100 locations** for performance.
- **Map functionality requires both Jetpack and a Mapbox API token.**
- **Block patterns require Jetpack** to be installed and active.
- **Featured listing expiration countdown begins only after first publication**, not when the expiration date is set.
- **Self-serve customer submissions require editor review** before going live — customers cannot self-publish.

### Social Media

- **Nearly all procedural detail lives in Jetpack's external documentation**, not in Newspack's own help system. Consult Jetpack docs for platform-specific account connection steps.
