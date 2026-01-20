<?php
/**
 * Outputs the Gallery Type Tab Selector and Panels
 *
 * @since   1.5.0
 *
 * @var array $data Array of data to pass to the view.
 *
 * @package Envira_Gallery
 * @author  Envira Team
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

?>
<h2 id="envira-types-nav" class="nav-tab-wrapper envira-tabs-nav" data-container="#envira-types" data-update-hashbang="0">
	<label class="nav-tab nav-tab-native-envira-gallery<?php echo ( ( $data['instance']->get_config( 'type', $data['instance']->get_config_default( 'type' ) ) === 'default' ) ? ' envira-active' : '' ); ?>" for="envira-gallery-type-default" data-tab="#envira-gallery-native">
		<input id="envira-gallery-type-default" type="radio" name="_envira_gallery[type]" value="default" <?php checked( $data['instance']->get_config( 'type', $data['instance']->get_config_default( 'type' ) ), 'default' ); ?> />
		<span><?php esc_html_e( 'Native Envira Gallery', 'envira-gallery-lite' ); ?></span>
	</label>

	<a href="#envira-gallery-external" title="<?php esc_attr_e( 'External Gallery', 'envira-gallery-lite' ); ?>" class="nav-tab nav-tab-external-gallery<?php echo ( ( $data['instance']->get_config( 'type', $data['instance']->get_config_default( 'type' ) ) !== 'default' ) ? ' envira-active' : '' ); ?>">
		<span><?php esc_html_e( 'External Gallery', 'envira-gallery-lite' ); ?></span>
	</a>

	<a href="#envira-gallery-envira-ai" title="<?php esc_attr_e( 'Create with Envira AI', 'envira-gallery-lite' ); ?>" class="nav-tab nav-tab-envira-ai">
		<span><?php esc_html_e( 'Create with Envira AI', 'envira-gallery-lite' ); ?></span>
	</a>
</h2>

<!-- Types -->
<div id="envira-types" data-navigation="#envira-types-nav">
	<!-- Native Envira Gallery - Drag and Drop Uploader -->
	<div id="envira-gallery-native" class="envira-tab envira-clear<?php echo ( ( $data['instance']->get_config( 'type', $data['instance']->get_config_default( 'type' ) ) === 'default' ) ? ' envira-active' : '' ); ?>">
		<!-- Errors -->
		<div id="envira-gallery-upload-error"></div>

		<!-- WP Media Upload Form -->
		<?php
		media_upload_form();
		?>
		<script type="text/javascript">
			var post_id = <?php echo intval( $data['post']->ID ); ?>,
				shortform = 3;
		</script>
		<input type="hidden" name="post_id" id="post_id" value="<?php echo intval( $data['post']->ID ); ?>" />
	</div>

	<!-- External Gallery -->
	<div id="envira-gallery-external" class="envira-tab envira-clear<?php echo ( ( $data['instance']->get_config( 'type', $data['instance']->get_config_default( 'type' ) ) !== 'default' ) ? ' envira-active' : '' ); ?>">

		<?php $upgrade_link = Envira_Gallery_Common_Admin::get_instance()->get_upgrade_link( false, 'adminpage', 'externalgalleryinstagram' ); ?>
		<p class="envira-intro"><?php esc_html_e( 'Create Dynamic Galleries with Envira', 'envira-gallery-lite' ); ?></p>
		<ul id="envira-gallery-types-nav">
			<li id="envira-gallery-type-instagram">
				<a href="javascript:void(0);" title="<?php esc_attr_e( 'Build Galleries from Instagram images.', 'envira-gallery-lite' ); ?>" class="link-envira-instagram-tab upsell">
					<div class="icon"></div>
					<div class="title"><?php esc_html_e( 'Instagram', 'envira-gallery-lite' ); ?></div>
				</a>
			</li>
			<li id="envira-gallery-type-dribbble">
				<a href="javascript:void(0);" title="<?php esc_attr_e( 'Build Galleries from Dribbble images.', 'envira-gallery-lite' ); ?>" class="link-envira-dribbble-tab upsell">
					<div class="icon"></div>
					<div class="title"><?php esc_html_e( 'Dribbble', 'envira-gallery-lite' ); ?></div>
				</a>
			</li>
			<li id="envira-gallery-type-tiktok">
				<a href="javascript:void(0);" title="<?php esc_attr_e( 'Build Galleries from TikTok videos.', 'envira-gallery-lite' ); ?>" class="link-envira-tiktok-tab upsell">
					<div class="icon"></div>
					<div class="title"><?php esc_html_e( 'TikTok', 'envira-gallery-lite' ); ?></div>
				</a>
			</li>
		</ul>
	</div>

	<!-- Envira AI -->
	<div id="envira-gallery-envira-ai" class="envira-tab envira-clear">

		<p><?php esc_html_e( 'Add images to your gallery using the power of AI', 'envira-gallery-lite' ); ?></p>
		<p>
			<a href="javascript:void(0);" class="button button-primary button-x-large button-envira-ai-tab upsell" title="<?php esc_attr_e( 'Create Images with AI', 'envira-gallery-lite' ); ?>">
				<?php esc_html_e( 'Create Images with AI', 'envira-gallery-lite' ); ?>
			</a>
		</p>

	</div>

</div>

<!-- Envira AI Upsell Modal -->
<div id="envira-ai-upsell-modal" class="envira-ai-modal">
	<div class="envira-ai-modal-overlay"></div>
	<div class="envira-ai-upsell-modal-content">
		<a href="javascript:void(0);" id="close-envira-ai-upsell" class="close-envira-ai-upsell">&times;</a>
		<div class="upsell-content-container">
			<img src="<?php echo esc_url( trailingslashit( ENVIRA_LITE_URL ) . 'assets/css/images/icons/locked-with-key.svg' ); ?>" alt="<?php esc_attr_e( 'Unlock feature', 'envira-gallery-lite' ); ?>" class="lock-icon" />
			<h3><?php esc_html_e( 'Upgrade to Envira Gallery and Create images with AI!', 'envira-gallery-lite' ); ?></h3>
			<p><?php esc_html_e( 'Create unique and stunning galleries with Envira AI. No matter the type of site or design style, Envira Gallery makes it easy to create assets without a designer.', 'envira-gallery-lite' ); ?></p>
			<div class="upsell-two-column">
				<div class="upsell-left-column">
					<div class="top-content">
						<h4><?php esc_html_e( 'Here’s what you get with Envira AI', 'envira-gallery-lite' ); ?></h4>
						<ul>
							<li><?php esc_html_e( 'Specify your asset needs.', 'envira-gallery-lite' ); ?></li>
							<li><?php esc_html_e( 'Create an unlimited number of images.', 'envira-gallery-lite' ); ?></li>
							<li><?php esc_html_e( 'Choose your design style.', 'envira-gallery-lite' ); ?></li>
							<li><?php esc_html_e( 'Customize your image sizes.', 'envira-gallery-lite' ); ?></li>
						</ul>
					</div>
					<div class="bottom-content">
						<a href="https://enviragallery.com/lite/?utm_source=liteplugin&amp;utm_medium=adminpageunlockai&amp;utm_campaign=upgradetopro" class="button button-primary" target="_blank"><?php esc_html_e( 'Unlock AI Features', 'envira-gallery-lite' ); ?></a>
						<p class="gift-text">
							<?php
							$image_url = esc_url( trailingslashit( ENVIRA_LITE_URL ) . 'assets/css/images/icons/wrapped-gift.svg' );
							$image_alt = esc_attr__( 'Unlock feature', 'envira-gallery-lite' );

							// Create the full HTML string.
							$html = sprintf(
								'<img src="%1$s" alt="%2$s" class="gift-icon" /> %3$s',
								$image_url,
								$image_alt,
								__( 'Plus <span class="offer-text">Save 50%</span> by Upgrading to Pro today', 'envira-gallery-lite' )
							);

							echo wp_kses_post( $html );
							?>
						</p>
					</div>
				</div>
				<div class="upsell-right-column">
					<img src="<?php echo esc_url( trailingslashit( ENVIRA_LITE_URL ) . 'assets/images/upsell-ai-group.png' ); ?>" alt="<?php esc_attr_e( 'Demo Images', 'envira-gallery-lite' ); ?>" />
				</div>
			</div>
		</div>
	</div>
</div>

<!-- Envira Instagram Upsell Modal -->
<div id="envira-instagram-upsell-modal" class="envira-addon-upsell-modal">
	<div class="envira-addon-upsell-modal-overlay"></div>
	<div class="envira-addon-upsell-modal-content">
		<img src="<?php echo esc_url( trailingslashit( ENVIRA_LITE_URL ) . 'assets/images/close.png' ); ?>" alt="Close upsell modal" id="close-envira-instagram-upsell-modal" class="close-envira-addon-upsell-modal"></img>

		<div class="upsell-content-container">
			<div class="top-content">
				<img src="<?php echo esc_url( trailingslashit( ENVIRA_LITE_URL ) . 'assets/images/lock.png' ); ?>" alt="Lock icon" class="lock-icon"></img>
				<h3>Instagram is a Pro Feature</h3>
				<p>We’re sorry, using Instagram is not available on your plan. Please upgrade to the Pro plan to unlock all these awesome features.</p>
			</div>
			<div class="bottom-content">
				<div class="connector-icon">
					<img src="<?php echo esc_url( trailingslashit( ENVIRA_LITE_URL ) . 'assets/images/connector.png' ); ?>" alt="Connector icon"></img>
				</div>
				<a href="https://enviragallery.com/lite/?utm_source=liteplugin&amp;utm_medium=adminpageunlock_instagram&amp;utm_campaign=upgradetopro" class="button button-upsell-modal" target="_blank"><?php esc_html_e( 'Upgrade to Pro', 'envira-gallery-lite' ); ?>
					<img src="<?php echo esc_url( trailingslashit( ENVIRA_LITE_URL ) . 'assets/images/arrow-right.png' ); ?>" alt="Arrow right icon" class="arrow-right-icon"></img>
				</a>
				<div class="discount-section">
					<div class="discount-percentage">
						<img src="<?php echo esc_url( trailingslashit( ENVIRA_LITE_URL ) . 'assets/images/discount-icon.png' ); ?>" alt="Discount percentage">
						</img>
						<span>%</span>
					</div>
					<p>Envira Gallery <?php echo esc_html( ENVIRA_LITE_VERSION ); ?> users <span class="offer-text">get 50% off</span> the regular price, automatically applied at checkout.</p>
				</div>
			</div>
		</div>
	</div>
</div>

<!-- Envira Dribbble Upsell Modal -->
<div id="envira-dribbble-upsell-modal" class="envira-addon-upsell-modal">
	<div class="envira-addon-upsell-modal-overlay"></div>
	<div class="envira-addon-upsell-modal-content">
		<img src="<?php echo esc_url( trailingslashit( ENVIRA_LITE_URL ) . 'assets/images/close.png' ); ?>" alt="Close upsell modal" id="close-envira-dribbble-upsell-modal" class="close-envira-addon-upsell-modal"></img>

		<div class="upsell-content-container">
			<div class="top-content">
				<img src="<?php echo esc_url( trailingslashit( ENVIRA_LITE_URL ) . 'assets/images/lock.png' ); ?>" alt="Lock icon" class="lock-icon"></img>
				<h3>Dribbble is a Pro Feature</h3>
				<p>We’re sorry, using Dribbble is not available on your plan. Please upgrade to the Pro plan to unlock all these awesome features.</p>
			</div>
			<div class="bottom-content">
				<div class="connector-icon">
					<img src="<?php echo esc_url( trailingslashit( ENVIRA_LITE_URL ) . 'assets/images/connector.png' ); ?>" alt="Connector icon"></img>
				</div>
				<a href="https://enviragallery.com/lite/?utm_source=liteplugin&amp;utm_medium=adminpageunlock_dribbble&amp;utm_campaign=upgradetopro" class="button button-upsell-modal" target="_blank"><?php esc_html_e( 'Upgrade to Pro', 'envira-gallery-lite' ); ?>
					<img src="<?php echo esc_url( trailingslashit( ENVIRA_LITE_URL ) . 'assets/images/arrow-right.png' ); ?>" alt="Arrow right icon" class="arrow-right-icon"></img>
				</a>
				<div class="discount-section">
					<div class="discount-percentage">
						<img src="<?php echo esc_url( trailingslashit( ENVIRA_LITE_URL ) . 'assets/images/discount-icon.png' ); ?>" alt="Discount percentage">
						</img>
						<span>%</span>
					</div>
					<p>Envira Gallery lite users <span class="offer-text">get 50% off</span> the regular price, automatically applied at checkout.</p>
				</div>
			</div>
		</div>
	</div>
</div>

<!-- Envira Tiktok Upsell Modal -->
<div id="envira-tiktok-upsell-modal" class="envira-addon-upsell-modal">
	<div class="envira-addon-upsell-modal-overlay"></div>
	<div class="envira-addon-upsell-modal-content">
		<img src="<?php echo esc_url( trailingslashit( ENVIRA_LITE_URL ) . 'assets/images/close.png' ); ?>" alt="Close upsell modal" id="close-envira-tiktok-upsell-modal" class="close-envira-addon-upsell-modal"></img>

		<div class="upsell-content-container">
			<div class="top-content">
				<img src="<?php echo esc_url( trailingslashit( ENVIRA_LITE_URL ) . 'assets/images/lock.png' ); ?>" alt="Lock icon" class="lock-icon"></img>
				<h3>TikTok is a Pro Feature</h3>
				<p>We’re sorry, using TikTok is not available on your plan. Please upgrade to the Pro plan to unlock all these awesome features.</p>
			</div>
			<div class="bottom-content">
				<div class="connector-icon">
					<img src="<?php echo esc_url( trailingslashit( ENVIRA_LITE_URL ) . 'assets/images/connector.png' ); ?>" alt="Connector icon"></img>
				</div>
				<a href="https://enviragallery.com/lite/?utm_source=liteplugin&amp;utm_medium=adminpageunlock_tiktok&amp;utm_campaign=upgradetopro" class="button button-upsell-modal" target="_blank"><?php esc_html_e( 'Upgrade to Pro', 'envira-gallery-lite' ); ?>
					<img src="<?php echo esc_url( trailingslashit( ENVIRA_LITE_URL ) . 'assets/images/arrow-right.png' ); ?>" alt="Arrow right icon" class="arrow-right-icon"></img>
				</a>
				<div class="discount-section">
					<div class="discount-percentage">
						<img src="<?php echo esc_url( trailingslashit( ENVIRA_LITE_URL ) . 'assets/images/discount-icon.png' ); ?>" alt="Discount percentage">
						</img>
						<span>%</span>
					</div>
					<p>Envira Gallery lite users <span class="offer-text">get 50% off</span> the regular price, automatically applied at checkout.</p>
				</div>
			</div>
		</div>
	</div>
</div>

<div class="clear"></div>
