<?php
/**
 * Small helpers used by the templates.
 *
 * @package Sena_Crafted
 */

defined( 'ABSPATH' ) || exit;

/**
 * URL of a theme image.
 */
function sena_img( string $file ): string {
	return SENA_URI . '/assets/img/' . ltrim( $file, '/' );
}

/**
 * Print a decorative doodle image (stars, leaves, car…).
 */
function sena_doodle( string $file, string $class = '', bool $eager = false ): void {
	printf(
		'<img class="doodle %1$s" src="%2$s" alt="" aria-hidden="true" loading="%3$s" decoding="async">',
		esc_attr( $class ),
		esc_url( sena_img( 'drawings/' . $file ) ),
		$eager ? 'eager' : 'lazy'
	);
}

/**
 * Build a mailto: link to the shop owner.
 */
function sena_mailto( string $subject, string $body = '' ): string {
	$email = sena_get_option( 'email' );
	$query = 'subject=' . rawurlencode( $subject );
	if ( '' !== $body ) {
		$query .= '&body=' . rawurlencode( $body );
	}
	return 'mailto:' . $email . '?' . $query;
}

/**
 * Absolute URL of the custom order page.
 */
function sena_custom_order_url(): string {
	$path = sena_get_option( 'custom_url' );
	if ( str_starts_with( $path, '/' ) ) {
		return home_url( $path );
	}
	return $path;
}

/**
 * Split text into paragraphs on blank lines.
 *
 * @return string[]
 */
function sena_paragraphs( string $text ): array {
	$parts = preg_split( '/\n\s*\n/', str_replace( "\r", '', $text ) ) ?: array();
	return array_values( array_filter( array_map( 'trim', $parts ), 'strlen' ) );
}

/**
 * Echo paragraphs as <p> tags.
 */
function sena_the_paragraphs( string $text, string $class = '' ): void {
	foreach ( sena_paragraphs( $text ) as $p ) {
		printf( '<p%s>%s</p>', $class ? ' class="' . esc_attr( $class ) . '"' : '', esc_html( $p ) );
	}
}

/**
 * Anchor link that works from any page (hash on the front page, home URL + hash elsewhere).
 */
function sena_anchor( string $hash ): string {
	return is_front_page() ? '#' . $hash : home_url( '/#' . $hash );
}

/**
 * Fallback for the primary menu when none is assigned.
 */
function sena_fallback_menu(): void {
	$items = array(
		'work'    => __( 'Shop', 'sena-crafted' ),
		'custom'  => __( 'Custom', 'sena-crafted' ),
		'events'  => __( 'Find me', 'sena-crafted' ),
		'about'   => __( 'About', 'sena-crafted' ),
		'contact' => __( 'Contact', 'sena-crafted' ),
	);
	foreach ( $items as $hash => $label ) {
		printf( '<a href="%s">%s</a>', esc_url( sena_anchor( $hash ) ), esc_html( $label ) );
	}
}

/**
 * About photo: customizer URL + alt (from the media library when possible).
 */
function sena_about_photo( string $key, string $default_alt, string $class = '' ): void {
	$url = sena_get_option( $key );
	if ( '' === $url ) {
		return;
	}
	$id = attachment_url_to_postid( $url );
	if ( $id ) {
		$alt = (string) get_post_meta( $id, '_wp_attachment_image_alt', true );
		echo wp_get_attachment_image(
			$id,
			'sena-about',
			false,
			array(
				'class'   => $class,
				'alt'     => '' !== $alt ? $alt : $default_alt,
				'loading' => 'lazy',
			)
		);
		return;
	}
	printf(
		'<img%s src="%s" alt="%s" loading="lazy" decoding="async">',
		$class ? ' class="' . esc_attr( $class ) . '"' : '',
		esc_url( $url ),
		esc_attr( $default_alt )
	);
}
