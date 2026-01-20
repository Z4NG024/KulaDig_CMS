<?php
/**
 * Envira Gallery Settings
 *
 * @package Envira Gallery Lite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use Envira\Admin\Envira_Permissions;

/**
 * Settings Class
 *
 * @since 1.8.7
 */
class Envira_Settings {

	/**
	 * Holds the submenu pagehook.
	 *
	 * @since 1.7.0
	 *
	 * @var string
	 */
	public $hook;

	/**
	 * Class Hooks
	 *
	 * @since 1.8.7
	 *
	 * @return void
	 */
	public function hooks() {
		add_action( 'admin_menu', [ $this, 'admin_menu' ], 11 );
		add_action( 'envira_gallery_tab_settings_general', [ $this, 'settings_general_tab' ] );
		add_action( 'envira_gallery_tab_settings_permissions', [ $this, 'settings_permissions_tab' ] );
		add_action( 'envira_gallery_tab_settings_licensing', [ $this, 'settings_licensing_tab' ] );
		add_action( 'envira_gallery_tab_settings_convert_to_envira', [ $this, 'settings_convert_to_envira_tab' ] );
	}

	/**
	 * Adds admin menus
	 *
	 * @since 1.8.7
	 *
	 * @return void
	 */
	public function admin_menu() {
		global $submenu;

		$whitelabel = apply_filters( 'envira_whitelabel', false ) ? '' : __( 'Envira Gallery ', 'envira-gallery-lite' );

		// Register the submenus.
		$this->hook = add_submenu_page(
			'edit.php?post_type=envira',
			esc_html__( 'Settings', 'envira-gallery-lite' ),
			esc_html__( 'Settings', 'envira-gallery-lite' ),
			apply_filters( 'envira_gallery_menu_cap', 'manage_options' ),
			'envira-gallery-settings',
			[ $this, 'page' ]
		);

		// If successful, load admin assets only on that page and check for tabs refresh.
		if ( ! $this->hook ) {
			return;
		}

		add_action( 'load-' . $this->hook, [ $this, 'permissions_settings_save' ] );
	}

	/**
	 * Callback for getting all the settings tabs for Envira lite.
	 *
	 * @since 1.8.9
	 *
	 * @return array Array of tab information.
	 */
	public function get_envira_settings_tab_nav() {

		$tabs = [
			'general'           => __( 'General', 'envira-gallery-lite' ), // This tab is required. DO NOT REMOVE VIA FILTERING.
			'permissions'       => __( 'Permissions', 'envira-gallery-lite' ),
			'licensing'         => __( 'Image Licensing', 'envira-gallery-lite' ),
			'convert_to_envira' => __( 'Convert to Envira', 'envira-gallery-lite' ),

		];
		$tabs = apply_filters( 'envira_gallery_settings_tab_nav', $tabs );

		return $tabs;
	}

