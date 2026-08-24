<?php
/**
 * Shared sanitizing and escaping helpers.
 *
 * @package PhoMenuGrid
 */

namespace PhoMenuGrid\Support;

defined( 'ABSPATH' ) || exit;

/**
 * Centralises every sanitize/escape rule used by the plugin.
 */
class Sanitizer {

	/**
	 * Attributes shared by every allowed SVG element.
	 *
	 * Deliberately excludes `style`, `href`/`xlink:href` and any `on*` handler.
	 *
	 * @var array<string, bool>
	 */
	private const SVG_COMMON_ATTS = array(
		'class'            => true,
		'fill'             => true,
		'fill-opacity'     => true,
		'fill-rule'        => true,
		'clip-rule'        => true,
		'stroke'           => true,
		'stroke-width'     => true,
		'stroke-linecap'   => true,
		'stroke-linejoin'  => true,
		'stroke-dasharray' => true,
		'opacity'          => true,
		'transform'        => true,
		'aria-hidden'      => true,
		'aria-label'       => true,
		'role'             => true,
		'focusable'        => true,
		'id'               => true,
		'data-*'           => true,
	);

	/**
	 * Build the wp_kses allowlist for inline SVG icons.
	 *
	 * @return array<string, array<string, bool>>
	 */
	public static function svg_allowed_html(): array {
		$common = self::SVG_COMMON_ATTS;

		$tags = array(
			'svg'            => array(
				'xmlns'               => true,
				'xmlns:xlink'         => true,
				'viewbox'             => true,
				'width'               => true,
				'height'              => true,
				'preserveaspectratio' => true,
				'version'             => true,
			),
			'g'              => array(),
			'defs'           => array(),
			'style'          => array( 'type' => true ),
			'title'          => array(),
			'desc'           => array(),
			'path'           => array( 'd' => true ),
			'circle'         => array(
				'cx' => true,
				'cy' => true,
				'r'  => true,
			),
			'ellipse'        => array(
				'cx' => true,
				'cy' => true,
				'rx' => true,
				'ry' => true,
			),
			'rect'           => array(
				'x'      => true,
				'y'      => true,
				'width'  => true,
				'height' => true,
				'rx'     => true,
				'ry'     => true,
			),
			'line'           => array(
				'x1' => true,
				'y1' => true,
				'x2' => true,
				'y2' => true,
			),
			'polyline'       => array( 'points' => true ),
			'polygon'        => array( 'points' => true ),
			'lineargradient' => array(
				'id'            => true,
				'x1'            => true,
				'y1'            => true,
				'x2'            => true,
				'y2'            => true,
				'gradientunits' => true,
			),
			'radialgradient' => array(
				'id'            => true,
				'cx'            => true,
				'cy'            => true,
				'r'             => true,
				'fx'            => true,
				'fy'            => true,
				'gradientunits' => true,
			),
			'stop'           => array(
				'offset'       => true,
				'stop-color'   => true,
				'stop-opacity' => true,
			),
		);

		foreach ( $tags as $tag => $atts ) {
			$tags[ $tag ] = array_merge( $common, $atts );
		}

		return $tags;
	}

	/**
	 * Strip everything but safe SVG markup.
	 *
	 * Applied both on save and on output, so legacy rows already stored in the
	 * database cannot execute either.
	 *
	 * @param string $svg Raw SVG markup.
	 * @return string Sanitized SVG markup.
	 */
	public static function kses_svg( string $svg ): string {
		$svg = wp_kses( $svg, self::svg_allowed_html() );

		return trim( self::scrub_style_blocks( $svg ) );
	}

	/**
	 * Neutralise anything dangerous inside an SVG <style> block.
	 *
	 * Illustrator exports colour their shapes with a `<style>` block and class
	 * selectors instead of `fill` attributes, so the element has to survive —
	 * but its body is never parsed by wp_kses. Anything that can reach the
	 * network or execute is dropped, leaving plain declarations intact.
	 *
	 * @param string $svg SVG markup that has already been through wp_kses.
	 * @return string
	 */
	private static function scrub_style_blocks( string $svg ): string {
		if ( false === stripos( $svg, '<style' ) ) {
			return $svg;
		}

		return (string) preg_replace_callback(
			'#(<style\b[^>]*>)(.*?)(</style>)#is',
			static function ( $matches ) {
				$css = $matches[2];

				// At-rules that fetch or execute, plus any URL reference.
				$css = preg_replace( '/@(import|charset|namespace|document)[^;{]*;?/i', '', $css );
				$css = preg_replace( '/url\s*\([^)]*\)/i', '', $css );
				$css = preg_replace( '/expression\s*\(/i', '', $css );
				$css = preg_replace( '/(behavior|-moz-binding)\s*:[^;}]*/i', '', $css );
				$css = preg_replace( '/javascript\s*:/i', '', $css );

				return $matches[1] . $css . $matches[3];
			},
			$svg
		);
	}

	/**
	 * Validate a CSS colour value.
	 *
	 * Accepts 3/4/6/8 digit hex plus rgb()/rgba() notation. Anything else falls
	 * back to the supplied default so a shortcode attribute can never inject
	 * arbitrary CSS declarations.
	 *
	 * @param string $color    Untrusted colour value.
	 * @param string $fallback Colour used when the value is not recognised.
	 * @return string
	 */
	public static function color( string $color, string $fallback ): string {
		$color = trim( $color );

		if ( preg_match( '/^#([0-9a-f]{3,4}|[0-9a-f]{6}|[0-9a-f]{8})$/i', $color ) ) {
			return $color;
		}

		if ( preg_match( '/^rgba?\(\s*[0-9]{1,3}\s*,\s*[0-9]{1,3}\s*,\s*[0-9]{1,3}\s*(?:,\s*(?:0|1|0?\.[0-9]+)\s*)?\)$/i', $color ) ) {
			return $color;
		}

		return $fallback;
	}

	/**
	 * Sanitize a space separated list of CSS class names.
	 *
	 * @param string ...$lists One or more raw class strings.
	 * @return string Space separated, deduplicated, safe class names.
	 */
	public static function class_list( string ...$lists ): string {
		$classes = array();

		foreach ( $lists as $list ) {
			$tokens = preg_split( '/\s+/', trim( $list ) );

			foreach ( is_array( $tokens ) ? $tokens : array() as $class ) {
				$class = sanitize_html_class( $class );

				if ( '' !== $class ) {
					$classes[ $class ] = true;
				}
			}
		}

		return implode( ' ', array_keys( $classes ) );
	}

	/**
	 * Cast a shortcode attribute to a boolean.
	 *
	 * @param mixed $value Raw attribute value.
	 * @return bool
	 */
	public static function bool( $value ): bool {
		return filter_var( $value, FILTER_VALIDATE_BOOLEAN );
	}
}
