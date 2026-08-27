<?php
/**
 * Plugin Name: Pho Menu Grid
 * Plugin URI:  https://ztavi.com/
 * Description: Custom Flatsome UX Builder elements for displaying Pho Menu Tabs, Grid and Showcase (CPT based).
 * Version:     3.0.1
 * Author:      VN
 * License:     GPL-2.0-or-later
 * Text Domain: pho-menu-grid
 * Requires PHP: 7.4
 *
 * @package PhoMenuGrid
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

define( 'PHO_MENU_GRID_VERSION', '3.0.0' );
define( 'PHO_MENU_GRID_FILE', __FILE__ );
define( 'PHO_MENU_GRID_URL', plugin_dir_url( __FILE__ ) );
define( 'PHO_MENU_GRID_PATH', plugin_dir_path( __FILE__ ) );

/**
 * PSR-4 style autoloader for the PhoMenuGrid namespace.
 *
 * @param string $class_name Fully qualified class name.
 * @return void
 */
spl_autoload_register(
	static function ( $class_name ) {
		$prefix = 'PhoMenuGrid\\';

		if ( 0 !== strpos( $class_name, $prefix ) ) {
			return;
		}

		$relative = substr( $class_name, strlen( $prefix ) );
		$path     = PHO_MENU_GRID_PATH . 'src/' . str_replace( '\\', '/', $relative ) . '.php';

		if ( is_readable( $path ) ) {
			require_once $path;
		}
	}
);

PhoMenuGrid\Plugin::instance()->boot();
