<?php
/**
 * The analytics screen.
 *
 * @package Unstoppable_Share_Buttons
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read only range selector.
$ushb_days = isset( $_GET['range'] ) ? absint( wp_unslash( $_GET['range'] ) ) : 30;
$ushb_days = in_array( $ushb_days, array( 7, 30, 90, 365 ), true ) ? $ushb_days : 30;

$ushb_series   = USHB_Stats::daily_series( $ushb_days );
$ushb_networks = USHB_Stats::totals_by_network( $ushb_days );
$ushb_posts    = USHB_Stats::top_posts( $ushb_days, 10 );
$ushb_period   = array_sum( $ushb_series );
$ushb_peak     = $ushb_series ? max( $ushb_series ) : 0;
$ushb_all      = USHB_Networks::all();
$ushb_top      = $ushb_networks ? max( $ushb_networks ) : 0;
?>
<div class="wrap ushb-wrap">

	<h1 class="screen-reader-text"><?php esc_html_e( 'Share analytics', 'unstoppable-share-buttons' ); ?></h1>

	<div class="ushb-masthead">
		<div class="ushb-masthead__brand">
			<span class="ushb-logo" aria-hidden="true">
				<?php echo wp_kses( USHB_Icons::svg( 'native' ), USHB_Icons::allowed_svg_tags() ); ?>
			</span>
			<div>
				<p class="ushb-masthead__title"><?php esc_html_e( 'Share analytics', 'unstoppable-share-buttons' ); ?></p>
				<p><?php esc_html_e( 'Clicks recorded on your own site. No third party involved.', 'unstoppable-share-buttons' ); ?></p>
			</div>
		</div>
	</div>

	<?php if ( ! USHB_Settings::get( 'track_clicks' ) ) : ?>
		<div class="notice notice-warning">
			<p>
				<?php esc_html_e( 'Click recording is switched off, so these numbers are not moving.', 'unstoppable-share-buttons' ); ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . USHB_Admin::MENU_SLUG . '&tab=tracking' ) ); ?>">
					<?php esc_html_e( 'Turn it on', 'unstoppable-share-buttons' ); ?>
				</a>
			</p>
		</div>
	<?php endif; ?>

	<nav class="ushb-tabs" aria-label="<?php esc_attr_e( 'Date range', 'unstoppable-share-buttons' ); ?>">
		<?php
		$ushb_ranges = array(
			7   => __( 'Last 7 days', 'unstoppable-share-buttons' ),
			30  => __( 'Last 30 days', 'unstoppable-share-buttons' ),
			90  => __( 'Last 90 days', 'unstoppable-share-buttons' ),
			365 => __( 'Last year', 'unstoppable-share-buttons' ),
		);

		foreach ( $ushb_ranges as $ushb_value => $ushb_label ) :
			?>
			<a class="ushb-tabs__link<?php echo $ushb_value === $ushb_days ? ' is-active' : ''; ?>"
				href="<?php echo esc_url( admin_url( 'admin.php?page=' . USHB_Admin::MENU_SLUG . '-analytics&range=' . $ushb_value ) ); ?>">
				<?php echo esc_html( $ushb_label ); ?>
			</a>
		<?php endforeach; ?>
	</nav>

	<div class="ushb-stats">
		<div class="ushb-stat">
			<span class="ushb-stat__label"><?php esc_html_e( 'Shares in range', 'unstoppable-share-buttons' ); ?></span>
			<strong class="ushb-stat__value"><?php echo esc_html( number_format_i18n( $ushb_period ) ); ?></strong>
		</div>
		<div class="ushb-stat">
			<span class="ushb-stat__label"><?php esc_html_e( 'Busiest day', 'unstoppable-share-buttons' ); ?></span>
			<strong class="ushb-stat__value"><?php echo esc_html( number_format_i18n( $ushb_peak ) ); ?></strong>
		</div>
		<div class="ushb-stat">
			<span class="ushb-stat__label"><?php esc_html_e( 'Networks used', 'unstoppable-share-buttons' ); ?></span>
			<strong class="ushb-stat__value"><?php echo esc_html( number_format_i18n( count( $ushb_networks ) ) ); ?></strong>
		</div>
		<div class="ushb-stat">
			<span class="ushb-stat__label"><?php esc_html_e( 'All time', 'unstoppable-share-buttons' ); ?></span>
			<strong class="ushb-stat__value"><?php echo esc_html( number_format_i18n( USHB_Stats::grand_total() ) ); ?></strong>
		</div>
	</div>

	<?php if ( 0 === $ushb_period ) : ?>

		<div class="ushb-card ushb-card--empty">
			<h2><?php esc_html_e( 'Nothing recorded yet', 'unstoppable-share-buttons' ); ?></h2>
			<p><?php esc_html_e( 'Once visitors start using your share buttons, their clicks appear here within a few seconds.', 'unstoppable-share-buttons' ); ?></p>
			<p>
				<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=' . USHB_Admin::MENU_SLUG ) ); ?>">
					<?php esc_html_e( 'Set up your share bars', 'unstoppable-share-buttons' ); ?>
				</a>
			</p>
		</div>

	<?php else : ?>

		<div class="ushb-card">
			<h2><?php esc_html_e( 'Shares per day', 'unstoppable-share-buttons' ); ?></h2>
			<div class="ushb-chart" role="img" aria-label="<?php esc_attr_e( 'Bar chart of share clicks per day', 'unstoppable-share-buttons' ); ?>">
				<?php
				foreach ( $ushb_series as $ushb_date => $ushb_count ) :
					$ushb_height = $ushb_peak ? max( 2, round( ( $ushb_count / $ushb_peak ) * 100 ) ) : 2;
					?>
					<span class="ushb-chart__bar ushb-h<?php echo esc_attr( $ushb_height ); ?>"
						title="<?php echo esc_attr( $ushb_date . ' - ' . $ushb_count ); ?>"></span>
				<?php endforeach; ?>
			</div>
			<div class="ushb-chart__axis">
				<span><?php echo esc_html( (string) array_key_first( $ushb_series ) ); ?></span>
				<span><?php echo esc_html( (string) array_key_last( $ushb_series ) ); ?></span>
			</div>
		</div>

		<div class="ushb-columns">
			<section class="ushb-card">
				<h2><?php esc_html_e( 'By network', 'unstoppable-share-buttons' ); ?></h2>
				<ul class="ushb-bars">
					<?php
					foreach ( $ushb_networks as $ushb_slug => $ushb_count ) :
						$ushb_net   = isset( $ushb_all[ $ushb_slug ] ) ? $ushb_all[ $ushb_slug ] : null;
						$ushb_label = $ushb_net ? $ushb_net['label'] : $ushb_slug;
						$ushb_pct   = $ushb_top ? round( ( $ushb_count / $ushb_top ) * 100 ) : 0;
						?>
						<li class="ushb-bars__row ushb-chip--<?php echo esc_attr( $ushb_slug ); ?>">
							<span class="ushb-bars__icon ushb-chip__icon">
								<?php echo wp_kses( USHB_Icons::svg( $ushb_net && ! empty( $ushb_net['icon'] ) ? $ushb_net['icon'] : 'more' ), USHB_Icons::allowed_svg_tags() ); ?>
							</span>
							<span class="ushb-bars__name"><?php echo esc_html( $ushb_label ); ?></span>
							<span class="ushb-bars__track">
								<span class="ushb-bars__fill ushb-w<?php echo esc_attr( $ushb_pct ); ?>"></span>
							</span>
							<span class="ushb-bars__value"><?php echo esc_html( number_format_i18n( $ushb_count ) ); ?></span>
						</li>
					<?php endforeach; ?>
				</ul>
			</section>

			<section class="ushb-card">
				<h2><?php esc_html_e( 'Most shared', 'unstoppable-share-buttons' ); ?></h2>
				<?php if ( empty( $ushb_posts ) ) : ?>
					<p class="ushb-empty"><?php esc_html_e( 'No posts in this range.', 'unstoppable-share-buttons' ); ?></p>
				<?php else : ?>
					<ol class="ushb-toplist">
						<?php
						foreach ( $ushb_posts as $ushb_row ) :
							$ushb_title = get_the_title( $ushb_row['post_id'] );
							$ushb_link  = get_permalink( $ushb_row['post_id'] );
							?>
							<li>
								<span class="ushb-toplist__title">
									<?php if ( $ushb_link ) : ?>
										<a href="<?php echo esc_url( $ushb_link ); ?>" target="_blank" rel="noopener noreferrer">
											<?php echo esc_html( $ushb_title ? $ushb_title : __( '(no title)', 'unstoppable-share-buttons' ) ); ?>
										</a>
									<?php else : ?>
										<?php esc_html_e( '(deleted post)', 'unstoppable-share-buttons' ); ?>
									<?php endif; ?>
								</span>
								<span class="ushb-toplist__value"><?php echo esc_html( number_format_i18n( $ushb_row['total'] ) ); ?></span>
							</li>
						<?php endforeach; ?>
					</ol>
				<?php endif; ?>
			</section>
		</div>

	<?php endif; ?>
</div>
