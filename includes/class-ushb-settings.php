<?php
/**
 * Settings storage, defaults and sanitisation.
 *
 * @package Unstoppable_Share_Buttons
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Reads, writes and cleans the ushb_settings option.
 */
class USHB_Settings {

	/**
	 * Option name holding every setting.
	 */
	const OPTION = 'ushb_settings';

	/**
	 * Runtime cache of the merged settings array.
	 *
	 * @var array|null
	 */
	private static $cache = null;

	/**
	 * The shipped defaults.
	 *
	 * Every feature is on or available from the start. Nothing here is a trial,
	 * a quota or a locked feature.
	 *
	 * @return array Default settings.
	 */
	public static function defaults() {
		$defaults = array(

			// Where bars may appear.
			'post_types'               => array( 'post' ),
			'show_on_archives'         => 0,
			'show_on_front'            => 0,
			'exclude_ids'              => '',
			'include_ids'              => '',
			'hide_for_logged_in'       => 0,

			// Inline bar.
			'inline_enabled'           => 1,
			'inline_position'          => 'below',
			'inline_paragraph'         => 2,
			'inline_align'             => 'left',
			'inline_networks'          => array( 'facebook', 'x', 'linkedin', 'whatsapp', 'email', 'copy' ),
			'inline_heading'           => '',

			// Floating sidebar.
			'floating_enabled'         => 0,
			'floating_side'            => 'left',
			'floating_offset'          => 50,
			'floating_networks'        => array( 'facebook', 'x', 'linkedin', 'pinterest', 'copy' ),
			'floating_min_width'       => 1024,

			// Sticky mobile bar.
			'sticky_enabled'           => 0,
			'sticky_networks'          => array( 'whatsapp', 'facebook', 'x', 'native', 'copy' ),
			'sticky_max_width'         => 782,

			// Universal share button.
			'popup_label'              => '',
			'popup_networks'           => array( 'facebook', 'x', 'linkedin', 'whatsapp', 'telegram', 'email', 'copy', 'qr' ),

			// Look and feel.
			'shape'                    => 'rounded',
			'style'                    => 'filled',
			'size'                     => 42,
			'gap'                      => 8,
			'radius'                   => 10,
			'palette'                  => 'brand',
			'custom_color'             => '#f0b849',
			'custom_text_color'        => '#1a202c',
			'labels'                   => 'icon',
			'hover_effect'             => 'lift',
			'full_width'               => 0,
			'show_counts'              => 0,
			'count_threshold'          => 10,
			'cta_text'                 => '',
			'cta_position'             => 'above',
			'color_scheme'             => 'auto',

			// Measurement. Off by default, per the privacy rules.
			'track_clicks'             => 0,
			'ga4_enabled'              => 0,
			'meta_pixel_enabled'       => 0,
			'datalayer_enabled'        => 0,
			'utm_enabled'              => 0,
			'utm_source'               => '{network}',
			'utm_medium'               => 'social',
			'utm_campaign'             => 'share',
			'utm_content'              => '',
			'utm_term'                 => '',

			// Content and meta.
			'fb_app_id'                => '',
			'x_via'                    => '',
			'x_hashtags'               => '',
			'ai_prompt'                => 'Read this article and give me a short summary of the key points: {url}',
			'copy_confirmation'        => '',
			'og_enabled'               => 0,
			'og_default_image'         => '',
			'twitter_card'             => 'summary_large_image',

			// WooCommerce.
			'woo_enabled'              => 0,
			'woo_position'             => 'after_summary',
			'woo_networks'             => array( 'facebook', 'x', 'pinterest', 'whatsapp', 'email' ),

			// Housekeeping.
			'delete_data_on_uninstall' => 0,
		);

		/**
		 * Filters the shipped default settings.
		 *
		 * @param array $defaults Default settings.
		 */
		return apply_filters( 'ushb_default_settings', $defaults );
	}

