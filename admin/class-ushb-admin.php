<?php
/**
 * Admin screens, form handling and tools.
 *
 * @package Unstoppable_Share_Buttons
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Everything behind wp-admin.
 */
class USHB_Admin {

	/**
	 * Capability required to reach any of these screens.
	 */
	const CAPABILITY = 'manage_options';

	/**
	 * Top level menu slug.
	 */
	const MENU_SLUG = 'unstoppable-share-buttons';

	/**
	 * Hooks the admin side.
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'menu_branding' ) );
		add_action( 'admin_post_ushb_save_settings', array( __CLASS__, 'handle_save' ) );
		add_action( 'admin_post_ushb_tool', array( __CLASS__, 'handle_tool' ) );
		add_filter( 'plugin_action_links_' . USHB_BASENAME, array( __CLASS__, 'action_links' ) );
	}

	/**
	 * Adds a settings link on the plugins screen.
	 *
	 * @param array $links Existing links.
	 * @return array Links with ours first.
	 */
	public static function action_links( $links ) {
		$url = admin_url( 'admin.php?page=' . self::MENU_SLUG );

		array_unshift(
			$links,
			'<a href="' . esc_url( $url ) . '">' . esc_html__( 'Settings', 'unstoppable-share-buttons' ) . '</a>'
		);

		return $links;
	}

	/**
	 * Registers the menu and its subpages.
	 */
	public static function menu() {
		add_menu_page(
			__( 'Unstoppable Share Buttons', 'unstoppable-share-buttons' ),
			__( 'Unstoppable Share', 'unstoppable-share-buttons' ),
			self::CAPABILITY,
			self::MENU_SLUG,
			array( __CLASS__, 'render_settings' ),
			'dashicons-share',
			58
		);

		add_submenu_page(
			self::MENU_SLUG,
			__( 'Share button settings', 'unstoppable-share-buttons' ),
			__( 'Settings', 'unstoppable-share-buttons' ),
			self::CAPABILITY,
			self::MENU_SLUG,
			array( __CLASS__, 'render_settings' )
		);

		add_submenu_page(
			self::MENU_SLUG,
			__( 'Share analytics', 'unstoppable-share-buttons' ),
			__( 'Analytics', 'unstoppable-share-buttons' ),
			self::CAPABILITY,
			self::MENU_SLUG . '-analytics',
			array( __CLASS__, 'render_analytics' )
		);

		add_submenu_page(
			self::MENU_SLUG,
			__( 'Share button tools', 'unstoppable-share-buttons' ),
			__( 'Tools', 'unstoppable-share-buttons' ),
			self::CAPABILITY,
			self::MENU_SLUG . '-tools',
			array( __CLASS__, 'render_tools' )
		);
	}

	/**
	 * Enqueues the small stylesheet that colours the admin menu item.
	 */
	public static function menu_branding() {
		wp_enqueue_style(
			'ushb-admin-menu',
			USHB_PLUGIN_URL . 'assets/css/admin-menu.css',
			array(),
			USHB_VERSION
		);
	}

	/**
	 * Loads admin assets on this plugin's screens only.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public static function assets( $hook ) {
		if ( false === strpos( $hook, self::MENU_SLUG ) ) {
			return;
		}

		wp_enqueue_style( 'ushb-frontend', USHB_PLUGIN_URL . 'assets/css/frontend.css', array(), USHB_VERSION );
		wp_enqueue_style( 'ushb-admin', USHB_PLUGIN_URL . 'assets/css/admin.css', array( 'ushb-frontend' ), USHB_VERSION );
		wp_enqueue_script( 'ushb-admin', USHB_PLUGIN_URL . 'assets/js/admin.js', array(), USHB_VERSION, true );

		wp_localize_script(
			'ushb-admin',
			'ushbAdmin',
			array(
				'confirmResetStats'    => __( 'This clears every recorded share click and cannot be undone. Continue?', 'unstoppable-share-buttons' ),
				'confirmResetSettings' => __( 'This restores every setting to its default and cannot be undone. Continue?', 'unstoppable-share-buttons' ),
			)
		);
	}

	/**
	 * Saves the settings form.
	 */
	public static function handle_save() {
		// 1. Verify intent.
		check_admin_referer( 'ushb_save_settings', 'ushb_nonce' );

		// 2. Check permissions.
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die(
				esc_html__( 'You do not have permission to change these settings.', 'unstoppable-share-buttons' ),
				esc_html__( 'Permission denied', 'unstoppable-share-buttons' ),
				array( 'response' => 403 )
			);
		}

