<?php
/**
 * Orders: "packed" status, tracking meta, packing slips, customer-facing tracking.
 *
 * @package SenaStudio
 */

defined( 'ABSPATH' ) || exit;

final class Sena_Studio_Orders {

	public const META_CARRIER = '_sena_tracking_carrier';
	public const META_NUMBER  = '_sena_tracking_number';

	public static function init(): void {
		add_action( 'init', array( __CLASS__, 'register_status' ) );
		add_filter( 'wc_order_statuses', array( __CLASS__, 'add_status' ) );
		add_filter( 'woocommerce_order_is_paid_statuses', array( __CLASS__, 'paid_statuses' ) );
		add_filter( 'woocommerce_reports_order_statuses', array( __CLASS__, 'report_statuses' ) );
		add_filter( 'woocommerce_valid_order_statuses_for_payment_complete', array( __CLASS__, 'report_statuses' ) );

		add_action( 'admin_post_sena_studio_order_packed', array( __CLASS__, 'handle_packed' ) );
		add_action( 'admin_post_sena_studio_order_shipped', array( __CLASS__, 'handle_shipped' ) );
		add_action( 'admin_post_sena_studio_order_tracking', array( __CLASS__, 'handle_tracking' ) );
		add_action( 'admin_post_sena_studio_packing_slip', array( __CLASS__, 'handle_packing_slip' ) );
		add_action( 'admin_post_sena_studio_packing_slips', array( __CLASS__, 'handle_packing_slips' ) );

		add_action( 'woocommerce_email_after_order_table', array( __CLASS__, 'email_tracking' ), 10, 4 );
		add_action( 'woocommerce_order_details_after_order_table', array( __CLASS__, 'order_view_tracking' ) );

		add_shortcode( 'sena_track_order', array( __CLASS__, 'shortcode_track_order' ) );
	}

	/* ---------------- Status ---------------- */

	public static function register_status(): void {
		register_post_status(
			'wc-packed',
			array(
				'label'                     => _x( 'Packed', 'Order status', 'sena-studio' ),
				'public'                    => true,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				/* translators: %s: number of orders */
				'label_count'               => _n_noop( 'Packed <span class="count">(%s)</span>', 'Packed <span class="count">(%s)</span>', 'sena-studio' ),
			)
		);
	}

	public static function add_status( array $statuses ): array {
		$new = array();
		foreach ( $statuses as $key => $label ) {
			$new[ $key ] = $label;
			if ( 'wc-processing' === $key ) {
				$new['wc-packed'] = _x( 'Packed', 'Order status', 'sena-studio' );
			}
		}
		if ( ! isset( $new['wc-packed'] ) ) {
			$new['wc-packed'] = _x( 'Packed', 'Order status', 'sena-studio' );
		}
		return $new;
	}

	public static function paid_statuses( array $statuses ): array {
		$statuses[] = 'packed';
		return array_unique( $statuses );
	}

	public static function report_statuses( $statuses ): array {
		if ( ! is_array( $statuses ) ) {
			$statuses = array();
		}
		$statuses[] = 'packed';
		return array_unique( $statuses );
	}

	public static function carriers(): array {
		return array(
			'usps'  => 'USPS',
			'ups'   => 'UPS',
			'fedex' => 'FedEx',
			'other' => __( 'Other', 'sena-studio' ),
		);
	}

	public static function tracking_url( string $carrier, string $number ): string {
		$number = rawurlencode( $number );
		switch ( $carrier ) {
			case 'usps':
				return 'https://tools.usps.com/go/TrackConfirmAction?tLabels=' . $number;
			case 'ups':
				return 'https://www.ups.com/track?tracknum=' . $number;
			case 'fedex':
				return 'https://www.fedex.com/fedextrack/?trknbr=' . $number;
		}
		return '';
	}

	public static function tracking( WC_Order $order ): array {
		$carrier = (string) $order->get_meta( self::META_CARRIER );
		$number  = (string) $order->get_meta( self::META_NUMBER );
		return array(
			'carrier'       => $carrier,
			'carrier_label' => self::carriers()[ $carrier ] ?? ucfirst( $carrier ),
			'number'        => $number,
			'url'           => $number ? self::tracking_url( $carrier, $number ) : '',
		);
	}

	public static function status_label( WC_Order $order ): string {
		return wc_get_order_status_name( $order->get_status() );
	}

	/* ---------------- Tab ---------------- */

