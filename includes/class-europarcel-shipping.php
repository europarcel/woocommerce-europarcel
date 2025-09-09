<?php

defined('ABSPATH') || exit;
require_once EUROPARCEL_ROOT_PATH . '/lib/europarcel-customer.php';
require_once EUROPARCEL_ROOT_PATH . '/includes/class-europarcel-custom-fields.php';
include_once EUROPARCEL_ROOT_PATH . '/lib/europarcel-constants.php';

class WC_Europarcel_Shipping extends WC_Shipping_Method {

    public function __construct($instance_id = 0) {
        $this->instance_id = absint($instance_id);
        $this->id = 'europarcel_shipping';
        if ($this->instance_id) {
            $this->id .= '_' . $this->instance_id;
        }
        $this->method_title = __('Europarcel Shipping', 'europarcel');
        $this->method_description = __('O metoda noua pentru a chema curierii', 'europarcel');
        $this->supports = array('shipping-zones', 'instance-settings');
        $this->init();

        $this->enabled = $this->get_option('enabled', 'yes');
        $this->title = $this->get_option('title', $this->method_title);
    }

    public function init() {
        if (!$this->instance_id) {
            return;
        }
        $this->settings = get_option('woocommerce_europarcel_shipping_' . $this->instance_id . '_settings');
        $this->init_form_fields();
    }

    public function init_form_fields() {
        $this->form_fields = array(
            'enabled' => array(
                'title' => __('Enable/Disable', 'europarcel'),
                'type' => 'checkbox',
                'label' => __('Enable this shipping method', 'europarcel'),
                'default' => 'yes',
            ),
            'title' => array(
                'title' => __('Method Title', 'europarcel'),
                'type' => 'text',
                'description' => __('This controls the title which the user sees during checkout.', 'europarcel'),
                'default' => __('Europarcel Shipping', 'europarcel'),
                'desc_tip' => true,
            ),
            'api_key' => array(
                'title' => __('Api Key', 'europarcel'),
                'type' => 'text',
                'description' => __('The key required to access the server.', 'europarcel'),
                'desc_tip' => true,
            ),
        );
        $post_data = $this->get_post_data();
        if (isset($post_data['woocommerce_' . $this->id . '_api_key'])) {
            $this->process_admin_options();
            //$this->settings['api_key']=$post_data['woocommerce_' . $this->id . '_api_key'];
        }
        if (!isset($this->settings['api_key']) || empty($this->settings['api_key'])) {
            return;
        }
        $customer = new \EuroparcelShipping\EuroparcelCustomer($this->instance_id);
        $customer_info = $customer->getCustomerInfo();
        if (!$customer_info) {
            $this->form_fields = array_merge($this->form_fields, array(
                'europarcel_customer' => array(
                    'title' => __('Eroare la conectare', 'europarcel'),
                    'type' => 'title',
            )));
            return;
        } else {
            $this->update_option('europarcel_customer', $customer_info);
        }
        $shipping_classes = get_terms(array('taxonomy' => 'product_shipping_class', 'hide_empty' => false));
        $view_shipping_classes = [];
        foreach ($shipping_classes as $shipping_class) {
            $view_shipping_classes[$shipping_class->slug] = $shipping_class->name;
        }
        $this->form_fields = array_merge($this->form_fields, array(
            'customer_info' => array(
                'title' => __($customer_info['name'] . ' sunteti conectat la eAWB ', 'europarcel'),
                'type' => 'title',
            ),
            'default_shipping' => array(
                'title' => __('Default Pickup Address', 'europarcel'),
                'type' => 'select',
                'description' => __('Default pickup address', 'europarcel'),
                'desc_tip' => true,
                'default' => "",
                'options' => $customer->getPickUpAdresses(),
            ),
            'default_billing' => array(
                'title' => __('Default Billing', 'europarcel'),
                'type' => 'select',
                'description' => __('Default billing to innvoice', 'europarcel'),
                'desc_tip' => true,
                'default' => "",
                'options' => $customer->getCutomerBillingAdresses(),
            ),
            'available_services' => array(
                'title' => __('Agreate Services', 'europarcel'),
                'type' => 'multiselect',
                'description' => __('Select which services are available', 'europarcel'),
                'desc_tip' => true,
                'class' => 'wc-enhanced-select',
                'css' => 'width: 450px;height:450px;',
                'default' => array(),
                'options' => \EuroparcelShipping\EuroparcelConstants::getAvailableServices()
            ),
            'excluded_locker_classes' => array(
                'title' => __('Excludes classes for locker', 'europarcel'),
                'type' => 'multiselect',
                'class' => 'wc-enhanced-select',
                'css' => 'width: 450px',
                'default' => array(),
                'description' => __('Excludes locker transport for products in classes', 'europarcel'),
                'desc_tip' => true,
                'options' => $view_shipping_classes
            ),
            'title_for_h2h' => array(
                'title' => __('Payment method displayed for delivery to address', 'europarcel'),
                'type' => 'text',
                'default' => 'Transport la adresa prin Europarcel',
                'description' => __('Payment method displayed for delivery to address', 'europarcel'),
                'desc_tip' => true,
            ),
            'fixed_price_h2h' => array(
                'title' => __('Fixed price for shipping to home', 'europarcel'),
                'type' => 'number',
                'default' => 15,
                'description' => __('Fixed price for shipping to home', 'europarcel'),
                'desc_tip' => true,
            ),
            'title_for_h2l' => array(
                'title' => __('Payment method displayed for delivery to locker', 'europarcel'),
                'type' => 'text',
                'default' => 'Transport la lockerul ales prin Europarcel',
                'description' => __('Payment method displayed for delivery to locker', 'europarcel'),
                'desc_tip' => true,
            ),
            'fixed_price_h2l' => array(
                'title' => __('Fixed price for shipping to locker', 'europarcel'),
                'type' => 'number',
                'default' => 15,
                'description' => __('Fixed price for shipping to locker', 'europarcel'),
                'desc_tip' => true,
            ),
            'free_shipping_amount_to_home' => array(
                'title' => __('Free shiping from min amount for shipping to home', 'europarcel'),
                'type' => 'number',
                'default' => 0,
                'description' => __('Free shiping from min amount for shipping to home.', 'europarcel'),
                'desc_tip' => true,
            ),
            'free_shipping_amount_to_locker' => array(
                'title' => __('Free shiping from min amount for shipping to locker', 'europarcel'),
                'type' => 'number',
                'default' => 0,
                'description' => __('Minimum order price for free delivery for shipping to locker.', 'europarcel'),
                'desc_tip' => true,
            ),
            'free_shipping_classes_to_home' => array(
                'title' => __('Free shiping for product classes for shipping to home', 'europarcel'),
                'type' => 'multiselect',
                'class' => 'wc-enhanced-select',
                'css' => 'width: 450px',
                'default' => array(),
                'description' => __('Free shiping dor classes for shipping to home.', 'europarcel'),
                'desc_tip' => true,
                'options' => $view_shipping_classes
            ),
            'free_shipping_classes_to_locker' => array(
                'title' => __('Free shiping for product classes for shipping to locker', 'europarcel'),
                'type' => 'multiselect',
                'class' => 'wc-enhanced-select',
                'css' => 'width: 450px',
                'default' => array(),
                'description' => __('Free shiping dor classes for shipping to locker.', 'europarcel'),
                'desc_tip' => true,
                'options' => $view_shipping_classes
            ),
        ));
    }

