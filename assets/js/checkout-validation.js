/**
 * Phone validation field highlighting for WooCommerce classic checkout.
 *
 * Presentation only. The server adds the error with the field id
 * (array( 'id' => 'billing_phone' )) and WooCommerce renders that id as a
 * data-id attribute on the notice. The field is located through that
 * attribute, never through the (translatable) message text.
 *
 * Where the attribute sits depends on which notice templates WooCommerce uses:
 * - classic themes: <ul class="woocommerce-error"><li data-id="billing_phone">,
 *   one li per error;
 * - block themes (and classic themes that opt in through the
 *   woocommerce_use_block_notices_in_classic_theme filter): one error puts it on
 *   the banner itself, <div class="wc-block-components-notice-banner is-error"
 *   data-id="billing_phone">; several errors put it on <li data-id="..."> items
 *   inside that banner, which then has no data-id of its own.
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
	 * Build a selector matching an error notice for a field, in any of the
	 * notice markups described at the top of this file.
	 *
	 * @param {string} fieldId Input id, e.g. 'billing_phone'.
	 * @return {string} jQuery selector.
	 */
	function getErrorNoticeSelector(fieldId) {
		var dataId = '[data-id="' + fieldId + '"]';

		return [
			'.woocommerce-error li' + dataId,
			'.wc-block-components-notice-banner.is-error' + dataId,
			'.wc-block-components-notice-banner.is-error li' + dataId
		].join(', ');
	}

	/**
	 * Highlight phone fields that have a server-side error notice.
	 */
	function processCheckoutErrors() {
		$.each(PHONE_FIELDS, function(index, fieldId) {
			if ($(getErrorNoticeSelector(fieldId)).length) {
				highlightField(fieldId);
			}
		});
	}

	$(function() {
		// Notices rendered with the page (non-AJAX submit).
		processCheckoutErrors();

		// Notices returned by the AJAX checkout submit. By then WooCommerce has
		// removed the previous notices and re-validated every field, which
		// clears a highlight left over from an earlier submit.
		// Deliberately not bound to updated_checkout: a successful order review
		// update leaves the old checkout notices in place, so re-reading them
		// would highlight a number the customer has already corrected.
		$(document.body).on('checkout_error', processCheckoutErrors);

		// Clear the highlight as soon as the customer edits the number.
		$(document.body).on('input', '#billing_phone, #shipping_phone', function() {
			clearFieldError(this.id);
		});
	});

})(jQuery);
