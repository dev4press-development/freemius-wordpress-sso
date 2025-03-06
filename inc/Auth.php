<?php

namespace FreemiusSSO;

use WP_Error;
use WP_User;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class Auth {
	#region Properties

	/** @var Auth */
	private static $INSTANCE;

	#endregion

	private function __construct() {
		add_filter( 'authenticate', array( $this, 'authenticate' ), 30, 3 );
	}

	public static function instance() : Auth {
		if ( ! isset( self::$INSTANCE ) ) {
			self::$INSTANCE = new self();
		}

		return self::$INSTANCE;
	}

	/**
	 * This logic assumes that if a user exists in WP, there's a matching user (based on email) in Freemius.
	 *
	 * @param WP_User|null|WP_Error $user
	 * @param string                $username Username or email.
	 * @param string                $password Plain text password.
	 *
	 * @return WP_User|null|WP_Error
	 */
	public function authenticate( $user, $username, $password ) {
		$is_login_by_email = strpos( $username, '@' );
		$wp_user_found     = ( $user instanceof WP_User );

		/**
		 * If there's no matching user in WP and the login is by a username and not an email address, there's no way for us to fetch an access token for the user.
		 */
		if ( ! $wp_user_found &&
		     ! $is_login_by_email
		) {
			return $user;
		}

		if (
			is_wp_error( $user ) &&
			! in_array( $user->get_error_code(), array(
				'authentication_failed',
				'invalid_email',
				'invalid_password',
				'incorrect_password',
			) )
		) {
			return $user;
		}

		$email = $is_login_by_email
			?
			$username
			:
			$user->user_email;

		$fs_user_token = null;
		$fs_user_id    = null;

		$fetch_access_token = true;
		if ( $wp_user_found ) {
			$fs_user_id = get_user_meta( $user->ID, 'fs_user_id', true );

			if ( is_numeric( $fs_user_id ) ) {
				$fs_user_token = get_user_meta( $user->ID, 'fs_token', true );

				if ( ! empty( $fs_user_token ) ) {
					// Validate access token didn't yet to expire.
					if ( $fs_user_token->expires > time() ) {
						// No need to get a new access token for now, we can use the cached token.
						$fetch_access_token = false;
					}
				}
			}
		}

		if ( $fetch_access_token ) {
			// Fetch user's info and access token from Freemius.
			$result = API::instance()->fetch_user_access_token(
				$email,
				( $wp_user_found ? '' : $password )
			);

			if ( is_wp_error( $result ) ) {
				return $user;
			}

			$result = json_decode( $result['body'] );

			if ( isset( $result->error ) ) {
				if ( $wp_user_found ) {
					return $user;
				} else {
					return new WP_Error( $result->error->code, __( '<strong>ERROR</strong>: ' . $result->error->message ) );
				}
			}

			$fs_user       = $result->user_token->person;
			$fs_user_id    = $fs_user->id;
			$fs_user_token = $result->user_token->token;

			if ( ! $wp_user_found ) {
				// Check if there's a user with a matching email address.
				$user_by_email = get_user_by( 'email', $email );

				if ( is_object( $user_by_email ) ) {
					$user = $user_by_email;
				} else {
					/**
					 * No user in WP with a matching email address. Therefore, create the user.
					 */
					$username = strtolower( $fs_user->first . ( empty( $fs_user->last ) ? '' : '.' . $fs_user->last ) );

					if ( empty( $username ) ) {
						$username = substr( $fs_user->email, 0, strpos( $fs_user->email, '@' ) );
					}

					$username = $this->generate_unique_username( $username );

					$user_id = wp_create_user( $username, $password, $email );

					if ( is_wp_error( $user_id ) ) {
						return $user;
					}

					$user = get_user_by( 'ID', $user_id );

					do_action( 'fs_sso_after_user_creation', $user );
				}
			}

			/**
			 * Store the token and user ID locally.
			 */
			update_user_meta( $user->ID, 'fs_token', $fs_user_token );
			update_user_meta( $user->ID, 'fs_user_id', $fs_user_id );
		}

		if ( $user instanceof WP_User ) {
			$has_any_active_licenses = 'no';

			if ( Account::instance( $user->ID )->get_freemius_has_any_license() ) {
				$has_any_licenses = 'yes';
			} else {
				$has_any_licenses = 'no';

				$result = API::instance()->fetch_user_store_licenses( $fs_user_id, $fs_user_token->access );

				if ( ! is_wp_error( $result ) ) {
					$result = json_decode( $result['body'] );

					if (
						is_object( $result ) &&
						! isset( $result->error ) &&
						! empty( $result->licenses )
					) {
						$has_any_licenses = 'yes';

						/**
						 * Check if license is active to save an API call.
						 */
						$license = $result->licenses[0];
						if (
							false === $license->is_cancelled &&
							! API::instance()->has_license_expired( $license )
						) {
							$has_any_active_licenses = 'yes';
						}
					}
				}

				update_user_meta( $user->ID, 'fs_has_licenses', $has_any_licenses );
			}

			if ( 'yes' !== $has_any_active_licenses && 'yes' === $has_any_licenses ) {
				$result = API::instance()->fetch_user_store_licenses(
					$fs_user_id,
					$fs_user_token->access,
					'active'
				);

				if ( ! is_wp_error( $result ) ) {
					$result = json_decode( $result['body'] );

					if (
						is_object( $result ) &&
						! isset( $result->error ) &&
						! empty( $result->licenses )
					) {
						update_user_meta( $user->ID, 'fs_active_licenses', $result->licenses );

						$has_any_active_licenses = 'yes';
					}
				}
			}

			update_user_meta( $user->ID, 'fs_has_active_licenses', $has_any_active_licenses );
		}

		do_action( 'fs_sso_after_successful_login', $user );

		return $user;
	}

	/**
	 * @param string $base_username
	 *
	 * @return string
	 */
	private function generate_unique_username( $base_username ) {
		// Sanitize.
		$base_username = sanitize_user( $base_username );

		$numeric_suffix = 0;

		do {
			$username = ( 0 == $numeric_suffix )
				?
				$base_username
				:
				sprintf( '%s%s', $base_username, $numeric_suffix );

			$numeric_suffix ++;
		} while ( username_exists( $username ) );

		return $username;
	}
}
