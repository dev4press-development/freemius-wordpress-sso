<?php

namespace FreemiusSSO;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class Plugin {
	private int $ID;
	private string $bearer_token;

	/** @var Plugin[] */
	private static array $INSTANCE = array();

	private function __construct( int $id, string $bearer_token ) {
		$this->ID           = $id;
		$this->bearer_token = $bearer_token;
	}

	public static function instance( int $id, string $bearer_token ) : Plugin {
		if ( ! isset( self::$INSTANCE[ $id ] ) ) {
			self::$INSTANCE[ $id ] = new self( $id, $bearer_token );
		}

		return self::$INSTANCE[ $id ];
	}

	public function latest_release() {
		return Store::instance()->latest_plugin_release( $this->ID, $this->bearer_token );
	}

	public function plans() {
		return Store::instance()->plans( $this->ID, $this->bearer_token );
	}

	public function plan_pricing_id( $plan_id, $licenses ) {
		$plans = Store::instance()->plan_pricing( $this->ID, $plan_id, $this->bearer_token );

		foreach ( $plans['pricing'] as $pricing ) {
			if ( ( $licenses == 0 && is_null( $pricing['licenses'] ) ) || ( $licenses > 0 && $pricing['licenses'] == $licenses ) ) {
				return $pricing['id'];
			}
		}

		return 0;
	}
}
