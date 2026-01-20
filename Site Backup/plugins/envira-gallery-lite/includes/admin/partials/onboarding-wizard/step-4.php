<?php
/**
 * Outputs the first step of the Onboarding Wizard.
 *
 * @since   1.8.11
 *
 * @package Envira Gallery Lite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

?>
<div class="envira-onboarding-form-step envira-wizard-license-key" id="summary">
	<div class="envira-onboarding-wizard-body">
		<div class="steps"><?php esc_html_e( 'Step - 4 of 5', 'envira-gallery-lite' ); ?></div>
		<div class="envira-onboarding-settings-row no-border no-margin">
			<div class="settings-name">
				<h2><?php esc_html_e( 'Create a Beautiful Gallery and Sell your Photo Online', 'envira-gallery-lite' ); ?></h2>
				<div class="name small-margin">
				</div>
				<div class="envira-onboarding-description"><?php esc_html_e( 'You are currently using Envira Lite.', 'envira-gallery-lite' ); ?></div>
			</div>
		</div>
		<div class="license-cta-box">
			<div class="">
				<?php
				printf(
				// Translators: %s is the link to upgrade to PRO.
					__( 'To Unlock below features, <strong><a target="_blank" href="%s">Upgrade to PRO</a></strong> and Enter your license key below', 'envira-gallery-lite' ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					esc_url( 'https://enviragallery.com/lite/?utm_source=liteplugin&utm_medium=wizard&utm_campaign=wizard' )
				);
				?>
			</div>
			<div class="envira-row" id="selected-add-ons">
				<div class="envira-col col-xs-12 col-sm-6 text-xs-left">
					<svg viewBox="0 0 14 11" fill="none" xmlns="http://www.w3.org/2000/svg" class="envira-checkmark">
						<path
							fill-rule="evenodd"
							clip-rule="evenodd"
							d="M10.8542 1.37147C11.44 0.785682 12.3897 0.785682 12.9755 1.37147C13.5613 1.95726 13.5613 2.907 12.9755 3.49279L6.04448 10.4238C5.74864 10.7196 5.35996 10.8661 4.97222 10.8631C4.58548 10.8653 4.19805 10.7189 3.90298 10.4238L1.0243 7.5451C0.438514 6.95931 0.438514 6.00956 1.0243 5.42378C1.61009 4.83799 2.55983 4.83799 3.14562 5.42378L4.97374 7.2519L10.8542 1.37147Z"
							fill="currentColor"
						></path>
					</svg>
					<?php esc_html_e( 'Ecommerce Integration', 'envira-gallery-lite' ); ?>
				</div>
				<div class="envira-col col-xs-12 col-sm-6 text-xs-left">
					<svg viewBox="0 0 14 11" fill="none" xmlns="http://www.w3.org/2000/svg" class="envira-checkmark">
						<path
							fill-rule="evenodd"
							clip-rule="evenodd"
							d="M10.8542 1.37147C11.44 0.785682 12.3897 0.785682 12.9755 1.37147C13.5613 1.95726 13.5613 2.907 12.9755 3.49279L6.04448 10.4238C5.74864 10.7196 5.35996 10.8661 4.97222 10.8631C4.58548 10.8653 4.19805 10.7189 3.90298 10.4238L1.0243 7.5451C0.438514 6.95931 0.438514 6.00956 1.0243 5.42378C1.61009 4.83799 2.55983 4.83799 3.14562 5.42378L4.97374 7.2519L10.8542 1.37147Z"
							fill="currentColor"
						></path>
					</svg>
					<?php esc_html_e( 'Watermarking to avoid Redistribution', 'envira-gallery-lite' ); ?>
				</div>
				<div class="envira-col col-xs-12 col-sm-6 text-xs-left">
					<svg viewBox="0 0 14 11" fill="none" xmlns="http://www.w3.org/2000/svg" class="envira-checkmark">
						<path
							fill-rule="evenodd"
							clip-rule="evenodd"
							d="M10.8542 1.37147C11.44 0.785682 12.3897 0.785682 12.9755 1.37147C13.5613 1.95726 13.5613 2.907 12.9755 3.49279L6.04448 10.4238C5.74864 10.7196 5.35996 10.8661 4.97222 10.8631C4.58548 10.8653 4.19805 10.7189 3.90298 10.4238L1.0243 7.5451C0.438514 6.95931 0.438514 6.00956 1.0243 5.42378C1.61009 4.83799 2.55983 4.83799 3.14562 5.42378L4.97374 7.2519L10.8542 1.37147Z"
							fill="currentColor"
						></path>
					</svg>
					<?php esc_html_e( 'Proofing and Social Sharing', 'envira-gallery-lite' ); ?>
				</div>
				<div class="envira-col col-xs-12 col-sm-6 text-xs-left">
					<svg viewBox="0 0 14 11" fill="none" xmlns="http://www.w3.org/2000/svg" class="envira-checkmark">
						<path
							fill-rule="evenodd"
							clip-rule="evenodd"
							d="M10.8542 1.37147C11.44 0.785682 12.3897 0.785682 12.9755 1.37147C13.5613 1.95726 13.5613 2.907 12.9755 3.49279L6.04448 10.4238C5.74864 10.7196 5.35996 10.8661 4.97222 10.8631C4.58548 10.8653 4.19805 10.7189 3.90298 10.4238L1.0243 7.5451C0.438514 6.95931 0.438514 6.00956 1.0243 5.42378C1.61009 4.83799 2.55983 4.83799 3.14562 5.42378L4.97374 7.2519L10.8542 1.37147Z"
							fill="currentColor"
						></path>
					</svg>
					<?php esc_html_e( 'Deeplinking for better SEO', 'envira-gallery-lite' ); ?>
				</div>
			</div>
		</div>
		<div class="envira-onboarding-settings-row no-border ">
			<div class=" ">
				<p>
					<?php esc_html_e( 'Already purchased? Simply enter your license key below to connect with Envira Pro!', 'envira-gallery-lite' ); ?>
				</p>
				<form id="envira-settings-verify-key" method="post">
					<div class="envira-row ">
						<div class="envira-col col-xs-12 col-sm-8 text-xs-left envira-onboarding-input">
							<input type="password" name="envira-license-key" id="envira-settings-key" placeholder="<?php esc_attr_e( 'Enter your license key', 'envira-gallery-lite' ); ?>" required/>
						</div>
						<div class="envira-col col-xs-12 col-sm-2 text-xs-left">
							<input type="submit" class=" btn envira-onboarding-wizard-primary-btn envira-gallery-verify-submit" id="envira-gallery-settings-connect-btn" value="<?php esc_html_e( 'Connect', 'envira-gallery-lite' ); ?>" />
						</div>
						<div class="envira-col col-xs-12 col-sm-1 text-xs-right">
							<span class="spinner envira-onboarding-spinner"></span>
						</div>
					</div>
				</form>
				<div class="envira-row ">
					<div class="envira-col col-xs-12 col-sm-12 text-xs-left">
						<div id="license-key-message" class=""></div>
					</div>
				</div>
			</div>
	</div>
	</div>
	<div class="envira-onboarding-wizard-footer">
		<div class="go-back"><a href="#recommended" data-prev="2" class="envira-onboarding-wizard-back-btn envira-onboarding-btn-prev" id="" >←&nbsp;<?php esc_html_e( 'Go back', 'envira-gallery-lite' ); ?></a></div>
		<div class="spacer"></div><button type="button" data-next="4" class="btn envira-onboarding-wizard-primary-btn envira-onboarding-btn-next "><?php esc_html_e( 'Save and Continue', 'envira-gallery-lite' ); ?>&nbsp; →</button>
	</div>
</div>
