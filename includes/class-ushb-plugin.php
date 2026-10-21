<?php
/**
 * Plugin bootstrap: loads every class and wires the hooks.
 *
 * @package Unstoppable_Share_Buttons
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main plugin class.
 */
class USHB_Plugin {

	/**
	 * Single instance of the class.
	 *
	 * @var USHB_Plugin
	 */
	private static $instance = null;

	/**
	 * Get single instance of the class.
	 *
	 * @return USHB_Plugin
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->includes();
		$this->init_hooks();
	}

	/**
	 * Load the plugin's files.
	 *
	 * Files are required explicitly rather than autoloaded so the load order is
	 * obvious to anyone reading this file.
	 */
	private function includes() {
		// Core.
		require_once USHB_PLUGIN_DIR . 'includes/class-ushb-icons.php';
		require_once USHB_PLUGIN_DIR . 'includes/class-ushb-networks.php';
		require_once USHB_PLUGIN_DIR . 'includes/class-ushb-settings.php';
		require_once USHB_PLUGIN_DIR . 'includes/class-ushb-install.php';
		require_once USHB_PLUGIN_DIR . 'includes/class-ushb-stats.php';
		require_once USHB_PLUGIN_DIR . 'includes/class-ushb-ajax.php';
		require_once USHB_PLUGIN_DIR . 'includes/class-ushb-shortcodes.php';
		require_once USHB_PLUGIN_DIR . 'includes/class-ushb-blocks.php';
		require_once USHB_PLUGIN_DIR . 'includes/class-ushb-meta-tags.php';
		require_once USHB_PLUGIN_DIR . 'includes/class-ushb-woocommerce.php';
		require_once USHB_PLUGIN_DIR . 'includes/class-ushb-elementor.php';

		// Front end.
		require_once USHB_PLUGIN_DIR . 'frontend/class-ushb-render.php';
		require_once USHB_PLUGIN_DIR . 'frontend/class-ushb-frontend.php';

		// Admin.
		if ( is_admin() ) {
			require_once USHB_PLUGIN_DIR . 'admin/class-ushb-admin.php';
			require_once USHB_PLUGIN_DIR . 'admin/class-ushb-admin-notices.php';
		}
	}

	/**
	 * Initialize hooks.
	 */
	private function init_hooks() {
		USHB_Install::maybe_upgrade();

		USHB_Frontend::init();
		USHB_Shortcodes::init();
		USHB_Blocks::init();
		USHB_Meta_Tags::init();
		USHB_Ajax::init();
		USHB_Stats::init();
		USHB_WooCommerce::init();
		USHB_Elementor::init();

		if ( is_admin() ) {
			USHB_Admin::init();
			new USHB_Admin_Notices();
		}
	}
}