		// 3. Unslash then sanitise.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce verified above; every value is sanitised by USHB_Settings::sanitize() below, which whitelists keys and validates each one by type.
		$raw = isset( $_POST['ushb'] ) ? wp_unslash( $_POST['ushb'] ) : array();

		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce verified above; each entry is passed through sanitize_key() on the next line.
		$declared = isset( $_POST['ushb_fields'] ) ? (array) wp_unslash( $_POST['ushb_fields'] ) : array();
		$declared = array_map( 'sanitize_key', $declared );

		USHB_Settings::save( USHB_Settings::sanitize_partial( (array) $raw, $declared ) );

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified above by check_admin_referer.
		$tab = isset( $_POST['ushb_tab'] ) ? sanitize_key( wp_unslash( $_POST['ushb_tab'] ) ) : 'placement';

		USHB_Admin_Notices::add_flash_notice(
			__( 'Settings saved.', 'unstoppable-share-buttons' ),
			'success'
		);

		wp_safe_redirect(
			add_query_arg(
				array(
					'page' => self::MENU_SLUG,
					'tab'  => $tab,
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Runs an action from the Tools screen.
	 */
	public static function handle_tool() {
		check_admin_referer( 'ushb_tool', 'ushb_nonce' );

		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die(
				esc_html__( 'You do not have permission to run this.', 'unstoppable-share-buttons' ),
				esc_html__( 'Permission denied', 'unstoppable-share-buttons' ),
				array( 'response' => 403 )
			);
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified above by check_admin_referer.
		$action = isset( $_POST['ushb_action'] ) ? sanitize_key( wp_unslash( $_POST['ushb_action'] ) ) : '';

		$allowed = array( 'export', 'import', 'reset_stats', 'reset_settings', 'uninstall_pref' );

		if ( ! in_array( $action, $allowed, true ) ) {
			wp_safe_redirect( admin_url( 'admin.php?page=' . self::MENU_SLUG . '-tools' ) );
			exit;
		}

		switch ( $action ) {
			case 'export':
				self::export_settings();
				exit;

			case 'import':
				// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce verified above; the raw string is JSON, decoded and then fully sanitised by USHB_Settings::sanitize().
				$json     = isset( $_POST['ushb_import'] ) ? wp_unslash( $_POST['ushb_import'] ) : '';
				$imported = json_decode( (string) $json, true );

				if ( is_array( $imported ) && JSON_ERROR_NONE === json_last_error() ) {
					USHB_Settings::save( USHB_Settings::sanitize( $imported ) );
					USHB_Admin_Notices::add_flash_notice( __( 'Settings imported.', 'unstoppable-share-buttons' ), 'success' );
				} else {
					USHB_Admin_Notices::add_flash_notice(
						__( 'That did not look like an export from this plugin. Paste the whole JSON file, including the outer braces.', 'unstoppable-share-buttons' ),
						'error'
					);
				}
				break;

			case 'reset_stats':
				USHB_Stats::reset();
				USHB_Admin_Notices::add_flash_notice( __( 'Share history cleared.', 'unstoppable-share-buttons' ), 'success' );
				break;

			case 'reset_settings':
				USHB_Settings::save( USHB_Settings::defaults() );
				USHB_Admin_Notices::add_flash_notice( __( 'Settings restored to their defaults.', 'unstoppable-share-buttons' ), 'success' );
				break;

			case 'uninstall_pref':
				// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified above by check_admin_referer.
				$delete_flag = isset( $_POST['ushb_delete_data'] ) ? sanitize_key( wp_unslash( $_POST['ushb_delete_data'] ) ) : '';

				$current                             = USHB_Settings::all();
				$current['delete_data_on_uninstall'] = ( '1' === $delete_flag ) ? 1 : 0;

				USHB_Settings::save( $current );
				USHB_Admin_Notices::add_flash_notice( __( 'Saved.', 'unstoppable-share-buttons' ), 'success' );
				break;
		}

		wp_safe_redirect( admin_url( 'admin.php?page=' . self::MENU_SLUG . '-tools' ) );
		exit;
	}

	/**
	 * Streams the settings out as a JSON download.
	 */
	private static function export_settings() {
		$filename = 'unstoppable-share-buttons-' . gmdate( 'Y-m-d' ) . '.json';

		nocache_headers();
		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=' . $filename );

		echo wp_json_encode( USHB_Settings::all(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
	}

	/**
	 * The tab definitions for the settings screen.
	 *
	 * @return array Tab slug mapped to label.
	 */
	public static function tabs() {
		return array(
			'placement' => __( 'Placement', 'unstoppable-share-buttons' ),
			'networks'  => __( 'Networks', 'unstoppable-share-buttons' ),
			'design'    => __( 'Design', 'unstoppable-share-buttons' ),
			'tracking'  => __( 'Measurement', 'unstoppable-share-buttons' ),
			'content'   => __( 'Content and meta', 'unstoppable-share-buttons' ),
		);
	}

	/**
	 * The tab currently requested.
	 *
	 * @return string Tab slug.
	 */
	public static function current_tab() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read only navigation, no state change.
		$tab  = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'placement';
		$tabs = self::tabs();

		return isset( $tabs[ $tab ] ) ? $tab : 'placement';
	}

	/**
	 * Renders the settings screen.
	 */
	public static function render_settings() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'unstoppable-share-buttons' ) );
		}

		require USHB_PLUGIN_DIR . 'admin/views/settings.php';
	}

