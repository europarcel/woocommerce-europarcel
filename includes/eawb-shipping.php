<?php

defined('ABSPATH') || exit;
require_once EAWB_ROOT_PATH . '/lib/eawb-customer.php';
require_once EAWB_ROOT_PATH . '/includes/eawb-custom-fields.php';
include_once EAWB_ROOT_PATH . '/lib/eawb-constants.php';

class WC_Eawb_Shipping extends WC_Shipping_Method {

    public function __construct($instance_id = 0) {
        $this->id = 'eawb_shipping';
        $this->instance_id = absint($instance_id);
        $this->method_title = __('Eawb Shipping', 'woocommerce-shipping-plugin');
        $this->method_description = __('O metoda noua pentru a chema curierii', 'woocommerce-shipping-plugin');
        $this->supports = array('shipping-zones', 'instance-settings');
        //$this->plugin_id = 'woocommerce_eawb_shipping_';
        $this->init();

        $this->enabled = $this->get_option('enabled', 'yes');
        $this->title = $this->get_option('title', $this->method_title);
    }

    public function init() {
        $this->init_settings();
        $this->init_form_fields();
        //add_action('woocommerce_update_options_shipping_' . $this->id, array($this, 'process_admin_options'));
    }

    public function init_form_fields() {
        $this->form_fields = array(
            'enabled' => array(
                'title' => __('Enable/Disable', 'woocommerce-shipping-plugin'),
                'type' => 'checkbox',
                'label' => __('Enable this shipping method', 'woocommerce-shipping-plugin'),
                'default' => 'yes',
            ),
            'title' => array(
                'title' => __('Method Title', 'woocommerce-shipping-plugin'),
                'type' => 'text',
                'description' => __('This controls the title which the user sees during checkout.', 'woocommerce-shipping-plugin'),
                'default' => __('Custom Shipping', 'woocommerce-shipping-plugin'),
                'desc_tip' => true,
            ),
            'api_key' => array(
                'title' => __('Api Key', 'woocommerce-shipping-plugin'),
                'type' => 'text',
                'description' => __('The key required to access the server.', 'woocommerce-shipping-plugin'),
                'desc_tip' => true,
            ),
        );
        //$setings = get_option('woocommerce_eawb_shipping_settings');
        $post_data = $this->get_post_data();
        if (isset($post_data['woocommerce_' . $this->id . '_api_key'])) {
            $this->process_admin_options();
        }
        if (!isset($this->settings['api_key']) || empty($this->settings['api_key'])) {
            return;
        }
        $customer = new \EawbShipping\EawbCustomer;
        $customer_info = $customer->getCustomerInfo();
        if (!$customer_info) {
            $this->form_fields = array_merge($this->form_fields, array(
                'eawb_customer' => array(
                    'title' => __('Eroare la conectare', 'woocommerce-shipping-plugin'),
                    'type' => 'title',
            )));
            return;
        } else {
            $this->update_option('eawb_customer', $customer_info);
        }
        $shipping_classes = get_terms(array('taxonomy' => 'product_shipping_class', 'hide_empty' => false));
        $view_shipping_classes = [];
        foreach ($shipping_classes as $shipping_class) {
            $view_shipping_classes[$shipping_class->slug] = $shipping_class->name;
        }
        $this->form_fields = array_merge($this->form_fields, array(
            'customer_info' => array(// Nu va fi salvat, doar afișat
                'title' => __($customer_info['name'] . ' sunteti conectat la Eawb ', 'woocommerce-shipping-plugin'),
                'type' => 'title',
            ),
            'default_shipping' => array(
                'title' => __('Default Pickup Address', 'woocommerce-shipping-plugin'),
                'type' => 'select',
                'description' => __('Default pickup address', 'woocommerce-shipping-plugin'),
                'desc_tip' => true,
                'default' => "",
                'options' => $customer->getPickUpAdresses(),
            ),
            'default_billing' => array(
                'title' => __('Default Billing', 'woocommerce-shipping-plugin'),
                'type' => 'select',
                'description' => __('Default billing to innvoice', 'woocommerce-shipping-plugin'),
                'desc_tip' => true,
                'default' => "",
                'options' => $customer->getCutomerBillingAdresses(),
            ),
            'available_services' => array(
                'title' => __('Agreate Services', 'woocommerce-shipping-plugin'),
                'type' => 'multiselect',
                'description' => __('Select which services are available', 'woocommerce-shipping-plugin'),
                'desc_tip' => true,
                'class' => 'wc-enhanced-select',
                'css' => 'width: 450px;height:450px;',
                'default' => array(),
                'options' => \EawbShipping\EawbConstants::getAvailableServices()
            ),
            'courier_choice_method' => array(
                'title' => __('Courier choice method', 'woocommerce-shipping-plugin'),
                'type' => 'select',
                'options' => array(
                    'low_price' => __('First Low price', 'woocommerce-shipping-plugin'),
                    'carrier_order' => __('First In the order entered', 'woocommerce-shipping-plugin'),
                    'client_choice' => __('Client Choice', 'woocommerce-shipping-plugin')
                ),
                'default' => 'low_price',
                'description' => __('Modul de alegere curier', 'woocommerce-shipping-plugin'),
                'desc_tip' => true,
            ),
            /*
              'default_service' => array(
              'title' => __('Default Service', 'woocommerce-shipping-plugin'),
              'type' => 'select',
              'description' => __('Default selected service', 'woocommerce-shipping-plugin'),
              'desc_tip' => true,
              'default' => 'none',
              'options' => array_merge(['none' => ''], \EawbShipping\EawbConstants::getAvailableServices())
              ), */
            'price_type' => array(
                'title' => __('Tip preț transport', 'woocommerce-shipping-plugin'),
                'type' => 'select',
                'options' => array(
                    'fixed' => __('Preț fix'),
                    'calculated' => __('Preț calculat')
                ),
                'default' => 'fixed',
                'description' => __('Modul de calculare pret pentru client fix/calculat prin api', 'woocommerce-shipping-plugin'),
                'desc_tip' => true,
            ),
            'fixed_price_group' => array(
                'type' => 'fixed_price_group',
                'class' => 'eawb-price-type-dependent eawb-fixed-price'
            ),
            'calculated_price_group' => array(
                'type' => 'calculated_price_group',
                'class' => 'eawb-price-type-dependent eawb-calculated-price'
            ),
            'free_shipping_amount' => array(
                'title' => __('Free shiping from min amount', 'woocommerce-shipping-plugin'),
                'type' => 'number',
                'default' => 0,
                'description' => __('Minimum order price for free delivery.', 'woocommerce-shipping-plugin'),
                'desc_tip' => true,
            ),
            'free_shipping_classes' => array(
                'title' => __('Free shiping for product classes', 'woocommerce-shipping-plugin'),
                'type' => 'multiselect',
                'class' => 'wc-enhanced-select',
                'css' => 'width: 450px',
                'default' => array(),
                'description' => __('Free shiping dor classes.', 'woocommerce-shipping-plugin'),
                'desc_tip' => true,
                'options' => $view_shipping_classes
            ),
            'free_shipping_coupons' => array(
                'title' => __('Free shiping for coupons', 'woocommerce-shipping-plugin'),
                'type' => 'checkbox',
                'label' => __('Free shiping for coupons', 'woocommerce-shipping-plugin'),
                'default' => 'yes',
            ),
        ));
    }

