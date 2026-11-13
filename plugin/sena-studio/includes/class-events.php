<?php
/**
 * Events (CPT sena_event). The theme may register this post type too; we only
 * register it when it does not exist yet and always use the same meta keys.
 *
 * @package SenaStudio
 */

defined( 'ABSPATH' ) || exit;

final class Sena_Studio_Events {

	public const CPT        = 'sena_event';
	public const META_DATE  = 'event_date';
	public const META_PLACE = 'event_place';
	public const META_LINK  = 'event_link';

	public static function init(): void {
		add_action( 'init', array( __CLASS__, 'register_post_type' ), 20 );
		add_action( 'admin_post_sena_studio_event_save', array( __CLASS__, 'handle_save' ) );
		add_action( 'admin_post_sena_studio_event_delete', array( __CLASS__, 'handle_delete' ) );
	}

	public static function register_post_type(): void {
		if ( post_type_exists( self::CPT ) ) {
			return;
		}
		register_post_type(
			self::CPT,
			array(
				'labels'       => array(
					'name'          => __( 'Events', 'sena-studio' ),
					'singular_name' => __( 'Event', 'sena-studio' ),
				),
				'public'       => true,
				'show_ui'      => true,
				'show_in_menu' => false,
				'show_in_rest' => true,
				'has_archive'  => true,
				'rewrite'      => array( 'slug' => 'events' ),
				'supports'     => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
				'menu_icon'    => 'dashicons-calendar-alt',
			)
		);
		foreach ( array( self::META_DATE, self::META_PLACE, self::META_LINK ) as $key ) {
			register_post_meta(
				self::CPT,
				$key,
				array(
					'type'              => 'string',
					'single'            => true,
					'show_in_rest'      => true,
					'sanitize_callback' => self::META_LINK === $key ? 'esc_url_raw' : 'sanitize_text_field',
					'auth_callback'     => static fn() => current_user_can( 'edit_posts' ),
				)
			);
		}
	}

	public static function get( int $id ): array {
		$post = get_post( $id );
		if ( ! $post || self::CPT !== $post->post_type || 'trash' === $post->post_status ) {
			return array();
		}
		$date = (string) get_post_meta( $id, self::META_DATE, true );
		$ts   = $date ? self::local_midnight( $date ) : false;
		return array(
			'id'         => $id,
			'title'      => get_the_title( $post ),
			'date'       => $date,
			'ts'         => $ts ?: 0,
			'date_label' => $ts ? wp_date( get_option( 'date_format' ), $ts ) : '',
			'place'      => (string) get_post_meta( $id, self::META_PLACE, true ),
			'link'       => (string) get_post_meta( $id, self::META_LINK, true ),
			'notes'      => (string) $post->post_content,
			'status'     => $post->post_status,
			'relative'   => $ts ? self::relative( $ts ) : '',
		);
	}

	/**
	 * Midnight of a Y-m-d date in the site timezone, as a timestamp (strtotime would use UTC and shift the day).
	 */
	private static function local_midnight( string $ymd ): int {
		try {
			return ( new DateTimeImmutable( $ymd . ' 00:00:00', wp_timezone() ) )->getTimestamp();
		} catch ( Exception $e ) {
			return 0;
		}
	}

	private static function relative( int $ts ): string {
		$today = self::local_midnight( wp_date( 'Y-m-d' ) );
		$days  = (int) floor( ( $ts - $today ) / DAY_IN_SECONDS );
		if ( 0 === $days ) {
			return __( 'Today', 'sena-studio' );
		}
		if ( 1 === $days ) {
			return __( 'Tomorrow', 'sena-studio' );
		}
		if ( $days > 1 ) {
			return sprintf( /* translators: %d: days */ _n( 'in %d day', 'in %d days', $days, 'sena-studio' ), $days );
		}
		return sprintf( /* translators: %d: days */ _n( '%d day ago', '%d days ago', abs( $days ), 'sena-studio' ), abs( $days ) );
	}

