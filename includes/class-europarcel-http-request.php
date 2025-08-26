<?php

namespace EuroparcelShipping;
defined( 'ABSPATH' ) || exit;
class EuroparcelHttpRequest {
    private int $instance_id;
    public function __construct($instance_id) {
        $this->instance_id=$instance_id;
    }
    public function get(string $function, array $data = array(), int $timeout = 15) {
        $url = EUROPARCEL_API_URL . $function;
        if (!empty($data)) {
            $url .= '?' . http_build_query($data);
        }
        
        $params = array(
            'headers' => self::getHeader(),
            'timeout' => $timeout
        );
        
        $response = wp_remote_get($url, $params);
        return self::handle_api_response($response);
    }

    public function post(string $function, array $data = array(), int $timeout = 15) {
        $params = array(
            'body' => $data,
            'headers' => self::getHeader(),
            'timeout' => $timeout
        );
        $url = EUROPARCEL_API_URL . $function;
        $response = wp_remote_post($url, $params);
        return self::handle_api_response($response);
    }

    private function handle_api_response($response) {
        if (is_wp_error($response)) {
            throw new \Exception('HTTP request failed: ' . $response->get_error_message());
        }
        
        if (!is_array($response)) {
            throw new \Exception('Invalid response format');
        }
        
        $code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        if ($code != 200) {
            $error_data = json_decode($body, true);
            if ($error_data && isset($error_data['message'])) {
                throw new \Exception('API Error (' . $code . '): ' . $error_data['message']);
            } else {
                throw new \Exception('API Error: HTTP ' . $code);
            }
        }

        $decoded = json_decode($body, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Invalid JSON response: ' . json_last_error_msg());
        }
        
        return $decoded;
    }

    private function getHeader() {
        $settings = get_option('woocommerce_europarcel_shipping_'. $this->instance_id.'_settings');
        return [
            'accept' => 'application/json',
            'X-API-Key' => isset($settings['api_key']) ? $settings['api_key'] : ''
        ];
    }
}
