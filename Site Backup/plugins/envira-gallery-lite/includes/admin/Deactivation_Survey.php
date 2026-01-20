<?php
/**
 * Envira Gallery Deactivation Survey.
 *
 * This prompts the user for more details when they deactivate the plugin.
 *
 * @since 1.11.2
 *
 * @package Envira_Gallery
 * @author  Envira Gallery Team
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * This class is used to prompt users for feedback when they deactivate the plugin.
 *
 * @since 1.11.2
 */
class Deactivation_Survey {
	/**
	 * The API URL we are calling.
	 *
	 * @since 1.11.2
	 *
	 * @var string
	 */
	public $api_url = 'https://enviragallery.com/wp-json/am-deactivate-survey/v1/deactivation-data';

	/**
	 * Name for this plugin.
	 *
	 * @since 1.11.2
	 *
	 * @var string
	 */
	public $name = 'Envira Gallery Lite';

	/**
	 * Unique slug for this plugin.
	 *
	 * @since 1.11.2
	 *
	 * @var string
	 */
	public $plugin = 'envira-gallery-lite';

	/**
	 * Primary class constructor.
	 *
	 * @since 1.11.2
	 */
	public function __construct() {
		$this->api_url = $this->get_api_url();

		// Validate the URL.
		$this->api_url = filter_var( $this->api_url, FILTER_VALIDATE_URL );

		// If the URL is not valid, return.
		if ( empty( $this->api_url ) ) {
			return;
		}

		// Don't run deactivation survey on dev sites.
		if ( $this->is_dev_url() ) {
			return;
		}

		add_action( 'admin_print_scripts', [ $this, 'js' ], 20 );
		add_action( 'admin_print_scripts', [ $this, 'css' ] );
		add_action( 'admin_footer', [ $this, 'modal' ] );
	}


	/**
	 * Returns the URL of the remote endpoint.
	 *
	 * @since 1.11.2
	 *
	 * @return string The URL.
	 */
	public function get_api_url() {
		if ( defined( 'ENVIRA_GALLERY_DEACTIVATION_SURVEY_URL' ) ) {
			return ENVIRA_GALLERY_DEACTIVATION_SURVEY_URL;
		}
		return $this->api_url;
	}

	/**
	 * Checks if current site is a development one.
	 *
	 * @since 1.11.2
	 * @return bool
	 */
	public function is_dev_url() {
		// If it is an AM dev site, return false, so we can see them on our dev sites.
		if ( defined( 'ENVIRA_GALLERY_DEV_MODE' ) && ENVIRA_GALLERY_DEV_MODE ) {
			return false;
		}

		$url          = network_site_url( '/' );
		$is_local_url = false;

		// Trim it up.
		$url = strtolower( trim( $url ) );

		// Need to get the host...so let's add the scheme so we can use parse_url.
		if ( false === strpos( $url, 'http://' ) && false === strpos( $url, 'https://' ) ) {
			$url = 'http://' . $url;
		}
		$url_parts = wp_parse_url( $url );
		$host      = ! empty( $url_parts['host'] ) ? $url_parts['host'] : false;

		if ( ! empty( $url ) && ! empty( $host ) ) {
			if ( false !== ip2long( $host ) ) {
				if ( ! filter_var( $host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) ) {
					$is_local_url = true;
				}
			} elseif ( 'localhost' === $host ) {
				$is_local_url = true;
			}

			$tlds_to_check = array( '.dev', '.local', ':8888' );
			foreach ( $tlds_to_check as $tld ) {
				if ( false !== strpos( $host, $tld ) ) {
					$is_local_url = true;
					continue;
				}
			}

			if ( substr_count( $host, '.' ) > 1 ) {
				$subdomains_to_check = array( 'dev.', '*.staging.', 'beta.', 'test.' );
				foreach ( $subdomains_to_check as $subdomain ) {
					$subdomain = str_replace( '.', '(.)', $subdomain );
					$subdomain = str_replace( array( '*', '(.)' ), '(.*)', $subdomain );
					if ( preg_match( '/^(' . $subdomain . ')/', $host ) ) {
						$is_local_url = true;
						continue;
					}
				}
			}
		}
		return $is_local_url;
	}

	/**
	 * Checks if current admin screen is the plugins page.
	 *
	 * @since 1.11.2
	 *
	 * @return bool True if it is, false if not.
	 */
	public function is_plugin_page() {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : false;
		if ( empty( $screen ) ) {
			return false;
		}

		return (
			! empty( $screen->id ) &&
			in_array(
				$screen->id,
				array( 'plugins', 'plugins-network' ),
				true
			)
		);
	}

