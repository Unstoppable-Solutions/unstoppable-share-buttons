<?php
/**
 * Runs when the plugin is deleted from the Plugins screen.
 *
 * Nothing is removed unless the site owner asked for it, by ticking
 * "Delete settings and share history on uninstall" under
 * Unstoppable Share > Tools. Deactivating and reactivating never loses a
 * site's setup, and neither does deleting the plugin with that box unticked.
 *
 * @package Unstoppable_Share_Buttons
 */

// Prevent direct access. WordPress defines this constant only when it is
// genuinely uninstalling this plugin.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$ushb_settings = get_option( 'ushb_settings', array() );

if ( empty( $ushb_settings['delete_data_on_uninstall'] ) ) {
	return;
}

global $wpdb;

// Named options.
$ushb_options = array(
	'ushb_settings',
	'ushb_version',
	'ushb_install_date',
	'ushb_settings_repaired',
	'ushb_announcement',
	'ushb_announcement_dismissed_id',
	'ushb_welcome_notice_dismissed',
);

foreach ( $ushb_options as $ushb_option ) {
	delete_option( $ushb_option );
}

// The analytics table.
// phpcs:disable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- One-time cleanup of our own table on uninstall, where no caching applies. The table name is built from $wpdb->prefix plus a literal and never from input, so there is no value to place.
$ushb_table = $wpdb->prefix . 'ushb_clicks';

$wpdb->query( "DROP TABLE IF EXISTS {$ushb_table}" );

// Cached per-post counts, the grand total and any queued flash notices. These
// are transients, so they are removed by name pattern rather than one by one.
$wpdb->query(
	"DELETE FROM {$wpdb->options}
	WHERE option_name LIKE '\_transient\_ushb\_%'
	OR option_name LIKE '\_transient\_timeout\_ushb\_%'"
);
// phpcs:enable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter

// On a multisite network the options above are per-site, so clear the cache
// that may still hold them for this site.
wp_cache_flush();
