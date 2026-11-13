<?php
/**
 * Product card in loops (shop archive, categories, related products, upsells).
 *
 * @package Sena_Crafted
 * @version 9.4.0
 */

defined( 'ABSPATH' ) || exit;

global $product;

if ( ! $product instanceof WC_Product || ! $product->is_visible() ) {
	return;
}

sena_product_card( $product );
