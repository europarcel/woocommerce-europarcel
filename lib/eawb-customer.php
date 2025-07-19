<?php

namespace EawbShipping;

defined('ABSPATH') || exit;
require_once EAWB_ROOT_PATH . '/includes/eawb-http-request.php';

class EawbCustomer {

    public function getCustomerInfo() {
        try {
            $response = \EawbShipping\EawbHttpRequest::get('public/account/profile');
        } catch (\Exception $ex) {
            return null;
        }
        if (is_array($response) && isset($response['data']['name'])) {
            return $response['data']['name'];
        }
    }

    public function getCutomerBillingAdresses() {
        try {
            $data = [
                'page' => 1,
                'per_page' => 200
            ];
            $response = \EawbShipping\EawbHttpRequest::get('public/addresses/billing', $data);
            if (is_array($response) && isset($response['list'])) {
                $ret[0]="";
                foreach ($response['list'] as $adress) {
                    if ($adress['address_type']=='individual') {
                        $ret[$adress['id']] = $adress['contact'].', '.$adress['locality_name'].', '.$adress['street_no'];
                    } else {
                        $ret[$adress['id']] = $adress['company'].', '.$adress['locality_name'].', '.$adress['street_no'];
                    }
                }
                return $ret;
            } else {
                return [];
            }
        } catch (\Exception $ex) {
            return [];
        }
    }
    public function getPickUpAdresses() {
        try {
            $data = [
                'page' => 1,
                'per_page' => 200
            ];
            $response = \EawbShipping\EawbHttpRequest::get('public/addresses/shipping', $data);
            if (is_array($response) && isset($response['list'])) {
                $ret[0]="";
                foreach ($response['list'] as $adress) {
                    if ($adress['address_type']=='individual') {
                        $ret[$adress['id']] = $adress['contact'].', '.$adress['locality_name'].', '.$adress['street_no'];
                    } else {
                        $ret[$adress['id']] = $adress['company'].', '.$adress['locality_name'].', '.$adress['street_no'];
                    }
                }
                return $ret;
            } else {
                return [];
            }
        } catch (\Exception $ex) {
            return [];
        }
    }
}
