/**
 * EuroParcel admin order "See on map" modal.
 *
 * Binds click handlers to .europarcel-see-on-map buttons in the admin order
 * view and opens a lightweight iframe modal to maps.europarcel.com with the
 * locker pre-selected via the ?locker_id= URL parameter.
 *
 * @since 1.1.1
 */
(function () {
    'use strict';

    var config = window.europarcelAdminMap || {};
    var mapBaseUrl = config.mapUrl || 'https://maps.europarcel.com/';
    var i18n = config.i18n || {};
    var lastFocusedTrigger = null;

    function buildMapUrl(lockerId, countryCode) {
        var url = new URL(mapBaseUrl);
        url.searchParams.set('country_code', countryCode || 'RO');
        url.searchParams.set('locker_id', String(lockerId));
        return url.toString();
    }

    function openModal(lockerId, countryCode, triggerEl) {
        closeModal();
        lastFocusedTrigger = triggerEl || document.activeElement;

        var overlay = document.createElement('div');
        overlay.className = 'europarcel-admin-map-overlay';
        overlay.setAttribute('role', 'dialog');
        overlay.setAttribute('aria-modal', 'true');
        overlay.setAttribute('aria-label', i18n.map_title || 'Locker location');

        var dialog = document.createElement('div');
        dialog.className = 'europarcel-admin-map-dialog';

        var closeBtn = document.createElement('button');
        closeBtn.type = 'button';
        closeBtn.className = 'europarcel-admin-map-close';
        closeBtn.setAttribute('aria-label', i18n.close || 'Close');
        closeBtn.textContent = '×';
        closeBtn.addEventListener('click', closeModal);

        var iframe = document.createElement('iframe');
        iframe.className = 'europarcel-admin-map-iframe';
        iframe.src = buildMapUrl(lockerId, countryCode);
        iframe.setAttribute('title', i18n.map_title || 'Locker location');
        iframe.setAttribute('loading', 'lazy');
        iframe.setAttribute('allow', 'geolocation');
        iframe.setAttribute('referrerpolicy', 'no-referrer');
        iframe.setAttribute('sandbox', 'allow-scripts allow-same-origin allow-popups allow-forms');

        dialog.appendChild(closeBtn);
        dialog.appendChild(iframe);
        overlay.appendChild(dialog);
        overlay.addEventListener('click', function (event) {
            if (event.target === overlay) {
                closeModal();
            }
        });

        document.body.appendChild(overlay);
        document.body.classList.add('europarcel-admin-map-open');
        document.addEventListener('keydown', onKeyDown);

        // Move focus into the dialog so keyboard users aren't left on the page behind
        closeBtn.focus();
    }

    function closeModal() {
        var overlay = document.querySelector('.europarcel-admin-map-overlay');
        if (overlay && overlay.parentNode) {
            overlay.parentNode.removeChild(overlay);
        }
        document.body.classList.remove('europarcel-admin-map-open');
        document.removeEventListener('keydown', onKeyDown);

        if (lastFocusedTrigger && typeof lastFocusedTrigger.focus === 'function') {
            try {
                lastFocusedTrigger.focus();
            } catch (e) {
                // Element may have been removed from the DOM; ignore.
            }
        }
        lastFocusedTrigger = null;
    }

    function onKeyDown(event) {
        if (event.key === 'Escape') {
            closeModal();
            return;
        }
        if (event.key !== 'Tab') {
            return;
        }
        // Cross-origin iframe contents are not tabbable from the parent; keep
        // keyboard focus trapped on the only parent-owned control (close button)
        // so users can't tab out into the page behind the modal.
        var overlay = document.querySelector('.europarcel-admin-map-overlay');
        if (!overlay) {
            return;
        }
        var closeBtn = overlay.querySelector('.europarcel-admin-map-close');
        if (!closeBtn) {
            return;
        }
        event.preventDefault();
        closeBtn.focus();
    }

    function onButtonClick(event) {
        var button = event.target.closest('.europarcel-see-on-map');
        if (!button) return;
        event.preventDefault();
        var lockerId = button.getAttribute('data-locker-id');
        if (!lockerId) return;
        var countryCode = button.getAttribute('data-country-code') || 'RO';
        openModal(lockerId, countryCode, button);
    }

    function init() {
        document.addEventListener('click', onButtonClick);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
