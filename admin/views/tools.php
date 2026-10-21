<?php
/**
 * The tools screen.
 *
 * @package Unstoppable_Share_Buttons
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap ushb-wrap">

	<h1 class="screen-reader-text"><?php esc_html_e( 'Unstoppable Share Buttons tools', 'unstoppable-share-buttons' ); ?></h1>

	<div class="ushb-masthead">
		<div class="ushb-masthead__brand">
			<span class="ushb-logo" aria-hidden="true">
				<?php echo wp_kses( USHB_Icons::svg( 'native' ), USHB_Icons::allowed_svg_tags() ); ?>
			</span>
			<div>
				<p class="ushb-masthead__title"><?php esc_html_e( 'Tools', 'unstoppable-share-buttons' ); ?></p>
				<p><?php esc_html_e( 'Move settings between sites and clear things out.', 'unstoppable-share-buttons' ); ?></p>
			</div>
		</div>
	</div>

	<section class="ushb-card ushb-card--diag">
		<h2><?php esc_html_e( 'Why am I not seeing share buttons?', 'unstoppable-share-buttons' ); ?></h2>
		<p class="ushb-card__intro"><?php esc_html_e( 'Every condition the front end checks, with its current value.', 'unstoppable-share-buttons' ); ?></p>
		<?php USHB_Admin::diagnostics(); ?>
	</section>

	<div class="ushb-columns">

		<section class="ushb-card">
			<h2><?php esc_html_e( 'Export', 'unstoppable-share-buttons' ); ?></h2>
			<p><?php esc_html_e( 'Downloads every setting as JSON. Share history is not included.', 'unstoppable-share-buttons' ); ?></p>
			<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
				<input type="hidden" name="action" value="ushb_tool">
				<input type="hidden" name="ushb_action" value="export">
				<?php wp_nonce_field( 'ushb_tool', 'ushb_nonce' ); ?>
				<button type="submit" class="button button-primary"><?php esc_html_e( 'Download settings', 'unstoppable-share-buttons' ); ?></button>
			</form>
		</section>

		<section class="ushb-card">
			<h2><?php esc_html_e( 'Import', 'unstoppable-share-buttons' ); ?></h2>
			<p><?php esc_html_e( 'Paste an export below. This replaces your current settings.', 'unstoppable-share-buttons' ); ?></p>
			<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
				<input type="hidden" name="action" value="ushb_tool">
				<input type="hidden" name="ushb_action" value="import">
				<?php wp_nonce_field( 'ushb_tool', 'ushb_nonce' ); ?>
				<textarea name="ushb_import" class="ushb-input ushb-input--area" rows="8" placeholder="{ &quot;inline_networks&quot;: [ ... ] }"></textarea>
				<button type="submit" class="button button-primary"><?php esc_html_e( 'Import settings', 'unstoppable-share-buttons' ); ?></button>
			</form>
		</section>

		<section class="ushb-card ushb-card--danger">
			<h2><?php esc_html_e( 'Clear share history', 'unstoppable-share-buttons' ); ?></h2>
			<p>
				<?php
				printf(
					/* translators: %s: number of recorded clicks. */
					esc_html__( 'Deletes all %s recorded clicks. Your settings are untouched.', 'unstoppable-share-buttons' ),
					'<strong>' . esc_html( number_format_i18n( USHB_Stats::grand_total() ) ) . '</strong>'
				);
				?>
			</p>
			<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" data-confirm="stats">
				<input type="hidden" name="action" value="ushb_tool">
				<input type="hidden" name="ushb_action" value="reset_stats">
				<?php wp_nonce_field( 'ushb_tool', 'ushb_nonce' ); ?>
				<button type="submit" class="button button-link-delete"><?php esc_html_e( 'Clear history', 'unstoppable-share-buttons' ); ?></button>
			</form>
		</section>

		<section class="ushb-card ushb-card--danger">
			<h2><?php esc_html_e( 'Restore defaults', 'unstoppable-share-buttons' ); ?></h2>
			<p><?php esc_html_e( 'Puts every setting back to how it shipped. Share history is kept.', 'unstoppable-share-buttons' ); ?></p>
			<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" data-confirm="settings">
				<input type="hidden" name="action" value="ushb_tool">
				<input type="hidden" name="ushb_action" value="reset_settings">
				<?php wp_nonce_field( 'ushb_tool', 'ushb_nonce' ); ?>
				<button type="submit" class="button button-link-delete"><?php esc_html_e( 'Restore defaults', 'unstoppable-share-buttons' ); ?></button>
			</form>
		</section>

		<section class="ushb-card">
			<h2><?php esc_html_e( 'When the plugin is deleted', 'unstoppable-share-buttons' ); ?></h2>
			<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
				<input type="hidden" name="action" value="ushb_tool">
				<input type="hidden" name="ushb_action" value="uninstall_pref">
				<?php wp_nonce_field( 'ushb_tool', 'ushb_nonce' ); ?>
				<label class="ushb-check">
					<input type="checkbox" name="ushb_delete_data" value="1" <?php checked( 1, (int) USHB_Settings::get( 'delete_data_on_uninstall' ) ); ?>>
					<span><?php esc_html_e( 'Delete settings and share history on uninstall', 'unstoppable-share-buttons' ); ?></span>
				</label>
				<p class="ushb-help"><?php esc_html_e( 'Off by default, so deactivating and reactivating never loses your setup.', 'unstoppable-share-buttons' ); ?></p>
				<button type="submit" class="button"><?php esc_html_e( 'Save', 'unstoppable-share-buttons' ); ?></button>
			</form>
		</section>

	</div>
</div>
