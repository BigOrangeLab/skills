# GAQL — Google Ads Query Language

GAQL is a SQL-like language for querying the Google Ads API. All reporting goes through `GoogleAdsService.Search` or `SearchStream`.

## Syntax

```sql
SELECT resource.field [, resource.field, ...]
FROM resource_type
[WHERE condition [AND condition ...]]
[ORDER BY field [ASC|DESC]]
[LIMIT n]
```

## Key concepts

| Type       | Examples                                                       | Notes                      |
| ---------- | -------------------------------------------------------------- | -------------------------- |
| Resources  | `campaign`, `ad_group`, `keyword_view`                         | The `FROM` clause entity   |
| Attributes | `campaign.id`, `campaign.name`, `campaign.status`              | Metadata fields            |
| Metrics    | `metrics.impressions`, `metrics.clicks`, `metrics.cost_micros` | Aggregated numeric values  |
| Segments   | `segments.date`, `segments.device`, `segments.ad_network_type` | Split metrics by dimension |

When a **segment** is included in `SELECT`, metrics are automatically split per segment value per row.

## Date ranges

```sql
-- Relative ranges (no quotes)
WHERE segments.date DURING LAST_7_DAYS
WHERE segments.date DURING LAST_30_DAYS
WHERE segments.date DURING THIS_MONTH
WHERE segments.date DURING LAST_MONTH

-- Absolute ranges (ISO 8601, quoted)
WHERE segments.date BETWEEN '2026-01-01' AND '2026-01-31'

-- Single day
WHERE segments.date = '2026-06-01'
```

## Filtering

```sql
-- Enum comparison
WHERE campaign.status = 'ENABLED'
WHERE ad_group_criterion.type = 'KEYWORD'

-- Exclude
WHERE campaign.status != 'REMOVED'

-- Numeric comparison
WHERE metrics.impressions > 1000

-- IN list
WHERE campaign.id IN (111111, 222222, 333333)

-- String LIKE (% is wildcard)
WHERE campaign.name LIKE '%brand%'
```

## Common resource queries

### Campaigns

```sql
SELECT campaign.id, campaign.name, campaign.status,
       campaign.advertising_channel_type, campaign.bidding_strategy_type,
       metrics.impressions, metrics.clicks, metrics.cost_micros,
       metrics.conversions, metrics.conversions_value, metrics.ctr, metrics.average_cpc
FROM campaign
WHERE segments.date DURING LAST_30_DAYS
  AND campaign.status = 'ENABLED'
ORDER BY metrics.cost_micros DESC
LIMIT 50
```

### Ad groups

```sql
SELECT ad_group.id, ad_group.name, ad_group.status,
       campaign.name, metrics.impressions, metrics.clicks, metrics.cost_micros
FROM ad_group
WHERE segments.date DURING LAST_30_DAYS
  AND ad_group.status = 'ENABLED'
```

### Keywords

```sql
SELECT ad_group_criterion.keyword.text,
       ad_group_criterion.keyword.match_type,
       ad_group_criterion.status,
       ad_group_criterion.quality_info.quality_score,
       campaign.name, ad_group.name,
       metrics.impressions, metrics.clicks, metrics.cost_micros, metrics.conversions
FROM ad_group_criterion
WHERE ad_group_criterion.type = 'KEYWORD'
  AND segments.date DURING LAST_30_DAYS
ORDER BY metrics.cost_micros DESC
```

### Search terms

```sql
SELECT search_term_view.search_term, search_term_view.status,
       campaign.name, ad_group.name,
       metrics.impressions, metrics.clicks, metrics.conversions, metrics.cost_micros
FROM search_term_view
WHERE segments.date DURING LAST_30_DAYS
ORDER BY metrics.impressions DESC
```

### Ads

```sql
SELECT ad_group_ad.ad.id, ad_group_ad.ad.type,
       ad_group_ad.ad.responsive_search_ad.headlines,
       ad_group_ad.status, campaign.name, ad_group.name,
       metrics.impressions, metrics.clicks, metrics.conversions
FROM ad_group_ad
WHERE segments.date DURING LAST_30_DAYS
  AND ad_group_ad.status != 'REMOVED'
```

### Conversion actions

```sql
SELECT conversion_action.id, conversion_action.name, conversion_action.type,
       conversion_action.status, conversion_action.include_in_conversions_metric,
       metrics.all_conversions, metrics.conversions
FROM conversion_action
WHERE conversion_action.status = 'ENABLED'
```

### Performance by device

```sql
SELECT campaign.name, segments.device,
       metrics.impressions, metrics.clicks, metrics.cost_micros,
       metrics.conversions, metrics.ctr
FROM campaign
WHERE segments.date DURING LAST_30_DAYS
  AND campaign.status = 'ENABLED'
ORDER BY campaign.name, segments.device
```

## Metric reference

| Metric                            | Description                                 |
| --------------------------------- | ------------------------------------------- |
| `metrics.impressions`             | Ad impressions                              |
| `metrics.clicks`                  | Clicks                                      |
| `metrics.cost_micros`             | Cost in micros (÷ 1,000,000 = currency)     |
| `metrics.conversions`             | Attributed conversions                      |
| `metrics.conversions_value`       | Conversion value                            |
| `metrics.all_conversions`         | All conversions including view-through      |
| `metrics.ctr`                     | Click-through rate                          |
| `metrics.average_cpc`             | Avg cost per click in micros                |
| `metrics.average_cpm`             | Avg cost per thousand impressions in micros |
| `metrics.search_impression_share` | % of eligible impressions won               |
| `metrics.quality_score`           | (via `ad_group_criterion` resource)         |

## Parsing results in JavaScript

```javascript
const results = await search(customerId, query, accessToken, developerToken);

const rows = results.map((row) => ({
	campaignName: row.campaign?.name,
	impressions: row.metrics?.impressions ?? 0,
	clicks: row.metrics?.clicks ?? 0,
	costUSD: (row.metrics?.costMicros ?? 0) / 1_000_000,
	conversions: row.metrics?.conversions ?? 0,
}));
```

Note: The REST API returns camelCase field names (`costMicros` not `cost_micros`).
