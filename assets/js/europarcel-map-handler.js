/**
 * Europarcel Map Handler
 * Manages Leaflet map, markers, and pin functionality
 * WordPress Plugin Standards Compliant
 */
(function() {
    'use strict';

    window.EuroparcelMapHandler = {
        map: null,
        markers: [],
        
        /**
         * Initialize Leaflet map with lockers
         */
        initializeMap: function(lockers) {
            if (!window.L) return;

            const mapContainer = document.getElementById('europarcel-map');
            if (!mapContainer) return;
            
            this.cleanup();
            
            setTimeout(() => {
                try {
                    this.createMap();
                    
                    if (lockers && lockers.length > 0) {
                        this.addMarkers(lockers);
                    }
                    
                    // Force resize after creation
                    setTimeout(() => {
                        if (this.map) {
                            this.map.invalidateSize();
                        }
                    }, 200);
                    
                } catch (error) {
                    // Silent fail - map functionality is optional
                }
            }, 300);
        },

        /**
         * Create the Leaflet map instance
         */
        createMap: function() {
            this.map = L.map('europarcel-map').setView([45.9443, 25.0094], 7);
            
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors'
            }).addTo(this.map);
        },

        /**
         * Add markers for all lockers
         */
        addMarkers: function(lockers) {
            if (!this.map) return;
            
            this.markers = [];
            const bounds = L.latLngBounds();
            let hasValidCoordinates = false;

            lockers.forEach((locker, index) => {
                const lat = parseFloat(locker.lat || locker.coordinates?.lat);
                const lng = parseFloat(locker.long || locker.coordinates?.long);
                
                if (lat && lng && !isNaN(lat) && !isNaN(lng)) {
                    const marker = this.createMarker(locker, lat, lng, index);
                    
                    if (marker) {
                        this.markers.push({
                            marker: marker,
                            lockerId: locker.id || locker.location_id || `locker_${index}`,
                            carrierId: String(locker.carrier_id || '')
                        });
                        
                        bounds.extend([lat, lng]);
                        hasValidCoordinates = true;
                    }
                }
            });

            // Fit map to show all markers
            if (hasValidCoordinates && this.markers.length > 0) {
                this.map.fitBounds(bounds, { padding: [20, 20] });
                
                setTimeout(() => {
                    if (this.map.getZoom() > 16) {
                        this.map.setZoom(16);
                    }
                }, 500);
            }
        },

        /**
         * Create individual marker with custom pin
         */
        createMarker: function(locker, lat, lng, index) {
            const lockerId = locker.id || locker.location_id || `locker_${index}`;
            const lockerName = locker.name || locker.location_name || `Locker ${index + 1}`;
            
            // Get carrier-specific icon
            const markerIcon = this.createCarrierIcon(locker.carrier_id);
            
            // Create marker
            const marker = L.marker([lat, lng], { icon: markerIcon }).addTo(this.map);
            
            // Create popup content
            const popupContent = `
                <strong>${lockerName}</strong>
                ${locker.carrier_name ? `<br><span style="color: #f97316; font-weight: 500; font-size: 12px;">${locker.carrier_name}</span>` : ''}
                ${locker.address ? `<br><small style="color: #666;">${locker.address}</small>` : ''}
            `;
            marker.bindPopup(popupContent);
            
            // Add click handler
            marker.on('click', () => {
                if (window.EuroparcelModal) {
                    window.EuroparcelModal.selectLocker(lockerId);
                }
            });
            
            return marker;
        },

        /**
         * Create carrier-specific icon
         */
        createCarrierIcon: function(carrierId) {
            const carrierIdStr = String(carrierId || '');
            
            if (window.EuroparcelLockerData?.carrierPins?.[carrierIdStr]) {
                try {
                    return L.icon({
                        iconUrl: window.EuroparcelLockerData.carrierPins[carrierIdStr],
                        iconSize: [35, 50],
                        iconAnchor: [18, 50],
                        popupAnchor: [0, -50],
                    });
                } catch (e) {
                    // Fallback to default icon
                }
            }
            
            return new L.Icon.Default();
        },

        /**
         * Highlight specific marker when locker is selected
         */
        highlightMarker: function(lockerId) {
            if (!this.markers) return;

            this.markers.forEach(({marker, lockerId: markerLockerId}) => {
                if (markerLockerId === lockerId) {
                    marker.openPopup();
                    this.map.setView(marker.getLatLng(), Math.max(this.map.getZoom(), 15));
                }
            });
        },

        /**
         * Update marker visibility based on carrier filter
         */
        updateMarkersVisibility: function(selectedCarriers) {
            if (!this.markers) return;

            this.markers.forEach(({marker, carrierId}) => {
                const isVisible = selectedCarriers.length === 0 || selectedCarriers.includes(carrierId);
                
                if (isVisible) {
                    marker.addTo(this.map);
                } else {
                    marker.remove();
                }
            });
        },

        /**
         * Clean up map and markers
         */
        cleanup: function() {
            if (this.map) {
                this.map.remove();
                this.map = null;
            }
            this.markers = [];
        }
    };

})();
