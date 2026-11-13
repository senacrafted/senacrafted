<?php
/**
 * "Find me at a festival" — the sena_event custom post type.
 *
 * Post meta: event_date (YYYY-MM-DD), event_place, event_link.
 *
 * @package Sena_Crafted
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register the post type.
 */
function sena_register_event_cpt(): void {
	register_post_type(
		'sena_event',
		array(
			'labels'              => array(
				'name'               => __( 'Events', 'sena-crafted' ),
				'singular_name'      => __( 'Event', 'sena-crafted' ),
				'add_new'            => __( 'Add event', 'sena-crafted' ),
				'add_new_item'       => __( 'Add new event', 'sena-crafted' ),
				'edit_item'          => __( 'Edit event', 'sena-crafted' ),
				'new_item'           => __( 'New event', 'sena-crafted' ),
				'all_items'          => __( 'All events', 'sena-crafted' ),
				'search_items'       => __( 'Search events', 'sena-crafted' ),
				'not_found'          => __( 'No events yet.', 'sena-crafted' ),
				'not_found_in_trash' => __( 'No events in the trash.', 'sena-crafted' ),
				'menu_name'          => __( 'Events', 'sena-crafted' ),
			),
			'description'         => __( 'Festivals and markets shown on the front page.', 'sena-crafted' ),
			'public'              => false,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_rest'        => true,
			'exclude_from_search' => true,
			'publicly_queryable'  => false,
			'menu_icon'           => 'dashicons-calendar-alt',
			'menu_position'       => 21,
			'supports'            => array( 'title' ),
			'rewrite'             => false,
		)
	);

	register_post_meta(
		'sena_event',
		'event_date',
		array(
			'type'              => 'string',
			'single'            => true,
			'show_in_rest'      => true,
			'sanitize_callback' => 'sena_sanitize_event_date',
			'auth_callback'     => static fn() => current_user_can( 'edit_posts' ),
		)
	);
	register_post_meta(
		'sena_event',
		'event_place',
		array(
			'type'              => 'string',
			'single'            => true,
			'show_in_rest'      => true,
			'sanitize_callback' => 'sanitize_text_field',
			'auth_callback'     => static fn() => current_user_can( 'edit_posts' ),
		)
	);
	register_post_meta(
		'sena_event',
		'event_link',
		array(
			'type'              => 'string',
			'single'            => true,
			'show_in_rest'      => true,
			'sanitize_callback' => 'esc_url_raw',
			'auth_callback'     => static fn() => current_user_can( 'edit_posts' ),
		)
	);
}
add_action( 'init', 'sena_register_event_cpt' );

/**
 * Keep only valid YYYY-MM-DD dates.
 */
function sena_sanitize_event_date( $value ): string {
	$value = trim( (string) $value );
	if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $value ) && false !== strtotime( $value ) ) {
		return $value;
	}
	return '';
}

/**
 * Meta box.
 */
function sena_event_meta_box(): void {
	add_meta_box( 'sena_event_details', __( 'Event details', 'sena-crafted' ), 'sena_event_meta_box_html', 'sena_event', 'normal', 'high' );
}
add_action( 'add_meta_boxes', 'sena_event_meta_box' );

/**
 * Meta box markup.
 */
function sena_event_meta_box_html( WP_Post $post ): void {
	wp_nonce_field( 'sena_event_save', 'sena_event_nonce' );
	$date  = (string) get_post_meta( $post->ID, 'event_date', true );
	$place = (string) get_post_meta( $post->ID, 'event_place', true );
	$link  = (string) get_post_meta( $post->ID, 'event_link', true );
	?>
	<p>
		<label for="sena_event_date"><strong><?php esc_html_e( 'Date', 'sena-crafted' ); ?></strong></label><br>
		<input type="date" id="sena_event_date" name="event_date" value="<?php echo esc_attr( $date ); ?>" required>
		<span class="description"><?php esc_html_e( 'Past events are hidden automatically.', 'sena-crafted' ); ?></span>
	</p>
	<p>
		<label for="sena_event_place"><strong><?php esc_html_e( 'Place', 'sena-crafted' ); ?></strong></label><br>
		<input type="text" class="widefat" id="sena_event_place" name="event_place" value="<?php echo esc_attr( $place ); ?>" placeholder="<?php esc_attr_e( 'Athens, GA', 'sena-crafted' ); ?>">
	</p>
	<p>
		<label for="sena_event_link"><strong><?php esc_html_e( 'Details link (optional)', 'sena-crafted' ); ?></strong></label><br>
		<input type="url" class="widefat" id="sena_event_link" name="event_link" value="<?php echo esc_attr( $link ); ?>" placeholder="https://">
	</p>
	<?php
}

