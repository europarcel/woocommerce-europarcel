(function ($) {
    'use strict';

    function initializeLockerSelector() {
        const addLockerButton = () => {
                // Handle WooCommerce Blocks checkout
                const blockShippingOptions = document.querySelectorAll('.wc-block-components-radio-control__option-layout');
                blockShippingOptions.forEach((option, index) => {
                    if ((option.textContent.toLowerCase().includes('locker') || option.textContent.toLowerCase().includes('la locker')) && !option.querySelector('.select-locker-btn')) {
                        const button = document.createElement('button');
                        button.textContent = 'Selectare locker';
                        button.className = 'button select-locker-btn';
                        button.type = 'button'; // CRUCIAL: Prevent form submission
                        button.style.marginTop = '10px';
                        button.style.backgroundColor = '#ebe9eb';
                        button.style.color = '#515151';
                        button.style.padding = '5px 10px';
                        button.style.border = '1px solid #d3ced2';
                        button.style.borderRadius = '3px';
                        button.style.cursor = 'pointer';
                        button.style.display = 'none'; // Hidden by default
                        button.onclick = function(event) {
                            event.preventDefault();
                            event.stopPropagation();
                            handleLockerSelection();
                        };
                        option.appendChild(button);
                    }
                });
                
                // Handle Classic WooCommerce checkout
                const classicShippingOptions = document.querySelectorAll('#shipping_method li, .woocommerce-shipping-methods li, ul.shipping_method li');
                classicShippingOptions.forEach((option, index) => {
                    if ((option.textContent.toLowerCase().includes('locker') || option.textContent.toLowerCase().includes('la locker')) && !option.querySelector('.select-locker-btn')) {
                        const button = document.createElement('button');
                        button.textContent = 'Selectare locker';
                        button.className = 'button select-locker-btn';
                        button.type = 'button'; // CRUCIAL: Prevent form submission
                        button.style.marginTop = '10px';
                        button.style.backgroundColor = '#ebe9eb';
                        button.style.color = '#515151';
                        button.style.padding = '5px 10px';
                        button.style.border = '1px solid #d3ced2';
                        button.style.borderRadius = '3px';
                        button.style.cursor = 'pointer';
                        button.style.display = 'none'; // Hidden by default
                        button.onclick = function(event) {
                            event.preventDefault();
                            event.stopPropagation();
                            handleLockerSelection();
                        };
                        option.appendChild(button);
                    }
                });
            };
        
        async function handleLockerSelection() {
            showLoadingState();
            
            try {
                // Extract location data from WooCommerce fields
                const county = getWooCommerceCounty();
                const city = getWooCommerceCity();
                
                if (!county || !city) {
                    alert('Te rugăm să completezi județul și orașul de livrare.');
                    hideLoadingState();
                    return;
                }
                
                // Get instance ID
                const instanceId = getSelectedShippingInstanceId();
                
                // Fetch locker carriers via AJAX
                const carrierIds = await fetchLockerCarriers(instanceId);
                
                if (!carrierIds || carrierIds.length === 0) {
                    alert('Nu există curieri configurați pentru livrare în locker.');
                    hideLoadingState();
                    return;
                }
                
                // Create iframe URL with location parameters and fetched carrier IDs
                const iframeUrl = `https://maps.europarcel.com/?country_code=RO&county_name=${encodeURIComponent(county)}&locality_name=${encodeURIComponent(city)}&carrier_id=${carrierIds.join(',')}`;
                
                showLockerModal(iframeUrl);
                hideLoadingState();
                
            } catch (error) {
                hideLoadingState();
                alert('Eroare la încărcarea lockerelor!');
                console.error('Locker selection error:', error);
            }
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
        
        function getWooCommerceCounty() {
            // Try different selectors for county/state field
            const stateSelectors = [
                '#shipping_state',          // Classic checkout shipping
                '#billing_state',           // Classic checkout billing
                '#calc_shipping_state',     // Cart calculator
                '.wc-block-components-address-form select[id*="state"]',  // Blocks checkout
                'select[name*="state"]'     // Fallback
            ];
            
            for (const selector of stateSelectors) {
                const element = document.querySelector(selector);
                if (element && element.value) {
                    return element.value;
                }
            }
            
            return null;
        }
        
        function getWooCommerceCity() {
            // Try different selectors for city field
            const citySelectors = [
                '#shipping_city',           // Classic checkout shipping
                '#billing_city',            // Classic checkout billing  
                '#calc_shipping_city',      // Cart calculator
                '.wc-block-components-address-form input[id*="city"]',   // Blocks checkout
                'input[name*="city"]'       // Fallback
            ];
            
            for (const selector of citySelectors) {
                const element = document.querySelector(selector);
                if (element && element.value) {
                    return element.value;
                }
            }
            
            return null;
        }
        
        function showLockerModal(iframeUrl) {
            // Check if we're on mobile
            const isMobile = window.innerWidth <= 768;
            
            // Create modal HTML with mobile-optimized styling
            const modalHtml = `
                <div id="europarcel-iframe-modal" style="
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background: rgba(0, 0, 0, 0.5);
                    z-index: 2147483647;
                    display: flex;
                    align-items: ${isMobile ? 'flex-start' : 'center'};
                    justify-content: center;
                    padding: ${isMobile ? '0' : '20px'};
                ">
                    <div style="
                        background: white;
                        width: 100%;
                        max-width: ${isMobile ? '100%' : '1200px'};
                        height: ${isMobile ? '100%' : '90%'};
                        border-radius: ${isMobile ? '0' : '8px'};
                        overflow: hidden;
                        position: relative;
                        ${isMobile ? 'margin: 0;' : ''}
                    ">
                        <button id="close-locker-modal" style="
                            position: absolute;
                            top: ${isMobile ? '15px' : '10px'};
                            right: ${isMobile ? '15px' : '10px'};
                            z-index: 10;
                            background: rgba(0, 0, 0, 0.7);
                            color: white;
                            border: none;
                            border-radius: 50%;
                            width: ${isMobile ? '35px' : '30px'};
                            height: ${isMobile ? '35px' : '30px'};
                            cursor: pointer;
                            font-size: ${isMobile ? '20px' : '18px'};
                            line-height: 1;
                            box-shadow: 0 2px 8px rgba(0,0,0,0.3);
                            transition: all 0.2s ease;
                        "
                        onmouseover="this.style.background='rgba(0,0,0,0.9)'; this.style.transform='scale(1.1)';"
                        onmouseout="this.style.background='rgba(0,0,0,0.7)'; this.style.transform='scale(1)';"
                        >&times;</button>
                        <iframe 
                            src="${iframeUrl}" 
                            style="width: 100%; height: 100%; border: none;"
                            title="Selectare locker de livrare"
                            id="europarcel-locker-iframe">
                        </iframe>
                    </div>
                </div>
            `;
            
            // Add modal to page
            document.body.insertAdjacentHTML('beforeend', modalHtml);
            
            // Prevent body scrolling and add mobile-specific adjustments
            const originalBodyOverflow = document.body.style.overflow;
            const originalBodyHeight = document.body.style.height;
            const originalHtmlOverflow = document.documentElement.style.overflow;
            
            document.body.style.overflow = 'hidden';
            document.documentElement.style.overflow = 'hidden';
            
            // On mobile, also prevent iOS Safari bouncing
            if (isMobile) {
                document.body.style.height = '100%';
                document.body.style.position = 'fixed';
                document.body.style.width = '100%';
            }
            
            // Store original styles for restoration
            const modal = document.getElementById('europarcel-iframe-modal');
            modal.dataset.originalBodyOverflow = originalBodyOverflow;
            modal.dataset.originalBodyHeight = originalBodyHeight;
            modal.dataset.originalHtmlOverflow = originalHtmlOverflow;
            
            // Add close handler
            document.getElementById('close-locker-modal').onclick = function(e) {
                e.preventDefault();
                e.stopPropagation();
                closeLockerModal();
            };
            
            // Close on backdrop click (but not on mobile to prevent accidental closes)
            if (!isMobile) {
                modal.onclick = function(e) {
                    if (e.target === this) {
                        closeLockerModal();
                    }
                };
            }
            
            // Prevent modal content clicks from closing modal
            modal.querySelector('div').onclick = function(e) {
                e.stopPropagation();
            };
            
            // Add ESC key handler for closing modal
            const escapeHandler = function(e) {
                if (e.key === 'Escape' || e.keyCode === 27) {
                    closeLockerModal();
                }
            };
            document.addEventListener('keydown', escapeHandler);
            
            // Store escape handler for cleanup
            modal.escapeHandler = escapeHandler;

        }
        
        function closeLockerModal() {
            const modal = document.getElementById('europarcel-iframe-modal');
            if (modal) {
                // Clean up escape key handler
                if (modal.escapeHandler) {
                    document.removeEventListener('keydown', modal.escapeHandler);
                }
                
                // Restore original styles
                const originalBodyOverflow = modal.dataset.originalBodyOverflow || '';
                const originalBodyHeight = modal.dataset.originalBodyHeight || '';
                const originalHtmlOverflow = modal.dataset.originalHtmlOverflow || '';
                
                document.body.style.overflow = originalBodyOverflow;
                document.body.style.height = originalBodyHeight;
                document.body.style.position = '';
                document.body.style.width = '';
                document.documentElement.style.overflow = originalHtmlOverflow;
                
                // Remove modal
                modal.remove();
            }
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
        
        async function fetchLockerCarriers(instanceId) {
            try {
                const formData = new FormData();
                formData.append('action', 'get_locker_carriers');
                formData.append('instance_id', instanceId);
                formData.append('nonce', europarcel_ajax.nonce);
                
                const response = await fetch(europarcel_ajax.ajax_url, {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    return data.data.carriers;
                } else {
                    console.error('Failed to fetch locker carriers:', data.data.message);
                    return [];
                }
            } catch (error) {
                console.error('Error fetching locker carriers:', error);
                return [];
            }
        }
        

        
        // Listen for locker selection from iframe
        window.addEventListener('message', function(event) {
            // Security check - only accept messages from your domain
            if (!event.origin.includes('maps.europarcel.com')) {
                return;
            }
            
            if (event.data && event.data.type === 'locker-selected') {
                const locker = event.data.locker;
                
                // Update WooCommerce hidden fields
                updateWooCommerceFields(locker);
                
                // Close modal after selection
                closeLockerModal();
                
                // Show success message
                showLockerSelectedInfo(locker);
                
                // Trigger checkout update to refresh shipping methods with locker info
                if (typeof jQuery !== 'undefined') {
                    jQuery('body').trigger('update_checkout');
                }
            }
        });
        
        function updateWooCommerceFields(locker) {
            // Update hidden fields that WooCommerce will save to order
            let lockerIdField = document.getElementById('eawb_locker_id');
            let lockerInstanceField = document.getElementById('eawb_locker_instance');
            let carrierIdField = document.getElementById('eawb_carrier_id');
            let lockerDataField = document.getElementById('eawb_locker_data');
            
            if (!lockerIdField) {
                lockerIdField = document.createElement('input');
                lockerIdField.type = 'hidden';
                lockerIdField.id = 'eawb_locker_id';
                lockerIdField.name = 'eawb_locker_id';
                document.body.appendChild(lockerIdField);
            }
            
            if (!lockerInstanceField) {
                lockerInstanceField = document.createElement('input');
                lockerInstanceField.type = 'hidden';
                lockerInstanceField.id = 'eawb_locker_instance';
                lockerInstanceField.name = 'eawb_locker_instance';
                document.body.appendChild(lockerInstanceField);
            }
            
            if (!carrierIdField) {
                carrierIdField = document.createElement('input');
                carrierIdField.type = 'hidden';
                carrierIdField.id = 'eawb_carrier_id';
                carrierIdField.name = 'eawb_carrier_id';
                document.body.appendChild(carrierIdField);
            }
            
            if (!lockerDataField) {
                lockerDataField = document.createElement('input');
                lockerDataField.type = 'hidden';
                lockerDataField.id = 'eawb_locker_data';
                lockerDataField.name = 'eawb_locker_data';
                document.body.appendChild(lockerDataField);
            }
            
            lockerIdField.value = locker.id;
            lockerInstanceField.value = getSelectedShippingInstanceId();
            carrierIdField.value = locker.carrier_id;
            lockerDataField.value = JSON.stringify({
                id: locker.id,
                carrier_id: locker.carrier_id,
                name: locker.name,
                address: locker.address,
                carrier_name: locker.carrier_name
            });
            
        }
        
        // Carrier logo mapping based on requirements
        function getCarrierLogo(carrierId) {
            const carrierLogos = {
                1: 'cargus-ship-go-200.webp',  // Cargus = Ship & Go
                2: 'dpd-200.webp',             // DPD = DPD
                3: 'fanbox-200.webp',          // FAN Courier = Fanbox
                4: 'gls-200.webp',             // GLS = GLS
                6: 'sameday-200.webp'          // Sameday = EasyBox (using sameday logo)
            };
            
            return carrierLogos[carrierId] || 'default-carrier-logo.png';
        }
        
        function showLockerSelectedInfo(locker) {
            // Create or update info display
            let infoDiv = document.getElementById('selected-locker-info');
            if (!infoDiv) {
                infoDiv = document.createElement('div');
                infoDiv.id = 'selected-locker-info';
                infoDiv.style.marginTop = '10px';
                infoDiv.style.padding = '15px';
                infoDiv.style.background = '#d1ecf1';
                infoDiv.style.border = '1px solid #bee5eb';
                infoDiv.style.borderRadius = '8px';
                infoDiv.style.fontSize = '14px';
                infoDiv.style.boxShadow = '0 2px 4px rgba(0,0,0,0.1)';
                
                // Add after the locker button
                const button = document.querySelector('.select-locker-btn');
                if (button && button.parentNode) {
                    button.parentNode.appendChild(infoDiv);
                }
            }
            
            const carrierLogo = getCarrierLogo(locker.carrier_id);
            const logoUrl = europarcel_ajax.plugin_url + '/assets/images/carriers-logo/' + carrierLogo;
            
            infoDiv.innerHTML = `
                <div style="display: flex; align-items: flex-start; gap: 12px;">
                    <img src="${logoUrl}" 
                         alt="${locker.carrier_name}" 
                         style="width: 60px; height: 40px; object-fit: contain; flex-shrink: 0;"
                         onerror="this.style.display='none'">
                    <div style="flex: 1;">
                        <div style="font-weight: bold; color: #155724; margin-bottom: 4px;">
                            ✓ Locker selectat - ${locker.carrier_name}
                        </div>
                        <div style="font-weight: 600; margin-bottom: 2px;">
                            ${locker.name}
                        </div>
                        <div style="color: #666; font-size: 13px; line-height: 1.4;">
                            ${locker.address}
                        </div>
                    </div>
                </div>
            `;
        }

        // Handle shipping method changes to show/hide buttons
        function handleShippingMethodChange() {
            // Find all possible selected shipping methods
            const selectedMethods = [
                document.querySelector('input[name^="shipping_method"]:checked'),
                document.querySelector('.wc-block-components-radio-control__input:checked'),
                document.querySelector('input[type="radio"]:checked[name*="shipping"]')
            ].filter(Boolean);
            
            const buttons = document.querySelectorAll('.select-locker-btn');
            
            let selectedMethod = selectedMethods[0];
            let isLockerMethod = false;
            
            if (selectedMethod) {
                const methodValue = selectedMethod.value || selectedMethod.id || '';
                
                // Check if this is a locker method
                isLockerMethod = methodValue.includes('_locker') || 
                               methodValue.includes('locker') ||
                               (selectedMethod.parentElement && selectedMethod.parentElement.textContent.toLowerCase().includes('locker'));
            }
            
            // Show/hide buttons based on whether locker method is selected
            buttons.forEach((button, index) => {
                if (isLockerMethod) {
                    // Only show the button if it belongs to the currently selected method
                    const parentOption = button.closest('.wc-block-components-radio-control__option-layout, li, label');
                    
                    if (parentOption) {
                        // Try multiple selectors for different WooCommerce checkout types
                        let radioInput = parentOption.querySelector(
                            'input[type="radio"], .wc-block-components-radio-control__input, input.wc-block-components-radio-control__input'
                        );
                        
                        // If not found in parent, try looking in nearby elements
                        if (!radioInput) {
                            const parentContainer = parentOption.closest('.wc-block-components-radio-control__option, .wc-block-components-radio-control');
                            if (parentContainer) {
                                radioInput = parentContainer.querySelector(
                                    'input[type="radio"], .wc-block-components-radio-control__input'
                                );
                            }
                        }
                        
                        const isCurrentlySelected = radioInput && radioInput.checked;
                        
                        if (isCurrentlySelected) {
                            button.style.display = 'block';
                        } else if (!radioInput) {
                            // If we can't find the radio input, match by text content
                            const buttonText = parentOption.textContent || '';
                            const selectedMethodText = selectedMethod?.parentElement?.textContent || selectedMethod?.closest('label')?.textContent || '';
                            const textMatches = buttonText === selectedMethodText;
                            
                            if (textMatches) {
                                button.style.display = 'block';
                            } else {
                                button.style.display = 'none';
                            }
                        } else {
                            button.style.display = 'none';
                        }
                    } else {
                        // Fallback - if we can't find parent, show it
                        button.style.display = 'block';
                    }
                } else {
                    button.style.display = 'none';
                }
            });
            
            // Remove any previous locker selection if not locker method
            if (!isLockerMethod) {
                const lockerDisplay = document.querySelector('.eawb-selected-locker, #selected-locker-info');
                if (lockerDisplay) {
                    lockerDisplay.remove();
                }
            }
        }
        
        // Event listeners for shipping method changes
        // Handle WooCommerce Blocks checkout
        const checkoutForm = document.querySelector('form.wc-block-checkout__form');
        if (checkoutForm) {
            checkoutForm.addEventListener('change', function (e) {
                if (e.target.closest('.wc-block-components-shipping-rates-control')) {
                    setTimeout(handleShippingMethodChange, 50);
                }
            });
        }
        
        // Handle Classic WooCommerce checkout - multiple selectors for robustness
        $(document).on('change', 'input[name^="shipping_method"], input[type="radio"][name*="shipping"]', function() {
            setTimeout(handleShippingMethodChange, 50);
        });
        
        // Additional event listener for any radio button changes
        $(document).on('change', 'input[type="radio"]', function() {
            if (this.name && (this.name.includes('shipping') || this.value.includes('shipping'))) {
                setTimeout(handleShippingMethodChange, 50);
            }
        });
        
        new MutationObserver(() => {
            addLockerButton();
            setTimeout(handleShippingMethodChange, 100);
        }).observe(document.body, {childList: true, subtree: true});
        
        addLockerButton();
        
        $(document.body).on('updated_checkout updated_shipping_method', () => {
            setTimeout(() => {
                addLockerButton();
                handleShippingMethodChange();
            }, 100);
        });
        
        // Check initial state
        setTimeout(handleShippingMethodChange, 500);
    }

    $(document).ready(initializeLockerSelector);

})(jQuery);