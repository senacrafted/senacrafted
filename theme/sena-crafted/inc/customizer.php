<?php
/**
 * Customizer settings: all the site-wide copy that used to live in works.js (SITE).
 *
 * @package Sena_Crafted
 */

defined( 'ABSPATH' ) || exit;

/**
 * Default values (mirrors the old SITE object).
 */
function sena_defaults(): array {
	return array(
		'announcement' => __( 'Handmade in Georgia · Custom orders open', 'sena-crafted' ),
		'tagline'      => __( 'Nature paintings and handmade leather crafts — made with warmth, nostalgia, and a little bit of magic.', 'sena-crafted' ),
		'about'        => __( "Ever since I was a kid, I've loved creating with my hands. Growing up in Turkey, I knew I wanted to pursue my passion for art, so I came to the USA and studied Computer Animation! It taught me the professional pipeline and digital art, and now I channel what I've learned into leatherworking, painting, and drawing.\n\nMy work draws on childhood memories, nature, and everyday life, so no two pieces are exactly alike. You'll find me at festivals and markets around Georgia, and if you see me, come say hi, or reach out for a custom piece!", 'sena-crafted' ),
		'about_title'  => __( "Hi, I'm Sena!", 'sena-crafted' ),
		'custom'       => __( "I'm open to custom orders, so please reach out if you're thinking of something specific! Whether it's something I've made before or something unique, I'd be more than happy to bring your vision to life.", 'sena-crafted' ),
		'hello'        => __( "Questions, comments, or just want to reach out?\nShoot me a message!", 'sena-crafted' ),
		'email'        => 'senacrafted@gmail.com',
		'instagram'    => 'senacrafted',
		'tiktok'       => 'senacrafted',
		'location'     => __( 'Georgia, USA', 'sena-crafted' ),
		'about_img_1'  => SENA_URI . '/assets/img/about/sena.jpg',
		'about_img_2'  => SENA_URI . '/assets/img/about/bts-workbench.jpg',
		'about_img_3'  => SENA_URI . '/assets/img/about/bts-crow-eyes.jpg',
		'custom_url'   => '/custom-order/',
	);
}

/**
 * Read a theme option with its default.
 */
function sena_get_option( string $key ): string {
	$defaults = sena_defaults();
	$default  = $defaults[ $key ] ?? '';
	$value    = get_theme_mod( 'sena_' . $key, $default );
	return is_string( $value ) ? $value : (string) $default;
}

/**
 * Sanitize a social handle (no @, no URL).
 */
function sena_sanitize_handle( $value ): string {
	$value = ltrim( trim( (string) $value ), '@' );
	return preg_replace( '/[^A-Za-z0-9._\-]/', '', $value ) ?? '';
}

/**
 * Register everything in a single "Sena Crafted" panel.
 */
function sena_customize_register( WP_Customize_Manager $wp_customize ): void {
	$defaults = sena_defaults();

	$wp_customize->add_panel(
		'sena_panel',
		array(
			'title'       => __( 'Sena Crafted', 'sena-crafted' ),
			'description' => __( 'Site-wide text, contact details and about photos.', 'sena-crafted' ),
			'priority'    => 10,
		)
	);

	$sections = array(
		'sena_text'    => __( 'Announcement & tagline', 'sena-crafted' ),
		'sena_about'   => __( 'About section', 'sena-crafted' ),
		'sena_custom'  => __( 'Custom orders', 'sena-crafted' ),
		'sena_contact' => __( 'Contact & socials', 'sena-crafted' ),
	);
	foreach ( $sections as $id => $title ) {
		$wp_customize->add_section( $id, array( 'title' => $title, 'panel' => 'sena_panel' ) );
	}

	$fields = array(
		'announcement' => array( 'sena_text', 'text', __( 'Announcement bar', 'sena-crafted' ), __( 'Thin bar at the very top. Leave empty to hide it.', 'sena-crafted' ), 'sanitize_text_field' ),
		'tagline'      => array( 'sena_text', 'text', __( 'Tagline', 'sena-crafted' ), __( 'Used for the hero heading text read by search engines and screen readers.', 'sena-crafted' ), 'sanitize_text_field' ),
		'about_title'  => array( 'sena_about', 'text', __( 'About heading', 'sena-crafted' ), '', 'sanitize_text_field' ),
		'about'        => array( 'sena_about', 'textarea', __( 'About paragraphs', 'sena-crafted' ), __( 'Leave an empty line between paragraphs.', 'sena-crafted' ), 'sanitize_textarea_field' ),
		'custom'       => array( 'sena_custom', 'textarea', __( 'Custom orders text', 'sena-crafted' ), '', 'sanitize_textarea_field' ),
		'custom_url'   => array( 'sena_custom', 'text', __( 'Custom order page URL', 'sena-crafted' ), __( 'Relative path (e.g. /custom-order/) or a full URL.', 'sena-crafted' ), 'sena_sanitize_path' ),
		'hello'        => array( 'sena_contact', 'textarea', __( 'Hello text', 'sena-crafted' ), __( 'Line breaks are kept.', 'sena-crafted' ), 'sanitize_textarea_field' ),
		'email'        => array( 'sena_contact', 'email', __( 'Email address', 'sena-crafted' ), '', 'sanitize_email' ),
		'instagram'    => array( 'sena_contact', 'text', __( 'Instagram handle', 'sena-crafted' ), __( 'Without the @. Leave empty to hide the button.', 'sena-crafted' ), 'sena_sanitize_handle' ),
		'tiktok'       => array( 'sena_contact', 'text', __( 'TikTok handle', 'sena-crafted' ), __( 'Without the @. Leave empty to hide the button.', 'sena-crafted' ), 'sena_sanitize_handle' ),
		'location'     => array( 'sena_contact', 'text', __( 'Location (footer)', 'sena-crafted' ), '', 'sanitize_text_field' ),
	);

	foreach ( $fields as $key => [ $section, $type, $label, $description, $sanitize ] ) {
		$wp_customize->add_setting(
			'sena_' . $key,
			array(
				'default'           => $defaults[ $key ],
				'sanitize_callback' => $sanitize,
				'transport'         => 'refresh',
			)
		);
		$wp_customize->add_control(
			'sena_' . $key,
			array(
				'section'     => $section,
				'type'        => $type,
				'label'       => $label,
				'description' => $description,
			)
		);
	}

	$images = array(
		'about_img_1' => __( 'Photo 1 (big, left)', 'sena-crafted' ),
		'about_img_2' => __( 'Photo 2 (top right)', 'sena-crafted' ),
		'about_img_3' => __( 'Photo 3 (bottom right)', 'sena-crafted' ),
	);
	foreach ( $images as $key => $label ) {
		$wp_customize->add_setting(
			'sena_' . $key,
			array(
				'default'           => $defaults[ $key ],
				'sanitize_callback' => 'esc_url_raw',
				'transport'         => 'refresh',
			)
		);
		$wp_customize->add_control(
			new WP_Customize_Image_Control(
				$wp_customize,
				'sena_' . $key,
				array(
					'section' => 'sena_about',
					'label'   => $label,
				)
			)
		);
	}
}
add_action( 'customize_register', 'sena_customize_register' );

/**
 * Allow a relative path or an absolute URL.
 */
function sena_sanitize_path( $value ): string {
	$value = trim( (string) $value );
	if ( '' === $value ) {
		return '/custom-order/';
	}
	if ( str_starts_with( $value, '/' ) ) {
		return '/' . ltrim( sanitize_text_field( $value ), '/' );
	}
	return esc_url_raw( $value );
}
