<?php
/**
 * Removes every trace of the plugin on uninstall.
 *
 * @package PhoMenuGrid
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

/**
 * Delete this plugin's data for the current site.
 *
 * Deliberately avoids wildcard deletes: `taxonomy_%` and `pho_%` are prefixes
 * other plugins also use, so only rows this plugin can prove ownership of are
 * removed.
 *
 * @return void
 */
function pho_menu_grid_uninstall_site() {
	global $wpdb;

	delete_option( 'pho_menu_grid_settings' );

	// Collect term ids before the terms are deleted.
	$term_ids = get_terms(
		array(
			'taxonomy'   => 'pho_menu_category',
			'hide_empty' => false,
			'fields'     => 'ids',
		)
	);

	$term_ids = is_wp_error( $term_ids ) ? array() : array_map( 'intval', $term_ids );

	// Legacy per-term icon options, written before term meta was used.
	foreach ( $term_ids as $term_id ) {
		delete_option( 'taxonomy_' . $term_id );
	}

	/*
	 * Queried directly: the post type is no longer registered at uninstall time,
	 * and caching a result set that is about to be deleted serves no purpose.
	 */
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$post_ids = $wpdb->get_col(
		$wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_type = %s", 'pho_menu_item' )
	);

	foreach ( $post_ids as $post_id ) {
		wp_delete_post( (int) $post_id, true );
	}

	// Deleting a term also removes its term meta.
	foreach ( $term_ids as $term_id ) {
		wp_delete_term( $term_id, 'pho_menu_category' );
	}
}

if ( is_multisite() ) {
	$site_ids = get_sites(
		array(
			'fields' => 'ids',
			'number' => 0,
		)
	);

	foreach ( $site_ids as $site_id ) {
		switch_to_blog( (int) $site_id );
		pho_menu_grid_uninstall_site();
		restore_current_blog();
	}
} else {
	pho_menu_grid_uninstall_site();
}
