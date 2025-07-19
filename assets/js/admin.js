jQuery(document).ready(function($) {
    // Actualizează dropdown-ul de serviciu default când se schimbă selecția
    $('select[name="woocommerce_eawb_shipping_available_services[]"]').on('change', function() {
        var available = $(this).val() || [];
        var $default = $('select[name="woocommerce_eawb_shipping_default_service"]');
        
        $default.find('option').each(function() {
            if ($(this).val() && !available.includes($(this).val())) {
                $(this).prop('disabled', true);
            } else {
                $(this).prop('disabled', false);
            }
        });
    }).trigger('change');
});