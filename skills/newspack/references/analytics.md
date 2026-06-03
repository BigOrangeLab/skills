# Newspack Analytics Reference

## 1. Initial GA4 Setup and Configuration

_Docs: [help.newspack.com/analytics/getting-started/](https://help.newspack.com/analytics/getting-started/)_

### Prerequisites

Newspack analytics features require Google Analytics 4 (GA4) connected exclusively through the **Google Site Kit** WordPress plugin. Other GA4 connection methods are not supported.

### Activating Newspack Custom Events

Once GA4 is connected via Site Kit, activate the custom event pipeline:

1. In GA4 Admin, navigate to **Data collection and modification > Data Streams > [your site stream] > Measurement Protocol API secrets** and create a new secret. Copy the secret value.
2. From the same Data Stream details page, copy your **Measurement ID** (format: `G-XXXXXXXX`).
3. In WordPress Admin, go to **Newspack > Settings > Connections > Activate Newspack Custom Events**.
4. Paste the Measurement Protocol API Secret and the Measurement ID (G- format) into the provided fields and save.

**Wait 24-72 hours** before proceeding to the next step. Events must fire on the live site and parameter values must populate in GA4's interface before custom dimensions can be created.

### Creating Custom Dimensions

Custom dimensions are the step that makes event parameter values visible in GA4 reports. Without this step, data is collected but not reportable.

1. In GA4 Admin, navigate to **Data Display > Custom Definitions > Create custom dimension**.
2. In the event parameter dropdown, select the parameter you want to expose.
3. Name the dimension to match the parameter name exactly.
4. Keep **Scope** set to **Event**.
5. Save and repeat for each recommended parameter.

**Important:** Parameters only appear in the dropdown after the corresponding event has actually fired on your live site at least once. You cannot pre-create dimensions for events that have never fired. Perform test actions (a test donation, registration, etc.) to populate parameters before attempting to create dimensions.

#### Recommended parameters to create dimensions for

`action`, `action_type`, `amount`, `author`, `category`, `donation_amount`, `donation_recurrence`, `is_reader`, `is_newsletter_subscriber`, `is_subscriber`, `is_donor`, `lists`, `logged_in`, `newsletter_subscription_method`, `popup_id`, `prompt_frequency`, `prompt_id`, `prompt_placement`, `prompt_title`, `product_id`, `range`, `recurrence`, `referrer`, `registration_method`

---

## 2. Auto-Collected Events and What They Track

_Docs: [help.newspack.com/analytics/auto-collected-events/](https://help.newspack.com/analytics/auto-collected-events/)_

### GA4 Enhanced Measurement Events

When Enhanced Measurement is enabled in GA4, the following events are collected automatically:

| Event                                               | Trigger                             | Key Parameters                                 |
| --------------------------------------------------- | ----------------------------------- | ---------------------------------------------- |
| `page_view`                                         | Every page load                     | Page location, referrer, category, author      |
| `scroll`                                            | Reaching 90% vertical depth         | Scroll depth (fixed at 90% — not configurable) |
| `click` (outbound)                                  | Clicks to external domains          | Link URL, link text                            |
| `view_search_results`                               | URL contains search query parameter | Search term                                    |
| `video_start` / `video_progress` / `video_complete` | YouTube embed interaction           | Video title, provider, timing, percent watched |
| `file_download`                                     | Clicks to downloadable file types   | File extension, file name, link URL            |

Ad-specific events and dimensions are additionally auto-collected when GA4 is connected to **Google Ad Manager**.

### Newspack Custom Events (np\_ prefix)

Six high-signal events are auto-collected once the GA API is activated and custom dimensions are configured:

#### `np_reader_registered`

Fires on successful new account creation via the Registration block, Newsletter Subscription Form, or account modal.

Key parameters: `registration_method`, `popup_id`, `gate_post_id`, `is_sso`, `referrer`, `ga_session_id`

#### `np_reader_logged_in`

Fires on successful login via the Sign In modal, magic link, or Google Sign-in.

Key parameters: `login_method`, `referrer`, `ga_session_id`

#### `np_newsletter_subscribed`

Fires on newsletter subscription completion.

Key parameters: `newsletter_subscription_method`, `lists`, `popup_id`, `referrer`

#### `np_prompt_interaction`

Tracks the full lifecycle of Campaign prompts.

Key parameters: `action` (loaded / seen / dismissed / clicked), `prompt_id`, `prompt_title`, `prompt_placement`, `prompt_frequency`, block-level flags

#### `np_gate_interaction`

Tracks content gate events (registration gates and paywalls).

Key parameters: `action` (seen / dismissed / form_submission), `gate_post_id`, `gate_has_registration_block`, `gate_has_checkout_button`, donation and checkout detail parameters

#### `np_modal_checkout_interaction`

Captures modal checkout transactions.

Key parameters: `action`, `action_type`, `amount`, `currency`, `product_id`, `product_type`, `recurrence`, `variation_id`

**Note:** As of May 2024, the previously separate events `np_donation_new` and `np_donation_subscription_cancelled` were removed. Donation and subscription conversions now flow through `np_modal_checkout_interaction` filtered on `action = form_submission`. Any existing GA4 Explorations or Looker Studio dashboards referencing the old events must be updated manually.

### Universal Reader-State Parameters

The following parameters are appended to all applicable Newspack custom events, enabling segmentation by reader identity and content context without additional instrumentation:

`logged_in`, `is_reader`, `is_newsletter_subscriber`, `is_subscriber`, `is_donor`, `categories`, `author`

**Note:** Some of these optional parameters (`is_reader`, `is_subscriber`, `categories`) are disabled by default due to potential GA4 data sampling impact. Enabling them requires a request via the `#newspack-help` Slack channel — they cannot be self-enabled in settings.

---

## 3. Custom Reporting and Dashboards

_Docs: [help.newspack.com/analytics/reporting/](https://help.newspack.com/analytics/reporting/)_

### Building GA4 Explorations

Use GA4 Explorations to answer specific business questions about audience and revenue.

#### Quick reference: event to use-case mapping

| Business Question                         | Event                           | Key Filter                                                      |
| ----------------------------------------- | ------------------------------- | --------------------------------------------------------------- |
| How many readers saw a registration gate? | `np_gate_interaction`           | `action=seen`, `gate_has_registration_block=true`               |
| How many readers saw a paywall?           | `np_gate_interaction`           | `action=seen`, `gate_has_checkout_button=true`                  |
| How many readers registered via a gate?   | `np_reader_registered`          | `gate_post_id` is set                                           |
| How many checkouts completed via a gate?  | `np_modal_checkout_interaction` | `action=form_submission_success`, `action_type=checkout_button` |
| What revenue came from a specific gate?   | `np_modal_checkout_interaction` | Sum `amount`, group by `gate_post_id`                           |
| Where are checkouts being abandoned?      | `np_modal_checkout_interaction` | `action=continue` without matching `form_submission`            |
| Which prompts are being dismissed?        | `np_prompt_interaction`         | `action=dismissed`, group by `prompt_id`                        |
| Newsletter signup completions by method   | `np_newsletter_subscribed`      | Group by `newsletter_subscription_method`                       |

### Google Tag Manager for Additional Tracking

Publishers needing granular, block-level event tracking beyond Newspack's built-in events can layer on Google Tag Manager. GTM must be implemented **exclusively through Site Kit** to avoid conflicts with Newspack's existing GA4 configuration. Any other GTM implementation method risks breaking the custom event pipeline.

Newspack intentionally excludes block-level interactions from its custom events — those are scoped as a poor fit for the built-in layer.

---

## 4. Third-Party Analytics Integrations

_Docs: [help.newspack.com/analytics/integrations/](https://help.newspack.com/analytics/integrations/)_

| Service                                                              | Role                                           | Notes                                                                                  |
| -------------------------------------------------------------------- | ---------------------------------------------- | -------------------------------------------------------------------------------------- |
| **Google Site Kit**                                                  | Required GA4 connection method                 | All other GA4 connection methods unsupported                                           |
| **Google Ad Manager**                                                | Auto-collected ad dimensions                   | Activates automatically when integrated with GA4                                       |
| **Google Tag Manager**                                               | Custom granular event tracking                 | Supported only via Site Kit implementation                                             |
| **Parse.ly**                                                         | Supplemental content analytics                 | Auto-configured when the plugin is present; bundled with Analytics Bundle pricing tier |
| **Stripe**                                                           | Webhook integration feeding GA via WooCommerce | Requires webhook configuration                                                         |
| **Mailchimp / ActiveCampaign / Campaign Monitor / Constant Contact** | Newsletter subscriber data                     | ActiveCampaign uses `NP_`-prefixed metadata naming to avoid conflicts                  |
| **Metorik**                                                          | WooCommerce / reader revenue analytics         | Third-party service; Newspack builds initial dashboards during onboarding              |
| **Google BigQuery**                                                  | Data warehouse for Newspack Data Dashboard     | Governed by Google BigQuery security protocols                                         |
| **YouTube**                                                          | Video embed interaction events                 | Auto-collected by GA4 Enhanced Measurement                                             |

### Metorik for Reader Revenue Analytics

Metorik is a third-party WooCommerce analytics platform that Newspack uses to supplement standard WooCommerce reporting for reader revenue tracking. Standard Woo Analytics does not provide sufficient nuance for publishers with donation or subscription revenue models.

During onboarding, Newspack builds customized Metorik dashboards tailored to each publisher's specific business model. Publishers are responsible for building additional custom reports beyond those provisioned by Newspack.

Key Metorik capabilities for Newspack publishers:

- Data segmentation for slicing revenue by reader or product dimension
- Google Sheets export for external analysis
- **Metorik Digests** — automated periodic reports delivered via email or Slack (daily, weekly, or monthly)

Metorik is a separate third-party service; deep customization requires familiarity with Metorik's own documentation, not just Newspack docs.

---

## 5. The Newspack Data Dashboard

_Docs: [help.newspack.com/analytics/newspack-data-dashboard/](https://help.newspack.com/analytics/newspack-data-dashboard/)_

### Overview

The Newspack Data Dashboard is a premium analytics product available exclusively to Newspack customers. It aggregates data from multiple third-party services into a single consolidated view, benchmarked against publishers of similar size and nonprofit/for-profit classification.

**Access URL:** `newspack.com/data-dashboard`  
Authentication is email-based, using the address associated with the Newspack account.

### What It Tracks

- User acquisition
- Audience engagement and conversion
- Newsletter engagement
- Advertising performance
- Reader revenue
- Site speed and performance

### Data Storage and Privacy

Data is stored in **Google BigQuery**. No personally identifiable information is retained. The dashboard has no performance impact on publisher websites — data is pulled directly from source APIs rather than injected into page loads. Governed by the Newspack Supplemental Terms of Service.

### Pricing Tiers (by annual publisher revenue)

| Annual Revenue | Analytics Bundle (incl. Parse.ly)                          | Dashboard Only |
| -------------- | ---------------------------------------------------------- | -------------- |
| Under $300K    | $50/month                                                  | Not available  |
| $300K – $600K  | $250/month                                                 | $150/month     |
| $600K – $1M    | $500/month                                                 | $300/month     |
| Over $1M       | $1,000/month (first site) + $250/month per additional site | $600/month     |

### Onboarding

1. Complete the signup form at `newspack.com/analytics-bundle`.
2. After signup, access the dashboard at `newspack.com/data-dashboard`.
3. Authenticate using the email associated with the Newspack account.
4. Training sessions are available to **Analytics Bundle subscribers only** and open after April 1.

---

## 6. Common Configuration Gotchas

### Setup Timing

- **Wait 24-72 hours** after connecting the Measurement Protocol API before creating custom dimensions. Creating dimensions too early results in an empty parameter dropdown — no parameters will be available to select.
- After initial configuration, allow **at least 24 hours** before expecting event data to appear in reports.
- Parameters only appear in the GA4 custom dimensions dropdown after the event has actually fired on the live site with that parameter.

### Custom Dimensions Are Mandatory for Reporting

If a custom event parameter is not mapped to a GA4 custom dimension, the data is collected silently but cannot be viewed in any report. Connecting the API and waiting is not sufficient — the dimension creation step is required for every parameter you want to query.

### Site Kit Requirement

Newspack analytics features only work when GA is connected via **Site Kit**. If GA was previously connected through another method (hard-coded snippet, other plugin), that connection must be replaced with Site Kit before Newspack custom events will function.

### GTM Must Route Through Site Kit

If Google Tag Manager is used for additional custom events, it must be implemented exclusively through Site Kit. Implementing GTM through any other method (direct script injection, separate plugin) risks conflicts with Newspack's existing GA4 configuration that can corrupt the entire event pipeline.

### Removed Events (May 2024 Refactor)

`np_donation_new` and `np_donation_subscription_cancelled` were removed in May 2024. Any existing GA4 Explorations, saved segments, or Looker Studio dashboards referencing these events will silently stop receiving data. Replace these references with `np_modal_checkout_interaction` filtered on `action = form_submission`.

### Optional Reader Parameters Are Disabled by Default

`is_reader`, `is_subscriber`, and `categories` parameters are disabled by default because enabling them can impact GA4 data sampling thresholds. To enable them, request activation through the `#newspack-help` Slack channel — they cannot be self-enabled in settings.

### Data Dashboard Access Restrictions

- The Dashboard is exclusively available to Newspack customers. Non-subscribers cannot purchase it independently.
- The standalone Dashboard-only pricing tier is not available at the under-$300K revenue band — only the bundle is offered at that level.
- Training sessions are restricted to **Analytics Bundle subscribers** and are not available to Dashboard-only tier subscribers.
- Training does not open until after April 1 for new subscribers.

### Metorik Is a Third-Party Service

Newspack builds initial Metorik dashboards during onboarding, but does not manage ongoing Metorik configuration. Publishers requiring custom reports, additional segmentation, or Digest setup must work directly within Metorik's platform and documentation.
