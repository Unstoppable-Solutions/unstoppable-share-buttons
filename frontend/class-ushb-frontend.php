<?php
/**
 * Decides where bars appear on the public site and loads their assets.
 *
 * @package Unstoppable_Share_Buttons
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Public side wiring.
 */
class USHB_Frontend {

	/**
	 * Tracks posts already processed, so the_content filters cannot double up.
	 *
	 * @var array
	 */
	private static $done = array();

	/**
	 * Hooks the public side.
	 */
	public static function init() {
		if ( is_admin() ) {
			return;
		}

		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'register_assets' ) );
		add_filter( 'the_content', array( __CLASS__, 'inject' ), 20 );
		add_action( 'wp_footer', array( __CLASS__, 'footer_bars' ), 20 );
	}

	/**
	 * Registers, and where appropriate enqueues, the front end assets.
	 *
	 * Everything is bundled locally. No CDN, no remote script, no bundled copy
	 * of a library WordPress already ships.
	 */
	public static function register_assets() {
		wp_register_style(
			'ushb-frontend',
			USHB_PLUGIN_URL . 'assets/css/frontend.css',
			array(),
			USHB_VERSION
		);

		wp_register_script(
			'ushb-frontend',
			USHB_PLUGIN_URL . 'assets/js/frontend.js',
			array(),
			USHB_VERSION,
			true
		);

		wp_localize_script( 'ushb-frontend', 'ushbData', self::script_data() );

		if ( self::should_display() ) {
			self::enqueue();
		}
	}

	/**
	 * Enqueues the assets and attaches the settings driven CSS.
	 */
	public static function enqueue() {
		if ( wp_style_is( 'ushb-frontend', 'enqueued' ) ) {
			return;
		}

		wp_enqueue_style( 'ushb-frontend' );
		wp_enqueue_script( 'ushb-frontend' );

		$css = self::dynamic_css();

		if ( '' !== $css ) {
			wp_add_inline_style( 'ushb-frontend', $css );
		}
	}

	/**
	 * Builds the CSS that comes from the design settings.
	 *
	 * @return string CSS.
	 */
	private static function dynamic_css() {
		$settings = USHB_Settings::all();

		$rules = array(
			'--ushb-size:' . absint( $settings['size'] ) . 'px',
			'--ushb-gap:' . absint( $settings['gap'] ) . 'px',
			'--ushb-radius:' . absint( $settings['radius'] ) . 'px',
		);

		$css = '.ushb{' . implode( ';', $rules ) . '}';

		if ( 'custom' === $settings['palette'] ) {
			$color = sanitize_hex_color( $settings['custom_color'] );
			$text  = sanitize_hex_color( $settings['custom_text_color'] );

			if ( $color ) {
				$css .= '.ushb--palette-custom .ushb-btn{--ushb-brand:' . $color . ';';
				$css .= '--ushb-on-brand:' . ( $text ? $text : '#ffffff' ) . '}';
			}
		}

		$css .= '.ushb--floating{--ushb-offset:' . absint( $settings['floating_offset'] ) . '%}';

		// Per-instance overrides collected while rendering shortcodes and blocks.
		$css .= USHB_Render::get_instance_css();

		return $css;
	}

	/**
	 * Values handed to the front end script.
	 *
	 * @return array Script data.
	 */
	public static function script_data() {
		$confirmation = USHB_Settings::get( 'copy_confirmation' );

		return array(
			'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
			'nonce'     => wp_create_nonce( USHB_Ajax::TRACK_NONCE ),
			'track'     => USHB_Settings::get( 'track_clicks' ) ? 1 : 0,
			'ga4'       => USHB_Settings::get( 'ga4_enabled' ) ? 1 : 0,
			'pixel'     => USHB_Settings::get( 'meta_pixel_enabled' ) ? 1 : 0,
			'dataLayer' => USHB_Settings::get( 'datalayer_enabled' ) ? 1 : 0,
			'floatMin'  => absint( USHB_Settings::get( 'floating_min_width' ) ),
			'stickyMax' => absint( USHB_Settings::get( 'sticky_max_width' ) ),
			'i18n'      => array(
				'copied'         => $confirmation ? $confirmation : __( 'Link copied', 'unstoppable-share-buttons' ),
				'copyFailed'     => __( 'Could not copy automatically. Press Ctrl+C or Cmd+C to copy the link.', 'unstoppable-share-buttons' ),
				'mastodonPrompt' => __( 'Which Mastodon server are you on?', 'unstoppable-share-buttons' ),
				'bookmarkHint'   => __( 'Press Ctrl+D, or Cmd+D on a Mac, to bookmark this page.', 'unstoppable-share-buttons' ),
				'qrTitle'        => __( 'Scan to open this page', 'unstoppable-share-buttons' ),
				'qrUnavailable'  => __( 'A QR code could not be generated for this address.', 'unstoppable-share-buttons' ),
				'close'          => __( 'Close', 'unstoppable-share-buttons' ),
			),
		);
	}

	/**
	 * Decides whether the current view should get share bars.
	 *
	 * @param int $post_id Optional post ID. Defaults to the current post.
	 * @return bool True when bars are allowed here.
	 */
	public static function should_display( $post_id = 0 ) {
		$post_id = $post_id ? absint( $post_id ) : (int) get_the_ID();
		$allow   = true;

		if ( is_feed() || is_embed() || is_404() || is_search() ) {
			$allow = false;
		} elseif ( is_front_page() && ! USHB_Settings::get( 'show_on_front' ) ) {
			$allow = false;
		} elseif ( ( is_archive() || is_home() ) && ! is_front_page() && ! USHB_Settings::get( 'show_on_archives' ) ) {
			$allow = false;
		} elseif ( is_singular() ) {
			$types = (array) USHB_Settings::get( 'post_types', array() );

			if ( ! in_array( get_post_type( $post_id ), $types, true ) ) {
				$allow = false;
			}
		}

		if ( $allow && USHB_Settings::get( 'hide_for_logged_in' ) && is_user_logged_in() ) {
			$allow = false;
		}

		if ( $allow && $post_id ) {
			$exclude = USHB_Settings::parse_ids( USHB_Settings::get( 'exclude_ids' ) );

			if ( in_array( $post_id, $exclude, true ) ) {
				$allow = false;
			}
		}

		// An explicit include list overrides every rule above.
		if ( $post_id ) {
			$include = USHB_Settings::parse_ids( USHB_Settings::get( 'include_ids' ) );

			if ( in_array( $post_id, $include, true ) ) {
				$allow = true;
			}
		}

		/**
		 * Filters whether share bars may render on this view.
		 *
		 * @param bool $allow   Whether bars are allowed.
		 * @param int  $post_id Post ID under consideration.
		 */
		return (bool) apply_filters( 'ushb_should_display', $allow, $post_id );
	}

	/**
	 * Adds the inline bar to post content.
	 *
	 * @param string $content Post content.
	 * @return string Content, possibly with bars attached.
	 */
	public static function inject( $content ) {
		if ( ! USHB_Settings::get( 'inline_enabled' ) || is_admin() ) {
			return $content;
		}

		// Never let a bar leak into an excerpt.
		if ( function_exists( 'doing_filter' ) && doing_filter( 'get_the_excerpt' ) ) {
			return $content;
		}

		$post_id = (int) get_the_ID();

		if ( ! $post_id ) {
			return $content;
		}

		/*
		 * Two ways a post's content legitimately reaches this filter.
		 *
		 * Classic themes iterate the main loop, so in_the_loop() is true. Block
		 * themes render the content through the core/post-content block, which
		 * applies this filter without iterating the loop, so in_the_loop() is
		 * false there and checking it alone would hide the bar on every block
		 * theme. Comparing the post against the queried object covers that case
		 * without opening the filter up to widgets and secondary queries.
		 */
		$is_queried_post = is_singular() && (int) get_queried_object_id() === $post_id;
		$is_loop_item    = in_the_loop() && is_main_query();

		if ( ! $is_queried_post && ! $is_loop_item ) {
			return $content;
		}

		if ( isset( self::$done[ $post_id ] ) || ! self::should_display( $post_id ) ) {
			return $content;
		}

		self::$done[ $post_id ] = 1;

		$bar = USHB_Render::bar(
			array(
				'placement' => 'inline',
				'post_id'   => $post_id,
				'heading'   => USHB_Settings::get( 'inline_heading' ),
			)
		);

		if ( '' === $bar ) {
			return $content;
		}

		self::enqueue();

		switch ( USHB_Settings::get( 'inline_position' ) ) {
			case 'above':
				return $bar . $content;

			case 'both':
				return $bar . $content . $bar;

			case 'after_paragraph':
				return self::after_paragraph( $content, $bar, (int) USHB_Settings::get( 'inline_paragraph', 2 ) );

			case 'below':
			default:
				return $content . $bar;
		}
	}

	/**
	 * Inserts a bar after the given paragraph.
	 *
	 * @param string $content   Post content.
	 * @param string $bar       Bar markup.
	 * @param int    $paragraph Paragraph number to insert after.
	 * @return string Content with the bar inserted.
	 */
	private static function after_paragraph( $content, $bar, $paragraph ) {
		$paragraph = max( 1, $paragraph );
		$closing   = '</p>';
		$parts     = explode( $closing, $content );

		// Not enough paragraphs to reach the target, so fall back to the end.
		if ( count( $parts ) <= $paragraph ) {
			return $content . $bar;
		}

		$out   = '';
		$count = 0;

		foreach ( $parts as $index => $part ) {
			$out .= $part;

			// The final fragment has no closing tag to restore.
			if ( count( $parts ) - 1 === $index ) {
				continue;
			}

			$out .= $closing;
			++$count;

			if ( $paragraph === $count ) {
				$out .= $bar;
			}
		}

		return $out;
	}

	/**
	 * Prints the floating and sticky bars into the footer.
	 */
	public static function footer_bars() {
		if ( ! self::should_display() ) {
			return;
		}

		$post_id = (int) get_the_ID();
		$html    = '';

		if ( USHB_Settings::get( 'floating_enabled' ) ) {
			$html .= '<div class="ushb-floating-wrap">'
				. USHB_Render::bar(
					array(
						'placement' => 'floating',
						'post_id'   => $post_id,
						'labels'    => 'icon',
					)
				)
				. '</div>';
		}

		if ( USHB_Settings::get( 'sticky_enabled' ) ) {
			$html .= '<div class="ushb-sticky-wrap">'
				. USHB_Render::bar(
					array(
						'placement' => 'sticky',
						'post_id'   => $post_id,
						'labels'    => 'icon',
						'align'     => 'center',
					)
				)
				. '</div>';
		}

		if ( '' === $html ) {
			return;
		}

		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Markup is assembled from individually escaped parts.
	}
}