	/**
	 * Survey javascript.
	 *
	 * @since 1.11.2
	 */
	public function js() {

		if ( ! $this->is_plugin_page() ) {
			return;
		}

		?>
		<script type="text/javascript">
			window.addEventListener("load", function() {
				var deactivateLink = document.querySelector('#the-list [data-slug="<?php echo esc_html( $this->plugin ); ?>"] span.deactivate a') ||
					document.querySelector('#deactivate-<?php echo esc_html( $this->plugin ); ?>'),
					overlay = document.querySelector('#am-deactivate-survey-<?php echo esc_html( $this->plugin ); ?>'),
					form = overlay.querySelector('form'),
					formOpen = false;

				deactivateLink.addEventListener('click', function(event) {
					event.preventDefault();
					overlay.style.display = 'table';
					formOpen = true;
					form.querySelector('.am-deactivate-survey-option:first-of-type input[type=radio]').focus();
				});

				form.addEventListener('change', function(event) {
					if (event.target.matches('input[type=radio]')) {
						event.preventDefault();
						Array.from(form.querySelectorAll('input[type=text], .error')).forEach(function(el) { el.style.display = 'none'; });
						Array.from(form.querySelectorAll('.am-deactivate-survey-option')).forEach(function(el) { el.classList.remove('selected'); });
						var option = event.target.closest('.am-deactivate-survey-option');
						option.classList.add('selected');

						var otherField = option.querySelector('input[type=text]');
						if (otherField) {
							otherField.style.display = 'block';
							otherField.focus();
						}
					}
				});

				form.addEventListener('click', function(event) {
					if (event.target.matches('.am-deactivate-survey-deactivate')) {
						event.preventDefault();
						window.location.href = deactivateLink.getAttribute('href');
					}
				});

				var closeButton = form.querySelector('.am-deactivate-survey-close');
				if (closeButton) {
					closeButton.addEventListener('click', function(event) {
						event.preventDefault();
						overlay.style.display = 'none';
						formOpen = false;
					});
				}

				form.addEventListener('submit', function(event) {
					event.preventDefault();
					if (!form.querySelector('input[type=radio]:checked')) {
						if(!form.querySelector('span[class="error"]')) {
							form.querySelector('.am-deactivate-survey-footer')
							.insertAdjacentHTML('afterbegin', '<span class="error"><?php echo esc_js( __( 'Please select an option', 'envira-gallery-lite' ) ); ?></span>');
						}
						return;
					}

					// Disable submit button to prevent multiple clicks
					var submitBtn = form.querySelector('.am-deactivate-survey-submit');
					submitBtn.disabled = true;
					submitBtn.textContent = '<?php echo esc_js( __( 'Submitting...', 'envira-gallery-lite' ) ); ?>';

					var selected = form.querySelector('.selected');
					var otherField = selected.querySelector('input[type=text]');
					var data = {
						code: selected.querySelector('input[type=radio]').value,
						reason: selected.querySelector('.am-deactivate-survey-option-reason').textContent,
						details: otherField ? otherField.value : '',
						site: '<?php echo esc_url( home_url() ); ?>',
						plugin: '<?php echo esc_html( $this->plugin ); ?>'
					}

					var submitSurvey = fetch('<?php echo esc_url( $this->api_url ); ?>', {
						method: 'POST',
						body: JSON.stringify(data),
						headers: { 'Content-Type': 'application/json' }
					});

					submitSurvey.finally(function() {
						submitBtn.textContent = '<?php echo esc_js( __( 'Deactivating...', 'envira-gallery-lite' ) ); ?>';
						window.location.href = deactivateLink.getAttribute('href');
					});
				});

				document.addEventListener('keyup', function(event) {
					if (27 === event.keyCode && formOpen) {
						overlay.style.display = 'none';
						formOpen = false;
						deactivateLink.focus();
					}
				});
			});
		</script>
		<?php
	}

	/**
	 * Survey CSS.
	 *
	 * @since 1.11.2
	 */
	public function css() {

		if ( ! $this->is_plugin_page() ) {
			return;
		}
		?>
		<style type="text/css">
			.am-deactivate-survey-modal {
				display: none;
				table-layout: fixed;
				position: fixed;
				z-index: 9999;
				width: 100%;
				height: 100%;
				text-align: center;
				font-size: 14px;
				top: 0;
				left: 0;
				background: rgba(0,0,0,0.8);
			}
			.am-deactivate-survey-wrap {
				display: table-cell;
				vertical-align: middle;
			}
			.am-deactivate-survey {
				background-color: #fff;
				max-width: 550px;
				margin: 0 auto;
				padding: 30px;
				text-align: left;
			}
			.am-deactivate-survey .error {
				display: block;
				color: red;
				margin: 0 0 10px 0;
			}
			.am-deactivate-survey-title {
				display: block;
				font-size: 18px;
				font-weight: 700;
				text-transform: uppercase;
				border-bottom: 1px solid #ddd;
				padding: 0 0 18px 0;
				margin: 0 0 18px 0;
			}
			.am-deactivate-survey-title span {
				color: #999;
				margin-right: 10px;
			}
			.am-deactivate-survey-desc {
				display: block;
				font-weight: 600;
				margin: 0 0 18px 0;
			}
			.am-deactivate-survey-option {
				margin: 0 0 10px 0;
			}
			.am-deactivate-survey-option-input {
				margin-right: 10px !important;
			}
			.am-deactivate-survey-option-details {
				display: none;
				width: 90%;
				margin: 10px 0 0 30px;
			}
			.am-deactivate-survey-footer {
				margin-top: 18px;
			}
			.am-deactivate-survey-deactivate {
				float: right;
				font-size: 13px;
				color: #ccc;
				text-decoration: none;
				padding-top: 7px;
			}
			.am-deactivate-survey-close {
				position: absolute;
				top: 15px;
				right: 15px;
				background: none;
				border: none;
				cursor: pointer;
				padding: 5px;
				color: #999;
				font-size: 20px;
				line-height: 1;
			}
			.am-deactivate-survey-close:hover {
				color: #666;
			}
			.am-deactivate-survey-close .dashicons {
				width: 20px;
				height: 20px;
				font-size: 20px;
			}
			.am-deactivate-survey {
				position: relative;
			}
		</style>
		<?php
	}

