<?php
/**
 * Search form.
 *
 * @package Sena_Crafted
 */

defined( 'ABSPATH' ) || exit;
$sena_search_id = wp_unique_id( 'search-' );
?>
<form role="search" method="get" class="search-form" action="<?php echo esc_url( home_url( '/' ) ); ?>">
	<label class="visually-hidden" for="<?php echo esc_attr( $sena_search_id ); ?>"><?php esc_html_e( 'Search', 'sena-crafted' ); ?></label>
	<input type="search" id="<?php echo esc_attr( $sena_search_id ); ?>" name="s" value="<?php echo get_search_query(); ?>" placeholder="<?php esc_attr_e( 'Search…', 'sena-crafted' ); ?>">
	<button type="submit" class="btn small"><?php esc_html_e( 'Search', 'sena-crafted' ); ?></button>
</form>
