const prevBtns = document.querySelectorAll('.envira-onboarding-btn-prev');
const nextBtns = document.querySelectorAll('.envira-onboarding-btn-next');
const progress = document.querySelector('.envira-onboarding-progress');
const formSteps = document.querySelectorAll('.envira-onboarding-form-step');
const progressSteps = document.querySelectorAll('.envira-onboarding-progress-step');

let formStepsNum = 0;

/* Event Listener for Next Button. */
nextBtns.forEach(btn => {
	btn.addEventListener('click', () => {
		if (formStepsNum === 0) {
			saveFormData();
		} else {
			let nextStep = btn.getAttribute('data-next');
			if (nextStep) {
				formStepsNum = nextStep;
				updateFormSteps();
				updateProgressbar();
			}
		}
	});
});

/* Event Listener for Back Button. */
prevBtns.forEach(btn => {
	btn.addEventListener('click', () => {
		// Get data-prev attribute from the button and set it as stepsNum.
		let prevStep = btn.getAttribute('data-prev');
		if (prevStep) {
			formStepsNum = prevStep;
			updateFormSteps();
			updateProgressbar();
		}
	});
});

/* Updates Form Items */
function updateFormSteps() {
	formSteps.forEach(formStep => {
		formStep.classList.contains('envira-onboarding-form-step-active') &&
			formStep.classList.remove('envira-onboarding-form-step-active');
	});
	formSteps[formStepsNum].classList.add('envira-onboarding-form-step-active');

	// Show selected plugins div only on step 2.
	if (formStepsNum === '2') {
		selectedPluginsdiv.style.display = 'block';
	} else {
		selectedPluginsdiv.style.display = 'none';
	}
}

/* Updates Progress Bar */
function updateProgressbar() {
	progressSteps.forEach((progressStep, index) => {
		let spacer = progressStep.previousElementSibling;
		if (index <= formStepsNum) {
			progressStep.classList.add('envira-onboarding-progress-step-active');
			spacer.style.borderColor = '#37993B';
		} else {
			progressStep.classList.remove('envira-onboarding-progress-step-active');
			spacer.style.borderColor = '#DCDDE1';
		}
	});
	progress.style.width = (formStepsNum / (progressSteps.length - 1)) * 100 + '%';
}

// when envira-onboarding-back-to-welcome or envira-get-started-btn is clicked, show and hide the onboarding wizard pages.
const backToWelcomeBtn = document.querySelector('#envira-onboarding-back-to-welcome');
const getStartedBtn = document.querySelector('#envira-get-started-btn');

backToWelcomeBtn.addEventListener('click', () => {
	document.querySelector('.envira-onboarding-wizard-intro').style = {display: 'flex'};
	document.querySelector('.envira-onboarding-wizard-wrapper').style = 'height: 85vh';
	document.querySelector('.envira-onboarding-wizard-pages').style.display = 'none';
});

getStartedBtn.addEventListener('click', () => {
	document.querySelector('.envira-onboarding-wizard-intro').style.display = 'none';
	document.querySelector('.envira-onboarding-wizard-wrapper').style = 'height: auto';
	document.querySelector('.envira-onboarding-wizard-pages').style = {display: 'flex'};
});

// Disable click on no-clickable checkboxes with class no-clicks.

const noClicks = document.querySelectorAll('.no-clicks');
noClicks.forEach(noClick => {
	noClick.addEventListener('click', e => {
		e.preventDefault();
		e.stopPropagation();
	});
});

// set height on page load.
document.querySelector('.envira-onboarding-wizard-wrapper').style = 'height: 85vh';

function isValidEmail(email) {
	let emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
	return emailRegex.test(email);
}

// Check if email_address is valid.
const email = document.querySelector('#email_address');
const saveOptIn = document.querySelector('#save-opt-in');
let emailError = document.querySelector('.envira-email-error');
emailError.innerHTML = '';

const setEmailValid = () => {
	emailError.innerHTML = '';
	saveOptIn.disabled = false;
	saveOptIn.classList.remove('envira-disabled');
};

