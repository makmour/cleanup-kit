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

// 3. Recursively remove the log directory using WP_Filesystem.
$cleanup_kit_upload_dir = wp_upload_dir();
$cleanup_kit_log_dir    = trailingslashit( $cleanup_kit_upload_dir['basedir'] ) . 'cleanup-kit-logs';

global $wp_filesystem;
if ( empty( $wp_filesystem ) ) {
	require_once ABSPATH . '/wp-admin/includes/file.php';
	WP_Filesystem();
}

if ( $wp_filesystem->is_dir( $cleanup_kit_log_dir ) ) {
	$wp_filesystem->delete( $cleanup_kit_log_dir, true );
}
