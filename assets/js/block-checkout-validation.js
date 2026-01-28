/**
 * Phone validation field highlighting for WooCommerce block checkout.
 *
 * @package Shift64\SmartPhoneValidation
 */

(function() {
	'use strict';

	/**
	 * Error messages to detect (both PL and EN).
	 */
	var phoneErrors = {
		billing: [
			'Telefon do płatności nie jest prawidłowym numerem telefonu',
			'Telefon do płatności musi zawierać prefiks kraju',
			'Billing phone is not a valid phone number',
			'Billing phone must contain country prefix'
		],
		shipping: [
			'Telefon do wysyłki nie jest prawidłowym numerem telefonu',
			'Telefon do wysyłki musi zawierać prefiks kraju',
			'Shipping phone is not a valid phone number',
			'Shipping phone must contain country prefix'
		]
	};

	/**
	 * Find phone input field in block checkout.
	 *
	 * @param {string} type 'billing' or 'shipping'.
	 * @return {Element|null} The input element or null.
	 */
	function findPhoneField(type) {
		// Try various selectors used by WooCommerce Blocks.
		var selectors = [
			'#' + type + '-phone',
			'#' + type + '_phone',
			'[name="' + type + '-phone"]',
			'[name="' + type + '_phone"]',
			'input[id*="' + type + '"][id*="phone"]',
			'.wc-block-components-address-form__' + type + ' input[autocomplete="tel"]',
			'.wc-block-components-address-form input[id*="phone"]'
		];

		for (var i = 0; i < selectors.length; i++) {
			var field = document.querySelector(selectors[i]);
			if (field) {
				return field;
			}
		}

		return null;
	}

	/**
	 * Find the field wrapper element.
	 *
	 * @param {Element} input The input element.
	 * @return {Element|null} The wrapper element or null.
	 */
	function findFieldWrapper(input) {
		if (!input) return null;

		// Look for WooCommerce Blocks field wrapper.
		var wrapper = input.closest('.wc-block-components-text-input');
		if (wrapper) return wrapper;

		// Fallback to parent elements.
		wrapper = input.closest('.wc-block-components-address-form__phone');
		if (wrapper) return wrapper;

		return input.parentElement;
	}

	/**
	 * Add error styling to a field.
	 *
	 * @param {string} type 'billing' or 'shipping'.
	 */
	function highlightField(type) {
		var input = findPhoneField(type);
		if (!input) return;

		var wrapper = findFieldWrapper(input);
		if (wrapper) {
			wrapper.classList.add('has-error');
			wrapper.setAttribute('data-phone-validation-error', 'true');
		}

		input.classList.add('has-error');
		input.setAttribute('aria-invalid', 'true');

		// Add error border style directly.
		input.style.borderColor = '#cc1818';
		input.style.boxShadow = '0 0 0 1px #cc1818';
	}

	/**
	 * Remove error styling from a field.
	 *
	 * @param {string} type 'billing' or 'shipping'.
	 */
	function clearFieldError(type) {
		var input = findPhoneField(type);
		if (!input) return;

		var wrapper = findFieldWrapper(input);
		if (wrapper) {
			wrapper.classList.remove('has-error');
			wrapper.removeAttribute('data-phone-validation-error');
		}

		input.classList.remove('has-error');
		input.setAttribute('aria-invalid', 'false');
		input.style.borderColor = '';
		input.style.boxShadow = '';
	}

	/**
	 * Check if text contains a phone error.
	 *
	 * @param {string} text The error text.
	 * @return {string|null} 'billing', 'shipping', or null.
	 */
	function detectPhoneErrorType(text) {
		for (var i = 0; i < phoneErrors.billing.length; i++) {
			if (text.indexOf(phoneErrors.billing[i]) !== -1) {
				return 'billing';
			}
		}

		for (var j = 0; j < phoneErrors.shipping.length; j++) {
			if (text.indexOf(phoneErrors.shipping[j]) !== -1) {
				return 'shipping';
			}
		}

		return null;
	}

	/**
	 * Process all error notices on the page.
	 */
	function processErrors() {
		// Clear previous errors.
		clearFieldError('billing');
		clearFieldError('shipping');

		// Look for WooCommerce Blocks error notices.
		var errorNotices = document.querySelectorAll(
			'.wc-block-components-notice-banner.is-error, ' +
			'.wc-block-store-notice.is-error, ' +
			'.woocommerce-error, ' +
			'[role="alert"]'
		);

		errorNotices.forEach(function(notice) {
			var text = notice.textContent || '';
			var errorType = detectPhoneErrorType(text);

			if (errorType) {
				highlightField(errorType);

				// Scroll field into view.
				var input = findPhoneField(errorType);
				if (input) {
					setTimeout(function() {
						input.scrollIntoView({ behavior: 'smooth', block: 'center' });
						input.focus();
					}, 100);
				}
			}
		});
	}

	/**
	 * Set up input listeners to clear errors on typing.
	 */
	function setupInputListeners() {
		['billing', 'shipping'].forEach(function(type) {
			var input = findPhoneField(type);
			if (input && !input.hasAttribute('data-phone-validation-listener')) {
				input.setAttribute('data-phone-validation-listener', 'true');
				input.addEventListener('input', function() {
					clearFieldError(type);
				});
			}
		});
	}

	/**
	 * Initialize the validation highlighting.
	 */
	function init() {
		// Process errors on page load.
		processErrors();
		setupInputListeners();

		// Watch for DOM changes (new error notices, field changes).
		var observer = new MutationObserver(function(mutations) {
			var shouldProcess = false;

			mutations.forEach(function(mutation) {
				// Check if error notices were added.
				if (mutation.addedNodes.length > 0) {
					mutation.addedNodes.forEach(function(node) {
						if (node.nodeType === 1) {
							if (node.classList && (
								node.classList.contains('wc-block-components-notice-banner') ||
								node.classList.contains('wc-block-store-notice') ||
								node.classList.contains('woocommerce-error') ||
								node.getAttribute('role') === 'alert'
							)) {
								shouldProcess = true;
							}
						}
					});
				}
			});

			if (shouldProcess) {
				setTimeout(function() {
					processErrors();
					setupInputListeners();
				}, 50);
			}
		});

		observer.observe(document.body, {
			childList: true,
			subtree: true
		});

		// Also listen for WooCommerce Blocks events.
		document.addEventListener('wc-blocks_added_to_cart', processErrors);
		document.addEventListener('wc-blocks_removed_from_cart', processErrors);

		// Re-setup input listeners periodically (for dynamically loaded fields).
		setInterval(setupInputListeners, 2000);
	}

	// Start when DOM is ready.
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}

})();
