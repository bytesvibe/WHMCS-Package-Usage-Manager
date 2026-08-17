# Changelog

## 1.0.0 — Native WHMCS simplification

- Removed the custom dashboard shell and custom stylesheet.
- Restored default WHMCS admin classes, panels, forms, tables, labels, and pagination controls.
- Removed CSV export UI, export code, and the standalone export endpoint completely.
- Kept all filters, secure input normalization, native client/service links, and 20-row pagination.

## 1.0.0 — Reference redesign, export repair, and pagination

- Rebuilt the admin screen around the supplied clean StarAdmin-style reference with a light canvas, navigation rail, tab strip, summary metrics, white cards, and blue status panel.
- Added responsive layout behavior for desktop, tablet, and mobile screens, including a compact mobile service-card presentation.
- Added a standalone authenticated `export.php` endpoint to isolate CSV download headers from the addon dashboard renderer.
- Set pagination to 20 services per page and added numbered navigation with ellipses, previous/next controls, and visible result ranges.
- Preserved all product, group, service status, client status, billing cycle, server, search, and date filters across page navigation and CSV export.

## 1.0.0 — UX and export refinement

- Reworked the admin dashboard into a restrained WHMCS-native layout with clearer hierarchy, consistent controls, and less decorative styling.
- Added a narrow-screen card presentation for service rows and improved tablet/mobile filter stacking.
- Replaced the CSV link action with a POST form protected by a WHMCS form token, while retaining link-token compatibility for existing environments.
- Added clearer token-expiration messaging instead of masking it as a database failure.
- Added UTF-8 CSV output and spreadsheet formula neutralization.
- Added regression checks for responsive markup, export hardening, and CSV safety.

## 1.0.0 — Initial release

- Added the WHMCS Package Usage Manager admin addon.
- Added product group, product, service status, client status, billing cycle, server, date range, and text-search filters.
- Added matching service, distinct customer, and active service summary metrics.
- Added paginated customer/service results with native WHMCS administrator links.
- Added filtered CSV export with a 50,000-row safety cap.
- Added scoped responsive styling with no external frontend dependencies.
- Added author, company, and bytesvibe.com footer credit metadata.
- Added read-only activation and deactivation callbacks with no custom schema.
