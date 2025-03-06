<?php

namespace FreemiusSSO;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class Dashboard {
	#region Properties

	/** @var Dashboard */
	private static $INSTANCE;

	#endregion

	private function __construct() {
		add_action( 'init', array( $this, 'register_shortcodes' ) );
	}

	public static function instance() : Dashboard {
		if ( ! isset( self::$INSTANCE ) ) {
			self::$INSTANCE = new self();
		}

		return self::$INSTANCE;
	}

	private function get_dashboard_url() {
		return FREEMIUS_SSO_MEMBERS_DASHBOARD_DEBUG ? 'http://users.freemius-local.com:4200/dashboard.js' : 'https://users.freemius.com/dashboard.js';
	}

	public function register_shortcodes() {
		add_shortcode( 'fs_members', array( $this, 'dashboard_shortcode' ) );
	}

	public function dashboard_shortcode( $atts = array() ) {
		$store_id   = isset( $atts['store_id'] ) && is_numeric( $atts['store_id'] ) ? $atts['store_id'] : ( defined( 'FREEMIUS_SSO_STORE_ID' ) ? FREEMIUS_SSO_STORE_ID : null );
		$public_key = isset( $atts['public_key'] ) && is_string( $atts['public_key'] ) ? $atts['public_key'] : ( defined( 'FREEMIUS_SSO_PUBLIC_KEY' ) ? FREEMIUS_SSO_PUBLIC_KEY : null );

		if ( ! is_numeric( $store_id ) || empty( $public_key ) ) {
			return '<p style="font-weight: bold; color: red;">You have to specify the store_id and its public_key to embed the Freemius members dashboard securely to your site.</p>';
		}

		$product_id = isset( $atts['product_id'] ) && is_numeric( $atts['product_id'] ) ? $atts['product_id'] : null;

		$css = array(
			'position' => 'relative',
			'top'      => 'auto',
			'bottom'   => 'auto',
			'left'     => 'auto',
			'right'    => 'auto',
			'zIndex'   => '2',
		);

		foreach ( $css as $k => $v ) {
			if ( isset( $atts[ $k ] ) ) {
				$css[ $k ] = $atts[ $k ];
			}
		}

		// Fix props.
		$numeric_props = array( 'top' => true, 'bottom' => true, 'left' => true, 'right' => true );

		foreach ( $css as $k => $v ) {
			if ( ! isset( $numeric_props[ $k ] ) ) {
				continue;
			}

			if ( is_numeric( $v ) ) {
				$css[ $k ] = "{$v}px";
			}
		}

		$cache_killer = FREEMIUS_SSO_MEMBERS_DASHBOARD_DEBUG
			?
			// Clear cache every time on debug mode.
			date( 'Y-m-d H:i:s' )
			:
			// Clear cache on an hourly basis.
			date( 'Y-m-d H' );

		$user_id      = null;
		$access_token = null;

		if ( is_user_logged_in() && class_exists( 'FS_SSO' ) ) {
			$sso = Account::instance();

			$user_id = $sso->get_freemius_user_id();

			if ( is_numeric( $user_id ) ) {
				$access_token = $sso->get_freemius_access_token();

				$access_token = is_object( $access_token )
					?
					$access_token->access
					:
					null;
			}
		}

		$dashboard_params = array(
			'css'        => $css,
			'public_key' => $public_key,
		);

		if ( is_numeric( $store_id ) ) {
			$dashboard_params['store_id'] = $store_id;
		}

		if ( is_numeric( $product_id ) ) {
			$dashboard_params['product_id'] = $product_id;
		}

		if ( is_numeric( $user_id ) && ! empty( $access_token ) ) {
			$dashboard_params['user_id'] = $user_id;
			$dashboard_params['token']   = $access_token;
		}

		wp_enqueue_scripts( 'jquery' );

		return apply_filters( 'fs_members_dashboard', '
<script type="text/javascript" src="' . $this->get_dashboard_url() . '?ck=' . $cache_killer . '"></script>
<script id="fs_dashboard_anchor" type="text/javascript">
    (function(){
        FS.Members.configure(' . json_encode( $dashboard_params ) . ').open({
            afterLogout: function() {
                window.location.href = \'' . str_replace( '&amp;', '&', wp_logout_url() ) . '\';
            }
        });
    })();
</script>
' );
	}
}
