<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Core functionality for Store Toolkit.
 */
class Store_Toolkit_Core {

	private $log_file_path;
	private $log_dir;

	public function __construct() {
		$upload_dir    = wp_upload_dir();
		// Updated log directory name
		$this->log_dir = trailingslashit( $upload_dir['basedir'] ) . 'store-toolkit-logs';

		// Create the directory if it doesn't exist.
		if ( ! file_exists( $this->log_dir ) ) {
			wp_mkdir_p( $this->log_dir );
		}

		// SECURITY: Prevent directory listing.
		if ( ! file_exists( trailingslashit( $this->log_dir ) . 'index.php' ) ) {
			file_put_contents( trailingslashit( $this->log_dir ) . 'index.php', '<?php // Silence is golden.' );
		}
	}

	/**
	 * Main function to run the cleanup process.
	 *
	 * @param array $term_ids Array of term IDs for product categories to clean.
	 * @param bool  $is_dry_run If true, no data will be deleted.
	 * @return string The path to the generated log file.
	 */
	public function run_cleanup( array $term_ids, bool $is_dry_run = true ) {
		$this->start_log( $is_dry_run );

		$this->log( 'Targeting Term IDs: ' . implode( ', ', $term_ids ) );

		$product_ids = get_posts(
			[
				'post_type'      => 'product',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'post_status'    => 'any',
				'tax_query'      => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
					[
						'taxonomy' => 'product_cat',
						'field'    => 'term_id',
						'terms'    => $term_ids,
					],
				],
			]
		);

		if ( empty( $product_ids ) ) {
			$this->log( 'No products found in the selected categories. Nothing to do.' );
			$this->log( '--- Cleanup Complete ---' );
			return $this->log_file_path;
		}

		$this->log( 'Found ' . count( $product_ids ) . ' products to process.' );

		foreach ( $product_ids as $product_id ) {
			$product = wc_get_product( $product_id );

			if ( ! $product ) {
				$this->log( "Skipping invalid product ID: {$product_id}" );
				continue;
			}

			// HARDENING: Sanitize product data before logging.
			$safe_name = sanitize_text_field( $product->get_name() );
			$safe_sku  = sanitize_text_field( $product->get_sku() );
			$this->log( "Processing Product ID: {$product_id} | SKU: {$safe_sku} | Name: {$safe_name}" );

			$child_ids = $product->get_children();

			if ( ! empty( $child_ids ) ) {
				$this->log( '-> Found ' . count( $child_ids ) . ' child products (variations). Processing them first.' );
				foreach ( $child_ids as $child_id ) {
					$this->delete_product_and_orphans( $child_id, $is_dry_run );
				}
			}

			$this->delete_product_and_orphans( $product_id, $is_dry_run );
		}

		if ( ! $is_dry_run ) {
			$this->log( 'Recounting terms for the affected categories.' );
			wp_update_term_count_now( $term_ids, 'product_cat' );
			$this->log( 'Term recount complete.' );
		}

		$this->log( '--- Cleanup Complete ---' );
		return $this->log_file_path;
	}

	private function delete_product_and_orphans( $product_id, $is_dry_run ) {
		global $wpdb;
		$log_prefix = $is_dry_run ? '[DRY RUN] Would ' : '';

		$this->log( "-> {$log_prefix}delete product post (ID: {$product_id})." );
		if ( ! $is_dry_run ) {
			wp_delete_post( $product_id, true );
		}

		$this->log( "-> {$log_prefix}delete from wc_product_meta_lookup for product ID {$product_id}." );
		if ( ! $is_dry_run ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->delete(
				$wpdb->prefix . 'wc_product_meta_lookup',
				[ 'product_id' => $product_id ],
				[ '%d' ]
			);
		}
	}

	private function start_log( $is_dry_run ) {
		$mode                  = $is_dry_run ? 'DRY_RUN' : 'LIVE';
		// Renamed log prefix
		$filename              = "store-toolkit-cleanup-{$mode}-" . gmdate( 'Y-m-d-His' ) . '.log';
		$this->log_file_path   = trailingslashit( $this->log_dir ) . $filename;
		file_put_contents( $this->log_file_path, '' );
	}

	public function log( $message ) {
		$timestamp = current_time( 'mysql' );
		file_put_contents(
			$this->log_file_path,
			"[{$timestamp}] " . $message . PHP_EOL,
			FILE_APPEND
		);
	}
}
