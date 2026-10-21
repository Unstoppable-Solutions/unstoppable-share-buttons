<?php
/**
 * AJAX handlers.
 *
 * Every handler follows the same order: verify the nonce, check the
 * capability, sanitise the input, validate it, then act.
 *
 * @package Unstoppable_Share_Buttons
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the plugin's AJAX endpoints.
 */
class USHB_Ajax {

	/**
	 * Nonce action used by the public click recorder.
	 */
	const TRACK_NONCE = 'ushb_track_nonce';

	/**
	 * Hooks the handlers.
	 */
	public static function init() {
		// Share clicks come from visitors, who may be logged out.
		add_action( 'wp_ajax_ushb_track_share', array( __CLASS__, 'track_share' ) );
		add_action( 'wp_ajax_nopriv_ushb_track_share', array( __CLASS__, 'track_share' ) );

		// Notice dismissal is administrators only.
		add_action( 'wp_ajax_ushb_dismiss_notice', array( __CLASS__, 'dismiss_notice' ) );
	}

	/**
	 * Records a share click.
	 *
	 * This endpoint is reachable by logged-out visitors because that is who
	 * shares posts. It is protected by a nonce and a short per-IP throttle, and
	 * it only ever increments a counter. The IP is used for throttling alone and
	 * is never stored.
	 */
	public static function track_share() {
		// 1. Verify nonce. A stale nonce on a cached page simply means no record.
		if ( ! check_ajax_referer( self::TRACK_NONCE, 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid security token.', 'unstoppable-share-buttons' ) ) );
		}

		// 2. Respect the site's setting.
		if ( ! USHB_Settings::get( 'track_clicks' ) ) {
			wp_send_json_error( array( 'message' => __( 'Click recording is switched off.', 'unstoppable-share-buttons' ) ) );
		}

		// 3. Rate limit before touching the database.
		if ( self::is_rate_limited() ) {
			wp_send_json_error( array( 'message' => __( 'Too many requests.', 'unstoppable-share-buttons' ) ) );
		}

		// 4. Sanitise input.
		$post_id   = isset( $_POST['post_id'] ) ? absint( wp_unslash( $_POST['post_id'] ) ) : 0;
		$network   = isset( $_POST['network'] ) ? sanitize_key( wp_unslash( $_POST['network'] ) ) : '';
		$placement = isset( $_POST['placement'] ) ? sanitize_key( wp_unslash( $_POST['placement'] ) ) : 'inline';

		// 5. Validate.
		if ( empty( $network ) || ! USHB_Networks::get( $network ) ) {
			wp_send_json_error( array( 'message' => __( 'Unknown network.', 'unstoppable-share-buttons' ) ) );
		}

		// 6. Record and respond.
		$recorded = USHB_Stats::record( $post_id, $network, $placement );

		if ( ! $recorded ) {
			wp_send_json_error( array( 'message' => __( 'Could not record that share.', 'unstoppable-share-buttons' ) ) );
		}

		wp_send_json_success( array( 'recorded' => true ) );
	}

	/**
	 * Stores an admin notice dismissal.
	 */
	public static function dismiss_notice() {
		// 1. Verify nonce.
		check_ajax_referer( 'ushb_dismiss_notice', 'nonce' );

		// 2. Check capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'unstoppable-share-buttons' ) ) );
		}

		// 3. Sanitise.
		$notice = isset( $_POST['notice'] ) ? sanitize_key( wp_unslash( $_POST['notice'] ) ) : '';

		// 4. Validate against the known notice types.
		$allowed = array( 'welcome', 'announcement' );

		if ( ! in_array( $notice, $allowed, true ) ) {
			wp_send_json_error( array( 'message' => __( 'Unknown notice.', 'unstoppable-share-buttons' ) ) );
		}

		// 5. Store the dismissal.
		update_option( 'ushb_' . $notice . '_notice_dismissed', true );

		if ( 'announcement' === $notice ) {
			$announcement_id = isset( $_POST['announcement_id'] ) ? sanitize_key( wp_unslash( $_POST['announcement_id'] ) ) : '';

			if ( $announcement_id ) {
				update_option( 'ushb_announcement_dismissed_id', $announcement_id );
			}
		}

		wp_send_json_success();
	}

	/**
	 * Throttles the public endpoint to 30 requests a minute per address.
	 *
	 * The address is hashed into a transient key and is not stored anywhere.
	 *
	 * @return bool True when the caller should be turned away.
	 */
	private static function is_rate_limited() {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';

		if ( '' === $ip ) {
			return false;
		}

		$key   = 'ushb_rate_' . md5( $ip );
		$count = (int) get_transient( $key );

		if ( $count >= 30 ) {
			return true;
		}

		set_transient( $key, $count + 1, MINUTE_IN_SECONDS );

		return false;
	}
}
