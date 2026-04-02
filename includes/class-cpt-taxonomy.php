<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register CPT pho_menu_item
 */
add_action( 'init', 'pho_menu_grid_register_cpt' );
function pho_menu_grid_register_cpt() {
	$labels = array(
		'name'                  => _x( 'Menu Items', 'Post type general name', 'pho-menu-grid' ),
		'singular_name'         => _x( 'Menu Item', 'Post type singular name', 'pho-menu-grid' ),
		'menu_name'             => _x( 'Menu Items', 'Admin Menu text', 'pho-menu-grid' ),
		'name_admin_bar'        => _x( 'Menu Item', 'Add New on Toolbar', 'pho-menu-grid' ),
		'add_new'               => __( 'Add New', 'pho-menu-grid' ),
		'add_new_item'          => __( 'Add New Menu Item', 'pho-menu-grid' ),
		'new_item'              => __( 'New Menu Item', 'pho-menu-grid' ),
		'edit_item'             => __( 'Edit Menu Item', 'pho-menu-grid' ),
		'view_item'             => __( 'View Menu Item', 'pho-menu-grid' ),
		'all_items'             => __( 'All Menu Items', 'pho-menu-grid' ),
		'search_items'          => __( 'Search Menu Items', 'pho-menu-grid' ),
		'not_found'             => __( 'No menu items found.', 'pho-menu-grid' ),
		'not_found_in_trash'    => __( 'No menu items found in Trash.', 'pho-menu-grid' ),
	);

	$args = array(
		'labels'             => $labels,
		'public'             => true,
		'publicly_queryable' => true,
		'show_ui'            => true,
		'show_in_menu'       => true,
		'query_var'          => true,
		'rewrite'            => array( 'slug' => 'menu-item' ),
		'capability_type'    => 'post',
		'has_archive'        => true,
		'hierarchical'       => false,
		'menu_position'      => null,
		'menu_icon'          => 'dashicons-food',
		'supports'           => array( 'title', 'thumbnail' ),
	);

	register_post_type( 'pho_menu_item', $args );
}

/**
 * Register Taxonomy pho_menu_category
 */
add_action( 'init', 'pho_menu_grid_register_taxonomy' );
function pho_menu_grid_register_taxonomy() {
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

	$args = array(
		'hierarchical'      => true,
		'labels'            => $labels,
		'show_ui'           => true,
		'show_admin_column' => true,
		'query_var'         => true,
		'rewrite'           => array( 'slug' => 'menu-category' ),
	);

	register_taxonomy( 'pho_menu_category', array( 'pho_menu_item' ), $args );
}

/**
 * Add Meta Box for CPT pho_menu_item
 */
add_action( 'add_meta_boxes', 'pho_menu_grid_add_meta_boxes' );
function pho_menu_grid_add_meta_boxes() {
	add_meta_box(
		'pho_menu_item_details',
		__( 'Menu Item Details', 'pho-menu-grid' ),
		'pho_menu_grid_meta_box_callback',
		'pho_menu_item',
		'normal',
		'high'
	);
}

function pho_menu_grid_meta_box_callback( $post ) {
	wp_nonce_field( 'pho_menu_item_meta_box', 'pho_menu_item_meta_box_nonce' );

	$item_link   = get_post_meta( $post->ID, 'pho_item_link', true );
	$star_rating = get_post_meta( $post->ID, 'pho_star_rating', true );
	$review_text = get_post_meta( $post->ID, 'pho_review_text', true );

	echo '<table class="form-table">';
	echo '<tr>';
	echo '<th><label for="pho_item_link">' . esc_html__( 'Item Link (URL)', 'pho-menu-grid' ) . '</label></th>';
	echo '<td><input type="text" id="pho_item_link" name="pho_item_link" value="' . esc_attr( $item_link ) . '" class="regular-text" placeholder="https:// or /some-page" /></td>';
	echo '</tr>';

	echo '<tr>';
	echo '<th><label for="pho_star_rating">' . esc_html__( 'Star Rating (Number)', 'pho-menu-grid' ) . '</label></th>';
	echo '<td><input type="text" id="pho_star_rating" name="pho_star_rating" value="' . esc_attr( $star_rating ) . '" class="regular-text" placeholder="e.g. 4.5" /></td>';
	echo '</tr>';

	echo '<tr>';
	echo '<th><label for="pho_review_text">' . esc_html__( 'Review Text', 'pho-menu-grid' ) . '</label></th>';
	echo '<td><input type="text" id="pho_review_text" name="pho_review_text" value="' . esc_attr( $review_text ) . '" class="regular-text" placeholder="e.g. (250+ Google Reviews)" /></td>';
	echo '</tr>';
	echo '</table>';
}

