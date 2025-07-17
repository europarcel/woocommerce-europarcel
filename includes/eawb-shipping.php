<?php

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
        $this->init_form_fields();
        $this->init_settings();
        add_action('woocommerce_update_options_shipping_' . $this->id, array($this, 'process_admin_options'));
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
                'default' => __('Custom Shippingx', 'woocommerce-shipping-plugin'),
                'desc_tip' => true,
            ),
            'cost' => array(
                'title' => __('Base Cost', 'woocommerce-shipping-plugin'),
                'type' => 'text',
                'description' => __('Base cost for shipping', 'woocommerce-shipping-plugin'),
                //'default' => '10',
                'desc_tip' => true,
            ),
            'available_services' => array(
                'title' => __('Agreate Services', 'woocommerce-shipping-plugin'),
                'type' => 'multiselect',
                'description' => __('Select which services are available', 'woocommerce-shipping-plugin'),
                'desc_tip' => true,
                'class' => 'wc-enhanced-select',
                'css' => 'width: 450px;height:450px;',
                'default' => array(),
                'options' => array(
                    'cargus_national' => __('Cargus National', 'woocommerce-shipping-plugin'),
                    'dpd_standard' => __('DPD Standard', 'woocommerce-shipping-plugin'),
                    'fan_courier' => __('FanCourier Standard', 'woocommerce-shipping-plugin'),
                    'gls_national' => __('GLS National', 'woocommerce-shipping-plugin'),
                    'sameday' => __('SameDay 24H', 'woocommerce-shipping-plugin'),
                ),
            ),
            'default_service' => array(
                'title' => __('Default Service', 'woocommerce-shipping-plugin'),
                'type' => 'select',
                'description' => __('Default selected service', 'woocommerce-shipping-plugin'),
                'desc_tip' => true,
                'default' => 'fan_courier',
                'options' => array(
                    'cargus_national' => __('Cargus National', 'woocommerce-shipping-plugin'),
                    'dpd_standard' => __('DPD Standard', 'woocommerce-shipping-plugin'),
                    'fan_courier' => __('FanCourier Standard', 'woocommerce-shipping-plugin'),
                    'gls_national' => __('GLS National', 'woocommerce-shipping-plugin'),
                    'sameday' => __('SameDay 24H', 'woocommerce-shipping-plugin'),
                ),
            ),
        );
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

        return update_option(
                $this->get_option_key(),
                apply_filters('woocommerce_shipping_' . $this->id . '_settings_values', $this->settings, $this),
                'yes'
        );
    }

    public function calculate_shipping($package = array()) {
        $this->add_rate(array(
            'id' => $this->id,
            'label' => $this->title,
            'cost' => $this->get_option('cost', 10),
            'package' => $package,
        ));
    }
}
