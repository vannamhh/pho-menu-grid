<?php
/**
 * Custom post type and taxonomy registration.
 *
 * @package PhoMenuGrid
 */

namespace PhoMenuGrid;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the `pho_menu_item` post type and `pho_menu_category` taxonomy.
 */
class PostType {

	/**
	 * Post type name.
	 */
	public const POST_TYPE = 'pho_menu_item';

	/**
	 * Taxonomy name.
	 */
	public const TAXONOMY = 'pho_menu_category';

	/**
	 * Hook into WordPress.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_action( 'init', array( $this, 'register_post_type' ) );
		add_action( 'init', array( $this, 'register_taxonomy' ) );
	}

	/**
	 * Register the menu item post type.
	 *
	 * Items are never browsed on their own URL — they are only ever rendered
	 * inside a shortcode — so the type stays entirely non-public.
	 *
	 * @return void
	 */
	public function register_post_type(): void {
		$labels = array(
			'name'               => _x( 'Menu Items', 'Post type general name', 'pho-menu-grid' ),
			'singular_name'      => _x( 'Menu Item', 'Post type singular name', 'pho-menu-grid' ),
			'menu_name'          => _x( 'Menu Items', 'Admin Menu text', 'pho-menu-grid' ),
			'name_admin_bar'     => _x( 'Menu Item', 'Add New on Toolbar', 'pho-menu-grid' ),
			'add_new'            => __( 'Add New', 'pho-menu-grid' ),
			'add_new_item'       => __( 'Add New Menu Item', 'pho-menu-grid' ),
			'new_item'           => __( 'New Menu Item', 'pho-menu-grid' ),
			'edit_item'          => __( 'Edit Menu Item', 'pho-menu-grid' ),
			'view_item'          => __( 'View Menu Item', 'pho-menu-grid' ),
			'all_items'          => __( 'All Menu Items', 'pho-menu-grid' ),
			'search_items'       => __( 'Search Menu Items', 'pho-menu-grid' ),
			'not_found'          => __( 'No menu items found.', 'pho-menu-grid' ),
			'not_found_in_trash' => __( 'No menu items found in Trash.', 'pho-menu-grid' ),
		);

		register_post_type(
			self::POST_TYPE,
			array(
				'labels'             => $labels,
				'public'             => false,
				'publicly_queryable' => false,
				'show_ui'            => true,
				'show_in_menu'       => true,
				'query_var'          => false,
				'rewrite'            => false,
				'capability_type'    => 'post',
				'has_archive'        => false,
				'hierarchical'       => false,
				'menu_position'      => null,
				'menu_icon'          => 'dashicons-food',
				'supports'           => array( 'title', 'thumbnail' ),
			)
		);
	}

	/**
	 * Register the menu category taxonomy.
	 *
	 * Attached to a non-public post type, so rewrite rules and the public query
	 * var are disabled — they only ever produced 404s while still costing a
	 * rewrite rule lookup on every request.
	 *
	 * @return void
	 */
	public function register_taxonomy(): void {
		$labels = array(
			'name'              => _x( 'Menu Categories', 'taxonomy general name', 'pho-menu-grid' ),
			'singular_name'     => _x( 'Menu Category', 'taxonomy singular name', 'pho-menu-grid' ),
			'search_items'      => __( 'Search Menu Categories', 'pho-menu-grid' ),
			'all_items'         => __( 'All Menu Categories', 'pho-menu-grid' ),
			'parent_item'       => __( 'Parent Menu Category', 'pho-menu-grid' ),
			'parent_item_colon' => __( 'Parent Menu Category:', 'pho-menu-grid' ),
			'edit_item'         => __( 'Edit Menu Category', 'pho-menu-grid' ),
			'update_item'       => __( 'Update Menu Category', 'pho-menu-grid' ),
			'add_new_item'      => __( 'Add New Menu Category', 'pho-menu-grid' ),
			'new_item_name'     => __( 'New Menu Category Name', 'pho-menu-grid' ),
			'menu_name'         => __( 'Menu Category', 'pho-menu-grid' ),
		);

		register_taxonomy(
			self::TAXONOMY,
			array( self::POST_TYPE ),
			array(
				'labels'            => $labels,
				'hierarchical'      => true,
				'public'            => false,
				'show_ui'           => true,
				'show_admin_column' => true,
				'query_var'         => false,
				'rewrite'           => false,
			)
		);
	}
}
