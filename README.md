# WHMCS Package Usage Manager

**WHMCS Package Usage Manager** is a lightweight WHMCS admin addon for answering a practical operational question: which customers are currently using a selected product or package? It reads native WHMCS records, presents a searchable dashboard, and provides a CSV export without creating custom tables or adding client-area overhead.

| Item | Value |
|---|---|
| Module name | WHMCS Package Usage Manager |
| Version | 1.0.0 |
| Author | Riduan Chowdhury |
| Company | Bytes Vibe |
| Website | [bytesvibe.com](https://bytesvibe.com) |
| Module type | WHMCS admin addon |
| Data model | Read-only; no custom tables |

## Installation

Install on a staging WHMCS installation first. Back up the WHMCS files and database before deployment. Upload the directory `modules/addons/whmcs_package_usage_manager/` into the matching `modules/addons/` directory of the WHMCS installation. The resulting main file path should be:

```text
/modules/addons/whmcs_package_usage_manager/whmcs_package_usage_manager.php
```

In the WHMCS administrator area, open **System Settings → Addon Modules**, locate **WHMCS Package Usage Manager**, and click **Activate**. Grant access to the appropriate administrator role group. Then open the addon from the **Addons** menu.

## Usage

Select a product group, product/package, service status, client status, billing cycle, or server. You may also enter a free-text search covering customer name, company, email, domain, username, and product name. Date filters can be applied to either the service signup date or the next due date. The dashboard shows matching services, distinct customers, and active services within the current result set.

Each result row includes the customer, product, domain or username, service status, client status, billing cycle, server, signup date, and next due date. The customer and service links open the corresponding native WHMCS administrator pages. Results are paginated at **20 services per page** with numbered navigation, previous/next controls, ellipses for long result sets, and a visible result range.

## Compatibility strategy

The addon uses the standard WHMCS addon callbacks and the WHMCS Capsule database layer. It does not rely on a theme, external CDN, frontend framework, custom database tables, background jobs, or provider credentials. The dashboard intentionally uses the default WHMCS admin classes and native form controls. It has no custom dashboard shell, custom stylesheet, JavaScript bundle, external asset, or frontend framework. The code intentionally avoids typed properties, Composer dependencies, migrations, and version-specific UI components so it can be deployed across common WHMCS 7.x and 8.x environments that expose the native `tblclients`, `tblhosting`, `tblproducts`, `tblproductgroups`, and `tblservers` tables.

Universal support cannot be guaranteed without testing against the customer’s exact WHMCS and PHP versions. A staging verification should be completed before production use, especially on legacy installations or installations with customized database schemas.

## Security and data ownership

The addon is read-only. It does not write customer, service, product, or server data and does not store credentials. Filter values are normalized, database queries use Capsule parameter binding, and output is HTML-escaped. Access is controlled by WHMCS administrator role permissions.

The module footer includes the requested credit: **Powered by bytesvibe.com**.

## References

[1]: [WHMCS Developer Documentation — Admin Area Content/Output](https://developers.whmcs.com/addon-modules/admin-area-output/)

## Upgrade and rollback

For an upgrade, back up the existing module directory, replace its contents with the new release, and verify the addon page. Because version 1.0.0 creates no custom tables, rollback consists of restoring the previous module directory. Deactivating or uninstalling the addon does not alter native WHMCS records.

## Verification checklist

On staging, activate the module and confirm that the page opens for an authorized administrator. Test a product with several active services, then combine product, service-status, client-status, billing-cycle, server, text-search, and date filters. Confirm that links open the correct client and service records, that CSV headers and rows match the on-screen filters, that an unauthorized administrator cannot access the addon, and that the layout remains usable at a narrow mobile width. Test an invalid or expired export token and confirm it is rejected.

## Known limitations

The module reports services represented by native WHMCS hosting records in `tblhosting`. It does not infer historical purchases that never became a service, orders that were not provisioned, or custom records stored outside the native WHMCS service model. Export is limited to 50,000 filtered service rows per request. The module has not been live-tested against a customer installation in this sandbox; the included validation is static and simulated.
