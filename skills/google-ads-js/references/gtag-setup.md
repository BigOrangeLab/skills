# gtag.js Setup Reference

## Tag IDs

| Format         | Used for                   |
| -------------- | -------------------------- |
| `GT-XXXXXXXX`  | Google tag (multi-product) |
| `AW-XXXXXXXXX` | Google Ads only            |
| `G-XXXXXXXXXX` | Google Analytics 4         |

A single page can load one Google tag and configure multiple destinations via additional `gtag('config', '...')` calls.

## Global site tag (every page, in `<head>`)

```html
<!-- Google tag (gtag.js) -->
<script
	async
	src="https://www.googletagmanager.com/gtag/js?id=GT-XXXXXXXX"
></script>
<script>
	window.dataLayer = window.dataLayer || [];
	function gtag() {
		dataLayer.push(arguments);
	}
	gtag("js", new Date());

	// Configure Google Ads destination
	gtag("config", "AW-CONVERSION_ID");

	// Configure GA4 destination (optional, same tag load)
	gtag("config", "G-XXXXXXXXXX");
</script>
```

## Conversion event snippet

Place on the confirmation/thank-you page, or fire on form submit / button click:

```html
<script>
	gtag("event", "conversion", {
		send_to: "AW-CONVERSION_ID/CONVERSION_LABEL",
		value: 0.0,
		currency: "USD",
		transaction_id: "", // optional — used for deduplication
	});
</script>
```

`send_to` combines the Conversion ID and label from Google Ads > Goals > Conversions > Edit > Tag setup.

## Dynamic remarketing event parameters

These feed dynamic ad creative from your product/service feed.

```javascript
// E-commerce — product page view
gtag("event", "view_item", {
	send_to: "AW-CONVERSION_ID",
	value: 59.99,
	items: [
		{
			id: "SKU-123",
			google_business_vertical: "retail",
		},
	],
});

// E-commerce — cart
gtag("event", "add_to_cart", {
	send_to: "AW-CONVERSION_ID",
	value: 119.98,
	items: [
		{ id: "SKU-123", google_business_vertical: "retail" },
		{ id: "SKU-456", google_business_vertical: "retail" },
	],
});

// E-commerce — purchase (also fires as conversion)
gtag("event", "purchase", {
	send_to: "AW-CONVERSION_ID",
	transaction_id: "ORDER-789",
	value: 119.98,
	currency: "USD",
	items: [{ id: "SKU-123", google_business_vertical: "retail" }],
});
```

`google_business_vertical` values: `retail`, `travel`, `flights`, `hotels_and_rentals`, `real_estate`, `education`, `local`, `jobs`, `custom`.

## Enhanced conversions — manual hashing option

If you need to SHA-256 hash before sending (e.g., server-side relay), do it yourself:

```javascript
async function sha256(str) {
	const buf = await crypto.subtle.digest(
		"SHA-256",
		new TextEncoder().encode(str.trim().toLowerCase()),
	);
	return Array.from(new Uint8Array(buf))
		.map((b) => b.toString(16).padStart(2, "0"))
		.join("");
}

const hashedEmail = await sha256("user@example.com");

gtag("event", "conversion", {
	send_to: "AW-CONVERSION_ID/LABEL",
	value: 49.0,
	currency: "USD",
	user_data: { sha256_email_address: hashedEmail },
});
```

Prefer `gtag('set', 'user_data', { email: '...' })` with raw values — gtag auto-hashes them, which is simpler and less error-prone.

## Google Tag Manager alternative

If the site uses GTM, skip manual gtag.js. In GTM:

1. Create a **Google Ads Conversion Tracking** tag, enter Conversion ID and label, set trigger.
2. Add a **Conversion Linker** tag with All Pages trigger (required for cross-domain click attribution).
3. For remarketing, create a **Google Ads Remarketing** tag.

Do not mix gtag.js and GTM on the same page for the same destination — it causes duplicate events.

## WordPress integration patterns

- **Direct theme** — add snippet to `wp_head` via `functions.php` or a site-specific plugin.
- **Plugin** — use [Site Kit by Google](https://sitekit.withgoogle.com/) for zero-code setup with official Google verification.
- **Tag Manager** — install GTM container ID via Site Kit or a dedicated GTM plugin; manage all tags in GTM UI.
