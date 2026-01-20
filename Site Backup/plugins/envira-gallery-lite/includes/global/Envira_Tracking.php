<?php
/**
 * Tracking functions for reporting plugin usage to the EnviraGallery site.
 *
 * @since 1.8.12
 * @package     Envira
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Envira_Tracking
 *
 * This class is responsible for tracking usage of the Envira plugin and sending check-in data.
 */
class Envira_Tracking {

	/**
	 * The endpoint to send the checkin data to.
	 *
	 * @var string
	 */
	protected $endpoint = '';

	/**
	 * The user agent to send with the request.
	 *
	 * @var string
	 */
	protected $user_agent = '';

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->user_agent = 'Envira/' . ENVIRA_LITE_VERSION . '; ' . get_bloginfo( 'url' );
		$this->endpoint   = 'https://evusage.enviragallery.com/v1/envira-checkin/';
	}

	/**
	 * Register hooks.
	 */
	public function hooks() {
		$onboarding_data = get_option( 'envira_onboarding_data', [] );
		$enabled         = $onboarding_data['_usage_tracking'] ?? false;
		$enabled         = filter_var( $enabled, FILTER_VALIDATE_BOOLEAN );

		if ( ! $enabled ) {
			return;
		}

		add_action( 'admin_init', [ $this, 'schedule_send' ] );
		add_filter( 'cron_schedules', [ $this, 'add_schedules' ], 99 );
		add_action( 'envira_usage_tracking_cron', [ $this, 'send_checkin' ] );
	}

	/**
	 * Get the settings to send.
	 *
	 * @return array
	 */
	protected function get_settings() {
		return [
			'envira_gallery_shareasale_id' => get_option( 'envira_gallery_shareasale_id', '' ),
		];
	}

	/**
	 * Get the data to send
	 *
	 * @return array
	 */
	private function get_data() {
		$data = [];

		// Retrieve current theme info.
		$theme_data = wp_get_theme();

		$sites_count = 1;
		if ( is_multisite() ) {
			if ( function_exists( 'get_blog_count' ) ) {
				$sites_count = get_blog_count();
			} else {
				$sites_count = 'Not Set';
			}
		}

		$settings = $this->get_settings();

		$key_option = get_option( 'envira_gallery', [] );
		$key        = isset( $key_option['key'] ) ? "{$key_option['key']}" : '';

		$data['envira_version'] = ENVIRA_LITE_VERSION;

		$data['php_version']    = phpversion();
		$data['wp_version']     = get_bloginfo( 'version' );
		$data['server']         = $_SERVER['SERVER_SOFTWARE'] ?? 'CLI'; // phpcs:ignore
		$data['over_time']      = get_option( 'envira_over_time', [] );
		$data['multisite']      = is_multisite();
		$data['url']            = home_url();
		$data['themename']      = $theme_data->get( 'Name' );
		$data['themeversion']   = $theme_data->get( 'Version' );
		$data['email']          = get_bloginfo( 'admin_email' );
		$data['key']            = $key;
		$data['settings']       = $settings;
		$data['pro']            = true;
		$data['sites']          = $sites_count;
		$data['usagetracking']  = false;
		$data['usercount']      = function_exists( 'get_user_count' ) ? get_user_count() : 'Not Set';
		$data['timezoneoffset'] = wp_date( 'P' );

		// Not used on sol.
		$data['tracking_mode'] = '';
		$data['events_mode']   = '';
		$data['usesauth']      = '';
		$data['autoupdate']    = false;

		// Retrieve current plugin information.
		if ( ! function_exists( 'get_plugins' ) ) {
			include_once ABSPATH . '/wp-admin/includes/plugin.php';
		}

		$plugins        = array_keys( get_plugins() );
		$active_plugins = get_option( 'active_plugins', [] );

		foreach ( $plugins as $key => $plugin ) {
			if ( in_array( $plugin, $active_plugins, true ) ) {
				// Remove active plugins from list so we can show active and inactive separately.
				unset( $plugins[ $key ] );
			}
		}

		$data['active_plugins']   = $active_plugins;
		$data['inactive_plugins'] = $plugins;
		$data['locale']           = get_locale();

		return $data;
	}

	/**
	 * Send the checkin
	 *
	 * @return bool
	 */
	public function send_checkin() {
		$ignore_last_checkin = true;

		$home_url = trailingslashit( home_url() );
		if ( strpos( $home_url, 'enviragallery.com' ) !== false ) {
			return false;
		}

		// Send a maximum of once per week.
		$last_send = get_option( 'envira_usage_tracking_last_checkin' );
		if ( is_numeric( $last_send ) && $last_send > strtotime( '-1 week' ) && ! $ignore_last_checkin ) {
			return false;
		}

		$request = wp_remote_post(
			$this->endpoint,
			[
				'method'      => 'POST',
				'timeout'     => 5,
				'redirection' => 5,
				'httpversion' => '1.1',
				'blocking'    => false,
				'body'        => $this->get_data(),
				'user-agent'  => $this->user_agent,
			]
		);

		// If we have completed successfully, recheck in 1 week.
		update_option( 'envira_usage_tracking_last_checkin', time() );

		return true;
	}

	/**
	 * Schedule the checkin
	 *
	 * @return void
	 */
	public function schedule_send() {
		if ( wp_next_scheduled( 'envira_usage_tracking_cron' ) ) {
			return;
		}

		$tracking            = [];
		$tracking['day']     = wp_rand( 0, 6 );
		$tracking['hour']    = wp_rand( 0, 23 );
		$tracking['minute']  = wp_rand( 0, 59 );
		$tracking['second']  = wp_rand( 0, 59 );
		$tracking['offset']  = ( $tracking['day'] * DAY_IN_SECONDS );
		$tracking['offset'] += ( $tracking['hour'] * HOUR_IN_SECONDS );
		$tracking['offset'] += ( $tracking['minute'] * MINUTE_IN_SECONDS );
		$tracking['offset'] += $tracking['second'];

		$tracking['initsend'] = strtotime( 'next sunday' ) + $tracking['offset'];

		wp_schedule_event( $tracking['initsend'], 'weekly', 'envira_usage_tracking_cron' );
		update_option( 'envira_usage_tracking_config', wp_json_encode( $tracking ) );
	}

	/**
	 * Add weekly schedule
	 *
	 * @param array $schedules Array of schedules.
	 *
	 * @return array
	 */
	public function add_schedules( $schedules = [] ) {
		if ( isset( $schedules['weekly'] ) ) {
			return $schedules;
		}

		$schedules['weekly'] = [
			'interval' => 604800,
			'display'  => __( 'Once Weekly', 'envira-gallery-lite' ),
		];

		return $schedules;
	}
}