add_action( 'save_post', 'pho_menu_grid_save_meta_box_data' );
function pho_menu_grid_save_meta_box_data( $post_id ) {
	if ( ! isset( $_POST['pho_menu_item_meta_box_nonce'] ) ) {
		return;
	}
	if ( ! wp_verify_nonce( $_POST['pho_menu_item_meta_box_nonce'], 'pho_menu_item_meta_box' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	if ( isset( $_POST['pho_item_link'] ) ) {
		update_post_meta( $post_id, 'pho_item_link', sanitize_text_field( wp_unslash( $_POST['pho_item_link'] ) ) );
	}
	if ( isset( $_POST['pho_star_rating'] ) ) {
		update_post_meta( $post_id, 'pho_star_rating', sanitize_text_field( wp_unslash( $_POST['pho_star_rating'] ) ) );
	}
	if ( isset( $_POST['pho_review_text'] ) ) {
		update_post_meta( $post_id, 'pho_review_text', sanitize_text_field( wp_unslash( $_POST['pho_review_text'] ) ) );
	}
}

/**
 * Add Term Meta to pho_menu_category
 */
// 1. Add fields on "Add New Category" form
add_action( 'pho_menu_category_add_form_fields', 'pho_menu_category_add_new_meta_field', 10, 2 );
function pho_menu_category_add_new_meta_field() {
	?>
	<div class="form-field">
		<label for="term_meta_tab_icon"><?php esc_html_e( 'Tab Icon (Class)', 'pho-menu-grid' ); ?></label>
		<input type="text" name="term_meta[tab_icon]" id="term_meta_tab_icon" value="">
		<p class="description"><?php esc_html_e( 'If you use Flatsome icon classes, enter here (e.g. icon-star). Ignored if Use Inline SVG is checked.', 'pho-menu-grid' ); ?></p>
	</div>
	<div class="form-field">
		<label for="term_meta_use_svg">
			<input type="checkbox" name="term_meta[use_svg]" id="term_meta_use_svg" value="1">
			<?php esc_html_e( 'Use Inline SVG for Tab Icon?', 'pho-menu-grid' ); ?>
		</label>
	</div>
	<div class="form-field">
		<label for="term_meta_svg_code"><?php esc_html_e( 'SVG Code', 'pho-menu-grid' ); ?></label>
		<textarea name="term_meta[svg_code]" id="term_meta_svg_code" rows="5" cols="50"></textarea>
		<p class="description"><?php esc_html_e( 'Paste your raw <svg>...</svg> code here.', 'pho-menu-grid' ); ?></p>
	</div>
	<?php
}

// 2. Add fields on "Edit Category" form
add_action( 'pho_menu_category_edit_form_fields', 'pho_menu_category_edit_meta_field', 10, 2 );
function pho_menu_category_edit_meta_field( $term ) {
	$term_id = $term->term_id;
	$tab_icon  = get_term_meta( $term_id, 'tab_icon', true );
	$use_svg   = get_term_meta( $term_id, 'use_svg', true );
	$svg_code  = get_term_meta( $term_id, 'svg_code', true );
	?>
	<tr class="form-field">
		<th scope="row" valign="top"><label for="term_meta_tab_icon"><?php esc_html_e( 'Tab Icon (Class)', 'pho-menu-grid' ); ?></label></th>
		<td>
			<input type="text" name="term_meta[tab_icon]" id="term_meta_tab_icon" value="<?php echo esc_attr( $tab_icon ); ?>">
			<p class="description"><?php esc_html_e( 'If you use Flatsome icon classes, enter here (e.g. icon-star). Ignored if Use Inline SVG is checked.', 'pho-menu-grid' ); ?></p>
		</td>
	</tr>
	<tr class="form-field">
		<th scope="row" valign="top"><label for="term_meta_use_svg"><?php esc_html_e( 'Use Inline SVG for Tab Icon?', 'pho-menu-grid' ); ?></label></th>
		<td>
			<input type="checkbox" name="term_meta[use_svg]" id="term_meta_use_svg" value="1" <?php checked( $use_svg, '1' ); ?>>
		</td>
	</tr>
	<tr class="form-field">
		<th scope="row" valign="top"><label for="term_meta_svg_code"><?php esc_html_e( 'SVG Code', 'pho-menu-grid' ); ?></label></th>
		<td>
			<textarea name="term_meta[svg_code]" id="term_meta_svg_code" rows="5" cols="50"><?php echo esc_textarea( $svg_code ); ?></textarea>
			<p class="description"><?php esc_html_e( 'Paste your raw <svg>...</svg> code here.', 'pho-menu-grid' ); ?></p>
		</td>
	</tr>
	<?php
}

// 3. Save Term Meta
add_action( 'edited_pho_menu_category', 'pho_menu_category_save_term_meta', 10, 2 );
add_action( 'create_pho_menu_category', 'pho_menu_category_save_term_meta', 10, 2 );
function pho_menu_category_save_term_meta( $term_id ) {
	if ( ! isset( $_POST['term_meta'] ) ) {
		return;
	}

	$tab_icon = isset( $_POST['term_meta']['tab_icon'] ) ? sanitize_text_field( wp_unslash( $_POST['term_meta']['tab_icon'] ) ) : '';
	$use_svg  = isset( $_POST['term_meta']['use_svg'] ) ? '1' : '0';
	
	$svg_code = '';
	if ( isset( $_POST['term_meta']['svg_code'] ) && current_user_can( 'manage_options' ) ) {
		$svg_code = trim( wp_unslash( $_POST['term_meta']['svg_code'] ) );
	}

	update_term_meta( $term_id, 'tab_icon', $tab_icon );
	update_term_meta( $term_id, 'use_svg', $use_svg );
	update_term_meta( $term_id, 'svg_code', $svg_code );
}
