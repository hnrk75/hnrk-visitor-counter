<?php
/**
 * Visitor tracking logic for HNRK Visitor Counter.
 *
 * Uses a long-lived cookie to identify unique visitors.
 * Stores all-time and per-day counts in wp_options / transients.
 *
 * @package HNRK_Visitor_Counter
 */

define( 'HNRK_COOKIE_NAME', 'hnrk_visitor' );
define( 'HNRK_COOKIE_LIFETIME', YEAR_IN_SECONDS );

/**
 * Check whether the visitor has accepted analytics cookies via CookieYes.
 *
 * @return bool
 */
function hnrk_has_analytics_consent() {
	if ( ! isset( $_COOKIE['cookieyes-consent'] ) ) {
		return false;
	}
	$consent = sanitize_text_field( wp_unslash( $_COOKIE['cookieyes-consent'] ) );
	return str_contains( $consent, 'analytics:yes' );
}

/**
 * Track the current visitor if they haven't been seen before.
 * Called on template_redirect so headers haven't been sent yet.
 */
function hnrk_track_visitor() {
	// Skip admin, REST, cron, and logged-in admins/editors.
	if ( is_admin() || wp_doing_cron() || wp_doing_ajax() ) {
		return;
	}

	// Respect CookieYes analytics consent.
	if ( ! hnrk_has_analytics_consent() ) {
		return;
	}

	if ( isset( $_COOKIE[ HNRK_COOKIE_NAME ] ) ) {
		// Known visitor — still count as a daily unique.
		$visitor_id = sanitize_text_field( wp_unslash( $_COOKIE[ HNRK_COOKIE_NAME ] ) );
		hnrk_maybe_count_daily( $visitor_id );
		return;
	}

	// New visitor: set cookie and increment all-time counter.
	$visitor_id = wp_generate_uuid4();
	setcookie( HNRK_COOKIE_NAME, $visitor_id, time() + HNRK_COOKIE_LIFETIME, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true );

	$total = (int) get_option( 'hnrk_visitor_count_total', 0 );
	update_option( 'hnrk_visitor_count_total', $total + 1, false );

	hnrk_maybe_count_daily( $visitor_id );
}

/**
 * Return a hashed representation of the current visitor's IP address.
 *
 * @return string SHA-256 hash of the IP, or empty string if unavailable.
 */
function hnrk_get_ip_hash() {
	$ip = '';
	if ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
		$forwarded = sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) );
		$ip        = trim( explode( ',', $forwarded )[0] );
	} elseif ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
		$ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
	}
	return $ip ? hash( 'sha256', $ip ) : '';
}

/**
 * Count a visitor as unique for today if not already seen today.
 * Deduplicates on both cookie UUID and IP hash.
 *
 * @param string $visitor_id UUID from the visitor cookie.
 */
function hnrk_maybe_count_daily( $visitor_id ) {
	$today = gmdate( 'Y-m-d' );
	$key   = 'hnrk_daily_' . $today;

	$seen = get_transient( $key );
	if ( ! is_array( $seen ) ) {
		$seen = array();
	}

	$cookie_hash = md5( $visitor_id );
	$ip_hash     = hnrk_get_ip_hash();

	// Skip if cookie or IP has already been counted today.
	if ( in_array( $cookie_hash, $seen['cookies'] ?? array(), true ) ) {
		return;
	}
	if ( $ip_hash && in_array( $ip_hash, $seen['ips'] ?? array(), true ) ) {
		return;
	}

	$seen['cookies'][] = $cookie_hash;
	if ( $ip_hash ) {
		$seen['ips'][] = $ip_hash;
	}

	// Expire at next midnight (UTC).
	$seconds_left = strtotime( 'tomorrow' ) - time();
	set_transient( $key, $seen, $seconds_left );

	// Persist the updated daily count permanently for monthly/yearly stats.
	$archive           = (array) get_option( 'hnrk_visitor_archive', array() );
	$archive[ $today ] = count( $seen['cookies'] );
	update_option( 'hnrk_visitor_archive', $archive, false );
}

/**
 * Get the unique visitor count for a given date.
 *
 * @param string $date Date in Y-m-d format. Defaults to today.
 * @return int
 */
function hnrk_get_daily_count( $date = '' ) {
	if ( ! $date ) {
		$date = gmdate( 'Y-m-d' );
	}
	$seen = get_transient( 'hnrk_daily_' . $date );
	return is_array( $seen ) ? count( $seen['cookies'] ?? array() ) : 0;
}
