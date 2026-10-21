<?php
/**
 * Turns settings plus a post into share bar markup.
 *
 * Brand colours live in the stylesheet as per-network classes and the settings
 * driven values are added with wp_add_inline_style(), so buttons carry no
 * inline style attributes.
 *
 * @package Unstoppable_Share_Buttons
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Builds share URLs and the markup around them.
 */
class USHB_Render {

	/**
	 * Incrementing counter so multiple bars on a page get unique instance IDs.
	 *
	 * @var int
	 */
	private static $instance = 0;

	/**
	 * Extra CSS collected from per-instance overrides.
	 *
	 * @var string
	 */
	private static $instance_css = '';

	/**
	 * Returns any CSS produced by per-instance overrides.
	 *
	 * @return string CSS.
	 */
	public static function get_instance_css() {
		return self::$instance_css;
	}

	/**
	 * Collects the shareable facts about a post.
	 *
	 * @param int $post_id Post ID. Falls back to the current post.
	 * @return array Context values used to fill URL placeholders.
	 */
	public static function context( $post_id = 0 ) {
		$post_id = $post_id ? absint( $post_id ) : (int) get_the_ID();
		$post    = $post_id ? get_post( $post_id ) : null;

		if ( $post ) {
			$url     = get_permalink( $post );
			$title   = wp_strip_all_tags( get_the_title( $post ) );
			$excerpt = wp_strip_all_tags( get_the_excerpt( $post ) );
			$thumb   = get_post_thumbnail_id( $post );
			$image   = $thumb ? wp_get_attachment_image_url( $thumb, 'large' ) : '';
		} else {
			$url     = home_url( '/' );
			$title   = wp_get_document_title();
			$excerpt = get_bloginfo( 'description' );
			$image   = '';
		}

		if ( ! $image ) {
			$image = USHB_Settings::get( 'og_default_image', '' );
		}

		$context = array(
			'post_id'  => $post_id,
			'url'      => $url ? $url : home_url( '/' ),
			'title'    => $title,
			'excerpt'  => $excerpt ? wp_trim_words( $excerpt, 40, '' ) : '',
			'image'    => $image,
			'sitename' => wp_strip_all_tags( get_bloginfo( 'name' ) ),
		);

		/**
		 * Filters the sharing context for a post.
		 *
		 * @param array $context Context values.
		 * @param int   $post_id Post ID.
		 */
		return apply_filters( 'ushb_context', $context, $post_id );
	}

	/**
	 * Appends the configured UTM parameters to a URL.
	 *
	 * @param string $url     URL being shared.
	 * @param string $network Network slug, available as {network}.
	 * @param array  $context Sharing context.
	 * @return string URL with campaign parameters.
	 */
	public static function add_utm( $url, $network, $context ) {
		if ( ! USHB_Settings::get( 'utm_enabled' ) || empty( $url ) ) {
			return $url;
		}

		$replace = array(
			'{network}'  => $network,
			'{sitename}' => sanitize_title( $context['sitename'] ),
			'{title}'    => sanitize_title( $context['title'] ),
			'{post_id}'  => (string) $context['post_id'],
		);

		$params = array();

		foreach ( array( 'source', 'medium', 'campaign', 'content', 'term' ) as $part ) {
			$value = (string) USHB_Settings::get( 'utm_' . $part, '' );

			if ( '' === $value ) {
				continue;
			}

			$params[ 'utm_' . $part ] = rawurlencode( strtr( $value, $replace ) );
		}

		if ( empty( $params ) ) {
			return $url;
		}

		return add_query_arg( $params, $url );
	}

	/**
	 * Builds the outbound share URL for one network.
	 *
	 * @param string $slug    Network slug.
	 * @param array  $context Sharing context.
	 * @return string Share endpoint, or an empty string for device-only actions.
	 */
	public static function share_url( $slug, $context ) {
		$network = USHB_Networks::get( $slug );

		if ( ! $network || empty( $network['url'] ) ) {
			return '';
		}

		$share_url = self::add_utm( $context['url'], $slug, $context );

		$via = trim( (string) USHB_Settings::get( 'x_via', '' ), '@ ' );

		// Strip every hash and space, not just the outer ones, or a list written
		// as "#WordPress, #design" would post the inner hash literally.
		$hashtags = str_replace( array( '#', ' ' ), '', (string) USHB_Settings::get( 'x_hashtags', '' ) );
		$hashtags = trim( $hashtags, ',' );

		$prompt = strtr(
			(string) USHB_Settings::get( 'ai_prompt', '' ),
			array(
				'{url}'      => $share_url,
				'{title}'    => $context['title'],
				'{sitename}' => $context['sitename'],
			)
		);

		$map = array(
			'{url}'        => rawurlencode( $share_url ),
			'{title}'      => rawurlencode( $context['title'] ),
			'{excerpt}'    => rawurlencode( $context['excerpt'] ),
			'{image}'      => rawurlencode( $context['image'] ),
			'{sitename}'   => rawurlencode( $context['sitename'] ),
			'{prompt}'     => rawurlencode( $prompt ),
			'{fb_app_id}'  => rawurlencode( (string) USHB_Settings::get( 'fb_app_id', '' ) ),
			'{via_param}'  => $via ? '&via=' . rawurlencode( $via ) : '',
			'{tags_param}' => $hashtags ? '&hashtags=' . rawurlencode( $hashtags ) : '',
		);

		$url = strtr( $network['url'], $map );

		/**
		 * Filters a finished share URL.
		 *
		 * @param string $url     Share endpoint.
		 * @param string $slug    Network slug.
		 * @param array  $context Sharing context.
		 */
		return apply_filters( 'ushb_share_url', $url, $slug, $context );
	}

