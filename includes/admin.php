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

	$total      = (int) get_option( 'hnrk_visitor_count_total', 0 );
	$today      = hnrk_get_daily_count();
	$today_ym   = gmdate( 'Y-m' );
	$today_year = (int) gmdate( 'Y' );

	// Selected month for daily chart.
	$sel_month = isset( $_GET['vc_month'] ) ? sanitize_text_field( wp_unslash( $_GET['vc_month'] ) ) : $today_ym; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( ! preg_match( '/^\d{4}-\d{2}$/', $sel_month ) || $sel_month < '2026-01' || $sel_month > $today_ym ) {
		$sel_month = $today_ym;
	}

	// Selected year for monthly chart.
	$sel_year = isset( $_GET['vc_year'] ) ? (int) $_GET['vc_year'] : $today_year; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( $sel_year < 2026 || $sel_year > $today_year ) {
		$sel_year = $today_year;
	}

	$daily_data   = hnrk_get_month_daily_data( $sel_month );
	$monthly_data = hnrk_get_year_monthly_data( $sel_year );
	$base_url     = admin_url( 'admin.php?page=hnrk-visitor-counter' );

	$prev_month    = gmdate( 'Y-m', strtotime( $sel_month . '-01 -1 month' ) );
	$next_month    = gmdate( 'Y-m', strtotime( $sel_month . '-01 +1 month' ) );
	$month_display = date_i18n( 'F Y', strtotime( $sel_month . '-01' ) );
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

		<div class="hnrk-cards">

			<div class="hnrk-card">
				<div class="hnrk-card-head">
					<h2><?php echo esc_html( $month_display ); ?></h2>
					<div class="hnrk-chart-nav">
						<?php if ( $sel_month > '2026-01' ) : ?>
					<a class="hnrk-nav-btn" href="<?php echo esc_url( add_query_arg( 'vc_month', $prev_month, $base_url ) ); ?>">&lsaquo;</a>
				<?php else : ?>
					<span class="hnrk-nav-btn hnrk-nav-disabled">&lsaquo;</span>
				<?php endif; ?>
						<?php if ( $sel_month < $today_ym ) : ?>
							<a class="hnrk-nav-btn" href="<?php echo esc_url( add_query_arg( 'vc_month', $next_month, $base_url ) ); ?>">&rsaquo;</a>
						<?php else : ?>
							<span class="hnrk-nav-btn hnrk-nav-disabled">&rsaquo;</span>
						<?php endif; ?>
					</div>
				</div>
				<?php hnrk_render_hbar_chart( array_reverse( $daily_data ), 'day' ); ?>
			</div>

			<div class="hnrk-card">
				<div class="hnrk-card-head">
					<h2><?php echo esc_html( $sel_year ); ?></h2>
					<div class="hnrk-chart-nav">
						<?php if ( $sel_year > 2026 ) : ?>
							<a class="hnrk-nav-btn" href="<?php echo esc_url( add_query_arg( 'vc_year', $sel_year - 1, $base_url ) ); ?>">&lsaquo;</a>
						<?php else : ?>
							<span class="hnrk-nav-btn hnrk-nav-disabled">&lsaquo;</span>
						<?php endif; ?>
						<?php if ( $sel_year < $today_year ) : ?>
							<a class="hnrk-nav-btn" href="<?php echo esc_url( add_query_arg( 'vc_year', $sel_year + 1, $base_url ) ); ?>">&rsaquo;</a>
						<?php else : ?>
							<span class="hnrk-nav-btn hnrk-nav-disabled">&rsaquo;</span>
						<?php endif; ?>
					</div>
				</div>
				<?php hnrk_render_hbar_chart( array_reverse( $monthly_data ), 'month' ); ?>
			</div>

		</div>
	</div>
	<?php
}

/**
 * Render a vertical bar chart (columns).
 *
 * @param array  $data   Associative array of key => count (null = future).
 * @param string $format 'day' or 'month' — controls label display.
 */
