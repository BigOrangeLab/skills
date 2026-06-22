---
name: google-ads-js
description: "Implement Google Ads JavaScript integrations: gtag.js conversion tracking, remarketing, enhanced conversions, Consent Mode v2, and programmatic campaign management via the Google Ads REST API. Use when adding any Google Ads measurement or management code to a website."
compatibility: "Any website with JavaScript. Google Ads REST API requires Node.js 18+ or a modern browser. Current minimum API version: v21 (default: v22)."
license: MIT
author: georgestephanis
version: "1.0"
written_against:
    google_ads_api: "v22"
    gtag_js: "2026"
---

# Google Ads JavaScript Integration

Google Ads offers two distinct integration surfaces for web developers:

1. **In-page measurement** — `gtag.js` (the Google tag): conversion tracking, remarketing audiences, enhanced conversions.
2. **Programmatic management** — Google Ads REST API: create/update campaigns, fetch reports, manage budgets.

## When to use

- Adding conversion tracking (purchase, form submit, call, page view)
- Building remarketing audiences from website visitors
- Implementing enhanced conversions (hashed first-party data)
- Complying with Consent Mode v2 for EU/UK traffic
- Querying campaign performance data via JavaScript/Node.js
- Creating or updating campaigns, ad groups, or keywords programmatically

Do **not** use the Google Ads REST API for in-page tagging — that's `gtag.js`. Do not use `gtag.js` for campaign management — that requires the API.

## Inputs required

**For in-page gtag.js:**

- Google tag ID (`G-XXXXXXXX` for GA4 or `GT-XXXXXXXX` for the Google tag) — found in Google Ads > Goals > Conversions or Google Tag Manager
- Conversion ID + label (`AW-XXXXXXXXX/LABEL`) — from each conversion action in Google Ads
- Consent framework decision (are users in EEA/UK?)

**For Google Ads REST API:**

- **Developer token** — obtained from Google Ads manager account (MCC) > API Center
- **OAuth 2.0 credentials** — client ID + secret from Google Cloud Console (project with Google Ads API enabled)
- **Customer ID** — the 10-digit Google Ads account ID (dashes stripped: `1234567890`)
- **Login customer ID** — if operating through a manager (MCC) account

## Procedure

### Part 1 — In-page tagging with gtag.js

See [references/gtag-setup.md](references/gtag-setup.md) for full snippets and options.

**1. Install the Google tag** (once, on every page — in `<head>`):

```html
<!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=TAG_ID"></script>
<script>
	window.dataLayer = window.dataLayer || [];
	function gtag() {
		dataLayer.push(arguments);
	}
	gtag("js", new Date());
	gtag("config", "TAG_ID");
</script>
```

Replace `TAG_ID` with your `GT-XXXXXXXX` or `AW-XXXXXXXXX` ID.

**2. Fire conversion events** on the relevant page or action:

```javascript
gtag("event", "conversion", {
	send_to: "AW-CONVERSION_ID/CONVERSION_LABEL",
	value: 29.99,
	currency: "USD",
	transaction_id: "ORDER-001", // deduplication
});
```

**3. Add remarketing** (audience building — fires automatically once `gtag('config')` is called with an `AW-` ID, or explicitly):

```javascript
// Standard remarketing fires via config; for dynamic remarketing add event params:
gtag("event", "view_item", {
	send_to: "AW-CONVERSION_ID",
	value: 89.0,
	items: [{ id: "SKU-123", google_business_vertical: "retail" }],
});
```

**4. Enhanced conversions** — pass hashed first-party data alongside conversions:

```javascript
gtag("set", "user_data", {
	email: "user@example.com", // gtag hashes this automatically
	phone_number: "+12125551212",
	address: {
		first_name: "Jane",
		last_name: "Doe",
		postal_code: "10001",
		country: "US",
	},
});
gtag("event", "conversion", {
	send_to: "AW-CONVERSION_ID/LABEL",
	value: 49.0,
	currency: "USD",
});
```

Enable enhanced conversions first in Google Ads: Goals > Conversions > Settings > Enhanced conversions > Turn on.

**5. Consent Mode v2** (required for EEA/UK):

```javascript
// Must run BEFORE gtag('config', ...) — ideally before the gtag.js <script> loads
gtag("consent", "default", {
	ad_storage: "denied",
	ad_user_data: "denied",
	ad_personalization: "denied",
	analytics_storage: "denied",
});

// After user grants consent via your CMP:
gtag("consent", "update", {
	ad_storage: "granted",
	ad_user_data: "granted",
	ad_personalization: "granted",
});
```

See [references/consent-mode.md](references/consent-mode.md).

---

### Part 2 — Google Ads REST API

