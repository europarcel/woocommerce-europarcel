/**
 * Europarcel Modal Utilities
 * Helper functions and data management utilities
 * WordPress Plugin Standards Compliant
 */
(function() {
    'use strict';

    window.EuroparcelUtils = {
        
        /**
         * Save selected locker to WooCommerce checkout form
         */
        saveToCheckoutForm: function(lockerId, data) {
            // Find or create hidden input for selected locker
            let hiddenInput = document.getElementById('eawb_selected_locker');
            if (!hiddenInput) {
                hiddenInput = this.createHiddenInput();
            }
            
            if (hiddenInput) {
                hiddenInput.value = lockerId;
                
                // Show confirmation with locker details
                const locker = this.findLockerById(lockerId, data?.lockers || []);
                const lockerName = locker?.name || locker?.location_name || `Locker ${lockerId}`;
                
                alert(`Locker selectat: ${lockerName}`);
            }
        },

        /**
         * Create hidden input for locker selection
         */
        createHiddenInput: function() {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.id = 'eawb_selected_locker';
            input.name = 'eawb_locker_id';
            
            // Find checkout form using multiple selectors
            const checkoutForm = this.findCheckoutForm();
            
            if (checkoutForm) {
                checkoutForm.appendChild(input);
                return input;
            }
            
            // Checkout form not found - silent fail
            return null;
        },

        /**
         * Find WooCommerce checkout form
         */
        findCheckoutForm: function() {
            const selectors = [
                'form[name="checkout"]',
                'form.checkout',
                '.wc-block-checkout__form',
                'form.woocommerce-checkout'
            ];
            
            for (const selector of selectors) {
                const form = document.querySelector(selector);
                if (form) {
                    return form;
                }
            }
            
            return null;
        },

        /**
         * Find locker by ID in data array
         */
        findLockerById: function(lockerId, lockers) {
            return lockers.find(locker => 
                (locker.id || locker.location_id) == lockerId
            );
        },

        /**
         * Format locker address for display
         */
        formatLockerAddress: function(locker) {
            return locker.address || locker.street_name || '';
        },

        /**
         * Get carrier name by ID
         */
        getCarrierName: function(carrierId) {
            const names = {
                '1': 'Cargus',
                '2': 'DPD', 
                '3': 'FanCourier',
                '4': 'GLS',
                '6': 'SameDay'
            };
            return names[String(carrierId)] || 'Unknown';
        },

        /**
         * Check if coordinates are valid
         */
        hasValidCoordinates: function(locker) {
            const lat = parseFloat(locker.lat || locker.coordinates?.lat);
            const lng = parseFloat(locker.long || locker.coordinates?.long);
            return lat && lng && !isNaN(lat) && !isNaN(lng);
        },

        /**
         * Sanitize HTML content for safe display
         */
        sanitizeHtml: function(str) {
            const div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
        },

        /**
         * Debounce function for performance
         */
        debounce: function(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }
    };

})();
