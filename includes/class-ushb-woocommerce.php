<?php
/**
 * WooCommerce product sharing.
 *
 * The plugin works perfectly well without WooCommerce. This file only does
 * anything when WooCommerce is active and the integration is switched on.
 *
 * @package Unstoppable_Share_Buttons
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Puts a share bar on product pages.
 */
class USHB_WooCommerce {

	/**
	 * Hooks the product template when WooCommerce is present.
	 */
	public static function init() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}

		// Product data improves the share preview even if the bar is placed by
		// a shortcode rather than the automatic position.
		add_filter( 'ushb_context', array( __CLASS__, 'product_context' ), 10, 2 );

		if ( ! USHB_Settings::get( 'woo_enabled' ) ) {
			return;
		}

		$hooks = array(
			'after_price'       => array( 'woocommerce_single_product_summary', 15 ),
			'after_add_to_cart' => array( 'woocommerce_single_product_summary', 35 ),
			'after_meta'        => array( 'woocommerce_single_product_summary', 45 ),
			'after_summary'     => array( 'woocommerce_single_product_summary', 60 ),
			'in_tabs'           => array( 'woocommerce_after_single_product_summary', 25 ),
		);

		$position = USHB_Settings::get( 'woo_position', 'after_summary' );
		$target   = isset( $hooks[ $position ] ) ? $hooks[ $position ] : $hooks['after_summary'];

		add_action( $target[0], array( __CLASS__, 'render' ), $target[1] );
	}

	/**
	 * Prints the product share bar.
	 */
	public static function render() {
		if ( ! function_exists( 'is_product' ) || ! is_product() ) {
			return;
		}

		USHB_Frontend::enqueue();

		$bar = USHB_Render::bar(
			array(
				'placement'   => 'woo',
				'post_id'     => (int) get_the_ID(),
				'networks'    => USHB_Settings::get( 'woo_networks' ),
				'extra_class' => 'ushb--product',
			)
		);

		echo $bar; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Markup is assembled from individually escaped parts in USHB_Render.
	}

	/**
	 * Prefers the product image and short description when sharing.
	 *
	 * @param array $context Sharing context.
	 * @param int   $post_id Post ID.
	 * @return array Adjusted context.
	 */
	public static function product_context( $context, $post_id ) {
		if ( ! function_exists( 'wc_get_product' ) ) {
			return $context;
		}

		if ( 'product' !== get_post_type( $post_id ) ) {
			return $context;
		}

		$product = wc_get_product( $post_id );

		if ( ! $product ) {
			return $context;
		}

		$short = $product->get_short_description();

		if ( $short ) {
			$context['excerpt'] = wp_trim_words( wp_strip_all_tags( $short ), 40, '' );
		}

		if ( empty( $context['image'] ) ) {
			$image_id = $product->get_image_id();

			if ( $image_id ) {
				$context['image'] = wp_get_attachment_image_url( $image_id, 'large' );
			}
		}

		return $context;
	}
}
