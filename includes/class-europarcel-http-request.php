<?php

/**
 * EuroParcel HTTP Request Handler
 *
 * Handles HTTP requests to the EuroParcel API including GET and POST
 * methods, authentication, error handling, and response processing.
 *
 * @link       https://eawb.ro
 * @since      1.0.0
 *
 * @package    Europarcel
 * @subpackage Europarcel/includes
 */

namespace EuroparcelShipping;

defined('ABSPATH') || exit;

/**
 * EuroParcel HTTP Request Class
 *
 * Manages HTTP communication with the EuroParcel API.
 * Handles authentication, request formatting, and response processing
 * for both GET and POST requests with proper error handling.
 *
 * @since      1.0.0
 * @package    Europarcel
 * @subpackage Europarcel/includes
 * @author     EuroParcel <cs@europarcel.com>
 */
class EuroparcelHttpRequest {

	/**
	 * The shipping method instance ID
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      int    $instance_id    WooCommerce shipping method instance ID
	 */
	private int $instance_id;

	/**
	 * Initialize the HTTP request handler
	 *
	 * Sets up the instance ID for accessing the correct
	 * shipping method settings and API key.
	 *
	 * @since    1.0.0
	 * @param    int    $instance_id    WooCommerce shipping method instance ID
	 */
	public function __construct($instance_id) {
		$this->instance_id = $instance_id;
	}
	/**
	 * Make a GET request to the EuroParcel API
	 *
	 * Sends a GET request to the specified API endpoint with optional
	 * query parameters and handles the response.
	 *
	 * @since    1.0.0
	 * @param    string    $function    API endpoint function name
	 * @param    array     $data        Optional query parameters
	 * @param    int       $timeout     Request timeout in seconds
	 * @return   array     Decoded API response
	 * @throws   \Exception             On API errors or invalid responses
	 */
	public function get(string $function, array $data = array(), int $timeout = 15) {
		$url = EUROPARCEL_API_URL . $function;
		
		if (!empty($data)) {
			$url .= '?' . http_build_query($data);
		}

		$params = array(
			'headers' => $this->getHeader(),
			'timeout' => $timeout
		);

		$response = wp_remote_get($url, $params);
		return $this->handle_api_response($response);
	}

	/**
	 * Make a POST request to the EuroParcel API
	 *
	 * Sends a POST request to the specified API endpoint with
	 * the provided data and handles the response.
	 *
	 * @since    1.0.0
	 * @param    string    $function    API endpoint function name
	 * @param    array     $data        POST data to send
	 * @param    int       $timeout     Request timeout in seconds
	 * @return   array     Decoded API response
	 * @throws   \Exception             On API errors or invalid responses
	 */
	public function post(string $function, array $data = array(), int $timeout = 15) {
		$url = EUROPARCEL_API_URL . $function;
		
		$params = array(
			'body' => $data,
			'headers' => $this->getHeader(),
			'timeout' => $timeout
		);
		
		$response = wp_remote_post($url, $params);
		return $this->handle_api_response($response);
	}

	/**
	 * Handle API response
	 *
	 * Processes the API response, handles errors, and decodes JSON.
	 * Throws exceptions for various error conditions.
	 *
	 * @since    1.0.0
	 * @param    array|WP_Error    $response    WordPress HTTP API response
	 * @return   array             Decoded JSON response
	 * @throws   \Exception        On various error conditions
	 */
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

	/**
	 * Get HTTP headers for API requests
	 *
	 * Retrieves the API key from settings and prepares
	 * the headers required for EuroParcel API authentication.
	 *
	 * @since    1.0.0
	 * @return   array    HTTP headers array with API key
	 */
	private function getHeader() {
		$settings = get_option('woocommerce_europarcel_shipping_' . $this->instance_id . '_settings');
		return [
			'accept' => 'application/json',
			'X-API-Key' => isset($settings['api_key']) ? $settings['api_key'] : ''
		];
	}
}
