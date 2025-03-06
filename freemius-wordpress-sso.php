<?php
/**
 * Plugin Name: Freemius SSO (Single Sign-On)
 * Plugin URI:  https://freemius.com/
 * Description: SSO for Freemius powered shops, with Dashboard integration.
 * Version:     2.0.0
 * Author:      Freemius,Milan Petrovic
 * Author URI:  https://freemius.com
 * License:     MIT
 */

use FreemiusSSO\Plugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

#region Autoloader
spl_autoload_register( function( $pClass ) {

	// Project-specific namespace prefix.
	$prefix = 'FreemiusSSO\\';

	// Base directory for the namespace prefix.
	$base_dir = __DIR__ . '/inc/';

	// Does the class use the namespace prefix?
	$len = strlen( $prefix );
	if ( strncmp( $prefix, $pClass, $len ) !== 0 ) {
		// No, move to the next registered autoloader.
		return;
	}

	// Get the relative class name.
	$relative_class = substr( $pClass, $len );

	/**
	 * Replace the namespace prefix with the base directory, replace namespace
	 * separators with directory separators in the relative class name, append
	 * with .php.
	 */
	$file = $base_dir . str_replace( '\\', '/', $relative_class ) . '.php';

	// If the file exists, require it.
	if ( file_exists( $file ) ) {
		require $file;
	}
} );

#endregion

const FREEMIUS_SSO_PLUGIN_VERSION = '2.0.0';

if ( ! defined( 'FREEMIUS_SSO_MEMBERS_DASHBOARD_DEBUG' ) ) {
	define( 'FREEMIUS_SSO_MEMBERS_DASHBOARD_DEBUG', false );
}

if (defined('FREEMIUS_SSO_STORE_ID') && defined('FREEMIUS_SSO_DEVELOPER_ID') && defined('FREEMIUS_SSO_DEVELOPER_SECRET_KEY') ) {
	Plugin::instance()->init(
		FREEMIUS_SSO_STORE_ID,
		FREEMIUS_SSO_DEVELOPER_ID,
		FREEMIUS_SSO_DEVELOPER_SECRET_KEY
	);
}
