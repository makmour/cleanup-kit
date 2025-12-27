<?php

if ( ! defined( 'ABSPATH' ) || ! ( defined( 'WP_CLI' ) && WP_CLI ) ) {
	exit;
}

/**
 * Implements WP-CLI commands for Store Toolkit.
 */
class Store_Toolkit_CLI extends WP_CLI_Command {

	/**
	 * Lists all product categories with their product counts.
	 *
	 * ## EXAMPLES
	 *
	 * wp store-toolkit list-categories
	 *
	 */
	public function list_categories( $_, $assoc_args ) {
		$categories = get_terms(
			[
				'taxonomy'   => 'product_cat',
				'hide_empty' => false,
			]
		);

		if ( empty( $categories ) ) {
			WP_CLI::success( 'No product categories found.' );
			return;
		}

		$items = array_map(
			function ( $term ) {
				return [
					'term_id' => $term->term_id,
					'name'    => $term->name,
					'slug'    => $term->slug,
					'count'   => $term->count,
				];
			},
			$categories
		);

		WP_CLI\Utils\format_items( 'table', $items, [ 'term_id', 'name', 'slug', 'count' ] );
	}

	/**
	 * Deletes all products within one or more categories.
	 *
	 * ## OPTIONS
	 *
	 * [--term-id=<ids>]
	 * : A comma-separated list of category term IDs to clean.
	 *
	 * [--category-slug=<slugs>]
	 * : A comma-separated list of category slugs to clean.
	 *
	 * [--dry-run]
	 * : Perform a dry run without deleting any data.
	 *
	 * ## EXAMPLES
	 *
	 * # Perform a dry run on the 'old-imports' category
	 * wp store-toolkit run --category-slug=old-imports --dry-run
	 *
	 * # Perform a live cleanup on categories with IDs 123 and 456
	 * wp store-toolkit run --term-id=123,456
	 *
	 * @when after_wp_load
	 */
	public function run( $_, $assoc_args ) {
		$term_ids      = WP_CLI\Utils\get_flag_value( $assoc_args, 'term-id' );
		$category_slug = WP_CLI\Utils\get_flag_value( $assoc_args, 'category-slug' );
		$is_dry_run    = WP_CLI\Utils\get_flag_value( $assoc_args, 'dry-run', false );

		if ( ! $term_ids && ! $category_slug ) {
			WP_CLI::error( 'Please provide either --term-id or --category-slug.' );
		}

		$final_term_ids = [];

		if ( $term_ids ) {
			$final_term_ids = array_merge( $final_term_ids, array_map( 'intval', explode( ',', $term_ids ) ) );
		}

		if ( $category_slug ) {
			$slugs    = explode( ',', $category_slug );
			$term_ids_from_slugs = [];

			foreach ( $slugs as $slug ) {
				$term = get_term_by( 'slug', $slug, 'product_cat' );
				if ( $term ) {
					$term_ids_from_slugs[] = $term->term_id;
				} else {
					WP_CLI::warning( "Category with slug '{$slug}' not found. Skipping." );
				}
			}
			$final_term_ids = array_merge( $final_term_ids, $term_ids_from_slugs );
		}

		$final_term_ids = array_unique( $final_term_ids );

		if ( empty( $final_term_ids ) ) {
			WP_CLI::error( 'No valid categories found based on the provided input.' );
		}

		if ( ! $is_dry_run ) {
			WP_CLI::confirm( 'You are about to permanently delete products from the selected categories. This action cannot be undone. Are you sure you want to proceed?' );
		}

		$core     = new Store_Toolkit_Core();
		$log_path = $core->run_cleanup( $final_term_ids, $is_dry_run );

		WP_CLI::line( "\n" . file_get_contents( $log_path ) );
		WP_CLI::success( "Process complete. Full log available at: {$log_path}" );
	}
}
