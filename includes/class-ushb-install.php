<?php
/**
 * Activation, upgrade and deactivation.
 *
 * @package Unstoppable_Share_Buttons
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sets the plugin up and keeps its schema current.
 */
class USHB_Install {

	/**
	 * Option holding the installed version number.
	 */
	const VERSION_OPTION = 'ushb_version';

	/**
	 * Runs on activation.
	 */
	public static function activate() {
		require_once USHB_PLUGIN_DIR . 'includes/class-ushb-settings.php';
		require_once USHB_PLUGIN_DIR . 'includes/class-ushb-networks.php';
		require_once USHB_PLUGIN_DIR . 'includes/class-ushb-icons.php';
		require_once USHB_PLUGIN_DIR . 'includes/class-ushb-stats.php';

		USHB_Stats::install_table();

		if ( false === get_option( USHB_Settings::OPTION, false ) ) {
			add_option( USHB_Settings::OPTION, USHB_Settings::defaults() );
		}

		if ( ! get_option( 'ushb_install_date' ) ) {
			add_option( 'ushb_install_date', time() );
		}

		update_option( self::VERSION_OPTION, USHB_VERSION );
	}

	/**
	 * Runs on deactivation.
	 *
	 * Nothing is deleted here. Deactivating and reactivating never loses a
	 * site's setup.
	 */
	public static function deactivate() {
		delete_transient( 'ushb_flash_notices' );
	}

	/**
	 * Brings an older install up to date after the plugin files change.
	 */
	public static function maybe_upgrade() {
		// The repair is checked before the version guard, because a site can
		// already be stamped with the current version while still carrying
		// settings emptied by the older save bug.
		self::repair_damaged_settings();

		$stored = get_option( self::VERSION_OPTION, '0' );

		if ( version_compare( $stored, USHB_VERSION, '>=' ) ) {
			return;
		}

		USHB_Stats::install_table();

		update_option( self::VERSION_OPTION, USHB_VERSION );
	}

	/**
	 * Repairs settings emptied by the save bug present before 1.0.3.
	 *
	 * Saving one settings tab used to write every other tab back as its
	 * default, which emptied the network lists and the post type list. A site
	 * left in that state shows no share bars at all, and updating the plugin
	 * cannot fix it on its own because the damaged values are already stored.
	 *
	 * The signature is narrow on purpose: an empty post type list together with
	 * an empty inline network list is the bug, not a choice anyone would make,
	 * since in that state the plugin cannot display anything anywhere.
	 */
	private static function repair_damaged_settings() {
		// Gated on the damage itself rather than on a version number, so a site
		// that upgrades through several releases still gets exactly one repair
		// and cannot miss its window.
		if ( get_option( 'ushb_settings_repaired' ) ) {
			return;
		}

		$saved = get_option( USHB_Settings::OPTION, array() );

		if ( ! is_array( $saved ) || empty( $saved ) ) {
			return;
		}

		// Whatever happens below, this runs once and never touches a site again.
		update_option( 'ushb_settings_repaired', time() );

		$no_types    = empty( $saved['post_types'] ) || ! is_array( $saved['post_types'] );
		$no_networks = empty( $saved['inline_networks'] ) || ! is_array( $saved['inline_networks'] );

		if ( ! $no_types || ! $no_networks ) {
			return;
		}

		$defaults = USHB_Settings::defaults();
		$restore  = array(
			'post_types',
			'inline_networks',
			'floating_networks',
			'sticky_networks',
			'popup_networks',
			'woo_networks',
		);

		foreach ( $restore as $key ) {
			if ( empty( $saved[ $key ] ) ) {
				$saved[ $key ] = $defaults[ $key ];
			}
		}

		// The same bug switched this off, and with it off nothing renders.
		if ( empty( $saved['inline_enabled'] ) ) {
			$saved['inline_enabled'] = 1;
		}

		update_option( USHB_Settings::OPTION, $saved );
	}
}
