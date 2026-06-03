# Newspack Revenue Reference

## 1. Advertising Setup

_Docs: [help.newspack.com/revenue/advertising/](https://help.newspack.com/revenue/advertising/)_

### Activation

Navigate to **Dashboard > Newspack > Advertising** to activate the Newspack Ads plugin. No ad server integrations are available until this plugin is explicitly activated.

### Supported Ad Servers

**Google Ad Manager (GAM)** is the primary and most fully documented integration. Before connecting, sign up for a GAM account using the Newspack-provided guide. GAM unlocks the full feature set including Ad Block Recovery and Ad Refresh Control.

**Broadstreet** is an alternative ad network targeting community and local news publishers. Configuration is handled through the same Advertising Wizard after selecting Broadstreet as the integration.

### Ad Placement Options

**Global Placements** — sitewide ad positions configured in the Advertising Wizard. Applied automatically across all eligible content without per-post intervention.

**Newspack Ad Block** — a Gutenberg block for placing individual ad units directly within post or page content. Use this when a specific layout position requires a manually placed unit.

**Ad Unit Widget** — places ad units in sidebar or widget areas. Configured through the standard WordPress widget interface.

**In-Content Ad Insertion** — automatically inserts ads within the body of article content at configured intervals. Configured separately from global placements.

### Ad Suppression

Ad Suppression rules can exclude ads from specific posts, pages, or content types. Navigate to the suppression settings within the Advertising Wizard to define exclusion rules. Use this for editorial content where ads are inappropriate (e.g., sensitive topics, sponsored content pages where a different ad policy applies).

### GAM-Specific Features

**Ad Block Recovery** — serves fallback content or messaging to users with ad blockers enabled. Configured under the Google Ad Manager section of the Advertising Wizard. Allows publishers to recapture revenue lost to ad blocking.

**Ad Refresh Control** — manages how often ad impressions refresh. Misconfiguring refresh rates can violate GAM policy or degrade user experience; configure within the bounds described in the Newspack GAM tutorials.

### Media Kit and Sales Enablement

Newspack provides a **Media Kit** guide and an **Industry Standard Ad Unit Sizes** reference to support publisher sales to direct advertisers. An **Ad Glossary** is also available for onboarding less technical staff.

### Newsletter Ad Management

Separate from on-site ads, Newspack supports ad management for email newsletters. Accessible from the same revenue section and documented separately at `help.newspack.com/revenue/newsletter-ad-management`.

### Third-Party Ad Plugins

Newspack does not support plugins such as Advanced Ads. If a third-party ad plugin is installed, Newspack support is limited to turning ads off and checking for PHP errors. Configuration assistance is not provided. Conflicts with the Newspack Ads plugin should be expected.

---

## 2. Reader Revenue: Donations and Subscriptions

_Docs: [help.newspack.com/revenue/reader-revenue/](https://help.newspack.com/revenue/reader-revenue/)_

### Platform Choice

Publishers choose one of two paths:

**Newspack-native** — built on WooCommerce. Supports one-time and recurring donations, flexible "name your price" amounts, Salesforce CRM integration, and the full subscription lifecycle.

**News Revenue Hub** (fundjournalism.org) — an external platform optimized for journalism fundraising. Configuration in Newspack requires only organization details, a Salesforce Campaign ID, and a donor landing page selection.

### Newspack-Native Setup (Three Stages)

#### Stage 1 — Payment Gateway

Enable Stripe via the Stripe tab in the Revenue settings. Stripe is the recommended processor but is not available in all countries. Publishers in unsupported regions must configure an alternative WooCommerce-compatible payment gateway. Selecting the wrong gateway or skipping this step will prevent checkout from functioning.

#### Stage 2 — Salesforce CRM (Optional)

Create a Connected App within Salesforce to obtain a Consumer Key and Consumer Secret. Enter both credentials in Newspack settings. This setup is non-trivial; Newspack documents it separately. Skip this stage if CRM sync is not required.

#### Stage 3 — Donations Block and Landing Page

Configure default donation amounts and tiers in the Donations block settings. Edit and publish a donations landing page that includes the Donations block. This is the primary reader-facing entry point for contributions.

### Key Blocks

**Donate Block** — the main front-end component readers interact with to choose an amount and complete a contribution.

**Checkout Button Block** — an alternative entry point that triggers the checkout flow, useful for embedding a call-to-action in editorial content.

**Modal Checkout** — an inline checkout experience that avoids a full page redirect. Reduces friction in the conversion flow.

### Premium Plugin Dependencies

Two WooCommerce extensions are required for key capabilities and are bundled at no extra charge for paid Newspack customers only:

| Plugin                      | Capability                        | Tier Required |
| --------------------------- | --------------------------------- | ------------- |
| WooCommerce Name Your Price | Flexible/custom donation amounts  | Paid          |
| WooCommerce Subscriptions   | Recurring monthly/annual payments | Paid          |

Free-tier Newspack sites cannot offer flexible donation amounts or recurring subscriptions.

### Subscription Lifecycle Management

- **Subscription Confirmation** — configurable confirmation flow after a reader subscribes.
- **Custom Email Receipts** — branded transactional emails for donations and subscription payments.
- **Gift Subscriptions** — allows readers to purchase subscriptions on behalf of others.
- **Manual Subscription Payment Management** — set manual payment methods or cancel subscriptions for individual subscribers.
- **Payment Retries and Expiration** — automated retry logic for failed recurring payments; expiration handling when retries are exhausted.

### Analytics

**Metorik** is the supported analytics integration for tracking donation and subscription data. During onboarding, Newspack builds customized Metorik dashboards; publishers build additional custom reports themselves.

---

## 3. Sponsored Content Workflows

_Docs: [help.newspack.com/revenue/sponsored-content/](https://help.newspack.com/revenue/sponsored-content/)_

### Plugin Activation

Install and activate the **Newspack Sponsors** plugin via Dashboard > Plugins. The plugin is open source at `github.com/Automattic/newspack-sponsors`.

**Requirement:** Newspack Sponsors requires a Newspack theme. Sponsor information will not render on non-Newspack themes.

### Sponsorship Types

**Native Content** — a third party creates the content without editorial staff involvement. Receives prominent treatment: sponsor flag, sponsor byline, disclaimer with toggleable tooltip, and logo appearing on single posts, archive pages, and search results.

**Underwritten Content** — editorial staff authors the content; a sponsor funds its production. Lighter-touch attribution: a small attribution block appears at the top of single post views only. No flag or archive/search treatment.

This distinction is set via the **Sponsorship Scope** field when creating a sponsor record.

### Creating a Sponsor Record

Navigate to **Advertising > Sponsors > Add New**. Available fields:

- **Sponsor Name** — display name used in attribution
- **Sponsorship Scope** — Native Content or Underwritten Content
- **Sponsor URL** — linked from the attribution block and logos
- **Flag Override** — custom text for the colored label (overrides site-wide default)
- **Disclaimer Override** — custom disclaimer text with a toggleable tooltip (overrides site-wide default)
- **Byline Prefix** — replaces normal author attribution on sponsored posts
- **Sponsor Logo** — uploaded from the WordPress Media Library

### Default Copy Configuration

Configure site-wide defaults for disclaimer text, byline prefix, and flag label copy under **Advertising > Sponsors > Settings**. Per-sponsor overrides take precedence over these defaults.

Configure sponsor label colors under **Appearance > Customize > Sponsored Content**.

### Assigning Sponsors to Content

**Category/Tag Assignment** — assign categories or tags to a sponsor record. All posts within those taxonomies automatically receive sponsor treatment. This is the lower-maintenance option for ongoing sponsorship campaigns.

**Direct Post Assignment** — assign individual posts directly to a specific sponsor. Use for targeted or one-off sponsorships.

**"Show on posts only if direct sponsor?" toggle** — when enabled on a category/tag-level sponsor, restricts the sponsor's branding to only posts explicitly assigned to that sponsor. Prevents accidental attribution to all content in a broad category.

### Multi-Sponsor Behavior

When multiple sponsors are associated with a single piece of content, all sponsor logos are displayed. However, only the first sponsor's disclaimer and flag label are rendered. Secondary sponsor disclaimers are suppressed. This limitation affects multi-sponsor campaigns and should be accounted for when structuring sponsorship agreements.

---

## 4. Membership Tiers and Gating Strategy

### Subscription-Based Access

WooCommerce Subscriptions (paid tier required) enables recurring monthly and annual payment plans. Subscription tiers correspond to WooCommerce product configurations.

### Modal Checkout and Conversion

The Modal Checkout reduces friction by keeping readers on-page during the conversion event. This is particularly relevant for gating strategies where a reader hits a paywall and needs a low-friction path to subscribe without losing their reading context.

### CRM and Donor Segmentation

The Salesforce integration enables downstream segmentation of donors and subscribers by tier, frequency, and campaign. Publishers using News Revenue Hub gain Salesforce Campaign ID-based tracking directly.

For publishers requiring dedicated content gating (restricting post access by subscription level), see the [content-gating reference](content-gating.md) — WooCommerce Memberships must be configured by the Newspack support team before content gating can be used.

---

## 5. Common Configuration Gotchas and Failure Modes

### Advertising

| Failure Mode                           | Cause                                                     | Resolution                                                                        |
| -------------------------------------- | --------------------------------------------------------- | --------------------------------------------------------------------------------- |
| No ad server options visible           | Newspack Ads plugin not yet activated                     | Navigate to Dashboard > Newspack > Advertising and activate the plugin            |
| Third-party ad plugin conflicts        | Installing Advanced Ads or similar alongside Newspack Ads | Remove the third-party plugin; Newspack will not assist with its configuration    |
| GAM features unavailable               | Using Broadstreet or no integration selected              | Switch to Google Ad Manager integration in the Advertising Wizard                 |
| Ads appearing on inappropriate content | No suppression rules configured                           | Set Ad Suppression rules for relevant post types, categories, or individual posts |

### Reader Revenue

| Failure Mode                          | Cause                                                        | Resolution                                                                                                                       |
| ------------------------------------- | ------------------------------------------------------------ | -------------------------------------------------------------------------------------------------------------------------------- |
| Checkout not functional               | Payment gateway not configured                               | Complete Stage 1 setup; verify Stripe is enabled or a WooCommerce gateway is active                                              |
| Stripe unavailable                    | Publisher's country not supported by Stripe                  | Configure an alternative WooCommerce-compatible payment gateway                                                                  |
| Flexible amounts not available        | Free-tier Newspack site                                      | Upgrade to a paid plan to access WooCommerce Name Your Price                                                                     |
| Recurring subscriptions not available | Free-tier Newspack site                                      | Upgrade to a paid plan to access WooCommerce Subscriptions                                                                       |
| Salesforce sync failing               | Connected App not created before entering credentials        | Create the Connected App in Salesforce first; obtain Consumer Key and Consumer Secret before attempting to configure in Newspack |
| Donations block not rendering         | Landing page not published, or block not present on the page | Confirm the Donations block is placed on a published page and the page URL is correctly referenced in settings                   |

### Sponsored Content

| Failure Mode                                   | Cause                                                | Resolution                                                                                                                                  |
| ---------------------------------------------- | ---------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------- |
| Sponsor attribution not displaying             | Non-Newspack theme active                            | Switch to a Newspack theme; the plugin has a hard theme dependency                                                                          |
| Sponsor branding appearing on unintended posts | Category/tag-level sponsor without scope restriction | Enable "Show on posts only if direct sponsor?" toggle on the sponsor record                                                                 |
| Second sponsor's disclaimer missing            | Multi-sponsor post                                   | Expected behavior — only the first sponsor's disclaimer and flag render; adjust contractual expectations or restructure content assignments |
| Default copy appearing when override expected  | Per-sponsor override field left blank                | Fill in the Flag Override, Disclaimer Override, or Byline Prefix fields on the specific sponsor record                                      |

### General Platform Gotchas

- **WooCommerce Name Your Price and WooCommerce Subscriptions are premium-only.** Attempting to configure flexible amounts or recurring payments on a free-tier site will silently fail or produce an incomplete checkout experience.
- **News Revenue Hub setup is not a Newspack-native payment path.** Publishers who choose News Revenue Hub bypass the Stripe/WooCommerce stack entirely. Do not attempt to configure Stripe alongside News Revenue Hub.
- **Salesforce Connected App setup requires non-trivial configuration.** The in-product settings screen alone is insufficient — the Salesforce side requires creating a Connected App documented at `newspack.com/support/reader-revenue/salesforce/`.
- **The Newspack Ads plugin must be activated before any ad configuration is possible.** This step is easy to miss if a publisher navigates directly to Advertising settings without completing the activation prompt.
- **Multi-sponsor campaigns need pre-sale disclosure.** The limitation that only the first sponsor's disclaimer renders is architectural, not configurable.