    public function process_admin_options() {
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'woocommerce-settings')) {
            error_log('[EAWB] Nonce verification failed');
            return false;
        }
        //parent::process_admin_options();
        $post_data = $this->get_post_data();
        $form_fields = $this->get_form_fields();

        foreach ($form_fields as $key => $field) {
            try {
                $this->settings[$key] = $this->get_field_value($key, $field, $post_data);
            } catch (Exception $e) {
                $this->add_error($e->getMessage());
            }
        }

        if (get_option($this->plugin_id . $this->id . '_settings')) {
            return update_option(
                    $this->plugin_id . $this->id . '_settings',
                    apply_filters('woocommerce_shipping_' . $this->id . '_settings_values', $this->settings, $this),
                    'yes'
            );
        } else {
            return add_option(
                    $this->plugin_id . $this->id . '_settings',
                    apply_filters('woocommerce_shipping_' . $this->id . '_settings_values', $this->settings, $this)
            );
        }
    }

    public function calculate_shipping($package = array()) {
        $applied_coupons = WC()->cart->get_applied_coupons();
        foreach ($applied_coupons as $coupon_code) {
            $coupon = new WC_Coupon($coupon_code);
            if ($coupon->get_free_shipping()) {
                $this->add_rate(array(
                    'id' => $this->id . '_free',
                    'label' => 'Transport gratuit cupon',
                    'cost' => 0,
                    'package' => $package,
                    'meta_data' => [
                        'carrier_id' => 0,
                        'service_id' => 0,
                    ]
                ));
                return;
            }
        }
        $customer = new \EuroparcelShipping\EuroparcelCustomer($this->instance_id);
        if (!$customer->settings) {
            return false;
        }
        $contents = $package['contents'];
        $allow_locker_shiping = true;
        $has_free_shipping_to_home = false;
        $has_free_shipping_to_locker = false;
        $products_free_shiping_to_home = 0;
        $products_free_shiping_to_locker = 0;
        foreach ($contents as $product) {
            if (!$product['data']->needs_shipping()) {
                continue;
            }
            $product_shiping_class = $product['data']->get_shipping_class();
            if (isset($customer->settings['free_shipping_classes_to_home']) && $customer->settings['free_shipping_classes_to_home']) {
                $product_shiping_class = $product['data']->get_shipping_class();
                if (in_array($product_shiping_class, $customer->settings['free_shipping_classes_to_home'])) {
                    $products_free_shiping_to_home++;
                }
            }
            if (isset($customer->settings['free_shipping_classes_to_locker']) && $customer->settings['free_shipping_classes_to_locker']) {
                $product_shiping_class = $product['data']->get_shipping_class();
                if (in_array($product_shiping_class, $customer->settings['free_shipping_classes_to_locker'])) {
                    $products_free_shiping_to_locker++;
                }
            }
            if (isset($customer->settings['excluded_locker_classes']) && $customer->settings['excluded_locker_classes']) {
                if (in_array($product_shiping_class, $customer->settings['excluded_locker_classes'])) {
                    $allow_locker_shiping = false;
                }
            }
        }
        if (count($contents) == $products_free_shiping_to_home) {
            $has_free_shipping_to_home = true;
        }
        if (count($contents) == $products_free_shiping_to_locker) {
            $has_free_shipping_to_locker = true;
        }
        if (isset($customer->settings['free_shipping_amount_to_home']) && $customer->settings['free_shipping_amount_to_home'] > 0) {
            $package_amount = WC()->cart->cart_contents_total +
                    WC()->cart->tax_total;
            if ($package_amount > $customer->settings['free_shipping_amount_to_home']) {
                $has_free_shipping_to_home = true;
            }
        }

        if (isset($customer->settings['free_shipping_amount_to_locker']) && $customer->settings['free_shipping_amount_to_locker'] > 0) {
            $package_amount = WC()->cart->cart_contents_total +
                    WC()->cart->tax_total;
            if ($package_amount > $customer->settings['free_shipping_amount_to_locker']) {
                $has_free_shipping_to_locker = true;
            }
        }
        $label = isset($customer->settings['title_for_h2h']) ? $customer->settings['title_for_h2h']:"Livrare la adresa";
        if (!empty($customer->get_home_carriers())) {
            if ($has_free_shipping_to_home) {
                $this->add_rate(array(
                    'id' => $this->id . '_free_h2h',
                    'label' => $label,
                    'cost' => 0,
                    'package' => $package,
                    'meta_data' => [
                        'carrier_id' => 0,
                        'service_id' => 1,
                    ]
                ));
            } else {
                $this->add_rate(array(
                    'id' => $this->id . '_fixed_h2h',
                    'label' => $label,
                    'cost' => $customer->settings['fixed_price_h2h'],
                    'package' => $package,
                    'meta_data' => [
                        'carrier_id' => 0,
                        'service_id' => 1,
                    ]
                ));
            }
        }

        // Add locker shipping option
        $customer_lockers_cariers = $customer->get_locker_carriers();
        if ($allow_locker_shiping && !empty($customer_lockers_cariers)) {
            $locker_info = WC()->session->get('locker_info');
            $user_id = get_current_user_id();
            if ($user_id) {
                $user_lockers = get_user_meta($user_id, '_europarcel_carrier_lockers', true);
                if (is_array($user_lockers)) {
                    foreach ($user_lockers as $carrier_id => $locker) {
                        if (in_array($carrier_id, $customer_lockers_cariers) && is_array($locker)) {
                            $locker_info = [
                                'locker_id' => $locker['locker_id'],
                                'carrier_id' => $locker['carrier_id'],
                                'instance_id' => $this->instance_id,
                                'carrier_name' => $locker['carrier_name'],
                                'locker_name' => $locker['locker_name'],
                                'locker_address' => $locker['locker_address']
                            ];
                            break;
                        }
                    }
                }
            }
            $meta_data = [
                'service_id' => 2,
                'is_locker' => true,
                'fixed_location_id' => $locker_info ? $locker_info['locker_id'] : 0,
                'carrier_id' => $locker_info ? $locker_info['carrier_id'] : 0,
            ];
            $label = isset($customer->settings['title_for_h2l']) ? $customer->settings['title_for_h2l'] : "Livrare la locker";
            if ($has_free_shipping_to_locker) {

                $this->add_rate(array(
                    'id' => $this->id . '_free_locker_' . $this->instance_id,
                    'label' => $label,
                    'cost' => 0,
                    'package' => $package,
                    'meta_data' => $meta_data
                ));
            } else {
                $this->add_rate(array(
                    'id' => $this->id . '_fixed_locker_' . $this->instance_id,
                    'label' => $label,
                    'cost' => $customer->settings['fixed_price_h2l'],
                    'package' => $package,
                    'meta_data' => $meta_data
                ));
            }
        }
    }
}
