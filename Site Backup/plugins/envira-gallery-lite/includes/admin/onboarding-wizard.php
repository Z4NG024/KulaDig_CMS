<?php
/**
 * Envira Gallery Lite Onboarding Wizard
 *
 * @package Envira_Gallery_Lite
 */

// Exit if accessed directly.

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class that holds our setup wizard.
 *
 * @since 1.8.12
 */
class OnboardingWizard {

	/**
	 * Holds base singleton.
	 *
	 * @since 1.8.12
	 *
	 * @var object
	 */
	public $base = null;

	/**
	 * Class constructor.
	 *
	 * @since 1.8.12
	 */
	public function __construct() {
		if ( ! is_admin() || wp_doing_cron() || wp_doing_ajax() ) {
			return;
		}

		// Load the base class object.
		$this->base = Envira_Gallery_Lite::get_instance();
	}

	/**
	 * Setup our hooks.
	 */
	public function hooks() {
		add_action( 'admin_menu', [ $this, 'add_dashboard_page' ] );
		add_action( 'admin_head', [ $this, 'hide_dashboard_page_from_menu' ] );
		add_action( 'admin_init', [ $this, 'maybeload_onboarding_wizard' ] );
		// Ajax actions.
		add_action( 'wp_ajax_save_onboarding_data', [ $this, 'save_onboarding_data' ], 10, 1 );
		add_action( 'wp_ajax_install_recommended_plugins', [ $this, 'install_recommended_plugins' ], 10, 1 );
		add_action( 'wp_ajax_save_selected_addons', [ $this, 'save_selected_addons' ], 10, 1 );
	}

	/**
	 * Adds a dashboard page for our setup wizard.
	 *
	 * @since 1.8.12
	 *
	 * @return void
	 */
	public function add_dashboard_page() {
		add_dashboard_page( '', '', 'manage_options', 'envira-setup-wizard', '' );
	}

	/**
	 * Hide the dashboard page from the menu.
	 *
	 * @since 1.8.12
	 *
	 * @return void
	 */
	public function hide_dashboard_page_from_menu() {
		remove_submenu_page( 'index.php', 'envira-setup-wizard' );
	}

	/**
	 * Checks to see if we should load the setup wizard.
	 *
	 * @since 1.8.12
	 *
	 * @return void
	 */
	public function maybeload_onboarding_wizard() {
		// Don't load the interface if doing an ajax call.
		if ( wp_doing_ajax() || wp_doing_cron() ) {
			return;
		}

		// Check for wizard-specific parameter
		// Allow plugins to disable the setup wizard
		// Check if current user is allowed to save settings.
		if (
			! isset( $_GET['page'] ) || // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			'envira-setup-wizard' !== sanitize_text_field( wp_unslash( $_GET['page'] ) ) || // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			! current_user_can( 'manage_options' )
		) {
			return;
		}

		set_current_screen();

		// Remove an action in the Gutenberg plugin ( not core Gutenberg ) which throws an error.
		remove_action( 'admin_print_styles', 'gutenberg_block_editor_admin_print_styles' );

		// If we are redirecting, clear the transient so it only happens once.

		$this->load_onboarding_wizard();
	}

	/**
	 * Load the Onboarding Wizard template.
	 *
	 * @since 1.8.12
	 *
	 * @return void
	 */
	private function load_onboarding_wizard() {
		$this->enqueue_scripts();
		$this->onboarding_wizard_header();
		$this->onboarding_wizard_content();
		$this->onboarding_wizard_footer();
		exit;
	}

