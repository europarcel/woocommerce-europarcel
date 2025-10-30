/**
 * EuroParcel Admin JavaScript
 *
 * Handles admin interface functionality including service selection
 * updates and price type toggling in the shipping method configuration.
 *
 * @package    Europarcel
 * @since      1.0.1
 */

jQuery(document).ready(function ($) {
	'use strict';

	// Update default service dropdown when available services change
	$('select[name="woocommerce_europarcel_shipping_available_services[]"]').on('change', function () {
		var available = $(this).val() || [];
		var $default = $('select[name="woocommerce_europarcel_shipping_default_service"]');

		$default.find('option').each(function () {
			if ($(this).val() && !available.includes($(this).val()) && $(this).val() !== 'none') {
				$(this).prop('disabled', true);
			} else {
				$(this).prop('disabled', false);
			}
		});
	}).trigger('change');

	/**
	 * Toggle price type dependent fields
	 */
	function toggleFields() {
		const priceType = $('.europarcel-price-type-selector').val();
		$('.europarcel-price-type-dependent').closest('tr').hide();

		if (priceType === 'fixed') {
			$('.europarcel-fixed-price').closest('tr').show();
		} else {
			$('.europarcel-calculated-price').closest('tr').show();
		}
	}

	// Event handlers
	$('.europarcel-price-type-selector').on('change', toggleFields);
	toggleFields();
});