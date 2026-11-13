<?php
/**
 * Sena Crafted theme bootstrap.
 *
 * @package Sena_Crafted
 */

defined( 'ABSPATH' ) || exit;

define( 'SENA_VERSION', '1.0.0' );
define( 'SENA_DIR', get_template_directory() );
define( 'SENA_URI', get_template_directory_uri() );

require SENA_DIR . '/inc/class-sena-flat-walker.php';
require SENA_DIR . '/inc/template-tags.php';
require SENA_DIR . '/inc/customizer.php';
require SENA_DIR . '/inc/events.php';

if ( class_exists( 'WooCommerce' ) ) {
	require SENA_DIR . '/inc/woocommerce.php';
}

/**
 * Theme supports, menus and image sizes.
 */
function sena_setup(): void {
	load_theme_textdomain( 'sena-crafted', SENA_DIR . '/languages' );

	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script', 'navigation-widgets' ) );
	add_theme_support(
		'custom-logo',
		array(
			'height'      => 400,
			'width'       => 500,
			'flex-height' => true,
			'flex-width'  => true,
		)
	);

	add_theme_support(
		'woocommerce',
		array(
			'thumbnail_image_width' => 800,
			'single_image_width'    => 1200,
			'product_grid'          => array(
				'default_columns' => 4,
				'min_columns'     => 2,
				'max_columns'     => 4,
			),
		)
	);
	add_theme_support( 'wc-product-gallery-zoom' );
	add_theme_support( 'wc-product-gallery-lightbox' );
	add_theme_support( 'wc-product-gallery-slider' );

	register_nav_menus(
		array(
			'primary' => __( 'Primary (header)', 'sena-crafted' ),
			'footer'  => __( 'Footer', 'sena-crafted' ),
		)
	);

	// 4:5 card tile, hover tile, and lightbox thumbnails.
	add_image_size( 'sena-card', 800, 1000, true );
	add_image_size( 'sena-thumb', 160, 200, true );
	add_image_size( 'sena-about', 900, 1100, false );
}
add_action( 'after_setup_theme', 'sena_setup' );

/**
 * Front-end assets.
 */
function sena_enqueue_assets(): void {
	$css_ver = (string) filemtime( SENA_DIR . '/assets/css/main.css' );
	$js_ver  = (string) filemtime( SENA_DIR . '/assets/js/main.js' );

	wp_enqueue_style(
		'sena-fonts',
		'https://fonts.googleapis.com/css2?family=Figtree:wght@400;500;600;700&display=swap',
		array(),
		null // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
	);
	wp_enqueue_style( 'sena-main', SENA_URI . '/assets/css/main.css', array( 'sena-fonts' ), $css_ver );

	wp_enqueue_script( 'sena-main', SENA_URI . '/assets/js/main.js', array(), $js_ver, array( 'strategy' => 'defer', 'in_footer' => true ) );
	wp_localize_script(
		'sena-main',
		'senaConfig',
		array(
			'i18n' => array(
				'photoOf' => __( '%1$s, photo %2$s of %3$s', 'sena-crafted' ),
				'photo'   => __( 'Photo %s', 'sena-crafted' ),
			),
		)
	);

	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
}
add_action( 'wp_enqueue_scripts', 'sena_enqueue_assets' );

/**
 * Preload the handwriting font and preconnect to Google Fonts.
 */
function sena_resource_hints(): void {
	echo '<link rel="preload" href="' . esc_url( SENA_URI . '/fonts/SenaHandwriting-Regular.ttf' ) . '" as="font" type="font/ttf" crossorigin>' . "\n";
	echo '<link rel="preconnect" href="https://fonts.googleapis.com">' . "\n";
	echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' . "\n";
}
add_action( 'wp_head', 'sena_resource_hints', 1 );

/**
 * Use the theme logo as a favicon when no site icon is set.
 */
function sena_fallback_favicon(): void {
	if ( ! has_site_icon() ) {
		echo '<link rel="icon" href="' . esc_url( SENA_URI . '/assets/img/logo.png' ) . '">' . "\n";
	}
}
add_action( 'wp_head', 'sena_fallback_favicon', 2 );

/**
 * Body classes.
 */
function sena_body_class( array $classes ): array {
	$classes[] = 'sena';
	if ( is_front_page() ) {
		$classes[] = 'sena-front';
	}
	return $classes;
}
add_filter( 'body_class', 'sena_body_class' );

/**
 * Slightly longer excerpts for cards.
 */
function sena_excerpt_more( string $more ): string {
	return '…';
}
add_filter( 'excerpt_more', 'sena_excerpt_more' );

/**
 * Register editor styles so the block editor uses the palette.
 */
function sena_editor_styles(): void {
	add_theme_support( 'editor-styles' );
	add_editor_style( 'assets/css/main.css' );
}
add_action( 'after_setup_theme', 'sena_editor_styles' );

/**
 * Customizer live-preview helper is not needed; keep refresh transport.
 *
 * Admin notice when WooCommerce is missing (the theme still works, the shop is just empty).
 */
function sena_wc_missing_notice(): void {
	if ( class_exists( 'WooCommerce' ) || ! current_user_can( 'activate_plugins' ) ) {
		return;
	}
	echo '<div class="notice notice-warning"><p>' .
		esc_html__( 'Sena Crafted: WooCommerce is not active, so the shop section on the front page will be empty.', 'sena-crafted' ) .
		'</p></div>';
}
add_action( 'admin_notices', 'sena_wc_missing_notice' );