	/**
	 * Returns saved settings merged over the defaults.
	 *
	 * @return array Settings.
	 */
	public static function all() {
		if ( null !== self::$cache ) {
			return self::$cache;
		}

		$saved = get_option( self::OPTION, array() );

		if ( ! is_array( $saved ) ) {
			$saved = array();
		}

		/**
		 * Filters the resolved settings on every read.
		 *
		 * @param array $settings Merged settings.
		 */
		self::$cache = apply_filters( 'ushb_settings', array_merge( self::defaults(), $saved ) );

		return self::$cache;
	}

	/**
	 * Reads a single setting.
	 *
	 * @param string $key           Setting key.
	 * @param mixed  $default_value Fallback when the key is unknown.
	 * @return mixed Setting value.
	 */
	public static function get( $key, $default_value = null ) {
		$all = self::all();

		return array_key_exists( $key, $all ) ? $all[ $key ] : $default_value;
	}

	/**
	 * Saves a full settings array and clears the runtime cache.
	 *
	 * @param array $settings Settings to store.
	 */
	public static function save( $settings ) {
		update_option( self::OPTION, $settings );
		self::$cache = null;
	}

	/**
	 * Empties the runtime cache.
	 */
	public static function flush() {
		self::$cache = null;
	}

	/**
	 * Cleans a raw submitted payload into storable settings.
	 *
	 * The approach throughout is sanitise, then validate, then reject: anything
	 * that is not a known key with an acceptable value is replaced by its
	 * default rather than trusted.
	 *
	 * @param array $input Raw submitted values, already unslashed by the caller.
	 * @return array Sanitised settings.
	 */
	public static function sanitize( $input ) {
		$defaults = self::defaults();

		if ( ! is_array( $input ) ) {
			return $defaults;
		}

		$out = array();

		// Checkboxes: present means on, absent means off.
		$checkboxes = array(
			'show_on_archives',
			'show_on_front',
			'hide_for_logged_in',
			'inline_enabled',
			'floating_enabled',
			'sticky_enabled',
			'full_width',
			'show_counts',
			'track_clicks',
			'ga4_enabled',
			'meta_pixel_enabled',
			'datalayer_enabled',
			'utm_enabled',
			'og_enabled',
			'woo_enabled',
			'delete_data_on_uninstall',
		);

		foreach ( $checkboxes as $key ) {
			$out[ $key ] = empty( $input[ $key ] ) ? 0 : 1;
		}

		// Network lists: whitelisted against the catalogue.
		$network_lists = array(
			'inline_networks',
			'floating_networks',
			'sticky_networks',
			'popup_networks',
			'woo_networks',
		);

		foreach ( $network_lists as $key ) {
			$out[ $key ] = USHB_Networks::filter_valid( isset( $input[ $key ] ) ? $input[ $key ] : array() );
		}

		// Post types: public, registered types only.
		$public_types      = array_keys( get_post_types( array( 'public' => true ), 'names' ) );
		$submitted_types   = isset( $input['post_types'] ) ? (array) $input['post_types'] : array();
		$out['post_types'] = array_values( array_intersect( array_map( 'sanitize_key', $submitted_types ), $public_types ) );

		// Fixed choices: anything unrecognised falls back to the default.
		$enums = array(
			'inline_position' => array( 'above', 'below', 'both', 'after_paragraph' ),
			'inline_align'    => array( 'left', 'center', 'right' ),
			'floating_side'   => array( 'left', 'right' ),
			'shape'           => array( 'square', 'rounded', 'pill', 'circle', 'ribbon', 'drop' ),
			'style'           => array( 'filled', 'outline', 'minimal', 'dashed', 'glass', 'gradient' ),
			'palette'         => array( 'brand', 'custom', 'mono', 'inherit' ),
			'labels'          => array( 'icon', 'label', 'both' ),
			'hover_effect'    => array( 'none', 'lift', 'grow', 'slide', 'glow' ),
			'cta_position'    => array( 'above', 'below' ),
			'color_scheme'    => array( 'auto', 'light', 'dark' ),
			'twitter_card'    => array( 'summary', 'summary_large_image' ),
			'woo_position'    => array( 'after_price', 'after_add_to_cart', 'after_meta', 'after_summary', 'in_tabs' ),
		);

		foreach ( $enums as $key => $allowed ) {
			$value       = isset( $input[ $key ] ) ? sanitize_key( $input[ $key ] ) : '';
			$out[ $key ] = in_array( $value, $allowed, true ) ? $value : $defaults[ $key ];
		}

		// Numbers: clamped to a sensible range.
		$ints = array(
			'inline_paragraph'   => array( 1, 50 ),
			'floating_offset'    => array( 0, 100 ),
			'floating_min_width' => array( 0, 2560 ),
			'sticky_max_width'   => array( 0, 2560 ),
			'size'               => array( 24, 96 ),
			'gap'                => array( 0, 40 ),
			'radius'             => array( 0, 50 ),
			'count_threshold'    => array( 0, 100000 ),
		);

		foreach ( $ints as $key => $range ) {
			$value       = isset( $input[ $key ] ) ? intval( $input[ $key ] ) : $defaults[ $key ];
			$out[ $key ] = max( $range[0], min( $range[1], $value ) );
		}

		// Colours: a failed hex parse falls back rather than storing an empty value.
		foreach ( array( 'custom_color', 'custom_text_color' ) as $key ) {
			$value       = isset( $input[ $key ] ) ? sanitize_hex_color( $input[ $key ] ) : '';
			$out[ $key ] = $value ? $value : $defaults[ $key ];
		}

		// Plain text fields.
		$texts = array(
			'exclude_ids',
			'include_ids',
			'inline_heading',
			'popup_label',
			'cta_text',
			'utm_source',
			'utm_medium',
			'utm_campaign',
			'utm_content',
			'utm_term',
			'fb_app_id',
			'x_via',
			'x_hashtags',
			'copy_confirmation',
		);

		foreach ( $texts as $key ) {
			$out[ $key ] = isset( $input[ $key ] ) ? sanitize_text_field( $input[ $key ] ) : '';
		}

		$out['ai_prompt']        = isset( $input['ai_prompt'] ) ? sanitize_textarea_field( $input['ai_prompt'] ) : $defaults['ai_prompt'];
		$out['og_default_image'] = isset( $input['og_default_image'] ) ? esc_url_raw( $input['og_default_image'] ) : '';

		// An empty UTM source would put a bare utm_source= on every shared link.
		if ( '' === $out['utm_source'] ) {
			$out['utm_source'] = $defaults['utm_source'];
		}

		/**
		 * Filters settings after sanitisation and before they are stored.
		 *
		 * @param array $out   Clean settings.
		 * @param array $input Raw submission.
		 */
		return apply_filters( 'ushb_sanitize_settings', $out, $input );
	}

