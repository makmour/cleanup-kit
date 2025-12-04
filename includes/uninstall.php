<?php
/**
 * Uninstall routine for Woo Cleanup.
 *
 * This file is executed when the user deletes the plugin from the WordPress admin.
 *
 * @package Woo_Cleanup
 * @version 1.0.6
 */

// if uninstall.php is not called by WordPress, die.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	die;
}

// 1. Delete the transient that stores the last log content.
delete_transient( 'woo_cleanup_last_log' );

// 2. Delete the user meta for screen options from all users.
// We are deleting the two custom meta keys we created.
delete_metadata( 'user', 0, 'woo_cleanup_columns', '', true );
delete_metadata( 'user', 0, 'woo_cleanup_per_page', '', true );

// 3. Recursively remove the log directory.
$upload_dir = wp_upload_dir();
$log_dir    = trailingslashit( $upload_dir['basedir'] ) . 'woo-clean-up-logs';

if ( is_dir( $log_dir ) ) {
	/**
	 * Recursively deletes a directory and all its contents.
	 *
	 * @param string $dir The directory to delete.
	 */
	function woo_cleanup_uninstall_delete_directory( $dir ) {
		$files = array_diff( scandir( $dir ), array( '.', '..' ) );
		foreach ( $files as $file ) {
			is_dir( "$dir/$file" ) ? woo_cleanup_uninstall_delete_directory( "$dir/$file" ) : unlink( "$dir/$file" );
		}
		return rmdir( $dir );
	}

	woo_cleanup_uninstall_delete_directory( $log_dir );
}
