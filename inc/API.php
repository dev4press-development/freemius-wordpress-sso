<?php

namespace FreemiusSSO;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class API {
	#region Properties

	/** @var number */
	private $_store_id;
	/** @var number */
	private $_developer_id;
	/** @var string */
	private $_developer_secret_key;
	/** @var bool */
	private $_use_localhost_api;
	/** @var Plugin */
	private static $INSTANCE;

	#endregion

	private function __construct() {

	}

	public static function instance() : API {
		if ( ! isset( self::$INSTANCE ) ) {
			self::$INSTANCE = new self();
		}

		return self::$INSTANCE;
	}

	/**
	 * @param number $store_id
	 * @param number $developer_id
	 * @param string $developer_secret_key
	 * @param bool   $use_localhost_api
	 */
	public function init(
		$store_id,
		$developer_id,
		$developer_secret_key,
		$use_localhost_api = false
	) {
		$this->_store_id             = $store_id;
		$this->_developer_id         = $developer_id;
		$this->_developer_secret_key = $developer_secret_key;
		$this->_use_localhost_api    = $use_localhost_api;
	}

	/**
	 * @return string
	 */
	private function get_api_root() {
		return $this->_use_localhost_api ? 'http://api.freemius-local.com:8080' : 'https://fast-api.freemius.com';
	}

	/**
	 * @param string $email
	 * @param string $password
	 *
	 * @return array|\WP_Error
	 */
	public function fetch_user_access_token( $email, $password = '' ) {
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

	/**
	 * @param number $fs_user_id
	 * @param string $access_token
	 * @param string $type
	 * @param int    $count
	 *
	 * @return array|\WP_Error
	 */
	public function fetch_user_store_licenses( $fs_user_id, $access_token, $type = 'all', $count = 1 ) {
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

	/**
	 * @param object $license
	 *
	 * @return bool
	 */
	public function has_license_expired( $license ) {
		if ( is_null( $license->expiration ) ) {
			// Lifetime license.
			return false;
		}

		return ( time() >= $this->get_timestamp_from_datetime( $license->expiration ) );
	}

	/**
	 * @param string $datetime
	 *
	 * @return int
	 */
	private function get_timestamp_from_datetime( $datetime ) {
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
