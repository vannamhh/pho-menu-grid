<?php
/**
 * Plugin Name: Pho Menu Grid
 * Plugin URI:  https://example.com/
 * Description: Custom Flatsome UX Builder element for displaying Pho Menu Tabs and Grid (CPT based).
 * Version:     1.0.0
 * Author:      Expert AI
 * Text Domain: pho-menu-grid
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

define( 'PHO_MENU_GRID_URL', plugin_dir_url( __FILE__ ) );
define( 'PHO_MENU_GRID_PATH', plugin_dir_path( __FILE__ ) );

/**
 * 1. Admin Settings Page for Global GSAP/Flickity toggles
 */
add_action( 'admin_menu', 'pho_menu_grid_add_admin_menu' );
add_action( 'admin_init', 'pho_menu_grid_settings_init' );

function pho_menu_grid_add_admin_menu() {
	add_options_page(
		'Pho Menu Grid Settings',
		'Pho Menu Grid',
		'manage_options',
		'pho_menu_grid',
		'pho_menu_grid_options_page'
	);
}

function pho_menu_grid_settings_init() {
	register_setting( 'pho_menu_grid_plugin_page', 'pho_menu_grid_settings' );

	add_settings_section(
		'pho_menu_grid_plugin_setting_section',
		__( 'Script Settings', 'pho-menu-grid' ),
		'pho_menu_grid_setting_section_callback',
		'pho_menu_grid_plugin_page'
	);

	add_settings_field(
		'load_gsap',
		__( 'Load GSAP from CDN', 'pho-menu-grid' ),
		'pho_menu_grid_load_gsap_render',
		'pho_menu_grid_plugin_page',
		'pho_menu_grid_plugin_setting_section'
	);

	add_settings_field(
		'load_flickity',
		__( 'Load Flickity from CDN', 'pho-menu-grid' ),
		'pho_menu_grid_load_flickity_render',
		'pho_menu_grid_plugin_page',
		'pho_menu_grid_plugin_setting_section'
	);
}

function pho_menu_grid_load_gsap_render() {
	$options = get_option( 'pho_menu_grid_settings' );
	$checked = isset( $options['load_gsap'] ) ? $options['load_gsap'] : 0;
	?>
	<input type='checkbox' name='pho_menu_grid_settings[load_gsap]' <?php checked( $checked, 1 ); ?> value='1'>
	<p class="description">Check this box to load the original GSAP library (Wait till you check if Flatsome has your desired GSAP version). Default: OFF.</p>
	<?php
}

function pho_menu_grid_load_flickity_render() {
	$options = get_option( 'pho_menu_grid_settings' );
	$checked = isset( $options['load_flickity'] ) ? $options['load_flickity'] : 0;
	?>
	<input type='checkbox' name='pho_menu_grid_settings[load_flickity]' <?php checked( $checked, 1 ); ?> value='1'>
	<p class="description">Check this box to load Flickity from CDN (Not recommended if Flatsome already provides Flickity). Default: OFF.</p>
	<?php
}

function pho_menu_grid_setting_section_callback() {
	echo __( 'Configure whether to load external vendor scripts for the Menu Grid.', 'pho-menu-grid' );
}

function pho_menu_grid_options_page() {
	?>
	<div class="wrap">
		<h2>Pho Menu Grid Settings</h2>
		<form action="options.php" method="post">
			<?php
			settings_fields( 'pho_menu_grid_plugin_page' );
			do_settings_sections( 'pho_menu_grid_plugin_page' );
			submit_button();
			?>
		</form>
	</div>
	<?php
}

/**
 * Enqueue scripts conditionally based on options.
 * Normally, you only enqueue on pages that use the shortcode.
 * For simplicity, we register them here and use them inside the shortcode.
 */
add_action( 'wp_enqueue_scripts', 'pho_menu_grid_register_scripts' );
function pho_menu_grid_register_scripts() {
	$options = get_option( 'pho_menu_grid_settings' );
	$load_gsap = isset( $options['load_gsap'] ) ? $options['load_gsap'] : 0;
	$load_flickity = isset( $options['load_flickity'] ) ? $options['load_flickity'] : 0;

	if ( $load_gsap ) {
		wp_register_script( 'pho-gsap', 'https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js', array(), '3.12.2', true );
	}

	if ( $load_flickity ) {
		wp_register_style( 'pho-flickity', 'https://unpkg.com/flickity@2/dist/flickity.min.css', array(), '2.0', 'all' );
		wp_register_script( 'pho-flickity', 'https://unpkg.com/flickity@2/dist/flickity.pkgd.min.js', array(), '2.0', true );
	}
}

/**
 * Require other components.
 */
require_once PHO_MENU_GRID_PATH . 'includes/class-cpt-taxonomy.php';
require_once PHO_MENU_GRID_PATH . 'includes/class-ux-builder.php';
require_once PHO_MENU_GRID_PATH . 'includes/shortcode-menu-grid.php';
