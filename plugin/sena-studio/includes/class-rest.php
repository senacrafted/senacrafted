<?php
/**
 * REST API (namespace sena-studio/v1) for automations such as n8n.
 *
 * Auth: cookie + X-WP-Nonce, or Basic auth with an application password.
 * Every route requires the manage_woocommerce capability (manage_options
 * when WooCommerce is inactive).
 *
 * @package SenaStudio
 */

defined( 'ABSPATH' ) || exit;

final class Sena_Studio_Rest {

	public const NS = 'sena-studio/v1';

	public static function init(): void {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
	}

	public static function permission(): bool|WP_Error {
		if ( current_user_can( Sena_Studio::cap() ) ) {
			return true;
		}
		return new WP_Error( 'sena_forbidden', __( 'You need permission to manage the shop.', 'sena-studio' ), array( 'status' => is_user_logged_in() ? 403 : 401 ) );
	}

	public static function register_routes(): void {
		$id_args = array(
			'id' => array(
				'validate_callback' => static fn( $v ) => is_numeric( $v ) && (int) $v > 0,
				'sanitize_callback' => 'absint',
				'required'          => true,
			),
		);

		register_rest_route(
			self::NS,
			'/posts',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( __CLASS__, 'get_posts' ),
					'permission_callback' => array( __CLASS__, 'permission' ),
					'args'                => array(
						'status'   => array( 'type' => 'string', 'sanitize_callback' => 'sanitize_key', 'default' => '' ),
						'per_page' => array( 'type' => 'integer', 'default' => 20, 'minimum' => 1, 'maximum' => 100 ),
						'page'     => array( 'type' => 'integer', 'default' => 1, 'minimum' => 1 ),
					),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( __CLASS__, 'create_post' ),
					'permission_callback' => array( __CLASS__, 'permission' ),
					'args'                => array(
						'platform'   => array( 'type' => 'string', 'enum' => array_keys( Sena_Studio_Posts::platforms() ), 'default' => 'instagram' ),
						'caption'    => array( 'type' => 'string', 'required' => true ),
						'hashtags'   => array( 'type' => 'string', 'default' => '' ),
						'product_id' => array( 'type' => 'integer', 'default' => 0 ),
						'image_id'   => array( 'type' => 'integer', 'default' => 0 ),
						'scheduled'  => array( 'type' => 'string', 'default' => '' ),
						'status'     => array( 'type' => 'string', 'enum' => array_keys( Sena_Studio_Posts::statuses() ), 'default' => 'draft' ),
					),
				),
			)
		);

		register_rest_route(
			self::NS,
			'/posts/(?P<id>\d+)',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( __CLASS__, 'get_post' ),
					'permission_callback' => array( __CLASS__, 'permission' ),
					'args'                => $id_args,
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( __CLASS__, 'update_post' ),
					'permission_callback' => array( __CLASS__, 'permission' ),
					'args'                => $id_args,
				),
			)
		);

		foreach ( array( 'approve' => 'approved', 'skip' => 'skipped', 'publish' => 'published' ) as $route => $status ) {
			register_rest_route(
				self::NS,
				'/posts/(?P<id>\d+)/' . $route,
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => static fn( WP_REST_Request $r ) => self::set_status( $r, $status ),
					'permission_callback' => array( __CLASS__, 'permission' ),
					'args'                => $id_args,
				)
			);
		}

		register_rest_route(
			self::NS,
			'/products/recent',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'recent_products' ),
				'permission_callback' => array( __CLASS__, 'permission' ),
				'args'                => array(
					'per_page' => array( 'type' => 'integer', 'default' => 10, 'minimum' => 1, 'maximum' => 50 ),
					'since'    => array( 'type' => 'string', 'default' => '', 'description' => 'ISO-8601 date; only products created after this.' ),
				),
			)
		);
	}

	/* ---------------- Posts ---------------- */

	public static function get_posts( WP_REST_Request $r ): WP_REST_Response {
		$status = (string) $r['status'];
		$posts  = Sena_Studio_Posts::query( $status, (int) $r['per_page'], (int) $r['page'] );
		$items  = array_values( array_filter( array_map( static fn( $p ) => Sena_Studio_Posts::get( $p->ID ), $posts ) ) );
		return rest_ensure_response( $items );
	}

	public static function get_post( WP_REST_Request $r ) {
		$post = Sena_Studio_Posts::get( (int) $r['id'] );
		if ( ! $post ) {
			return new WP_Error( 'sena_not_found', __( 'Post not found.', 'sena-studio' ), array( 'status' => 404 ) );
		}
		return rest_ensure_response( $post );
	}

	public static function create_post( WP_REST_Request $r ) {
		$id = Sena_Studio_Posts::create(
			array(
				'platform'   => $r['platform'],
				'caption'    => $r['caption'],
				'hashtags'   => $r['hashtags'],
				'product_id' => $r['product_id'],
				'image_id'   => $r['image_id'],
				'scheduled'  => $r['scheduled'],
				'status'     => $r['status'],
			)
		);
		if ( is_wp_error( $id ) ) {
			return $id;
		}
		$response = rest_ensure_response( Sena_Studio_Posts::get( $id ) );
		$response->set_status( 201 );
		return $response;
	}

	public static function update_post( WP_REST_Request $r ) {
		$id = (int) $r['id'];
		if ( ! Sena_Studio_Posts::get( $id ) ) {
			return new WP_Error( 'sena_not_found', __( 'Post not found.', 'sena-studio' ), array( 'status' => 404 ) );
		}
		$allowed = array( 'caption', 'hashtags', 'scheduled', 'platform', 'status', 'product_id', 'image_id' );
		$data    = array();
		foreach ( $allowed as $key ) {
			if ( $r->has_param( $key ) ) {
				$data[ $key ] = $r->get_param( $key );
			}
		}
		Sena_Studio_Posts::update( $id, $data );
		return rest_ensure_response( Sena_Studio_Posts::get( $id ) );
	}

	private static function set_status( WP_REST_Request $r, string $status ) {
		$id = (int) $r['id'];
		if ( ! Sena_Studio_Posts::set_status( $id, $status ) ) {
			return new WP_Error( 'sena_not_found', __( 'Post not found.', 'sena-studio' ), array( 'status' => 404 ) );
		}
		return rest_ensure_response( Sena_Studio_Posts::get( $id ) );
	}

	/* ---------------- Products ---------------- */

	public static function recent_products( WP_REST_Request $r ) {
		if ( ! Sena_Studio::has_woocommerce() ) {
			return new WP_Error( 'sena_no_wc', __( 'WooCommerce is not active.', 'sena-studio' ), array( 'status' => 501 ) );
		}
		$args = array(
			'limit'   => (int) $r['per_page'],
			'status'  => 'publish',
			'orderby' => 'date',
			'order'   => 'DESC',
		);
		$since = (string) $r['since'];
		if ( $since && strtotime( $since ) ) {
			$args['date_created'] = '>' . strtotime( $since );
		}
		$items = array();
		foreach ( wc_get_products( $args ) as $p ) {
			$gallery = array_values( array_filter( array_map( static fn( $id ) => wp_get_attachment_image_url( (int) $id, 'large' ), $p->get_gallery_image_ids() ) ) );
			$items[] = array(
				'id'          => $p->get_id(),
				'name'        => $p->get_name(),
				'slug'        => $p->get_slug(),
				'price'       => $p->get_price(),
				'currency'    => get_woocommerce_currency(),
				'url'         => $p->get_permalink(),
				'status'      => Sena_Studio_Products::status( $p ),
				'stock'       => $p->get_stock_quantity(),
				'description' => wp_strip_all_tags( $p->get_description() ),
				'categories'  => wp_list_pluck( wp_get_post_terms( $p->get_id(), 'product_cat' ), 'name' ),
				'image_id'    => (int) $p->get_image_id(),
				'image'       => $p->get_image_id() ? wp_get_attachment_image_url( (int) $p->get_image_id(), 'large' ) : '',
				'gallery'     => $gallery,
				'etsy_url'    => (string) $p->get_meta( Sena_Studio_Products::META_ETSY ),
				'created'     => $p->get_date_created() ? $p->get_date_created()->date( 'c' ) : '',
			);
		}
		return rest_ensure_response( $items );
	}
}
