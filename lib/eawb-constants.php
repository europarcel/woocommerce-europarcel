<?php
namespace EawbShipping;
defined('ABSPATH') || exit;

class EawbConstants {

    public static function getAvailableServices() {
        return [
            'cargus_national' => __('Cargus National', 'europarcel'),
            'dpd_standard' => __('DPD Standard', 'europarcel'),
            'fan_courier' => __('FanCourier Standard', 'europarcel'),
            'gls_national' => __('GLS National', 'europarcel'),
            'sameday' => __('SameDay', 'europarcel'),
            'easybox' => __('SameDay EasyBox', 'europarcel'),
            'fanbox' => __('FanCourier Box', 'europarcel'),
            'dpdbox' => __('DPD Box', 'europarcel'),
        ];
    }
    public static function getSettingsServices(array $services) {
        $carrier_settings = [
            'cargus_national' => ['carrier'=>'cargus_national','carrier_id' =>1 ,'service_id' => 1],
            'dpd_standard' => ['carrier'=>'dpd_standard','carrier_id' =>2 ,'service_id' => 1],
            'fan_courier' => ['carrier'=>'fan_courier','carrier_id' =>3 ,'service_id' => 1],
            'gls_national' => ['carrier'=>'gls_national','carrier_id' =>4 ,'service_id' => 1],
            'sameday' => ['carrier'=>'sameday','carrier_id' =>6 ,'service_id' => 1],
            'easybox' => ['carrier'=>'easybox','carrier_id' =>6 ,'service_id' => 2],
            'fanbox' => ['carrier'=>'fanbox','carrier_id' =>3 ,'service_id' => 2],
            'dpdbox' => ['carrier'=>'dpdbox','carrier_id' =>2 ,'service_id' => 2],
        ];
        $ret = [];
        foreach ($services as $service) {
            $ret [] = $carrier_settings[$service];
        }
        return $ret;
    }
}
