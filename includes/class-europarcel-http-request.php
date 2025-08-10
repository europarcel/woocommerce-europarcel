<?php

namespace EawbShipping;
defined( 'ABSPATH' ) || exit;
class EawbHttpRequest {
    private int $instance_id;
    public function __construct($instance_id) {
        $this->instance_id=$instance_id;
    }
    public function get(string $function,
            array $data = array(),
            int $timeout = 15) {
        $params = array(
            'body' => $data,
            'headers' => self::getHeader(),
            'timeout' => $timeout
        );
        $url=EAWB_API_URL.$function;
        $response = wp_remote_get($url, $params);
        return self::handle_api_response($response);
    }

    public function post(string $function,
            array $data = array(),
            int $timeout = 15) {
        $params = array(
            'body' => $data,
            'headers' => self::getHeader(),
            'timeout' => $timeout
        );
        $url=EAWB_API_URL.$function;
        $response = wp_remote_post($url, $params);
        return self::handle_api_response($response);
    }

    private function handle_api_response($response) {
        if (is_array($response) && !is_wp_error($response)) {
            $code = wp_remote_retrieve_response_code($response);
            if ($code != 200) {
                throw new \Exception(); //API error
            }
        } else {
            if (is_wp_error($response)) {
                if ($response->get_error_code() === 'http_request_failed') {
                    throw new \Exception($response, '', 0);
                }
            }
            throw new \Exception();
        }

        return json_decode(wp_remote_retrieve_body($response), true);
    }

    private function getHeader() {
        $setings=get_option('woocommerce_eawb_shipping_'. $this->instance_id.'_settings');
        return [
            'accept' => 'application/json',
            'X-API-Key' => $setings['api_key']
        ];
    }
}
