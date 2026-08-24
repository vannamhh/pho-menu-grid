<?php
/**
 * Flatsome UX Builder element registration.
 *
 * @package PhoMenuGrid
 */

namespace PhoMenuGrid\UxBuilder;

defined( 'ABSPATH' ) || exit;

/**
 * Registers both shortcodes as UX Builder elements.
 */
class Registrar {

	/**
	 * Hook into Flatsome.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_action( 'ux_builder_setup', array( $this, 'register_elements' ) );
	}

	/**
	 * Register the elements.
	 *
	 * @return void
	 */
	public function register_elements(): void {
		if ( ! function_exists( 'add_ux_builder_shortcode' ) ) {
			return;
		}

		add_ux_builder_shortcode(
			'pho_menu_grid',
			array(
				'name'     => __( 'Pho Menu Grid', 'pho-menu-grid' ),
				'category' => __( 'Content', 'pho-menu-grid' ),
				'info'     => '{{ primary_color }}',
				'icon'     => 'dashicons-food',
				'priority' => 10,
				'options'  => array(
					'content_options'  => Options::content(
						__( 'Items per Tab', 'pho-menu-grid' )
					),
					'slider_options'   => array(
						'type'    => 'group',
						'heading' => __( 'Tab Settings', 'pho-menu-grid' ),
						'options' => array(
							'auto_play_tabs' => array(
								'type'        => 'slider',
								'heading'     => __( 'Auto Play Tabs (ms)', 'pho-menu-grid' ),
								'default'     => 3000,
								'max'         => 10000,
								'min'         => 0,
								'step'        => 500,
								'description' => __( '0 to disable auto play.', 'pho-menu-grid' ),
							),
						),
					),
					'colors_options'   => Options::colors(),
					'advanced_options' => Options::advanced(),
				),
			)
		);

		add_ux_builder_shortcode(
			'pho_menu_showcase',
			array(
				'name'     => __( 'Pho Menu Showcase', 'pho-menu-grid' ),
				'category' => __( 'Content', 'pho-menu-grid' ),
				'info'     => '{{ primary_color }}',
				'icon'     => 'dashicons-star-filled',
				'priority' => 10,
				'options'  => array(
					'content_options'  => Options::content(
						__( 'Items per Category', 'pho-menu-grid' ),
						array(
							'order_btn_text' => array(
								'type'    => 'textfield',
								'heading' => __( 'Order Button Text', 'pho-menu-grid' ),
								'default' => 'ORDER',
							),
						)
					),
					'colors_options'   => Options::colors(),
					'advanced_options' => Options::advanced(),
				),
			)
		);
	}
}
