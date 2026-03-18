<?php
/**
 * Admin page for HNRK Visitor Counter.
 *
 * @package HNRK_Visitor_Counter
 */

/**
 * Register the admin menu item.
 */
function hnrk_create_admin_menu() {
	add_menu_page(
		__( 'Visitor Counter', 'hnrk-visitor-counter' ),
		__( 'Visitors', 'hnrk-visitor-counter' ),
		'manage_options',
		'hnrk-visitor-counter',
		'hnrk_display_admin_page',
		'dashicons-chart-bar',
		2
	);
}

/**
 * Render the admin page.
 */
function hnrk_display_admin_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'hnrk-visitor-counter' ) );
	}

	$total = (int) get_option( 'hnrk_visitor_count_total', 0 );
	$today = hnrk_get_daily_count();
	$rows  = hnrk_get_recent_daily_counts( 7 );
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Visitor Counter', 'hnrk-visitor-counter' ); ?></h1>

		<div class="hnrk-stat-boxes">
			<div class="hnrk-stat-box">
				<span class="hnrk-stat-number"><?php echo esc_html( number_format_i18n( $total ) ); ?></span>
				<span class="hnrk-stat-label"><?php esc_html_e( 'Total unique visitors', 'hnrk-visitor-counter' ); ?></span>
			</div>
			<div class="hnrk-stat-box">
				<span class="hnrk-stat-number"><?php echo esc_html( number_format_i18n( $today ) ); ?></span>
				<span class="hnrk-stat-label"><?php esc_html_e( 'Unique visitors today', 'hnrk-visitor-counter' ); ?></span>
			</div>
		</div>

		<h2><?php esc_html_e( 'Last 7 days', 'hnrk-visitor-counter' ); ?></h2>
		<div class="hnrk-logs-container">
			<div class="hnrk-log-header">
				<div class="hnrk-log-cell"><?php esc_html_e( 'Date', 'hnrk-visitor-counter' ); ?></div>
				<div class="hnrk-log-cell"><?php esc_html_e( 'Unique visitors', 'hnrk-visitor-counter' ); ?></div>
			</div>
			<div class="hnrk-log-body">
				<?php foreach ( $rows as $date => $count ) : ?>
					<div class="hnrk-log-row">
						<div class="hnrk-log-cell"><?php echo esc_html( $date ); ?></div>
						<div class="hnrk-log-cell"><?php echo esc_html( number_format_i18n( $count ) ); ?></div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>

		<p class="description">
			<?php
			printf(
				/* translators: %s: shortcode */
				esc_html__( 'Show total visitor count anywhere with the shortcode %s', 'hnrk-visitor-counter' ),
				'<code>[hnrk_visitor_count]</code>'
			);
			?>
		</p>
	</div>
	<?php
}

/**
 * Return unique visitor counts for the last N days.
 *
 * @param int $days Number of days to fetch.
 * @return array  Associative array of date => count, newest first.
 */
function hnrk_get_recent_daily_counts( $days = 7 ) {
	$result = array();
	for ( $i = 0; $i < $days; $i++ ) {
		$date            = gmdate( 'Y-m-d', strtotime( "-{$i} days" ) );
		$result[ $date ] = hnrk_get_daily_count( $date );
	}
	return $result;
}
