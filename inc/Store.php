<?php

namespace FreemiusSSO;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class Store {
	/** @var Store */
	private static $INSTANCE;

	private function __construct() {

	}

	public static function instance() : Store {
		if ( ! isset( self::$INSTANCE ) ) {
			self::$INSTANCE = new self();
		}

		return self::$INSTANCE;
	}

	private function get_api_root() : string {
		return 'https://api.freemius.com/v1';
	}

	public function latest_plugin_release( $plugin_id, $bearer_token, $expiration = DAY_IN_SECONDS ) {
		$query    = array(
			'is_premium' => 'true',
			'type'       => 'released'
		);
		$endpoint = '/products/' . $plugin_id . '/tags/latest.json';

		return $this->_bearer_token( $endpoint, $bearer_token, $query, array(), 'GET', $expiration );
	}

	public function plans( $plugin_id, $bearer_token, $expiration = WEEK_IN_SECONDS ) {
		$endpoint = '/products/' . $plugin_id . '/plans.json';

		return $this->_bearer_token( $endpoint, $bearer_token, array(), array(), 'GET', $expiration );
	}

	public function plan_features( $plugin_id, $plan_id, $bearer_token, $expiration = WEEK_IN_SECONDS ) {
		$endpoint = '/products/' . $plugin_id . '/plans/' . $plan_id . '/features.json';

		return $this->_bearer_token( $endpoint, $bearer_token, array(), array(), 'GET', $expiration );
	}

	public function plan_pricing( $plugin_id, $plan_id, $bearer_token, $expiration = WEEK_IN_SECONDS ) {
		$endpoint = '/products/' . $plugin_id . '/plans/' . $plan_id . '/pricing.json';

		return $this->_bearer_token( $endpoint, $bearer_token, array(), array(), 'GET', $expiration );
	}

	public function create_user( $plugin_id, $bearer_token, $args = array() ) {
		$endpoint = '/products/' . $plugin_id . '/users.json';

		return $this->_bearer_token( $endpoint, $bearer_token, array(), $args, 'POST' );
	}

	public function create_license( $plugin_id, $plan_id, $pricing_id, $bearer_token, $args = array() ) {
		$endpoint = '/products/' . $plugin_id . '/plans/' . $plan_id . '/pricing/' . $pricing_id . '/licenses.json';

		return $this->_bearer_token( $endpoint, $bearer_token, array(), $args, 'POST' );
	}

	private function _bearer_token( $endpoint, $token, $query = array(), $payload = array(), $method = 'GET', $expiration = DAY_IN_SECONDS ) {
		$url = $this->get_api_root() . $endpoint . ( ! empty( $query ) ? '?' . http_build_query( $query ) : '' );
		$key = 'fs_store_' . md5( $url );
		$get = $method === 'GET' ? get_site_transient( $key ) : false;

		if ( $get === false || ! is_array( $get ) ) {
			$get  = array();
			$args = array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $token,
					'Content-Type'  => 'application/json',
				),
				'timeout' => 15,
				'method'  => $method,
			);

			if ( ! empty( $payload ) ) {
				$args['body'] = json_encode( $payload );
			}

			$response = wp_remote_request( $url, $args );

			if ( is_wp_error( $response ) ) {
				Log::instance()->add( $response->get_error_message(), 'ERROR' );
			} else {
				$body = wp_remote_retrieve_body( $response );
				$json = json_decode( $body, true );

				if ( is_array( $json ) && ! empty( $json ) ) {
					if ( $method === 'GET' ) {
						$json['_cached'] = time();
						$json['_expire'] = time() + $expiration;
						$json['_input']  = $url;
						$json['_query']  = $query;

						set_site_transient( $key, $json, $expiration );
					}

					return $json;
				} else {
					Log::instance()->add( json_last_error_msg(), 'ERROR' );
				}
			}
		}

		return $get;
	}
}
