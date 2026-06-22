# Google Ads REST API Reference

Current recommended version: **v22** (minimum: v21 — v20 sunsets June 2026).

Base URL: `https://googleads.googleapis.com/v22/`

## Prerequisites

1. **Google Cloud project** with the Google Ads API enabled.
2. **OAuth 2.0 credentials** (type: Web Application) — client ID + secret from Cloud Console.
3. **Developer token** — obtained from a Google Ads manager (MCC) account under Tools > API Center. Starts in test mode (works only against test accounts); apply for Basic access to use against real accounts.
4. **Customer ID** — the 10-digit Google Ads account number, no dashes.

## Authentication (OAuth 2.0 — web application)

```javascript
// Step 1: Build authorization URL
function buildAuthUrl(clientId, redirectUri) {
	const url = new URL("https://accounts.google.com/o/oauth2/v2/auth");
	url.searchParams.set("client_id", clientId);
	url.searchParams.set("redirect_uri", redirectUri);
	url.searchParams.set("response_type", "code");
	url.searchParams.set("scope", "https://www.googleapis.com/auth/adwords");
	url.searchParams.set("access_type", "offline");
	url.searchParams.set("prompt", "consent");
	return url.toString();
}

// Step 2: Exchange authorization code for tokens
async function exchangeCode(code, { clientId, clientSecret, redirectUri }) {
	const res = await fetch("https://oauth2.googleapis.com/token", {
		method: "POST",
		headers: { "Content-Type": "application/x-www-form-urlencoded" },
		body: new URLSearchParams({
			code,
			client_id: clientId,
			client_secret: clientSecret,
			redirect_uri: redirectUri,
			grant_type: "authorization_code",
		}),
	});
	return res.json(); // { access_token, refresh_token, expires_in, token_type }
}

// Step 3: Refresh access token (expires after 1 hour)
async function refreshAccessToken({ clientId, clientSecret, refreshToken }) {
	const res = await fetch("https://oauth2.googleapis.com/token", {
		method: "POST",
		headers: { "Content-Type": "application/x-www-form-urlencoded" },
		body: new URLSearchParams({
			client_id: clientId,
			client_secret: clientSecret,
			refresh_token: refreshToken,
			grant_type: "refresh_token",
		}),
	});
	const { access_token } = await res.json();
	return access_token;
}
```

## Making API calls

```javascript
function adsHeaders(accessToken, developerToken, loginCustomerId = null) {
	const headers = {
		Authorization: `Bearer ${accessToken}`,
		"developer-token": developerToken,
		"Content-Type": "application/json",
	};
	if (loginCustomerId) headers["login-customer-id"] = loginCustomerId;
	return headers;
}

// List accessible customer accounts
async function listAccessibleCustomers(accessToken, developerToken) {
	const res = await fetch(
		"https://googleads.googleapis.com/v22/customers:listAccessibleCustomers",
		{ headers: adsHeaders(accessToken, developerToken) },
	);
	return res.json(); // { resourceNames: ['customers/1234567890', ...] }
}
```

## GAQL search (reports)

```javascript
async function search(
	customerId,
	query,
	accessToken,
	developerToken,
	loginCustomerId = null,
) {
	const res = await fetch(
		`https://googleads.googleapis.com/v22/customers/${customerId}/googleAds:search`,
		{
			method: "POST",
			headers: adsHeaders(accessToken, developerToken, loginCustomerId),
			body: JSON.stringify({ query }),
		},
	);
	if (!res.ok) {
		const err = await res.json();
		throw new Error(JSON.stringify(err.error ?? err));
	}
	const data = await res.json();
	return data.results ?? [];
}
```

The `searchStream` endpoint (`googleAds:searchStream`) returns newline-delimited JSON chunks — prefer it for large result sets.

## Common GAQL queries

```sql
-- Campaign performance (last 30 days)
SELECT campaign.id, campaign.name, campaign.status,
       metrics.impressions, metrics.clicks, metrics.cost_micros,
       metrics.conversions, metrics.conversions_value
FROM campaign
WHERE segments.date DURING LAST_30_DAYS
  AND campaign.status != 'REMOVED'
ORDER BY metrics.cost_micros DESC

-- Ad group performance by device
SELECT ad_group.id, ad_group.name, segments.device,
       metrics.impressions, metrics.clicks, metrics.cost_micros
FROM ad_group
WHERE segments.date DURING LAST_7_DAYS

-- Search terms report
SELECT search_term_view.search_term, search_term_view.status,
       metrics.impressions, metrics.clicks, metrics.conversions
FROM search_term_view
WHERE segments.date DURING LAST_30_DAYS
ORDER BY metrics.impressions DESC

-- Keywords with quality score
SELECT ad_group_criterion.keyword.text, ad_group_criterion.keyword.match_type,
       ad_group_criterion.quality_info.quality_score,
       metrics.impressions, metrics.clicks
FROM ad_group_criterion
WHERE ad_group_criterion.type = 'KEYWORD'
  AND segments.date DURING LAST_30_DAYS

-- Conversion actions
SELECT conversion_action.id, conversion_action.name,
       conversion_action.status, conversion_action.type,
       metrics.all_conversions
FROM conversion_action
```

Key data notes:

- `cost_micros` is micros — divide by `1_000_000` for currency value.
- Dates use `YYYY-MM-DD` or constants: `TODAY`, `YESTERDAY`, `LAST_7_DAYS`, `LAST_30_DAYS`, `THIS_MONTH`, `LAST_MONTH`.
- Adding any segment field (e.g., `segments.device`) splits metric rows by that dimension.

## Mutations (create/update/remove)

Use the `googleAds:mutate` endpoint with an `operations` array:

```javascript
async function mutate(customerId, operations, accessToken, developerToken) {
	const res = await fetch(
		`https://googleads.googleapis.com/v22/customers/${customerId}/googleAds:mutate`,
		{
			method: "POST",
			headers: adsHeaders(accessToken, developerToken),
			body: JSON.stringify({ operations }),
		},
	);
	return res.json();
}

// Example: pause a campaign
const operations = [
	{
		update: {
			resourceName: `customers/${customerId}/campaigns/${campaignId}`,
			status: "PAUSED",
		},
		updateMask: "status",
	},
];

// Example: update campaign budget
const budgetOperations = [
	{
		update: {
			resourceName: `customers/${customerId}/campaignBudgets/${budgetId}`,
			amountMicros: 50_000_000, // $50.00/day
		},
		updateMask: "amountMicros",
	},
];
```

## Resource name format

All Google Ads resources use path-style resource names:

```text
customers/{customer_id}
customers/{customer_id}/campaigns/{campaign_id}
customers/{customer_id}/adGroups/{ad_group_id}
customers/{customer_id}/adGroupCriteria/{ad_group_id}~{criterion_id}
customers/{customer_id}/campaignBudgets/{budget_id}
customers/{customer_id}/conversionActions/{conversion_action_id}
```

## Useful links

- [Interactive GAQL Query Builder](https://developers.google.com/google-ads/api/docs/query/interactive-gaql-builder)
- [API reference browser](https://developers.google.com/google-ads/api/reference/rpc/v22/overview)
- [Release notes](https://developers.google.com/google-ads/api/docs/release-notes)
- [Google Ads MCP server](https://developers.google.com/google-ads/api/docs/developer-toolkit/mcp-server) — AI-assisted inspection
