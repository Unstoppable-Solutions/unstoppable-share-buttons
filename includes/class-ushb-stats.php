<?php
/**
 * First-party share click analytics.
 *
 * Clicks are aggregated into one row per post, network, placement and day, so
 * the table stays small on busy sites. Nothing identifying the visitor is ever
 * written: no IP address, no user agent, no user ID.
 *
 * @package Unstoppable_Share_Buttons
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Records and reports share clicks.
 */
class USHB_Stats {

	/**
	 * Table name without the site prefix.
	 */
	const TABLE = 'ushb_clicks';

	/**
	 * Hooks cache invalidation.
	 */
	public static function init() {
		add_action( 'ushb_click_recorded', array( __CLASS__, 'clear_post_cache' ), 10, 1 );
	}

	/**
	 * The prefixed table name.
	 *
	 * @return string Table name.
	 */
	public static function table() {
		global $wpdb;

		return $wpdb->prefix . self::TABLE;
	}

	/**
	 * Creates or updates the click table.
	 */
	public static function install_table() {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$table   = self::table();
		$collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			post_id bigint(20) unsigned NOT NULL DEFAULT 0,
			network varchar(40) NOT NULL DEFAULT '',
			placement varchar(20) NOT NULL DEFAULT '',
			day date NOT NULL,
			clicks bigint(20) unsigned NOT NULL DEFAULT 0,
			PRIMARY KEY  (id),
			UNIQUE KEY combo (post_id,network,placement,day),
			KEY network (network),
			KEY day (day)
		) {$collate};";

