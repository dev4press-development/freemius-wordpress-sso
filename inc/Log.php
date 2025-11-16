<?php

namespace FreemiusSSO;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class Log {
	/** @var string */
	private $log_file;
	/** @var Log */
	private static $INSTANCE;

	private function __construct() {
		$this->log_file = wp_normalize_path( WP_CONTENT_DIR . '/freemius.log' );
	}

	public static function instance() : Log {
		if ( ! isset( self::$INSTANCE ) ) {
			self::$INSTANCE = new self();
		}

		return self::$INSTANCE;
	}

	function add( string $msg, string $type = 'INFO' ) {
		$handle = fopen( $this->log_file, 'a' );

		if ( $handle ) {
			fwrite( $handle, gmdate( 'Y-m-d H:i:s' ) . ' | ' . strtoupper( $type ) . ' | ' . $msg . PHP_EOL );
			fclose( $handle );
		}
	}
}
