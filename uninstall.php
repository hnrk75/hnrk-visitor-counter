<?php
/**
 * Uninstall script for HNRK Visitor Counter.
 *
 * Removes all plugin data from the database when the plugin is deleted.
 *
 * @package HNRK_Visitor_Counter
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Remove all-time counter.
delete_option( 'hnrk_visitor_count_total' );

// Remove daily transients — direct DB call required for wildcard deletion on uninstall.
global $wpdb;
$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
	$wpdb->prepare(
		"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
		'_transient_hnrk_daily_%',
		'_transient_timeout_hnrk_daily_%'
	)
);