	/**
	 * Settings Page.
	 *
	 * @since 1.8.7
	 *
	 * @return void
	 */
	public function page() {
		?>

		<!-- Tabs -->
		<h2 id="envira-tabs-nav" class="envira-tabs-nav" data-container="#envira-gallery-settings" data-update-hashbang="1">
			<?php
			$i = 0;
			foreach ( (array) $this->get_envira_settings_tab_nav() as $id => $title ) {
				$class = ( 0 === $i ? 'envira-active' : '' );
				?>
				<a class="nav-tab <?php echo esc_html( $class ); ?>" href="#envira-tab-<?php echo esc_attr( $id ); ?>" title="<?php echo esc_attr( $title ); ?>">
					<?php echo wp_kses( $title, [ 'span' => [ 'class' => [] ] ] ); ?>
				</a>
				<?php
				++$i;
			}
			?>
		</h2>

		<!-- Tab Panels -->
		<div id="envira-gallery-settings" class="wrap">
			<h1 class="envira-hideme"></h1>
			<div class="envira-gallery envira-clear">
				<div id="envira-tabs" class="envira-clear" data-navigation="#envira-tabs-nav">
					<?php
					$i = 0;
					foreach ( (array) $this->get_envira_settings_tab_nav() as $id => $title ) {
						$class = ( 0 === $i ? 'envira-active' : '' );
						?>
						<div id="envira-tab-<?php echo esc_attr( $id ); ?>" class="envira-tab envira-clear <?php echo esc_attr( $class ); ?>">
							<?php do_action( 'envira_gallery_tab_settings_' . $id ); ?>
						</div>
						<?php
						++$i;
					}
					?>
				</div>
			</div>
		</div>
		<?php
	}
		/**
		 * Callback for displaying the UI for general settings tab.
		 *
		 * @since 1.8.9
		 */
	public function settings_general_tab() {
		?>
		<div id="envira-settings-general">
		<div class="envira-settings-tab">
			<table class="form-table">
				<tbody>
					<tr id="envira-image-gallery-settings-title">
						<th scope="row" colspan="2">
							<h3><?php esc_html_e( 'License', 'envira-gallery-lite' ); ?></h3>
							<p><?php esc_html_e( 'Your license key provides access to updates and add-ons.', 'envira-gallery-lite' ); ?></p>
						</th>
					</tr>
					<tr id="envira-settings-key-box" class="title">
						<th scope="row">
							<label for="envira-settings-key"><?php esc_html_e( ' License Key', 'envira-gallery-lite' ); ?></label>
						</th>
						<td>
							<p><?php esc_html_e( "You're using Envira Gallery Lite - no license needed. Enjoy! 🙂", 'envira-gallery-lite' ); ?></p>

							<p>
						<?php
							printf(
								// Translators: %1$s - Opening anchor tag, do not translate. %2$s - Closing anchor tag, do not translate.
								esc_html__( 'To unlock more features consider %1$supgrading to PRO%2$s.', 'envira-gallery-lite' ),
								'<strong><a href="' . esc_url( Envira_Gallery_Common_Admin::get_instance()->get_upgrade_link( 'https://enviragallery.com/pricing', 'settingspage', 'upgradingtopro' ) ) . '" target="_blank" rel="noopener noreferrer">',
								'</a></strong>'
							);
						?>
							</p>
							<p>
							<?php
							printf(
								// Translators: %1$s - Opening span tag, do not translate. %2$s - Closing span tag, do not translate.
								esc_html__( 'As a valued Envira Gallery Lite user you receive %1$s 50%% off%2$s, automatically applied at checkout', 'envira-gallery-lite' ),
								'<span class="envira-green"><strong>',
								'</strong></span>'
							);
							?>
							</p>
							<hr />
							<form id="envira-settings-verify-key" method="post">
								<p class="description"><?php esc_html_e( 'Already purchased? Simply enter your license key below to enable Envira Gallery PRO!', 'envira-gallery-lite' ); ?></p>
								<input placeholder="<?php esc_attr_e( 'Paste license key here', 'envira-gallery-lite' ); ?>" type="password" name="envira-license-key" id="envira-settings-key" value="" />
								<button type="button " class="button envira-button-dark envira-gallery-verify-submit primary" id="envira-gallery-settings-connect-btn">
					<?php esc_html_e( 'Verify Key', 'envira-gallery-lite' ); ?>
				</button>


							</form>
						</td>
					</tr>
				<tr>
					<th><?php esc_html_e( 'Setup Wizard', 'envira-gallery-lite' ); ?></th>
					<td><a href="<?php echo esc_url( '/wp-admin/index.php?page=envira-setup-wizard' ); ?>" title="<?php esc_html_e( 'Setup Wizard', 'envira-gallery-lite' ); ?>" class="button envira-button-dark envira-button-primary" ><?php esc_html_e( 'Launch Setup Wizard', 'envira-gallery-lite' ); ?></a></td>
				</tr>

				</tbody>
			</table>

			<!-- <hr /> -->
		</div>
		</div>
		<?php
	}