	public static function render_tab(): void {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$filter   = isset( $_GET['filter'] ) ? sanitize_key( wp_unslash( $_GET['filter'] ) ) : 'pack';
		$search   = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
		$focus_id = isset( $_GET['order'] ) ? absint( $_GET['order'] ) : 0;
		// phpcs:enable

		if ( ! in_array( $filter, array( 'pack', 'shipped', 'all' ), true ) ) {
			$filter = 'pack';
		}

		$args = array(
			'limit'   => 60,
			'orderby' => 'date',
			'order'   => 'DESC',
		);
		switch ( $filter ) {
			case 'pack':
				$args['status'] = array( 'processing', 'packed', 'on-hold' );
				$args['order']  = 'ASC';
				break;
			case 'shipped':
				$args['status'] = array( 'completed' );
				break;
			default:
				$args['status'] = array_keys( wc_get_order_statuses() );
		}

		if ( '' !== $search ) {
			if ( ctype_digit( ltrim( $search, '#' ) ) ) {
				$order  = wc_get_order( (int) ltrim( $search, '#' ) );
				$orders = $order instanceof WC_Order ? array( $order ) : array();
			} else {
				$ids    = wc_order_search( $search );
				$orders = $ids ? wc_get_orders( array( 'include' => array_map( 'absint', $ids ), 'limit' => 100, 'status' => array_keys( wc_get_order_statuses() ) ) ) : array();
			}
		} else {
			$orders = wc_get_orders( $args );
		}

		Sena_Studio::template(
			'orders',
			array(
				'orders'   => $orders,
				'filter'   => $filter,
				'search'   => $search,
				'focus_id' => $focus_id,
				'carriers' => self::carriers(),
				'settings' => Sena_Studio::settings(),
			)
		);
	}

	/* ---------------- Actions ---------------- */

	private static function order_from_request( string $action ): WC_Order {
		Sena_Studio::verify_action( $action );
		$id    = isset( $_REQUEST['order_id'] ) ? absint( $_REQUEST['order_id'] ) : 0;
		$order = $id ? wc_get_order( $id ) : false;
		if ( ! $order instanceof WC_Order ) {
			Sena_Studio::redirect( 'orders', __( 'Order not found.', 'sena-studio' ), 'error' );
		}
		return $order;
	}

	private static function back_args(): array {
		$args = array();
		foreach ( array( 'filter', 's' ) as $k ) {
			if ( isset( $_REQUEST[ $k ] ) ) {
				$args[ $k ] = sanitize_text_field( wp_unslash( $_REQUEST[ $k ] ) );
			}
		}
		return $args;
	}

	public static function handle_packed(): void {
		$order = self::order_from_request( 'sena_studio_order_packed' );
		$order->update_status( 'packed', __( 'Marked packed from Sena Studio.', 'sena-studio' ) );
		Sena_Studio::redirect( 'orders', sprintf( /* translators: %s: order number */ __( 'Order #%s marked packed.', 'sena-studio' ), $order->get_order_number() ), 'success', self::back_args() );
	}

	public static function handle_shipped(): void {
		$order = self::order_from_request( 'sena_studio_order_shipped' );
		$order->update_status( 'completed', __( 'Marked shipped from Sena Studio.', 'sena-studio' ) );
		Sena_Studio::redirect( 'orders', sprintf( /* translators: %s: order number */ __( 'Order #%s marked shipped. The customer has been emailed.', 'sena-studio' ), $order->get_order_number() ), 'success', self::back_args() );
	}

	public static function handle_tracking(): void {
		$order   = self::order_from_request( 'sena_studio_order_tracking' );
		$carrier = isset( $_POST['carrier'] ) ? sanitize_key( wp_unslash( $_POST['carrier'] ) ) : 'other';
		$number  = isset( $_POST['tracking_number'] ) ? sanitize_text_field( wp_unslash( $_POST['tracking_number'] ) ) : '';
		$number  = preg_replace( '/[^A-Za-z0-9\-\s]/', '', $number );
		$number  = trim( (string) $number );

		if ( ! isset( self::carriers()[ $carrier ] ) ) {
			$carrier = 'other';
		}

		$order->update_meta_data( self::META_CARRIER, $carrier );
		$order->update_meta_data( self::META_NUMBER, $number );
		$order->save();
		if ( $number ) {
			$order->add_order_note( sprintf( /* translators: 1: carrier 2: tracking number */ __( 'Tracking added: %1$s %2$s', 'sena-studio' ), self::carriers()[ $carrier ], $number ) );
		}

		$also_ship = ! empty( $_POST['mark_shipped'] );
		if ( $also_ship && $number ) {
			$order->update_status( 'completed', __( 'Marked shipped from Sena Studio.', 'sena-studio' ) );
			$msg = __( 'Tracking saved and order marked shipped.', 'sena-studio' );
		} else {
			$msg = __( 'Tracking saved.', 'sena-studio' );
		}
		Sena_Studio::redirect( 'orders', $msg, 'success', self::back_args() );
	}