	/**
	 * Resolves bar arguments against the saved settings.
	 *
	 * @param array $args Caller overrides.
	 * @return array Complete argument set.
	 */
	private static function parse_args( $args ) {
		$settings = USHB_Settings::all();

		$defaults = array(
			'placement'    => 'inline',
			'post_id'      => 0,
			'networks'     => array(),
			'shape'        => $settings['shape'],
			'style'        => $settings['style'],
			'size'         => $settings['size'],
			'gap'          => $settings['gap'],
			'radius'       => $settings['radius'],
			'palette'      => $settings['palette'],
			'color'        => $settings['custom_color'],
			'text_color'   => $settings['custom_text_color'],
			'labels'       => $settings['labels'],
			'hover'        => $settings['hover_effect'],
			'align'        => $settings['inline_align'],
			'full_width'   => $settings['full_width'],
			'heading'      => '',
			'cta_text'     => $settings['cta_text'],
			'cta_position' => $settings['cta_position'],
			'show_counts'  => $settings['show_counts'],
			'scheme'       => $settings['color_scheme'],
			'extra_class'  => '',
		);

		$args = wp_parse_args( $args, $defaults );

		if ( empty( $args['networks'] ) ) {
			$key              = $args['placement'] . '_networks';
			$args['networks'] = isset( $settings[ $key ] ) ? $settings[ $key ] : $settings['inline_networks'];
		}

		$args['networks'] = USHB_Networks::filter_valid( $args['networks'] );

		return $args;
	}

	/**
	 * Records per-instance CSS when a bar differs from the saved design.
	 *
	 * @param int   $instance Instance number.
	 * @param array $args     Parsed arguments.
	 */
	private static function collect_instance_css( $instance, $args ) {
		$settings = USHB_Settings::all();
		$rules    = array();

		if ( (int) $args['size'] !== (int) $settings['size'] ) {
			$rules[] = '--ushb-size:' . absint( $args['size'] ) . 'px';
		}

		if ( (int) $args['gap'] !== (int) $settings['gap'] ) {
			$rules[] = '--ushb-gap:' . absint( $args['gap'] ) . 'px';
		}

		if ( (int) $args['radius'] !== (int) $settings['radius'] ) {
			$rules[] = '--ushb-radius:' . absint( $args['radius'] ) . 'px';
		}

		if ( 'custom' === $args['palette'] ) {
			$color = sanitize_hex_color( $args['color'] );
			$text  = sanitize_hex_color( $args['text_color'] );

			if ( $color && $color !== $settings['custom_color'] ) {
				$rules[] = '--ushb-brand:' . $color;
			}

			if ( $text && $text !== $settings['custom_text_color'] ) {
				$rules[] = '--ushb-on-brand:' . $text;
			}
		}

		if ( empty( $rules ) ) {
			return;
		}

		self::$instance_css .= '.ushb-i' . absint( $instance ) . '{' . implode( ';', $rules ) . '}';
	}

	/**
	 * Escapes and flattens an attribute map.
	 *
	 * @param array $attrs Attribute name mapped to value.
	 * @return string Attribute string.
	 */
	private static function attrs( $attrs ) {
		$out = array();

		foreach ( $attrs as $name => $value ) {
			if ( '' === $value || null === $value ) {
				continue;
			}

			$out[] = esc_attr( $name ) . '="' . esc_attr( $value ) . '"';
		}

		return implode( ' ', $out );
	}

