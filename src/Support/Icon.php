<?php
/**
 * Tab icon resolution for menu categories.
 *
 * @package PhoMenuGrid
 */

namespace PhoMenuGrid\Support;

defined( 'ABSPATH' ) || exit;

/**
 * Resolves and renders the icon attached to a `pho_menu_category` term.
 *
 * Replaces the identical get_term_meta + legacy `taxonomy_{id}` option fallback
 * that used to be copy-pasted across the meta box and both shortcodes.
 */
class Icon {

	/**
	 * Neutral circle shown when a term has no icon configured.
	 */
	private const DEFAULT_SVG = '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8z" /></svg>';

	/**
	 * Read the icon settings for a term, falling back to the legacy option.
	 *
	 * Older versions stored these values in a `taxonomy_{term_id}` option rather
	 * than in term meta; that data is still read here so existing sites keep
	 * their icons.
	 *
	 * @param int $term_id Term ID.
	 * @return array{tab_icon: string, use_svg: bool, svg_code: string}
	 */
	public static function for_term( int $term_id ): array {
		$tab_icon = (string) get_term_meta( $term_id, 'tab_icon', true );
		$use_svg  = (string) get_term_meta( $term_id, 'use_svg', true );
		$svg_code = (string) get_term_meta( $term_id, 'svg_code', true );

		if ( '' === $tab_icon && '' === $use_svg && '' === $svg_code ) {
			$legacy = get_option( 'taxonomy_' . $term_id );

			if ( is_array( $legacy ) ) {
				$tab_icon = isset( $legacy['tab_icon'] ) ? (string) $legacy['tab_icon'] : '';
				$use_svg  = isset( $legacy['use_svg'] ) ? (string) $legacy['use_svg'] : '';
				$svg_code = isset( $legacy['svg_code'] ) ? (string) $legacy['svg_code'] : '';
			}
		}

		return array(
			'tab_icon' => trim( $tab_icon ),
			'use_svg'  => '1' === $use_svg,
			'svg_code' => trim( $svg_code ),
		);
	}

	/**
	 * Build the icon markup for a term.
	 *
	 * The stored SVG is passed through wp_kses again on output so rows saved by
	 * an older, unfiltered version of the plugin cannot execute either.
	 *
	 * @param int    $term_id Term ID.
	 * @param string $scope   Optional prefix making the SVG's internal ids and
	 *                        class names unique to this render.
	 * @return string Safe HTML.
	 */
	public static function render( int $term_id, string $scope = '' ): string {
		$icon = self::for_term( $term_id );

		if ( $icon['use_svg'] && '' !== $icon['svg_code'] ) {
			$svg = Sanitizer::kses_svg( $icon['svg_code'] );

			if ( '' !== $svg ) {
				return '' === $scope ? $svg : self::scope_svg( $svg, $scope );
			}
		}

		if ( '' !== $icon['tab_icon'] ) {
			return '<i class="' . esc_attr( $icon['tab_icon'] ) . '" aria-hidden="true"></i>';
		}

		return self::DEFAULT_SVG;
	}

	/**
	 * Make an SVG's internal identifiers unique to one render.
	 *
	 * Illustrator exports carry fixed ids and `.cls-N` class names. Rendering the
	 * same icon twice on a page — two shortcode instances, or the same category
	 * in two elements — would otherwise emit duplicate ids and let one icon's
	 * `<style>` rules repaint another's shapes.
	 *
	 * @param string $svg   Sanitized SVG markup.
	 * @param string $scope Unique prefix.
	 * @return string
	 */
	private static function scope_svg( string $svg, string $scope ): string {
		$prefix = sanitize_html_class( $scope ) . '-';

		// Rename every declared id, plus the url(#id) references pointing at it.
		if ( preg_match_all( '/\sid="([^"]+)"/', $svg, $matches ) ) {
			foreach ( array_unique( $matches[1] ) as $id ) {
				$quoted = preg_quote( $id, '/' );
				$svg    = preg_replace( '/\sid="' . $quoted . '"/', ' id="' . $prefix . $id . '"', $svg );
				$svg    = preg_replace( '/url\(\s*#' . $quoted . '\s*\)/', 'url(#' . $prefix . $id . ')', $svg );
				$svg    = preg_replace( '/href="#' . $quoted . '"/', 'href="#' . $prefix . $id . '"', $svg );
			}
		}

		// Rename the class names the embedded stylesheet actually defines.
		if ( preg_match( '#<style\b[^>]*>(.*?)</style>#is', $svg, $style )
			&& preg_match_all( '/\.([A-Za-z_][\w-]*)/', $style[1], $names )
		) {
			foreach ( array_unique( $names[1] ) as $name ) {
				$quoted = preg_quote( $name, '/' );

				// In the stylesheet selector.
				$svg = preg_replace( '/\.' . $quoted . '\b/', '.' . $prefix . $name, $svg );

				// And in each class attribute that uses it.
				$svg = preg_replace_callback(
					'/\sclass="([^"]*)"/',
					static function ( $attr ) use ( $name, $prefix ) {
						$classes = preg_split( '/\s+/', trim( $attr[1] ) );
						$classes = is_array( $classes ) ? $classes : array();
						$classes = array_map(
							static function ( $candidate ) use ( $name, $prefix ) {
								return $candidate === $name ? $prefix . $name : $candidate;
							},
							$classes
						);

						return ' class="' . implode( ' ', $classes ) . '"';
					},
					$svg
				);
			}
		}

		return $svg;
	}
}