	/**
	 * Enqueue scripts for the setup wizard.
	 *
	 * @since 1.8.12
	 *
	 * @return void
	 */
	private function enqueue_scripts() {
		// We don't want any plugin adding notices to our screens. Let's clear them out here.
		remove_all_actions( 'admin_notices' );
		remove_all_actions( 'all_admin_notices' );

		wp_register_script( 'envira-onboarding-wizard', ENVIRA_LITE_URL . 'assets/js/min/onboarding-wizard-min.js', [ 'jquery' ], ENVIRA_LITE_VERSION, true );
		wp_localize_script(
			'envira-onboarding-wizard',
			'enviraOnboardingWizard',
			[
				'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
				'nonce'         => wp_create_nonce( 'enviraOnboardingCheck' ),
				'connect_nonce' => wp_create_nonce( 'envira_gallery_connect' ),
				'plugins_list'  => $this->get_installed_plugins(),
			]
		);
		wp_register_style( 'envira-onboarding-wizard', ENVIRA_LITE_URL . 'assets/css/onboarding-wizard.css', [], ENVIRA_LITE_VERSION );
		wp_enqueue_style( 'envira-onboarding-wizard' );
		wp_enqueue_style( 'common' );
		wp_enqueue_media();
	}

	/**
	 * Setup the wizard header.
	 *
	 * @since 1.8.12
	 *
	 * @return void
	 */
	private function onboarding_wizard_header() {
		?>
		<!DOCTYPE html>
		<html <?php language_attributes(); ?> dir="ltr">
		<head>
			<meta charset="<?php bloginfo( 'charset' ); ?>">
			<meta name="viewport" content="width=device-width, initial-scale=1">
			<title>
			<?php
			// translators: %s is the plugin name.
			printf( esc_html__( '%1$s &rsaquo; Onboarding Wizard', 'envira-gallery-lite' ), esc_html( 'Envira Gallery Lite' ) );
			?>
			</title>
		</head>
		<body class="" style="visibility: hidden;">
		<div class="envira-onboarding-wizard">

		<?php
	}

