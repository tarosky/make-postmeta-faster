<?php
/**
 * Plugin Name: Make Postmeta Faster
 * Plugin URI: https://wordpress.org/extend/plugins/make-postmeta-faster
 * Description: Add index to post meta.
 * Version: nightly
 * Author: Tarosky INC
 * Author URI: https://tarosky.co.jp
 * Text Domain: mpmf
 * Domain Path: /languages
 * License: GPL3 or Later
 */

// Avoid direct acccess.
defined( 'ABSPATH' ) || die();

require_once __DIR__ . '/vendor/autoload.php';

// Initialize plugin.
add_action( 'plugins_loaded', function () {
	// Add translation.
	load_plugin_textdomain( 'mpmf', false, basename( __DIR__ ) . '/languages' );
	// Enable boostrap.
	\Tarosky\MakePostmetaFaster\Bootstrap::get_instance();
} );
