=== Cleanup Kit for WooCommerce ===
Contributors: wprepublic, thewebcitizen
Tags: woocommerce, cleanup, bulk delete, database, products, categories, wp-cli, developer, optimization, maintenance, orphaned data
Requires at least: 5.8
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 1.0.6
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Safely bulk delete WooCommerce products by category and purge orphaned data. Features WP-CLI and Dry Run protection.

== Description ==

**Is your WooCommerce database bloated with old products or failed imports?**

Managing a large store can be a nightmare when you need to delete thousands of products. Standard deletion often times out or leaves behind "orphaned data"—useless rows in your database that slow down your site.

**Cleanup Kit for WooCommerce** is a professional-grade utility designed to solve this with surgical precision. It allows you to bulk delete products by category while automatically identifying and removing the leftover data that WooCommerce leaves behind.

### Why use Cleanup Kit?

* **Deep Cleaning:** Unlike standard deletion, this tool removes associated relationships, `postmeta`, and `wc_product_meta_lookup` entries to keep your database lean.
* **Safety First:** Includes a **Dry Run** mode (enabled by default) so you can simulate the cleanup and view a detailed log before deleting a single file.
* **Developer Friendly:** Full **WP-CLI** integration allows you to script cleanups for massive stores via command line.

### Key Features

* **Bulk Delete by Category:** Select specific categories to wipe all contained products.
* **Orphaned Data Removal:** Automatically cleans up metadata and lookup table entries.
* **Detailed Logging:** Generates audit logs in `/wp-content/uploads/cleanup-kit-logs/` for every action.
* **WP-CLI Commands:** Use `wp cleanup-kit run` for server-side execution.
* **Visual Admin Interface:** Simple, easy-to-use dashboard inside WooCommerce settings.

> **WARNING:** This is a destructive tool. Always perform a full database backup before use.

== Installation ==

**From your WordPress dashboard:**

1.  Navigate to 'Plugins > Add New'.
2.  Search for 'Cleanup Kit for WooCommerce'.
3.  Click 'Install Now'.
4.  Activate the plugin through the 'Plugins' menu in WordPress.
5.  Navigate to 'WooCommerce > Cleanup Kit' to get started.

**Manual Installation:**

1.  Upload the `cleanup-kit` folder to the `/wp-content/plugins/` directory.
2.  Activate the plugin through the 'Plugins' menu in WordPress.
3.  Navigate to 'WooCommerce > Cleanup Kit' to get started.

== Frequently Asked Questions ==

= Is this tool safe to use? =

Yes, when used correctly. The plugin includes a **Dry Run** mode, which is enabled by default. This mode simulates the entire cleanup process and generates a detailed log of what *would* be deleted, without touching your database. We strongly recommend you perform a dry run and review the log before running a live cleanup. **Always back up your database first.**

= What data does it actually clean? =

It performs a deep clean. For each product, it deletes:
* The product post itself (and variations).
* All associated post metadata (`postmeta` table).
* All term relationships (e.g., tags, attributes).
* Entries in the `wc_product_meta_lookup` table.

= Can I use this for posts or other post types? =

No. This plugin is built specifically for WooCommerce products and `product_cat` taxonomies.

= Where are the log files stored? =

All logs are stored in your WordPress uploads directory, inside a folder named `cleanup-kit-logs`. The full path is: `/wp-content/uploads/cleanup-kit-logs/`.

== Changelog ==

= 1.0.6 =
* FEATURE: Added Search, Sort, and Pagination to the admin table.
* FIX: Resolved issue where unchecking screen options did not save correctly.

= 1.0.5 =
* FIX: Added `WC Blocks: true` declaration to the main plugin header to provide full compatibility with modern WooCommerce features.

= 1.0.0 =
* Initial release.
