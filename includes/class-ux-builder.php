<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'ux_builder_setup', 'pho_menu_grid_ux_builder_component' );

function pho_menu_grid_ux_builder_component() {

	// Require Shortcode function only after Flatsome UX Builder is ready.
	add_ux_builder_shortcode( 'pho_menu_grid', array(
		'name'      => __( 'Pho Menu Grid', 'pho-menu-grid' ),
		'category'  => __( 'Content', 'pho-menu-grid' ),
		'info'      => '{{ primary_color }}',
		'icon'      => 'dashicons-food', // using dashicon as fallback UX Builder icon is tricky sometimes, so string is mostly okay.
		'priority'  => 10,
		'options'   => array(

			// Tab: Content Settings
			'content_options'  => array(
				'type'    => 'group',
				'heading' => __( 'Content', 'pho-menu-grid' ),
				'options' => array(
					'categories'         => array(
						'type'       => 'select',
						'heading'    => 'Select Categories (Tabs)',
						'param_name' => 'categories',
						'config'     => array(
							'multiple'    => true,
							'placeholder' => 'Select categories...',
							'termSelect'  => array(
								'post_type' => 'pho_menu_item',
								'taxonomies' => 'pho_menu_category',
							),
						),
						'description' => 'Select the Menu Categories to display as tabs.',
					),
					'posts_per_category' => array(
						'type'        => 'slider',
						'heading'     => __( 'Items per Tab', 'pho-menu-grid' ),
						'default'     => 10,
						'max'         => 30,
						'min'         => -1,
						'step'        => 1,
						'description' => '-1 to show all items.',
					),
					'default_tab'        => array(
						'type'        => 'slider',
						'heading'     => __( 'Default Active Tab', 'pho-menu-grid' ),
						'default'     => 1,
						'max'         => 10,
						'min'         => 1,
						'step'        => 1,
						'description' => 'Which tab should be open by default (1 = first tab, 2 = second...).',
					),
				),
			),

			// Tab: Slider & Scripts
			'slider_options'   => array(
				'type'    => 'group',
				'heading' => __( 'Slider Settings', 'pho-menu-grid' ),
				'options' => array(
					'auto_play_tabs' => array(
						'type'        => 'slider',
						'heading'     => __( 'Auto Play Tabs (ms)', 'pho-menu-grid' ),
						'default'     => 3000,
						'max'         => 10000,
						'min'         => 0,
						'step'        => 500,
						'description' => '0 to disable auto play.',
					),
				),
			),

			// Tab: Colors
			'colors_options'   => array(
				'type'    => 'group',
				'heading' => __( 'Style & Colors', 'pho-menu-grid' ),
				'options' => array(
					'primary_color' => array(
						'type'    => 'colorpicker',
						'heading' => __( 'Primary Color', 'pho-menu-grid' ),
						'default' => '#0e603b',
						'alpha'   => true,
					),
					'accent_color'  => array(
						'type'    => 'colorpicker',
						'heading' => __( 'Accent Color', 'pho-menu-grid' ),
						'default' => '#f39c12',
						'alpha'   => true,
					),
				),
			),

			// Tab: Advanced
			'advanced_options' => array(
				'type'    => 'group',
				'heading' => __( 'Advanced', 'pho-menu-grid' ),
				'options' => array(
					'class' => array(
						'type'    => 'textfield',
						'heading' => __( 'Class', 'pho-menu-grid' ),
						'default' => '',
					),
					'visibility' => array(
						'type'    => 'select',
						'heading' => __( 'Visibility', 'pho-menu-grid' ),
						'default' => '',
						'options' => array(
							''                => 'Visible',
							'hide-for-small'  => 'Hide for Small',
							'hide-for-medium' => 'Hide for Medium',
							'hide-for-large'  => 'Hide for Large',
							'show-for-small'  => 'Show for Small Only',
							'show-for-medium' => 'Show for Medium Only',
							'show-for-large'  => 'Show for Large Only',
						)
					),
				),
			),
		),
	) );
}

add_action( 'ux_builder_setup', 'pho_menu_showcase_ux_builder_component' );
function pho_menu_showcase_ux_builder_component() {
	add_ux_builder_shortcode( 'pho_menu_showcase', array(
		'name'      => __( 'Pho Menu Showcase', 'pho-menu-grid' ),
		'category'  => __( 'Content', 'pho-menu-grid' ),
		'info'      => '{{ primary_color }}',
		'icon'      => 'dashicons-star-filled',
		'priority'  => 10,
		'options'   => array(
			'content_options'  => array(
				'type'    => 'group',
				'heading' => __( 'Content', 'pho-menu-grid' ),
				'options' => array(
					'categories'         => array(
						'type'       => 'select',
						'heading'    => 'Select Categories (Tabs)',
						'param_name' => 'categories',
						'config'     => array(
							'multiple'    => true,
							'placeholder' => 'Select categories...',
							'termSelect'  => array(
								'post_type' => 'pho_menu_item',
								'taxonomies' => 'pho_menu_category',
							),
						),
						'description' => 'Select the Menu Categories to display.',
					),
					'posts_per_category' => array(
						'type'        => 'slider',
						'heading'     => __( 'Items per Category', 'pho-menu-grid' ),
						'default'     => 10,
						'max'         => 30,
						'min'         => -1,
						'step'        => 1,
						'description' => '-1 to show all items.',
					),
					'default_tab'        => array(
						'type'        => 'slider',
						'heading'     => __( 'Default Active Tab', 'pho-menu-grid' ),
						'default'     => 1,
						'max'         => 10,
						'min'         => 1,
						'step'        => 1,
					),
					'order_btn_text' => array(
						'type'    => 'textfield',
						'heading' => __( 'Order Button Text', 'pho-menu-grid' ),
						'default' => 'ORDER',
					),
				),
			),
			'colors_options'   => array(
				'type'    => 'group',
				'heading' => __( 'Style & Colors', 'pho-menu-grid' ),
				'options' => array(
					'primary_color' => array(
						'type'    => 'colorpicker',
						'heading' => __( 'Primary Color', 'pho-menu-grid' ),
						'default' => '#0e603b',
						'alpha'   => true,
					),
					'accent_color'  => array(
						'type'    => 'colorpicker',
						'heading' => __( 'Accent Color', 'pho-menu-grid' ),
						'default' => '#f39c12',
						'alpha'   => true,
					),
				),
			),

			// Tab: Advanced
			'advanced_options' => array(
				'type'    => 'group',
				'heading' => __( 'Advanced', 'pho-menu-grid' ),
				'options' => array(
					'class' => array(
						'type'    => 'textfield',
						'heading' => __( 'Class', 'pho-menu-grid' ),
						'default' => '',
					),
					'visibility' => array(
						'type'    => 'select',
						'heading' => __( 'Visibility', 'pho-menu-grid' ),
						'default' => '',
						'options' => array(
							''                => 'Visible',
							'hide-for-small'  => 'Hide for Small',
							'hide-for-medium' => 'Hide for Medium',
							'hide-for-large'  => 'Hide for Large',
							'show-for-small'  => 'Show for Small Only',
							'show-for-medium' => 'Show for Medium Only',
							'show-for-large'  => 'Show for Large Only',
						)
					),
				),
			),
		),
	) );
}