function hnrk_render_bar_chart( $data, $format ) {
	$today      = gmdate( 'Y-m-d' );
	$today_ym   = gmdate( 'Y-m' );
	$bar_height = 120;

	$values = array_filter( $data, fn( $v ) => null !== $v );
	$max    = $values ? max( $values ) : 0;
	?>
	<div class="hnrk-chart">
		<?php
		foreach ( $data as $key => $count ) :
			$is_future = null === $count;
			$is_today  = ( 'day' === $format && $key === $today ) || ( 'month' === $format && $key === $today_ym );
			$display   = (int) $count;
			$height    = ( ! $is_future && $max > 0 ) ? max( 2, (int) round( ( $display / $max ) * $bar_height ) ) : 0;

			if ( 'day' === $format ) {
				$label = (int) substr( $key, 8, 2 );
			} else {
				$label = date_i18n( 'M', strtotime( $key . '-01' ) );
			}

			$col_class = 'hnrk-bar-col';
			if ( $is_future ) {
				$col_class .= ' hnrk-bar-future';
			} elseif ( $is_today ) {
				$col_class .= ' hnrk-bar-today';
			}
			?>
			<div class="<?php echo esc_attr( $col_class ); ?>">
				<span class="hnrk-bar-count"><?php echo ( ! $is_future && $display > 0 ) ? esc_html( number_format_i18n( $display ) ) : ''; ?></span>
				<div class="hnrk-bar" style="height:<?php echo esc_attr( $height ); ?>px"></div>
				<span class="hnrk-bar-label"><?php echo esc_html( $label ); ?></span>
			</div>
			<?php
		endforeach;
		?>
	</div>
	<?php
}

/**
 * Render a horizontal bar chart (rows).
 *
 * @param array  $data   Associative array of key => count (null = future, skip).
 * @param string $format 'weekday' (Mon 10), 'day' (day number), 'month' (Jan), or '' (key as-is).
 */
function hnrk_render_hbar_chart( $data, $format = 'weekday' ) {
	$today  = gmdate( 'Y-m-d' );
	$values = array_filter( $data, fn( $v ) => null !== $v );
	$max    = $values ? max( $values ) : 0;
	?>
	<div class="hnrk-hbar-chart">
		<?php
		foreach ( $data as $key => $count ) :
			if ( null === $count ) {
				continue;
			}
			$width    = ( $max > 0 && $count > 0 ) ? max( 2, (int) round( ( $count / $max ) * 100 ) ) : 0;
			$is_today = ( $key === $today );

			if ( 'weekday' === $format ) {
				$label = date_i18n( 'D j', strtotime( $key ) );
			} elseif ( 'day' === $format ) {
				$label = (int) substr( $key, 8, 2 );
			} elseif ( 'month' === $format ) {
				$label = date_i18n( 'M', strtotime( $key . '-01' ) );
			} else {
				$label = $key;
			}
			?>
			<div class="hnrk-hbar-row<?php echo $is_today ? ' hnrk-hbar-today' : ''; ?>">
				<span class="hnrk-hbar-label"><?php echo esc_html( $label ); ?></span>
				<div class="hnrk-hbar-track">
					<div class="hnrk-hbar-bar" style="width:<?php echo esc_attr( $width ); ?>%"></div>
				</div>
				<span class="hnrk-hbar-count"><?php echo esc_html( number_format_i18n( $count ) ); ?></span>
			</div>
		<?php endforeach; ?>
	</div>
	<?php
}

/**
 * Register the Dashboard widget.
 */
function hnrk_register_dashboard_widget() {
	wp_add_dashboard_widget(
		'hnrk_visitor_counter_widget',
		__( 'Visitor Counter', 'hnrk-visitor-counter' ),
		'hnrk_render_dashboard_widget'
	);
}

/**
 * Render the Dashboard widget.
 */
