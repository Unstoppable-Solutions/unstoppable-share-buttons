<?php
/**
 * The Elementor widget.
 *
 * Only loaded after Elementor confirms its base class exists.
 *
 * @package Unstoppable_Share_Buttons
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\Elementor\Widget_Base' ) ) {
	return;
}

/**
 * Drag and drop share buttons for Elementor.
 */
class USHB_Elementor_Widget extends \Elementor\Widget_Base {

	/**
	 * Widget slug.
	 *
	 * @return string Widget name.
	 */
	public function get_name() {
		return 'unstoppable-share-buttons';
	}

	/**
	 * Panel title.
	 *
	 * @return string Widget title.
	 */
	public function get_title() {
		return __( 'Unstoppable Share Buttons', 'unstoppable-share-buttons' );
	}

	/**
	 * Panel icon.
	 *
	 * @return string Icon class.
	 */
	public function get_icon() {
		return 'eicon-share';
	}

	/**
	 * Panel categories.
	 *
	 * @return array Category slugs.
	 */
	public function get_categories() {
		return array( 'general' );
	}

	/**
	 * Panel search terms.
	 *
	 * @return array Keywords.
	 */
	public function get_keywords() {
		return array( 'share', 'social', 'sharing', 'unstoppable' );
	}

	/**
	 * Styles this widget depends on.
	 *
	 * @return array Style handles.
	 */
	public function get_style_depends() {
		return array( 'ushb-frontend' );
	}

	/**
	 * Scripts this widget depends on.
	 *
	 * @return array Script handles.
	 */
	public function get_script_depends() {
		return array( 'ushb-frontend' );
	}

	/**
	 * Builds the widget controls.
	 */
	protected function register_controls() {
		$network_options = array();

		foreach ( USHB_Networks::all() as $slug => $network ) {
			$network_options[ $slug ] = $network['label'];
		}

		$this->start_controls_section(
			'section_content',
			array(
				'label' => __( 'Share buttons', 'unstoppable-share-buttons' ),
			)
		);

		$this->add_control(
			'networks',
			array(
				'label'       => __( 'Networks', 'unstoppable-share-buttons' ),
				'type'        => \Elementor\Controls_Manager::SELECT2,
				'multiple'    => true,
				'options'     => $network_options,
				'default'     => (array) USHB_Settings::get( 'inline_networks' ),
				'label_block' => true,
			)
		);

		$this->add_control(
			'heading',
			array(
				'label'       => __( 'Heading', 'unstoppable-share-buttons' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'placeholder' => __( 'Share this post', 'unstoppable-share-buttons' ),
			)
		);

		$this->add_control(
			'labels',
			array(
				'label'   => __( 'Button content', 'unstoppable-share-buttons' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => USHB_Settings::get( 'labels' ),
				'options' => array(
					'icon'  => __( 'Icon only', 'unstoppable-share-buttons' ),
					'label' => __( 'Name only', 'unstoppable-share-buttons' ),
					'both'  => __( 'Icon and name', 'unstoppable-share-buttons' ),
				),
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style',
			array(
				'label' => __( 'Style', 'unstoppable-share-buttons' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'shape',
			array(
				'label'   => __( 'Shape', 'unstoppable-share-buttons' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => USHB_Settings::get( 'shape' ),
				'options' => array(
					'square'  => __( 'Square', 'unstoppable-share-buttons' ),
					'rounded' => __( 'Rounded', 'unstoppable-share-buttons' ),
					'pill'    => __( 'Pill', 'unstoppable-share-buttons' ),
					'circle'  => __( 'Circle', 'unstoppable-share-buttons' ),
					'ribbon'  => __( 'Ribbon', 'unstoppable-share-buttons' ),
					'drop'    => __( 'Drop', 'unstoppable-share-buttons' ),
				),
			)
		);

		$this->add_control(
			'button_style',
			array(
				'label'   => __( 'Fill', 'unstoppable-share-buttons' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => USHB_Settings::get( 'style' ),
				'options' => array(
					'filled'   => __( 'Filled', 'unstoppable-share-buttons' ),
					'outline'  => __( 'Outline', 'unstoppable-share-buttons' ),
					'minimal'  => __( 'Minimal', 'unstoppable-share-buttons' ),
					'dashed'   => __( 'Dashed', 'unstoppable-share-buttons' ),
					'glass'    => __( 'Glass', 'unstoppable-share-buttons' ),
					'gradient' => __( 'Gradient', 'unstoppable-share-buttons' ),
				),
			)
		);

		$this->add_control(
			'align',
			array(
				'label'   => __( 'Alignment', 'unstoppable-share-buttons' ),
				'type'    => \Elementor\Controls_Manager::CHOOSE,
				'default' => USHB_Settings::get( 'inline_align' ),
				'options' => array(
					'left'   => array(
						'title' => __( 'Left', 'unstoppable-share-buttons' ),
						'icon'  => 'eicon-text-align-left',
					),
					'center' => array(
						'title' => __( 'Center', 'unstoppable-share-buttons' ),
						'icon'  => 'eicon-text-align-center',
					),
					'right'  => array(
						'title' => __( 'Right', 'unstoppable-share-buttons' ),
						'icon'  => 'eicon-text-align-right',
					),
				),
			)
		);

		$this->add_control(
			'palette',
			array(
				'label'   => __( 'Colours', 'unstoppable-share-buttons' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => USHB_Settings::get( 'palette' ),
				'options' => array(
					'brand'   => __( 'Brand colours', 'unstoppable-share-buttons' ),
					'custom'  => __( 'One custom colour', 'unstoppable-share-buttons' ),
					'mono'    => __( 'Monochrome', 'unstoppable-share-buttons' ),
					'inherit' => __( 'Inherit from theme', 'unstoppable-share-buttons' ),
				),
			)
		);

		$this->add_control(
			'custom_color',
			array(
				'label'     => __( 'Custom colour', 'unstoppable-share-buttons' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => USHB_Settings::get( 'custom_color' ),
				'condition' => array(
					'palette' => 'custom',
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Renders the widget.
	 */
	protected function render() {
		$settings = $this->get_settings_for_display();

		$args = array(
			'placement' => 'elementor',
			'networks'  => isset( $settings['networks'] ) ? $settings['networks'] : array(),
			'heading'   => isset( $settings['heading'] ) ? sanitize_text_field( $settings['heading'] ) : '',
			'shape'     => isset( $settings['shape'] ) ? sanitize_key( $settings['shape'] ) : USHB_Settings::get( 'shape' ),
			'style'     => isset( $settings['button_style'] ) ? sanitize_key( $settings['button_style'] ) : USHB_Settings::get( 'style' ),
			'labels'    => isset( $settings['labels'] ) ? sanitize_key( $settings['labels'] ) : USHB_Settings::get( 'labels' ),
			'align'     => isset( $settings['align'] ) ? sanitize_key( $settings['align'] ) : USHB_Settings::get( 'inline_align' ),
			'palette'   => isset( $settings['palette'] ) ? sanitize_key( $settings['palette'] ) : USHB_Settings::get( 'palette' ),
		);

		if ( ! empty( $settings['custom_color'] ) ) {
			$color = sanitize_hex_color( $settings['custom_color'] );

			if ( $color ) {
				$args['color'] = $color;
			}
		}

		echo USHB_Render::bar( $args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Markup is assembled from individually escaped parts.
	}
}
