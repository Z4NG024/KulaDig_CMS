/**
 * Handles:
 * - Permissions modal -shows when setting user roles.
 *
 * @since 1.8.9
 */

jQuery(document).ready(function ($) {
	/**
	 * Permissions modal -shows when setting user roles.
	 * use A11yDialog library.
	 */

	let perms_container = document.getElementById('envira-permissions-dialog-id');
	let perms_dialog = null;
	perms_dialog = new A11yDialog(perms_container);

	/**
	 * Permissions tab - Select User Roles.
	 * - Uses choices JS
	 */

	// all the select fields that have this class .envira-permissions-select-field are the permissions fields.

	var permissions_select = document.getElementsByClassName('envira-permissions-select-field');

	// store the permissions select elements as a constant.
	const enviraPermissionsChoices = [];

	for (var i = 0; i < permissions_select.length; i++) {
		var current_select = permissions_select[i];
		if (current_select.length > 0) {
			let choices_permissions = current_select.getAttribute('id') + '_select';
			enviraPermissionsChoices[choices_permissions] = new Choices(current_select, {
				allowHTML: true,
				searchChoices: false,
				searchEnabled: false,
				removeItemButton: true,
				itemSelectText: '',
				addItemText: '',
				shouldSort: false,
				shouldSortItems: false,
				classNames: {
					containerInner: 'choices__inner roles_inner',
					containerOuter: 'choices roles_inner'
				}
			});

			current_select.addEventListener(
				'addItem',
				function (event) {
					check_envira_permissions(
						$(this).attr('id'),
						event.detail.value,
						event.detail.label,
						'add'
					);
					disable_administrator();
				},
				false
			);

			current_select.addEventListener(
				'removeItem',
				function (event) {
					check_envira_permissions(
						$(this).attr('id'),
						event.detail.value,
						event.detail.label,
						'remove'
					);
					disable_administrator();
				},
				false
			);
		}
	}

	// find all choices that has 'administrator' as a value and disable it.

	function disable_administrator() {
		$('.choices__item').each(function () {
			if ($(this).attr('data-value') === 'administrator') {
				$(this).removeClass('choices__item--selectable');
				$(this).removeAttr('data-deletable');
				$(this).removeAttr('data-item');
				$(this).addClass('demos');
				$(this).off('click');
			}
		});

		$('.choices__button').each(function () {
			if ($(this).attr('aria-label') === "Remove item: '\administrator\'") {
				$(this).hide();
			}
		});
	}

	disable_administrator();

	function check_envira_permissions(element, value, label, type) {
		if (!enviraPermissions || !enviraPermissions.hasOwnProperty(element)) {
			return;
		}
		// get other elements value and see if it is already selected.
		var permissionLabel = enviraPermissionsLabels[element];
		var other_elements = enviraPermissions[element];
		let choices_permissions = element + '_select';
		let reqPermissionLabel = get_envira_permissions(other_elements, type, value);
		reqPermissionLabel = reqPermissionLabel.filter(function (item) {
			return item !== permissionLabel;
		});
		let message = '';
		let permission_text = reqPermissionLabel.length > 1 ? 'permissions' : 'permission';

		if (type === 'add') {
			message = `<p>In order to give <strong>${permissionLabel}</strong> permission,
				<strong>${reqPermissionLabel}</strong> ${permission_text} ${reqPermissionLabel.length > 1 ? 'are' : 'is'} also required.</p>`;
			message += `<p>Would you like to also grant <strong>${reqPermissionLabel}</strong> ${permission_text} to ${label}?</p>`;
		} else {
			message = `<p>In order to remove <strong>${permissionLabel}</strong> permission,
				<strong>${reqPermissionLabel}</strong> ${permission_text} will also be removed.</p>`;
			message += `<p>Would you like to also remove <strong>${reqPermissionLabel}</strong> ${permission_text} to <strong>${label}</strong>?</p>`;
		}

		if (reqPermissionLabel.length > 0) {
			$('#envira-permissions-alert').html(message);
			perms_dialog.show();
			$('.envira-permissions-yes').on('click', function () {
				update_envira_permissions(other_elements, value, type);
				perms_dialog.hide();
			});
			$('.envira-permissions-cancel').on('click', function () {
				if (type === 'add') {
					enviraPermissionsChoices[choices_permissions].removeActiveItemsByValue(value);
				}
				if (type === 'remove') {
					enviraPermissionsChoices[choices_permissions].setChoiceByValue(value);
				}
				perms_dialog.hide();
			});
		} else {
			update_envira_permissions(other_elements, value, type);
		}
		// Hide the choices after the dialog is closed.
		setTimeout(() => enviraPermissionsChoices[choices_permissions].hideDropdown(), 0);
	}

	function update_envira_permissions(elements, value, type) {
		for (var i = 0; i < elements.length; i++) {
			let element = elements[i];

			// Select the dropdown element
			let selectElement = document.querySelector('#' + element);

			// Get all selected options
			let selectedOptions = selectElement.selectedOptions;

			// Initialize an array to store the selected values
			let selectedValues = [];

			var choices_permissions = element + '_select';
			let reqPermissionLabel = enviraPermissionsLabels[element];
			// Loop through all selected options and get their values
			for (let option of selectedOptions) {
				selectedValues.push(option.value);
			}

			let key = Object.keys(enviraPermissionsLabels).find(
				key => enviraPermissionsLabels[key] === reqPermissionLabel
			);
			let currentPermission = enviraPermissionsChoices[key + '_select'];
			// check if the value is in the selected values array.
			if (type === 'add') {
				if (selectedValues.indexOf(value) === -1) {
					selectedValues.push(value);
					currentPermission.setChoiceByValue(value);
				}
			} else {
				if (selectedValues.indexOf(value) !== -1) {
					selectedValues = selectedValues.filter(function (item) {
						return item !== value;
					});
					currentPermission.removeActiveItemsByValue(value);
				}
			}
		}
	}

	function get_envira_permissions(elements, type, value) {
		var reqPermissionLabel = [];

		for (var i = 0; i < elements.length; i++) {
			let element = elements[i];

			// Select the dropdown element
			let selectElement = document.querySelector('#' + element);

			// Get all selected options
			let selectedOptions = selectElement.selectedOptions;

			// Initialize an array to store the selected values
			let selectedValues = [];

			// Loop through all selected options and get their values
			for (let option of selectedOptions) {
				selectedValues.push(option.value);
			}

			if (type === 'add') {
				if (selectedValues.indexOf(value) === -1) {
					selectedValues.push(value);
					reqPermissionLabel.push(enviraPermissionsLabels[element]);
				}
			} else {
				if (selectedValues.indexOf(value) !== -1) {
					selectedValues = selectedValues.filter(function (item) {
						return item !== value;
					});
					reqPermissionLabel = reqPermissionLabel.filter(function (item) {
						return item !== enviraPermissionsLabels[element];
					});
				}
			}
		}

		return reqPermissionLabel;
	}
});
