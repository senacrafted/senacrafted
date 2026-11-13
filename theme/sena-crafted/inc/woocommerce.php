<?php
/**
 * WooCommerce integration: hooks, wrappers and the product card used everywhere.
 * Only loaded when WooCommerce is active.
 *
 * @package Sena_Crafted
 */

defined( 'ABSPATH' ) || exit;

/* ── Wrappers, breadcrumbs, sidebar ─────────────────────────── */

remove_action( 'woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10 );
remove_action( 'woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10 );
remove_action( 'woocommerce_before_main_content', 'woocommerce_breadcrumb', 20 );
remove_action( 'woocommerce_sidebar', 'woocommerce_get_sidebar', 10 );

// The shop has its own filter pills, so hide result count and ordering dropdown.
remove_action( 'woocommerce_before_shop_loop', 'woocommerce_result_count', 20 );
remove_action( 'woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 30 );

// Loop item defaults are replaced by the theme card (content-product.php).
remove_action( 'woocommerce_before_shop_loop_item', 'woocommerce_template_loop_product_link_open', 10 );
remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_product_link_close', 5 );
remove_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_show_product_loop_sale_flash', 10 );
remove_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_template_loop_product_thumbnail', 10 );
remove_action( 'woocommerce_shop_loop_item_title', 'woocommerce_template_loop_product_title', 10 );
remove_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_rating', 5 );
remove_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_price', 10 );
remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10 );

// Single product: no tabs (description is rendered inline), related products titled "You may also like".
remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_product_data_tabs', 10 );
// Mirror the original lightbox: title, price, button. No excerpt (it duplicated the description), no SKU/category meta.
remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_excerpt', 20 );
remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_meta', 40 );
remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_rating', 10 );
add_filter( 'woocommerce_product_related_products_heading', static fn() => __( 'You may also like', 'sena-crafted' ) );
add_filter(
	'woocommerce_output_related_products_args',
	static function ( array $args ): array {
		$args['posts_per_page'] = 4;
		$args['columns']        = 4;
		return $args;
	}
);
add_filter( 'woocommerce_upsell_display_args', static function ( array $args ): array { $args['columns'] = 4; return $args; } );

/**
 * Wrapper for the generic WooCommerce pages (cart, checkout, account, product archives).
 */
function sena_wc_wrapper_start(): void {
	echo '<main id="top" class="site-main"><div class="section wc-section">';
}
function sena_wc_wrapper_end(): void {
	echo '</div></main>';
}
add_action( 'woocommerce_before_main_content', 'sena_wc_wrapper_start', 5 );
add_action( 'woocommerce_after_main_content', 'sena_wc_wrapper_end', 50 );

/**
 * Products per page on the shop archive.
 */
add_filter( 'loop_shop_per_page', static fn() => 24 );
add_filter( 'loop_shop_columns', static fn() => 4 );

/**
 * Gallery thumbnails sized for the theme.
 */
add_filter( 'woocommerce_gallery_thumbnail_size', static fn() => 'sena-thumb' );

/**
 * Cart fragments: update the header cart count after an AJAX add-to-cart.
 */
function sena_cart_fragment( array $fragments ): array {
	$fragments['.nav-cart'] = sena_cart_link( false );
	return $fragments;
}
add_filter( 'woocommerce_add_to_cart_fragments', 'sena_cart_fragment' );

/**
 * Header cart link with count.
 */
function sena_cart_link( bool $echo = true ): string {
	if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
		return '';
	}
	$count = (int) WC()->cart->get_cart_contents_count();
	$label = $count > 0
		/* translators: %d: number of items in the cart */
		? sprintf( _n( 'Cart (%d)', 'Cart (%d)', $count, 'sena-crafted' ), $count )
		: __( 'Cart', 'sena-crafted' );
	$html = sprintf(
		'<a class="nav-cart%s" href="%s" aria-label="%s">%s</a>',
		$count > 0 ? ' has-items' : '',
		esc_url( wc_get_cart_url() ),
		esc_attr( $label ),
		esc_html( $label )
	);
	if ( $echo ) {
		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above.
	}
	return $html;
}

/* ── Product status helpers ─────────────────────────────────── */

/**
 * Theme status for a product: sold | custom | showcase | available.
 */
function sena_product_status( WC_Product $product ): string {
	$meta = (string) $product->get_meta( '_sena_status', true );
	if ( 'custom' === $meta || 'showcase' === $meta ) {
		return $meta;
	}
	if ( 'sold' === $meta || ! $product->is_in_stock() ) {
		return 'sold';
	}
	return 'available';
}

/**
 * All gallery image IDs, main image first.
 *
 * @return int[]
 */
function sena_product_image_ids( WC_Product $product ): array {
	$ids = array();
	if ( $product->get_image_id() ) {
		$ids[] = (int) $product->get_image_id();
	}
	foreach ( $product->get_gallery_image_ids() as $id ) {
		$ids[] = (int) $id;
	}
	return array_values( array_unique( array_filter( $ids ) ) );
}

/**
 * Price markup (wc_price-based, ranges for variable products, "+" for made-to-order).
 */
function sena_product_price_html( WC_Product $product, string $status ): string {
	$html = $product->get_price_html();
	if ( '' === $html ) {
		return '<span></span>';
	}
	if ( 'custom' === $status ) {
		$html .= '+';
	}
	return '<span class="price">' . $html . '</span>';
}

/**
 * The action button for a card / lightbox.
 */
function sena_product_action_html( WC_Product $product, string $status ): string {
	$title = $product->get_name();

	if ( 'sold' === $status ) {
		return sprintf(
			'<a class="btn small ghost" href="%s">%s</a>',
			esc_url( sena_mailto( sprintf( 'About: %s', $title ), sprintf( "Hi! I'm interested in something similar to \"%s\".\n\n", $title ) ) ),
			esc_html__( 'Ask about similar', 'sena-crafted' )
		);
	}
	if ( 'showcase' === $status ) {
		return '<span class="muted small-note">' . esc_html__( 'Showcase piece', 'sena-crafted' ) . '</span>';
	}
	if ( 'custom' === $status ) {
		return sprintf( '<a class="btn small" href="%s">%s</a>', esc_url( sena_custom_order_url() ), esc_html__( 'Order one', 'sena-crafted' ) );
	}

	if ( $product->is_purchasable() && $product->is_in_stock() ) {
		if ( $product->is_type( 'simple' ) && $product->supports( 'ajax_add_to_cart' ) ) {
			return sprintf(
				'<a href="%1$s" data-quantity="1" class="btn small add_to_cart_button ajax_add_to_cart" data-product_id="%2$d" data-product_sku="%3$s" aria-label="%4$s" rel="nofollow">%5$s</a>',
				esc_url( $product->add_to_cart_url() ),
				$product->get_id(),
				esc_attr( $product->get_sku() ),
				/* translators: %s: product name */
				esc_attr( sprintf( __( 'Add “%s” to your cart', 'sena-crafted' ), $title ) ),
				esc_html__( 'Add to cart', 'sena-crafted' )
			);
		}
		return sprintf( '<a class="btn small" href="%s">%s</a>', esc_url( $product->get_permalink() ), esc_html__( 'View', 'sena-crafted' ) );
	}

	$etsy = (string) $product->get_meta( '_etsy_listing_url', true );
	if ( '' !== $etsy ) {
		return sprintf( '<a class="btn small" href="%s" target="_blank" rel="noopener">%s</a>', esc_url( $etsy ), esc_html__( 'Buy on Etsy', 'sena-crafted' ) );
	}

	return sprintf(
		'<a class="btn small ghost" href="%s">%s</a>',
		esc_url( sena_mailto( sprintf( 'About: %s', $title ), sprintf( "Hi! I'm interested in \"%s\".\n\n", $title ) ) ),
		esc_html__( 'Ask about it', 'sena-crafted' )
	);
}

/**
 * Badge markup.
 */
function sena_product_badge_html( string $status ): string {
	if ( 'sold' === $status ) {
		return '<span class="badge sold">' . esc_html__( 'Sold', 'sena-crafted' ) . '</span>';
	}
	if ( 'custom' === $status ) {
		return '<span class="badge">' . esc_html__( 'Made to order', 'sena-crafted' ) . '</span>';
	}
	return '';
}

/**
 * Category slugs for filtering (the product's categories plus all their ancestors).
 *
 * @return string[]
 */
function sena_product_filter_slugs( WC_Product $product ): array {
	$slugs = array();
	$terms = get_the_terms( $product->get_id(), 'product_cat' );
	if ( is_array( $terms ) ) {
		foreach ( $terms as $term ) {
			$slugs[] = $term->slug;
			foreach ( get_ancestors( $term->term_id, 'product_cat', 'taxonomy' ) as $anc_id ) {
				$anc = get_term( $anc_id, 'product_cat' );
				if ( $anc instanceof WP_Term ) {
					$slugs[] = $anc->slug;
				}
			}
		}
	}
	return array_values( array_unique( $slugs ) );
}

/**
 * Plain-text paragraphs of the long description (short description as fallback).
 *
 * @return string[]
 */
function sena_product_paragraphs( WC_Product $product ): array {
	$text = $product->get_description();
	if ( '' === trim( wp_strip_all_tags( $text ) ) ) {
		$text = $product->get_short_description();
	}
	$text = html_entity_decode( wp_strip_all_tags( str_replace( array( '</p>', '<br>', '<br />', '<br/>' ), "\n\n", $text ) ), ENT_QUOTES, 'UTF-8' );
	return sena_paragraphs( $text );
}

/**
 * Data the lightbox needs, JSON-encoded in a data attribute.
 */
function sena_product_lightbox_data( WC_Product $product, string $status ): array {
	$photos = array();
	foreach ( sena_product_image_ids( $product ) as $id ) {
		$full  = wp_get_attachment_image_src( $id, 'large' );
		$thumb = wp_get_attachment_image_src( $id, 'sena-thumb' );
		if ( ! $full ) {
			continue;
		}
		$photos[] = array(
			'src'   => $full[0],
			'thumb' => $thumb ? $thumb[0] : $full[0],
		);
	}
	return array(
		'title'  => $product->get_name(),
		'photos' => $photos,
		'paras'  => sena_product_paragraphs( $product ),
		'price'  => 'sold' === $status ? '<span class="price">' . esc_html__( 'Sold', 'sena-crafted' ) . '</span>' : sena_product_price_html( $product, $status ),
		'action' => sena_product_action_html( $product, $status ),
		'status' => $status,
		'url'    => $product->get_permalink(),
	);
}

/**
 * Render one product card (used on the front page, shop archive and related products).
 */
function sena_product_card( WC_Product $product ): void {
	$status = sena_product_status( $product );
	$ids    = sena_product_image_ids( $product );
	$title  = $product->get_name();
	$paras  = sena_product_paragraphs( $product );
	$cats   = sena_product_filter_slugs( $product );
	$data   = sena_product_lightbox_data( $product, $status );
	$count  = count( $ids );

	$classes = array( 'card', 'product-card' );
	if ( 'sold' === $status ) {
		$classes[] = 'is-sold';
	}
	?>
	<article class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>" data-cats="<?php echo esc_attr( implode( ' ', $cats ) ); ?>" data-product="<?php echo esc_attr( (string) $product->get_id() ); ?>">
		<div class="media" role="button" tabindex="0"
			aria-label="<?php echo esc_attr( sprintf( /* translators: %s: product name */ __( 'See photos of %s', 'sena-crafted' ), $title ) ); ?>"
			data-lightbox="<?php echo esc_attr( wp_json_encode( $data ) ); ?>">
			<?php
			if ( $count > 0 ) {
				echo wp_get_attachment_image( $ids[0], 'sena-card', false, array( 'alt' => $title, 'loading' => 'lazy', 'decoding' => 'async' ) );
				if ( isset( $ids[1] ) ) {
					echo wp_get_attachment_image( $ids[1], 'sena-card', false, array( 'class' => 'alt', 'alt' => '', 'loading' => 'lazy', 'decoding' => 'async', 'aria-hidden' => 'true' ) );
				}
				if ( $count > 1 ) {
					/* translators: %d: number of photos */
					printf( '<span class="count">%s</span>', esc_html( sprintf( _n( '%d photo', '%d photos', $count, 'sena-crafted' ), $count ) ) );
				}
			} else {
				printf(
					'<div class="placeholder"><div>%s<small>%s</small></div></div>',
					esc_html( $title ),
					esc_html__( 'Photo coming', 'sena-crafted' )
				);
			}
			echo sena_product_badge_html( $status ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			?>
		</div>
		<div class="info">
			<h3><a href="<?php echo esc_url( $product->get_permalink() ); ?>"><?php echo esc_html( $title ); ?></a></h3>
			<?php if ( ! empty( $paras[0] ) ) : ?>
				<p class="desc"><span><?php echo esc_html( $paras[0] ); ?></span></p>
			<?php endif; ?>
			<div class="row">
				<?php
				echo sena_product_price_html( $product, $status ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo sena_product_action_html( $product, $status ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				?>
			</div>
		</div>
	</article>
	<?php
}

/**
 * Filter pills: All + each top-level product category that has products.
 */
function sena_product_filters(): void {
	$terms = get_terms(
		array(
			'taxonomy'   => 'product_cat',
			'hide_empty' => true,
			'parent'     => 0,
			'orderby'    => 'name',
		)
	);
	if ( is_wp_error( $terms ) ) {
		$terms = array();
	}
	$terms = array_filter( $terms, static fn( WP_Term $t ) => 'uncategorized' !== $t->slug );
	?>
	<div class="filters" role="group" aria-label="<?php esc_attr_e( 'Filter products', 'sena-crafted' ); ?>">
		<button type="button" class="filter active" data-filter="all" aria-pressed="true"><?php esc_html_e( 'All', 'sena-crafted' ); ?></button>
		<?php foreach ( $terms as $term ) : ?>
			<button type="button" class="filter" data-filter="<?php echo esc_attr( $term->slug ); ?>" aria-pressed="false"><?php echo esc_html( $term->name ); ?></button>
		<?php endforeach; ?>
	</div>
	<?php
}

/**
 * All published, catalog-visible products, newest first.
 *
 * @return WC_Product[]
 */
function sena_front_page_products(): array {
	return wc_get_products(
		array(
			'status'     => 'publish',
			'limit'      => -1,
			'orderby'    => 'date',
			'order'      => 'DESC',
			'visibility' => 'catalog',
		)
	);
}

/**
 * Single product: "Sold" / "Made to order" notes next to the price.
 */
function sena_single_status_note(): void {
	global $product;
	if ( ! $product instanceof WC_Product ) {
		return;
	}
	$status = sena_product_status( $product );
	if ( 'custom' === $status ) {
		printf(
			'<p class="status-note">%s <a class="btn small" href="%s">%s</a></p>',
			esc_html__( 'This piece is made to order.', 'sena-crafted' ),
			esc_url( sena_custom_order_url() ),
			esc_html__( 'Order one', 'sena-crafted' )
		);
	} elseif ( 'showcase' === $status ) {
		echo '<p class="status-note muted">' . esc_html__( 'Showcase piece — shown, not for sale.', 'sena-crafted' ) . '</p>';
	} elseif ( 'sold' === $status ) {
		printf(
			'<p class="status-note"><span class="badge sold">%s</span> <a class="btn small ghost" href="%s">%s</a></p>',
			esc_html__( 'Sold', 'sena-crafted' ),
			esc_url( sena_mailto( sprintf( 'About: %s', $product->get_name() ) ) ),
			esc_html__( 'Ask about similar', 'sena-crafted' )
		);
	}
}
add_action( 'woocommerce_single_product_summary', 'sena_single_status_note', 25 );

/**
 * Hide the add-to-cart form for showcase pieces.
 */
add_filter(
	'woocommerce_is_purchasable',
	static function ( bool $purchasable, WC_Product $product ): bool {
		return 'showcase' === (string) $product->get_meta( '_sena_status', true ) ? false : $purchasable;
	},
	10,
	2
);

/**
 * Checkout: keep it clean. Remove the "order notes" clutter? No — keep, but drop the company field.
 */
add_filter(
	'woocommerce_checkout_fields',
	static function ( array $fields ): array {
		unset( $fields['billing']['billing_company'], $fields['shipping']['shipping_company'] );
		return $fields;
	}
);
