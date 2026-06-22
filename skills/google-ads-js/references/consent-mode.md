# Consent Mode v2 Reference

Consent Mode v2 is **required** for Google Ads and Analytics if serving users in the EEA or UK as of March 2024. Without it, remarketing audiences stop populating and conversion modelling is disabled.

## Consent signals

| Signal               | Controls                                    |
| -------------------- | ------------------------------------------- |
| `ad_storage`         | Cookies used for advertising (e.g., gclid)  |
| `ad_user_data`       | Sending user data to Google for advertising |
| `ad_personalization` | Personalized advertising / remarketing      |
| `analytics_storage`  | Cookies used for analytics                  |

## Implementation pattern

The `gtag('consent', 'default', ...)` call **must run before any `gtag('config', ...)`** and before the `gtag.js` script loads. Achieve this by inlining it in the `<head>` before the async script tag.

```html
<head>
	<!-- Consent defaults — inline, before gtag.js loads -->
	<script>
		window.dataLayer = window.dataLayer || [];
		function gtag() {
			dataLayer.push(arguments);
		}

		// Default: deny everything for EEA/UK users
		// (use region targeting to restrict to EEA/UK only if needed)
		gtag("consent", "default", {
			ad_storage: "denied",
			ad_user_data: "denied",
			ad_personalization: "denied",
			analytics_storage: "denied",
			wait_for_update: 500, // ms to wait for CMP before firing tags
		});
	</script>

	<!-- Google tag loads after defaults are set -->
	<script
		async
		src="https://www.googletagmanager.com/gtag/js?id=GT-XXXXXXXX"
	></script>
	<script>
		gtag("js", new Date());
		gtag("config", "GT-XXXXXXXX");
	</script>
</head>
```

## Updating consent after user interaction

Call this from your Consent Management Platform (CMP) callback after the user accepts:

```javascript
// Full consent
gtag("consent", "update", {
	ad_storage: "granted",
	ad_user_data: "granted",
	ad_personalization: "granted",
	analytics_storage: "granted",
});

// Analytics only (user declined ads)
gtag("consent", "update", {
	ad_storage: "denied",
	ad_user_data: "denied",
	ad_personalization: "denied",
	analytics_storage: "granted",
});
```

Consent state is not persisted across page loads by gtag — your CMP must set the default on every page based on stored consent.

## Region-scoped defaults (EEA/UK only)

If you want to deny by default only for EEA/UK and grant for all other regions:

```javascript
gtag("consent", "default", {
	ad_storage: "granted", // default for non-EEA
	analytics_storage: "granted",
});

gtag("consent", "default", {
	region: [
		"AT",
		"BE",
		"BG",
		"HR",
		"CY",
		"CZ",
		"DK",
		"EE",
		"FI",
		"FR",
		"DE",
		"GR",
		"HU",
		"IE",
		"IT",
		"LV",
		"LT",
		"LU",
		"MT",
		"NL",
		"PL",
		"PT",
		"RO",
		"SK",
		"SI",
		"ES",
		"SE",
		"GB",
		"IS",
		"LI",
		"NO",
	],
	ad_storage: "denied",
	ad_user_data: "denied",
	ad_personalization: "denied",
	analytics_storage: "denied",
	wait_for_update: 500,
});
```

## Certified CMPs

Google requires using a [certified CMP](https://cmppartners.withgoogle.com/) for Consent Mode v2 in EEA. Popular options:

- Cookiebot / Usercentrics
- OneTrust
- Didomi
- Complianz (WordPress plugin)

The CMP handles the consent UI and calls `gtag('consent', 'update', ...)` — you do not build that UI yourself.

## Verification

In Google Ads: Tools > Data Manager > Consent signals — should show "Active" after 48 hours of traffic.

In Tag Assistant: open Consent tab — confirm signals update from `denied` to `granted` after clicking accept in your CMP.
