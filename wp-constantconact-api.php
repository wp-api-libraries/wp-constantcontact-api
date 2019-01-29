<?php
/**
 * WP-ConstantContact-API (https://developer.constantcontact.com/docs/developer-guides/api-documentation-index.html)
 *
 * @package WP-ConstantContactAPI
 */

/*
* Plugin Name: WP Constant Contact API
* Plugin URI: https://github.com/wp-api-libraries/wp-connectwise-api
* Description: Perform API requests to Constant Contact in WordPress.
* Author: WP API Libraries
* Version: 1.0.0
* Author URI: https://wp-api-libraries.com
* GitHub Plugin URI: https://github.com/wp-api-libraries/wp-constantcontact-api
* GitHub Branch: master
*/

/* Exit if accessed directly. */
defined( 'ABSPATH' ) || exit;


/* Check if class exists. */
if ( ! class_exists( 'ConstantContactAPI' ) ) {

	/**
	 * ConstantContactAPI
	 */
	class ConstantContactAPI {
		
		/**
		 * Key.
		 *
		 * @var string
		 */
		static private $key;
		
		/**
		 * Key.
		 *
		 * @var string
		 */
		static private $secret;
		
		/**
		 * BaseAPI Endpoint
		 *
		 * @var string
		 * @access protected
		 */
		protected $base_uri;
		
		
		/**
		 * __construct function.
		 * 
		 * @access public
		 * @param mixed $key
		 * @param mixed $secret
		 * @return void
		 */
		function __construct( $key, $secret ) {
			
			static::$key = $key;
			static::$secret = $secret;
			$this->base_uri = 'https://api.constantcontact.com/v2';
			
		}
		
		
		/**
		 * Prepares API request.
		 *
		 * @param  string $route   API route to make the call to.
		 * @param  array  $args    Arguments to pass into the API call.
		 * @param  array  $method  HTTP Method to use for request.
		 * @return self            Returns an instance of itself so it can be chained to the fetch method.
		 */
		protected function build_request( $route, $args = array(), $method = 'GET' ) {
			// Headers get added first.
			$this->set_headers();

			// Add Method and Route.
			$this->args['method'] = $method;
			$this->route          = $route;

			// Generate query string for GET requests.
			if ( 'GET' === $method ) {
				$this->route = add_query_arg( array_filter( $args ), $route );
			}
			// Add to body for all other requests. (Json encode if content-type is json).
			elseif ( 'application/json' === $this->args['headers']['Content-Type'] ) {
				$this->args['body'] = wp_json_encode( $args );
			} else {
				$this->args['body'] = $args;
			}

			return $this;
		}


		/**
		 * Fetch the request from the API.
		 *
		 * @access private
		 * @return array|WP_Error Request results or WP_Error on request failure.
		 */
		protected function fetch() {
			// Make the request.
			// pp( $this->base_uri . $this->route, $this->args );
			$response = wp_remote_request( $this->base_uri . $this->route, $this->args );
			// pp( $this->base_uri . $this->route, $response );
			// Retrieve Status code & body.
			$code = wp_remote_retrieve_response_code( $response );
			$body = json_decode( wp_remote_retrieve_body( $response ) );

			$this->set_links( $response );

			$this->clear();
			// Return WP_Error if request is not successful.
			if ( ! $this->is_status_ok( $code ) ) {
				return new WP_Error( 'response-error', sprintf( __( 'Status: %d', 'wp-postmark-api' ), $code ), $body );
			}

			return $body;
		}

		/**
		 * set_links function.
		 *
		 * @access protected
		 * @param mixed $response
		 * @return void
		 */
		protected function set_links( $response ) {
			$this->links = array();

			// Get links from response header.
			$links = wp_remote_retrieve_header( $response, 'link' );

			// Parse the string into a convenient array.
			$links = explode( ',', $links );
			if ( ! empty( $links ) ) {
				foreach ( $links as $link ) {
					$tmp = explode( ';', $link );
					$res = preg_match( '~<(.*?)>~', $tmp[0], $match );
					if ( ! empty( $res ) ) {
						// Some string magic to set array key. Changes 'rel="next"' => 'next'.
						$key                 = str_replace( array( 'rel=', '"' ), '', trim( $tmp[1] ) );
						$this->links[ $key ] = $match[1];
					}
				}
			}
		}

		/**
		 * Set request headers.
		 */
		protected function set_headers() {
			// Set request headers.
			$this->args['headers'] = array(
				'Content-Type'  => 'application/json',
				'Authorization' => 'Basic ' . base64_encode( static::$company_id . '+' . static::$public_key . ':' . static::$private_key ),
			);
		}

		/**
		 * Clear query data.
		 */
		protected function clear() {
			$this->args = array();
		}

		/**
		 * Check if HTTP status code is a success.
		 *
		 * @param  int $code HTTP status code.
		 * @return boolean       True if status is within valid range.
		 */
		protected function is_status_ok( $code ) {
			return ( 200 <= $code && 300 > $code );
		}


		// ACCOUNT INFO.
		
		/**
		 * get_account_info function.
		 * 
		 * @access public
		 * @return void
		 */
		public function get_account_info() {
			return $this->build_request( "/account/info" )->fetch();
		}
		
		
	}
	
}