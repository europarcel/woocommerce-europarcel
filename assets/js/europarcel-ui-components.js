/**
 * Europarcel UI Components Handler
 * Manages carrier filtering, locker list, and user interactions
 * WordPress Plugin Standards Compliant
 */
(function() {
    'use strict';

    window.EuroparcelUIComponents = {
        selectedCarriers: [],
        
        /**
         * Update modal content with new data
         */
        updateContent: function(data) {
            this.selectedCarriers = [];
            
            this.renderCarrierFilter(data.lockers || []);
            this.renderLockersList(data.lockers || []);
            this.bindEvents();
        },

        /**
         * Render carrier filter buttons
         */
        renderCarrierFilter: function(lockers) {
            const container = document.querySelector('.europarcel-carrier-buttons');
            if (!container) return;

            const carrierCounts = this.getCarrierCounts(lockers);
            const carrierNames = { '1': 'Cargus', '2': 'DPD', '3': 'FanCourier', '4': 'GLS', '6': 'SameDay' };

            let html = '';
            Object.entries(carrierCounts).forEach(([carrierId, count]) => {
                const carrierName = carrierNames[carrierId] || 'Unknown';
                const logoUrl = window.EuroparcelLockerData?.carrierPins?.[carrierId] || '';
                
                html += `
                    <button class="europarcel-carrier-btn active" data-carrier-id="${carrierId}">
                        ${logoUrl ? `<img src="${logoUrl}" alt="${carrierName}" class="europarcel-carrier-logo">` : ''}
                        <span>${carrierName} (${count})</span>
                    </button>
                `;
                
                this.selectedCarriers.push(carrierId);
            });

            container.innerHTML = html;
        },

        /**
         * Render lockers list
         */
        renderLockersList: function(lockers) {
            const container = document.querySelector('.europarcel-lockers-list');
            if (!container) return;

            if (!lockers || lockers.length === 0) {
                container.innerHTML = '<div class="europarcel-no-lockers"><p>Nu sunt lockere disponibile.</p></div>';
                return;
            }

            let html = '';
            lockers.forEach((locker, index) => {
                const lockerId = locker.id || locker.location_id || `locker_${index}`;
                const lockerName = locker.name || locker.location_name || `Locker ${index + 1}`;
                const lockerAddress = locker.address || '';
                const carrierId = locker.carrier_id;
                const logoUrl = window.EuroparcelLockerData?.carrierPins?.[carrierId] || '';
                
                html += `
                    <div class="europarcel-locker-item" data-locker-id="${lockerId}" data-carrier-id="${carrierId}">
                        ${logoUrl ? `<img src="${logoUrl}" alt="Carrier" class="europarcel-locker-logo">` : ''}
                        <div class="europarcel-locker-details">
                            <h4 class="europarcel-locker-name">${lockerName}</h4>
                            <p class="europarcel-locker-address">${lockerAddress}</p>
                        </div>
                    </div>
                `;
            });

            container.innerHTML = html;
        },

        /**
         * Bind UI event handlers
         */
        bindEvents: function() {
            this.bindCarrierFilter();
            this.bindLockerSelection();
            this.bindConfirmButton();
        },

        /**
         * Bind carrier filter functionality
         */
        bindCarrierFilter: function() {
            const buttons = document.querySelectorAll('.europarcel-carrier-btn');
            buttons.forEach(button => {
                button.addEventListener('click', () => {
                    const carrierId = button.dataset.carrierId;
                    this.toggleCarrier(carrierId, button);
                });
            });
        },

        /**
         * Bind locker selection functionality
         */
        bindLockerSelection: function() {
            const items = document.querySelectorAll('.europarcel-locker-item');
            items.forEach(item => {
                item.addEventListener('click', () => {
                    const lockerId = item.dataset.lockerId;
                    if (window.EuroparcelModal) {
                        window.EuroparcelModal.selectLocker(lockerId);
                    }
                });
            });
        },

        /**
         * Bind confirm button functionality
         */
        bindConfirmButton: function() {
            const confirmBtn = document.querySelector('.europarcel-modal-btn-primary');
            if (confirmBtn) {
                confirmBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    if (window.EuroparcelModal) {
                        window.EuroparcelModal.saveSelection();
                    }
                });
            }
        },

        /**
         * Toggle carrier filter
         */
        toggleCarrier: function(carrierId, buttonElement) {
            const index = this.selectedCarriers.indexOf(carrierId);
            
            if (index > -1) {
                this.selectedCarriers.splice(index, 1);
                buttonElement.classList.remove('active');
            } else {
                this.selectedCarriers.push(carrierId);
                buttonElement.classList.add('active');
            }
            
            this.filterLockersList();
            
            if (window.EuroparcelMapHandler) {
                window.EuroparcelMapHandler.updateMarkersVisibility(this.selectedCarriers);
            }
        },

        /**
         * Filter lockers list based on selected carriers
         */
        filterLockersList: function() {
            const items = document.querySelectorAll('.europarcel-locker-item');
            items.forEach(item => {
                const carrierId = item.dataset.carrierId;
                const isVisible = this.selectedCarriers.length === 0 || this.selectedCarriers.includes(carrierId);
                item.style.display = isVisible ? 'flex' : 'none';
            });
        },

        /**
         * Handle locker selection visual updates
         */
        handleSelection: function(lockerId) {
            // Update visual selection in list
            const items = document.querySelectorAll('.europarcel-locker-item');
            items.forEach(item => {
                item.classList.remove('selected');
                if (item.dataset.lockerId === lockerId) {
                    item.classList.add('selected');
                }
            });
            
            // Enable confirm button
            const confirmBtn = document.querySelector('.europarcel-modal-btn-primary');
            if (confirmBtn) {
                confirmBtn.disabled = false;
                confirmBtn.textContent = 'Confirmă selecția';
            }
        },

        /**
         * Get carrier counts from lockers
         */
        getCarrierCounts: function(lockers) {
            const counts = {};
            lockers.forEach(locker => {
                const carrierId = String(locker.carrier_id || '');
                counts[carrierId] = (counts[carrierId] || 0) + 1;
            });
            return counts;
        },

        /**
         * Reset component state
         */
        reset: function() {
            this.selectedCarriers = [];
        }
    };

})();
