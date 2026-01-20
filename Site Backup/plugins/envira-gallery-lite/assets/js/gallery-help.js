/**
 * Handles:
 * - Inline Video Help
 *
 * @since 1.5.0
 */

// Setup vars
var envira_video_link = 'p.envira-intro a.envira-video',
	envira_close_video_link = 'a.envira-video-close';

jQuery(document).ready(function ($) {
	/**
	 * Display Video Inline on Video Link Click
	 */
	$(document).on('click', envira_video_link, function (e) {
		// Prevent default action
		e.preventDefault();

		// Get the video URL
		var envira_video_url = $(this).attr('href');

		// Check if the video has the autoplay parameter included
		// If not, add it now - this will play the video when it's inserted to the iframe.
		if (envira_video_url.search('autoplay=1') == -1) {
			if (envira_video_url.search('rel=') == -1) {
				envira_video_url += '?rel=0&autoplay=1';
			} else {
				envira_video_url += '&autoplay=1';
			}
		}

		// Destroy any other instances of Envira Video iframes
		$('div.envira-video-help').remove();

		// Get the intro paragraph
		var envira_video_paragraph = $(this).closest('p.envira-intro');

		// Load the video below the intro paragraph on the current tab
		$(envira_video_paragraph).append(
			'<div class="envira-video-help"><iframe src="' +
				envira_video_url +
				'" /><a href="#" class="envira-video-close dashicons dashicons-no"></a></div>'
		);
	});

	/**
	 * Destroy Video when closed
	 */
	$(document).on('click', envira_close_video_link, function (e) {
		e.preventDefault();

		$(this).closest('.envira-video-help').remove();
	});
});

/**
 * Display upsell section on gallery layout selection.
 */
jQuery(document).ready(function ($) {
	var $thumbnails = $('.thumbnails li .upgrade-to-pro');
	var $upsellPrompt = $('#upsell-prompt');
	var $thumbnailsContainer = $('.thumbnails');
	var $closeButton = $('#close-upsell');

	$thumbnails.on('click', function () {
		$upsellPrompt.show();
		$thumbnailsContainer.addClass('no-click');
	});

	$closeButton.on('click', function () {
		$upsellPrompt.hide();
		$thumbnailsContainer.removeClass('no-click');
	});
});

/**
 * Display envira ai upsell modal.
 */
jQuery(document).ready(function ($) {
	var $btnUpsell = $('.envira-tab .button-envira-ai-tab.upsell');
	var $upsellModalAI = $('#envira-ai-upsell-modal');
	var $closeButton = $('#close-envira-ai-upsell');

	$btnUpsell.on('click', function () {
		$upsellModalAI.show();
	});

	$closeButton.on('click', function () {
		$upsellModalAI.hide();
	});
});

/**
 * Display envira dribbble  upsell modal.
 */
jQuery(document).ready(function ($) {
	var $btnUpsell = $('.envira-tab .link-envira-dribbble-tab.upsell');
	var $upsellModalAI = $('#envira-dribbble-upsell-modal');
	var $closeButton = $('#close-envira-dribbble-upsell-modal');

	$btnUpsell.on('click', function () {
		$upsellModalAI.show();
	});

	$closeButton.on('click', function () {
		$upsellModalAI.hide();
	});
});

/**
 * Display envira instagram  upsell modal.
 */
jQuery(document).ready(function ($) {
	var $btnUpsell = $('.envira-tab .link-envira-instagram-tab.upsell');
	var $upsellModalAI = $('#envira-instagram-upsell-modal');
	var $closeButton = $('#close-envira-instagram-upsell-modal');

	$btnUpsell.on('click', function () {
		$upsellModalAI.show();
	});

	$closeButton.on('click', function () {
		$upsellModalAI.hide();
	});
});

/**
 * Display envira tiktok  upsell modal.
 */
jQuery(document).ready(function ($) {
	var $btnUpsell = $('.envira-tab .link-envira-tiktok-tab.upsell');
	var $upsellModalAI = $('#envira-tiktok-upsell-modal');
	var $closeButton = $('#close-envira-tiktok-upsell-modal');

	$btnUpsell.on('click', function () {
		$upsellModalAI.show();
	});

	$closeButton.on('click', function () {
		$upsellModalAI.hide();
	});
});
