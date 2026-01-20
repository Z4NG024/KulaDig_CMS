<?php
/**
 * Welcome class.
 *
 * @since 1.8.13
 *
 * @package Envira_Gallery
 * @author  Envira Gallery Team
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Menu_Nudge.
 *
 * @since 1.8.13
 */
class Menu_Nudge {

	/**
	 * Holds base singleton.
	 *
	 * @since 1.9.14
	 *
	 * @var object
	 */
	public $base = null;

	/**
	 * Primary class constructor.
	 *
	 * @since 1.9.15
	 */
	public function __construct() {

		if ( ! is_admin() || wp_doing_cron() || wp_doing_ajax() ) {
			return;
		}

		// Load the base class object.
		$this->base = \Envira_Gallery_Lite::get_instance();
	}

	/**
	 * Setup our hooks.
	 */
	public function hooks() {
		// ToolTip.
		add_action( 'adminmenu', [ $this, 'envira_get_admin_menu_tooltip' ] );
		// Hide ToolTip.
		add_action( 'wp_ajax_envira_hide_admin_menu_tooltip', [ $this, 'envira_hide_admin_menu_tooltip_callback' ] );
		// Add scripts and styles.
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_styles' ] );
		// Reload to add new gallery.
		add_action( 'wp_ajax_envira_redirect_to_add_new_gallery', [ $this, 'envira_redirect_to_add_new_gallery_callback' ] );
	}

	/**
	 * Register and enqueue addons page specific CSS.
	 *
	 * @since 1.8.1
	 */
	public function enqueue_admin_styles() {
		wp_register_style( '-menu-nudge', ENVIRA_LITE_URL . 'assets/css/menu-nudge.css', [], ENVIRA_LITE_VERSION );
		wp_register_script( '-menu-nudge-script', ENVIRA_LITE_URL . 'assets/js/min/menu-nudge-min.js', [ 'jquery' ], ENVIRA_LITE_VERSION, true );
	}

	/**
	 * Admin menu tooltip.
	 */
	public function envira_get_admin_menu_tooltip() {

		// Hide ToolTip if already gallery item created.
		$args = \get_posts(
			[
				'post_type'      => 'envira',
				'posts_per_page' => 1,
				'post_status'    => [ 'draft', 'publish' ],
			]
		);
		if ( ! empty( $args ) ) {
			return;
		}

		// Hide ToolTip if the user is not allowed to save settings.
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Hide ToolTip if the user has dismissed the tooltip within 7 days.
		$show_tooltip = get_option( 'envira_admin_menu_tooltip', 0 );

		if ( ! ( $show_tooltip && $show_tooltip + 7 * DAY_IN_SECONDS > time() ) ) {
			wp_enqueue_style( '-menu-nudge' );
			wp_enqueue_script( '-menu-nudge-script' );
			?>
		<div id="envira-admin-menu-tooltip" class="envira-admin-menu-tooltip-hide">
			<div class="envira-admin-menu-tooltip-header">
				<span class="envira-admin-menu-tooltip-icon"><span
						class="dashicons dashicons-megaphone"></span></span>
				<?php esc_html_e( 'Envira Gallery Alert!', 'envira-gallery-lite' ); ?>
				<span class="envira-admin-menu-tooltip-close"><span
						class="dashicons dashicons-dismiss"></span></span>
			</div>
			<div class="envira-admin-menu-tooltip-content">
				<?php esc_html_e( "👋 You're not showcasing any images on this website. Why not create a stunning gallery with Envira?", 'envira-gallery-lite' ); ?>
				<p>
					<button id="envira-admin-menu-launch-tooltip-button" class="button button-primary"><?php esc_html_e( 'Build a Gallery', 'envira-gallery-lite' ); ?></button>
				</p>
			</div>
		</div>
			<?php
		}
	}

	/**
	 * Hide the admin menu tooltip.
	 */
	public function envira_hide_admin_menu_tooltip_callback() {
		if ( current_user_can( 'manage_options' ) ) {
			update_option( 'envira_admin_menu_tooltip', time() );
		}
		wp_send_json_success( 'Option Added Successfully' );
		wp_die();
	}

	/**
	 * Reload to add new page.
	 */
	public function envira_redirect_to_add_new_gallery_callback() {
		if ( current_user_can( 'manage_options' ) ) {
			$url = admin_url( 'post-new.php?post_type=envira' );
			wp_send_json_success( [ 'redirect_url' => $url ] );
		} else {
			wp_send_json_error( 'Unauthorized' );
		}
		wp_die();
	}
}