	/**
	 * Outputs the content of the current step.
	 *
	 * @since 1.8.12
	 *
	 * @return void
	 */
	public function onboarding_wizard_content() {
		?>
		<div class="envira-onboarding-wizard-wrapper ">
			<div class="envira-onboarding-wizard-intro " id="welcome">
				<?php $this->base->load_admin_partial( 'onboarding-wizard/welcome' ); ?>
			</div>
			<div class="envira-onboarding-wizard-pages" style="display: none">
				<!-- logo -->
				<img width="339" src="<?php echo esc_attr( ENVIRA_LITE_URL . 'assets/images/envira-logo-color.svg' ); ?>" alt="Envira Gallery" class="envira-onboarding-wizard-logo" style="width:339px;">
				<!-- Progress Bar  -->
				<div class="envira-onboarding-progressbar">
					<div class="envira-onboarding-progress" id="envira-onboarding-progress"></div>
					<div class="envira-onboarding-progress-step envira-onboarding-progress-step-active"></div>
					<div class="envira-onboarding-spacer"></div>
					<div class="envira-onboarding-progress-step" ></div>
					<div class="envira-onboarding-spacer"></div>
					<div class="envira-onboarding-progress-step" ></div>
					<div class="envira-onboarding-spacer"></div>
					<div class="envira-onboarding-progress-step" ></div>
					<div class="envira-onboarding-spacer"></div>
					<div class="envira-onboarding-progress-step" ></div>
				</div>
			<?php
			// Load template partials for each step based on URL hash.
			for ( $i = 1; $i <= 5; $i++ ) {
				$step = 'step-' . $i;
				$this->base->load_admin_partial( 'onboarding-wizard/' . $step );
			}
			?>
				<div class="envira-onboarding-close-and-exit">
					<a href="<?php echo esc_url( admin_url( '/edit.php?post_type=envira&page=envira-gallery-settings' ) ); ?>"><?php esc_html_e( 'Close and Exit Wizard Without Saving', 'envira-gallery-lite' ); ?></a>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Outputs the simplified footer used for the Onboarding Wizard.
	 *
	 * @since 1.8.12
	 *
	 * @return void
	 */
	public function onboarding_wizard_footer() {
		?>
		<?php

		wp_print_scripts( 'envira-onboarding-wizard' );
		do_action( 'admin_footer', '' );
		do_action( 'admin_print_footer_scripts' );
		?>
		</div>
		</body>
		</html>
		<?php
	}

	/**
	 * Get a list of installed recommended plugins and addons.
	 *
	 * @return array
	 */
	public function get_installed_plugins(): array {
		$addons = [
			'envira-albums'    => 'envira-albums/envira-albums.php',
			'envira-tags'      => 'envira-tags/envira-tags.php',
			'envira-proofing'  => 'envira-proofing/envira-proofing.php',
			'envira-videos'    => 'envira-videos/envira-videos.php',
			'envira-slideshow' => 'envira-slideshow/envira-slideshow.php',
		];

		$recommended_plugins = $this->get_recommended_plugins();

		$plugins = array_merge( $recommended_plugins, $addons );

		// Check if these plugins are installed already or not.
		$all_plugins = get_plugins();
		$installed   = [];

		foreach ( $plugins as $plugin ) {
			if ( in_array( $plugin, array_keys( $all_plugins ), true ) ) {
				// Get array key of $plugins.
				$installed[] = array_search( $plugin, $plugins, true );
			}
		}

		return $installed;
	}

	/**
	 * Get a list of recommended plugins on step 3.
	 *
	 * @return array
	 */
	public function get_recommended_plugins(): array {
		$plugins = [
			'all-in-one-seo-pack'            => 'all-in-one-seo-pack/all_in_one_seo_pack.php',
			'wpforms-lite'                   => 'wpforms-lite/wpforms.php',
			'google-analytics-for-wordpress' => 'google-analytics-for-wordpress/googleanalytics.php',
			'duplicator'                     => 'duplicator/duplicator.php',
			'wp-mail-smtp'                   => 'wp-mail-smtp/wp_mail_smtp.php',
		];

		return $plugins;
	}

	/**
	 * Check if a recommended plugin is installed.
	 *
	 * @param string $recommended The plugin slug.
	 *
	 * @return string
	 */
	public function is_recommended_plugin_installed( string $recommended ): string {
		// Check if these plugins are installed already or not.
		$all_plugins = get_plugins();
		$plugins     = $this->get_recommended_plugins();
		if ( strpos( $recommended, 'envira-' ) !== false ) {
			$plugin = $recommended . '/' . $recommended . '.php';
		} elseif ( array_key_exists( $recommended, $plugins ) ) {
			// check if key exists in the array.
			$plugin = $plugins[ $recommended ];
		} else {
			return '';
		}
		if ( in_array( $plugin, array_keys( $all_plugins ), true ) ) {
			return 'no-clicks disabled';
		}
		return '';
	}

	/**
	 * Get saved onboarding data.
	 *
	 * @param string $key The key to get the data.
	 *
	 * @return mixed
	 */
	public function get_onboarding_data( string $key ) {
		if ( ! empty( $key ) ) {
			$onboarding_data = get_option( 'envira_onboarding_data' );
			if ( ! empty( $onboarding_data ) && isset( $onboarding_data[ $key ] ) ) {
				return $onboarding_data[ $key ];
			}
		}
		return '';
	}

	/**
	 * Save the onboarding data.
	 *
	 * @return void
	 */
	public function save_onboarding_data() {

		// check for nonce enviraOnboardingCheck.
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'enviraOnboardingCheck' ) ) {
			wp_send_json_error( 'Invalid nonce' );
			wp_die();
		}