See [references/rest-api.md](references/rest-api.md) for detailed auth flow and GAQL examples.

**Current API versions (June 2026):**

- Minimum supported: **v21** (v20 sunsets June 2026)
- Default / recommended: **v22**
- Base URL: `https://googleads.googleapis.com/v22/`

**Required headers on every request:**

```text
Authorization: Bearer ACCESS_TOKEN
developer-token: DEVELOPER_TOKEN
login-customer-id: MCC_CUSTOMER_ID   (only when operating through a manager account)
```

**Authenticate with OAuth 2.0 (web app flow):**

```javascript
// 1. Redirect user to consent screen
const authUrl = new URL("https://accounts.google.com/o/oauth2/v2/auth");
authUrl.searchParams.set("client_id", CLIENT_ID);
authUrl.searchParams.set("redirect_uri", REDIRECT_URI);
authUrl.searchParams.set("response_type", "code");
authUrl.searchParams.set("scope", "https://www.googleapis.com/auth/adwords");
authUrl.searchParams.set("access_type", "offline");

// 2. Exchange code for tokens
const tokenRes = await fetch("https://oauth2.googleapis.com/token", {
	method: "POST",
	headers: { "Content-Type": "application/x-www-form-urlencoded" },
	body: new URLSearchParams({
		code,
		client_id: CLIENT_ID,
		client_secret: CLIENT_SECRET,
		redirect_uri: REDIRECT_URI,
		grant_type: "authorization_code",
	}),
});
const { access_token, refresh_token } = await tokenRes.json();
```

**Run a GAQL report** (campaign performance last 30 days):

```javascript
const customerId = "1234567890"; // no dashes
const query = `
  SELECT campaign.id, campaign.name, campaign.status,
         metrics.impressions, metrics.clicks, metrics.cost_micros, metrics.conversions
  FROM campaign
  WHERE segments.date DURING LAST_30_DAYS
  ORDER BY metrics.impressions DESC
`;

const res = await fetch(
	`https://googleads.googleapis.com/v22/customers/${customerId}/googleAds:search`,
	{
		method: "POST",
		headers: {
			Authorization: `Bearer ${accessToken}`,
			"developer-token": developerToken,
			"Content-Type": "application/json",
		},
		body: JSON.stringify({ query }),
	},
);
const { results } = await res.json();
// results[].campaign.name, results[].metrics.clicks, etc.
```

See [references/gaql.md](references/gaql.md) for more query patterns and the GAQL resource/segment/metric model.

## Verification

**gtag.js:**

- Install [Google Tag Assistant](https://tagassistant.google.com/) browser extension and confirm the tag ID fires on every page.
- In Google Ads: Goals > Conversions — status should move from "Unverified" to "Recording" within 24–48 hours after a real test conversion.
- Use browser DevTools Network tab, filter by `googletagmanager.com`, and confirm conversion pings after test actions.

**REST API:**

- Start with a test manager account (developer token works immediately against test accounts with no approval needed).
- Call `GET https://googleads.googleapis.com/v22/customers:listAccessibleCustomers` to confirm auth is working before making mutations.
- All costs returned as `cost_micros` — divide by `1_000_000` for the currency amount.

## Failure modes

- **Tag fires but conversions stay "Unverified"** — event snippet is not on the right page, or conversion is blocked by Consent Mode `denied` state. Check Tag Assistant.
- **Enhanced conversions not matching** — ensure `gtag('set', 'user_data', ...)` fires _before_ the conversion event on the same page load.
- **API returns 401** — access token expired (valid for 1 hour); exchange refresh token for a new one.
- **API returns 403 `DEVELOPER_TOKEN_NOT_APPROVED`** — developer token is in test mode; it only works against test accounts until approved. Apply for Basic access in Google Ads > API Center.
- **API returns 400 on v19/v20** — those versions are sunset. Update the URL path to `v22`.
- **`login-customer-id` errors** — include this header only when the authenticating user is an MCC manager; omit it for direct account access.
- **Consent Mode blocking conversions** — expected in EEA/UK without consent; Google models conversions from consented users. Confirm Consent Mode V2 (not V1) is implemented.

## Escalation

- GAQL syntax: use the [Interactive Query Builder](https://developers.google.com/google-ads/api/docs/query/interactive-gaql-builder)
- API changelog / field removals per version: [Release notes](https://developers.google.com/google-ads/api/docs/release-notes)
- Tag debugger: [tagassistant.google.com](https://tagassistant.google.com)
- Developer token approval process: Google Ads > Tools > API Center
- Google Ads MCP server for AI-assisted campaign inspection: [MCP server guide](https://developers.google.com/google-ads/api/docs/developer-toolkit/mcp-server)
