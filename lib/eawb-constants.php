<?php
namespace EawbShipping;
class EawbConstants {

    public static function getAvailableServices() {
        return [
            'cargus_national' => __('Cargus National', 'woocommerce-shipping-plugin'),
            'dpd_standard' => __('DPD Standard', 'woocommerce-shipping-plugin'),
            'fan_courier' => __('FanCourier Standard', 'woocommerce-shipping-plugin'),
            'gls_national' => __('GLS National', 'woocommerce-shipping-plugin'),
            'sameday' => __('SameDay', 'woocommerce-shipping-plugin'),
            'easybox' => __('SameDay EasyBox', 'woocommerce-shipping-plugin'),
            'fanbox' => __('FanCourier Box', 'woocommerce-shipping-plugin'),
            'dpdbox' => __('DPD Box', 'woocommerce-shipping-plugin'),
        ];
    }
}
