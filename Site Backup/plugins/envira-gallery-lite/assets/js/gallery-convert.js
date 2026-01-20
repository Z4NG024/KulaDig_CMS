/**
 * Handle Convert Gallery functionality.
 */

(function ($, document, envira_gallery_convert) {
	// DOM ready.
	$(function () {
		/**
		 * Gallery Bulk Conversion.
		 */
		$(document).on('click', '.convert-envira-gallery-tab-btn', function (e) {
			e.preventDefault();

			// Prompt the user for confirmation.
			if (!window.confirm(envira_gallery_convert.bulk_confirmation_alert)) {
				return;
			}

			$('.envira-posttype-dropdown-error').hide();
			$('.envira-convert-gallery-message').html('').removeClass('success error').hide();
			$('.envira-convert-process-logs').html('').hide();

			const convertButton = $(this);
			const converting = convertButton.attr('data-converting');
			const buttonText = convertButton.attr('title');
			const postTypeDropDown = $('#envira_convert_post_types_dropdown');
			const selectedPostType = postTypeDropDown.val();

			if (!selectedPostType) {
				$('.envira-posttype-dropdown-error').show();
				return;
			}

			// Prepare form data.
			const formData = new FormData();
			formData.append('selected_posttype', selectedPostType);

			// Disable the dropdown and button.
			postTypeDropDown.attr('disabled', true);
			convertButton.attr('disabled', true);
			convertButton.html(converting);

			// Make the fetch request to the REST API.
			fetch(envira_gallery_convert.bulk_convert_rest_url, {
				method: 'POST',
				body: formData,
				headers: {
					'X-WP-Nonce': envira_gallery_convert.gallery_convert_rest_nonce // REST API nonce for authorization.
				}
			})
				.then(response => response.json())
				.then(response => {
					if (response.posts) {
						let posts = response.posts;
						let totalPosts = posts.length;
						let currentPostIndex = 0;
						let failedPostsCount = 0;

						// Create and initialize the progress bar.
						$('.envira-convert-gallery-message')
							.addClass('success')
							.html(envira_gallery_convert.bulk_conversion_started)
							.show();

						$('.envira-convert-process-logs')
							.show()
							.html(
								'<p><strong>' +
									totalPosts +
									' ' +
									envira_gallery_convert.found_posts_text +
									'</strong></p><div class="envira-convert-progress-bar-container"><div class="envira-convert-progress-bar"></div></div><div class="envira-convert-progress-counts"></div>'
							);

						// Function to update the progress bar.
						function updateProgressBar() {
							const progress = Math.round((currentPostIndex / totalPosts) * 100);
							$('.envira-convert-progress-bar').css('width', progress + '%');
							$('.envira-convert-progress-counts').html(progress + '%');
						}

						// Function to process each post one by one.
						function processNextPost() {
							if (currentPostIndex < totalPosts) {
								const post_id = posts[currentPostIndex];

								// Prepare form data.
								const itemFormData = new FormData();
								itemFormData.append('post_id', post_id);

								// Make the fetch request to the REST API.
								fetch(envira_gallery_convert.process_item_rest_url, {
									method: 'POST',
									body: itemFormData,
									headers: {
										'X-WP-Nonce': envira_gallery_convert.gallery_convert_rest_nonce // REST API nonce for authorization.
									}
								})
									.then(processResponse => processResponse.json())
									.then(processResponse => {
										if (processResponse.error) {
											let edit_url_html = '';
											if (processResponse.edit_url) {
												const edit_url = processResponse.edit_url;
												edit_url_html =
													' (<a target="_blank" href="' +
													edit_url +
													'" target="_blank">' +
													envira_gallery_convert.edit_post_text +
													'</a>)';
											}
											if (failedPostsCount === 0) {
												$('.envira-convert-process-logs')
													.show()
													.append(
														'<p><strong>' +
															envira_gallery_convert.failed_conversion_logs +
															'</strong></p>'
													);
											}
											$('.envira-convert-process-logs')
												.show()
												.append(
													'<p><strong>' +
														envira_gallery_convert.post_id_text +
														' ' +
														post_id +
														edit_url_html +
														'</strong>: ' +
														processResponse.error +
														'</p>'
												);
											failedPostsCount++;
										}

										currentPostIndex++; // Move to next post.
										updateProgressBar(); // Update progress bar.
										processNextPost(); // Process next post.
									})
									.catch(error => {
										$('.envira-convert-gallery-message').addClass('error').html(error).show();
									});
							} else {
								// Conversion process completed.
								updateProgressBar();
								$('.envira-convert-gallery-message')
									.addClass('success')
									.html(envira_gallery_convert.conversion_completed)
									.show();

								// Re-enable the dropdown and button.
								postTypeDropDown.attr('disabled', false);
								convertButton.attr('disabled', false);
								convertButton.html(buttonText);
							}
						}

						// Start processing the first post.
						updateProgressBar(); // Initialize progress bar.
						processNextPost();
					} else {
						// Re-enable the dropdown and button.
						postTypeDropDown.attr('disabled', false);
						convertButton.attr('disabled', false);
						convertButton.html(buttonText);

						$('.envira-convert-gallery-message').addClass('error').html(response.message).show();
					}
				})
				.catch(error => {
					// Re-enable the dropdown and button.
					postTypeDropDown.attr('disabled', false);
					convertButton.attr('disabled', false);
					convertButton.html(buttonText);

					// Display the error message.
					$('.envira-convert-gallery-message').addClass('error').html(error).show();
				})
				.finally(() => {
					// Re-enable the dropdown and button.
					postTypeDropDown.attr('disabled', false);
					convertButton.attr('disabled', false);
					convertButton.html(buttonText);
				});
		});
	});
})(jQuery, document, envira_gallery_convert);
