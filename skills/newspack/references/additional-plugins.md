# Newspack Additional Plugins Reference

Plugins maintained by Automattic under the Newspack umbrella that address specialized publisher needs beyond the core suite. These are not installed by default — activate only after confirming compatibility with your TAM and running against the approved-plugins process.

---

## Migration Utilities

### Newspack Post Image Downloader

_Repo: [newspack-post-image-downloader](https://github.com/Automattic/newspack-post-image-downloader)_

Downloads images referenced in post content that are hosted on external domains and imports them into the WordPress media library. Also supports importing images directly from local files.

**When to use:** After a content migration where post body HTML still references images on the old host (e.g. a previous CMS, a CDN that will be retired, or a staging URL). Running this ensures images remain available if the original source goes offline and removes cross-origin image load penalties.

---

## Membership Extensions

These three plugins extend WooCommerce Memberships and Teams for WooCommerce Memberships. All require WooCommerce Memberships to be configured first (which itself requires Newspack support-team involvement — see the [engagement reference](engagement.md#content-gating)).

### Newspack WooCommerce Memberships Auto-Archive

_Repo: [newspack-woocomm-memberships-auto-archive](https://github.com/Automattic/newspack-woocomm-memberships-auto-archive)_

Keeps a post publicly accessible for a configurable number of days after publication, then automatically restricts it to members only. Implements a "soft paywall" or embargo window without manual intervention.

**When to use:** Publishers who want to give non-members a time-limited window to read new content (e.g. free for 7 days, then members-only) without maintaining a manual workflow.

### Newspack Teams for WooCommerce Memberships — Access by IP

_Repo: [newspack-teams-for-wc-memberships-access-by-ip](https://github.com/Automattic/newspack-teams-for-wc-memberships-access-by-ip)_

Grants public access to Team Membership-gated content based on visitor IP address or IP range. Designed for institutional subscribers such as libraries, universities, or corporate accounts.

**When to use:** Publishers selling institutional subscriptions where individual login per user is impractical. The institution's IP range is registered; any visitor from that range gets member access automatically.

### Newspack Teams for WooCommerce Memberships — Auto-Join by Email Domain

_Repo: [newspack-teams-for-wc-memberships-auto-join-by-email](https://github.com/Automattic/newspack-teams-for-wc-memberships-auto-join-by-email)_

When a new user registers, automatically assigns them to corresponding WooCommerce Team Memberships based on their email domain. For example, all `@university.edu` registrants are auto-enrolled in a university membership tier.

**When to use:** Publishers with institutional or organizational subscription tiers where access is determined by email domain rather than individual purchase.

---

## Editorial Specializations

### Newspack Election Kit

_Repo: [newspack-electionkit](https://github.com/Automattic/newspack-electionkit)_

A sample ballot tool generator and database manager for local elected officials. Allows publishers to build structured, searchable data about candidates, offices, and races for voter-guide editorial features.

**When to use:** Local and civic news publishers covering elections who want to produce structured ballot guides without building a custom tool. Pairs well with the Listings plugin for directory-style display.

### Newspack Elections

_Repo: [newspack-elections](https://github.com/Automattic/newspack-elections)_

A CRM-style database for elected officials and government entities. Stores and manages structured data about officials, their roles, terms, and affiliated entities — distinct from Election Kit's ballot-guide focus.

**When to use:** Civic/local news publishers who maintain an ongoing database of officeholders and want to link coverage to structured official records. Complements Election Kit but serves ongoing coverage rather than one-time election cycles.

### Newspack TablePress Sports

_Repo: [newspack-tablepress-sports](https://github.com/Automattic/newspack-tablepress-sports)_

Sports score reporting and management built on top of TablePress. Provides structured score tracking and display for local sports coverage.

**When to use:** Local and community publishers covering school, amateur, or regional sports who need structured score tables without building a custom solution. Requires the TablePress plugin.

---

## Theme Customization

### Newspack Rename Comments

_Repo: [newspack-rename-comments](https://github.com/Automattic/newspack-rename-comments)_

Allows the "Comments" section label to be renamed in Newspack themes. Intended for publishers who use comments for purposes other than traditional reader discussion (e.g. "Letters", "Responses", "Community Notes").

**When to use:** Publishers whose editorial voice calls for a different label than "Comments," or who want to signal a different community norm around reader contributions.

---

## Third-Party Integrations

### Newspack Disqus AMP

_Repo: [newspack-disqus-amp](https://github.com/Automattic/newspack-disqus-amp)_

Adds AMP (Accelerated Mobile Pages) compatibility to the Disqus comments plugin on Newspack sites.

**When to use:** Publishers using Disqus for comments who have AMP enabled and need the two to coexist without breaking the AMP validation. Note: AMP is a declining standard; verify whether AMP is still a requirement before installing.

---

## Performance

### Newspack 404 Cache

_Repo: [newspack-404-cache](https://github.com/Automattic/newspack-404-cache)_

Removes response headers that prevent 404 pages from being cached by the server or CDN. Without this, every 404 response is served fresh, which can create server load issues on sites with many broken inbound links or aggressive crawlers.

**When to use:** Sites experiencing elevated server load from 404 responses, particularly those with large legacy URL structures from a CMS migration or significant inbound link rot.

---

## Themes (Archived / Reference)

### Newspack Style 4

_Repo: [newspack-style-4](https://github.com/Automattic/newspack-style-4)_

An early attempt at converting one of the Newspack Style Pack designs into a standalone child theme. The repository notes it is an "initial pass" and it does not appear to be actively maintained.

**Status:** Likely inactive. Check the commit history before using as a base. If building a custom Newspack child theme, prefer starting from the documented child theme approach against the active [newspack-theme](https://github.com/Automattic/newspack-theme) or [newspack-block-theme](https://github.com/Automattic/newspack-block-theme).