	/**
	 * Renders the analytics screen.
	 */
	public static function render_analytics() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'unstoppable-share-buttons' ) );
		}

		require USHB_PLUGIN_DIR . 'admin/views/analytics.php';
	}

	/**
	 * Renders the tools screen.
	 */
	public static function render_tools() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'unstoppable-share-buttons' ) );
		}

		require USHB_PLUGIN_DIR . 'admin/views/tools.php';
	}

	/**
	 * Field helpers below. Each prints one labelled control.
	 */

	/**
	 * Keys rendered by the form currently being built.
	 *
	 * The settings screen is split across tabs, so any one submission carries
	 * only a fraction of the settings. Recording what was actually rendered
	 * lets the save handler update those keys and leave the rest alone.
	 *
	 * @var array
	 */
	private static $rendered_fields = array();

	/**
	 * Builds the name attribute for a setting and records the key.
	 *
	 * @param string $key Setting key.
	 * @return string Field name.
	 */
	private static function field_name( $key ) {
		self::register_field( $key );

		return 'ushb[' . $key . ']';
	}

	/**
	 * Records that a setting appears in the form being rendered.
	 *
	 * @param string $key Setting key.
	 */
	public static function register_field( $key ) {
		if ( ! in_array( $key, self::$rendered_fields, true ) ) {
			self::$rendered_fields[] = $key;
		}
	}

	/**
	 * Prints the list of settings this form is responsible for.
	 *
	 * Must be called after every field, immediately before the form closes.
	 */
	public static function field_registry() {
		foreach ( self::$rendered_fields as $key ) {
			echo '<input type="hidden" name="ushb_fields[]" value="' . esc_attr( $key ) . '">';
		}
	}

	/**
	 * A row wrapper with a label and optional description.
	 *
	 * @param string $label Field label.
	 * @param string $html  Control markup, already escaped by the caller.
	 * @param string $help  Optional description, may contain simple tags.
	 */
	public static function row( $label, $html, $help = '' ) {
		$allowed_help = array(
			'code'   => array(),
			'strong' => array(),
			'em'     => array(),
			'a'      => array(
				'href'   => array(),
				'target' => array(),
				'rel'    => array(),
			),
		);

		echo '<div class="ushb-row">';
		echo '<div class="ushb-row__label">' . esc_html( $label ) . '</div>';
		echo '<div class="ushb-row__control">' . $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built by the helpers below from escaped parts.

		if ( $help ) {
			echo '<p class="ushb-help">' . wp_kses( $help, $allowed_help ) . '</p>';
		}

		echo '</div></div>';
	}

	/**
	 * A single checkbox styled as a switch.
	 *
	 * @param string $key   Setting key.
	 * @param string $label Label text.
	 * @param string $help  Optional description.
	 */
	public static function toggle( $key, $label, $help = '' ) {
		$html = '<label class="ushb-switch"><input type="checkbox" name="' . esc_attr( self::field_name( $key ) ) . '" value="1" '
			. checked( 1, (int) USHB_Settings::get( $key ), false )
			. ' data-key="' . esc_attr( $key ) . '"><span class="ushb-switch__track"></span></label>';

		self::row( $label, $html, $help );
	}

	/**
	 * A text input.
	 *
	 * @param string $key         Setting key.
	 * @param string $label       Label text.
	 * @param string $help        Optional description.
	 * @param string $placeholder Placeholder text.
	 */
	public static function text( $key, $label, $help = '', $placeholder = '' ) {
		$html = '<input type="text" class="ushb-input" name="' . esc_attr( self::field_name( $key ) ) . '"'
			. ' value="' . esc_attr( USHB_Settings::get( $key ) ) . '"'
			. ' placeholder="' . esc_attr( $placeholder ) . '" data-key="' . esc_attr( $key ) . '">';

		self::row( $label, $html, $help );
	}

	/**
	 * A multi line text area.
	 *
	 * @param string $key   Setting key.
	 * @param string $label Label text.
	 * @param string $help  Optional description.
	 * @param int    $rows  Visible rows.
	 */
	public static function textarea( $key, $label, $help = '', $rows = 6 ) {
		$html = '<textarea class="ushb-input ushb-input--area" rows="' . absint( $rows ) . '"'
			. ' name="' . esc_attr( self::field_name( $key ) ) . '">'
			. esc_textarea( USHB_Settings::get( $key ) ) . '</textarea>';

		self::row( $label, $html, $help );
	}

	/**
	 * A number input.
	 *
	 * @param string $key    Setting key.
	 * @param string $label  Label text.
	 * @param int    $min    Minimum accepted value.
	 * @param int    $max    Maximum accepted value.
	 * @param string $help   Optional description.
	 * @param string $suffix Unit shown after the field.
	 */
	public static function number( $key, $label, $min, $max, $help = '', $suffix = '' ) {
		$html = '<input type="number" class="ushb-input ushb-input--num" min="' . intval( $min ) . '" max="' . intval( $max ) . '"'
			. ' name="' . esc_attr( self::field_name( $key ) ) . '"'
			. ' value="' . esc_attr( USHB_Settings::get( $key ) ) . '" data-key="' . esc_attr( $key ) . '">';

		if ( $suffix ) {
			$html .= '<span class="ushb-suffix">' . esc_html( $suffix ) . '</span>';
		}

		self::row( $label, $html, $help );
	}

	/**
	 * A select box.
	 *
	 * @param string $key     Setting key.
	 * @param string $label   Label text.
	 * @param array  $options Value mapped to label.
	 * @param string $help    Optional description.
	 */
	public static function select( $key, $label, $options, $help = '' ) {
		$current = USHB_Settings::get( $key );
		$html    = '<select class="ushb-input ushb-select" name="' . esc_attr( self::field_name( $key ) ) . '" data-key="' . esc_attr( $key ) . '">';

		foreach ( $options as $value => $text ) {
			$html .= '<option value="' . esc_attr( $value ) . '" ' . selected( $current, $value, false ) . '>' . esc_html( $text ) . '</option>';
		}

		$html .= '</select>';

		self::row( $label, $html, $help );
	}

	/**
	 * A segmented radio group.
	 *
	 * @param string $key     Setting key.
	 * @param string $label   Label text.
	 * @param array  $options Value mapped to label.
	 * @param string $help    Optional description.
	 */
	public static function segmented( $key, $label, $options, $help = '' ) {
		$current = USHB_Settings::get( $key );
		$html    = '<div class="ushb-seg">';

		foreach ( $options as $value => $text ) {
			$id    = 'ushb-' . sanitize_html_class( $key . '-' . $value );
			$html .= '<input type="radio" id="' . esc_attr( $id ) . '" name="' . esc_attr( self::field_name( $key ) ) . '"'
				. ' value="' . esc_attr( $value ) . '" ' . checked( $current, $value, false ) . ' data-key="' . esc_attr( $key ) . '">';
			$html .= '<label for="' . esc_attr( $id ) . '">' . esc_html( $text ) . '</label>';
		}

		$html .= '</div>';

		self::row( $label, $html, $help );
	}

	/**
	 * A colour picker.
	 *
	 * @param string $key   Setting key.
	 * @param string $label Label text.
	 * @param string $help  Optional description.
	 */
	public static function color( $key, $label, $help = '' ) {
		$value = USHB_Settings::get( $key );
		$html  = '<span class="ushb-color"><input type="color" name="' . esc_attr( self::field_name( $key ) ) . '"'
			. ' value="' . esc_attr( $value ) . '" data-key="' . esc_attr( $key ) . '">'
			. '<code>' . esc_html( $value ) . '</code></span>';

		self::row( $label, $html, $help );
	}

	/**
	 * A checkbox list of public post types.
	 */
	public static function post_type_picker() {
		self::register_field( 'post_types' );

		$selected = (array) USHB_Settings::get( 'post_types' );
		$types    = get_post_types( array( 'public' => true ), 'objects' );
		$html     = '<div class="ushb-checks">';

		foreach ( $types as $type ) {
			if ( 'attachment' === $type->name ) {
				continue;
			}

			$html .= '<label class="ushb-check"><input type="checkbox" name="ushb[post_types][]"'
				. ' value="' . esc_attr( $type->name ) . '" '
				. checked( in_array( $type->name, $selected, true ), true, false )
				. '><span>' . esc_html( $type->label ) . '</span></label>';
		}

		$html .= '</div>';

		self::row(
			__( 'Post types', 'unstoppable-share-buttons' ),
			$html,
			__( 'Bars appear on the single view of the types you tick here.', 'unstoppable-share-buttons' )
		);
	}

	/**
	 * The drag to order network picker for one placement.
	 *
	 * @param string $key   Setting key holding the network list.
	 * @param string $label Label above the picker.
	 * @param string $help  Optional description.
	 */
	public static function network_picker( $key, $label, $help = '' ) {
		self::register_field( $key );

		$chosen = USHB_Networks::filter_valid( USHB_Settings::get( $key ) );
		$all    = USHB_Networks::all();
		$groups = USHB_Networks::groups();
		$name   = 'ushb[' . $key . '][]';

		echo '<div class="ushb-picker" data-picker="' . esc_attr( $key ) . '" data-field="' . esc_attr( $name ) . '">';
		echo '<div class="ushb-picker__head"><h3>' . esc_html( $label ) . '</h3>';

		if ( $help ) {
			echo '<p class="ushb-help">' . esc_html( $help ) . '</p>';
		}

		echo '</div>';

		echo '<div class="ushb-picker__chosen">';
		echo '<p class="ushb-picker__title">' . esc_html__( 'Showing, in this order', 'unstoppable-share-buttons' )
			. ' <span class="ushb-count">' . esc_html( count( $chosen ) ) . '</span></p>';
		echo '<ul class="ushb-sortable" data-sortable>';

		foreach ( $chosen as $slug ) {
			if ( ! isset( $all[ $slug ] ) ) {
				continue;
			}

			self::picker_item( $slug, $all[ $slug ], $name, true );
		}

		echo '</ul>';
		echo '<p class="ushb-picker__empty"' . ( $chosen ? ' hidden' : '' ) . '>'
			. esc_html__( 'Nothing chosen yet. Pick a network below.', 'unstoppable-share-buttons' ) . '</p>';
		echo '</div>';

		echo '<div class="ushb-picker__available">';

		foreach ( $groups as $group_slug => $group_label ) {
			$items = array();

			foreach ( $all as $slug => $network ) {
				$network_group = isset( $network['group'] ) ? $network['group'] : 'social';

				if ( $network_group !== $group_slug || in_array( $slug, $chosen, true ) ) {
					continue;
				}

				$items[ $slug ] = $network;
			}

			if ( empty( $items ) ) {
				continue;
			}

			echo '<p class="ushb-picker__title">' . esc_html( $group_label ) . '</p>';
			echo '<ul class="ushb-grid" data-group="' . esc_attr( $group_slug ) . '">';

			foreach ( $items as $slug => $network ) {
				self::picker_item( $slug, $network, $name, false );
			}

			echo '</ul>';
		}

		echo '</div></div>';
	}

	/**
	 * One entry inside the network picker.
	 *
	 * @param string $slug    Network slug.
	 * @param array  $network Network definition.
	 * @param string $name    Field name for the hidden input.
	 * @param bool   $chosen  Whether it is currently selected.
	 */
	private static function picker_item( $slug, $network, $name, $chosen ) {
		$note = isset( $network['note'] ) ? $network['note'] : '';
		$icon = USHB_Icons::svg( isset( $network['icon'] ) ? $network['icon'] : 'more' );

		echo '<li class="ushb-chip ushb-chip--' . esc_attr( $slug ) . ( $chosen ? ' is-chosen' : '' ) . '"'
			. ' data-network="' . esc_attr( $slug ) . '"'
			. ' data-label="' . esc_attr( $network['label'] ) . '"'
			. ' data-group="' . esc_attr( isset( $network['group'] ) ? $network['group'] : 'social' ) . '"'
			. ( $chosen ? ' draggable="true"' : '' ) . '>';

		if ( $chosen ) {
			echo '<span class="ushb-chip__grip" aria-hidden="true"></span>';
		}

		echo '<span class="ushb-chip__icon">' . wp_kses( $icon, USHB_Icons::allowed_svg_tags() ) . '</span>';
		echo '<span class="ushb-chip__label">' . esc_html( $network['label'] ) . '</span>';

		if ( $note ) {
			echo '<span class="ushb-chip__note" title="' . esc_attr( $note ) . '" aria-label="' . esc_attr( $note ) . '">?</span>';
		}

		$action_label = $chosen
			? __( 'Remove', 'unstoppable-share-buttons' )
			: __( 'Add', 'unstoppable-share-buttons' );

		echo '<button type="button" class="ushb-chip__toggle" aria-label="' . esc_attr( $action_label . ': ' . $network['label'] ) . '">'
			. ( $chosen ? '&times;' : '+' ) . '</button>';

		if ( $chosen ) {
			echo '<input type="hidden" name="' . esc_attr( $name ) . '" value="' . esc_attr( $slug ) . '">';
		}

		echo '</li>';
	}

	/**
	 * Reports, in plain terms, whether a share bar can currently render.
	 *
	 * Written because "I cannot see the buttons" is otherwise a guessing game.
	 * Every condition the front end checks is listed here with its live value.
	 */
	public static function diagnostics() {
		$settings = USHB_Settings::all();

		$types    = (array) $settings['post_types'];
		$networks = USHB_Networks::filter_valid( $settings['inline_networks'] );

		$rows = array();

		$rows[] = array(
			'label' => __( 'Inline bar', 'unstoppable-share-buttons' ),
			'ok'    => ! empty( $settings['inline_enabled'] ),
			'value' => ! empty( $settings['inline_enabled'] ) ? __( 'Enabled', 'unstoppable-share-buttons' ) : __( 'Switched off, so no bar is added to post content', 'unstoppable-share-buttons' ),
		);

		$rows[] = array(
			'label' => __( 'Post types', 'unstoppable-share-buttons' ),
			'ok'    => ! empty( $types ),
			'value' => ! empty( $types ) ? implode( ', ', $types ) : __( 'None selected, so bars cannot appear anywhere', 'unstoppable-share-buttons' ),
		);

		$rows[] = array(
			'label' => __( 'Networks in the inline bar', 'unstoppable-share-buttons' ),
			'ok'    => ! empty( $networks ),
			/* translators: %d: number of networks. */
			'value' => ! empty( $networks ) ? sprintf( _n( '%d network', '%d networks', count( $networks ), 'unstoppable-share-buttons' ), count( $networks ) ) : __( 'Empty, and a bar with no networks renders nothing', 'unstoppable-share-buttons' ),
		);

		$rows[] = array(
			'label' => __( 'Hidden from logged-in users', 'unstoppable-share-buttons' ),
			'ok'    => empty( $settings['hide_for_logged_in'] ),
			'value' => empty( $settings['hide_for_logged_in'] ) ? __( 'No', 'unstoppable-share-buttons' ) : __( 'Yes, so you will not see bars while signed in', 'unstoppable-share-buttons' ),
		);

		$excluded = USHB_Settings::parse_ids( $settings['exclude_ids'] );
		$rows[]   = array(
			'label' => __( 'Excluded posts', 'unstoppable-share-buttons' ),
			'ok'    => true,
			'value' => $excluded ? implode( ', ', $excluded ) : __( 'None', 'unstoppable-share-buttons' ),
		);

		$rows[] = array(
			'label' => __( 'Front page', 'unstoppable-share-buttons' ),
			'ok'    => true,
			'value' => ! empty( $settings['show_on_front'] ) ? __( 'Bars allowed', 'unstoppable-share-buttons' ) : __( 'Off by default, so the front page shows no bars', 'unstoppable-share-buttons' ),
		);

		$rows[] = array(
			'label' => __( 'Archives and blog index', 'unstoppable-share-buttons' ),
			'ok'    => true,
			'value' => ! empty( $settings['show_on_archives'] ) ? __( 'Bars allowed', 'unstoppable-share-buttons' ) : __( 'Off by default, so post listings show no bars', 'unstoppable-share-buttons' ),
		);

		if ( class_exists( 'WooCommerce' ) ) {
			$rows[] = array(
				'label' => __( 'WooCommerce products', 'unstoppable-share-buttons' ),
				'ok'    => ! empty( $settings['woo_enabled'] ),
				'value' => ! empty( $settings['woo_enabled'] ) ? __( 'Enabled', 'unstoppable-share-buttons' ) : __( 'Switched off, so products show no bars', 'unstoppable-share-buttons' ),
			);
		} else {
			$rows[] = array(
				'label' => __( 'WooCommerce products', 'unstoppable-share-buttons' ),
				'ok'    => true,
				'value' => __( 'WooCommerce is not active', 'unstoppable-share-buttons' ),
			);
		}

		$blocking = array();

		if ( empty( $settings['inline_enabled'] ) ) {
			$blocking[] = __( 'the inline bar is switched off', 'unstoppable-share-buttons' );
		}

		if ( empty( $types ) ) {
			$blocking[] = __( 'no post types are selected', 'unstoppable-share-buttons' );
		}

		if ( empty( $networks ) ) {
			$blocking[] = __( 'the inline bar has no networks', 'unstoppable-share-buttons' );
		}

		echo '<table class="ushb-diag">';

		foreach ( $rows as $row ) {
			echo '<tr class="' . ( $row['ok'] ? 'is-ok' : 'is-bad' ) . '">';
			echo '<th>' . esc_html( $row['label'] ) . '</th>';
			echo '<td>' . esc_html( $row['value'] ) . '</td>';
			echo '</tr>';
		}

		echo '</table>';

		if ( empty( $blocking ) ) {
			echo '<p class="ushb-diag__verdict is-ok">'
				. esc_html__( 'A share bar should appear on the single view of the post types listed above.', 'unstoppable-share-buttons' )
				. '</p>';
		} else {
			echo '<p class="ushb-diag__verdict is-bad">'
				. esc_html(
					sprintf(
						/* translators: %s: list of reasons, already joined. */
						__( 'No bar can render right now because %s.', 'unstoppable-share-buttons' ),
						implode( __( ', and ', 'unstoppable-share-buttons' ), $blocking )
					)
				)
				. '</p>';
		}
	}

	/**
	 * The shortcode and template tag reference.
	 *
	 * Rendered on both the Placement and the Content tabs. Placement is where
	 * people look when they want to put a bar somewhere specific, and the
	 * reference was previously only on Content, where nobody found it.
	 */
	public static function shortcode_reference() {
		$rows = array(
			array(
				'[unstoppable_share]',
				__( 'A share bar. Accepts networks, shape, style, labels, align, size, palette, color, counts, heading and cta.', 'unstoppable-share-buttons' ),
			),
			array(
				'[unstoppable_share_button]',
				__( 'One button that opens the share dialog.', 'unstoppable-share-buttons' ),
			),
			array(
				'[unstoppable_share_quote]',
				__( 'A pull quote people can post in one click.', 'unstoppable-share-buttons' ),
			),
			array(
				'[unstoppable_share_ai]',
				__( 'The ask an AI row.', 'unstoppable-share-buttons' ),
			),
			array(
				'[unstoppable_share_count]',
				__( 'The recorded share total for a post.', 'unstoppable-share-buttons' ),
			),
			array(
				'ushb_share_bar()',
				__( 'Template tag for theme files. Takes the same arguments.', 'unstoppable-share-buttons' ),
			),
		);

		echo '<table class="ushb-table">
<tbody>';

		foreach ( $rows as $row ) {
			echo '<tr><td><code>' . esc_html( $row[0] ) . '</code></td>';
			echo '<td>' . esc_html( $row[1] ) . '</td></tr>';
		}

		echo '</tbody>
</table>';
	}

	/**
	 * A hidden input carrying the current tab through the save redirect.
	 *
	 * @param string $tab Tab slug.
	 */
	public static function tab_input( $tab ) {
		echo '<input type="hidden" name="ushb_tab" value="' . esc_attr( $tab ) . '">';
	}
}
