// no conflict jquery wrapper
(function ($) {
	var $tooltip = $(document.getElementById('envira-admin-menu-tooltip'));
	var $menuwrapper = $(document.getElementById('adminmenuwrap'));
	var $menuitem = $(document.querySelector('.menu-icon-envira'));

	if (0 === $menuitem.length) {
		return;
	}

	if ($menuitem.length) {
		$menuitem.append($tooltip);
	}

	$tooltip.css({
		top: (-1 * $tooltip.innerHeight()) / 2 + 'px'
	});

	$('#envira-admin-menu-launch-tooltip-button').click(function (e) {
		e.preventDefault();
		$('#envira-admin-menu-tooltip').hide();
		$.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'envira_redirect_to_add_new_gallery'
			},
			success: function (response) {
				if (response.success) {
					let redirectUrl = new URL(response.data.redirect_url);
					redirectUrl.searchParams.append('hideTooltip', 'true');
					window.location.href = redirectUrl.toString();
				} else {
					console.error('Error:', response.data);
				}
			},
			error: function (xhr, status, error) {
				console.error('AJAX request failed:', status, error);
			}
		});
	});

	$('.envira-admin-menu-tooltip-close').on('click', function (e) {
		e.preventDefault();
		$(this).parent().parent().hide();
		$.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'envira_hide_admin_menu_tooltip'
			}
		});
	});

	const urlParams = new URLSearchParams(window.location.search);
	if (urlParams.get('hideTooltip') === 'true' || urlParams.get('post_type') === 'envira') {
		$('#envira-admin-menu-tooltip').hide();
		$('.menu-icon-envira .wp-submenu-wrap').css('display', 'block');
	}

	if (
		(urlParams.get('post_type') === 'envira' &&
			urlParams.get('page') === 'envira-gallery-settings') ||
		(urlParams.get('post_type') === 'envira' &&
			urlParams.get('page') === 'envira-gallery-addons') ||
		(urlParams.get('post_type') === 'envira' &&
			urlParams.get('page') === 'envira-gallery-get-started') ||
		(urlParams.get('post_type') === 'envira' && urlParams.get('page') === 'envira-gallery-tools') ||
		(urlParams.get('post_type') === 'envira' &&
			urlParams.get('page') === 'envira-gallery-lite-about-us')
	) {
		$('#envira-admin-menu-tooltip').show();
	}
})(jQuery);
