<?php
/**
 * Log functions for the HNRK User Activity Log plugin.
 *
 * @package HNRK_User_Activity_Log
 */

/**
 * Log user login times.
 *
 * @param string  $user_login The user's login name.
 * @param WP_User $user       The user object.
 */
function hnrk_log_user_login( $user_login, $user ) {
	if ( in_array( 'subscriber', (array) $user->roles, true ) ) {
		$time    = current_time( 'mysql' );
		$user_id = $user->ID;

		$logins = get_user_meta( $user_id, 'login_times', true );
		if ( ! $logins ) {
			$logins = array();
		}

		$logins[] = array(
			'time'  => $time,
			'pages' => array(),
		);

		update_user_meta( $user_id, 'login_times', $logins );
	}
}

/**
 * Log user registration time.
 *
 * @param int $user_id The newly registered user's ID.
 */
function hnrk_log_user_registration( $user_id ) {
	$registration_time = current_time( 'mysql' );

	$logins = get_user_meta( $user_id, 'login_times', true );
	if ( ! $logins ) {
		$logins = array();
	}

	array_unshift(
		$logins,
		array(
			'time'  => $registration_time,
			'pages' => array(),
		)
	);

	update_user_meta( $user_id, 'login_times', $logins );
}
add_action( 'user_register', 'hnrk_log_user_registration' );

/**
 * Log page visits for logged-in subscribers.
 */
function hnrk_log_page_visits() {
	if ( is_user_logged_in() ) {
		$user = wp_get_current_user();
		if ( in_array( 'subscriber', (array) $user->roles, true ) ) {
			$user_id      = $user->ID;
			$current_page = home_url( add_query_arg( array(), $GLOBALS['wp']->request ) );
			$time         = current_time( 'mysql' );

			$logins = get_user_meta( $user_id, 'login_times', true );
			if ( $logins ) {
				$last_login_index                       = count( $logins ) - 1;
				$logins[ $last_login_index ]['pages'][] = array(
					'page' => $current_page,
					'time' => $time,
				);

				update_user_meta( $user_id, 'login_times', $logins );
			}
		}
	}
}
