<?php

class EuroparcelCheckout {

    public function __construct() {
        //add_filter('woocommerce_blocks_register_script_dependencies', [$this, 'add_locker_selector_dependency']);
        //if (!has_block('woocommerce/checkout')) {
        //    add_action('woocommerce_after_checkout_form', [$this, 'add_hidden_fields']);
        //}
        //add_action('wp_ajax_get_lockers', [$this,'get_lockers']);
        //add_action('wp_ajax_nopriv_get_lockers', [$this,'get_lockers']);

        add_action('woocommerce_checkout_create_order', [$this, 'save_locker_to_order'], 10, 2);
        add_action('wp_footer', [$this, 'maybe_display_locker_in_block']);
        //add_action('woocommerce_after_order_notes', [$this, 'display_locker_selection']);
    }

    public function maybe_display_locker_in_block() {
        wp_enqueue_style(
                'eawb-locker-css',
                plugins_url('europarcel-plugin/assets/css/locker-delivery.css')
        );
/*
        wp_enqueue_script(
                'eawb-locker-js',
                plugins_url('europarcel-plugin/assets/js/locker-delivery.js'),
                ['jquery', 'wp-util'],
                '1.0',
                true
        );
 * */

        wp_enqueue_script(
                'europarcel-locker-selector',
                plugins_url('europarcel-plugin/assets/js/locker-selector.js'),
                array('wp-plugins', 'wp-components', 'wp-element'),
                '1.0',
                true
        );
        wp_localize_script('europarcel-locker-selector', 'EuroparcelLockerData', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('europarcel_locker_nonce'),
        ));
        //$has_block = has_block('woocommerce/checkout');
        //$selected_locker = WC()->session->get('eawb_selected_locker');
    }

    

    public function save_locker_to_order($order, $data) {
        if (!empty($_POST['eawb_locker_id'])) {
            $order->update_meta_data('_eawb_locker_id', sanitize_text_field($_POST['eawb_locker_id']));
            $order->update_meta_data('_eawb_locker_instance', sanitize_text_field($_POST['eawb_locker_instance']));
        }
    }
    public function add_locker_selector_dependency($dependencies) {
        $dependencies[] = 'locker-selector';
        return $dependencies;
    }
    
}
