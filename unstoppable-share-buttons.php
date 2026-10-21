<?php
/**
 * Plugin Name: Unstoppable Share Buttons
 * Plugin URI: https://unstoppablesolutions.app/plugins/unstoppable-share-buttons
 * Description: Social sharing done properly. Inline, floating, sticky mobile and popup share bars with 48 networks, first-party click analytics, UTM tagging, a block, an Elementor widget and WooCommerce support.
 * Version: 1.0.9
 * Author: Unstoppable Solutions
 * Author URI: https://unstoppablesolutions.app
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: unstoppable-share-buttons
 * Domain Path: /languages
 * Requires at least: 5.8
 * Tested up to: 7.1
 * Requires PHP: 7.4
 *
 * @package Unstoppable_Share_Buttons
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Plugin constants.
define( 'USHB_VERSION', '1.0.9' );
define( 'USHB_FILE', __FILE__ );
define( 'USHB_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'USHB_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'USHB_BASENAME', plugin_basename( __FILE__ ) );
define( 'USHB_SLUG', 'unstoppable-share-buttons' );

require_once USHB_PLUGIN_DIR . 'includes/class-ushb-plugin.php';

/**
 * Start the plugin once all plugins are loaded.
 *
 * Waiting for plugins_loaded means WooCommerce and Elementor have had a chance
 * to declare themselves before the integrations check for them.
 */
function ushb_init() {
	USHB_Plugin::get_instance();
}
add_action( 'plugins_loaded', 'ushb_init' );

/**
 * Template tag: echo a share bar from a theme file.
 *
 * @param array $args Optional overrides. See USHB_Render::bar().
 */
function ushb_share_bar( $args = array() ) {
	if ( ! class_exists( 'USHB_Render' ) ) {
		return;
	}

	echo USHB_Render::bar( $args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Markup is assembled from individually escaped parts.
}

/**
 * Template tag: return a share bar as a string.
 *
 * @param array $args Optional overrides. See USHB_Render::bar().
 * @return string Bar markup.
 */
function ushb_get_share_bar( $args = array() ) {
	if ( ! class_exists( 'USHB_Render' ) ) {
		return '';
	}

	return USHB_Render::bar( $args );
}

// Activation and deactivation must be registered in the main file.
require_once USHB_PLUGIN_DIR . 'includes/class-ushb-install.php';
register_activation_hook( __FILE__, array( 'USHB_Install', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'USHB_Install', 'deactivate' ) );