/**
 * Save meta.
 */
function sena_event_save( int $post_id ): void {
	if ( ! isset( $_POST['sena_event_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['sena_event_nonce'] ) ), 'sena_event_save' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$date  = isset( $_POST['event_date'] ) ? sena_sanitize_event_date( wp_unslash( $_POST['event_date'] ) ) : '';
	$place = isset( $_POST['event_place'] ) ? sanitize_text_field( wp_unslash( $_POST['event_place'] ) ) : '';
	$link  = isset( $_POST['event_link'] ) ? esc_url_raw( wp_unslash( $_POST['event_link'] ) ) : '';

	update_post_meta( $post_id, 'event_date', $date );
	update_post_meta( $post_id, 'event_place', $place );
	update_post_meta( $post_id, 'event_link', $link );
}
add_action( 'save_post_sena_event', 'sena_event_save' );

/**
 * Admin list columns.
 */
function sena_event_columns( array $columns ): array {
	return array(
		'cb'          => $columns['cb'] ?? '',
		'title'       => __( 'Event', 'sena-crafted' ),
		'event_date'  => __( 'Date', 'sena-crafted' ),
		'event_place' => __( 'Place', 'sena-crafted' ),
	);
}
add_filter( 'manage_sena_event_posts_columns', 'sena_event_columns' );

/**
 * Admin list column content.
 */
function sena_event_column_content( string $column, int $post_id ): void {
	if ( 'event_date' === $column ) {
		$date = (string) get_post_meta( $post_id, 'event_date', true );
		echo $date ? esc_html( wp_date( get_option( 'date_format' ), ( new DateTimeImmutable( $date . ' 00:00:00', wp_timezone() ) )->getTimestamp() ) ) : '—';
	} elseif ( 'event_place' === $column ) {
		echo esc_html( (string) get_post_meta( $post_id, 'event_place', true ) );
	}
}
add_action( 'manage_sena_event_posts_custom_column', 'sena_event_column_content', 10, 2 );

/**
 * Sortable date column.
 */
function sena_event_sortable( array $columns ): array {
	$columns['event_date'] = 'event_date';
	return $columns;
}
add_filter( 'manage_edit-sena_event_sortable_columns', 'sena_event_sortable' );

function sena_event_orderby( WP_Query $query ): void {
	if ( is_admin() && $query->is_main_query() && 'event_date' === $query->get( 'orderby' ) ) {
		$query->set( 'meta_key', 'event_date' );
		$query->set( 'orderby', 'meta_value' );
	}
}
add_action( 'pre_get_posts', 'sena_event_orderby' );

/**
 * Upcoming events (today or later), soonest first.
 *
 * @return WP_Post[]
 */
function sena_get_upcoming_events(): array {
	$today = wp_date( 'Y-m-d' );
	return get_posts(
		array(
			'post_type'      => 'sena_event',
			'post_status'    => 'publish',
			'posts_per_page' => 20,
			'meta_key'       => 'event_date', // phpcs:ignore WordPress.DB.SlowDBQuery
			'orderby'        => 'meta_value',
			'order'          => 'ASC',
			'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery
				array(
					'key'     => 'event_date',
					'value'   => $today,
					'compare' => '>=',
					'type'    => 'DATE',
				),
			),
		)
	);
}

/**
 * Short stamp-style date, e.g. "Nov 15".
 */
function sena_event_short_date( string $ymd ): string {
	try {
		$ts = ( new DateTimeImmutable( $ymd . ' 00:00:00', wp_timezone() ) )->getTimestamp();
	} catch ( Exception $e ) {
		return '';
	}
	return wp_date( 'M j', $ts );
}
