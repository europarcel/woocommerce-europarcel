<?php

defined('ABSPATH') || exit;
require_once EAWB_ROOT_PATH . '/lib/europarcel-customer.php';
require_once EAWB_ROOT_PATH . '/includes/class-europarcel-custom-fields.php';
include_once EAWB_ROOT_PATH . '/lib/europarcel-constants.php';

class WC_Eawb_Shipping extends WC_Shipping_Method {

    public function __construct($instance_id = 0) {
        $this->instance_id = absint($instance_id);
        $this->id = 'eawb_shipping';
        if ($this->instance_id) {
            $this->id .= '_' . $this->instance_id;
        }
        $this->method_title = __('Eawb Shipping', 'europarcel');
        $this->method_description = __('O metoda noua pentru a chema curierii', 'europarcel');
        $this->supports = array('shipping-zones', 'instance-settings');
        //$this->plugin_id = 'woocommerce_eawb_shipping_';
        $this->init();

        $this->enabled = $this->get_option('enabled', 'yes');
        $this->title = $this->get_option('title', $this->method_title);
    }

    public function init() {
        //$this->init_settings();
        if (!$this->instance_id) {
            return;
        }
        $this->settings = get_option('woocommerce_eawb_shipping_' . $this->instance_id . '_settings');
        $this->init_form_fields();
        //add_action('woocommerce_update_options_shipping_' . $this->id, array($this, 'process_admin_options'));
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
        }
        if (!isset($this->settings['api_key']) || empty($this->settings['api_key'])) {
            return;
        }
        $customer = new \EawbShipping\EawbCustomer($this->instance_id);
        $customer_info = $customer->getCustomerInfo();
        if (!$customer_info) {
            $this->form_fields = array_merge($this->form_fields, array(
                'eawb_customer' => array(
                    'title' => __('Eroare la conectare', 'europarcel'),
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
            'customer_info' => array(
                'title' => __($customer_info['name'] . ' sunteti conectat la Eawb ', 'europarcel'),
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
                'options' => \EawbShipping\EawbConstants::getAvailableServices()
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
            'fixed_price_h2h' => array(
                'title' => __('Fixed price for shipping to home', 'europarcel'),
                'type' => 'number',
                'default' => 15,
                'description' => __('Fixed price for shipping to home', 'europarcel'),
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
        $settings = get_option('woocommerce_' . $this->id . '_settings');
        if (!$settings) {
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
            if ($settings['free_shipping_classes_to_home']) {
                $product_shiping_class = $product['data']->get_shipping_class();
                if (in_array($product_shiping_class, $settings['free_shipping_classes_to_home'])) {
                    $products_free_shiping_to_home++;
                }
            }
            if ($settings['free_shipping_classes_to_locker']) {
                $product_shiping_class = $product['data']->get_shipping_class();
                if (in_array($product_shiping_class, $settings['free_shipping_classes_to_locker'])) {
                    $products_free_shiping_to_locker++;
                }
            }
            if ($settings['excluded_locker_classes']) {
                if (in_array($product_shiping_class, $settings['excluded_locker_classes'])) {
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
        $prices = (new \EawbShipping\EawbCustomer($this->instance_id))->getPrices($package, $allow_locker_shiping);

        if ($settings['free_shipping_amount_to_home'] > 0) {
            $package_amount = WC()->cart->cart_contents_total +
                    WC()->cart->tax_total;
            if ($package_amount > $settings['free_shipping_amount_to_home']) {
                $has_free_shipping_to_home = true;
            }
        }

        if ($settings['free_shipping_amount_to_locker'] > 0) {
            $package_amount = WC()->cart->cart_contents_total +
                    WC()->cart->tax_total;
            if ($package_amount > $settings['free_shipping_amount_to_locker']) {
                $has_free_shipping_to_locker = true;
            }
        }
        if ($prices && !empty($prices[0])) {
            if ($has_free_shipping_to_home) {
                $this->add_rate(array(
                    'id' => $this->id . '_free_h2h',
                    'label' => 'Transport gratuit la adresa',
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
                    'label' => 'Cost Transport la adresa cu ' . $this->settings['title'],
                    'cost' => $settings['fixed_price_h2h'],
                    'package' => $package,
                    'meta_data' => [
                        'carrier_id' => 0,
                        'service_id' => 1,
                    ]
                ));
            }
        }
        if ($allow_locker_shiping && $prices && !empty($prices[1])) {
            if ($has_free_shipping_to_locker) {
                $this->add_rate(array(
                    'id' => $this->id . '_free_h2l',
                    'label' => 'Transport gratuit la locker' . $this->settings['title'],
                    'cost' => 0,
                    'package' => $package,
                    'meta_data' => [
                        'carrier_id' => 0,
                        'service_id' => 2,
                        'fixed_location_id' => 0
                    ]
                ));
            } else  {
                $this->add_rate(array(
                    'id' => $this->id . '_fixed_h2l',
                    'label' => 'Cost Transport la locker cu ' . $this->settings['title'],
                    'cost' => $settings['fixed_price_h2l'],
                    'package' => $package,
                    'meta_data' => [
                        'carrier_id' => 0,
                        'service_id' => 2,
                        'fixed_location_id' => 0,
                    ]
                ));
            }
        }
        return;
        /*
          if ($prices && is_array($prices)) {
          if ($settings['courier_choice_method'] == 'client_choice') {
          foreach ($prices[0] as $price) { //home to home
          $this->add_rate(array(
          'id' => $this->id . '_' . $price['carrier_id'] . '_' . $price['service_id'],
          'label' => __('Shipping Home To Home with ', 'europarcel') . $price['carrier'],
          'cost' => $price['price']['total'] * $settings['price_multiplier'],
          'package' => $package,
          'meta_data' => [
          'carrier_id' => $price['carrier_id'],
          'service_id' => $price['service_id'],
          ]
          ));
          }
          foreach ($prices[1] as $price) { //home to locker
          $this->add_rate(array(
          'id' => $this->id . '_' . $price['carrier_id'] . '_' . $price['service_id'],
          'label' => __('Shipping Home To Locker with ', 'europarcel') . $price['carrier'],
          'cost' => $price['price']['total'] * $settings['price_multiplier'],
          'package' => $package,
          'meta_data' => [
          'carrier_id' => $price['carrier_id'],
          'service_id' => $price['service_id'],
          ]
          ));
          }
          return;
          } else {
          if ($prices[0]) { //home to home
          $price = $prices[0][0];
          $this->add_rate(array(
          'id' => $this->id . '_' . $price['carrier_id'] . '_' . $price['service_id'],
          'label' => __('Shipping Home To Home with ', 'europarcel') . $price['carrier'],
          'cost' => $price['price']['total'] * $settings['price_multiplier'],
          'package' => $package,
          'meta_data' => [
          'carrier_id' => $price['carrier_id'],
          'service_id' => $price['service_id'],
          ]
          ));
          }
          if ($prices[1]) { //home to locker
          $price = $prices[1][0];
          $this->add_rate(array(
          'id' => $this->id . '_' . $price['carrier_id'] . '_' . $price['service_id'],
          'label' => __('Shipping Home To Locker with ', 'europarcel') . $price['carrier'],
          'cost' => $price['price']['total'] * $settings['price_multiplier'],
          'package' => $package,
          'meta_data' => [
          'carrier_id' => $price['carrier_id'],
          'service_id' => $price['service_id'],
          ]
          ));
          }
          return;
          }
          }
          return false;
         * 
         */
    }
}
