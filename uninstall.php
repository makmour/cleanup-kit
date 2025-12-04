<?php
/**
 * Uninstall routine for Cleanup Kit.
 *
 * This file is executed when the user deletes the plugin from the WordPress admin.
 *
 * @package Cleanup_Kit
 * @version 1.0.6
 */

// if uninstall.php is not called by WordPress, die.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	die;
}

// 1. Delete the transient that stores the last log content.
delete_transient( 'cleanup_kit_last_log' );

// 2. Delete the user meta for screen options from all users.
delete_metadata( 'user', 0, 'cleanup_kit_columns', '', true );
delete_metadata( 'user', 0, 'cleanup_kit_per_page', '', true );

// 3. Recursively remove the log directory.
$upload_dir = wp_upload_dir();
$log_dir    = trailingslashit( $upload_dir['basedir'] ) . 'cleanup-kit-logs';

if ( is_dir( $log_dir ) ) {
	/**
	 * Recursively deletes a directory and all its contents.
	 *
	 * @param string $dir The directory to delete.
	 */
	function cleanup_kit_uninstall_delete_directory( $dir ) {
		$files = array_diff( scandir( $dir ), array( '.', '..' ) );
		foreach ( $files as $file ) {
			is_dir( "$dir/$file" ) ? cleanup_kit_uninstall_delete_directory( "$dir/$file" ) : unlink( "$dir/$file" );
		}
		return rmdir( $dir );
	}

	cleanup_kit_uninstall_delete_directory( $log_dir );
}
