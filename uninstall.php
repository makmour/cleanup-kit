<?php
/**
 * Uninstall routine for Store Toolkit for WooCommerce.
 *
 * @package Store_Toolkit_WooCommerce
 * @version 1.1.0
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	die;
}

// 1. Delete the transient that stores the last log content.
delete_transient( 'store_toolkit_last_log' );

// 2. Delete the user meta for screen options from all users.
delete_metadata( 'user', 0, 'store_toolkit_columns', '', true );
delete_metadata( 'user', 0, 'store_toolkit_per_page', '', true );

// 3. Recursively remove the log directory.
$store_toolkit_upload_dir = wp_upload_dir();
$store_toolkit_log_dir    = trailingslashit( $store_toolkit_upload_dir['basedir'] ) . 'store-toolkit-logs';

global $wp_filesystem;
if ( empty( $wp_filesystem ) ) {
	require_once ABSPATH . '/wp-admin/includes/file.php';
	WP_Filesystem();
}

if ( $wp_filesystem->is_dir( $store_toolkit_log_dir ) ) {
	$wp_filesystem->delete( $store_toolkit_log_dir, true );
}
