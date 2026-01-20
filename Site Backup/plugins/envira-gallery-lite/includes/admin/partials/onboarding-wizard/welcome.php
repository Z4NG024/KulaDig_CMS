<?php
/**
 * Outputs the welcome step of the Onboarding Wizard.
 *
 * @since   1.8.11
 *
 * @package Envira Gallery Lite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

?>
<!-- logo -->
<img width="339" src="<?php echo esc_attr( ENVIRA_LITE_URL . 'assets/images/envira-logo-color.svg' ); ?>" alt="Envira Gallery Lite" class="envira-onboarding-wizard-logo">
<div class="envira-onboarding-wizard-step envira-onboarding-wizard-step-welcome">
	<div class="envira-onboarding-welcome-content">
		<h1><?php esc_html_e( 'Welcome to Envira Gallery!', 'envira-gallery-lite' ); ?></h1>
		<p><?php esc_html_e( 'Create stunning responsive photo and video galleries for your website. Your first gallery is only minutes away.', 'envira-gallery-lite' ); ?></p>
		<div class="envira-onboarding-wizard-cta">
			<a href="#" class="button envira-onboarding-wizard-primary-btn btn-large envira-button-dark envira-button-primary" id="envira-get-started-btn"><?php esc_html_e( 'Get Started → ', 'envira-gallery-lite' ); ?></a>
		</div>
	</div>
</div>
<a href="<?php echo esc_url( admin_url( '/edit.php?post_type=envira&page=envira-gallery-settings' ) ); ?>" class="envira-onboarding-wizard-back-btn">←&nbsp;<?php esc_html_e( 'Go back to the dashboard', 'envira-gallery-lite' ); ?></a>

