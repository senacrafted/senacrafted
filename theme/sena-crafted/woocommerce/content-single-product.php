<?php
/**
 * Single product: gallery left, summary right, description, "You may also like".
 *
 * @package Sena_Crafted
 * @version 3.6.0
 */

defined( 'ABSPATH' ) || exit;

global $product;

do_action( 'woocommerce_before_single_product' );

if ( post_password_required() ) {
	echo get_the_password_form(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	return;
}
?>
<div id="product-<?php the_ID(); ?>" <?php wc_product_class( 'product-page', $product ); ?>>

	<div class="product-top">
		<div class="product-gallery">
			<?php
			/**
			 * Hook: woocommerce_before_single_product_summary (sale flash, images).
			 */
			do_action( 'woocommerce_before_single_product_summary' );
			?>
		</div>

		<div class="summary entry-summary">
			<?php
			/**
			 * Hook: woocommerce_single_product_summary
			 * title 5, rating 10, price 10, excerpt 20, status note 25, add to cart 30, meta 40, sharing 50.
			 */
			do_action( 'woocommerce_single_product_summary' );
			?>
		</div>
	</div>

	<?php
	$sena_description = $product->get_description();
	if ( '' !== trim( wp_strip_all_tags( $sena_description ) ) ) :
		?>
		<div class="product-description prose">
			<h2><?php esc_html_e( 'About this piece', 'sena-crafted' ); ?></h2>
			<?php echo wp_kses_post( wpautop( do_shortcode( $sena_description ) ) ); ?>
		</div>
	<?php endif; ?>

	<?php
	/**
	 * Hook: woocommerce_after_single_product_summary (upsells 15, related 20).
	 */
	do_action( 'woocommerce_after_single_product_summary' );
	?>
</div>

<?php
do_action( 'woocommerce_after_single_product' );
