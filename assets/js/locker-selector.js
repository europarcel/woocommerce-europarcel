(function ($) {
    'use strict';

    function initializeLockerSelector() {
        const addLockerButton = () => {
                // Handle WooCommerce Blocks checkout
                const blockShippingOptions = document.querySelectorAll('.wc-block-components-radio-control__option-layout');
                blockShippingOptions.forEach(option => {
                    if ((option.textContent.toLowerCase().includes('locker') || option.textContent.toLowerCase().includes('la locker')) && !option.querySelector('.select-locker-btn')) {
                        const button = document.createElement('button');
                        button.textContent = 'Selectare locker';
                        button.className = 'button select-locker-btn';
                        button.style.marginTop = '10px';
                        button.style.backgroundColor = '#ebe9eb';
                        button.style.color = '#515151';
                        button.style.padding = '5px 10px';
                        button.style.border = '1px solid #d3ced2';
                        button.style.borderRadius = '3px';
                        button.style.cursor = 'pointer';
                        button.onclick = handleLockerSelection;
                        option.appendChild(button);
                    }
                });
                
                // Handle Classic WooCommerce checkout
                const classicShippingOptions = document.querySelectorAll('#shipping_method li');
                classicShippingOptions.forEach(option => {
                    if ((option.textContent.toLowerCase().includes('locker') || option.textContent.toLowerCase().includes('la locker')) && !option.querySelector('.select-locker-btn')) {
                        const button = document.createElement('button');
                        button.textContent = 'Selectare locker';
                        button.className = 'button select-locker-btn';
                        button.style.marginTop = '10px';
                        button.style.backgroundColor = '#ebe9eb';
                        button.style.color = '#515151';
                        button.style.padding = '5px 10px';
                        button.style.border = '1px solid #d3ced2';
                        button.style.borderRadius = '3px';
                        button.style.cursor = 'pointer';
                        button.onclick = handleLockerSelection;
                        option.appendChild(button);
                    }
                });
            };
        
        function handleLockerSelection() {
            const instanceId = getSelectedShippingInstanceId();
            showLoadingState();
            
            Promise.all([
                fetchLockers(instanceId),
                fetchLockerServices(instanceId)
            ])
            .then(([lockersResult, servicesResult]) => {
                hideLoadingState();
                
                if (lockersResult.success && servicesResult.success) {
                    window.EuroparcelModal.showWithData({
                        lockers: lockersResult.data,
                        services: servicesResult.data,
                        instanceId: instanceId
                    });
                } else {
                    alert('Nu sunt lockere disponibile.');
                }
            })
            .catch(() => {
                hideLoadingState();
                alert('Eroare la încărcarea lockerelor!');
            });
        }
        
        function getSelectedShippingInstanceId() {
            let selectedMethod = document.querySelector('input[name^="shipping_method"]:checked') || 
                               document.querySelector('.wc-block-components-radio-control__input:checked');
            
            if (selectedMethod) {
                const methodValue = selectedMethod.value || selectedMethod.id;
                const parts = methodValue.split(':');
                if (parts.length > 1) return parts[1];
                
                const underscoreParts = methodValue.split('_');
                if (underscoreParts.length > 1) return underscoreParts[underscoreParts.length - 1];
            }
            
            return '1';
        }
        
        function fetchLockers(instanceId) {
            return new Promise((resolve, reject) => {
                $.ajax({
                    url: EuroparcelLockerData.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'eawb_get_lockers',
                        security: EuroparcelLockerData.nonce,
                        instance_id: instanceId,
                        country: $('#shipping-country').find(":selected").val(),
                        state: $('#shipping-state').find(":selected").text(),
                        city: $('#shipping-city').val()
                    },
                    dataType: 'json',
                    success: function(response) {
                        resolve(response);
                    },
                    error: function(xhr, textStatus, errorThrown) {
                        reject({
                            xhr: xhr,
                            textStatus: textStatus,
                            errorThrown: errorThrown
                        });
                    }
                });
            });
        }
        
        function fetchLockerServices(instanceId) {
            return new Promise((resolve, reject) => {
                $.ajax({
                    url: EuroparcelLockerData.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'eawb_get_locker_services',
                        security: EuroparcelLockerData.nonce,
                        instance_id: instanceId
                    },
                    dataType: 'json',
                    success: function(response) {
                        resolve(response);
                    },
                    error: function(xhr, textStatus, errorThrown) {
                        reject({
                            xhr: xhr,
                            textStatus: textStatus,
                            errorThrown: errorThrown
                        });
                    }
                });
            });
        }
        
        function showLoadingState() {
            document.querySelectorAll('.select-locker-btn').forEach(btn => {
                btn.disabled = true;
                btn.textContent = 'Se încarcă...';
            });
        }
        
        function hideLoadingState() {
            document.querySelectorAll('.select-locker-btn').forEach(btn => {
                btn.disabled = false;
                btn.textContent = 'Selectare locker';
            });
        }

        new MutationObserver(() => addLockerButton()).observe(document.body, {childList: true, subtree: true});
        
        addLockerButton();
        
        $(document.body).on('updated_checkout updated_shipping_method', () => {
            setTimeout(addLockerButton, 100);
        });
    }

    $(document).ready(initializeLockerSelector);

})(jQuery);