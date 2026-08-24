<?php
/**
 * Asset registration and enqueueing.
 *
 * @package PhoMenuGrid
 */

namespace PhoMenuGrid;

defined( 'ABSPATH' ) || exit;

/**
 * Registers front-end and admin assets.
 *
 * Everything ships with the plugin — the previous GSAP and Flickity CDN
 * registrations were removed, so no third-party host is contacted at runtime.
 */
class Assets {

	/**
	 * Hook into WordPress.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_action( 'wp_enqueue_scripts', array( $this, 'register_front_assets' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
	}

	/**
	 * Register front-end assets and enqueue them inside the builder preview.
	 *
	 * Shortcodes enqueue what they need on render; the builder preview loads
	 * everything up front because its elements arrive over AJAX after
	 * `wp_enqueue_scripts` has already run.
	 *
	 * @return void
	 */
	public function register_front_assets(): void {
		wp_register_script(
			'pho-menu-nav',
			PHO_MENU_GRID_URL . 'assets/js/pho-menu-nav.js',
			array(),
			PHO_MENU_GRID_VERSION,
			true
		);

		wp_register_style(
			'pho-menu-grid',
			PHO_MENU_GRID_URL . 'assets/css/pho-menu-grid.css',
			array(),
			PHO_MENU_GRID_VERSION
		);

		wp_register_script(
			'pho-menu-grid',
			PHO_MENU_GRID_URL . 'assets/js/pho-menu-grid.js',
			array( 'pho-menu-nav' ),
			PHO_MENU_GRID_VERSION,
			true
		);

		wp_register_style(
			'pho-menu-showcase',
			PHO_MENU_GRID_URL . 'assets/css/pho-menu-showcase.css',
			array(),
			PHO_MENU_GRID_VERSION
		);

		wp_register_script(
			'pho-menu-showcase',
			PHO_MENU_GRID_URL . 'assets/js/pho-menu-showcase.js',
			array( 'pho-menu-nav' ),
			PHO_MENU_GRID_VERSION,
			true
		);

		if ( $this->is_builder_preview() ) {
			wp_enqueue_style( 'pho-menu-grid' );
			wp_enqueue_script( 'pho-menu-grid' );
			wp_enqueue_style( 'pho-menu-showcase' );
			wp_enqueue_script( 'pho-menu-showcase' );
		}
	}

	/**
	 * Whether the current request is an editor preview.
	 *
	 * The capability check stops an anonymous visitor from forcing every asset
	 * onto any page by appending `?uxb_iframe=1`.
	 *
	 * @return bool
	 */
	private function is_builder_preview(): bool {
		if ( ! current_user_can( 'edit_posts' ) ) {
			return false;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only presence check, gated by the capability above.
		return isset( $_GET['uxb_iframe'] ) || is_customize_preview();
	}

	/**
	 * Enqueue the meta box repeater script on the menu item editor only.
	 *
	 * @param string $hook_suffix Current admin page.
	 * @return void
	 */
	public function enqueue_admin_assets( string $hook_suffix ): void {
		if ( ! in_array( $hook_suffix, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}

		$screen = get_current_screen();

		if ( ! $screen || PostType::POST_TYPE !== $screen->post_type ) {
			return;
		}

		wp_enqueue_script(
			'pho-menu-admin',
			PHO_MENU_GRID_URL . 'assets/js/admin-meta-box.js',
			array(),
			PHO_MENU_GRID_VERSION,
			true
		);

		wp_enqueue_style(
			'pho-menu-admin',
			PHO_MENU_GRID_URL . 'assets/css/admin-meta-box.css',
			array(),
			PHO_MENU_GRID_VERSION
		);

		wp_localize_script(
			'pho-menu-admin',
			'phoMenuAdmin',
			array(
				'i18n' => array(
					'labelPlaceholder' => __( 'Label (e.g. Gluten-Free Option)', 'pho-menu-grid' ),
					'textPlaceholder'  => __( 'Description text...', 'pho-menu-grid' ),
					'remove'           => __( 'Remove', 'pho-menu-grid' ),
				),
			)
		);
	}
}