		// check if the current user can manage options.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'You do not have permission to save data' );
			wp_die();
		}

		if ( ! empty( $_POST['eow'] ) ) {
			// Sanitize data and merge to existing data.
			$onboarding_data = get_option( 'envira_onboarding_data', [] );

			$onboarding_data = $this->sanitize_and_assign( '_usage_tracking', 'sanitize_text_field', $onboarding_data );
			$onboarding_data = $this->sanitize_and_assign( '_email_address', 'sanitize_email', $onboarding_data );
			$onboarding_data = $this->sanitize_and_assign( '_email_opt_in', 'sanitize_text_field', $onboarding_data );
			$onboarding_data = $this->sanitize_and_assign( '_user_type', 'sanitize_text_field', $onboarding_data );

			$updated = update_option( 'envira_onboarding_data', $onboarding_data );

			if ( $updated ) {
				// Send data to Drip.
				$this->save_to_drip( $onboarding_data );
			}

			wp_send_json_success( 'Data saved successfully' );
			wp_die();
		}

		wp_send_json_error( 'Something went wrong. Please try again.' );
		wp_die();
	}

	/**
	 * Sanitize and assign the data.
	 *
	 * @param string $key The key to get the data.
	 * @param string $sanitize_function The sanitize function.
	 * @param array  $onboarding_data The onboarding data.
	 *
	 * @return array
	 */
	public function sanitize_and_assign( string $key, string $sanitize_function, array $onboarding_data ): array {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( isset( $_POST['eow'][ $key ] ) ) { // Nonce is verified in the parent function.
			// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$onboarding_data[ $key ] = $sanitize_function( wp_unslash( $_POST['eow'][ $key ] ) );
		} else {
			unset( $onboarding_data[ $key ] );
		}
		return $onboarding_data;
	}



	/**
	 * Save the onboarding data to Drip.
	 *
	 * @param array $onboarding_data The onboarding data.
	 *
	 * @return void
	 */
	public function save_to_drip( array $onboarding_data ) {

		$url = 'https://enviragallery.com/wp-json/envira/v1/get_opt_in_data';

		$email = sanitize_email( $onboarding_data['_email_address'] );

		if ( empty( $email ) ) {
			return;
		}

		$tags = [ 'envira-lite' ];

		if ( isset( $onboarding_data['_user_type'] ) ) {
			$tags[] = $onboarding_data['_user_type'];
		}

		$body_data = [
			'envira-drip-email' => base64_encode( $email ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
			'envira-drip-tags'  => $tags,
		];

		$body = wp_json_encode( $body_data );

		$args = [
			'method'      => 'POST',
			'headers'     => [
				'Content-Type' => 'application/json',
				'user-agent'   => 'ENVIRA/LITE/' . ENVIRA_LITE_VERSION . '; ' . get_bloginfo( 'url' ),
			],
			'body'        => $body,
			'timeout'     => '5', // Timeout in seconds.
			'redirection' => '5',
			'httpversion' => '1.0',
			'blocking'    => false,
			'data_format' => 'body',
		];

		$response = wp_remote_request( $url, $args );
	}

	/**
	 * Save selected addons to database.
	 */
	public function save_selected_addons() {
		// check for nonce enviraOnboardingCheck.
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'enviraOnboardingCheck' ) ) {
			wp_send_json_error( 'Invalid nonce' );
			wp_die();
		}

		// check if the current user can manage options.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'You do not have permission to save data' );
			wp_die();
		}

		if ( ! empty( $_POST['addons'] ) ) {

			$addons = explode( ',', sanitize_text_field( wp_unslash( $_POST['addons'] ) ) );

			// Sanitize data and merge to existing data.
			$onboarding_data = get_option( 'envira_onboarding_data' );
			if ( empty( $onboarding_data ) ) {
				$onboarding_data = [];
			}

			// Check if $addons has albums addon, then add tags addon too.
			if ( in_array( 'envira-albums', $addons, true ) ) {
				$addons[] = 'envira-tags';
			}

			// Save addons as _addons key.
			$onboarding_data['_addons'] = $addons;

			$updated = update_option( 'envira_onboarding_data', $onboarding_data );

			wp_send_json_success( 'Addons saved successfully' );
			wp_die();
		}

		wp_send_json_error( 'Something went wrong. Please try again.' );
		wp_die();
	}

	/**
	 * Install the selected addons.
	 *
	 * @param string $key The key to get the data.
	 */
	public function install_selected_addons( string $key ) {

		// check if the current user can manage options.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'You do not have permission to install plugins' );
			wp_die();
		}

		// Get addons data from onboarding_data.
		$onboarding_data = get_option( 'envira_onboarding_data' );

		if ( ! empty( $onboarding_data['_addons'] ) ) {
			$plugins = $onboarding_data['_addons'];
			// Install the addons.

			// Check if the user has valid license key.
			$addons      = new Envira_Gallery_Addons();
			$license     = $key;
			$addons_list = $addons->get_addons_data( $license );

			// Get the addons slugs from addons list.
			$addons_slug = wp_list_pluck( $addons_list, 'slug', 'slug' );
			$addons_url  = wp_list_pluck( $addons_list, 'url', 'slug' );

			// Install the plugins.
			foreach ( $plugins as $plugin ) {
				if ( '' !== $this->is_recommended_plugin_installed( $plugin ) ) {
					continue; // Skip the plugin if it is already installed.
				}

				// Check if the addon is available for this license key.
				if ( ! empty( $license ) && ! empty( $addons_list ) && ! in_array( $plugin, $addons_slug, true ) ) {
					continue;
				}

				// Get addon url by slug.
				if ( isset( $addons_url[ $plugin ] ) ) {
					$this->install_helper( $addons_url[ $plugin ] );
				}
			}
			// The success message will be sent from the ajax response.
		}
	}

	/**
	 * Install the recommended plugins and add-ons.
	 *
	 * @return void
	 */
	public function install_recommended_plugins() {
		// check for nonce enviraOnboardingCheck.
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'enviraOnboardingCheck' ) ) {
			wp_send_json_error( 'Invalid nonce' );
			wp_die();
		}

		// check if the current user can manage options.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'You do not have permission to install plugins' );
			wp_die();
		}

		if ( ! empty( $_POST['plugins'] ) ) {
			// Sanitize data, plugins is a string delimited by comma.

			$plugins = explode( ',', sanitize_text_field( wp_unslash( $_POST['plugins'] ) ) );
			// Install the plugins.
			foreach ( $plugins as $plugin ) {
				if ( '' !== $this->is_recommended_plugin_installed( $plugin ) ) {
					continue; // Skip the plugin if it is already installed.
				}
				// Generate the plugin URL by slug.
				$url = 'https://downloads.wordpress.org/plugin/' . $plugin . '.zip';
				$this->install_helper( $url );
			}
			wp_send_json_success( 'Installed the recommended plugins successfully.' );
			wp_die();
		}

		wp_send_json_error( 'Something went wrong. Please try again.' );
		wp_die();
	}

	/**
	 * Helper function to install the free plugins.
	 *
	 * @param string $download_url The download URL.
	 *
	 * @return void
	 */
	public function install_helper( string $download_url ) {

		if ( empty( $download_url ) ) {
			return;
		}

			global $hook_suffix;

			// Set the current screen to avoid undefined notices.
			set_current_screen();

			$method = '';
			$url    = esc_url( admin_url( 'index.php?page=envira-setup-wizard' ) );

			// Start output buffering to catch the filesystem form if credentials are needed.
			ob_start();
			$creds = request_filesystem_credentials( $url, $method, false, false, null );
		if ( false === $creds ) {
			$form = ob_get_clean();
			echo wp_json_encode( [ 'form' => $form ] );
			die;
		}

			// If we are not authenticated, make it happen now.
		if ( ! WP_Filesystem( $creds ) ) {
			ob_start();
			request_filesystem_credentials( $url, $method, true, false, null );
			$form = ob_get_clean();
			echo wp_json_encode( [ 'form' => $form ] );
			die;
		}

			// We do not need any extra credentials if we have gotten this far, so let's install the plugin.
			require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
			require_once plugin_dir_path( ENVIRA_LITE_FILE ) . 'includes/global/Installer_Skin.php';

			// Create the plugin upgrader with our custom skin.
			$skin      = new Envira_Lite_Installer_Skin();
			$installer = new Plugin_Upgrader( $skin );
			$installer->install( $download_url );

			// Flush the cache and return.
			wp_cache_flush();
	}
}