	/**
	 * Permissions Setting function.
	 *
	 * @since 1.8.9
	 *
	 * @access public
	 * @return void
	 */
	public function permissions_settings_save() {
		// Check nonce is valid.

		if ( empty( $_POST ) ) {
			return;
		}

		if ( ! isset( $_POST['envira-permissions-nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['envira-permissions-nonce'] ), 'envira-permissions-nonce' ) ) {
			add_action( 'envira_gallery_settings_permissions_tab_notice', [ $this, 'permissions_settings_nonce_notice' ] );
			return;
		}

		$permissions = new Envira_Permissions();
		$fields      = $permissions->get_fields();

		foreach ( $fields as $field ) {
			// The method sanitize_field_data() is already sanitizing the data.

			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$permissions_data[ $field ] = isset( $_POST[ $field ] ) ? $permissions->sanitize_field_data( wp_unslash( $_POST[ $field ] ) ) : '';
			if ( empty( $permissions_data[ $field ] ) ) {
				continue;
			}
			$permissions->add_capabilities_by_permissions( $field, $permissions_data[ $field ] );
			update_option( $field, maybe_serialize( $permissions_data[ $field ] ) );
		}

		do_action( 'envira_permissions_mapper' );

		// Output success notice.
		add_action( 'envira_gallery_settings_permissions_tab_notice', [ $this, 'permissions_settings_saved_notice' ] );
	}

	/**
	 * Outputs a message to tell the user that the nonce field is invalid
	 *
	 * @since 1.9.11
	 */
	public function permissions_settings_nonce_notice() {

		?>
		<div class="notice error below-h2">
			<p><?php esc_html_e( 'The nonce field is invalid.', 'envira-gallery-lite' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Outputs a message to tell the user that the permissions has been saved
	 *
	 * @since 1.8.9
	 */
	public function permissions_settings_saved_notice() {

		?>
		<div class="notice updated below-h2">
			<p><?php esc_html_e( 'Permissions updated successfully!', 'envira-gallery-lite' ); ?></p>
		</div>
		<?php
	}


	/**
	 * Callback for displaying the UI for permissions settings tab.
	 *
	 * @since 1.8.9
	 */
	public function settings_permissions_tab() {

		$permissions         = new Envira_Permissions();
		$default_permissions = get_option( 'envira_permissions_set_default', false );
		?>
		<div id="envira-settings-permissions" class="envira-settings-tab">
			<?php
			// Output notices.
			do_action( 'envira_gallery_settings_permissions_tab_notice' );
			?>


			<form action="edit.php?post_type=envira&page=envira-gallery-settings#!envira-tab-permissions" method="post">

				<table class="form-table">
					<tbody>
					<tr id="envira-settings-permissions-create" class="envira-settings-roles-select">
						<th scope="row">
							<label for="envira_permissions_create"><?php esc_html_e( 'Create Galleries', 'envira-gallery-lite' ); ?></label>
						</th>
						<td>
							<?php $permissions->render_permissions_field( 'envira_permissions_create' ); ?>
							<p class="description"><?php esc_html_e( 'Users that have at least one of these roles will be able to create galleries.', 'envira-gallery-lite' ); ?></p>
						</td>
					</tr>
					<tr id="envira-settings-permissions-edit" class="envira-settings-roles-select">
						<th scope="row">
							<label for="envira_permissions_edit"><?php esc_html_e( 'Edit Galleries', 'envira-gallery-lite' ); ?></label>
						</th>
						<td>
							<?php $permissions->render_permissions_field( 'envira_permissions_edit' ); ?>
							<p class="description"><?php esc_html_e( 'Users that have at least one of these roles will be able to edit/update galleries.', 'envira-gallery-lite' ); ?></p>
						</td>
					</tr>
					<tr id="envira-settings-permissions-delete" class="envira-settings-roles-select">
						<th scope="row">
							<label for="envira-permissions-delete"><?php esc_html_e( 'Delete Galleries', 'envira-gallery-lite' ); ?></label>
						</th>
						<td>
							<?php $permissions->render_permissions_field( 'envira_permissions_delete' ); ?>
							<p class="description"><?php esc_html_e( 'Users that have at least one of these roles will be able to delete galleries.', 'envira-gallery-lite' ); ?></p>
						</td>
					</tr>
					<tr id="envira-settings-permissions-edit-others" class="envira-settings-roles-select">
						<th scope="row">
							<label for="envira_permissions_edit_others"><?php esc_html_e( 'Edit Other\'s Galleries', 'envira-gallery-lite' ); ?></label>
						</th>
						<td>
							<?php $permissions->render_permissions_field( 'envira_permissions_edit_others' ); ?>
							<p class="description"><?php esc_html_e( 'Users that have at least one of these roles will be able to edit other\'s galleries.', 'envira-gallery-lite' ); ?></p>
						</td>
					</tr>
					<?php do_action( 'envira_permissions_addons_tab' ); ?>
					<tr>
						<?php wp_nonce_field( 'envira-permissions-nonce', 'envira-permissions-nonce' ); ?>
						<th scope="row"><?php submit_button( __( 'Save', 'envira-gallery-lite' ), 'primary', 'envira-gallery-verify-submit', false ); ?></th>
						<td>&nbsp;</td>
					</tr>
					</tbody>
				</table>
			</form>
			<div
				class="dialog-container envira-dialog-container"
				aria-hidden="true"
				id="envira-permissions-dialog-id"
				role="alertdialog"
			>
				<div class="dialog-overlay"></div>
				<div class="dialog-content" role="document">
					<h3 id="envira-permissions-dialog-title-id">Heads up!</h3>
					<div id="envira-permissions-alert">Permissions message here</div>
					<div class="envira-dialog-actions clear">
						<button type="button" class="envira-dialog-btn primary envira-permissions-yes" data-a11y-dialog-hide aria-label="Close dialog">OK</button>
						<button type="button" class="envira-dialog-btn envira-permissions-cancel" data-a11y-dialog-hide aria-label="Cancel">Cancel</button>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Outputs settings screen for the licensing tab.
	 *
	 * @since 1.8.12-0
	 */
	public function settings_licensing_tab() {
		// get instance of the plugin.
		$envira = Envira_Gallery_Lite::get_instance();
		$envira->load_admin_partial( 'licensing' );
	}

	/**
	 * Outputs settings screen for the convert to envira tab.
	 *
	 * @return void
	 */
	public function settings_convert_to_envira_tab() {
		?>
		<div class="envira-convert-to-envira-tab">
			<div class="envira-settings-tab">
				<table class="form-table">
					<tbody>
						<tr id="convert-to-envira-tab-content">
							<th scope="row" colspan="2">
								<h3><?php esc_html_e( 'Convert Galleries', 'envira-gallery-lite' ); ?></h3>
								<p><?php esc_html_e( 'With a single click, easily convert all of your website’s WordPress galleries to Envira Galleries! This will let you get features like drag and drop ordering, image layouts, and much more.', 'envira-gallery-lite' ); ?></p>
								<p class="envira-convert-important-note"><?php echo wp_kses( __( 'Important: Converting your galleries to Envira Gallery is not reversible. <em>We strongly recommend making a full backup of your website first before making changes.</em>', 'envira-gallery-lite' ), [ 'em' => [] ] ); ?></p>
								<?php
									Envira_Gallery_Common_Admin::get_instance()->envira_render_post_types_dropdown();
									$convert_button = __( 'Convert Galleries', 'envira-gallery-lite' );
								?>
								<a href="javascript:void(0);" title="<?php echo esc_attr( $convert_button ); ?>" class="button button-primary convert-envira-gallery-tab-btn" data-converting="<?php esc_attr_e( 'Converting...', 'envira-gallery-lite' ); ?>"><?php echo esc_attr( $convert_button ); ?></a>
								<p class="envira-convert-gallery-message"></p>
								<div class="envira-convert-process-logs"></div>
							</th>
						</tr>
					</tbody>
				</table>
			</div>
		</div>
		<?php
	}
}
