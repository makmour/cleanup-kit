=== Store Toolkit for WooCommerce ===
Contributors: wprepublic, thewebcitizen
Donate link: https://wprepublic.com
Tags: woocommerce, bulk delete products, delete categories, store maintenance, wp-cli
Requires at least: 5.8
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.1.0
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

The ultimate modular toolkit for WooCommerce admins. Starts with the "Missing Feature": Bulk Delete Products by Category (with WP-CLI support).

== Description ==

**Building a WooCommerce store is easy. Maintaining a massive one is hard.**

We built **Store Toolkit for WooCommerce** to be the Swiss Army knife that every store administrator wishes they had. This plugin is designed to host a variety of essential add-ons and utilities, evolving into a complete operations center for your shop.

### The First Module: The "Bulk Delete" Solution

Have you ever tried to delete a product category in WooCommerce, only to realize the *products* inside it stay behind?

You are left with thousands of orphaned items, and the default interface only lets you delete them 20 at a time. It is slow, it times out, and it frustrates even the most patient admins.
**For years, there wasn't a simple, native way to say: "Delete this category AND everything inside it."**

**Now there is.**

The **Store Toolkit Cleanup Module** allows you to select specific categories and surgically remove all products contained within them.

### Why this is the tool you've been looking for:

* **The "Missing" Feature:** Finally, you can bulk delete thousands of products based on their category with a few clicks.
* **Deep Cleaning:** It doesn't just delete the post. It scrubs the database of orphaned relationships, `postmeta`, and `wc_product_meta_lookup` entries, keeping your database lean and fast.
* **Safety First:** Includes a **Dry Run** mode (enabled by default). Simulate the entire process and read the logs before you delete a single byte of data.

### 🚀 Supercharged with WP-CLI

For developers and site reliability engineers, the graphical interface is just the beginning.
**Store Toolkit** comes with robust **WP-CLI integration** out of the box.

* **No Timeouts:** managing 50,000 products? Run the cleanup from the terminal. It is faster, more stable, and immune to browser timeouts (PHP max_execution_time).
* **Secure & Scriptable:** Automate your store maintenance via cron jobs or shell scripts securely.
* **Dry Runs in Terminal:** Test your cleanup logic directly in the command line before executing.

### What's Next?

This is just the beginning. **Store Toolkit for WooCommerce** is being actively developed to include more modules for bulk editing, customer management, and store optimization.

> **WARNING:** This is a powerful, destructive tool. Always perform a full database backup before running a live cleanup.

== Installation ==

**From your WordPress dashboard:**

1.  Navigate to 'Plugins > Add New'.
2.  Search for 'Store Toolkit for WooCommerce'.
3.  Click 'Install Now'.
4.  Activate the plugin.
5.  Navigate to 'WooCommerce > Store Toolkit' to get started.

== WP-CLI Commands ==

Use the power of the command line to manage your store cleanup efficiently.

### 1. List Categories
View a table of all product categories, their IDs, slugs, and product counts.

`wp store-toolkit list-categories`

### 2. Run Cleanup
Execute the cleanup process for specific categories.

**Options:**

* `--term-id=<ids>` : A comma-separated list of category IDs to process.
* `--category-slug=<slugs>` : A comma-separated list of category slugs.
* `--dry-run` : (Optional) Simulate the cleanup without deleting data.

**Examples:**

**Safe Simulation (Dry Run):**
`wp store-toolkit run --category-slug=temp-collection --dry-run`

**Live Cleanup (By ID):**
`wp store-toolkit run --term-id=152,189`

**Live Cleanup (By Slug):**
`wp store-toolkit run --category-slug=clothing,accessories`

== Frequently Asked Questions ==

= Is this tool safe? =
Yes. The plugin defaults to **Dry Run** mode. This means you can press "Run" and see exactly what *would* happen in a log file, without actually deleting anything. We strongly recommend doing this first.

= Does it delete the category folders too? =
No. The plugin deletes all the **products** inside the selected categories. The categories themselves (the terms) remain on your site, but they will be empty (0 products).

= Why use WP-CLI? =
If you are trying to delete 10,000+ products, a web browser might "time out" (stop working) halfway through. WP-CLI runs directly on the server, making it much faster and reliable for massive stores.

== Screenshots ==

1.  **Dashboard:** Select categories to clean and choose between Simulation (Dry Run) or Live Cleanup.
2.  **Audit Logs:** Detailed logs ensure you know exactly what happened during the process.

== Changelog ==

= 1.1.0 =
* REBRAND: Renamed plugin to **Store Toolkit for WooCommerce** to reflect its new modular architecture.
* FEATURE: Prepared codebase for future add-on modules.
* UPDATE: Updated WP-CLI commands to `wp store-toolkit`.

= 1.0.6 =
* FEATURE: Added Search, Sort, and Pagination to the admin table.

= 1.0.0 =
* Initial release.
