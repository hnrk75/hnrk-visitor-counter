<?php
/**
 * HNRK Visitor Counter — main plugin file.
 *
 * @package HNRK_Visitor_Counter
 */

/**
 * Plugin Name: HNRK Visitor Counter
 * Plugin URI:  https://hnrkagency.se
 * Description: Simple unique visitor counter. Cookie-based tracking with daily and all-time stats.
 * Version:     2.0
 * Author: Henrik Pettersson at HNRK Labs
 * Author URI: https://hnrkagency.se
 * Text Domain: hnrk-visitor-counter
 * Domain Path: /languages
 * Requires PHP: 8.0
 * Requires at least: 6.0
 * License: GNU General Public License v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package HNRK_Visitor_Counter
 */

require plugin_dir_path( __FILE__ ) . 'includes/tracker.php';
require plugin_dir_path( __FILE__ ) . 'includes/admin.php';

add_action( 'plugins_loaded', 'hnrk_load_textdomain' );
add_action( 'template_redirect', 'hnrk_track_visitor' );
add_action( 'admin_menu', 'hnrk_create_admin_menu' );
add_action( 'admin_enqueue_scripts', 'hnrk_enqueue_custom_css' );
add_shortcode( 'hnrk_visitor_count', 'hnrk_visitor_count_shortcode' );

/**
 * Load plugin translations.
 */
function hnrk_load_textdomain() {
	load_plugin_textdomain( 'hnrk-visitor-counter', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}

/**
 * Enqueue admin CSS.
 *
 * @param string $hook Current admin page hook.
 */
function hnrk_enqueue_custom_css( $hook ) {
	if ( 'toplevel_page_hnrk-visitor-counter' === $hook ) {
		wp_enqueue_style( 'hnrk-admin-style', plugin_dir_url( __FILE__ ) . 'assets/css/style.css', array(), '2.0.0' );
	}
}

/**
 * Shortcode to display total unique visitor count.
 *
 * @return string
 */
function hnrk_visitor_count_shortcode() {
	return number_format_i18n( (int) get_option( 'hnrk_visitor_count_total', 0 ) );
}
