<?php
/**
 * Pieces (WooCommerce products): grid, quick edits and the simplified "Add a piece" form.
 *
 * @package SenaStudio
 */

defined( 'ABSPATH' ) || exit;

final class Sena_Studio_Products {

	public const META_STATUS = '_sena_status';
	public const META_ETSY   = '_etsy_listing_url';

	public static function init(): void {
		add_action( 'admin_post_sena_studio_product_quick', array( __CLASS__, 'handle_quick' ) );
		add_action( 'admin_post_sena_studio_add_piece', array( __CLASS__, 'handle_add_piece' ) );
	}

	public static function statuses(): array {
		return array(
			'available'     => __( 'Available', 'sena-studio' ),
			'sold'          => __( 'Sold', 'sena-studio' ),
			'made_to_order' => __( 'Made to order', 'sena-studio' ),
			'showcase'      => __( 'Showcase', 'sena-studio' ),
		);
	}

	/**
	 * Derive the studio status for a product from meta + stock.
	 */
	public static function status( WC_Product $product ): string {
		$meta = (string) $product->get_meta( self::META_STATUS );
		if ( isset( self::statuses()[ $meta ] ) ) {
			if ( 'available' === $meta && ! $product->is_in_stock() ) {
				return 'sold';
			}
			return $meta;
		}
		if ( ! $product->is_in_stock() ) {
			return 'sold';
		}
		if ( $product->managing_stock() === false && ! $product->get_sold_individually() ) {
			return 'made_to_order';
		}
		return 'available';
	}

	/**
	 * Products with stock <= 1 that are not sold and not one-of-a-kind (where 1 is expected).
	 *
	 * @return WC_Product[]
	 */
	public static function low_stock( int $limit = 20 ): array {
		$products = wc_get_products(
			array(
				'limit'        => -1,
				'status'       => 'publish',
				'stock_status' => 'instock',
				'manage_stock' => true,
				'orderby'      => 'date',
				'order'        => 'DESC',
			)
		);
		$low = array();
		foreach ( $products as $product ) {
			$qty = $product->get_stock_quantity();
			if ( null === $qty || $qty > 1 ) {
				continue;
			}
			if ( 'sold' === self::status( $product ) ) {
				continue;
			}
			if ( $product->get_sold_individually() && 1 === (int) $qty ) {
				continue; // one-of-a-kind, 1 is expected.
			}
			$low[] = $product;
			if ( count( $low ) >= $limit ) {
				break;
			}
		}
		return $low;
	}

	/* ---------------- Tab ---------------- */

