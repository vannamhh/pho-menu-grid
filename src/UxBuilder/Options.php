<?php
/**
 * Reusable UX Builder option group factories.
 *
 * @package PhoMenuGrid
 */

namespace PhoMenuGrid\UxBuilder;

use PhoMenuGrid\PostType;

defined( 'ABSPATH' ) || exit;

/**
 * Builds the option groups shared by both builder elements.
 */
class Options {

	/**
	 * The category / items / default tab / icons group.
	 *
	 * @param string               $items_heading Heading for the item count slider.
	 * @param array<string, mixed> $extra         Additional options appended to the group.
	 * @return array<string, mixed>
	 */
	public static function content( string $items_heading, array $extra = array() ): array {
		return array(
			'type'    => 'group',
			'heading' => __( 'Content', 'pho-menu-grid' ),
			'options' => array_merge(
				array(
					'categories'         => array(
						'type'        => 'select',
						'heading'     => __( 'Select Categories (Tabs)', 'pho-menu-grid' ),
						'param_name'  => 'categories',
						'config'      => array(
							'multiple'    => true,
							'placeholder' => __( 'Select categories...', 'pho-menu-grid' ),
							'termSelect'  => array(
								'post_type'  => PostType::POST_TYPE,
								'taxonomies' => PostType::TAXONOMY,
							),
						),
						'description' => __( 'Leave empty to show every non-empty category.', 'pho-menu-grid' ),
					),
					'posts_per_category' => array(
						'type'    => 'slider',
						'heading' => $items_heading,
						'default' => 10,
						'max'     => 30,
						'min'     => 1,
						'step'    => 1,
					),
					'default_tab'        => array(
						'type'        => 'slider',
						'heading'     => __( 'Default Active Tab', 'pho-menu-grid' ),
						'default'     => 1,
						'max'         => 10,
						'min'         => 1,
						'step'        => 1,
						'description' => __( '1 = first tab, 2 = second tab, and so on.', 'pho-menu-grid' ),
					),
					'show_icon'          => array(
						'type'    => 'checkbox',
						'heading' => __( 'Show Tab Icons', 'pho-menu-grid' ),
						'default' => 'true',
					),
				),
				$extra
			),
		);
	}

	/**
	 * The colour group.
	 *
	 * @return array<string, mixed>
	 */
	public static function colors(): array {
		return array(
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
		);
	}

	/**
	 * The advanced (class / visibility) group.
	 *
	 * @return array<string, mixed>
	 */
	public static function advanced(): array {
		return array(
			'type'    => 'group',
			'heading' => __( 'Advanced', 'pho-menu-grid' ),
			'options' => array(
				'class'      => array(
					'type'    => 'textfield',
					'heading' => __( 'Class', 'pho-menu-grid' ),
					'default' => '',
				),
				'visibility' => array(
					'type'    => 'select',
					'heading' => __( 'Visibility', 'pho-menu-grid' ),
					'default' => '',
					'options' => array(
						''                => __( 'Visible', 'pho-menu-grid' ),
						'hide-for-small'  => __( 'Hide for Small', 'pho-menu-grid' ),
						'hide-for-medium' => __( 'Hide for Medium', 'pho-menu-grid' ),
						'hide-for-large'  => __( 'Hide for Large', 'pho-menu-grid' ),
						'show-for-small'  => __( 'Show for Small Only', 'pho-menu-grid' ),
						'show-for-medium' => __( 'Show for Medium Only', 'pho-menu-grid' ),
						'show-for-large'  => __( 'Show for Large Only', 'pho-menu-grid' ),
					),
				),
			),
		);
	}
}
