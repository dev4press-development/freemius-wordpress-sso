<?php

namespace FreemiusSSO;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class Plugin {
	#region Properties

	/** @var Plugin */
	private static $INSTANCE;

	#endregion

	private function __construct() {
	}

	public static function instance() : Plugin {
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
		API::instance()->init( $store_id, $developer_id, $developer_secret_key, $use_localhost_api );

		Auth::instance();
		Dashboard::instance();
	}
}
