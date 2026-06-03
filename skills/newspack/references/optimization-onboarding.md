# Newspack Optimization & Onboarding Reference

---

## 1. Prelaunch Checklist

_Docs: [help.newspack.com/onboarding/prelaunch-tasks/](https://help.newspack.com/onboarding/prelaunch-tasks/)_

Work through these tasks with your Technical Account Manager (TAM) before going live.

**Admin email**  
Navigate to `Settings > General` and update the admin email to a publisher-domain address. The Automattic placeholder `newspack@a8c.com` is set during migration and must be replaced.

**Google Site Kit**  
Connect via the Site Kit tab in the WordPress dashboard. Grant `newspack@a8c.com` Editor Access to Google Analytics (GA4) and Full User Access to Google Search Console. Custom GA4 targeting dimensions require a 48–72 hour wait after setup before data appears.

**reCAPTCHA v2 (optional — newsletter spam prevention)**  
Set up a reCAPTCHA account selecting v2. Then in Newspack Settings, select the matching version. The version selection must match in both places; a mismatch silently disables spam prevention with no visible error.

**Yoast SEO**  
Open Yoast SEO in the toolbar, go to General, and complete the First-time configuration wizard.

**Google Ad Manager** (if using)  
Navigate to `Newspack > Settings > Connections > API` and connect using the email listed as Admin in GAM.

**Jetpack social sharing**  
Go to `Jetpack > Settings > Social Sharing` and connect social accounts.

---

## 2. Domain Transfer Procedure

_Docs: [help.newspack.com/onboarding/transferring-your-domain/](https://help.newspack.com/onboarding/transferring-your-domain/)_

A domain transfer moves registration rights only — not posts, pages, or media files.

### Prerequisites

- Active WordPress.com account
- Domain TLD must be supported by WordPress.com (confirm with TAM if unsure)
- Domain must not be under a 60-day post-registration or post-transfer ICANN lock
- Domain must not be at its maximum renewal term (typically 10 years)

### At your current registrar

1. Unlock the domain (remove the registrar-lock security flag; may require contacting provider support)
2. Obtain the EPP authorization code (also called transfer code or auth code) — copy-paste it, never retype manually; it is case-sensitive

### Transfer steps in WordPress.com

1. Navigate to `My Sites > Manage > Domains > Add Domain`
2. Select "Use a domain I own"
3. Choose "Transfer to WordPress.com"
4. Enter the domain name
5. Confirm the domain is unlocked
6. Submit the authorization code
7. Proceed after validation
8. Review/update contact details; optionally enable private (WHOIS-privacy) registration
9. Apply the Newspack-provided coupon code at checkout (transfer is free for Newspack clients)
10. Await the confirmation email; allow 5–7 business days

### Critical warnings

- Do NOT cancel the domain at the old registrar before the transfer completes — cancellation immediately forfeits ownership and kills the transfer
- If the transfer is not completed within 30 days it is automatically cancelled
- Upon successful transfer the domain is automatically renewed for one additional year

**Troubleshooting invalid auth code errors:** recheck for case sensitivity; copy-paste from the registrar dashboard directly.

---

## 3. Jetpack Connection

_Docs: [help.newspack.com/onboarding/connecting-jetpack/](https://help.newspack.com/onboarding/connecting-jetpack/)_

Jetpack must be installed on the current (pre-migration) site, not the new Newspack site. This establishes real-time backup coverage used during migration.

### Steps

1. Log into the current WordPress admin
2. Go to `Plugins > Add New`, search for "Jetpack," install and activate Jetpack by Automattic
3. Open the Jetpack dashboard and click "Set Up Jetpack"
4. Connect or create a WordPress.com account
5. Select the **Complete** plan (not a lower tier)
6. Enter the Newspack-provided coupon code
7. Notify your TAM of the connected email address

### Jetpack Cloud dashboard features

- Real-time backups (VaultPress)
- Activity logs
- Security scanning
- Downtime monitoring
- Site stats
- CDN (enabled by default — reduces server load)
- Elasticsearch-powered enhanced search

---

## 4. Core Web Vitals Optimization for Newspack

_Docs: [help.newspack.com/optimization/core-web-vitals/](https://help.newspack.com/optimization/core-web-vitals/)_

### The four metrics

| Metric                         | What it measures                                           |
| ------------------------------ | ---------------------------------------------------------- |
| First Contentful Paint (FCP)   | Time until browser renders initial content                 |
| Largest Contentful Paint (LCP) | Time until largest image or text block appears in viewport |
| First Input Delay (FID)        | Lag between user interaction and browser response          |
| Cumulative Layout Shift (CLS)  | Amount page elements shift during load                     |

Core Web Vitals functions as a tiebreaker among roughly 100 Google ranking factors — it matters most when competing directly with similar-quality content.

**Newspack native advantages**  
The platform natively integrates async-loaded scripts, layout-shift placeholders, and lazy-loading techniques, giving publishers a baseline advantage.

### Testing

- Use Google PageSpeed Insights (`pagespeed.web.dev`) — provides separate mobile and desktop scores
- Test multiple article pages, not just the homepage; articles vary significantly due to differing embeds, image counts, and content length
- Use Google Search Console for site-wide CWV aggregate reporting
- Do NOT use Lighthouse without careful configuration — Newspack documentation explicitly notes it "often provides inaccurate readings"

### Image guidelines

- Width: 1200–2560px
- File size: under 2 MB
- Format: JPG preferred over PNG; PNG files resist CDN optimization
- WordPress and Jetpack CDN handle further delivery optimization after upload

### Content guidelines

- Limit post tags to approximately 6; consolidate existing tag sprawl
- Interactive embeds (Flourish, Infogram, Datawrapper, Tableau) each load their own scripts and can severely degrade performance; mitigations:
    - Break content across multiple pages
    - Substitute a static image for the interactive visualization
    - Link to the data source rather than embedding

---

## 5. Security Recommendations

_Docs: [help.newspack.com/optimization/security-recommendations/](https://help.newspack.com/optimization/security-recommendations/)_

### Passwords

- Minimum 16 characters, mixing uppercase, lowercase, numbers, and symbols
- The Newspack platform includes a built-in password strength meter
- Never store passwords in spreadsheets, Google Docs, wikis, or any unencrypted document
- Use a dedicated password manager — 1Password (free accounts for journalists; discounted for journalism organizations) or Bitwarden (open-source)
- Avoid: personal names, dictionary words, purely numeric or purely alphabetic strings

**Two-factor authentication (2FA)**  
Enforced via Jetpack SSO:

1. In the WordPress dashboard go to `Newspack > Connections`
2. Locate the Jetpack SSO section
3. Toggle "Force two-factor authentication"

2FA can be required independently per role: Administrator, Editor, Author, Contributor.

**Username enumeration prevention**  
In the same Jetpack SSO section, enable generic "not found" error messages so login attempts do not reveal whether a username exists on the site.

### Administrator account hygiene

- Keep Administrator-level accounts to the minimum necessary
- Most editorial staff need only Editor access or below
- Conduct regular audits; remove stale or unnecessary accounts

**Plugin hygiene**  
Deactivate AND delete unused plugins — inactive plugins still present an exploitable attack surface.

**Further reading:** "The Field Guide to Security Training in the Newsroom" by OpenNews (`securitytraining.opennews.org`).

---

## 6. Blocking AI Crawlers

_Docs: [help.newspack.com/optimization/blocking-ai-crawlers/](https://help.newspack.com/optimization/blocking-ai-crawlers/)_

**Mechanism:** robots.txt (advisory only — crawlers are not required to comply; rogue scrapers may ignore it)

### Crawlers to block

| Crawler         | Operator                                                              |
| --------------- | --------------------------------------------------------------------- |
| GPTbot          | OpenAI (ChatGPT training)                                             |
| ChatGPT-User    | OpenAI (ChatGPT live browsing agent)                                  |
| CCBot           | Common Crawl (widely used in LLM training datasets)                   |
| Google-Extended | Google Bard/Gemini training (separate from Googlebot search indexing) |
| PerplexityBot   | Perplexity AI                                                         |

### Procedure (Yoast SEO free, bundled with Newspack)

1. Navigate to `Yoast SEO > Tools > File editor`
2. If no robots.txt file exists, select "Create robots.txt file"
3. Add a blank line after any existing Disallow entries
4. Paste the following blocks, with a blank line separating each:

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

5. Save the file
6. Verify by visiting `https://yoursite.com/robots.txt` in a browser

**Procedure (Yoast Premium)**  
Navigate to `Yoast SEO > Settings > Advanced > Crawl optimization` and enable the crawler-blocking toggles — no manual text editing required.

**Formatting requirement:** blank lines between User-agent blocks are required for correct robots.txt parsing; missing blank lines cause entries to be misread.

**Important caveat:** Blocking Google-Extended only affects Bard/Gemini training. Googlebot (standard web search indexing) is a separate agent and is not affected by this block. The list of AI crawlers grows over time — revisit and update robots.txt periodically.

---

## 7. Homepage Optimization

_Docs: [help.newspack.com/optimization/optimizing-your-home-page/](https://help.newspack.com/optimization/optimizing-your-home-page/)_

**Why it still matters**  
Despite readers increasingly accessing content directly via article links, the homepage showcases publication identity, surfaces important stories, promotes partnerships, and drives newsletter signups and donation prompts.

### Images

- Follow Newspack image size guidelines across all articles — Content Loop blocks display multiple images simultaneously, making one poor image-sizing choice affect every loop instance on the page
- Optimize one-off graphics, logos, and PNG files for file size before uploading; PNG assets do not benefit from CDN compression as effectively as other formats
- WordPress and Jetpack CDNs provide dual-layer image optimization, but proper sizing and format remain the publisher's responsibility

### Third-party scripts

- Evaluate each tracking pixel, analytics snippet, or page-modification script for genuine business necessity
- Run a Core Web Vitals performance test before adding any new script (establish a baseline), then re-test after adding it to measure impact
- Remove unused scripts promptly — dormant snippets load on every page view

**Embedded media and widgets — high-risk items**  
The following are flagged as significant sources of page weight and CLS/FID risk:

- Autoplay video
- Live weather widgets
- Social media feed widgets
- RSS widgets
- Podcast players

Homepages already tend to have high bounce rates; heavy embeds compound this problem. Test major additions before deploying to production.

**Benchmarking**  
The Newspack team maintains a curated collection of example sites at Raindrop.io for publishers to benchmark effective homepage strategies.

---

## 8. Plugin Slimming / Performance Recommendations

Every activated plugin adds code that executes on page load. Plugin management is a direct performance concern, not just a housekeeping task.

### Audit procedure

1. Navigate to `Plugins > Installed Plugins`
2. Deactivate any Newspack-managed plugins not actively in use
3. Remove (delete, not merely deactivate) unused third-party plugins — deactivated plugins still carry attack surface risk
4. Contact Newspack support for any plugin you do not recognize

### Before installing a new plugin

1. Check the Newspack approved third-party plugins list (obtain link from TAM or Newspack dashboard)
2. If the plugin is not on the approved list, submit it via the review form at `https://forms.gle/ofFmGKqoaPWJm2bm8` before installing
3. Establish a performance baseline on target pages using Google PageSpeed Insights before activation
4. Activate and configure the plugin, then re-test to identify any regression

**Monetization balance**  
Higher CPM advertising is noted as a strategy to balance revenue generation with performance constraints — fewer, higher-value ad units rather than many lower-value ones.

---

## 9. Common Gotchas

### Domain transfer

- Canceling the domain at the old registrar during transfer immediately forfeits ownership — the transfer fails and the domain may be lost
- EPP/auth codes are case-sensitive; always copy-paste, never retype
- Domains under a 60-day ICANN lock cannot be transferred regardless of other preparations
- Transfers expire after 30 days if not completed

### Jetpack / prelaunch

- Jetpack must be installed and connected on the pre-migration site, not the new Newspack site
- Jetpack plan must be Complete, not a lower tier
- Admin email must be changed away from `newspack@a8c.com` post-migration — leaving it as the Automattic address will cause operational issues

### reCAPTCHA

- reCAPTCHA version (v2 vs v3) must match between the reCAPTCHA account and the Newspack Settings panel — a mismatch silently disables spam prevention with no visible error

### Google Analytics / Site Kit

- Custom GA4 dimensions require a 48–72 hour wait after setup before data appears — this is expected, not a misconfiguration

### Performance testing

- Do not use Lighthouse without careful configuration; it frequently provides inaccurate readings for Newspack sites
- Testing only the homepage gives a misleading performance picture; article pages have very different profiles

### Image format gotchas

- PNG files do not optimize well through the WordPress/Jetpack CDN — use JPG for featured images and hero images
- Large logo or hero graphic files uploaded as PNG bypass effective CDN optimization

### Embeds

- Each interactive embed (Flourish, Datawrapper, Infogram, Tableau) loads its own scripts; a single post with several embeds can take a severe performance hit
- Blocking Google-Extended in robots.txt only affects Bard/Gemini training, not standard Google search indexing (Googlebot is a separate agent)

### CSV data migration

- Post Body HTML is restricted to `<p>`, `<b>`, and `<i>` tags only; other tags will be stripped or cause import errors
- User Name field in users.csv is alphanumeric only (A–Z, 0–9); special characters cause errors
- All dates must follow `YYYY-MM-DD HH:MM:SS` format exactly

### Security

- Leaving unused plugins installed (even deactivated) still presents an exploitable attack surface; delete rather than merely deactivate
- Login pages that reveal whether a username exists enable enumeration attacks; enable the generic error message option in Jetpack SSO settings
