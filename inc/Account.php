<?php

namespace FreemiusSSO;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class Account {
	#region Properties

	/** @var int */
	private $ID;
	/** @var Account[] */
	private static $INSTANCE = array();

	#endregion

	private function __construct( $user_id ) {
		$this->ID = $user_id;
	}

	public static function instance( $user_id = null ) : Account {
		if ( is_null( $user_id ) ) {
			$user_id = get_current_user_id();
		}

		if ( ! isset( self::$INSTANCE[ $user_id ] ) ) {
			self::$INSTANCE[ $user_id ] = new self( $user_id );
		}

		return self::$INSTANCE[ $user_id ];
	}

	/**
	 * Clean up the stored user access token.
	 */
	public function clear_access_token() {
		delete_user_meta( $this->ID, 'fs_token' );
	}

	/**
	 * Get user's Freemius user ID from meta-entry.
	 *
	 * @return number
	 */
	public function get_freemius_user_id() {
		return get_user_meta( $this->ID, 'fs_user_id', true );
	}

	/**
	 * Get user's Freemius access token from meta-entry.
	 *
	 * @return object
	 */
	public function get_freemius_access_token() {
		return get_user_meta( $this->ID, 'fs_token', true );
	}

	/**
	 * Check if the user has any licenses, reading value from the meta-entry.
	 *
	 * @return bool
	 */
	public function get_freemius_has_any_license() {
		return 'yes' === get_user_meta( $this->ID, 'fs_has_licenses', true );
	}

	/**
	 * Check if the user has active licenses, reading value from the meta-entry.
	 *
	 * @return bool
	 */
	public function get_freemius_has_any_active_license() {
		return 'yes' === get_user_meta( $this->ID, 'fs_has_active_licenses', true );
	}

	/**
	 * Get user active licenses, reading value from the meta-entry.
	 *
	 * @return array
	 */
	public function get_freemius_active_licenses() {
		$licenses = get_user_meta( $this->ID, 'fs_active_licenses', true );

		if ( is_array( $licenses ) ) {
			return $licenses;
		}

		return null;
	}

	public function refresh_user_access_token( $force = false ) {
		$fetch_access_token = false;

		if ( $this->ID > 0 ) {
			$fetch_access_token = true;

			if ( ! $force ) {
				$fs_user_token = get_user_meta( $this->ID, 'fs_token', true );

				if ( ! empty( $fs_user_token ) ) {
					if ( $fs_user_token->expires > time() ) {
						$fetch_access_token = false;
					}
				}
			}

			if ( $fetch_access_token ) {
				$user = get_userdata( $this->ID );

				if ( $user ) {
					$result = API::instance()->fetch_user_access_token( $user->user_email );

					if ( ! is_wp_error( $result ) ) {
						$result = json_decode( $result['body'] );

						if ( isset( $result->error ) && $result->error->http == 401 ) {
							update_user_meta( $this->ID, 'fs_error', $result->error );
							update_user_meta( $this->ID, 'fs_token', (object) array(
								'access'  => '',
								'refresh' => '',
								'expires' => time() + DAY_IN_SECONDS,
							) );
						} else {
							if ( isset( $result->user_token ) ) {
								$fs_user       = $result->user_token->person;
								$fs_user_id    = $fs_user->id;
								$fs_user_token = $result->user_token->token;

								delete_user_meta( $this->ID, 'fs_error' );
								update_user_meta( $this->ID, 'fs_token', $fs_user_token );
								update_user_meta( $this->ID, 'fs_user_id', $fs_user_id );
							}
						}
					}
				}
			}
		}

		return $fetch_access_token;
	}

	public function refresh_any_user_licenses() {
		$has_any_licenses = 'no';

		$fs_user_id    = $this->get_freemius_user_id();
		$fs_user_token = $this->get_freemius_access_token();

		$result = API::instance()->fetch_user_store_licenses( $fs_user_id, $fs_user_token->access );

		if ( ! is_wp_error( $result ) ) {
			$result = json_decode( $result['body'] );

			if ( is_object( $result ) && ! isset( $result->error ) && ! empty( $result->licenses ) ) {
				$has_any_licenses = 'yes';
			}
		}

		update_user_meta( $this->ID, 'fs_has_licenses', $has_any_licenses );
	}

	public function refresh_active_user_licenses() {
		$fs_user_id    = $this->get_freemius_user_id();
		$fs_user_token = $this->get_freemius_access_token();

		$result = API::instance()->fetch_user_store_licenses( $fs_user_id, $fs_user_token->access, 'active', PHP_INT_MAX );

		if ( ! is_wp_error( $result ) ) {
			$result = json_decode( $result['body'] );

			if ( is_object( $result ) && ! isset( $result->error ) && is_array( $result->licenses ) && ! empty( $result->licenses ) ) {
				update_user_meta( $this->ID, 'fs_active_licenses', $result->licenses );
			}
		}
	}
}