const setEmailInvalid = () => {
	emailError.innerHTML = 'Please enter a valid email address.';
	saveOptIn.disabled = true;
	saveOptIn.classList.add('envira-disabled');
};

// If the user opt in for email, then check if the email is valid or not empty.

let email_opt_in = document.querySelector('#email_opt_in');

email_opt_in.addEventListener('change', () => {
	emailValidation();
});

let emailValidation = () => {
	// If email opt in is checked and email is invalid, show error message.
	if ((email.value === '' || !isValidEmail(email.value)) && email_opt_in.checked) {
		setEmailInvalid();
	}
	// If email opt in is not checked and email is invalid, show error message.
	if (email.value !== '' && !isValidEmail(email.value) && !email_opt_in.checked) {
		setEmailInvalid();
	}
	// If email opt in is not checked and email is valid or empty, remove the error message.
	if (
		(email.value !== '' && isValidEmail(email.value)) ||
		(email.value === '' && !email_opt_in.checked)
	) {
		setEmailValid();
	}
};

email.addEventListener('input', () => {
	emailValidation();
});

// Show others option when something else is selected for Drip.
// if user_type radio buttons are not checked disable the save button.
const userTypes = document.querySelectorAll("input[name='eow[_user_type]']");

userTypes.forEach(userType => {
	userType.addEventListener('change', e => {
		if (e.target.value === 'other') {
			document.querySelector('#others_div').style.display = 'block';
			document.querySelector('#others').required = true;
		} else {
			document.querySelector('#others_div').style.display = 'none';
			document.querySelector('#others').required = false;
		}
		let isAnyUserTypeChecked = Array.from(userTypes).some(radio => radio.checked);

		if (!isAnyUserTypeChecked) {
			saveOptIn.disabled = true;
			saveOptIn.classList.add('envira-disabled');
		} else {
			saveOptIn.disabled = false;
			saveOptIn.classList.remove('envira-disabled');
		}
	});
});

function saveFormData() {
	// post form data via WP admin-ajax. enviraOnboardingWizard.ajaxUrl,
	const form = document.querySelector('#envira-general');
	// Disable form submit.
	form.addEventListener('submit', async e => {
		e.preventDefault();
		e.stopPropagation();

		const formData = new FormData(form);

		formData.append('action', 'save_onboarding_data');
		formData.append('nonce', enviraOnboardingWizard.nonce);
		try {
			const response = await fetch(enviraOnboardingWizard.ajaxUrl, {
				method: 'POST',
				body: formData
			});
			const data = await response.json();
			if (data.success) {
				formStepsNum = 1;
				updateFormSteps();
				updateProgressbar();
			} else {
				formStepsNum = 0;
				console.log('Error saving the data');
			}
		} catch (error) {
			formStepsNum = 0;
			console.error('Error:', error);
		}
	});
}
// Get all the checkboxes with the class feature.
const features = document.querySelectorAll('.feature');
let selectedFeatures = [];
features.forEach(feature => {
	feature.addEventListener('click', e => {
		if (e.target.checked) {
			if (!selectedFeatures.includes(e.target.value)) {
				selectedFeatures.push(e.target.value);
				// Find the element with e.target.value + -desc and show it.
				document.querySelector(`#${e.target.value}-desc`).style.display = 'block';
			}
		} else {
			selectedFeatures = selectedFeatures.filter(feature => feature !== e.target.value);
			document.querySelector(`#${e.target.value}-desc`).style.display = 'none';
		}
	});
});

// Save selected features to the database.
const saveFeaturesBtn = document.querySelector('#envira-save-features');
saveFeaturesBtn.addEventListener('click', () => {
	if (selectedFeatures.length === 0) {
		return;
	}

	let requestData = {
		action: 'save_selected_addons',
		addons: selectedFeatures,
		nonce: enviraOnboardingWizard.nonce
	};

	fetch(enviraOnboardingWizard.ajaxUrl, {
		method: 'post',
		headers: {'Content-Type': 'application/x-www-form-urlencoded'},
		body: new URLSearchParams(requestData).toString()
	})
		.then(response => response.json())
		.then(data => {
			if (data.success) {
				// Show success message.
			}
		})
		.catch(error => {
			console.error('Error:', error);
		});

	displaySelectedAddons();
});