	/**
	 * @return array[] events sorted by date
	 */
	public static function all( string $scope = 'upcoming' ): array {
		$today = wp_date( 'Y-m-d' );
		$args  = array(
			'post_type'      => self::CPT,
			'post_status'    => array( 'publish', 'draft', 'future', 'private' ),
			'posts_per_page' => 200,
			'meta_key'       => self::META_DATE, // phpcs:ignore WordPress.DB.SlowDBQuery
			'orderby'        => 'meta_value',
			'order'          => 'upcoming' === $scope ? 'ASC' : 'DESC',
		);
		if ( 'upcoming' === $scope ) {
			$args['meta_query'] = array( array( 'key' => self::META_DATE, 'value' => $today, 'compare' => '>=', 'type' => 'DATE' ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
		} elseif ( 'past' === $scope ) {
			$args['meta_query'] = array( array( 'key' => self::META_DATE, 'value' => $today, 'compare' => '<', 'type' => 'DATE' ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
		}
		return array_values( array_filter( array_map( static fn( $p ) => self::get( $p->ID ), get_posts( $args ) ) ) );
	}

	public static function next_event(): ?array {
		$events = self::all( 'upcoming' );
		foreach ( $events as $e ) {
			if ( 'publish' === $e['status'] ) {
				return $e;
			}
		}
		return $events[0] ?? null;
	}

	/* ---------------- Tab ---------------- */

	public static function render_tab(): void {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$scope = isset( $_GET['scope'] ) && 'past' === $_GET['scope'] ? 'past' : 'upcoming';
		$edit  = isset( $_GET['event'] ) ? absint( $_GET['event'] ) : 0;
		$add   = ! empty( $_GET['add'] );
		// phpcs:enable
		Sena_Studio::template(
			'events',
			array(
				'events'   => self::all( $scope ),
				'scope'    => $scope,
				'editing'  => $edit ? self::get( $edit ) : array(),
				'show_add' => $add,
			)
		);
	}

	public static function handle_save(): void {
		Sena_Studio::verify_action( 'sena_studio_event_save' );
		$id    = isset( $_POST['event_id'] ) ? absint( $_POST['event_id'] ) : 0;
		$title = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';
		$date  = isset( $_POST['event_date'] ) ? sanitize_text_field( wp_unslash( $_POST['event_date'] ) ) : '';
		$place = isset( $_POST['event_place'] ) ? sanitize_text_field( wp_unslash( $_POST['event_place'] ) ) : '';
		$link  = isset( $_POST['event_link'] ) ? esc_url_raw( trim( (string) wp_unslash( $_POST['event_link'] ) ) ) : '';
		$notes = isset( $_POST['notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['notes'] ) ) : '';

		if ( '' === $title ) {
			Sena_Studio::redirect( 'events', __( 'Give the event a name.', 'sena-studio' ), 'error', array( 'add' => 1 ) );
		}
		$d = DateTime::createFromFormat( 'Y-m-d', $date );
		if ( ! $d || $d->format( 'Y-m-d' ) !== $date ) {
			Sena_Studio::redirect( 'events', __( 'Please pick a valid date.', 'sena-studio' ), 'error', array( 'add' => 1 ) );
		}

		$postarr = array(
			'post_type'    => self::CPT,
			'post_status'  => 'publish',
			'post_title'   => $title,
			'post_content' => $notes,
			'meta_input'   => array(
				self::META_DATE  => $date,
				self::META_PLACE => $place,
				self::META_LINK  => $link,
			),
		);
		if ( $id && self::get( $id ) ) {
			$postarr['ID'] = $id;
			$result        = wp_update_post( $postarr, true );
			$msg           = __( 'Event updated.', 'sena-studio' );
		} else {
			$result = wp_insert_post( $postarr, true );
			$msg    = __( 'Event added.', 'sena-studio' );
		}
		if ( is_wp_error( $result ) ) {
			Sena_Studio::redirect( 'events', $result->get_error_message(), 'error' );
		}
		Sena_Studio::redirect( 'events', $msg, 'success', strtotime( $date ) < strtotime( wp_date( 'Y-m-d' ) ) ? array( 'scope' => 'past' ) : array() );
	}

	public static function handle_delete(): void {
		Sena_Studio::verify_action( 'sena_studio_event_delete' );
		$id = isset( $_REQUEST['event_id'] ) ? absint( $_REQUEST['event_id'] ) : 0;
		if ( self::get( $id ) ) {
			wp_trash_post( $id );
		}
		Sena_Studio::redirect( 'events', __( 'Event removed.', 'sena-studio' ) );
	}
}
