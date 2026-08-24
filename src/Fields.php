<?php
/**
 * Single source of truth for menu item meta fields.
 *
 * @package PhoMenuGrid
 */

namespace PhoMenuGrid;

defined( 'ABSPATH' ) || exit;

/**
 * Declares every `pho_*` post meta field once.
 *
 * Both the meta box markup and the save routine iterate this list, so a field
 * can never be rendered without being saved (or vice versa).
 */
class Fields {

	/**
	 * Meta key holding the dietary guide repeater, handled separately.
	 */
	public const DIETARY_KEY = 'pho_dietary_guide_json';

	/**
	 * Field definitions in display order.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function all(): array {
		return array(
			'pho_vn_name'             => array(
				'label'       => __( 'Vietnamese Name', 'pho-menu-grid' ),
				'type'        => 'text',
				'placeholder' => 'BÁNH MÌ THỊT NƯỚNG',
				'sanitize'    => 'sanitize_text_field',
			),
			'pho_description'         => array(
				'label'    => __( 'Description', 'pho-menu-grid' ),
				'type'     => 'textarea',
				'rows'     => 4,
				'sanitize' => 'sanitize_textarea_field',
			),
			'pho_price'               => array(
				'label'       => __( 'Regular Price', 'pho-menu-grid' ),
				'type'        => 'text',
				'placeholder' => __( 'e.g. $18.00', 'pho-menu-grid' ),
				'sanitize'    => 'sanitize_text_field',
			),
			'pho_sale_price'          => array(
				'label'       => __( 'Sale Price (Optional)', 'pho-menu-grid' ),
				'type'        => 'text',
				'placeholder' => __( 'e.g. $15.00', 'pho-menu-grid' ),
				'sanitize'    => 'sanitize_text_field',
			),
			'pho_sticker'             => array(
				'label'    => __( 'Sticker / Badge', 'pho-menu-grid' ),
				'type'     => 'select',
				'choices'  => array(
					''             => __( 'None', 'pho-menu-grid' ),
					'chefs_choice' => __( "Chef's Choice", 'pho-menu-grid' ),
					'must_try'     => __( 'Must Try', 'pho-menu-grid' ),
					'best_seller'  => __( 'Best Seller', 'pho-menu-grid' ),
				),
				'sanitize' => 'sanitize_key',
			),
			'pho_item_link'           => array(
				'label'       => __( 'Item Link (URL)', 'pho-menu-grid' ),
				'type'        => 'url',
				'placeholder' => 'https:// or /some-page',
				'sanitize'    => array( self::class, 'sanitize_link' ),
			),
			'pho_secondary_btn_label' => array(
				'label'       => __( 'Secondary Button Label', 'pho-menu-grid' ),
				'type'        => 'text',
				'placeholder' => 'DISCOVER MORE...',
				'sanitize'    => 'sanitize_text_field',
			),
			'pho_secondary_btn_link'  => array(
				'label'       => __( 'Secondary Button Link', 'pho-menu-grid' ),
				'type'        => 'url',
				'placeholder' => 'https:// order online url',
				'sanitize'    => array( self::class, 'sanitize_link' ),
			),
			'pho_star_rating'         => array(
				'label'       => __( 'Star Rating', 'pho-menu-grid' ),
				'type'        => 'text',
				'placeholder' => __( 'e.g. 4.5', 'pho-menu-grid' ),
				'sanitize'    => 'sanitize_text_field',
			),
			'pho_review_text'         => array(
				'label'       => __( 'Review Text', 'pho-menu-grid' ),
				'type'        => 'text',
				'placeholder' => '(250+ Google Reviews)',
				'sanitize'    => 'sanitize_text_field',
			),
		);
	}

	/**
	 * Sanitize a link that may be absolute or site relative.
	 *
	 * `esc_url_raw()` alone would discard values like `/order-online`, which the
	 * field explicitly accepts, so relative paths are kept but stripped of any
	 * scheme-like prefix.
	 *
	 * @param string $value Raw value.
	 * @return string
	 */
	public static function sanitize_link( string $value ): string {
		$value = trim( $value );

		if ( '' === $value ) {
			return '';
		}

		if ( 0 === strpos( $value, '/' ) || 0 === strpos( $value, '#' ) ) {
			return sanitize_text_field( $value );
		}

		return esc_url_raw( $value );
	}

	/**
	 * Decode and sanitize the dietary guide repeater payload.
	 *
	 * @param string $json Raw JSON from the meta box.
	 * @return array<int, array{label: string, text: string}>
	 */
	public static function sanitize_dietary( string $json ): array {
		$decoded = json_decode( $json, true );

		if ( ! is_array( $decoded ) ) {
			return array();
		}

		$rows = array();

		foreach ( $decoded as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}

			$label = isset( $row['label'] ) ? sanitize_text_field( (string) $row['label'] ) : '';
			$text  = isset( $row['text'] ) ? sanitize_textarea_field( (string) $row['text'] ) : '';

			if ( '' === $label && '' === $text ) {
				continue;
			}

			$rows[] = array(
				'label' => $label,
				'text'  => $text,
			);
		}

		return $rows;
	}

	/**
	 * Read the stored dietary guide rows for a post.
	 *
	 * @param int $post_id Post ID.
	 * @return array<int, array{label: string, text: string}>
	 */
	public static function get_dietary( int $post_id ): array {
		$stored = (string) get_post_meta( $post_id, self::DIETARY_KEY, true );

		if ( '' === $stored ) {
			return array();
		}

		return self::sanitize_dietary( $stored );
	}
}
