<?php
/**
 * Menu item meta box.
 *
 * @package PhoMenuGrid
 */

namespace PhoMenuGrid;

defined( 'ABSPATH' ) || exit;

/**
 * Renders and saves the "Menu Item Details" meta box.
 *
 * Both halves iterate {@see Fields::all()}, so the render markup and the save
 * routine can no longer drift apart.
 */
class MetaBox {

	/**
	 * Nonce action.
	 */
	private const NONCE_ACTION = 'pho_menu_item_meta_box';

	/**
	 * Nonce request key.
	 */
	private const NONCE_NAME = 'pho_menu_item_meta_box_nonce';

	/**
	 * Hook into WordPress.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
		add_action( 'save_post_' . PostType::POST_TYPE, array( $this, 'save' ) );
	}

	/**
	 * Register the meta box.
	 *
	 * @return void
	 */
	public function add_meta_box(): void {
		add_meta_box(
			'pho_menu_item_details',
			__( 'Menu Item Details', 'pho-menu-grid' ),
			array( $this, 'render' ),
			PostType::POST_TYPE,
			'normal',
			'high'
		);
	}

	/**
	 * Render the meta box.
	 *
	 * @param \WP_Post $post Current post.
	 * @return void
	 */
	public function render( \WP_Post $post ): void {
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );

		echo '<table class="form-table">';

		foreach ( Fields::all() as $key => $field ) {
			$value = (string) get_post_meta( $post->ID, $key, true );

			echo '<tr>';
			printf(
				'<th><label for="%1$s">%2$s</label></th>',
				esc_attr( $key ),
				esc_html( $field['label'] )
			);
			echo '<td>';
			$this->render_control( $key, $field, $value );
			echo '</td>';
			echo '</tr>';
		}

		$this->render_dietary_row( $post->ID );

		echo '</table>';
	}

	/**
	 * Render a single field control.
	 *
	 * @param string               $key   Meta key.
	 * @param array<string, mixed> $field Field definition.
	 * @param string               $value Stored value.
	 * @return void
	 */
	private function render_control( string $key, array $field, string $value ): void {
		$placeholder = isset( $field['placeholder'] ) ? (string) $field['placeholder'] : '';

		switch ( $field['type'] ) {
			case 'textarea':
				printf(
					'<textarea id="%1$s" name="%1$s" rows="%2$d" class="large-text" placeholder="%3$s">%4$s</textarea>',
					esc_attr( $key ),
					absint( $field['rows'] ?? 4 ),
					esc_attr( $placeholder ),
					esc_textarea( $value )
				);
				break;

			case 'select':
				printf( '<select id="%1$s" name="%1$s">', esc_attr( $key ) );

				foreach ( (array) $field['choices'] as $choice => $label ) {
					printf(
						'<option value="%1$s"%2$s>%3$s</option>',
						esc_attr( $choice ),
						selected( $value, $choice, false ),
						esc_html( $label )
					);
				}

				echo '</select>';
				break;

			default:
				printf(
					'<input type="text" id="%1$s" name="%1$s" value="%2$s" class="regular-text" placeholder="%3$s" />',
					esc_attr( $key ),
					esc_attr( $value ),
					esc_attr( $placeholder )
				);
		}
	}

	/**
	 * Render the dietary guide repeater row.
	 *
	 * The repeater UI itself lives in assets/js/admin-meta-box.js.
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	private function render_dietary_row( int $post_id ): void {
		$rows = Fields::get_dietary( $post_id );

		echo '<tr>';
		printf( '<th><label>%s</label></th>', esc_html__( 'Dietary Guide', 'pho-menu-grid' ) );
		echo '<td>';
		echo '<div id="pho_dietary_repeater_wrap">';
		printf(
			'<input type="hidden" id="%1$s" name="%1$s" value="%2$s" />',
			esc_attr( Fields::DIETARY_KEY ),
			esc_attr( (string) wp_json_encode( $rows ) )
		);
		echo '<div id="pho_dietary_rows" class="pho-dietary-rows"></div>';
		printf(
			'<button type="button" class="button button-secondary" id="pho_add_dietary_row">%s</button>',
			esc_html__( '+ Add Guide Row', 'pho-menu-grid' )
		);
		echo '</div>';
		echo '</td>';
		echo '</tr>';
	}

	/**
	 * Persist the submitted meta.
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	public function save( int $post_id ): void {
		$nonce = isset( $_POST[ self::NONCE_NAME ] )
			? sanitize_text_field( wp_unslash( $_POST[ self::NONCE_NAME ] ) )
			: '';

		if ( '' === $nonce || ! wp_verify_nonce( $nonce, self::NONCE_ACTION ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		foreach ( Fields::all() as $key => $field ) {
			if ( ! isset( $_POST[ $key ] ) ) {
				continue;
			}

			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized by the field's own callback below.
			$raw   = wp_unslash( $_POST[ $key ] );
			$value = call_user_func( $field['sanitize'], (string) $raw );

			if ( '' === $value ) {
				delete_post_meta( $post_id, $key );
			} else {
				update_post_meta( $post_id, $key, $value );
			}
		}

		$this->save_dietary( $post_id );
	}

	/**
	 * Persist the dietary guide repeater.
	 *
	 * The payload is decoded, each row sanitized, then re-encoded — the raw
	 * client-supplied JSON is never stored.
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	private function save_dietary( int $post_id ): void {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- save() verifies the meta box nonce before delegating here.
		if ( ! isset( $_POST[ Fields::DIETARY_KEY ] ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- decoded and sanitized row by row in sanitize_dietary().
		$raw  = (string) wp_unslash( $_POST[ Fields::DIETARY_KEY ] );
		$rows = Fields::sanitize_dietary( $raw );

		if ( empty( $rows ) ) {
			delete_post_meta( $post_id, Fields::DIETARY_KEY );
			return;
		}

		// update_metadata() unslashes its value, which would corrupt the escape
		// sequences inside the JSON, so it is re-slashed here.
		update_post_meta( $post_id, Fields::DIETARY_KEY, wp_slash( (string) wp_json_encode( $rows ) ) );
		// phpcs:enable WordPress.Security.NonceVerification.Missing
	}
}
