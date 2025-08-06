<?php

namespace EawbShipping;

defined('ABSPATH') || exit;
include_once EAWB_ROOT_PATH . '/lib/europarcel-constants.php';

class EawbRequestData {

    private $request_data = [
        'carrier_id' => 0,
        'service_id' => 0,
        'billing_to' => [
            'billing_address_id' => null
        ],
        'address_from' => [
            'address_id' => null
        ],
        'address_to' => [
            'email' => '',
            'phone' => '',
            'contact' => '',
            'company' => '',
            'country_code' => 'RO',
            'county_name' => '',
            'locality_name' => '',
            'street_name' => '',
            'street_number' => '',
            'street_details' => ''
        ],
        'content' => [
            'envelopes_count' => 0,
            'pallets_count' => 0,
            'parcels_count' => 0,
            'total_weight' => 0,
            'parcels' => [
                [
                    'size' => [
                        'weight' => 0,
                        'width' => 0,
                        'height' => 0,
                        'length' => 0
                    ],
                    'sequence_no' => 1
                ]
            ]
        ],
        'extra' => [
            'sms_sender' => false,
            'open_package' => false,
            'sms_recipient' => false,
            'parcel_content' => 'diverse',
            'internal_identifier' => 'Comanda X',
            'return_package' => false,
            'insurance_amount' => null,
            'insurance_amount_currency' => 'RON',
            'return_of_documents' => false,
            'bank_repayment_amount' => null,
            'bank_repayment_currency' => 'RON',
            'bank_holder' => '',
            'bank_iban' => ''
        ],
    ];

    public function __construct($allow_locker=false) {
        $setings = get_option('woocommerce_eawb_shipping_settings');
        if (!$setings || $setings['enabled'] != 'yes' || !$setings['default_shipping'] || !$setings['default_billing'] || !$setings['available_services'] || !$setings['default_weight'] || !$setings['default_length'] || !$setings['default_width'] || !$setings['default_height']) {
            throw new \Exception();
        }

        $services_config = \EawbShipping\EawbConstants::getSettingsServices($setings['available_services']);
        if (count($services_config) == 1) {
            $this->request_data['carrier_id'] = intval($services_config[0]['carrier_id']);
            $this->request_data['service_id'] = intval($services_config[0]['service_id']);
        } else {
            $carriers = count(array_unique(array_column($services_config, 'carrier_id')));
            $services = count(array_unique(array_column($services_config, 'service_id')));
            $this->request_data['carrier_id'] = $carriers == 1 ? intval($services_config[0]['carrier_id']) : 0;
            if ($allow_locker) {
                $this->request_data['service_id'] = $services == 1 ? intval($services_config[0]['service_id']) : 0;
            } else {
                $this->request_data['service_id'] = 1;
            }
        }
        $this->request_data['billing_to']['billing_address_id'] = intval($setings['default_billing']);
        $this->request_data['address_from']['address_id'] = intval($setings['default_shipping']);
        $this->request_data['content']['parcels_count'] = 1;
        $this->request_data['content']['total_weight'] = floatval($setings['default_weight']);
        $this->request_data['content']['parcels'] = [
            [
                'size' => [
                    'weight' => floatval($setings['default_weight']),
                    'width' => floatval($setings['default_width']),
                    'height' => floatval($setings['default_height']),
                    'length' => floatval($setings['default_length'])
                ],
                'sequence_no' => 1
            ]
        ];
    }

    public function setCarrierId($carrier_id) {
        $this->request_data['carrier_id'] = $carrier_id;
    }

    public function setServiceId($service_id) {
        $this->request_data['carier_id'] = $service_id;
    }

    public function setDeliveryAddress($delivery_address) { //set request address_to
        $this->request_data['address_to'] = $delivery_address;
    }

    public function setContent($param) {
        
    }

    public function setExtraOptions($param) {
        
    }

    public function getData() {
        return $this->request_data;
    }
}
