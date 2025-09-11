/**
 * EuroParcel Locker Selector
 *
 * Handles locker selection functionality for both WooCommerce Classic
 * and Blocks checkout. Manages checkout detection, button creation,
 * and locker selection workflow.
 *
 * @package    Europarcel
 * @since      1.0.0
 */

(function ($) {
	'use strict';

	const checkoutType = europarcel_ajax.checkout_type || 'blocks';

	/**
	 * Helper function to get WooCommerce county/state
	 */
	window.getWooCommerceCounty = function() {
		const stateSelectors = [
			'#shipping_state',
			'#billing_state',
			'#calc_shipping_state',
			'.wc-block-components-address-form select[id*="state"]',
			'select[name*="state"]'
		];

		for (const selector of stateSelectors) {
			const element = document.querySelector(selector);
			if (element && element.value) {
				return element.value;
			}
		}
		return null;
	};

	/**
	 * Helper function to get WooCommerce city
	 */
	window.getWooCommerceCity = function() {
		const citySelectors = [
			'#shipping_city',
			'#billing_city',
			'#calc_shipping_city',
			'.wc-block-components-address-form input[id*="city"]',
			'input[name*="city"]'
		];

		for (const selector of citySelectors) {
			const element = document.querySelector(selector);
			if (element && element.value) {
				return element.value;
			}
		}
		return null;
	};

	/**
	 * Helper function to get selected shipping instance ID
	 */
	window.getSelectedShippingInstanceId = function() {
		let selectedMethod = document.querySelector('input[name^="shipping_method"]:checked') ||
			document.querySelector('.wc-block-components-radio-control__input:checked');

		if (selectedMethod) {
			const methodValue = selectedMethod.value || selectedMethod.id;
			const parts = methodValue.split(':');
			if (parts.length > 1)
				return parts[1];

			const underscoreParts = methodValue.split('_');
			if (underscoreParts.length > 1)
				return underscoreParts[underscoreParts.length - 1];
		}

		return '1';
	};

	/**
	 * Handle locker selection for classic checkout button
	 * Called from PHP onclick handler
	 */
	window.openLockerSelector = function() {
		const county = window.getWooCommerceCounty();
		const city = window.getWooCommerceCity();

		const instanceId = window.getSelectedShippingInstanceId();
		const carrierIds = europarcel_ajax.instances_lockers && europarcel_ajax.instances_lockers[instanceId]
			? europarcel_ajax.instances_lockers[instanceId]
			: [];

		if (!carrierIds || carrierIds.length === 0) {
			alert(europarcel_ajax.i18n.no_carriers_configured);
			return;
		}

		let iframeUrl = `https://maps.europarcel.com/?country_code=RO&carrier_id=${carrierIds.join(',')}`;
		if (county) {
			iframeUrl += `&county_name=${encodeURIComponent(county)}`;
		}
		if (city) {
			iframeUrl += `&locality_name=${encodeURIComponent(city)}`;
		}

		if (window.EuroparcelModal) {
			window.EuroparcelModal.show(iframeUrl);
		}
	};

	/**
	 * Initialize locker selector functionality
	 */
	function initializeLockerSelector() {
		let instances_lockers = europarcel_ajax.instances_lockers;
		let user_lockers = europarcel_ajax.user_lockers;
		let order_lockers = europarcel_ajax.order_lockers;

		function updateButtonText(button, hasSelectedLocker) {
			const newText = hasSelectedLocker ? europarcel_ajax.i18n.modify_locker : europarcel_ajax.i18n.select_locker;
			button.textContent = newText;
		}

        const addLockerButtonToReviewSection = () => {
            if (checkoutType === 'blocks') {
                const reviewSelectors = [
                    '.wc-block-components-order-summary',
                    '.wc-block-checkout__order-summary',  
                    '.wc-block-components-totals',
                    '.wp-block-woocommerce-checkout-order-summary-block',
                    '.wc-block-components-order-summary-item',
                    '.wc-block-checkout-order-summary',
                    '.wc-block-components-sidebar'
                ];
                
                let reviewContainer = null;
                for (const selector of reviewSelectors) {
                    const found = document.querySelector(selector);
                    if (found && !reviewContainer) {
                        reviewContainer = found;
                        break;
                    }
                }

                if (reviewContainer && !document.getElementById('europarcel-blocks-locker-btn')) {
                    const lockerRow = document.createElement('div');
                    lockerRow.id = 'europarcel-blocks-locker-container';
                    lockerRow.style.marginTop = '20px';
                    lockerRow.style.padding = '15px';
                    lockerRow.style.border = '1px solid rgba(0,0,0,0.1)';
                    lockerRow.style.borderRadius = '4px';
                    lockerRow.style.backgroundColor = 'rgba(0,0,0,0.02)';
                    lockerRow.style.display = 'none';
                    
                    const button = document.createElement('button');
                    button.id = 'europarcel-blocks-locker-btn';
                    button.className = 'button alt wp-element-button';
                    button.type = 'button';
                    button.style.width = '100%';
                    button.style.marginBottom = '10px';
                    button.textContent = europarcel_ajax.i18n.select_locker;
                    button.onclick = function (event) {
                        event.preventDefault();
                        event.stopPropagation();
                        handleLockerSelection();
                    };
                    
                    const detailsDiv = document.createElement('div');
                    detailsDiv.id = 'europarcel-blocks-location-details';
                    detailsDiv.style.display = 'none';
                    
                    lockerRow.appendChild(button);
                    lockerRow.appendChild(detailsDiv);
                    reviewContainer.appendChild(lockerRow);
                }
            }
        };

        async function handleLockerSelection() {
            showLoadingState();

            try {
                const county = window.getWooCommerceCounty();
                const city = window.getWooCommerceCity();

                const instanceId = window.getSelectedShippingInstanceId();
                const carrierIds = europarcel_ajax.instances_lockers && europarcel_ajax.instances_lockers[instanceId]
                        ? europarcel_ajax.instances_lockers[instanceId]
                        : [];

                if (!carrierIds || carrierIds.length === 0) {
                    alert(europarcel_ajax.i18n.no_carriers_configured);
                    hideLoadingState();
                    return;
                }

                let iframeUrl = `https://maps.europarcel.com/?country_code=RO&carrier_id=${carrierIds.join(',')}`;
                if (county) {
                    iframeUrl += `&county_name=${encodeURIComponent(county)}`;
                }
                if (city) {
                    iframeUrl += `&locality_name=${encodeURIComponent(city)}`;
                }
                
                // Use the new modal object
                if (window.EuroparcelModal) {
                    window.EuroparcelModal.show(iframeUrl);
                }
                hideLoadingState();

            } catch (error) {
                hideLoadingState();
                // Silently handle error
            }
        }


        function showLoadingState() {
            if (checkoutType === 'blocks') {
                const button = document.getElementById('europarcel-blocks-locker-btn');
                if (button) {
                    button.disabled = true;
                    button.textContent = europarcel_ajax.i18n.loading;
                    button.style.opacity = '0.7';
                }
            } else {
                document.querySelectorAll('.select-locker-btn').forEach(btn => {
                    btn.disabled = true;
                    btn.textContent = europarcel_ajax.i18n.loading;
                    btn.style.opacity = '0.7';
                });
            }
        }

        function hideLoadingState() {
            if (checkoutType === 'blocks') {
                const button = document.getElementById('europarcel-blocks-locker-btn');
                if (button) {
                    button.disabled = false;
                    button.style.opacity = '1';
                    const hasSelectedLocker = user_lockers && Object.keys(user_lockers).length > 0;
                    updateButtonText(button, hasSelectedLocker);
                }
            } else {
                document.querySelectorAll('.select-locker-btn').forEach(btn => {
                    btn.disabled = false;
                    btn.style.opacity = '1';
                    const instanceId = window.getSelectedShippingInstanceId();
                    const hasSelectedLocker = user_lockers && Object.keys(user_lockers).length > 0;
                    updateButtonText(btn, hasSelectedLocker);
                });
            }
        }

        window.addEventListener('message', function (event) {
            if (!event.origin.includes('maps.europarcel.com')) {
                return;
            }

            if (event.data && event.data.type === 'locker-selected') {
                const locker = event.data.locker;

                updateWooCommerceFields(locker);

                if (window.EuroparcelModal) {
                    window.EuroparcelModal.close();
                }

                if (checkoutType === 'blocks') {
                    const blocksButton = document.getElementById('europarcel-blocks-locker-btn');
                    const blocksDetails = document.getElementById('europarcel-blocks-location-details');
                    
                    if (blocksButton) {
                        blocksButton.textContent = europarcel_ajax.i18n.modify_locker;
                    }

                    if (blocksDetails) {
                        blocksDetails.innerHTML = `
                            <div style="margin-top: 10px; padding: 15px; border: 1px solid rgba(0,0,0,0.1); border-radius: 4px; background-color: rgba(0,0,0,0.02);">
                                <div style="font-weight: bold; margin-bottom: 4px;">
                                    ${europarcel_ajax.i18n.locker_selected} ${locker.carrier_name}
                                </div>
                                <div style="font-weight: 600; margin-bottom: 2px;">
                                    ${locker.name}
                                </div>
                                <div style="margin-bottom: 0;">
                                    ${locker.address}
                                </div>
                            </div>
                        `;
                        blocksDetails.style.display = 'block';
                    }
                } else {
                    const classicButton = document.querySelector('button[onclick="openLockerSelector()"]');
                    if (classicButton) {
                        classicButton.textContent = europarcel_ajax.i18n.modify_locker;
                    }

                    const details = document.getElementById('europarcel-location-details');
                    if (details) {
                        details.innerHTML = `
                            <div style="margin-top: 10px; padding: 15px; border: 1px solid rgba(0,0,0,0.1); border-radius: 4px; background-color: rgba(0,0,0,0.02);">
                                <div style="font-weight: bold; margin-bottom: 4px;">
                                    ${europarcel_ajax.i18n.locker_selected} ${locker.carrier_name}
                                </div>
                                <div style="font-weight: 600; margin-bottom: 2px;">
                                    ${locker.name}
                                </div>
                                <div style="margin-bottom: 0;">
                                    ${locker.address}
                                </div>
                            </div>
                        `;
                        details.style.display = 'block';
                    }
                }

                $("body").trigger("update_checkout");
            }
        });

        function updateWooCommerceFields(locker) {
            $.ajax({
                url: europarcel_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'update_locker_shipping',
                    security: europarcel_ajax.nonce,
                    instance_id: window.getSelectedShippingInstanceId(),
                    locker_id: locker.id,
                    carrier_id: locker.carrier_id,
                    carrier_name: locker.carrier_name,
                    locker_name: locker.name,
                    locker_address: locker.address
                },
                dataType: 'json',
                success: function (response) {
                    user_lockers = response['data']['user_locker'];
                    order_lockers = response['data']['order_lockers'];
                }
            });
        }

        function handleShippingMethodChange() {
            const selectedMethods = [
                document.querySelector('input[name^="shipping_method"]:checked'),
                document.querySelector('.wc-block-components-radio-control__input:checked'),
                document.querySelector('input[type="radio"]:checked[name*="shipping"]')
            ].filter(Boolean);

            let selectedMethod = selectedMethods[0];
            let isLockerMethod = false;

            if (selectedMethod) {
                const methodValue = selectedMethod.value || selectedMethod.id || '';
                const selectedMethodText = selectedMethod.parentElement ? selectedMethod.parentElement.textContent : '';
                
                isLockerMethod = methodValue.includes('_locker') ||
                        methodValue.includes('locker') ||
                        selectedMethodText.toLowerCase().includes('locker');
            }

            if (checkoutType === 'blocks') {
                const blocksContainer = document.getElementById('europarcel-blocks-locker-container');
                
                if (blocksContainer) {
                    if (isLockerMethod) {
                        blocksContainer.style.display = 'block';
                    } else {
                        blocksContainer.style.display = 'none';
                        const details = document.getElementById('europarcel-blocks-location-details');
                        if (details) {
                            details.style.display = 'none';
                        }
                    }
                }
            }
        }

        // Handle shipping method changes
        $(document).on("change", "input[type=radio]", function() {
            if ((this.name && this.name.includes('shipping')) || 
                (this.value && (this.value.includes('shipping') || this.value.includes('europarcel'))) ||
                this.name === 'radio-control-0') {
                handleShippingMethodChange();
            }
        });

        $(document).on("change", "input[name^=shipping_method]", function() {
            handleShippingMethodChange();
        });

        $(document).on('updated_checkout updated_shipping_method', function() {
            addLockerButtonToReviewSection();
            handleShippingMethodChange();
        });

        if (checkoutType === 'blocks') {
            // For blocks, need to wait for dynamic content to load
            const initBlocks = () => {
                addLockerButtonToReviewSection();
                handleShippingMethodChange();
                
                // If button wasn't added, content isn't ready yet
                const button = document.getElementById('europarcel-blocks-locker-btn');
                if (!button) {
                    setTimeout(initBlocks, 500);
                }
            };
            initBlocks();
        } else {
            handleShippingMethodChange();
        }
    }

	$(document).ready(initializeLockerSelector);

})(jQuery);