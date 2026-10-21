<?php
/**
 * The settings screen.
 *
 * @package Unstoppable_Share_Buttons
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$ushb_tab  = USHB_Admin::current_tab();
$ushb_tabs = USHB_Admin::tabs();
?>
<div class="wrap ushb-wrap">

	<h1 class="screen-reader-text"><?php esc_html_e( 'Unstoppable Share Buttons settings', 'unstoppable-share-buttons' ); ?></h1>

	<div class="ushb-masthead">
		<div class="ushb-masthead__brand">
			<span class="ushb-logo" aria-hidden="true">
				<?php echo wp_kses( USHB_Icons::svg( 'native' ), USHB_Icons::allowed_svg_tags() ); ?>
			</span>
			<div>
				<p class="ushb-masthead__title"><?php esc_html_e( 'Unstoppable Share Buttons', 'unstoppable-share-buttons' ); ?></p>
				<p><?php esc_html_e( 'Share buttons where you want them, looking how you want.', 'unstoppable-share-buttons' ); ?></p>
			</div>
		</div>
		<div class="ushb-masthead__stat">
			<strong><?php echo esc_html( number_format_i18n( USHB_Stats::grand_total() ) ); ?></strong>
			<span><?php esc_html_e( 'shares recorded', 'unstoppable-share-buttons' ); ?></span>
		</div>
	</div>

	<nav class="ushb-tabs" aria-label="<?php esc_attr_e( 'Settings sections', 'unstoppable-share-buttons' ); ?>">
		<?php foreach ( $ushb_tabs as $ushb_slug => $ushb_label ) : ?>
			<a class="ushb-tabs__link<?php echo $ushb_slug === $ushb_tab ? ' is-active' : ''; ?>"
				href="<?php echo esc_url( admin_url( 'admin.php?page=' . USHB_Admin::MENU_SLUG . '&tab=' . $ushb_slug ) ); ?>">
				<?php echo esc_html( $ushb_label ); ?>
			</a>
		<?php endforeach; ?>
	</nav>

	<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" class="ushb-form">
		<input type="hidden" name="action" value="ushb_save_settings">
		<?php
		wp_nonce_field( 'ushb_save_settings', 'ushb_nonce' );
		USHB_Admin::tab_input( $ushb_tab );
		?>

		<div class="ushb-layout">
			<div class="ushb-main">

				<?php if ( 'placement' === $ushb_tab ) : ?>

					<section class="ushb-card">
						<h2><?php esc_html_e( 'Where bars can appear', 'unstoppable-share-buttons' ); ?></h2>
						<?php
						USHB_Admin::post_type_picker();
						USHB_Admin::toggle( 'show_on_front', __( 'Front page', 'unstoppable-share-buttons' ) );
						USHB_Admin::toggle( 'show_on_archives', __( 'Archives and blog index', 'unstoppable-share-buttons' ) );
						USHB_Admin::toggle( 'hide_for_logged_in', __( 'Hide from logged-in users', 'unstoppable-share-buttons' ) );
						USHB_Admin::text( 'exclude_ids', __( 'Never show on these post IDs', 'unstoppable-share-buttons' ), __( 'Comma separated, for example <code>12, 340, 512</code>.', 'unstoppable-share-buttons' ), '12, 340' );
						USHB_Admin::text( 'include_ids', __( 'Always show on these post IDs', 'unstoppable-share-buttons' ), __( 'Overrides every rule above, including the exclude list.', 'unstoppable-share-buttons' ), '7, 19' );
						?>
					</section>

					<section class="ushb-card">
						<h2><?php esc_html_e( 'Inline bar', 'unstoppable-share-buttons' ); ?></h2>
						<p class="ushb-card__intro"><?php esc_html_e( 'Sits in the flow of the post content.', 'unstoppable-share-buttons' ); ?></p>
						<?php
						USHB_Admin::toggle( 'inline_enabled', __( 'Enable the inline bar', 'unstoppable-share-buttons' ) );
						USHB_Admin::segmented(
							'inline_position',
							__( 'Position', 'unstoppable-share-buttons' ),
							array(
								'above'           => __( 'Above', 'unstoppable-share-buttons' ),
								'below'           => __( 'Below', 'unstoppable-share-buttons' ),
								'both'            => __( 'Both', 'unstoppable-share-buttons' ),
								'after_paragraph' => __( 'Mid-post', 'unstoppable-share-buttons' ),
							)
						);
						USHB_Admin::number( 'inline_paragraph', __( 'Insert after paragraph', 'unstoppable-share-buttons' ), 1, 50, __( 'Only used by the mid-post position. Falls back to the end of the post when there are fewer paragraphs than this.', 'unstoppable-share-buttons' ) );
						USHB_Admin::segmented(
							'inline_align',
							__( 'Alignment', 'unstoppable-share-buttons' ),
							array(
								'left'   => __( 'Left', 'unstoppable-share-buttons' ),
								'center' => __( 'Center', 'unstoppable-share-buttons' ),
								'right'  => __( 'Right', 'unstoppable-share-buttons' ),
							)
						);
						USHB_Admin::text( 'inline_heading', __( 'Heading', 'unstoppable-share-buttons' ), __( 'Optional line printed above the buttons.', 'unstoppable-share-buttons' ), __( 'Share this post', 'unstoppable-share-buttons' ) );
						?>
					</section>

					<section class="ushb-card">
						<h2><?php esc_html_e( 'Floating sidebar', 'unstoppable-share-buttons' ); ?></h2>
						<p class="ushb-card__intro"><?php esc_html_e( 'Sticks to the edge of the window and follows the reader down the page.', 'unstoppable-share-buttons' ); ?></p>
						<?php
						USHB_Admin::toggle( 'floating_enabled', __( 'Enable the floating bar', 'unstoppable-share-buttons' ) );
						USHB_Admin::segmented(
							'floating_side',
							__( 'Side', 'unstoppable-share-buttons' ),
							array(
								'left'  => __( 'Left', 'unstoppable-share-buttons' ),
								'right' => __( 'Right', 'unstoppable-share-buttons' ),
							)
						);
						USHB_Admin::number( 'floating_offset', __( 'Vertical position', 'unstoppable-share-buttons' ), 0, 100, __( 'Distance from the top of the window. 50 centres it.', 'unstoppable-share-buttons' ), '%' );
						USHB_Admin::number( 'floating_min_width', __( 'Hide below this screen width', 'unstoppable-share-buttons' ), 0, 2560, __( 'Narrow screens rarely have room beside the content.', 'unstoppable-share-buttons' ), 'px' );
						?>
					</section>

					<section class="ushb-card">
						<h2><?php esc_html_e( 'Sticky mobile bar', 'unstoppable-share-buttons' ); ?></h2>
						<p class="ushb-card__intro"><?php esc_html_e( 'Pinned to the bottom of the screen on phones.', 'unstoppable-share-buttons' ); ?></p>
						<?php
						USHB_Admin::toggle( 'sticky_enabled', __( 'Enable the sticky bar', 'unstoppable-share-buttons' ) );
						USHB_Admin::number( 'sticky_max_width', __( 'Show below this screen width', 'unstoppable-share-buttons' ), 0, 2560, '', 'px' );
						?>
					</section>

					<section class="ushb-card">
						<h2><?php esc_html_e( 'Place a bar by hand', 'unstoppable-share-buttons' ); ?></h2>
						<p class="ushb-card__intro">
							<?php esc_html_e( 'The bars above appear on their own. To put one in a particular spot instead — a widget, a page builder, a template, partway down a page — paste a shortcode where you want it.', 'unstoppable-share-buttons' ); ?>
						</p>
						<p class="ushb-shortcode-hint"><code>[unstoppable_share]</code></p>
						<?php USHB_Admin::shortcode_reference(); ?>
						<p class="ushb-help">
							<?php esc_html_e( 'There is also a block called Unstoppable Share Buttons in the editor, and a widget in Elementor.', 'unstoppable-share-buttons' ); ?>
						</p>
					</section>

					<section class="ushb-card">
						<h2><?php esc_html_e( 'WooCommerce products', 'unstoppable-share-buttons' ); ?></h2>
						<?php if ( class_exists( 'WooCommerce' ) ) : ?>
							<?php
							USHB_Admin::toggle( 'woo_enabled', __( 'Share bar on product pages', 'unstoppable-share-buttons' ) );
							USHB_Admin::select(
								'woo_position',
								__( 'Position', 'unstoppable-share-buttons' ),
								array(
									'after_price'       => __( 'After the price', 'unstoppable-share-buttons' ),
									'after_add_to_cart' => __( 'After the add-to-cart button', 'unstoppable-share-buttons' ),
									'after_meta'        => __( 'After the product meta', 'unstoppable-share-buttons' ),
									'after_summary'     => __( 'End of the summary column', 'unstoppable-share-buttons' ),
									'in_tabs'           => __( 'Below the tabs', 'unstoppable-share-buttons' ),
								)
							);
							?>
						<?php else : ?>
							<p class="ushb-empty"><?php esc_html_e( 'WooCommerce is not active, so there is nothing to configure here yet.', 'unstoppable-share-buttons' ); ?></p>
						<?php endif; ?>
					</section>

				<?php elseif ( 'networks' === $ushb_tab ) : ?>

					<section class="ushb-card">
						<?php USHB_Admin::network_picker( 'inline_networks', __( 'Inline bar', 'unstoppable-share-buttons' ), __( 'Click a network to add it, then drag to reorder.', 'unstoppable-share-buttons' ) ); ?>
					</section>

					<section class="ushb-card">
						<?php USHB_Admin::network_picker( 'floating_networks', __( 'Floating sidebar', 'unstoppable-share-buttons' ), __( 'Keep this list short. Five or six icons is usually the limit before it crowds the page.', 'unstoppable-share-buttons' ) ); ?>
					</section>

					<section class="ushb-card">
						<?php USHB_Admin::network_picker( 'sticky_networks', __( 'Sticky mobile bar', 'unstoppable-share-buttons' ), __( 'Messaging apps and the device share sheet work best on phones.', 'unstoppable-share-buttons' ) ); ?>
					</section>

					<section class="ushb-card">
						<h2><?php esc_html_e( 'Universal share button', 'unstoppable-share-buttons' ); ?></h2>
						<p class="ushb-card__intro"><?php esc_html_e( 'One button that opens a dialog with everything in it. Place it with the [unstoppable_share_button] shortcode.', 'unstoppable-share-buttons' ); ?></p>
						<?php
						USHB_Admin::text( 'popup_label', __( 'Button text', 'unstoppable-share-buttons' ), '', __( 'Share', 'unstoppable-share-buttons' ) );
						USHB_Admin::network_picker( 'popup_networks', __( 'Networks in the dialog', 'unstoppable-share-buttons' ) );
						?>
					</section>

					<?php if ( class_exists( 'WooCommerce' ) ) : ?>
						<section class="ushb-card">
							<?php USHB_Admin::network_picker( 'woo_networks', __( 'Product pages', 'unstoppable-share-buttons' ) ); ?>
						</section>
					<?php endif; ?>

				<?php elseif ( 'design' === $ushb_tab ) : ?>

					<section class="ushb-card">
						<h2><?php esc_html_e( 'Shape and fill', 'unstoppable-share-buttons' ); ?></h2>
						<?php
						USHB_Admin::segmented(
							'shape',
							__( 'Shape', 'unstoppable-share-buttons' ),
							array(
								'square'  => __( 'Square', 'unstoppable-share-buttons' ),
								'rounded' => __( 'Rounded', 'unstoppable-share-buttons' ),
								'pill'    => __( 'Pill', 'unstoppable-share-buttons' ),
								'circle'  => __( 'Circle', 'unstoppable-share-buttons' ),
								'ribbon'  => __( 'Ribbon', 'unstoppable-share-buttons' ),
								'drop'    => __( 'Drop', 'unstoppable-share-buttons' ),
							)
						);
						USHB_Admin::segmented(
							'style',
							__( 'Fill', 'unstoppable-share-buttons' ),
							array(
								'filled'   => __( 'Filled', 'unstoppable-share-buttons' ),
								'outline'  => __( 'Outline', 'unstoppable-share-buttons' ),
								'minimal'  => __( 'Minimal', 'unstoppable-share-buttons' ),
								'dashed'   => __( 'Dashed', 'unstoppable-share-buttons' ),
								'glass'    => __( 'Glass', 'unstoppable-share-buttons' ),
								'gradient' => __( 'Gradient', 'unstoppable-share-buttons' ),
							)
						);
						USHB_Admin::segmented(
							'labels',
							__( 'Button content', 'unstoppable-share-buttons' ),
							array(
								'icon'  => __( 'Icon only', 'unstoppable-share-buttons' ),
								'label' => __( 'Name only', 'unstoppable-share-buttons' ),
								'both'  => __( 'Icon and name', 'unstoppable-share-buttons' ),
							)
						);
						USHB_Admin::segmented(
							'hover_effect',
							__( 'Hover', 'unstoppable-share-buttons' ),
							array(
								'none'  => __( 'None', 'unstoppable-share-buttons' ),
								'lift'  => __( 'Lift', 'unstoppable-share-buttons' ),
								'grow'  => __( 'Grow', 'unstoppable-share-buttons' ),
								'slide' => __( 'Slide', 'unstoppable-share-buttons' ),
								'glow'  => __( 'Glow', 'unstoppable-share-buttons' ),
							)
						);
						?>
					</section>

					<section class="ushb-card">
						<h2><?php esc_html_e( 'Size and spacing', 'unstoppable-share-buttons' ); ?></h2>
						<?php
						USHB_Admin::number( 'size', __( 'Button size', 'unstoppable-share-buttons' ), 24, 96, '', 'px' );
						USHB_Admin::number( 'gap', __( 'Gap between buttons', 'unstoppable-share-buttons' ), 0, 40, '', 'px' );
						USHB_Admin::number( 'radius', __( 'Corner radius', 'unstoppable-share-buttons' ), 0, 50, __( 'Used by the rounded shape. Pill and circle set their own.', 'unstoppable-share-buttons' ), 'px' );
						USHB_Admin::toggle( 'full_width', __( 'Stretch buttons to fill the row', 'unstoppable-share-buttons' ) );
						?>
					</section>

					<section class="ushb-card">
						<h2><?php esc_html_e( 'Colour', 'unstoppable-share-buttons' ); ?></h2>
						<?php
						USHB_Admin::segmented(
							'palette',
							__( 'Palette', 'unstoppable-share-buttons' ),
							array(
								'brand'   => __( 'Brand colours', 'unstoppable-share-buttons' ),
								'custom'  => __( 'One colour', 'unstoppable-share-buttons' ),
								'mono'    => __( 'Monochrome', 'unstoppable-share-buttons' ),
								'inherit' => __( 'Inherit', 'unstoppable-share-buttons' ),
							)
						);
						USHB_Admin::color( 'custom_color', __( 'Custom colour', 'unstoppable-share-buttons' ) );
						USHB_Admin::color( 'custom_text_color', __( 'Icon colour on top', 'unstoppable-share-buttons' ) );
						USHB_Admin::segmented(
							'color_scheme',
							__( 'Light or dark', 'unstoppable-share-buttons' ),
							array(
								'auto'  => __( 'Follow the visitor', 'unstoppable-share-buttons' ),
								'light' => __( 'Always light', 'unstoppable-share-buttons' ),
								'dark'  => __( 'Always dark', 'unstoppable-share-buttons' ),
							)
						);
						?>
					</section>

					<section class="ushb-card">
						<h2><?php esc_html_e( 'Extras', 'unstoppable-share-buttons' ); ?></h2>
						<?php
						USHB_Admin::text( 'cta_text', __( 'Call to action', 'unstoppable-share-buttons' ), __( 'A short line of encouragement printed with the bar.', 'unstoppable-share-buttons' ), __( 'Found this useful? Pass it on.', 'unstoppable-share-buttons' ) );
						USHB_Admin::segmented(
							'cta_position',
							__( 'Call to action goes', 'unstoppable-share-buttons' ),
							array(
								'above' => __( 'Above buttons', 'unstoppable-share-buttons' ),
								'below' => __( 'Below buttons', 'unstoppable-share-buttons' ),
							)
						);
						USHB_Admin::toggle( 'show_counts', __( 'Show share counts', 'unstoppable-share-buttons' ), __( 'Counts come from clicks recorded on this site, not from the networks themselves.', 'unstoppable-share-buttons' ) );
						USHB_Admin::number( 'count_threshold', __( 'Hide the total below', 'unstoppable-share-buttons' ), 0, 100000, __( 'A total of two shares is worse than no total at all.', 'unstoppable-share-buttons' ) );
						?>
					</section>

				<?php elseif ( 'tracking' === $ushb_tab ) : ?>

					<section class="ushb-card">
						<h2><?php esc_html_e( 'Built-in analytics', 'unstoppable-share-buttons' ); ?></h2>
						<?php
						USHB_Admin::toggle( 'track_clicks', __( 'Record share clicks', 'unstoppable-share-buttons' ), __( 'Stores a per-day count of clicks by post and network on your own server. No IP address, browser or user ID is saved, and nothing is sent anywhere.', 'unstoppable-share-buttons' ) );
						?>
						<p class="ushb-inline-link">
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . USHB_Admin::MENU_SLUG . '-analytics' ) ); ?>">
								<?php esc_html_e( 'Open the analytics screen', 'unstoppable-share-buttons' ); ?>
							</a>
						</p>
					</section>

					<section class="ushb-card">
						<h2><?php esc_html_e( 'Send events elsewhere', 'unstoppable-share-buttons' ); ?></h2>
						<p class="ushb-card__intro"><?php esc_html_e( 'These fire through scripts your site already loads. This plugin never loads a tracker for you.', 'unstoppable-share-buttons' ); ?></p>
						<?php
						USHB_Admin::toggle( 'ga4_enabled', __( 'Google Analytics 4', 'unstoppable-share-buttons' ), __( 'Fires the standard <code>share</code> event through an existing <code>gtag</code>.', 'unstoppable-share-buttons' ) );
						USHB_Admin::toggle( 'meta_pixel_enabled', __( 'Meta Pixel', 'unstoppable-share-buttons' ), __( 'Fires a <code>Share</code> custom event through an existing <code>fbq</code>.', 'unstoppable-share-buttons' ) );
						USHB_Admin::toggle( 'datalayer_enabled', __( 'Google Tag Manager dataLayer', 'unstoppable-share-buttons' ), __( 'Pushes an <code>unstoppable_share</code> event with the network, placement and post ID.', 'unstoppable-share-buttons' ) );
						?>
					</section>

					<section class="ushb-card">
						<h2><?php esc_html_e( 'Campaign tagging', 'unstoppable-share-buttons' ); ?></h2>
						<p class="ushb-card__intro"><?php esc_html_e( 'Adds UTM parameters to the link people share, so returning traffic is attributable.', 'unstoppable-share-buttons' ); ?></p>
						<?php
						USHB_Admin::toggle( 'utm_enabled', __( 'Add UTM parameters', 'unstoppable-share-buttons' ) );
						USHB_Admin::text( 'utm_source', __( 'Source', 'unstoppable-share-buttons' ), __( 'Placeholders: <code>{network}</code>, <code>{sitename}</code>, <code>{title}</code>, <code>{post_id}</code>.', 'unstoppable-share-buttons' ), '{network}' );
						USHB_Admin::text( 'utm_medium', __( 'Medium', 'unstoppable-share-buttons' ), '', 'social' );
						USHB_Admin::text( 'utm_campaign', __( 'Campaign', 'unstoppable-share-buttons' ), '', 'share' );
						USHB_Admin::text( 'utm_content', __( 'Content', 'unstoppable-share-buttons' ) );
						USHB_Admin::text( 'utm_term', __( 'Term', 'unstoppable-share-buttons' ) );
						?>
					</section>

				<?php elseif ( 'content' === $ushb_tab ) : ?>

					<section class="ushb-card">
						<h2><?php esc_html_e( 'Network settings', 'unstoppable-share-buttons' ); ?></h2>
						<?php
						USHB_Admin::text( 'x_via', __( 'X username', 'unstoppable-share-buttons' ), __( 'Added as <code>via</code> on shares to X. No @ needed.', 'unstoppable-share-buttons' ), 'yourhandle' );
						USHB_Admin::text( 'x_hashtags', __( 'Default hashtags', 'unstoppable-share-buttons' ), __( 'Comma separated. The # is added for you.', 'unstoppable-share-buttons' ), 'WordPress, design' );
						USHB_Admin::text( 'fb_app_id', __( 'Facebook App ID', 'unstoppable-share-buttons' ), __( 'Required by the Messenger button and used for the <code>fb:app_id</code> tag.', 'unstoppable-share-buttons' ) );
						USHB_Admin::text( 'copy_confirmation', __( 'Copy confirmation text', 'unstoppable-share-buttons' ), '', __( 'Link copied', 'unstoppable-share-buttons' ) );
						?>
					</section>

					<section class="ushb-card">
						<h2><?php esc_html_e( 'Ask an AI', 'unstoppable-share-buttons' ); ?></h2>
						<p class="ushb-card__intro"><?php esc_html_e( 'The AI buttons open an assistant with a prompt already written. Place them with the [unstoppable_share_ai] shortcode.', 'unstoppable-share-buttons' ); ?></p>
						<?php USHB_Admin::textarea( 'ai_prompt', __( 'Prompt', 'unstoppable-share-buttons' ), __( 'Placeholders: <code>{url}</code>, <code>{title}</code>, <code>{sitename}</code>.', 'unstoppable-share-buttons' ), 3 ); ?>
					</section>

					<section class="ushb-card">
						<h2><?php esc_html_e( 'Link previews', 'unstoppable-share-buttons' ); ?></h2>
						<?php
						$ushb_conflict = USHB_Meta_Tags::conflicting_plugin();

						if ( $ushb_conflict ) {
							echo '<p class="ushb-notice-inline">' . sprintf(
								/* translators: %s: name of the active SEO plugin. */
								esc_html__( '%s is already writing Open Graph tags, so this stays switched off. Two sets of tags confuse the scrapers they exist for.', 'unstoppable-share-buttons' ),
								'<strong>' . esc_html( $ushb_conflict ) . '</strong>'
							) . '</p>';
						}

						USHB_Admin::toggle( 'og_enabled', __( 'Output Open Graph and X card tags', 'unstoppable-share-buttons' ) );
						USHB_Admin::segmented(
							'twitter_card',
							__( 'Card size', 'unstoppable-share-buttons' ),
							array(
								'summary'             => __( 'Small', 'unstoppable-share-buttons' ),
								'summary_large_image' => __( 'Large image', 'unstoppable-share-buttons' ),
							)
						);
						USHB_Admin::text( 'og_default_image', __( 'Fallback image URL', 'unstoppable-share-buttons' ), __( 'Used when a post has no featured image. Also used as the Pinterest image.', 'unstoppable-share-buttons' ) );
						?>
					</section>

					<section class="ushb-card">
						<h2><?php esc_html_e( 'Shortcodes and template tags', 'unstoppable-share-buttons' ); ?></h2>
						<?php USHB_Admin::shortcode_reference(); ?>
					</section>

				<?php endif; ?>

				<?php USHB_Admin::field_registry(); ?>

				<div class="ushb-actions">
					<button type="submit" class="button button-primary button-hero"><?php esc_html_e( 'Save changes', 'unstoppable-share-buttons' ); ?></button>
				</div>
			</div>

			<aside class="ushb-side">
				<div class="ushb-preview">
					<p class="ushb-preview__title"><?php esc_html_e( 'Preview', 'unstoppable-share-buttons' ); ?></p>
					<div class="ushb-preview__stage">
						<?php
						$ushb_preview = USHB_Render::bar(
							array(
								'placement' => 'preview',
								'networks'  => USHB_Settings::get( 'inline_networks' ),
								'align'     => 'left',
							)
						);

						echo $ushb_preview; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Markup is assembled from individually escaped parts in USHB_Render.
						?>
					</div>
					<p class="ushb-help"><?php esc_html_e( 'Shape, fill, size and colour update as you change them. Save to apply.', 'unstoppable-share-buttons' ); ?></p>
				</div>

				<div class="ushb-side__box">
					<h3><?php esc_html_e( 'Worth knowing', 'unstoppable-share-buttons' ); ?></h3>
					<ul>
						<li><?php esc_html_e( 'Four to six networks beats twenty. Every extra button costs attention.', 'unstoppable-share-buttons' ); ?></li>
						<li><?php esc_html_e( 'Put the inline bar below the post. People share after they read.', 'unstoppable-share-buttons' ); ?></li>
						<li><?php esc_html_e( 'On phones the device share sheet beats individual network buttons.', 'unstoppable-share-buttons' ); ?></li>
						<li><?php esc_html_e( 'Hide the share count until it is a number worth showing.', 'unstoppable-share-buttons' ); ?></li>
					</ul>
				</div>
			</aside>
		</div>
	</form>
</div>
