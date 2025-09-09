/**
 * EuroParcel Modal functionality
 * 
 * Handles the display and interaction of the locker selection modal iframe.
 * Follows WordPress JavaScript coding standards.
 * 
 * @package    Europarcel
 * @subpackage Assets/JavaScript
 * @since      1.0.0
 */

(function($) {
	'use strict';

	/**
	 * EuroParcel Modal object
	 * 
	 * @since 1.0.0
	 */
	window.EuroparcelModal = {

		/**
		 * Show the locker selection modal
		 * 
		 * Creates and displays a modal dialog containing an iframe with the locker map.
		 * Handles both desktop and mobile responsive display.
		 * 
		 * @since 1.0.0
		 * @param {string} iframeUrl - The URL to load in the iframe
		 */
		show: function(iframeUrl) {
			var isMobile = window.innerWidth <= 768;

			var modalHtml = `
				<div id="europarcel-iframe-modal" style="
					position: fixed; top: 0; left: 0; width: 100%; height: 100%;
					background: rgba(0, 0, 0, 0.5); z-index: 2147483647; display: flex;
					align-items: ${isMobile ? 'flex-start' : 'center'}; justify-content: center;
					padding: ${isMobile ? '0' : '20px'};
				">
					<div style="
						background: white; width: 100%; max-width: ${isMobile ? '100%' : '1200px'};
						height: ${isMobile ? '100%' : '90%'}; border-radius: ${isMobile ? '0' : '8px'};
						overflow: hidden; position: relative; ${isMobile ? 'margin: 0;' : ''}
					">
						<button id="close-locker-modal" style="
							position: absolute; top: ${isMobile ? '15px' : '10px'}; right: ${isMobile ? '15px' : '10px'};
							z-index: 10; background: rgba(0, 0, 0, 0.7); color: white; border: none;
							border-radius: 50%; width: ${isMobile ? '35px' : '30px'}; height: ${isMobile ? '35px' : '30px'};
							cursor: pointer; font-size: ${isMobile ? '20px' : '18px'}; line-height: 1;
							box-shadow: 0 2px 8px rgba(0,0,0,0.3); transition: all 0.2s ease;
						"
						onmouseover="this.style.background='rgba(0,0,0,0.9)'; this.style.transform='scale(1.1)';"
						onmouseout="this.style.background='rgba(0,0,0,0.7)'; this.style.transform='scale(1)';"
						>&times;</button>
						<iframe src="${iframeUrl}" style="width: 100%; height: 100%; border: none;"
							title="Selectare locker de livrare" id="europarcel-locker-iframe"></iframe>
					</div>
				</div>
			`;

			document.body.insertAdjacentHTML('beforeend', modalHtml);

			// Store original styles to restore later
			var originalBodyOverflow = document.body.style.overflow;
			var originalBodyHeight = document.body.style.height;
			var originalHtmlOverflow = document.documentElement.style.overflow;

			// Prevent scrolling
			document.body.style.overflow = 'hidden';
			document.documentElement.style.overflow = 'hidden';

			if (isMobile) {
				document.body.style.height = '100%';
				document.body.style.position = 'fixed';
				document.body.style.width = '100%';
			}

			// Store original styles in modal dataset
			var modal = document.getElementById('europarcel-iframe-modal');
			modal.dataset.originalBodyOverflow = originalBodyOverflow;
			modal.dataset.originalBodyHeight = originalBodyHeight;
			modal.dataset.originalHtmlOverflow = originalHtmlOverflow;

			// Setup event handlers
			this.setupEventHandlers(modal, isMobile);
		},

		/**
		 * Setup modal event handlers
		 * 
		 * Configures click handlers, escape key handler, and other modal interactions.
		 * 
		 * @since 1.0.0
		 * @param {HTMLElement} modal - The modal element
		 * @param {boolean} isMobile - Whether the device is mobile
		 */
		setupEventHandlers: function(modal, isMobile) {
			// Close button handler
			var closeButton = document.getElementById('close-locker-modal');
			if (closeButton) {
				closeButton.onclick = function(e) {
					e.preventDefault();
					e.stopPropagation();
					EuroparcelModal.close();
				};
			}

			// Click outside to close (desktop only)
			if (!isMobile) {
				modal.onclick = function(e) {
					if (e.target === this) {
						EuroparcelModal.close();
					}
				};
			}

			// Prevent clicks inside modal from closing it
			var modalContent = modal.querySelector('div');
			if (modalContent) {
				modalContent.onclick = function(e) {
					e.stopPropagation();
				};
			}

			// Escape key handler
			var escapeHandler = function(e) {
				if (e.key === 'Escape' || e.keyCode === 27) {
					EuroparcelModal.close();
				}
			};

			document.addEventListener('keydown', escapeHandler);
			modal.escapeHandler = escapeHandler;
		},

		/**
		 * Close the modal
		 * 
		 * Removes the modal from DOM and restores original page styles.
		 * Cleans up event listeners to prevent memory leaks.
		 * 
		 * @since 1.0.0
		 */
		close: function() {
			var modal = document.getElementById('europarcel-iframe-modal');
			if (!modal) {
				return;
			}

			// Remove escape key event listener
			if (modal.escapeHandler) {
				document.removeEventListener('keydown', modal.escapeHandler);
			}

			// Restore original styles
			var originalBodyOverflow = modal.dataset.originalBodyOverflow || '';
			var originalBodyHeight = modal.dataset.originalBodyHeight || '';
			var originalHtmlOverflow = modal.dataset.originalHtmlOverflow || '';

			document.body.style.overflow = originalBodyOverflow;
			document.body.style.height = originalBodyHeight;
			document.body.style.position = '';
			document.body.style.width = '';
			document.documentElement.style.overflow = originalHtmlOverflow;

			// Remove modal from DOM
			modal.remove();
		}
	};


})(jQuery);
