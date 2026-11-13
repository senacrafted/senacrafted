<?php
/**
 * Social post approval queue (CPT sena_post).
 *
 * @package SenaStudio
 */

defined( 'ABSPATH' ) || exit;

final class Sena_Studio_Posts {

	public const CPT = 'sena_post';

	public static function init(): void {
		add_action( 'init', array( __CLASS__, 'register_post_type' ) );
		add_action( 'admin_post_sena_studio_post_save', array( __CLASS__, 'handle_save' ) );
		add_action( 'admin_post_sena_studio_post_delete', array( __CLASS__, 'handle_delete' ) );
		add_action( 'admin_post_sena_studio_post_create', array( __CLASS__, 'handle_create' ) );
	}

	public static function register_post_type(): void {
		if ( post_type_exists( self::CPT ) ) {
			return;
		}
		register_post_type(
			self::CPT,
			array(
				'labels'              => array(
					'name'          => __( 'Social posts', 'sena-studio' ),
					'singular_name' => __( 'Social post', 'sena-studio' ),
				),
				'public'              => false,
				'show_ui'             => false,
				'show_in_rest'        => false,
				'exclude_from_search' => true,
				'supports'            => array( 'title', 'editor' ),
				'capability_type'     => 'post',
				'map_meta_cap'        => true,
			)
		);
	}

	public static function platforms(): array {
		return array(
			'instagram' => 'Instagram',
			'tiktok'    => 'TikTok',
			'pinterest' => 'Pinterest',
		);
	}

	public static function statuses(): array {
		return array(
			'draft'     => __( 'Needs approval', 'sena-studio' ),
			'approved'  => __( 'Approved', 'sena-studio' ),
			'published' => __( 'Published', 'sena-studio' ),
			'skipped'   => __( 'Skipped', 'sena-studio' ),
		);
	}

