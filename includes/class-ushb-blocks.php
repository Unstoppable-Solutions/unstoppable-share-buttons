<?php
/**
 * The editor block.
 *
 * @package Unstoppable_Share_Buttons
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers a server rendered share buttons block.
 */
class USHB_Blocks {

	/**
	 * Hooks block registration.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register' ) );
	}

	/**
	 * Registers the block type and its editor script.
	 */
	public static function register() {
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}

		wp_register_script(
			'ushb-block',
			USHB_PLUGIN_URL . 'assets/js/block.js',
			array( 'wp-blocks', 'wp-element', 'wp-components', 'wp-block-editor', 'wp-i18n', 'wp-server-side-render' ),
			USHB_VERSION,
			true
		);

		wp_localize_script(
			'ushb-block',
			'ushbBlockData',
			array(
				'networks' => self::network_options(),
				'defaults' => array(
					'networks' => implode( ',', (array) USHB_Settings::get( 'inline_networks' ) ),
					'shape'    => USHB_Settings::get( 'shape' ),
					'style'    => USHB_Settings::get( 'style' ),
					'labels'   => USHB_Settings::get( 'labels' ),
					'align'    => USHB_Settings::get( 'inline_align' ),
				),
			)
		);

		register_block_type(
			'unstoppable/share-buttons',
			array(
				'api_version'     => 2,
				'title'           => __( 'Unstoppable Share Buttons', 'unstoppable-share-buttons' ),
				'description'     => __( 'Share buttons for this post.', 'unstoppable-share-buttons' ),
				'category'        => 'widgets',
				'icon'            => 'share',
				'editor_script'   => 'ushb-block',
				'style'           => 'ushb-frontend',
				'render_callback' => array( __CLASS__, 'render' ),
				'attributes'      => array(
					'networks' => array(
						'type'    => 'string',
						'default' => '',
					),
					'shape'    => array(
						'type'    => 'string',
						'default' => '',
					),
					'style'    => array(
						'type'    => 'string',
						'default' => '',
					),
					'labels'   => array(
						'type'    => 'string',
						'default' => '',
					),
					'align'    => array(
						'type'    => 'string',
						'default' => '',
					),
					'heading'  => array(
						'type'    => 'string',
						'default' => '',
					),
					'counts'   => array(
						'type'    => 'boolean',
						'default' => false,
					),
				),
			)
		);
	}

	/**
	 * Network choices for the block sidebar.
	 *
	 * @return array Options list.
	 */
	private static function network_options() {
		$out = array();

		foreach ( USHB_Networks::all() as $slug => $network ) {
			$out[] = array(
				'value' => $slug,
				'label' => $network['label'],
			);
		}

		return $out;
	}

	/**
	 * Renders the block.
	 *
	 * @param array $attributes Block attributes.
	 * @return string Markup.
	 */
	public static function render( $attributes ) {
		$attributes = wp_parse_args(
			(array) $attributes,
			array(
				'networks' => '',
				'shape'    => '',
				'style'    => '',
				'labels'   => '',
				'align'    => '',
				'heading'  => '',
				'counts'   => false,
			)
		);

		USHB_Frontend::enqueue();

		$args = array(
			'placement'   => 'block',
			'networks'    => $attributes['networks'] ? $attributes['networks'] : USHB_Settings::get( 'inline_networks' ),
			'heading'     => sanitize_text_field( $attributes['heading'] ),
			'show_counts' => (bool) $attributes['counts'],
		);

		foreach ( array( 'shape', 'style', 'labels', 'align' ) as $key ) {
			if ( ! empty( $attributes[ $key ] ) ) {
				$args[ $key ] = sanitize_key( $attributes[ $key ] );
			}
		}

		$html = USHB_Render::bar( $args );

		if ( '' === $html ) {
			return '<p class="ushb-empty">' . esc_html__( 'Choose at least one network for these share buttons.', 'unstoppable-share-buttons' ) . '</p>';
		}

		return $html;
	}
}
