<?php

namespace Tarosky\MakePostmetaFaster;


use Tarosky\MakePostmetaFaster\Pattern\SingletonPattern;

/**
 * Admin setting screen.
 */
class Setting extends SingletonPattern {

	/**
	 * @var string Admin page hook suffix.
	 */
	private $hook_suffix = '';

	/**
	 * {@inheritDoc}
	 */
	protected function init() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Register menu.
	 *
	 * @return void
	 */
	public function register_menu() {
		$this->hook_suffix = add_submenu_page(
			'tools.php',
			__( 'Meta Index', 'mpmf' ),
			__( 'Meta Index', 'mpmf' ),
			'manage_options',
			'mpmf',
			array( $this, 'render_menu' )
		);
	}

	/**
	 * Render menu page.
	 *
	 * @return void
	 */
	public function render_menu() {
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Meta Index', 'mpmf' ); ?></h1>
			<div id="mpmf-admin-root"></div>
		</div>
		<?php
	}

	/**
	 * Enqueue admin assets.
	 *
	 * @param string $hook_suffix Current admin page hook suffix.
	 * @return void
	 */
	public function enqueue_assets( $hook_suffix ) {
		if ( $hook_suffix !== $this->hook_suffix ) {
			return;
		}
		$base_dir = plugin_dir_path( __DIR__ );
		$base_url = plugin_dir_url( __DIR__ );
		$json     = $base_dir . 'wp-dependencies.json';
		if ( ! file_exists( $json ) ) {
			return;
		}
		$deps = json_decode( file_get_contents( $json ), true );
		if ( ! $deps ) {
			return;
		}
		foreach ( $deps as $dep ) {
			if ( empty( $dep['handle'] ) || empty( $dep['path'] ) ) {
				continue;
			}
			$handle = $dep['handle'];
			$src    = $base_url . $dep['path'];
			$ver    = $dep['hash'] ?? $dep['version'] ?? false;
			$d      = $dep['deps'] ?? array();
			if ( preg_match( '/\.css$/', $dep['path'] ) ) {
				wp_enqueue_style( $handle, $src, $d, $ver );
			} else {
				$footer = $dep['footer'] ?? true;
				wp_enqueue_script( $handle, $src, $d, $ver, $footer );
			}
		}
	}

	/**
	 * Register settings for option page.
	 *
	 * @return void
	 */
	public function register_settings() {
		register_setting( 'mpmf', 'mpmf-postmeta-key-length' );
		register_setting( 'mpmf', 'mpmf-usermeta-key-length' );
		register_setting( 'mpmf', 'mpmf-termmeta-key-length' );
	}
}