	public static function handle_packing_slip(): void {
		$order = self::order_from_request( 'sena_studio_packing_slip' );
		nocache_headers();
		header( 'Content-Type: text/html; charset=' . get_option( 'blog_charset' ) );
		Sena_Studio::template(
			'packing-slip',
			array(
				'order'    => $order,
				'settings' => Sena_Studio::settings(),
				'tracking' => self::tracking( $order ),
			)
		);
		exit;
	}

	/**
	 * One printable page with a packing slip per order that needs packing.
	 */
	public static function handle_packing_slips(): void {
		Sena_Studio::verify_action( 'sena_studio_packing_slips' );
		$orders = wc_get_orders(
			array(
				'status'  => array( 'processing' ),
				'limit'   => 100,
				'orderby' => 'date',
				'order'   => 'ASC',
				'return'  => 'objects',
			)
		);
		$orders = array_values( array_filter( $orders, static fn( $o ) => $o instanceof WC_Order ) );
		nocache_headers();
		header( 'Content-Type: text/html; charset=' . get_option( 'blog_charset' ) );
		Sena_Studio::template(
			'packing-slips',
			array(
				'orders'   => $orders,
				'settings' => Sena_Studio::settings(),
			)
		);
		exit;
	}

	/* ---------------- Customer-facing ---------------- */

	public static function email_tracking( $order, $sent_to_admin, $plain_text, $email ): void {
		if ( ! $order instanceof WC_Order || $sent_to_admin ) {
			return;
		}
		if ( ! $email || 'customer_completed_order' !== $email->id ) {
			return;
		}
		$t = self::tracking( $order );
		if ( ! $t['number'] ) {
			return;
		}
		if ( $plain_text ) {
			echo "\n" . esc_html__( 'Tracking', 'sena-studio' ) . ': ' . esc_html( $t['carrier_label'] . ' ' . $t['number'] ) . "\n";
			if ( $t['url'] ) {
				echo esc_url( $t['url'] ) . "\n";
			}
			return;
		}
		echo '<p style="margin:16px 0;padding:12px 16px;background:#f6efe3;border-radius:12px;">';
		echo '<strong>' . esc_html__( 'Your package is on its way.', 'sena-studio' ) . '</strong><br>';
		echo esc_html( $t['carrier_label'] ) . ' ';
		if ( $t['url'] ) {
			echo '<a href="' . esc_url( $t['url'] ) . '">' . esc_html( $t['number'] ) . '</a>';
		} else {
			echo esc_html( $t['number'] );
		}
		echo '</p>';
	}

	public static function order_view_tracking( $order ): void {
		if ( ! $order instanceof WC_Order ) {
			return;
		}
		$t = self::tracking( $order );
		if ( ! $t['number'] ) {
			return;
		}
		echo '<section class="sena-tracking"><h2>' . esc_html__( 'Tracking', 'sena-studio' ) . '</h2><p>' . esc_html( $t['carrier_label'] ) . ' ';
		if ( $t['url'] ) {
			echo '<a href="' . esc_url( $t['url'] ) . '" rel="noopener" target="_blank">' . esc_html( $t['number'] ) . '</a>';
		} else {
			echo esc_html( $t['number'] );
		}
		echo '</p></section>';
	}

	/**
	 * [sena_track_order] – order number + billing email lookup.
	 */
	public static function shortcode_track_order(): string {
		Sena_Studio::front_styles();
		$result = null;
		$error  = '';
		$number = '';
		$email  = '';

		if ( isset( $_POST['sena_track_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['sena_track_nonce'] ) ), 'sena_track_order' ) ) {
			$number = isset( $_POST['order_number'] ) ? sanitize_text_field( wp_unslash( $_POST['order_number'] ) ) : '';
			$email  = isset( $_POST['order_email'] ) ? sanitize_email( wp_unslash( $_POST['order_email'] ) ) : '';
			$id     = absint( ltrim( $number, '#' ) );
			$order  = $id ? wc_get_order( $id ) : false;

			if ( ! $order instanceof WC_Order || ! is_email( $email ) || strtolower( $order->get_billing_email() ) !== strtolower( $email ) ) {
				$error = __( 'We could not find an order with that number and email.', 'sena-studio' );
			} else {
				$result = array(
					'order'    => $order,
					'status'   => self::status_label( $order ),
					'tracking' => self::tracking( $order ),
				);
			}
		}

		ob_start();
		Sena_Studio::template(
			'track-order',
			array(
				'result' => $result,
				'error'  => $error,
				'number' => $number,
				'email'  => $email,
			)
		);
		return (string) ob_get_clean();
	}
}