	public static function render_tab(): void {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$filter = isset( $_GET['filter'] ) ? sanitize_key( wp_unslash( $_GET['filter'] ) ) : 'all';
		$search = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
		$add    = ! empty( $_GET['add'] );
		// phpcs:enable

		$args = array(
			'limit'   => 80,
			'status'  => array( 'publish', 'draft', 'private' ),
			'orderby' => 'date',
			'order'   => 'DESC',
		);
		if ( 'low' === $filter ) {
			$products = self::low_stock( 80 );
		} else {
			if ( '' !== $search ) {
				$ids = WC_Data_Store::load( 'product' )->search_products( $search, '', false, true, 80 );
				$ids = array_values( array_filter( array_map( 'absint', (array) $ids ) ) );
				if ( ! $ids ) {
					$products = array();
				} else {
					$args['include'] = $ids;
					$products        = wc_get_products( $args );
				}
			} else {
				$products = wc_get_products( $args );
			}
			if ( isset( self::statuses()[ $filter ] ) ) {
				$products = array_values( array_filter( $products, static fn( $p ) => self::status( $p ) === $filter ) );
			}
		}

		$categories = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'hide_empty' => false,
				'orderby'    => 'name',
			)
		);

		Sena_Studio::template(
			'products',
			array(
				'products'   => $products,
				'filter'     => $filter,
				'search'     => $search,
				'show_add'   => $add,
				'statuses'   => self::statuses(),
				'categories' => is_wp_error( $categories ) ? array() : $categories,
			)
		);
	}

	/* ---------------- Quick actions ---------------- */

	public static function handle_quick(): void {
		Sena_Studio::verify_action( 'sena_studio_product_quick' );

		$id      = isset( $_REQUEST['product_id'] ) ? absint( $_REQUEST['product_id'] ) : 0;
		$product = $id ? wc_get_product( $id ) : false;
		$do      = isset( $_REQUEST['do'] ) ? sanitize_key( wp_unslash( $_REQUEST['do'] ) ) : '';
		$back    = array();
		foreach ( array( 'filter', 's' ) as $k ) {
			if ( ! empty( $_REQUEST[ $k ] ) ) {
				$back[ $k ] = sanitize_text_field( wp_unslash( $_REQUEST[ $k ] ) );
			}
		}

		if ( ! $product instanceof WC_Product ) {
			Sena_Studio::redirect( 'products', __( 'Piece not found.', 'sena-studio' ), 'error', $back );
		}

		switch ( $do ) {
			case 'price':
				$price = isset( $_POST['price'] ) ? wc_format_decimal( sanitize_text_field( wp_unslash( $_POST['price'] ) ) ) : '';
				if ( '' === $price || (float) $price < 0 ) {
					Sena_Studio::redirect( 'products', __( 'Please enter a valid price.', 'sena-studio' ), 'error', $back );
				}
				$product->set_regular_price( $price );
				if ( '' === $product->get_sale_price() || (float) $product->get_sale_price() >= (float) $price ) {
					$product->set_sale_price( '' );
				}
				$product->save();
				$msg = __( 'Price updated.', 'sena-studio' );
				break;

			case 'stock':
				$qty = isset( $_POST['stock'] ) ? (int) wp_unslash( $_POST['stock'] ) : 0;
				$qty = max( 0, $qty );
				$product->set_manage_stock( true );
				$product->set_stock_quantity( $qty );
				$product->set_stock_status( $qty > 0 ? 'instock' : 'outofstock' );
				if ( $qty > 0 && 'sold' === (string) $product->get_meta( self::META_STATUS ) ) {
					$product->update_meta_data( self::META_STATUS, 'available' );
				}
				$product->save();
				$msg = __( 'Stock updated.', 'sena-studio' );
				break;

			case 'status':
				$status = isset( $_POST['status'] ) ? sanitize_key( wp_unslash( $_POST['status'] ) ) : 'available';
				if ( ! isset( self::statuses()[ $status ] ) ) {
					$status = 'available';
				}
				$product->update_meta_data( self::META_STATUS, $status );
				if ( 'made_to_order' === $status ) {
					$product->set_manage_stock( false );
					$product->set_stock_status( 'instock' );
					$product->set_sold_individually( false );
				} elseif ( 'showcase' === $status ) {
					$product->set_manage_stock( false );
					$product->set_stock_status( 'outofstock' );
				} elseif ( 'available' === $status && ! $product->is_in_stock() ) {
					$product->set_manage_stock( true );
					$product->set_stock_quantity( 1 );
					$product->set_stock_status( 'instock' );
				}
				$product->save();
				$msg = __( 'Status updated.', 'sena-studio' );
				break;

			case 'sold':
				$product->update_meta_data( self::META_STATUS, 'sold' );
				$product->set_manage_stock( true );
				$product->set_stock_quantity( 0 );
				$product->set_stock_status( 'outofstock' );
				$product->save();
				$msg = sprintf( /* translators: %s: product name */ __( '"%s" marked sold. Congratulations!', 'sena-studio' ), $product->get_name() );
				break;

			default:
				$msg = '';
		}

		Sena_Studio::redirect( 'products', $msg, 'success', $back );
	}

	/* ---------------- Add a piece ---------------- */

	public static function handle_add_piece(): void {
		Sena_Studio::verify_action( 'sena_studio_add_piece' );

		$name  = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
		$price = isset( $_POST['price'] ) ? wc_format_decimal( sanitize_text_field( wp_unslash( $_POST['price'] ) ) ) : '';
		$kind  = isset( $_POST['kind'] ) && 'made_to_order' === $_POST['kind'] ? 'made_to_order' : 'one_of_a_kind';
		$story = isset( $_POST['story'] ) ? sanitize_textarea_field( wp_unslash( $_POST['story'] ) ) : '';
		$etsy  = isset( $_POST['etsy'] ) ? esc_url_raw( trim( (string) wp_unslash( $_POST['etsy'] ) ) ) : '';
		$cat   = isset( $_POST['category'] ) ? absint( $_POST['category'] ) : 0;
		$ids   = isset( $_POST['image_ids'] ) ? array_filter( array_map( 'absint', explode( ',', sanitize_text_field( wp_unslash( $_POST['image_ids'] ) ) ) ) ) : array();

		if ( '' === $name ) {
			Sena_Studio::redirect( 'products', __( 'Please give the piece a name.', 'sena-studio' ), 'error', array( 'add' => 1 ) );
		}
		if ( '' === $price || (float) $price < 0 ) {
			Sena_Studio::redirect( 'products', __( 'Please enter a price.', 'sena-studio' ), 'error', array( 'add' => 1 ) );
		}
		if ( $etsy && ! str_starts_with( $etsy, 'https://' ) ) {
			$etsy = '';
		}

		// Native file input (camera capture / fallback when wp.media is unavailable).
		$uploaded = self::handle_native_uploads();
		if ( is_wp_error( $uploaded ) ) {
			Sena_Studio::redirect( 'products', $uploaded->get_error_message(), 'error', array( 'add' => 1 ) );
		}
		$ids = array_values( array_unique( array_merge( $ids, $uploaded ) ) );
		$ids = array_values( array_filter( $ids, static fn( $id ) => wp_attachment_is_image( $id ) ) );

		$product = new WC_Product_Simple();
		$product->set_name( $name );
		$product->set_status( 'publish' );
		$product->set_catalog_visibility( 'visible' );
		$product->set_regular_price( $price );
		$product->set_description( $story );
		$product->set_short_description( wp_trim_words( $story, 30 ) );

		if ( 'made_to_order' === $kind ) {
			$product->set_manage_stock( false );
			$product->set_stock_status( 'instock' );
			$product->set_sold_individually( false );
			$product->update_meta_data( self::META_STATUS, 'made_to_order' );
		} else {
			$product->set_manage_stock( true );
			$product->set_stock_quantity( 1 );
			$product->set_stock_status( 'instock' );
			$product->set_sold_individually( true );
			$product->update_meta_data( self::META_STATUS, 'available' );
		}

		if ( $etsy ) {
			$product->update_meta_data( self::META_ETSY, $etsy );
		}
		if ( $cat && term_exists( $cat, 'product_cat' ) ) {
			$product->set_category_ids( array( $cat ) );
		}
		if ( $ids ) {
			$product->set_image_id( array_shift( $ids ) );
			if ( $ids ) {
				$product->set_gallery_image_ids( $ids );
			}
		}

		$product_id = $product->save();

		// Attach uploaded images to the product for tidiness.
		foreach ( array_merge( array( $product->get_image_id() ), $product->get_gallery_image_ids() ) as $aid ) {
			if ( $aid && ! wp_get_post_parent_id( $aid ) ) {
				wp_update_post( array( 'ID' => $aid, 'post_parent' => $product_id ) );
			}
		}

		Sena_Studio::redirect( 'products', sprintf( /* translators: %s: product name */ __( '"%s" is live in the shop.', 'sena-studio' ), $name ) );
	}

	/**
	 * Handle `photos[]` from the native file input. Only images, max 10 MB each.
	 *
	 * @return int[]|WP_Error attachment ids
	 */
	private static function handle_native_uploads() {
		if ( empty( $_FILES['photos'] ) || ! is_array( $_FILES['photos']['name'] ) ) {
			return array();
		}
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$allowed = array(
			'jpg|jpeg|jpe' => 'image/jpeg',
			'png'          => 'image/png',
			'webp'         => 'image/webp',
			'gif'          => 'image/gif',
			'heic'         => 'image/heic',
		);
		$ids   = array();
		$files = $_FILES['photos']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$count = count( $files['name'] );

		for ( $i = 0; $i < $count; $i++ ) {
			if ( UPLOAD_ERR_NO_FILE === (int) $files['error'][ $i ] ) {
				continue;
			}
			if ( UPLOAD_ERR_OK !== (int) $files['error'][ $i ] ) {
				return new WP_Error( 'upload', __( 'One of the photos failed to upload.', 'sena-studio' ) );
			}
			if ( (int) $files['size'][ $i ] > 10 * MB_IN_BYTES ) {
				return new WP_Error( 'upload', __( 'Photos must be 10 MB or smaller.', 'sena-studio' ) );
			}
			$check = wp_check_filetype_and_ext( $files['tmp_name'][ $i ], $files['name'][ $i ], $allowed );
			if ( empty( $check['ext'] ) || empty( $check['type'] ) ) {
				return new WP_Error( 'upload', __( 'Only JPG, PNG, WebP, GIF or HEIC photos are allowed.', 'sena-studio' ) );
			}
			$_FILES['sena_single'] = array(
				'name'     => sanitize_file_name( $files['name'][ $i ] ),
				'type'     => $check['type'],
				'tmp_name' => $files['tmp_name'][ $i ],
				'error'    => $files['error'][ $i ],
				'size'     => $files['size'][ $i ],
			);
			$id = media_handle_upload( 'sena_single', 0, array(), array( 'test_form' => false, 'mimes' => $allowed ) );
			unset( $_FILES['sena_single'] );
			if ( is_wp_error( $id ) ) {
				return $id;
			}
			$ids[] = (int) $id;
		}
		return $ids;
	}
}