// Get all the checkboxes with the class recommended.
const recommendedPlugins = document.querySelectorAll('.recommended');
let selectedRecommended = [];
let selectedPluginsdiv = document.querySelector('.selected-plugins-names');
let selectedPluginsNames = [];
recommendedPlugins.forEach(recommended => {
	recommended.addEventListener('click', e => {
		if (e.target.checked) {
			selectedRecommended.push(e.target.value);
			selectedPluginsNames.push(e.target.getAttribute('data-name'));
			// Find the element with e.target.value + -desc and show it.
			document.querySelector(`#${e.target.value}-desc`).style.display = 'block';
		} else {
			selectedRecommended = selectedRecommended.filter(
				recommended => recommended !== e.target.value
			);
			selectedPluginsNames = selectedPluginsNames.filter(
				name => name !== e.target.getAttribute('data-name')
			);
			document.querySelector(`#${e.target.value}-desc`).style.display = 'none';
		}
		displaySelectedPlugins();
	});
});

// Display selected recommended plugins based on selection.
let displaySelectedPlugins = () => {
	selectedPluginsdiv.innerHTML = '';

	if (selectedPluginsNames.length === 0) {
		// check if there are any selected recommended plugins.
		recommendedPlugins.forEach(recommended => {
			// get the checked recommended plugins that are not in the selectedRecommended array.
			if (
				recommended.checked &&
				!selectedRecommended.includes(recommended.value) &&
				!enviraOnboardingWizard.plugins_list.includes(recommended.value)
			) {
				selectedPluginsNames.push(recommended.getAttribute('data-name'));
				document.querySelector(`#${recommended.value}-desc`).style.display = 'block';
			}
		});
	}

	if (selectedPluginsNames.length > 0) {
		selectedPluginsdiv.innerHTML = 'The following plugins will be installed: ';
	}

	selectedPluginsNames.forEach(name => {
		let plugin = document.createElement('span');
		plugin.innerHTML = `${name}`;
		selectedPluginsdiv.appendChild(plugin);
		// Append comma after each plugin name but not after the last plugin name.
		if (selectedPluginsNames.indexOf(name) !== selectedPluginsNames.length - 1) {
			let comma = document.createElement('span');
			comma.innerHTML = ', ';
			selectedPluginsdiv.appendChild(comma);
		}
	});
};

displaySelectedPlugins();

// Install the selected recommended plugins.
const installBtn = document.querySelector('#envira-install-recommended');
installBtn.addEventListener('click', () => {
	if (selectedRecommended.length === 0) {
		// check if there are any selected recommended plugins.
		recommendedPlugins.forEach(recommended => {
			// get the checked recommended plugins that are not in the selectedRecommended array.
			if (recommended.checked && !selectedRecommended.includes(recommended.value)) {
				selectedRecommended.push(recommended.value);
			}
		});
	}

	let requestData = {
		action: 'install_recommended_plugins',
		plugins: selectedRecommended,
		nonce: enviraOnboardingWizard.nonce
	};

	fetch(enviraOnboardingWizard.ajaxUrl, {
		method: 'post',
		headers: {'Content-Type': 'application/x-www-form-urlencoded'},
		body: new URLSearchParams(requestData).toString()
	})
		.then(response => response.json())
		.then(data => {
			if (data.success) {
				// Show success message.
			}
		})
		.catch(error => {
			console.error('Error:', error);
		});
});

// Insert selected addons to #selected-add-ons div.