		dbDelta( $sql );
	}

	/**
	 * Records one share click.
	 *
	 * @param int    $post_id   Post that was shared.
	 * @param string $network   Network slug.
	 * @param string $placement Placement slug.
	 * @return bool True when the click was stored.
	 */
	public static function record( $post_id, $network, $placement = 'inline' ) {
		global $wpdb;

		if ( ! USHB_Settings::get( 'track_clicks' ) ) {
			return false;
		}

		$network = sanitize_key( $network );

		// Validate against the catalogue rather than storing whatever arrived.
		if ( ! USHB_Networks::get( $network ) ) {
			return false;
		}

		$post_id   = absint( $post_id );
		$placement = sanitize_key( $placement );
		$table     = self::table();
		$day       = current_time( 'Y-m-d' );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Custom analytics table with no core API. The table name comes from $wpdb->prefix plus a class constant and never from input; all user values use placeholders.
		$result = $wpdb->query(
			$wpdb->prepare(
				"INSERT INTO {$table} (post_id, network, placement, day, clicks)
				VALUES (%d, %s, %s, %s, 1)
				ON DUPLICATE KEY UPDATE clicks = clicks + 1",
				$post_id,
				$network,
				$placement,
				$day
			)
		);
		// phpcs:enable

		if ( false === $result ) {
			return false;
		}

		/**
		 * Fires after a share click is recorded.
		 *
		 * @param int    $post_id   Post ID.
		 * @param string $network   Network slug.
		 * @param string $placement Placement slug.
		 */
		do_action( 'ushb_click_recorded', $post_id, $network, $placement );

		return true;
	}

	/**
	 * Per-network click counts for one post, plus a _total key.
	 *
	 * @param int $post_id Post ID.
	 * @return array Counts keyed by network slug.
	 */
	public static function counts_for_post( $post_id ) {
		global $wpdb;

		$post_id = absint( $post_id );

		if ( ! $post_id ) {
			return array();
		}

		$cache_key = 'ushb_counts_' . $post_id;
		$cached    = get_transient( $cache_key );

		if ( false !== $cached && is_array( $cached ) ) {
			return $cached;
		}

		$table = self::table();

		// phpcs:disable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Custom analytics table with no core API. The table name comes from $wpdb->prefix plus a class constant and never from input; all user values use placeholders.
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT network, SUM(clicks) AS total FROM {$table} WHERE post_id = %d GROUP BY network",
				$post_id
			),
			ARRAY_A
		);
		// phpcs:enable

		$counts = array( '_total' => 0 );

		if ( is_array( $rows ) ) {
			foreach ( $rows as $row ) {
				$counts[ $row['network'] ] = (int) $row['total'];
				$counts['_total']         += (int) $row['total'];
			}
		}

		set_transient( $cache_key, $counts, 5 * MINUTE_IN_SECONDS );

		return $counts;
	}

	/**
	 * Drops the cached counts for a post.
	 *
	 * @param int $post_id Post ID.
	 */
	public static function clear_post_cache( $post_id ) {
		delete_transient( 'ushb_counts_' . absint( $post_id ) );
	}

	/**
	 * Totals per network across a date window.
	 *
	 * @param int $days How many days back to look.
	 * @return array Network slug mapped to clicks, highest first.
	 */
	public static function totals_by_network( $days = 30 ) {
		global $wpdb;

		$table = self::table();
		$since = gmdate( 'Y-m-d', strtotime( '-' . absint( $days ) . ' days' ) );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Custom analytics table with no core API. The table name comes from $wpdb->prefix plus a class constant and never from input; all user values use placeholders.
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT network, SUM(clicks) AS total FROM {$table} WHERE day >= %s GROUP BY network ORDER BY total DESC",
				$since
			),
			ARRAY_A
		);
		// phpcs:enable

		$out = array();

		if ( is_array( $rows ) ) {
			foreach ( $rows as $row ) {
				$out[ $row['network'] ] = (int) $row['total'];
			}
		}

		return $out;
	}

	/**
	 * The most shared posts in a date window.
	 *
	 * @param int $days  How many days back to look.
	 * @param int $limit Row limit.
	 * @return array Ranked posts, each with post_id and total.
	 */
	public static function top_posts( $days = 30, $limit = 10 ) {
		global $wpdb;

		$table = self::table();
		$since = gmdate( 'Y-m-d', strtotime( '-' . absint( $days ) . ' days' ) );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Custom analytics table with no core API. The table name comes from $wpdb->prefix plus a class constant and never from input; all user values use placeholders.
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT post_id, SUM(clicks) AS total FROM {$table}
				WHERE day >= %s AND post_id > 0
				GROUP BY post_id ORDER BY total DESC LIMIT %d",
				$since,
				absint( $limit )
			),
			ARRAY_A
		);
		// phpcs:enable

		$out = array();

		if ( is_array( $rows ) ) {
			foreach ( $rows as $row ) {
				$out[] = array(
					'post_id' => (int) $row['post_id'],
					'total'   => (int) $row['total'],
				);
			}
		}

		return $out;
	}

	/**
	 * A day by day series for charting, with gaps filled.
	 *
	 * @param int $days How many days back to look.
	 * @return array Date in Y-m-d mapped to clicks, oldest first.
	 */
	public static function daily_series( $days = 30 ) {
		global $wpdb;

		$days  = max( 1, absint( $days ) );
		$table = self::table();
		$since = gmdate( 'Y-m-d', strtotime( '-' . ( $days - 1 ) . ' days' ) );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Custom analytics table with no core API. The table name comes from $wpdb->prefix plus a class constant and never from input; all user values use placeholders.
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT day, SUM(clicks) AS total FROM {$table} WHERE day >= %s GROUP BY day",
				$since
			),
			ARRAY_A
		);
		// phpcs:enable

		$found = array();

		if ( is_array( $rows ) ) {
			foreach ( $rows as $row ) {
				$found[ $row['day'] ] = (int) $row['total'];
			}
		}

		$series = array();

		for ( $i = $days - 1; $i >= 0; $i-- ) {
			$date            = gmdate( 'Y-m-d', strtotime( '-' . $i . ' days' ) );
			$series[ $date ] = isset( $found[ $date ] ) ? $found[ $date ] : 0;
		}

		return $series;
	}

	/**
	 * Grand total of every recorded click.
	 *
	 * @return int Click count.
	 */
	public static function grand_total() {
		global $wpdb;

		$cached = get_transient( 'ushb_grand_total' );

		if ( false !== $cached ) {
			return (int) $cached;
		}

		$table = self::table();

		// phpcs:disable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Custom analytics table with no core API. The table name comes from $wpdb->prefix plus a class constant and never from input; all user values use placeholders.
		$total = (int) $wpdb->get_var( "SELECT SUM(clicks) FROM {$table}" );
		// phpcs:enable

		set_transient( 'ushb_grand_total', $total, 5 * MINUTE_IN_SECONDS );

		return $total;
	}

	/**
	 * Deletes every recorded click.
	 */
	public static function reset() {
		global $wpdb;

		$table = self::table();

		// phpcs:disable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Custom analytics table with no core API. The table name comes from $wpdb->prefix plus a class constant and never from input; all user values use placeholders.
		$wpdb->query( "TRUNCATE TABLE {$table}" );
		// phpcs:enable

		delete_transient( 'ushb_grand_total' );
	}

	/**
	 * Shortens large numbers for display, so 1200 becomes 1.2K.
	 *
	 * @param int $number Raw count.
	 * @return string Display string.
	 */
	public static function format_number( $number ) {
		$number = (int) $number;

		if ( $number >= 1000000 ) {
			return round( $number / 1000000, 1 ) . 'M';
		}

		if ( $number >= 1000 ) {
			return round( $number / 1000, 1 ) . 'K';
		}

		return (string) $number;
	}
}
