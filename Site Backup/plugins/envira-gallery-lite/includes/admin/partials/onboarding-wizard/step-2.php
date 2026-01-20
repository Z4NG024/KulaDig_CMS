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

$onboarding = new OnboardingWizard();
?>
<div class="envira-onboarding-form-step " id="features">
	<div class="envira-onboarding-wizard-body envira-wizard-features">
		<div class="steps"><?php esc_html_e( 'Step - 2 of 5', 'envira-gallery-lite' ); ?></div>
		<div class="envira-onboarding-settings-row no-border no-margin">
			<div class="settings-name">
				<h2><?php esc_html_e( 'What Envira Gallery features do you want to enable?', 'envira-gallery-lite' ); ?></h2>
				<div class="name small-margin">
				</div>
				<div class="envira-onboarding-description">
					<?php esc_html_e( 'We have already selected our recommended features based upon your website, but you can choose other features.', 'envira-gallery-lite' ); ?>
				</div>
			</div>
		</div>
		<div class="feature-grid small-padding medium-margin">
			<div class="envira-row">
				<div class="envira-col col-xs-11 text-xs-left">
					<div class="settings-name">
						<div class="name small-margin">
							<?php esc_html_e( 'Responsive Photo Galleries', 'envira-gallery-lite' ); ?>
							<!---->
						</div>
						<div class="envira-description-text"><?php esc_html_e( 'Create beautiful fully responsive galleries which are cross browser compatible.', 'envira-gallery-lite' ); ?></div>
						<!---->
					</div>
				</div>
				<div class="envira-col col-xs-1 text-xs-left">
					<label class="envira-checkbox round no-clicks disabled">
				<span class="form-checkbox-wrapper">
					<span class="form-checkbox">
						<input type="checkbox" class="no-clicks" checked="checked"/>
						<span class="fancy-checkbox green">
							<svg viewBox="0 0 14 11" fill="none" xmlns="http://www.w3.org/2000/svg" class="envira-checkmark">
								<path
									fill-rule="evenodd"
									clip-rule="evenodd"
									d="M10.8542 1.37147C11.44 0.785682 12.3897 0.785682 12.9755 1.37147C13.5613 1.95726 13.5613 2.907 12.9755 3.49279L6.04448 10.4238C5.74864 10.7196 5.35996 10.8661 4.97222 10.8631C4.58548 10.8653 4.19805 10.7189 3.90298 10.4238L1.0243 7.5451C0.438514 6.95931 0.438514 6.00956 1.0243 5.42378C1.61009 4.83799 2.55983 4.83799 3.14562 5.42378L4.97374 7.2519L10.8542 1.37147Z"
									fill="currentColor"
								></path>
							</svg>
						</span>
					</span>
				</span>
					</label>
				</div>
			</div>
	</div>
		<div class="feature-grid small-padding medium-margin">
			<div class="envira-row">
				<div class="envira-col col-xs-11 text-xs-left">
					<div class="settings-name">
						<div class="name small-margin">
							<?php esc_html_e( 'Drag and Drop', 'envira-gallery-lite' ); ?>
						</div>
						<div class="envira-description-text"><?php esc_html_e( 'Easily add, remove, and reorder gallery images with drag and drop simplicity.', 'envira-gallery-lite' ); ?></div>
					</div>
				</div>
				<div class="envira-col col-xs-1 text-xs-left">
					<label class="envira-checkbox round no-clicks disabled">
				<span class="form-checkbox-wrapper">
					<span class="form-checkbox">
						<input type="checkbox" class="no-clicks" checked="checked" />
						<span class="fancy-checkbox green">
							<svg viewBox="0 0 14 11" fill="none" xmlns="http://www.w3.org/2000/svg" class="envira-checkmark">
								<path
									fill-rule="evenodd"
									clip-rule="evenodd"
									d="M10.8542 1.37147C11.44 0.785682 12.3897 0.785682 12.9755 1.37147C13.5613 1.95726 13.5613 2.907 12.9755 3.49279L6.04448 10.4238C5.74864 10.7196 5.35996 10.8661 4.97222 10.8631C4.58548 10.8653 4.19805 10.7189 3.90298 10.4238L1.0243 7.5451C0.438514 6.95931 0.438514 6.00956 1.0243 5.42378C1.61009 4.83799 2.55983 4.83799 3.14562 5.42378L4.97374 7.2519L10.8542 1.37147Z"
									fill="currentColor"
								></path>
							</svg>
						</span>
					</span>
				</span>
					</label>
				</div>
			</div>
		</div>
		<div class="feature-grid small-padding medium-margin">
			<div class="envira-row">
				<div class="envira-col col-xs-11 text-xs-left">
					<div class="settings-name">
						<div class="name small-margin">
							<?php esc_html_e( 'Lightboxes', 'envira-gallery-lite' ); ?>
						</div>
						<div class="envira-description-text"><?php esc_html_e( 'Showcase your galleries in beautiful lightboxes, which look great on every device.', 'envira-gallery-lite' ); ?></div>

					</div>
				</div>
				<div class="envira-col col-xs-1 text-xs-left">
					<label class="envira-checkbox round no-clicks disabled">
				<span class="form-checkbox-wrapper">
					<span class="form-checkbox">
						<input type="checkbox" class="" checked="checked" />
						<span class="fancy-checkbox green">
							<svg viewBox="0 0 14 11" fill="none" xmlns="http://www.w3.org/2000/svg" class="envira-checkmark">
								<path
									fill-rule="evenodd"
									clip-rule="evenodd"
									d="M10.8542 1.37147C11.44 0.785682 12.3897 0.785682 12.9755 1.37147C13.5613 1.95726 13.5613 2.907 12.9755 3.49279L6.04448 10.4238C5.74864 10.7196 5.35996 10.8661 4.97222 10.8631C4.58548 10.8653 4.19805 10.7189 3.90298 10.4238L1.0243 7.5451C0.438514 6.95931 0.438514 6.00956 1.0243 5.42378C1.61009 4.83799 2.55983 4.83799 3.14562 5.42378L4.97374 7.2519L10.8542 1.37147Z"
									fill="currentColor"
								></path>
							</svg>
						</span>
					</span>
				</span>
					</label>
				</div>
			</div>
		</div>
		<?php
		$is_installed  = $onboarding->is_recommended_plugin_installed( 'envira-albums' );
		$checked       = $is_installed ? 'checked="checked"' : '';
		$installs_text = ! $is_installed ? esc_html__( 'Installs Envira Albums & Tags', 'envira-gallery-lite' ) : esc_html__( 'Envira Albums & Tags is already installed', 'envira-gallery-lite' );
		?>
		<div class="feature-grid small-padding medium-margin">
			<div class="envira-row">
				<div class="envira-col col-xs-11 text-xs-left">
					<div class="settings-name">
						<div class="name small-margin">
							<label for="envira-albums"><?php esc_html_e( 'Albums & Tags', 'envira-gallery-lite' ); ?><span class="envira-pro-badge">PRO</span></label>
						</div>
						<div class="envira-description-text"><?php esc_html_e( 'Easily organize and label galleries to allow your visitors to see exactly what they’re looking for.', 'envira-gallery-lite' ); ?></div>
						<div class="envira-desc" id="envira-albums-desc"><?php echo esc_html( $installs_text ); ?></div>
					</div>
				</div>
				<div class="envira-col col-xs-1 text-xs-left">
					<label class="envira-checkbox round <?php echo esc_attr( $is_installed ); ?>">
				<span class="form-checkbox-wrapper">
					<span class="form-checkbox">
						<input type="checkbox" data-name="<?php esc_attr_e( 'Albums & Tags', 'envira-gallery-lite' ); ?>" name="envira-albums" id="envira-albums"  value="envira-albums" class="feature <?php echo esc_attr( $is_installed ); ?>" <?php echo esc_attr( $checked ); ?> />
						<span class="fancy-checkbox blue">
							<svg viewBox="0 0 14 11" fill="none" xmlns="http://www.w3.org/2000/svg" class="envira-checkmark">
								<path
									fill-rule="evenodd"
									clip-rule="evenodd"
									d="M10.8542 1.37147C11.44 0.785682 12.3897 0.785682 12.9755 1.37147C13.5613 1.95726 13.5613 2.907 12.9755 3.49279L6.04448 10.4238C5.74864 10.7196 5.35996 10.8661 4.97222 10.8631C4.58548 10.8653 4.19805 10.7189 3.90298 10.4238L1.0243 7.5451C0.438514 6.95931 0.438514 6.00956 1.0243 5.42378C1.61009 4.83799 2.55983 4.83799 3.14562 5.42378L4.97374 7.2519L10.8542 1.37147Z"
									fill="currentColor"
								></path>
							</svg>
						</span>
					</span>
				</span>
					</label>
				</div>
			</div>
		</div>
		<?php
		$is_installed  = $onboarding->is_recommended_plugin_installed( 'envira-videos' );
		$checked       = $is_installed ? 'checked="checked"' : '';
		$installs_text = ! $is_installed ? esc_html__( 'Installs Envira Videos', 'envira-gallery-lite' ) : esc_html__( 'Envira Videos is already installed', 'envira-gallery-lite' );
		?>
		<div class="feature-grid small-padding medium-margin">
			<div class="envira-row">
				<div class="envira-col col-xs-11 text-xs-left">
					<div class="settings-name">
						<div class="name small-margin">
							<label for="envira-videos"><?php esc_html_e( 'Video Galleries', 'envira-gallery-lite' ); ?><span class="envira-pro-badge">PRO</span></label>
						</div>
						<div class="envira-description-text"><?php esc_html_e( 'Increase engagement from your galleries and embed videos. Works with all video types.', 'envira-gallery-lite' ); ?></div>
						<div class="envira-desc" id="envira-videos-desc"><?php echo esc_html( $installs_text ); ?></div>
					</div>
				</div>
				<div class="envira-col col-xs-1 text-xs-left">
					<label class="envira-checkbox round <?php echo esc_attr( $is_installed ); ?>">
				<span class="form-checkbox-wrapper">
					<span class="form-checkbox">
						<input type="checkbox" data-name="<?php esc_attr_e( 'Video Galleries', 'envira-gallery-lite' ); ?>" name="envira-videos" value="envira-videos" id="envira-videos" class="feature <?php echo esc_attr( $is_installed ); ?>" <?php echo esc_attr( $checked ); ?>/>
						<span class="fancy-checkbox blue">
							<svg viewBox="0 0 14 11" fill="none" xmlns="http://www.w3.org/2000/svg" class="envira-checkmark">
								<path
									fill-rule="evenodd"
									clip-rule="evenodd"
									d="M10.8542 1.37147C11.44 0.785682 12.3897 0.785682 12.9755 1.37147C13.5613 1.95726 13.5613 2.907 12.9755 3.49279L6.04448 10.4238C5.74864 10.7196 5.35996 10.8661 4.97222 10.8631C4.58548 10.8653 4.19805 10.7189 3.90298 10.4238L1.0243 7.5451C0.438514 6.95931 0.438514 6.00956 1.0243 5.42378C1.61009 4.83799 2.55983 4.83799 3.14562 5.42378L4.97374 7.2519L10.8542 1.37147Z"
									fill="currentColor"
								></path>
							</svg>
						</span>
					</span>
				</span>
					</label>
				</div>
			</div>
		</div>
		<?php
		$is_installed  = $onboarding->is_recommended_plugin_installed( 'envira-slideshow' );
		$checked       = $is_installed ? 'checked="checked"' : '';
		$installs_text = ! $is_installed ? esc_html__( 'Installs Envira Slideshows', 'envira-gallery-lite' ) : esc_html__( 'Envira Slideshows is already installed', 'envira-gallery-lite' );
		?>
		<div class="feature-grid small-padding medium-margin">
			<div class="envira-row">
				<div class="envira-col col-xs-11 text-xs-left">
					<div class="settings-name">
						<div class="name small-margin">
							<label for="envira-slideshow"><?php esc_html_e( 'Slideshows', 'envira-gallery-lite' ); ?><span class="envira-pro-badge">PRO</span></label>
						</div>
						<div class="envira-description-text"><?php esc_html_e( 'Create beautiful scrolling slideshows with customization.', 'envira-gallery-lite' ); ?></div>
						<div class="envira-desc" id="envira-slideshow-desc"><?php echo esc_html( $installs_text ); ?></div>
					</div>
				</div>
				<div class="envira-col col-xs-1 text-xs-left">
					<label class="envira-checkbox round <?php echo esc_attr( $is_installed ); ?>">
				<span class="form-checkbox-wrapper">
					<span class="form-checkbox">
						<input type="checkbox" data-name="<?php esc_attr_e( 'Slideshows', 'envira-gallery-lite' ); ?>" name="envira-slideshow" value="envira-slideshow" id="envira-slideshow" class="feature" <?php echo esc_attr( $checked ); ?> />
						<span class="fancy-checkbox blue">
							<svg viewBox="0 0 14 11" fill="none" xmlns="http://www.w3.org/2000/svg" class="envira-checkmark">
								<path
									fill-rule="evenodd"
									clip-rule="evenodd"
									d="M10.8542 1.37147C11.44 0.785682 12.3897 0.785682 12.9755 1.37147C13.5613 1.95726 13.5613 2.907 12.9755 3.49279L6.04448 10.4238C5.74864 10.7196 5.35996 10.8661 4.97222 10.8631C4.58548 10.8653 4.19805 10.7189 3.90298 10.4238L1.0243 7.5451C0.438514 6.95931 0.438514 6.00956 1.0243 5.42378C1.61009 4.83799 2.55983 4.83799 3.14562 5.42378L4.97374 7.2519L10.8542 1.37147Z"
									fill="currentColor"
								></path>
							</svg>
						</span>
					</span>
				</span>
					</label>
				</div>
			</div>
		</div>
		<?php
		$is_installed  = $onboarding->is_recommended_plugin_installed( 'envira-proofing' );
		$checked       = $is_installed ? 'checked="checked"' : '';
		$installs_text = ! $is_installed ? esc_html__( 'Installs Envira Proofing', 'envira-gallery-lite' ) : esc_html__( 'Envira Proofing is already installed', 'envira-gallery-lite' );
		?>
		<div class="feature-grid small-padding medium-margin">
			<div class="envira-row">
				<div class="envira-col col-xs-11 text-xs-left">
					<div class="settings-name">
						<div class="name small-margin">
							<label for="envira-proofing"><?php esc_html_e( 'Proofing and eCommerce', 'envira-gallery-lite' ); ?><span class="envira-pro-badge">PRO</span></label>
						</div>
						<div class="envira-description-text"><?php esc_html_e( 'Let your customers proof and purchase images from you. Great for photographers.', 'envira-gallery-lite' ); ?></div>
						<div class="envira-desc" id="envira-proofing-desc"><?php echo esc_html( $installs_text ); ?></div>
					</div>
				</div>
				<div class="envira-col col-xs-1 text-xs-left">
					<label class="envira-checkbox round <?php echo esc_attr( $is_installed ); ?>">
				<span class="form-checkbox-wrapper">
					<span class="form-checkbox">
						<input type="checkbox" data-name="<?php esc_attr_e( 'Proofing and eCommerce', 'envira-gallery-lite' ); ?>" name="envira-proofing" value="envira-proofing" id="envira-proofing" class="feature" <?php echo esc_attr( $checked ); ?>/>
						<span class="fancy-checkbox blue">
							<svg viewBox="0 0 14 11" fill="none" xmlns="http://www.w3.org/2000/svg" class="envira-checkmark">
								<path
									fill-rule="evenodd"
									clip-rule="evenodd"
									d="M10.8542 1.37147C11.44 0.785682 12.3897 0.785682 12.9755 1.37147C13.5613 1.95726 13.5613 2.907 12.9755 3.49279L6.04448 10.4238C5.74864 10.7196 5.35996 10.8661 4.97222 10.8631C4.58548 10.8653 4.19805 10.7189 3.90298 10.4238L1.0243 7.5451C0.438514 6.95931 0.438514 6.00956 1.0243 5.42378C1.61009 4.83799 2.55983 4.83799 3.14562 5.42378L4.97374 7.2519L10.8542 1.37147Z"
									fill="currentColor"
								></path>
							</svg>
						</span>
					</span>
				</span>
					</label>
				</div>
			</div>
		</div>

	</div>
	<div class="envira-onboarding-wizard-footer">
		<div class="go-back"><a href="#general" data-prev="0" class="envira-onboarding-wizard-back-btn envira-onboarding-btn-prev" id="" >←&nbsp;<?php esc_html_e( 'Go back', 'envira-gallery-lite' ); ?></a></div>
		<div class="spacer"></div><button type="submit" data-next="2" class="btn envira-onboarding-wizard-primary-btn envira-onboarding-btn-next " id="envira-save-features" ><?php esc_html_e( 'Save and Continue', 'envira-gallery-lite' ); ?>&nbsp; →</button>
	</div>
</div>
