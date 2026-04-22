<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * EuroParcel Admin Order View
 *
 * Renders eAWB delivery details inline under the shipping line item on the
 * WooCommerce admin order edit screen: carrier, locker name/ID, full address,
 * and a "See on map" button. Compatible with HPOS and legacy order storage.
 *
 * @link       https://eawb.ro
 * @since      1.1.1
 *
 * @package    Europarcel
 * @subpackage Europarcel/includes
 */
require_once EUROPARCELCOM_WC_ROOT_PATH . '/includes/class-europarcel-constants.php';

/**
 * EuroParcel Admin Order Class
 *
 * Hooks into woocommerce_after_order_itemmeta to render locker details under
 * the EuroParcel shipping line item, plus filter_hidden_order_itemmeta to hide
 * the raw shipping-rate internals from the same view.
 *
 * @since      1.1.1
 * @package    Europarcel
 * @subpackage Europarcel/includes
 * @author     EuroParcel <cs@europarcel.com>
 */
class EuroParcelComWC_Admin_Order {

    const METHOD_PREFIX = 'europarcelcom_wc_shipping';

    /**
     * Render eAWB locker details inline under the shipping line item.
     *
     * Fires on woocommerce_after_order_itemmeta for every order item row. We
     * bail unless the row is a EuroParcel shipping line item for a locker
     * delivery — home-delivery rows already show everything useful via the
     * stock shipping method label and items meta.
     *
     * @since    1.1.1
     * @param    int                        $item_id    Order item ID
     * @param    WC_Order_Item              $item       Current item
     * @param    WC_Product|null|false      $product    Product for product items; null/false otherwise
     */
    public function render_shipping_item_details($item_id, $item, $product) {
        // Defense in depth — WC already gates the order edit screen on this
        // capability, but this method can be called from arbitrary contexts
        // if the hook ever fires outside the admin edit page.
        if (!current_user_can('edit_shop_orders')) {
            return;
        }
        if (!$item instanceof WC_Order_Item_Shipping) {
            return;
        }
        if (strpos($item->get_method_id(), self::METHOD_PREFIX) !== 0) {
            return;
        }

        $service_id = (int) $item->get_meta('service_id', true);
        $carrier_id = (int) $item->get_meta('carrier_id', true);
        $locker_id = $item->get_meta('fixed_location_id', true);
        $locker_name = $item->get_meta('_europarcel_locker_name', true);
        $locker_address = $item->get_meta('_europarcel_locker_address', true);
        $locker_locality = $item->get_meta('_europarcel_locker_locality', true);
        $locker_county = $item->get_meta('_europarcel_locker_county', true);
        $is_locker = ($service_id === 2) || !empty($locker_id);

        if (!$is_locker) {
            return;
        }

        $full_address_parts = array_filter([$locker_address, $locker_locality, $locker_county], 'strlen');
        $full_address = implode(', ', $full_address_parts);

        $carrier_name = $carrier_id > 0 ? \EuroparcelComWCShipping\EuroParcelComWC_Constants::get_carrier_name($carrier_id) : '';
        $eawb_logo_url = plugins_url('assets/images/eawb-logo.webp', EUROPARCELCOM_WC_ROOT_PATH . '/europarcel-com.php');

        $order = $item->get_order();
        $country_code = ($order && $order->get_shipping_country()) ? $order->get_shipping_country() : 'RO';
        ?>
        <div class="europarcel-item-details">
            <div class="europarcel-item-details__row europarcel-item-details__brand">
                <img src="<?php echo esc_url($eawb_logo_url); ?>" alt="eAWB" class="europarcel-item-details__brand-logo" />
            </div>
            <?php if (!empty($locker_name)) : ?>
                <div class="europarcel-item-details__row europarcel-item-details__locker-name">
                    <?php echo esc_html($locker_name); ?>
                    <?php if (!empty($carrier_name)) : ?>
                        <span class="europarcel-item-details__carrier-inline"><?php echo esc_html(' (' . $carrier_name . ')'); ?></span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            <?php if (!empty($full_address)) : ?>
                <div class="europarcel-item-details__row europarcel-item-details__address">
                    <span class="dashicons dashicons-location" aria-hidden="true"></span>
                    <span><?php echo esc_html($full_address); ?></span>
                </div>
            <?php endif; ?>
            <?php if (!empty($locker_id)) : ?>
                <div class="europarcel-item-details__row europarcel-item-details__id">
                    <span class="dashicons dashicons-tag" aria-hidden="true"></span>
                    <?php
                    /* translators: %s: locker unique identifier */
                    echo esc_html(sprintf(__('ID %s', 'europarcel-com'), $locker_id));
                    ?>
                </div>
                <div class="europarcel-item-details__row europarcel-item-details__actions">
                    <button type="button"
                            class="button europarcel-see-on-map"
                            data-locker-id="<?php echo esc_attr($locker_id); ?>"
                            data-country-code="<?php echo esc_attr($country_code); ?>">
                        <span class="dashicons dashicons-location"></span>
                        <?php esc_html_e('See on map', 'europarcel-com'); ?>
                    </button>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Enqueue the admin order panel stylesheet, map-modal script, and
     * localized data on the order edit screen.
     *
     * @since    1.1.1
     * @param    string    $hook    Current admin page hook
     */
    public function enqueue_assets($hook) {
        if (!$this->is_order_edit_screen($hook)) {
            return;
        }

        $plugin_root = EUROPARCELCOM_WC_ROOT_PATH . '/europarcel-com.php';

        wp_enqueue_style(
            'europarcel-admin-order',
            plugins_url('assets/css/europarcel-admin-order.css', $plugin_root),
            [],
            EUROPARCELCOM_WC_VERSION
        );

        wp_enqueue_script(
            'europarcel-admin-order-map',
            plugins_url('assets/js/europarcel-admin-order-map.js', $plugin_root),
            [],
            EUROPARCELCOM_WC_VERSION,
            true
        );

        wp_localize_script('europarcel-admin-order-map', 'europarcelAdminMap', [
            'mapUrl' => 'https://maps.europarcel.com/',
            'i18n' => [
                'close' => __('Close', 'europarcel-com'),
                'map_title' => __('Locker location', 'europarcel-com'),
            ],
        ]);
    }

    /**
     * Hide EuroParcel shipping-rate internals from the admin item meta view.
     *
     * The inline details rendered above already surface the useful info for locker
     * orders; for home-delivery orders these keys carry nothing actionable
     * (`carrier_id` is always 0 because eAWB resolves the carrier server-side at
     * export time, `service_id` is always 1). Hiding them declutters both cases.
     *
     * Note: `woocommerce_hidden_order_itemmeta` is a global filter — the generic
     * keys (`service_id`, `carrier_id`, `is_locker`, `fixed_location_id`) would
     * also be hidden on another shipping plugin's items if it happened to use
     * identical names. The collision risk is negligible in practice; if another
     * plugin ever conflicts, prefix our rate meta keys in class-europarcel-shipping.php.
     *
     * @since    1.1.1
     * @param    array    $hidden    Existing hidden meta keys
     * @return   array
     */
    public function filter_hidden_order_itemmeta($hidden) {
        return array_merge((array) $hidden, [
            'service_id',
            'is_locker',
            'fixed_location_id',
            'carrier_id',
            '_europarcel_locker_name',
            '_europarcel_locker_address',
            '_europarcel_locker_locality',
            '_europarcel_locker_county',
        ]);
    }

    /**
     * Detect whether the current admin screen is a WooCommerce order edit screen
     *
     * @since    1.1.1
     * @param    string    $hook    Current admin page hook
     * @return   bool
     */
    private function is_order_edit_screen($hook) {
        if (!function_exists('get_current_screen')) {
            return false;
        }
        $screen = get_current_screen();
        if (!$screen) {
            return false;
        }
        // HPOS: the orders list and the single-order edit share screen->id
        // `woocommerce_page_wc-orders`. Only the edit view carries ?action=edit.
        if ($screen->id === 'woocommerce_page_wc-orders') {
            $action = isset($_GET['action']) ? sanitize_key(wp_unslash($_GET['action'])) : '';
            return $action === 'edit';
        }
        // Legacy post-type-based single-edit screen (the list is `edit-shop_order`).
        if ($screen->id === 'shop_order') {
            return true;
        }
        return false;
    }
}
