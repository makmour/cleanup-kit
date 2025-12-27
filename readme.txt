=== Store Toolkit for WooCommerce ===
Contributors: wprepublic, thewebcitizen
Donate link: https://wprepublic.com
Tags: woocommerce, woocommerce cleanup, categories bulk delete, store maintenance, woocommerce tools
Requires at least: 5.8
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.1.0
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Professional tools to manage, clean, and optimize your WooCommerce store. Includes a powerful Cleanup Module.

== Description ==

**Managing a growing WooCommerce store requires the right tools.**

**Store Toolkit for WooCommerce** is designed to be your Swiss Army knife for store operations.
The first available module is the **Cleanup Utility**, designed to solve the problem of bloated databases caused by old products or failed imports.

### Module 1: Cleanup Utility

When you need to delete thousands of products, standard deletion often times out or leaves behind "orphaned data".
The Cleanup Utility allows you to:

* **Bulk Delete by Category:** Select specific categories to wipe all contained products (the categories themselves are preserved).
* **Deep Clean:** Removes associated relationships, `postmeta`, and `wc_product_meta_lookup` entries.
* **Dry Run Mode:** Simulate the cleanup and view a detailed log before deleting a single file.
* **WP-CLI Support:** Script cleanups for massive stores via command line.

*(More modules coming soon)*

== Installation ==

**From your WordPress dashboard:**

1.  Navigate to 'Plugins > Add New'.
2.  Search for 'Store Toolkit for WooCommerce'.
3.  Click 'Install Now'.
4.  Activate the plugin.
5.  Navigate to 'WooCommerce > Store Toolkit' to get started.

== WP-CLI Commands ==

This plugin provides robust WP-CLI commands for server-side management.

### 1. List Categories
View a table of all product categories.

`wp store-toolkit list-categories`

### 2. Run Cleanup
Execute the cleanup process for specific categories.

**Options:**

* `--term-id=<ids>` : A comma-separated list of category IDs.
* `--category-slug=<slugs>` : A comma-separated list of category slugs.
* `--dry-run` : (Optional) Simulate the cleanup.

**Examples:**

**Dry Run:**
`wp store-toolkit run --category-slug=clothing --dry-run`

**Live Cleanup:**
`wp store-toolkit run --term-id=12,45`

== Frequently Asked Questions ==

= Is this tool safe to use? =

Yes. The **Dry Run** mode (enabled by default) allows you to simulate the process safely. Always back up your database before running a live cleanup.

= Does it delete the categories too? =

No. The plugin deletes all products *within* the selected categories, but the category terms themselves remain.

= Where are the log files stored? =

Logs are stored in: `/wp-content/uploads/store-toolkit-logs/`.

== Screenshots ==

1. **Admin Dashboard:** The main interface allowing you to select categories and choose between Dry Run or Live Cleanup.
2. **Cleanup Log:** An example of the detailed log file generated after a cleanup process.

== Changelog ==

= 1.1.0 =
* REBRAND: Renamed plugin to **Store Toolkit for WooCommerce**.
* FEATURE: Prepared architecture for future add-on modules.
* UPDATE: Updated WP-CLI commands to `wp store-toolkit`.

= 1.0.6 =
* FEATURE: Added Search, Sort, and Pagination to the admin table.

= 1.0.0 =
* Initial release.
