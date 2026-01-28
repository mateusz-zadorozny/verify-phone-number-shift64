/**
 * Phone validation field highlighting for WooCommerce classic checkout.
 *
 * @package Shift64\SmartPhoneValidation
 */

(function($) {
	'use strict';

	if (typeof $ === 'undefined') {
		return;
	}

	/**
	 * Highlight phone field with error styling.
	 *
	 * @param {string} fieldId The field wrapper ID (e.g., 'billing_phone_field').
	 */
	function highlightField(fieldId) {
		var $field = $('#' + fieldId);
		if ($field.length) {
			$field.addClass('woocommerce-invalid woocommerce-invalid-required-field');
			$field.find('input').attr('aria-invalid', 'true');
		}
	}

	/**
	 * Remove error styling from phone field.
	 *
	 * @param {string} fieldId The field wrapper ID.
	 */
	function clearFieldError(fieldId) {
		var $field = $('#' + fieldId);
		if ($field.length) {
			$field.removeClass('woocommerce-invalid woocommerce-invalid-required-field');
			$field.find('input').attr('aria-invalid', 'false');
		}
	}

	/**
	 * Check if notice contains our phone validation error.
	 *
	 * @param {string} noticeText The notice text content.
	 * @return {Object|null} Object with field info or null.
	 */
	function detectPhoneError(noticeText) {
		var billingErrors = [
			'Proszę podać prawidłowy numer telefonu',
			'Please enter a valid phone number',
			'Numer musi zawierać prefiks kraju',
			'Number must contain country prefix'
		];

		var shippingErrors = [
			'Proszę podać prawidłowy numer telefonu do wysyłki',
			'Please enter a valid shipping phone number',
			'Numer telefonu do wysyłki musi zawierać prefiks kraju',
			'Shipping phone number must contain country prefix'
		];

		for (var i = 0; i < billingErrors.length; i++) {
			if (noticeText.indexOf(billingErrors[i]) !== -1) {
				return { field: 'billing_phone_field' };
			}
		}

		for (var j = 0; j < shippingErrors.length; j++) {
			if (noticeText.indexOf(shippingErrors[j]) !== -1) {
				return { field: 'shipping_phone_field' };
			}
		}

		return null;
	}

	/**
	 * Process checkout errors and highlight relevant fields.
	 */
	function processCheckoutErrors() {
		// Clear previous phone field errors.
		clearFieldError('billing_phone_field');
		clearFieldError('shipping_phone_field');

		// Check notices for phone validation errors.
		$('.woocommerce-error li, .woocommerce-error').each(function() {
			var noticeText = $(this).text();
			var errorInfo = detectPhoneError(noticeText);

			if (errorInfo) {
				highlightField(errorInfo.field);

				// Scroll to field if it's the first error.
				var $field = $('#' + errorInfo.field);
				if ($field.length && !$field.is(':visible')) {
					$('html, body').animate({
						scrollTop: $field.offset().top - 100
					}, 500);
				}
			}
		});
	}

	// Initialize on document ready.
	$(document).ready(function() {
		// Process errors on page load (in case of page reload with errors).
		processCheckoutErrors();

		// Process errors after checkout update.
		$(document.body).on('checkout_error', function() {
			processCheckoutErrors();
		});

		// Process errors when notices are updated.
		$(document.body).on('updated_checkout', function() {
			processCheckoutErrors();
		});

		// Clear field error when user starts typing.
		$('#billing_phone').on('input', function() {
			clearFieldError('billing_phone_field');
		});

		$('#shipping_phone').on('input', function() {
			clearFieldError('shipping_phone_field');
		});
	});

})(jQuery);
