jQuery(document).ready(function ($) {
    const checkoutForm = document.querySelector('form.wc-block-checkout__form');
    if (checkoutForm) {
        checkoutForm.addEventListener('change', function (e) {
            if (e.target.closest('.wc-block-components-shipping-rates-control')) {
                handleShippingMethodChange();
            }
        });
    }
    function handleShippingMethodChange() {
        const selectedMethod = document.querySelector(
                'input[name^="shipping_method"]:checked, .wc-block-components-radio-control__input:checked'
                );
        if (selectedMethod) {
            const methodValue = selectedMethod.value || selectedMethod.id;
            if (methodValue.includes('_locker')) {
                const instanceId = methodValue.split('_').pop();
                showLockerModal(instanceId);
            } else {
                removeLockerSelection();
            }
        }
    }
    async function showLockerModal(instanceId) {
        var x = 0;
    }
    function removeLockerSelection() {
        const lockerDisplay = document.querySelector('.eawb-selected-locker');
        if (lockerDisplay) {
            lockerDisplay.remove();
        }
    }

});
