<?php
/**
 * Admin notices.
 *
 * Every notice here is dismissible and only appears where it is relevant. No
 * site wide nagging, no pop-ups, no messages that block the dashboard.
 *
 * @package Unstoppable_Share_Buttons
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Displays the plugin's admin notices.
 */
class USHB_Admin_Notices {

	/**
	 * Option and handle prefix.
	 *
	 * @var string
	 */
	private $prefix = 'ushb';

	/**
	 * Plugin display name.
	 *
	 * @var string
	 */
	private $plugin_name = 'Unstoppable Share Buttons';

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_notices', array( $this, 'display_notices' ) );
		add_action( 'admin_notices', array( $this, 'display_flash_notices' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	/**
	 * Loads the dismissal script.
	 */
	public function enqueue_scripts() {
		wp_enqueue_script(
			'ushb-admin-notices',
			USHB_PLUGIN_URL . 'assets/js/admin-notices.js',
			array(),
			USHB_VERSION,
			true
		);

		wp_localize_script(
			'ushb-admin-notices',
			'ushbNotices',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'ushb_dismiss_notice' ),
			)
		);
	}

	/**
	 * Displays the persistent notices.
	 */
	public function display_notices() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$this->maybe_show_welcome_notice();
		$this->maybe_show_announcement_notice();
	}

	/**
	 * Setup prompt shown after the plugin is first activated.
	 */
	private function maybe_show_welcome_notice() {
		if ( get_option( $this->prefix . '_welcome_notice_dismissed' ) ) {
			return;
		}

		$screen = get_current_screen();

		// Only on the dashboard, the plugins list and this plugin's own pages.
		if ( ! $screen ) {
			return;
		}

		// This plugin's own screens only. The dashboard and the plugins list
		// belong to the user, not to us.
		if ( false === strpos( $screen->id, USHB_Admin::MENU_SLUG ) ) {
			return;
		}

		$settings_url = admin_url( 'admin.php?page=' . USHB_Admin::MENU_SLUG );
		?>
		<div class="notice notice-success is-dismissible ushb-notice" data-notice="welcome">
			<p>
				<strong><?php echo esc_html( $this->plugin_name ); ?></strong>
				&mdash;
				<?php esc_html_e( 'Share buttons are live on your posts already. Choose your networks and style whenever you are ready.', 'unstoppable-share-buttons' ); ?>
				<a href="<?php echo esc_url( $settings_url ); ?>" class="button button-primary ushb-notice__button">
					<?php esc_html_e( 'Open settings', 'unstoppable-share-buttons' ); ?>
				</a>
			</p>
		</div>
		<?php
	}

	/**
	 * Important announcements, such as a security or breaking change notice.
	 *
	 * The announcement is read from a local option. Nothing is fetched from a
	 * remote server.
	 */
	private function maybe_show_announcement_notice() {
		$announcement = get_option( $this->prefix . '_announcement' );

		if ( empty( $announcement ) || ! is_array( $announcement ) || empty( $announcement['message'] ) ) {
			return;
		}

		$announcement_id = isset( $announcement['id'] ) ? sanitize_key( $announcement['id'] ) : '';
		$dismissed_id    = get_option( $this->prefix . '_announcement_dismissed_id' );

		if ( $announcement_id && $dismissed_id === $announcement_id ) {
			return;
		}

		$type    = isset( $announcement['type'] ) ? $announcement['type'] : 'info';
		$allowed = array( 'info', 'warning', 'error', 'success' );

		if ( ! in_array( $type, $allowed, true ) ) {
			$type = 'info';
		}
		?>
		<div class="notice notice-<?php echo esc_attr( $type ); ?> is-dismissible ushb-notice"
			data-notice="announcement"
			data-announcement-id="<?php echo esc_attr( $announcement_id ); ?>">
			<p>
				<strong><?php echo esc_html( $this->plugin_name ); ?></strong>
				&mdash;
				<?php echo esc_html( $announcement['message'] ); ?>
				<?php if ( ! empty( $announcement['link'] ) && ! empty( $announcement['link_text'] ) ) : ?>
					<a href="<?php echo esc_url( $announcement['link'] ); ?>" target="_blank" rel="noopener noreferrer" class="button ushb-notice__button">
						<?php echo esc_html( $announcement['link_text'] ); ?>
					</a>
				<?php endif; ?>
			</p>
		</div>
		<?php
	}

	/**
	 * Displays one-time success and error messages.
	 */
	public function display_flash_notices() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$notices = get_transient( $this->prefix . '_flash_notices' );

		if ( empty( $notices ) || ! is_array( $notices ) ) {
			return;
		}

		$allowed = array( 'success', 'error', 'warning', 'info' );

		foreach ( $notices as $notice ) {
			$message = isset( $notice['message'] ) ? $notice['message'] : '';
			$type    = isset( $notice['type'] ) ? $notice['type'] : 'info';

			if ( '' === $message ) {
				continue;
			}

			if ( ! in_array( $type, $allowed, true ) ) {
				$type = 'info';
			}

			printf(
				'<div class="notice notice-%1$s is-dismissible"><p>%2$s</p></div>',
				esc_attr( $type ),
				esc_html( $message )
			);
		}

		delete_transient( $this->prefix . '_flash_notices' );
	}

	/**
	 * Queues a message for the next page load.
	 *
	 * @param string $message The notice text.
	 * @param string $type    One of success, error, warning or info.
	 */
	public static function add_flash_notice( $message, $type = 'success' ) {
		$notices = get_transient( 'ushb_flash_notices' );

		if ( ! is_array( $notices ) ) {
			$notices = array();
		}

		$notices[] = array(
			'message' => $message,
			'type'    => $type,
		);

		set_transient( 'ushb_flash_notices', $notices, 60 );
	}
}
