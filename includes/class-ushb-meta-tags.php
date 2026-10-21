<?php
/**
 * Open Graph and X card tags.
 *
 * These are off by default and stand down automatically when an SEO plugin is
 * already writing them, because duplicate tags confuse the scrapers they exist
 * for.
 *
 * @package Unstoppable_Share_Buttons
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Emits sharing preview metadata.
 */
class USHB_Meta_Tags {

	/**
	 * Hooks the head output.
	 */
	public static function init() {
		add_action( 'wp_head', array( __CLASS__, 'output' ), 5 );
	}

	/**
	 * Detects an SEO plugin that already handles Open Graph tags.
	 *
	 * @return string Plugin name, or an empty string when none is active.
	 */
	public static function conflicting_plugin() {
		if ( defined( 'WPSEO_VERSION' ) ) {
			return 'Yoast SEO';
		}

		if ( class_exists( 'RankMath' ) ) {
			return 'Rank Math';
		}

		if ( function_exists( 'aioseo' ) ) {
			return 'All in One SEO';
		}

		if ( defined( 'SEOPRESS_VERSION' ) ) {
			return 'SEOPress';
		}

		if ( defined( 'SLIM_SEO_VER' ) ) {
			return 'Slim SEO';
		}

		return '';
	}

	/**
	 * Prints the tags.
	 */
	public static function output() {
		if ( ! USHB_Settings::get( 'og_enabled' ) || self::conflicting_plugin() ) {
			return;
		}

		if ( ! is_singular() ) {
			return;
		}

		$context = USHB_Render::context();
		$card    = USHB_Settings::get( 'twitter_card', 'summary_large_image' );
		$via     = trim( (string) USHB_Settings::get( 'x_via', '' ), '@ ' );

		$tags = array(
			array( 'property', 'og:type', 'article' ),
			array( 'property', 'og:site_name', $context['sitename'] ),
			array( 'property', 'og:title', $context['title'] ),
			array( 'property', 'og:url', $context['url'] ),
			array( 'name', 'twitter:card', $card ),
			array( 'name', 'twitter:title', $context['title'] ),
		);

		if ( $context['excerpt'] ) {
			$tags[] = array( 'property', 'og:description', $context['excerpt'] );
			$tags[] = array( 'name', 'twitter:description', $context['excerpt'] );
		}

		if ( $context['image'] ) {
			$tags[] = array( 'property', 'og:image', $context['image'] );
			$tags[] = array( 'name', 'twitter:image', $context['image'] );
		}

		if ( $via ) {
			$tags[] = array( 'name', 'twitter:site', '@' . $via );
		}

		$app_id = USHB_Settings::get( 'fb_app_id' );

		if ( $app_id ) {
			$tags[] = array( 'property', 'fb:app_id', $app_id );
		}

		$published = get_the_date( DATE_W3C );
		$modified  = get_the_modified_date( DATE_W3C );

		if ( $published ) {
			$tags[] = array( 'property', 'article:published_time', $published );
		}

		if ( $modified ) {
			$tags[] = array( 'property', 'article:modified_time', $modified );
		}

		/**
		 * Filters the meta tags before they are printed.
		 *
		 * @param array $tags    Each entry is attribute, name, content.
		 * @param array $context Sharing context.
		 */
		$tags = apply_filters( 'ushb_meta_tags', $tags, $context );

		foreach ( $tags as $tag ) {
			if ( ! is_array( $tag ) || count( $tag ) < 3 || '' === $tag[2] ) {
				continue;
			}

			printf(
				'<meta %1$s="%2$s" content="%3$s" />' . "\n",
				esc_attr( $tag[0] ),
				esc_attr( $tag[1] ),
				esc_attr( $tag[2] )
			);
		}
	}
}
