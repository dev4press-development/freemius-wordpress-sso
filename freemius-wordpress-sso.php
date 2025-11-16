<?php
/**
 * Plugin Name: Freemius SSO (Single Sign-On)
 * Plugin URI:  https://freemius.com/
 * Description: SSO for Freemius powered shops, with Dashboard integration.
 * Version:     2.1.0
 * Author:      Freemius,Milan Petrovic
 * Author URI:  https://freemius.com
 * License:     MIT
 */

use FreemiusSSO\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

#region Autoloader
spl_autoload_register( function( $pClass ) {
	$prefix   = 'FreemiusSSO\\';
	$base_dir = __DIR__ . '/inc/';

	$len = strlen( $prefix );
	if ( strncmp( $prefix, $pClass, $len ) !== 0 ) {
		return;
	}

	$relative_class = substr( $pClass, $len );

	$file = wp_normalize_path( $base_dir . $relative_class . '.php' );

	if ( file_exists( $file ) ) {
		require $file;
	}
} );

#endregion

const FREEMIUS_SSO_PLUGIN_VERSION = '2.1.0';

if ( ! defined( 'FREEMIUS_SSO_MEMBERS_DASHBOARD_DEBUG' ) ) {
	define( 'FREEMIUS_SSO_MEMBERS_DASHBOARD_DEBUG', false );
}

if (
	defined( 'FREEMIUS_SSO_STORE_ID' ) &&
	defined( 'FREEMIUS_SSO_DEVELOPER_ID' ) &&
	defined( 'FREEMIUS_SSO_DEVELOPER_SECRET_KEY' )
) {
	Core::instance()->init(
		FREEMIUS_SSO_STORE_ID,
		FREEMIUS_SSO_DEVELOPER_ID,
		FREEMIUS_SSO_DEVELOPER_SECRET_KEY
	);
}
