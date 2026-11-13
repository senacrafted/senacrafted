<?php
/**
 * Renders a menu as bare <a> tags (no <ul>/<li>), matching the original markup.
 *
 * @package Sena_Crafted
 */

defined( 'ABSPATH' ) || exit;

class Sena_Flat_Walker extends Walker_Nav_Menu {

	public function start_lvl( &$output, $depth = 0, $args = null ) {}
	public function end_lvl( &$output, $depth = 0, $args = null ) {}

	public function start_el( &$output, $item, $depth = 0, $args = null, $id = 0 ) {
		$classes = array();
		if ( in_array( 'current-menu-item', (array) $item->classes, true ) ) {
			$classes[] = 'current';
		}
		$atts = array(
			'href'   => ! empty( $item->url ) ? $item->url : '',
			'class'  => implode( ' ', $classes ),
			'target' => ! empty( $item->target ) ? $item->target : '',
			'rel'    => ! empty( $item->xfn ) ? $item->xfn : '',
		);
		if ( '_blank' === $atts['target'] && '' === $atts['rel'] ) {
			$atts['rel'] = 'noopener';
		}
		$attr_html = '';
		foreach ( $atts as $attr => $value ) {
			if ( '' !== $value ) {
				$attr_html .= ' ' . $attr . '="' . ( 'href' === $attr ? esc_url( $value ) : esc_attr( $value ) ) . '"';
			}
		}
		$title   = apply_filters( 'the_title', $item->title, $item->ID );
		$output .= '<a' . $attr_html . '>' . esc_html( $title ) . '</a>';
	}

	public function end_el( &$output, $item, $depth = 0, $args = null ) {}
}
