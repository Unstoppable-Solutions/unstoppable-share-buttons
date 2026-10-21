<?php
/**
 * Elementor integration.
 *
 * @package Unstoppable_Share_Buttons
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Adds a share buttons widget to the Elementor panel.
 */
class USHB_Elementor {

	/**
	 * Hooks widget registration if Elementor is present.
	 */
	public static function init() {
		add_action( 'elementor/widgets/register', array( __CLASS__, 'register' ) );
	}

	/**
	 * Registers the widget.
	 *
	 * @param object $widgets_manager Elementor widget manager.
	 */
	public static function register( $widgets_manager ) {
		if ( ! class_exists( '\Elementor\Widget_Base' ) || ! is_object( $widgets_manager ) ) {
			return;
		}

		require_once USHB_PLUGIN_DIR . 'includes/class-ushb-elementor-widget.php';

		if ( class_exists( 'USHB_Elementor_Widget' ) ) {
			$widgets_manager->register( new USHB_Elementor_Widget() );
		}
	}
}