let tickSvg = `<svg class=envira-checkmark fill=none viewBox="0 0 14 11" xmlns=http://www.w3.org/2000/svg>
<path clip-rule=evenodd d="M10.8542 1.37147C11.44 0.785682 12.3897 0.785682 12.9755 1.37147C13.5613 1.95726 13.5613 2.907 12.9755 3.49279L6.04448 10.4238C5.74864 10.7196 5.35996 10.8661 4.97222 10.8631C4.58548 10.8653 4.19805 10.7189 3.90298 10.4238L1.0243 7.5451C0.438514 6.95931 0.438514 6.00956 1.0243 5.42378C1.61009 4.83799 2.55983 4.83799 3.14562 5.42378L4.97374 7.2519L10.8542 1.37147Z" fill=currentColor fill-rule=evenodd></path>
</svg>`;

let selectedAddons = document.querySelector('#selected-add-ons');

function displaySelectedAddons() {
	// Find all the selected-addon-item divs and remove them, to avoid duplicates.
	let selectedAddonItems = document.querySelectorAll('.selected-addon-item');
	selectedAddonItems.forEach(item => {
		item.remove();
	});

	selectedFeatures.forEach(feature => {
		const addon = document.createElement('div');
		addon.classList.add(
			'envira-col',
			'col-sm-6',
			'col-xs-12',
			'envira-col',
			'text-xs-left',
			'selected-addon-item'
		);
		// Get data-name attribute from the checkbox by its name.
		let addonName = document.querySelector(`input[name="${feature}"]`).getAttribute('data-name');
		addon.innerHTML = `${tickSvg}${addonName}</div>`;
		selectedAddons.appendChild(addon);
		// show the desc of the selected feature.
		document.querySelector(`#${feature}-desc`).style.display = 'block';
	});
}

// Verify license key.
const verifyBtn = document.querySelector('.envira-gallery-verify-submit');
const successMessage = document.querySelector('#license-key-message');
let loadingSpinner = document.querySelector('.envira-onboarding-spinner');

/**
 * Lite install addons while verifying the license key.
 * Introduced onboarding: 'install_addons', param for installing addons.
 * Pro installs addons after verifying the license key via separate ajax call.
 */
verifyBtn.addEventListener('click', e => {
	e.preventDefault();

	let licenseKey = document.getElementById('envira-settings-key').value;

	if (licenseKey === '') {
		successMessage.classList.add('envira-error');
		successMessage.innerHTML = 'Please enter your license key.';
		return;
	}

	// Show spinner.
	loadingSpinner.style.visibility = 'visible';
	verifyBtn.classList.add('envira-disabled');
	successMessage.classList.remove('envira-success', 'envira-error');

	let requestData = {
		action: 'envira_connect',
		key: licenseKey,
		onboarding: 'install_addons',
		_wpnonce: enviraOnboardingWizard.connect_nonce
	};

	let toggleButtonsVisibility = () => {
		loadingSpinner.style.visibility = 'hidden';
		verifyBtn.disabled = false;
		verifyBtn.classList.remove('envira-disabled');
	};

	fetch(enviraOnboardingWizard.ajaxUrl, {
		method: 'post',
		headers: {'Content-Type': 'application/x-www-form-urlencoded'},
		body: new URLSearchParams(requestData).toString()
	})
		.then(response => response.json())
		.then(data => {
			if (data.success) {
				successMessage.classList.add('envira-success');
				successMessage.innerHTML = data.data.message;
				toggleButtonsVisibility();
			} else {
				successMessage.classList.add('envira-error');
				successMessage.innerHTML = data.data.message;
				toggleButtonsVisibility();
			}
		})
		.catch(error => {
			successMessage.classList.add('envira-error');
			successMessage.innerHTML = 'Error verifying the license key.';
			console.log('Error:', error);
			toggleButtonsVisibility();
		});
});

// Show body content once it is loaded.
let domReady = cb => {
	document.readyState === 'interactive' || document.readyState === 'complete'
		? cb()
		: document.addEventListener('DOMContentLoaded', cb);
};

domReady(() => {
	// Display body when DOM is loaded
	document.body.style.visibility = 'visible';
});