	/**
	 * The accessible label for a button.
	 *
	 * @param string $slug    Network slug.
	 * @param array  $network Network definition.
	 * @return string Label text.
	 */
	private static function aria_label( $slug, $network ) {
		$action = isset( $network['action'] ) ? $network['action'] : '';

		switch ( $action ) {
			case 'copy':
				return __( 'Copy link to clipboard', 'unstoppable-share-buttons' );
			case 'print':
				return __( 'Print this page', 'unstoppable-share-buttons' );
			case 'qr':
				return __( 'Show a QR code for this page', 'unstoppable-share-buttons' );
			case 'bookmark':
				return __( 'Bookmark this page', 'unstoppable-share-buttons' );
			case 'native':
				return __( 'Open the device share sheet', 'unstoppable-share-buttons' );
		}

		/* translators: %s: name of a social network, for example Facebook. */
		return sprintf( __( 'Share on %s', 'unstoppable-share-buttons' ), $network['label'] );
	}

	/**
	 * Renders a single share button.
	 *
	 * @param string $slug    Network slug.
	 * @param array  $context Sharing context.
	 * @param array  $args    Parsed bar arguments.
	 * @param array  $counts  Click counts keyed by network.
	 * @return string Button markup.
	 */
	public static function button( $slug, $context, $args, $counts = array() ) {
		$network = USHB_Networks::get( $slug );

		if ( ! $network ) {
			return '';
		}

		$classes = array( 'ushb-btn', 'ushb-btn--' . $slug );

		if ( ! empty( $network['action'] ) ) {
			$classes[] = 'ushb-btn--action';
		}

		if ( 'native' === $slug ) {
			$classes[] = 'ushb-btn--needs-native';
		}

		$attrs = array(
			'class'        => implode( ' ', $classes ),
			'data-network' => $slug,
			'data-post'    => (string) $context['post_id'],
			'data-place'   => $args['placement'],
			'aria-label'   => self::aria_label( $slug, $network ),
		);

		$icon = USHB_Icons::svg( isset( $network['icon'] ) ? $network['icon'] : 'more' );

		$inner = '<span class="ushb-btn__icon">' . wp_kses( $icon, USHB_Icons::allowed_svg_tags() ) . '</span>';

		if ( 'icon' !== $args['labels'] ) {
			$inner .= '<span class="ushb-btn__label">' . esc_html( $network['label'] ) . '</span>';
		}

		if ( ! empty( $args['show_counts'] ) && ! empty( $counts[ $slug ] ) ) {
			$inner .= '<span class="ushb-btn__count">' . esc_html( USHB_Stats::format_number( $counts[ $slug ] ) ) . '</span>';
		}

		// Device actions are buttons, not links, because they go nowhere.
		if ( ! empty( $network['action'] ) ) {
			$attrs['type']        = 'button';
			$attrs['data-action'] = $network['action'];
			$attrs['data-url']    = self::add_utm( $context['url'], $slug, $context );
			$attrs['data-title']  = $context['title'];

			return '<button ' . self::attrs( $attrs ) . '>' . $inner . '</button>';
		}

		$url = self::share_url( $slug, $context );

		if ( ! $url ) {
			return '';
		}

		$attrs['href'] = $url;
		$attrs['rel']  = 'noopener noreferrer nofollow';

		if ( ! empty( $network['popup'] ) ) {
			$attrs['target']     = '_blank';
			$attrs['data-popup'] = '1';
		}

		return '<a ' . self::attrs( $attrs ) . '>' . $inner . '</a>';
	}

