<?php
/**
 * Plugin bootstrap and hook wiring.
 *
 * @package PhoMenuGrid
 */

namespace PhoMenuGrid;

use PhoMenuGrid\Shortcodes\MenuGrid;
use PhoMenuGrid\Shortcodes\MenuShowcase;
use PhoMenuGrid\UxBuilder\Registrar;

defined( 'ABSPATH' ) || exit;

/**
 * Wires every component of the plugin onto WordPress hooks.
 */
final class Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var Plugin|null
	 */
	private static $instance = null;

	/**
	 * Guards against booting twice.
	 *
	 * @var bool
	 */
	private $booted = false;

	/**
	 * Retrieve the singleton instance.
	 *
	 * @return Plugin
	 */
	public static function instance(): Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Private constructor — use {@see Plugin::instance()}.
	 */
	private function __construct() {}

	/**
	 * Register every hook the plugin needs.
	 *
	 * @return void
	 */
	public function boot(): void {
		if ( $this->booted ) {
			return;
		}

		$this->booted = true;

		add_action( 'init', array( $this, 'load_textdomain' ) );

		( new PostType() )->register_hooks();
		( new MetaBox() )->register_hooks();
		( new TermMeta() )->register_hooks();
		( new Assets() )->register_hooks();
		( new MenuGrid() )->register_hooks();
		( new MenuShowcase() )->register_hooks();
		( new Registrar() )->register_hooks();
	}

	/**
	 * Load translations for the plugin text domain.
	 *
	 * @return void
	 */
	public function load_textdomain(): void {
		load_plugin_textdomain(
			'pho-menu-grid',
			false,
			dirname( plugin_basename( PHO_MENU_GRID_FILE ) ) . '/languages'
		);
	}
}
