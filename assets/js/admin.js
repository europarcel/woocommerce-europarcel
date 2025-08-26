jQuery(document).ready(function ($) {
    // Actualizează dropdown-ul de serviciu default când se schimbă selecția
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


    function toggleFields() {
        const priceType = $('.europarcel-price-type-selector').val();
        $('.europarcel-price-type-dependent').closest('tr').hide();

        if (priceType === 'fixed') {
            $('.europarcel-fixed-price').closest('tr').show();
        } else {
            $('.europarcel-calculated-price').closest('tr').show();
        }
    }

    // Ascultă evenimente
    $('.europarcel-price-type-selector').on('change', toggleFields);
    toggleFields();
});