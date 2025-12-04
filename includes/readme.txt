=== Woo Cleanup ===
Contributors: Ger
Tags: woocommerce, cleanup, bulk delete, database, products, categories, wp-cli, developer
Requires at least: 5.8
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 1.0.6
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A safe, powerful bulk-cleanup utility for large WooCommerce stores with WP-CLI support.

== Description ==

Managing a large WooCommerce store, especially one with frequent or complex imports, can be a challenge. Over time, category-based imports or catalog changes can leave behind thousands of products and, more insidiously, a trail of orphaned data.

This digital clutter bloats your database, slows down queries, and makes maintenance a nightmare.

**Woo Cleanup** is a developer-focused tool built to solve this problem with surgical precision. It provides a simple but powerful interface to delete all products belonging to one or more categories.

More importantly, it goes beyond WooCommerce's standard deletion process to automatically find and purge all the orphaned data left behind, ensuring your database remains lean and performant.

**Key Features:**

* **WP-CLI Integration**: All functionality is exposed through powerful and scriptable WP-CLI commands.
* **Dry-Run Mode**: See exactly what will be deleted—from products to post meta—without making any changes to your database.
* **Automatic Orphan Cleanup**: Intelligently purges post meta, term relationships, and WooCommerce lookup table data associated with the deleted products.
* **Full Logging**: Every action is logged to a file in `wp-content/uploads/` for easy review and auditing.
* **Simple Admin Interface**: A clean UI in the WordPress admin for when you need a quick visual approach.

> **WARNING:** This is a destructive tool. Always perform a full database backup before use and run a "Dry Run" first.

== Installation ==

**From your WordPress dashboard:**

1.  Navigate to 'Plugins > Add New'.
2.  Search for 'Woo Cleanup'.
3.  Click 'Install Now'.
4.  Activate the plugin through the 'Plugins' menu in WordPress.
5.  Navigate to 'WooCommerce > Woo Cleanup' to get started.

**Manual Installation:**

1.  Upload the `woo-clean-up` folder to the `/wp-content/plugins/` directory.
2.  Activate the plugin through the 'Plugins' menu in WordPress.
3.  Navigate to 'WooCommerce > Woo Cleanup' to get started.

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

All logs are stored in your WordPress uploads directory, inside a folder named `woo-clean-up-logs`. The full path is: `/wp-content/uploads/woo-clean-up-logs/`.

== Changelog ==

= 1.0.6 =
* FIX: Added programmatic feature compatibility declarations for `custom_order_tables` and `cart_checkout_blocks` to resolve incompatibility notices.
* FEATURE: Added Search, Sort, and Pagination to the admin table.
* FIX: Resolved issue where unchecking screen options did not save correctly.

= 1.0.5 =
* FIX: Added `WC Blocks: true` declaration to the main plugin header to provide full compatibility with modern WooCommerce features.

= 1.0.0 =
* Initial release.