	/**
	 * @return WP_Post[]
	 */
	public static function query( string $status = '', int $limit = 50, int $page = 1 ): array {
		$args = array(
			'post_type'      => self::CPT,
			'post_status'    => 'publish',
			'posts_per_page' => $limit,
			'paged'          => $page,
			'orderby'        => 'date',
			'order'          => 'DESC',
		);
		if ( $status && isset( self::statuses()[ $status ] ) ) {
			$args['meta_query'] = array( array( 'key' => '_sena_status', 'value' => $status ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
		}
		return get_posts( $args );
	}

	public static function count( string $status ): int {
		$q = new WP_Query(
			array(
				'post_type'      => self::CPT,
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_query'     => array( array( 'key' => '_sena_status', 'value' => $status ) ), // phpcs:ignore WordPress.DB.SlowDBQuery
			)
		);
		return (int) $q->found_posts;
	}

	/**
	 * Normalised array for templates and REST.
	 */
	public static function get( int $id ): array {
		$post = get_post( $id );
		if ( ! $post || self::CPT !== $post->post_type || 'trash' === $post->post_status ) {
			return array();
		}
		$m          = static fn( string $k ) => get_post_meta( $id, $k, true );
		$product_id = (int) $m( '_sena_product_id' );
		$image_id   = (int) $m( '_sena_image_id' );
		if ( ! $image_id && $product_id ) {
			$image_id = (int) get_post_thumbnail_id( $product_id );
		}
		$image_url = $image_id ? (string) wp_get_attachment_image_url( $image_id, 'large' ) : '';

		return array(
			'id'           => $id,
			'platform'     => (string) $m( '_sena_platform' ) ?: 'instagram',
			'caption'      => (string) $post->post_content,
			'hashtags'     => (string) $m( '_sena_hashtags' ),
			'product_id'   => $product_id,
			'product_name' => $product_id ? get_the_title( $product_id ) : '',
			'product_url'  => $product_id ? (string) get_permalink( $product_id ) : '',
			'scheduled'    => (string) $m( '_sena_scheduled' ),
			'status'       => (string) $m( '_sena_status' ) ?: 'draft',
			'image_id'     => $image_id,
			'image_url'    => $image_url,
			'created'      => get_post_time( 'c', true, $post ),
			'modified'     => get_post_modified_time( 'c', true, $post ),
		);
	}

	/**
	 * Create a post from sanitized values. Returns ID or WP_Error.
	 *
	 * @return int|WP_Error
	 */
	public static function create( array $data ) {
		$platform = isset( $data['platform'] ) ? sanitize_key( $data['platform'] ) : 'instagram';
		if ( ! isset( self::platforms()[ $platform ] ) ) {
			$platform = 'instagram';
		}
		$caption = isset( $data['caption'] ) ? sanitize_textarea_field( $data['caption'] ) : '';
		if ( '' === trim( $caption ) ) {
			return new WP_Error( 'missing_caption', __( 'A caption is required.', 'sena-studio' ), array( 'status' => 400 ) );
		}
		$status = isset( $data['status'] ) ? sanitize_key( $data['status'] ) : 'draft';
		if ( ! isset( self::statuses()[ $status ] ) ) {
			$status = 'draft';
		}
		$product_id = isset( $data['product_id'] ) ? absint( $data['product_id'] ) : 0;
		if ( $product_id && 'product' !== get_post_type( $product_id ) ) {
			$product_id = 0;
		}
		$image_id = isset( $data['image_id'] ) ? absint( $data['image_id'] ) : 0;
		if ( $image_id && ! wp_attachment_is_image( $image_id ) ) {
			$image_id = 0;
		}

		$id = wp_insert_post(
			array(
				'post_type'    => self::CPT,
				'post_status'  => 'publish',
				'post_title'   => wp_trim_words( $caption, 8, '…' ),
				'post_content' => $caption,
				'meta_input'   => array(
					'_sena_platform'   => $platform,
					'_sena_hashtags'   => isset( $data['hashtags'] ) ? self::sanitize_hashtags( $data['hashtags'] ) : '',
					'_sena_product_id' => $product_id,
					'_sena_scheduled'  => isset( $data['scheduled'] ) ? self::sanitize_datetime( $data['scheduled'] ) : '',
					'_sena_status'     => $status,
					'_sena_image_id'   => $image_id,
				),
			),
			true
		);
		return is_wp_error( $id ) ? $id : (int) $id;
	}

	public static function update( int $id, array $data ): bool {
		if ( ! self::get( $id ) ) {
			return false;
		}
		if ( array_key_exists( 'caption', $data ) ) {
			$caption = sanitize_textarea_field( (string) $data['caption'] );
			wp_update_post( array( 'ID' => $id, 'post_content' => $caption, 'post_title' => wp_trim_words( $caption, 8, '…' ) ) );
		}
		if ( array_key_exists( 'hashtags', $data ) ) {
			update_post_meta( $id, '_sena_hashtags', self::sanitize_hashtags( (string) $data['hashtags'] ) );
		}
		if ( array_key_exists( 'scheduled', $data ) ) {
			update_post_meta( $id, '_sena_scheduled', self::sanitize_datetime( (string) $data['scheduled'] ) );
		}
		if ( array_key_exists( 'platform', $data ) && isset( self::platforms()[ sanitize_key( $data['platform'] ) ] ) ) {
			update_post_meta( $id, '_sena_platform', sanitize_key( $data['platform'] ) );
		}
		if ( array_key_exists( 'status', $data ) && isset( self::statuses()[ sanitize_key( $data['status'] ) ] ) ) {
			update_post_meta( $id, '_sena_status', sanitize_key( $data['status'] ) );
		}
		if ( array_key_exists( 'product_id', $data ) ) {
			$pid = absint( $data['product_id'] );
			update_post_meta( $id, '_sena_product_id', ( $pid && 'product' === get_post_type( $pid ) ) ? $pid : 0 );
		}
		if ( array_key_exists( 'image_id', $data ) ) {
			$iid = absint( $data['image_id'] );
			update_post_meta( $id, '_sena_image_id', ( $iid && wp_attachment_is_image( $iid ) ) ? $iid : 0 );
		}
		return true;
	}

	public static function set_status( int $id, string $status ): bool {
		if ( ! isset( self::statuses()[ $status ] ) || ! self::get( $id ) ) {
			return false;
		}
		update_post_meta( $id, '_sena_status', $status );
		if ( 'approved' === $status ) {
			update_post_meta( $id, '_sena_approved_at', gmdate( 'c' ) );
		}
		if ( 'published' === $status ) {
			update_post_meta( $id, '_sena_published_at', gmdate( 'c' ) );
		}
		return true;
	}

	public static function sanitize_hashtags( string $raw ): string {
		$raw  = sanitize_textarea_field( $raw );
		$tags = preg_split( '/[\s,]+/', $raw ) ?: array();
		$out  = array();
		foreach ( $tags as $t ) {
			$t = ltrim( trim( $t ), '#' );
			$t = preg_replace( '/[^\p{L}\p{N}_]/u', '', $t );
			if ( '' !== $t ) {
				$out[] = '#' . $t;
			}
		}
		return implode( ' ', array_unique( $out ) );
	}

	public static function sanitize_datetime( string $raw ): string {
		$raw = trim( sanitize_text_field( $raw ) );
		if ( '' === $raw ) {
			return '';
		}
		$ts = strtotime( $raw );
		return $ts ? gmdate( 'Y-m-d\TH:i', $ts ) : '';
	}

	/* ---------------- Tab ---------------- */

	public static function render_tab(): void {
		$status = isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : 'draft'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$add    = ! empty( $_GET['add'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( 'all' !== $status && ! isset( self::statuses()[ $status ] ) ) {
			$status = 'draft';
		}
		$posts  = array_map( static fn( $p ) => self::get( $p->ID ), self::query( 'all' === $status ? '' : $status, 60 ) );
		$counts = array();
		foreach ( array_keys( self::statuses() ) as $s ) {
			$counts[ $s ] = self::count( $s );
		}
		$products = Sena_Studio::has_woocommerce() ? wc_get_products( array( 'limit' => 100, 'status' => 'publish', 'orderby' => 'date', 'order' => 'DESC', 'return' => 'objects' ) ) : array();

		Sena_Studio::template(
			'posts',
			array(
				'posts'     => $posts,
				'status'    => $status,
				'statuses'  => self::statuses(),
				'platforms' => self::platforms(),
				'counts'    => $counts,
				'products'  => $products,
				'show_add'  => $add,
			)
		);
	}

	private static function back(): array {
		return ! empty( $_REQUEST['status'] ) ? array( 'status' => sanitize_key( wp_unslash( $_REQUEST['status'] ) ) ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}

	public static function handle_save(): void {
		Sena_Studio::verify_action( 'sena_studio_post_save' );
		$id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
		$do = isset( $_POST['do'] ) ? sanitize_key( wp_unslash( $_POST['do'] ) ) : 'save';
		if ( ! self::get( $id ) ) {
			Sena_Studio::redirect( 'posts', __( 'Post not found.', 'sena-studio' ), 'error', self::back() );
		}
		$data = array(
			'caption'   => isset( $_POST['caption'] ) ? wp_unslash( $_POST['caption'] ) : '', // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			'hashtags'  => isset( $_POST['hashtags'] ) ? wp_unslash( $_POST['hashtags'] ) : '', // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			'scheduled' => isset( $_POST['scheduled'] ) ? wp_unslash( $_POST['scheduled'] ) : '', // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			'platform'  => isset( $_POST['platform'] ) ? wp_unslash( $_POST['platform'] ) : 'instagram', // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		);
		self::update( $id, $data );

		$messages = array(
			'approve' => __( 'Post approved.', 'sena-studio' ),
			'skip'    => __( 'Post skipped.', 'sena-studio' ),
			'publish' => __( 'Marked published.', 'sena-studio' ),
			'draft'   => __( 'Moved back to the queue.', 'sena-studio' ),
			'save'    => __( 'Post saved.', 'sena-studio' ),
		);
		$map = array( 'approve' => 'approved', 'skip' => 'skipped', 'publish' => 'published', 'draft' => 'draft' );
		if ( isset( $map[ $do ] ) ) {
			self::set_status( $id, $map[ $do ] );
		}
		Sena_Studio::redirect( 'posts', $messages[ $do ] ?? $messages['save'], 'success', self::back() );
	}

	public static function handle_delete(): void {
		Sena_Studio::verify_action( 'sena_studio_post_delete' );
		$id = isset( $_REQUEST['post_id'] ) ? absint( $_REQUEST['post_id'] ) : 0;
		if ( self::get( $id ) ) {
			wp_trash_post( $id );
		}
		Sena_Studio::redirect( 'posts', __( 'Post deleted.', 'sena-studio' ), 'success', self::back() );
	}

	public static function handle_create(): void {
		Sena_Studio::verify_action( 'sena_studio_post_create' );
		$data = array(
			'platform'   => isset( $_POST['platform'] ) ? wp_unslash( $_POST['platform'] ) : 'instagram', // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			'caption'    => isset( $_POST['caption'] ) ? wp_unslash( $_POST['caption'] ) : '', // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			'hashtags'   => isset( $_POST['hashtags'] ) ? wp_unslash( $_POST['hashtags'] ) : '', // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			'scheduled'  => isset( $_POST['scheduled'] ) ? wp_unslash( $_POST['scheduled'] ) : '', // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			'product_id' => isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0,
			'image_id'   => isset( $_POST['image_id'] ) ? absint( $_POST['image_id'] ) : 0,
			'status'     => 'draft',
		);
		$id = self::create( $data );
		if ( is_wp_error( $id ) ) {
			Sena_Studio::redirect( 'posts', $id->get_error_message(), 'error' );
		}
		Sena_Studio::redirect( 'posts', __( 'Post added to the queue.', 'sena-studio' ), 'success', array( 'status' => 'draft' ) );
	}
}
