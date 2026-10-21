<?php
/**
 * Shortcodes.
 *
 * @package Unstoppable_Share_Buttons
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers every shortcode the plugin provides.
 */
class USHB_Shortcodes {

	/**
	 * Hooks the shortcodes.
	 */
	public static function init() {
		add_shortcode( 'unstoppable_share', array( __CLASS__, 'bar' ) );
		add_shortcode( 'unstoppable_share_button', array( __CLASS__, 'popup' ) );
		add_shortcode( 'unstoppable_share_quote', array( __CLASS__, 'quote' ) );
		add_shortcode( 'unstoppable_share_ai', array( __CLASS__, 'ai' ) );
		add_shortcode( 'unstoppable_share_count', array( __CLASS__, 'count' ) );
	}

	/**
	 * Renders a share bar.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string Markup.
	 */
	public static function bar( $atts ) {
		$atts = shortcode_atts(
			array(
				'networks' => '',
				'post_id'  => 0,
				'shape'    => USHB_Settings::get( 'shape' ),
				'style'    => USHB_Settings::get( 'style' ),
				'labels'   => USHB_Settings::get( 'labels' ),
				'align'    => USHB_Settings::get( 'inline_align' ),
				'size'     => USHB_Settings::get( 'size' ),
				'palette'  => USHB_Settings::get( 'palette' ),
				'color'    => USHB_Settings::get( 'custom_color' ),
				'counts'   => USHB_Settings::get( 'show_counts' ),
				'heading'  => '',
				'cta'      => '',
			),
			$atts,
			'unstoppable_share'
		);

		USHB_Frontend::enqueue();

		$color = sanitize_hex_color( $atts['color'] );

		return USHB_Render::bar(
			array(
				'placement'   => 'shortcode',
				'post_id'     => absint( $atts['post_id'] ),
				'networks'    => $atts['networks'] ? $atts['networks'] : USHB_Settings::get( 'inline_networks' ),
				'shape'       => sanitize_key( $atts['shape'] ),
				'style'       => sanitize_key( $atts['style'] ),
				'labels'      => sanitize_key( $atts['labels'] ),
				'align'       => sanitize_key( $atts['align'] ),
				'size'        => absint( $atts['size'] ),
				'palette'     => sanitize_key( $atts['palette'] ),
				'color'       => $color ? $color : USHB_Settings::get( 'custom_color' ),
				'show_counts' => filter_var( $atts['counts'], FILTER_VALIDATE_BOOLEAN ),
				'heading'     => sanitize_text_field( $atts['heading'] ),
				'cta_text'    => sanitize_text_field( $atts['cta'] ),
			)
		);
	}

	/**
	 * Renders the universal share button.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string Markup.
	 */
	public static function popup( $atts ) {
		$atts = shortcode_atts(
			array(
				'label'    => USHB_Settings::get( 'popup_label' ),
				'networks' => '',
				'post_id'  => 0,
			),
			$atts,
			'unstoppable_share_button'
		);

		USHB_Frontend::enqueue();

		$networks = $atts['networks'] ? $atts['networks'] : USHB_Settings::get( 'popup_networks' );

		if ( empty( $networks ) ) {
			$networks = USHB_Settings::get( 'inline_networks' );
		}

		return USHB_Render::popup(
			array(
				'post_id'  => absint( $atts['post_id'] ),
				'label'    => sanitize_text_field( $atts['label'] ),
				'networks' => $networks,
			)
		);
	}

	/**
	 * Renders a pull quote that shares itself.
	 *
	 * @param array  $atts    Shortcode attributes.
	 * @param string $content Quote text when wrapped.
	 * @return string Markup.
	 */
	public static function quote( $atts, $content = '' ) {
		$atts = shortcode_atts(
			array(
				'text'    => '',
				'network' => 'x',
				'cta'     => '',
				'theme'   => 'light',
				'accent'  => '',
			),
			$atts,
			'unstoppable_share_quote'
		);

		$text = $atts['text'] ? $atts['text'] : wp_strip_all_tags( $content );
		$text = trim( sanitize_text_field( $text ) );

		if ( '' === $text ) {
			return '';
		}

		$slug    = sanitize_key( $atts['network'] );
		$network = USHB_Networks::get( $slug );

		if ( ! $network || ! empty( $network['action'] ) ) {
			$slug    = 'x';
			$network = USHB_Networks::get( 'x' );
		}

		$context          = USHB_Render::context();
		$context['title'] = $text;

		$url = USHB_Render::share_url( $slug, $context );

		if ( ! $url ) {
			return '';
		}

		$cta   = $atts['cta'] ? sanitize_text_field( $atts['cta'] ) : __( 'Share this', 'unstoppable-share-buttons' );
		$theme = in_array( $atts['theme'], array( 'light', 'dark', 'plain' ), true ) ? $atts['theme'] : 'light';
		$icon  = wp_kses( USHB_Icons::svg( isset( $network['icon'] ) ? $network['icon'] : 'more' ), USHB_Icons::allowed_svg_tags() );

		USHB_Frontend::enqueue();

		$html  = '<figure class="ushb-quote ushb-quote--' . esc_attr( $theme ) . ' ushb-quote--' . esc_attr( $slug ) . '">';
		$html .= '<blockquote class="ushb-quote__text">' . esc_html( $text ) . '</blockquote>';
		$html .= '<a class="ushb-quote__cta" href="' . esc_url( $url ) . '" target="_blank" rel="noopener noreferrer nofollow"'
			. ' data-network="' . esc_attr( $slug ) . '" data-post="' . absint( $context['post_id'] ) . '"'
			. ' data-place="quote" data-popup="1">'
			. '<span class="ushb-btn__icon">' . $icon . '</span>'
			. '<span>' . esc_html( $cta ) . '</span></a>';
		$html .= '</figure>';

		return $html;
	}

	/**
	 * Renders the ask an AI row.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string Markup.
	 */
	public static function ai( $atts ) {
		$atts = shortcode_atts(
			array(
				'networks' => 'chatgpt,claude,gemini,perplexity,grok,google',
				'labels'   => 'both',
				'heading'  => '',
				'post_id'  => 0,
			),
			$atts,
			'unstoppable_share_ai'
		);

		USHB_Frontend::enqueue();

		return USHB_Render::bar(
			array(
				'placement' => 'ai',
				'post_id'   => absint( $atts['post_id'] ),
				'networks'  => $atts['networks'],
				'labels'    => sanitize_key( $atts['labels'] ),
				'heading'   => sanitize_text_field( $atts['heading'] ),
			)
		);
	}

	/**
	 * Prints the recorded share total for a post.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string Formatted number.
	 */
	public static function count( $atts ) {
		$atts = shortcode_atts(
			array(
				'post_id' => 0,
				'format'  => 'short',
			),
			$atts,
			'unstoppable_share_count'
		);

		$post_id = $atts['post_id'] ? absint( $atts['post_id'] ) : (int) get_the_ID();
		$counts  = USHB_Stats::counts_for_post( $post_id );
		$total   = isset( $counts['_total'] ) ? (int) $counts['_total'] : 0;

		if ( 'short' === $atts['format'] ) {
			return esc_html( USHB_Stats::format_number( $total ) );
		}

		return esc_html( number_format_i18n( $total ) );
	}
}
