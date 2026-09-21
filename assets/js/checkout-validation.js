/**
 * Phone validation field highlighting for WooCommerce classic checkout.
 *
 * Presentation only. The server adds the error with the field id
 * (array( 'id' => 'billing_phone' )), WooCommerce renders it as
 * <li data-id="billing_phone">. The field is located through that attribute,
 * never through the (translatable) message text.
 *
 * Scrolling and inline messages are left to WooCommerce / the theme.
 *
 * @package Shift64\SmartPhoneValidation
 */

(function($) {
	'use strict';

	if (typeof $ === 'undefined') {
		return;
	}

	var PHONE_FIELDS = ['billing_phone', 'shipping_phone'];
	var INVALID_CLASSES = 'woocommerce-invalid woocommerce-invalid-phone';

	/**
	 * Get the form row wrapping a phone input.
	 *
	 * @param {string} fieldId Input id, e.g. 'billing_phone'.
	 * @return {jQuery} The form row (may be empty).
	 */
	function getFieldRow(fieldId) {
		return $('#' + fieldId + '_field');
	}

	function highlightField(fieldId) {
		var $row = getFieldRow(fieldId);
		$row.removeClass('woocommerce-validated').addClass(INVALID_CLASSES);
		$row.find('input').attr('aria-invalid', 'true');
	}

	function clearFieldError(fieldId) {
		var $row = getFieldRow(fieldId);
		$row.removeClass(INVALID_CLASSES);
		$row.find('input').removeAttr('aria-invalid');
	}

	/**
	 * Highlight phone fields that have a server-side error notice.
	 */
	function processCheckoutErrors() {
		$.each(PHONE_FIELDS, function(index, fieldId) {
			if ($('.woocommerce-error li[data-id="' + fieldId + '"]').length) {
				highlightField(fieldId);
			}
		});
	}

	$(function() {
		// Notices rendered with the page (non-AJAX submit).
		processCheckoutErrors();

		// Notices returned by the AJAX checkout submit.
		$(document.body).on('checkout_error', processCheckoutErrors);

		// Clear the highlight as soon as the customer edits the number.
		$(document.body).on('input', '#billing_phone, #shipping_phone', function() {
			clearFieldError(this.id);
		});
	});

})(jQuery);
