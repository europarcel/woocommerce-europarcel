<?php

namespace EawbShipping;
defined( 'ABSPATH' ) || exit;
class EawbHttpRequest {
    
    public static function get(string $function,
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

    public static function post(string $function,
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

    private static function handle_api_response($response) {
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

    private static function getHeader() {
        $setings=get_option('woocommerce_eawb_shipping_settings');
        return [
            'accept' => 'application/json',
            'X-API-Key' => $setings['api_key']
        ];
    }
}