function hnrk_render_dashboard_widget() {
	$total  = (int) get_option( 'hnrk_visitor_count_total', 0 );
	$today  = hnrk_get_daily_count();
	$weekly = hnrk_get_recent_daily_counts( 7 );
	?>
	<div class="hnrk-widget-stats">
		<div class="hnrk-widget-stat">
			<strong><?php echo esc_html( number_format_i18n( $total ) ); ?></strong>
			<span><?php esc_html_e( 'Total unique', 'hnrk-visitor-counter' ); ?></span>
		</div>
		<div class="hnrk-widget-stat">
			<strong><?php echo esc_html( number_format_i18n( $today ) ); ?></strong>
			<span><?php esc_html_e( 'Today', 'hnrk-visitor-counter' ); ?></span>
		</div>
	</div>
	<?php hnrk_render_hbar_chart( $weekly ); ?>
	<?php
}

/**
 * Get daily visitor counts for all days in a month.
 *
 * @param string $year_month Month in YYYY-MM format.
 * @return array Associative array of YYYY-MM-DD => count (null for future dates).
 */
function hnrk_get_month_daily_data( $year_month ) {
	$archive = (array) get_option( 'hnrk_visitor_archive', array() );
	$today   = gmdate( 'Y-m-d' );
	$days    = (int) gmdate( 't', strtotime( $year_month . '-01' ) );
	$result  = array();

	for ( $d = 1; $d <= $days; $d++ ) {
		$date = sprintf( '%s-%02d', $year_month, $d );
		if ( $date > $today ) {
			$result[ $date ] = null;
		} elseif ( $date === $today ) {
			$result[ $date ] = hnrk_get_daily_count();
		} else {
			$result[ $date ] = $archive[ $date ] ?? 0;
		}
	}
	return $result;
}

/**
 * Get monthly visitor counts for all months in a year.
 *
 * @param int $year Year.
 * @return array Associative array of YYYY-MM => count (null for future months).
 */
function hnrk_get_year_monthly_data( $year ) {
	$archive  = (array) get_option( 'hnrk_visitor_archive', array() );
	$today    = gmdate( 'Y-m-d' );
	$today_ym = gmdate( 'Y-m' );
	$year_str = sprintf( '%04d', $year );
	$result   = array();

	for ( $m = 1; $m <= 12; $m++ ) {
		$result[ sprintf( '%s-%02d', $year_str, $m ) ] = 0;
	}

	foreach ( $archive as $date => $count ) {
		if ( strncmp( $date, $year_str . '-', 5 ) !== 0 ) {
			continue;
		}
		$month_key = substr( $date, 0, 7 );
		if ( array_key_exists( $month_key, $result ) ) {
			$result[ $month_key ] += $count;
		}
	}

	// Replace today's archived count with the live transient value.
	if ( strncmp( $today, $year_str . '-', 5 ) === 0 ) {
		$today_archive       = $archive[ $today ] ?? 0;
		$result[ $today_ym ] = $result[ $today_ym ] - $today_archive + hnrk_get_daily_count();
	}

	// Mark future months as null.
	foreach ( array_keys( $result ) as $key ) {
		if ( $key > $today_ym ) {
			$result[ $key ] = null;
		}
	}

	return $result;
}

/**
 * Return unique visitor counts for the last N days.
 *
 * @param int $days Number of days to fetch.
 * @return array Associative array of date => count, newest first.
 */
function hnrk_get_recent_daily_counts( $days = 7 ) {
	$result = array();
	for ( $i = 0; $i < $days; $i++ ) {
		$date            = gmdate( 'Y-m-d', strtotime( "-{$i} days" ) );
		$result[ $date ] = hnrk_get_daily_count( $date );
	}
	return $result;
}

/**
 * Return unique visitor counts grouped by year, newest first.
 *
 * @return array Associative array of YYYY => count.
 */
function hnrk_get_yearly_counts() {
	$archive = (array) get_option( 'hnrk_visitor_archive', array() );
	$years   = array();
	foreach ( $archive as $date => $count ) {
		$year           = substr( $date, 0, 4 );
		$years[ $year ] = ( $years[ $year ] ?? 0 ) + $count;
	}
	krsort( $years );
	return $years;
}
