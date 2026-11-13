<?php
/**
 * Plugin Name:       Sena Studio
 * Plugin URI:        https://senastudio.example
 * Description:       A phone-first studio dashboard for running a small handmade business on top of WooCommerce: orders, pieces, custom requests, social posts and events in one screen.
 * Version:           1.1.0
 * Requires at least: 6.8
 * Requires PHP:      8.3
 * Author:            Sena Studio
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       sena-studio
 * Domain Path:       /languages
 * WC requires at least: 9.0
 * WC tested up to:   11.0
 */

defined( 'ABSPATH' ) || exit;

define( 'SENA_STUDIO_VERSION', '1.1.0' );
define( 'SENA_STUDIO_FILE', __FILE__ );
define( 'SENA_STUDIO_DIR', plugin_dir_path( __FILE__ ) );
define( 'SENA_STUDIO_URL', plugin_dir_url( __FILE__ ) );
define( 'SENA_STUDIO_SLUG', 'sena-studio' );

require_once SENA_STUDIO_DIR . 'includes/class-studio.php';

/**
 * Activation: register post types so rewrite rules include them, then flush.
 */
function sena_studio_activate(): void {
	require_once SENA_STUDIO_DIR . 'includes/class-requests.php';
	require_once SENA_STUDIO_DIR . 'includes/class-posts.php';
	require_once SENA_STUDIO_DIR . 'includes/class-events.php';

	Sena_Studio_Requests::register_post_type();
	Sena_Studio_Posts::register_post_type();
	Sena_Studio_Events::register_post_type();
	Sena_Studio::add_rewrite();

	if ( false === get_option( Sena_Studio::OPTION ) ) {
		add_option( Sena_Studio::OPTION, Sena_Studio::default_settings() );
	} else {
		update_option( Sena_Studio::OPTION, wp_parse_args( get_option( Sena_Studio::OPTION ), Sena_Studio::default_settings() ) );
	}
	update_option( 'sena_studio_version', SENA_STUDIO_VERSION );

	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'sena_studio_activate' );

function sena_studio_deactivate(): void {
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'sena_studio_deactivate' );

/**
 * Declare HPOS (custom order tables) compatibility.
 */
add_action( 'before_woocommerce_init', static function (): void {
	if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
	}
} );

add_action( 'plugins_loaded', static function (): void {
	Sena_Studio::instance();
}, 20 );
