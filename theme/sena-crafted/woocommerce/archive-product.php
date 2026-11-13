<?php
/**
 * Shop / product category archive: filter pills + card grid.
 *
 * @package Sena_Crafted
 * @version 8.6.0
 */

defined( 'ABSPATH' ) || exit;

get_header( 'shop' );

/**
 * Hook: woocommerce_before_main_content (theme wrapper, no breadcrumbs).
 */
do_action( 'woocommerce_before_main_content' );
?>
<div class="section-head shop-head">
	<h1 class="page-title"><?php woocommerce_page_title(); ?></h1>
	<?php
	if ( is_shop() ) {
		sena_product_filters();
	}
	?>
</div>
<?php
/**
 * Hook: woocommerce_archive_description.
 */
do_action( 'woocommerce_archive_description' );

if ( woocommerce_product_loop() ) {

	do_action( 'woocommerce_before_shop_loop' );

	woocommerce_product_loop_start();

	if ( wc_get_loop_prop( 'total' ) ) {
		while ( have_posts() ) {
			the_post();
			do_action( 'woocommerce_shop_loop' );
			wc_get_template_part( 'content', 'product' );
		}
	}

	woocommerce_product_loop_end();

	do_action( 'woocommerce_after_shop_loop' );
} else {
	do_action( 'woocommerce_no_products_found' );
}

do_action( 'woocommerce_after_main_content' );

get_footer( 'shop' );
