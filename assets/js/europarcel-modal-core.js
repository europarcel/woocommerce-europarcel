/**
 * Europarcel Modal Core Controller
 * Handles modal creation, show/hide, and coordination between components
 * WordPress Plugin Standards Compliant
 */
(function() {
    'use strict';

    window.EuroparcelModal = {
        modalId: 'europarcel-locker-modal-wrapper',
        isVisible: false,
        selectedLockerId: null,
        currentData: null,
        
        /**
         * Initialize modal and create DOM structure
         */
        init: function() {
            if (document.getElementById(this.modalId)) {
                return; // Already exists
            }
            
            this.createModalStructure();
            this.bindCoreEvents();
        },

        /**
         * Create the main modal HTML structure
         */
        createModalStructure: function() {
            const modalHTML = `
                <div id="${this.modalId}" class="europarcel-modal-overlay" style="display: none;">
                    <div class="europarcel-modal-container">
                        <div class="europarcel-modal-header">
                            <h3>Selectează punct de livrare</h3>
                            <button type="button" class="europarcel-modal-close" aria-label="Close">&times;</button>
                        </div>
                        
                        <div class="europarcel-locality-search">
                            <label class="europarcel-search-label">Selectează localitatea pentru a vedea punctele de livrare disponibile</label>
                            <input type="text" class="europarcel-search-input" placeholder="Caută localitatea..." readonly value="Medias, Sibiu" />
                        </div>
                        
                        <div class="europarcel-modal-body">
                            <div class="europarcel-map-container">
                                <div id="europarcel-map"></div>
                            </div>
                            
                            <div class="europarcel-sidebar">
                                <div class="europarcel-carriers-filter">
                                    <p class="europarcel-filter-label">Curieri disponibili</p>
                                    <div class="europarcel-carrier-buttons"></div>
                                </div>
                                
                                <div class="europarcel-lockers-list">
                                    <div class="europarcel-no-lockers">
                                        <p>Selectează o localitate pentru a vedea punctele disponibile</p>
                                    </div>
                                </div>
                                
                                <div class="europarcel-modal-footer">
                                    <button type="button" class="europarcel-modal-btn europarcel-modal-btn-primary" disabled>Confirmă selecția</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            const container = document.createElement('div');
            container.innerHTML = modalHTML;
            document.body.appendChild(container.firstElementChild);
        },

        /**
         * Bind core modal events (close functionality)
         */
        bindCoreEvents: function() {
            const modal = document.getElementById(this.modalId);
            if (!modal) return;

            const closeBtn = modal.querySelector('.europarcel-modal-close');
            
            // Close button
            if (closeBtn) {
                closeBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    this.hide();
                });
            }

            // Close on overlay click
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    this.hide();
                }
            });

            // ESC key handling
            this.escKeyHandler = (e) => {
                if (e.key === 'Escape' && this.isVisible) {
                    e.preventDefault();
                    this.hide();
                }
            };
            
            document.addEventListener('keydown', this.escKeyHandler);
        },

        /**
         * Show modal with locker data
         */
        showWithData: function(data) {
            // Check for duplicate modals
            const existingModals = document.querySelectorAll('#' + this.modalId);
            if (existingModals.length > 1) {
                for (let i = 1; i < existingModals.length; i++) {
                    existingModals[i].remove();
                }
            }
            
            this.init();
            
            this.currentData = data;
            this.selectedLockerId = null;
            this.isVisible = true;
            
            // Update content using other components
            if (window.EuroparcelUIComponents) {
                window.EuroparcelUIComponents.updateContent(data);
            }
            
            if (window.EuroparcelMapHandler) {
                window.EuroparcelMapHandler.initializeMap(data.lockers || []);
            }
            
            // Show modal
            const modal = document.getElementById(this.modalId);
            if (modal) {
                modal.style.display = 'flex';
                modal.style.visibility = 'visible';
                document.body.style.overflow = 'hidden';
            }
        },

        /**
         * Hide modal and cleanup
         */
        hide: function() {
            this.isVisible = false;
            
            const modal = document.getElementById(this.modalId);
            if (modal) {
                modal.style.display = 'none';
                modal.style.visibility = 'hidden';
                document.body.style.overflow = '';
            }

            // Cleanup components
            if (window.EuroparcelMapHandler) {
                window.EuroparcelMapHandler.cleanup();
            }
            
            if (window.EuroparcelUIComponents) {
                window.EuroparcelUIComponents.reset();
            }
            
            // Cleanup core state
            this.selectedLockerId = null;
            this.currentData = null;
        },

        /**
         * Handle locker selection
         */
        selectLocker: function(lockerId) {
            this.selectedLockerId = lockerId;
            
            // Notify components
            if (window.EuroparcelUIComponents) {
                window.EuroparcelUIComponents.handleSelection(lockerId);
            }
            
            if (window.EuroparcelMapHandler) {
                window.EuroparcelMapHandler.highlightMarker(lockerId);
            }
        },

        /**
         * Save selected locker to checkout form
         */
        saveSelection: function() {
            if (!this.selectedLockerId) {
                alert('Vă rugăm să selectați un locker mai întâi.');
                return;
            }

            if (window.EuroparcelUtils) {
                window.EuroparcelUtils.saveToCheckoutForm(this.selectedLockerId, this.currentData);
            }
            
            this.hide();
        },

        /**
         * Cleanup when modal is destroyed
         */
        destroy: function() {
            if (this.escKeyHandler) {
                document.removeEventListener('keydown', this.escKeyHandler);
                this.escKeyHandler = null;
            }
            
            const modal = document.getElementById(this.modalId);
            if (modal) {
                modal.remove();
            }
            
            this.hide();
        }
    };

    // Backward compatibility
    window.EuroparcelLockerModal = window.EuroparcelModal;

})();