	/**
	 * Survey modal.
	 *
	 * @since 1.11.2
	 */
	public function modal() {

		if ( ! $this->is_plugin_page() ) {
			return;
		}

		$options = array(
			1 => array(
				'title' => esc_html__( 'I no longer need the plugin', 'envira-gallery-lite' ),
			),
			2 => array(
				'title'   => esc_html__( 'I\'m switching to a different plugin', 'envira-gallery-lite' ),
				'details' => esc_html__( 'Please share which plugin', 'envira-gallery-lite' ),
			),
			3 => array(
				'title'   => esc_html__( 'I couldn\'t get the plugin to work', 'envira-gallery-lite' ),
				'details' => esc_html__( 'We\'re sorry to hear. Can you let us know what didn\'t work?', 'envira-gallery-lite' ),
			),
			4 => array(
				'title' => esc_html__( 'It\'s a temporary deactivation', 'envira-gallery-lite' ),
			),
			5 => array(
				'title'   => esc_html__( 'Other', 'envira-gallery-lite' ),
				'details' => esc_html__( 'Please share the reason', 'envira-gallery-lite' ),
			),
		);
		?>
		<div class="am-deactivate-survey-modal" id="am-deactivate-survey-<?php echo esc_attr( $this->plugin ); ?>">
			<div class="am-deactivate-survey-wrap">
				<form class="am-deactivate-survey" method="post">
					<button type="button" class="am-deactivate-survey-close" aria-label="<?php echo esc_attr__( 'Close survey', 'envira-gallery-lite' ); ?>">
						<span class="dashicons dashicons-no-alt"></span>
					</button>
					<span class="am-deactivate-survey-title"><span class="dashicons dashicons-testimonial"></span><?php echo ' ' . esc_html__( 'Quick Feedback', 'envira-gallery-lite' ); ?></span>
					<span class="am-deactivate-survey-desc"><?php printf( esc_html__( 'If you have a moment, please share why you are deactivating %s:', 'envira-gallery-lite' ), $this->name ); // phpcs:ignore ?></span>
					<div class="am-deactivate-survey-options">
						<?php foreach ( $options as $id => $option ) : ?>
							<div class="am-deactivate-survey-option">
								<label for="am-deactivate-survey-option-<?php echo esc_attr( $this->plugin ); ?>-<?php echo esc_attr( $id ); ?>" class="am-deactivate-survey-option-label">
									<input id="am-deactivate-survey-option-<?php echo esc_attr( $this->plugin ); ?>-<?php echo esc_attr( $id ); ?>" class="am-deactivate-survey-option-input" type="radio" name="code" value="<?php echo esc_attr( $id ); ?>" />
									<span class="am-deactivate-survey-option-reason"><?php echo esc_html( $option['title'] ); ?></span>
								</label>
								<?php if ( ! empty( $option['details'] ) ) : ?>
									<input class="am-deactivate-survey-option-details" type="text" placeholder="<?php echo esc_attr( $option['details'] ); ?>" />
								<?php endif; ?>
							</div>
						<?php endforeach; ?>
					</div>
					<div class="am-deactivate-survey-footer">
						<button type="submit" class="am-deactivate-survey-submit button button-primary button-large"><?php printf( esc_html__( 'Submit %s Deactivate', 'envira-gallery-lite' ), '&amp;' ); // phpcs:ignore ?></button>
						<a href="#" class="am-deactivate-survey-deactivate"><?php printf( esc_html__( 'Skip %s Deactivate', 'envira-gallery-lite' ), '&amp;' ); // phpcs:ignore ?></a>
					</div>
				</form>
			</div>
		</div>
		<?php
	}
}