    /**
     * Generează HTML pentru grupul de preț fix 
     */
    public function generate_fixed_price_group_html() {
        return \EawbShipping\Eawb_Shipping_Custom_Fields::fixed_price_group($this);
    }

    /**
     * Generează HTML pentru grupul de preț calculat 
     */
    public function generate_calculated_price_group_html() {
        return \EawbShipping\Eawb_Shipping_Custom_Fields::calculated_price_group($this);
    }

    public function process_admin_options() {
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'woocommerce-settings')) {
            error_log('[EAWB] Nonce verification failed');
            return false;
        }

        $post_data = $this->get_post_data();
        $form_fields = $this->get_form_fields();

        foreach ($form_fields as $key => $field) {
            try {
                $this->settings[$key] = $this->get_field_value($key, $field, $post_data);
            } catch (Exception $e) {
                $this->add_error($e->getMessage());
            }
        }
        $this->updateCustomFields();
        return update_option(
                $this->get_option_key(),
                apply_filters('woocommerce_shipping_' . $this->id . '_settings_values', $this->settings, $this),
                'yes'
        );
    }

    public function updateCustomFields() {
        // Procesează câmpurile noastre custom
        $fixed_price = isset($_POST['woocommerce_eawb_shipping_fixed_price']) ?
                wc_clean($_POST['woocommerce_eawb_shipping_fixed_price']) : '15';
        $this->update_option('fixed_price', $fixed_price);

        $default_weight = isset($_POST['woocommerce_eawb_shipping_default_weight']) ?
                wc_clean($_POST['woocommerce_eawb_shipping_default_weight']) : '1';
        $this->update_option('default_weight', $default_weight);
        $default_length = isset($_POST['woocommerce_eawb_shipping_default_length']) ?
                wc_clean($_POST['woocommerce_eawb_shipping_default_length']) : '15';
        $this->update_option('default_length', $default_length);
        $default_width = isset($_POST['woocommerce_eawb_shipping_default_width']) ?
                wc_clean($_POST['woocommerce_eawb_shipping_default_width']) : '15';
        $this->update_option('default_width', $default_width);
        $default_height = isset($_POST['woocommerce_eawb_shipping_default_height']) ?
                wc_clean($_POST['woocommerce_eawb_shipping_default_height']) : '15';
        $this->update_option('default_height', $default_height);

        $price_multiplier = isset($_POST['woocommerce_eawb_shipping_price_multiplier']) ?
                wc_clean($_POST['woocommerce_eawb_shipping_price_multiplier']) : '1.2';
        $this->update_option('price_multiplier', $price_multiplier);
    }

    public function calculate_shipping($package = array()) {
        $settings = get_option('woocommerce_eawb_shipping_settings');
        if (!$settings) {
            return false;
        }
        $has_free_shipping = false;
        if ($settings['free_shipping_amount'] > 0) {
            $package_amount = WC()->cart->cart_contents_total +
                    WC()->cart->tax_total;
            if ($package_amount > $settings['free_shipping_amount']) {
                $has_free_shipping = true;
            }
        }
        if ($settings['free_shipping_coupons']) {
            $applied_coupons = WC()->cart->get_applied_coupons();
            foreach ($applied_coupons as $coupon_code) {
                $coupon = new WC_Coupon($coupon_code);
                if ($coupon->get_free_shipping()) {
                    $has_free_shipping = true;
                }
            }
        }
        if ($settings['free_shipping_classes']) {
            
        }
        if ($has_free_shipping) {
            $this->add_rate(array(
                'id' => $this->id,
                'label' => 'Transport gratuit',
                'cost' => 0,
                'package' => $package,
            ));
            return;
        }
        if ($settings['price_type'] === 'fixed') {
            $this->add_rate(array(
                'id' => $this->id,
                'label' => 'Cost Transport',
                'cost' => $settings['fixed_price'],
                'package' => $package,
            ));
            return;
        }
        $prices = (new \EawbShipping\EawbCustomer())->getPrices($package);
        if ($prices && is_array($prices)) {
            if ($settings['courier_choice_method'] == 'client_choice') {
                foreach ($prices[0] as $price) { //home to home
                    $this->add_rate(array(
                        'id' => $this->id . '_' . $price['carrier_id'] . '_' . $price['service_id'],
                        'carrier_id' => $price['carrier_id'],
                        'service_id' => $price['service_id'],
                        'label' => __('Shipping Home To Home with', 'woocommerce-shipping-plugin') . $price['carrier'],
                        'cost' => $price['price']['total'] * $settings['price_multiplier'],
                        'package' => $package,
                    ));
                }
                if ($prices[1]) {
                    foreach ($prices[1] as $price) { //home to locker
                        $this->add_rate(array(
                            'id' => $this->id . '_' . $price['carrier_id'] . '_' . $price['service_id'],
                            'carrier_id' => $price['carrier_id'],
                            'service_id' => $price['service_id'],
                            'label' => __('Shipping Home To Locker with', 'woocommerce-shipping-plugin') . $price['carrier'],
                            'cost' => $price['price']['total'] * $settings['price_multiplier'],
                            'package' => $package,
                        ));
                    }
                }
                return;
            } else {
                if ($prices[0]) { //home to home
                    $price = $prices[0][0];
                    $this->add_rate(array(
                        'id' => $this->id . '_' . $price['carrier_id'] . '_' . $price['service_id'],
                        'carrier_id' => $price['carrier_id'],
                        'service_id' => $price['service_id'],
                        'label' => __('Shipping Home To Home with', 'woocommerce-shipping-plugin') . $price['carrier'],
                        'cost' => $price['price']['total'] * $settings['price_multiplier'],
                        'package' => $package,
                    ));
                }
                if ($prices[1]) { //home to locker
                    $price = $prices[1][0];
                    $this->add_rate(array(
                        'id' => $this->id . '_' . $price['carrier_id'] . '_' . $price['service_id'],
                        'carrier_id' => $price['carrier_id'],
                        'service_id' => $price['service_id'],
                        'label' => __('Shipping Home To Locker with', 'woocommerce-shipping-plugin') . $price['carrier'],
                        'cost' => $price['price']['total'] * $settings['price_multiplier'],
                        'package' => $package,
                    ));
                }
                return;
            }
        }
        return false;
    }
}
