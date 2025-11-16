<?php

namespace FreemiusSSO;

use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class API {
	#region Properties

	private int $_store_id;
	private int $_developer_id;
	private string $_developer_secret_key;
	private bool $_use_localhost_api;

	private static self $INSTANCE;

	#endregion

	private function __construct() {
	}

	public static function instance() : API {
		if ( ! isset( self::$INSTANCE ) ) {
			self::$INSTANCE = new self();
		}

		return self::$INSTANCE;
	}

	public function init( int $store_id, int $developer_id, string $developer_secret_key, bool $use_localhost_api = false ) : void {
		$this->_store_id             = $store_id;
		$this->_developer_id         = $developer_id;
		$this->_developer_secret_key = $developer_secret_key;
		$this->_use_localhost_api    = $use_localhost_api;
	}

	private function get_api_root() : string {
		return $this->_use_localhost_api ? 'http://api.freemius-local.com:8080' : 'https://fast-api.freemius.com';
	}

	public function fetch_user_access_token( string $email, string $password = '' ) : WP_Error|array {
		$api_root = $this->get_api_root();

		// Fetch user's info and access token from Freemius.
		return wp_remote_post(
			"{$api_root}/v1/users/login.json",
			array(
				'method'   => 'POST',
				'blocking' => true,
				'body'     => array(
					'email'                => $email,
					'password'             => $password,
					'store_id'             => $this->_store_id,
					'developer_id'         => $this->_developer_id,
					'developer_secret_key' => $this->_developer_secret_key,
				)
			)
		);
	}

	public function fetch_user_store_licenses( int $fs_user_id, string $access_token, string $type = 'all', int $count = PHP_INT_MAX ) : WP_Error|array {
		$api_root = $this->get_api_root();

		// Fetch user's info and access token from Freemius.
		return wp_remote_post(
			"{$api_root}/v1/users/{$fs_user_id}/licenses.json?" . http_build_query( array(
				'count'         => $count,
				'store_id'      => $this->_store_id,
				'type'          => $type,
				'authorization' => "FSA {$fs_user_id}:$access_token",
			), '', '&', PHP_QUERY_RFC3986 ),
			array(
				'method'   => 'GET',
				'blocking' => true,
			)
		);
	}

	public function has_license_expired( $license ) : bool {
		if ( is_null( $license->expiration ) ) {
			// Lifetime license.
			return false;
		}

		return ( time() >= $this->get_timestamp_from_datetime( $license->expiration ) );
	}

	private function get_timestamp_from_datetime( string $datetime ) : int {
		$timezone = date_default_timezone_get();

		if ( 'UTC' !== $timezone ) {
			// Temporary change time zone.
			date_default_timezone_set( 'UTC' );
		}

		$datetime  = is_numeric( $datetime ) ? $datetime : strtotime( $datetime );
		$timestamp = date( 'Y-m-d H:i:s', $datetime );

		if ( 'UTC' !== $timezone ) {
			// Revert timezone.
			date_default_timezone_set( $timezone );
		}

		return $timestamp;
	}
}
