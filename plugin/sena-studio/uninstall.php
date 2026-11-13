<?php
/**
 * Uninstall cleanup for Sena Studio.
 *
 * Removes plugin options and the plugin's own content types (requests and
 * social posts). Events are left in place because the theme may also own
 * the sena_event post type. WooCommerce data is never touched.
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'sena_studio_settings' );
delete_option( 'sena_studio_version' );
delete_option( 'sena_studio_milestones' );
delete_metadata( 'user', 0, 'sena_studio_dismissed_steps', '', true );
$wpdb_prefix_key = $GLOBALS['wpdb']->get_blog_prefix() . 'sena_studio_dismissed_steps';
delete_metadata( 'user', 0, $wpdb_prefix_key, '', true );

foreach ( array( 'sena_request', 'sena_post' ) as $post_type ) {
	$ids = get_posts(
		array(
			'post_type'      => $post_type,
			'post_status'    => 'any',
			'numberposts'    => -1,
			'fields'         => 'ids',
			'suppress_filters' => true,
		)
	);
	foreach ( $ids as $id ) {
		wp_delete_post( (int) $id, true );
	}
}

global $wpdb;
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '\\_transient\\_sena\\_studio\\_%' OR option_name LIKE '\\_transient\\_timeout\\_sena\\_studio\\_%'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