	/**
	 * Renders a complete share bar.
	 *
	 * @param array $args {
	 *     Optional arguments.
	 *
	 *     @type string $placement   inline|floating|sticky|popup|woo|shortcode.
	 *     @type int    $post_id     Post to share. Defaults to the current post.
	 *     @type array  $networks    Network slugs. Defaults to the placement setting.
	 *     @type string $shape       square|rounded|pill|circle|ribbon|drop.
	 *     @type string $style       filled|outline|minimal|dashed|glass|gradient.
	 *     @type string $labels      icon|label|both.
	 *     @type string $align       left|center|right.
	 *     @type string $heading     Optional heading above the buttons.
	 * }
	 * @return string Bar markup.
	 */
	public static function bar( $args = array() ) {
		$args = self::parse_args( $args );

		if ( empty( $args['networks'] ) ) {
			return '';
		}

		$context = self::context( $args['post_id'] );
		$counts  = ! empty( $args['show_counts'] ) ? USHB_Stats::counts_for_post( $context['post_id'] ) : array();

		++self::$instance;
		self::collect_instance_css( self::$instance, $args );

		$buttons = '';

		foreach ( $args['networks'] as $slug ) {
			$buttons .= self::button( $slug, $context, $args, $counts );
		}

		if ( '' === $buttons ) {
			return '';
		}

		$classes = array(
			'ushb',
			'ushb-i' . self::$instance,
			'ushb--' . sanitize_html_class( $args['placement'] ),
			'ushb--shape-' . sanitize_html_class( $args['shape'] ),
			'ushb--style-' . sanitize_html_class( $args['style'] ),
			'ushb--labels-' . sanitize_html_class( $args['labels'] ),
			'ushb--hover-' . sanitize_html_class( $args['hover'] ),
			'ushb--align-' . sanitize_html_class( $args['align'] ),
			'ushb--palette-' . sanitize_html_class( $args['palette'] ),
			'ushb--scheme-' . sanitize_html_class( $args['scheme'] ),
		);

		if ( ! empty( $args['full_width'] ) ) {
			$classes[] = 'ushb--full';
		}

		if ( ! empty( $args['extra_class'] ) ) {
			$classes[] = sanitize_html_class( $args['extra_class'] );
		}

		if ( 'floating' === $args['placement'] ) {
			$classes[] = 'ushb--side-' . sanitize_html_class( USHB_Settings::get( 'floating_side' ) );
		}

		$html = '<div class="' . esc_attr( implode( ' ', $classes ) ) . '">';

		if ( ! empty( $args['heading'] ) ) {
			$html .= '<p class="ushb__heading">' . esc_html( $args['heading'] ) . '</p>';
		}

		if ( ! empty( $args['cta_text'] ) && 'above' === $args['cta_position'] ) {
			$html .= '<p class="ushb__cta">' . esc_html( $args['cta_text'] ) . '</p>';
		}

		$total     = isset( $counts['_total'] ) ? (int) $counts['_total'] : 0;
		$threshold = (int) USHB_Settings::get( 'count_threshold', 0 );

		if ( ! empty( $args['show_counts'] ) && $total >= $threshold && $total > 0 ) {
			$html .= '<span class="ushb__total"><strong>' . esc_html( USHB_Stats::format_number( $total ) ) . '</strong>'
				. '<span>' . esc_html__( 'shares', 'unstoppable-share-buttons' ) . '</span></span>';
		}

		$html .= '<div class="ushb__buttons" role="group" aria-label="' . esc_attr__( 'Share this', 'unstoppable-share-buttons' ) . '">' . $buttons . '</div>';

		if ( ! empty( $args['cta_text'] ) && 'below' === $args['cta_position'] ) {
			$html .= '<p class="ushb__cta">' . esc_html( $args['cta_text'] ) . '</p>';
		}

		$html .= '</div>';

		/**
		 * Filters finished share bar markup.
		 *
		 * @param string $html    Bar markup.
		 * @param array  $args    Parsed arguments.
		 * @param array  $context Sharing context.
		 */
		return apply_filters( 'ushb_bar_html', $html, $args, $context );
	}

	/**
	 * Renders the universal share button and the dialog it opens.
	 *
	 * @param array $args Optional overrides.
	 * @return string Markup.
	 */
	public static function popup( $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'post_id'  => 0,
				'label'    => USHB_Settings::get( 'popup_label' ),
				'networks' => USHB_Settings::get( 'popup_networks' ),
			)
		);

		$networks = USHB_Networks::filter_valid( $args['networks'] );

		if ( empty( $networks ) ) {
			return '';
		}

		$label = $args['label'] ? $args['label'] : __( 'Share', 'unstoppable-share-buttons' );

		++self::$instance;
		$dialog_id = 'ushb-dialog-' . self::$instance;

		$bar = self::bar(
			array(
				'placement' => 'popup',
				'post_id'   => $args['post_id'],
				'networks'  => $networks,
				'labels'    => 'both',
				'align'     => 'left',
			)
		);

		$icon = wp_kses( USHB_Icons::svg( 'native' ), USHB_Icons::allowed_svg_tags() );

		$html  = '<div class="ushb-popup">';
		$html .= '<button type="button" class="ushb-popup__trigger" data-dialog="' . esc_attr( $dialog_id ) . '" aria-haspopup="dialog">';
		$html .= '<span class="ushb-btn__icon">' . $icon . '</span>';
		$html .= '<span>' . esc_html( $label ) . '</span>';
		$html .= '</button>';
		$html .= '<div class="ushb-modal" id="' . esc_attr( $dialog_id ) . '" role="dialog" aria-modal="true" aria-label="' . esc_attr__( 'Share this page', 'unstoppable-share-buttons' ) . '" hidden>';
		$html .= '<div class="ushb-modal__backdrop" data-close></div>';
		$html .= '<div class="ushb-modal__panel">';
		$html .= '<div class="ushb-modal__head"><h2>' . esc_html__( 'Share this page', 'unstoppable-share-buttons' ) . '</h2>';
		$html .= '<button type="button" class="ushb-modal__close" data-close aria-label="' . esc_attr__( 'Close', 'unstoppable-share-buttons' ) . '">&times;</button></div>';
		$html .= $bar;
		$html .= '</div></div></div>';

		return $html;
	}
}