	/**
	 * Cleans a submission that covers only part of the settings.
	 *
	 * The settings screen is split across tabs, so a submission carries only the
	 * fields on the tab being saved. Running the full sanitiser over that would
	 * reset everything else to its default, which for a checkbox means off and
	 * for a network list means empty. Only the keys the form declared are taken
	 * from the submission; the rest keep their stored values.
	 *
	 * @param array $input    Raw submitted values, already unslashed.
	 * @param array $declared Setting keys the submitted form was responsible for.
	 * @return array Complete settings, safe to store.
	 */
	public static function sanitize_partial( $input, $declared ) {
		$clean = self::sanitize( $input );

		// No declaration means a full submission, such as an import.
		if ( empty( $declared ) || ! is_array( $declared ) ) {
			return $clean;
		}

		$current = self::all();

		// Intersecting against $clean means an unknown key cannot be injected.
		$updates = array_intersect_key( $clean, array_flip( $declared ) );

		return array_merge( $current, $updates );
	}

	/**
	 * Parses a comma or space separated list of post IDs.
	 *
	 * @param string $raw Raw field value.
	 * @return array Post IDs.
	 */
	public static function parse_ids( $raw ) {
		if ( empty( $raw ) ) {
			return array();
		}

		$parts = preg_split( '/[\s,]+/', (string) $raw, -1, PREG_SPLIT_NO_EMPTY );

		if ( ! is_array( $parts ) ) {
			return array();
		}

		return array_values( array_filter( array_map( 'absint', $parts ) ) );
	}
}
