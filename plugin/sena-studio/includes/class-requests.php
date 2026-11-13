<?php
/**
 * Custom order requests: CPT, public form shortcode, inbox and quoting.
 *
 * @package SenaStudio
 */

defined( 'ABSPATH' ) || exit;

final class Sena_Studio_Requests {

	public const CPT = 'sena_request';

	public static function init(): void {
		add_action( 'init', array( __CLASS__, 'register_post_type' ) );
		add_action( 'init', array( __CLASS__, 'maybe_handle_front_submit' ), 20 );
		add_shortcode( 'sena_custom_order_form', array( __CLASS__, 'shortcode_form' ) );

		add_action( 'admin_post_sena_studio_request_update', array( __CLASS__, 'handle_update' ) );
		add_action( 'admin_post_sena_studio_request_quote', array( __CLASS__, 'handle_quote' ) );
		add_action( 'admin_post_sena_studio_request_delete', array( __CLASS__, 'handle_delete' ) );
	}

	public static function register_post_type(): void {
		if ( post_type_exists( self::CPT ) ) {
			return;
		}
		register_post_type(
			self::CPT,
			array(
				'labels'              => array(
					'name'          => __( 'Custom requests', 'sena-studio' ),
					'singular_name' => __( 'Custom request', 'sena-studio' ),
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

	public static function statuses(): array {
		return array(
			'new'          => __( 'New', 'sena-studio' ),
			'quoted'       => __( 'Quoted', 'sena-studio' ),
			'deposit_paid' => __( 'Deposit paid', 'sena-studio' ),
			'in_progress'  => __( 'In progress', 'sena-studio' ),
			'done'         => __( 'Done', 'sena-studio' ),
			'declined'     => __( 'Declined', 'sena-studio' ),
		);
	}

	public static function budgets(): array {
		return array(
			'under_50'  => __( 'Under $50', 'sena-studio' ),
			'50_100'    => __( '$50 – $100', 'sena-studio' ),
			'100_250'   => __( '$100 – $250', 'sena-studio' ),
			'250_500'   => __( '$250 – $500', 'sena-studio' ),
			'over_500'  => __( 'Over $500', 'sena-studio' ),
			'not_sure'  => __( 'Not sure yet', 'sena-studio' ),
		);
	}

	/**
	 * @return WP_Post[]
	 */
	public static function query( string $status = '', int $limit = 100 ): array {
		$args = array(
			'post_type'      => self::CPT,
			'post_status'    => 'publish',
			'posts_per_page' => $limit,
			'orderby'        => 'date',
			'order'          => 'DESC',
		);
		if ( $status && isset( self::statuses()[ $status ] ) ) {
			$args['meta_query'] = array( array( 'key' => '_sena_req_status', 'value' => $status ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
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
				'meta_query'     => array( array( 'key' => '_sena_req_status', 'value' => $status ) ), // phpcs:ignore WordPress.DB.SlowDBQuery
			)
		);
		return (int) $q->found_posts;
	}

	public static function get( int $id ): array {
		$post = get_post( $id );
		if ( ! $post || self::CPT !== $post->post_type ) {
			return array();
		}
		$meta = static fn( string $k ) => (string) get_post_meta( $id, $k, true );
		return array(
			'id'       => $id,
			'post'     => $post,
			'name'     => $meta( '_sena_req_name' ),
			'email'    => $meta( '_sena_req_email' ),
			'details'  => $post->post_content,
			'budget'   => $meta( '_sena_req_budget' ),
			'image_id' => (int) $meta( '_sena_req_image_id' ),
			'status'   => $meta( '_sena_req_status' ) ?: 'new',
			'quote'    => $meta( '_sena_req_quote' ),
			'notes'    => $meta( '_sena_req_notes' ),
			'order_id' => (int) $meta( '_sena_req_order_id' ),
			'date'     => get_the_date( get_option( 'date_format' ), $post ),
		);
	}

	/* ---------------- Front-end form ---------------- */

	public static function shortcode_form(): string {
		Sena_Studio::front_styles();
		$state = isset( $_GET['sena_request'] ) ? sanitize_key( wp_unslash( $_GET['sena_request'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$errors = get_transient( 'sena_request_errors_' . self::visitor_key() );
		$old    = get_transient( 'sena_request_old_' . self::visitor_key() );
		if ( $errors ) {
			delete_transient( 'sena_request_errors_' . self::visitor_key() );
			delete_transient( 'sena_request_old_' . self::visitor_key() );
		}
		ob_start();
		Sena_Studio::template(
			'custom-order-form',
			array(
				'sent'    => 'sent' === $state,
				'errors'  => is_array( $errors ) ? $errors : array(),
				'old'     => is_array( $old ) ? $old : array(),
				'budgets' => self::budgets(),
			)
		);
		return (string) ob_get_clean();
	}

	private static function visitor_key(): string {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		$ua = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
		return substr( md5( $ip . '|' . $ua . '|' . wp_salt( 'nonce' ) ), 0, 20 );
	}

	public static function maybe_handle_front_submit(): void {
		if ( is_admin() || empty( $_POST['sena_custom_order_submit'] ) ) {
			return;
		}
		$redirect = isset( $_POST['_wp_http_referer'] ) ? wp_validate_redirect( wp_unslash( $_POST['_wp_http_referer'] ), home_url( '/' ) ) : home_url( '/' );
		$redirect = remove_query_arg( 'sena_request', $redirect );

		$fail = static function ( array $errors, array $old ) use ( $redirect ): void {
			set_transient( 'sena_request_errors_' . self::visitor_key(), $errors, 300 );
			set_transient( 'sena_request_old_' . self::visitor_key(), $old, 300 );
			wp_safe_redirect( add_query_arg( 'sena_request', 'error', $redirect ) );
			exit;
		};

		if ( ! isset( $_POST['sena_request_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['sena_request_nonce'] ) ), 'sena_custom_order' ) ) {
			$fail( array( __( 'Your session expired. Please try again.', 'sena-studio' ) ), array() );
		}
		// Honeypot: real people never fill this.
		if ( ! empty( $_POST['sena_website'] ) ) {
			wp_safe_redirect( add_query_arg( 'sena_request', 'sent', $redirect ) );
			exit;
		}
		// Simple rate limit per visitor.
		if ( get_transient( 'sena_request_rl_' . self::visitor_key() ) ) {
			$fail( array( __( 'Please wait a minute before sending another request.', 'sena-studio' ) ), array() );
		}

		$name    = isset( $_POST['sena_name'] ) ? sanitize_text_field( wp_unslash( $_POST['sena_name'] ) ) : '';
		$email   = isset( $_POST['sena_email'] ) ? sanitize_email( wp_unslash( $_POST['sena_email'] ) ) : '';
		$details = isset( $_POST['sena_details'] ) ? sanitize_textarea_field( wp_unslash( $_POST['sena_details'] ) ) : '';
		$budget  = isset( $_POST['sena_budget'] ) ? sanitize_key( wp_unslash( $_POST['sena_budget'] ) ) : '';
		$old     = compact( 'name', 'email', 'details', 'budget' );
		$errors  = array();

		if ( mb_strlen( $name ) < 2 ) {
			$errors[] = __( 'Please tell me your name.', 'sena-studio' );
		}
		if ( ! is_email( $email ) ) {
			$errors[] = __( 'Please enter a valid email address.', 'sena-studio' );
		}
		if ( mb_strlen( $details ) < 10 ) {
			$errors[] = __( 'Please describe what you have in mind (a sentence or two).', 'sena-studio' );
		}
		if ( ! isset( self::budgets()[ $budget ] ) ) {
			$budget = 'not_sure';
		}

		$image_id = 0;
		if ( ! empty( $_FILES['sena_image'] ) && UPLOAD_ERR_NO_FILE !== (int) $_FILES['sena_image']['error'] ) {
			$result = self::handle_image_upload( $_FILES['sena_image'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			if ( is_wp_error( $result ) ) {
				$errors[] = $result->get_error_message();
			} else {
				$image_id = $result;
			}
		}

		if ( $errors ) {
			$fail( $errors, $old );
		}

		$id = wp_insert_post(
			array(
				'post_type'    => self::CPT,
				'post_status'  => 'publish',
				'post_title'   => sprintf( /* translators: 1: name 2: date */ __( '%1$s – %2$s', 'sena-studio' ), $name, wp_date( 'M j' ) ),
				'post_content' => $details,
				'meta_input'   => array(
					'_sena_req_name'     => $name,
					'_sena_req_email'    => $email,
					'_sena_req_budget'   => $budget,
					'_sena_req_image_id' => $image_id,
					'_sena_req_status'   => 'new',
					'_sena_req_quote'    => '',
					'_sena_req_notes'    => '',
				),
			),
			true
		);

		if ( is_wp_error( $id ) ) {
			$fail( array( __( 'Something went wrong saving your request. Please email me directly.', 'sena-studio' ) ), $old );
		}
		if ( $image_id ) {
			wp_update_post( array( 'ID' => $image_id, 'post_parent' => $id ) );
		}

		set_transient( 'sena_request_rl_' . self::visitor_key(), 1, MINUTE_IN_SECONDS );
		self::notify_admin( (int) $id );

		wp_safe_redirect( add_query_arg( 'sena_request', 'sent', $redirect ) . '#sena-custom-order' );
		exit;
	}

	/**
	 * Validate and store a reference image (≤ 5 MB, image types only).
	 *
	 * @return int|WP_Error
	 */
	private static function handle_image_upload( array $file ) {
		if ( UPLOAD_ERR_OK !== (int) $file['error'] ) {
			return new WP_Error( 'upload', __( 'The image could not be uploaded.', 'sena-studio' ) );
		}
		if ( (int) $file['size'] > 5 * MB_IN_BYTES ) {
			return new WP_Error( 'upload', __( 'The image must be 5 MB or smaller.', 'sena-studio' ) );
		}
		$allowed = array(
			'jpg|jpeg|jpe' => 'image/jpeg',
			'png'          => 'image/png',
			'webp'         => 'image/webp',
			'gif'          => 'image/gif',
			'heic'         => 'image/heic',
		);
		$check = wp_check_filetype_and_ext( $file['tmp_name'], $file['name'], $allowed );
		if ( empty( $check['ext'] ) || empty( $check['type'] ) ) {
			return new WP_Error( 'upload', __( 'Please upload a JPG, PNG, WebP, GIF or HEIC image.', 'sena-studio' ) );
		}
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$_FILES['sena_image_checked'] = array(
			'name'     => sanitize_file_name( $file['name'] ),
			'type'     => $check['type'],
			'tmp_name' => $file['tmp_name'],
			'error'    => $file['error'],
			'size'     => $file['size'],
		);
		$id = media_handle_upload( 'sena_image_checked', 0, array( 'post_title' => __( 'Custom request reference', 'sena-studio' ) ), array( 'test_form' => false, 'mimes' => $allowed ) );
		unset( $_FILES['sena_image_checked'] );
		if ( is_wp_error( $id ) ) {
			return new WP_Error( 'upload', __( 'The image could not be saved.', 'sena-studio' ) );
		}
		return (int) $id;
	}

	private static function notify_admin( int $id ): void {
		$r  = self::get( $id );
		$to = Sena_Studio::setting( 'notification_email' );
		if ( ! is_email( $to ) ) {
			$to = get_option( 'admin_email' );
		}
		$subject = sprintf( /* translators: %s: customer name */ __( 'New custom request from %s', 'sena-studio' ), $r['name'] );
		$lines   = array(
			sprintf( '%s: %s', __( 'Name', 'sena-studio' ), $r['name'] ),
			sprintf( '%s: %s', __( 'Email', 'sena-studio' ), $r['email'] ),
			sprintf( '%s: %s', __( 'Budget', 'sena-studio' ), self::budgets()[ $r['budget'] ] ?? $r['budget'] ),
			'',
			$r['details'],
			'',
		);
		if ( $r['image_id'] ) {
			$lines[] = __( 'Reference image:', 'sena-studio' ) . ' ' . wp_get_attachment_url( $r['image_id'] );
			$lines[] = '';
		}
		$lines[] = __( 'Open in Studio:', 'sena-studio' ) . ' ' . Sena_Studio::url( 'requests', array( 'request' => $id ) );
		wp_mail( $to, $subject, implode( "\n", $lines ), array( 'Reply-To: ' . $r['name'] . ' <' . $r['email'] . '>' ) );
	}

	/* ---------------- Studio tab ---------------- */

	public static function render_tab(): void {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$status = isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : 'open';
		$focus  = isset( $_GET['request'] ) ? absint( $_GET['request'] ) : 0;
		// phpcs:enable

		if ( 'open' === $status ) {
			$requests = array_values( array_filter( self::query( '', 200 ), static fn( $p ) => ! in_array( get_post_meta( $p->ID, '_sena_req_status', true ), array( 'done', 'declined' ), true ) ) );
		} elseif ( 'all' === $status ) {
			$requests = self::query( '', 200 );
		} else {
			$requests = self::query( $status, 200 );
		}

		$counts = array();
		foreach ( array_keys( self::statuses() ) as $s ) {
			$counts[ $s ] = self::count( $s );
		}

		Sena_Studio::template(
			'requests',
			array(
				'requests' => $requests,
				'status'   => $status,
				'statuses' => self::statuses(),
				'budgets'  => self::budgets(),
				'counts'   => $counts,
				'detail'   => $focus ? self::get( $focus ) : array(),
				'settings' => Sena_Studio::settings(),
				'has_wc'   => Sena_Studio::has_woocommerce(),
			)
		);
	}

	private static function request_from_post( string $action ): array {
		Sena_Studio::verify_action( $action );
		$id = isset( $_REQUEST['request_id'] ) ? absint( $_REQUEST['request_id'] ) : 0;
		$r  = $id ? self::get( $id ) : array();
		if ( ! $r ) {
			Sena_Studio::redirect( 'requests', __( 'Request not found.', 'sena-studio' ), 'error' );
		}
		return $r;
	}

	public static function handle_update(): void {
		$r      = self::request_from_post( 'sena_studio_request_update' );
		$status = isset( $_POST['status'] ) ? sanitize_key( wp_unslash( $_POST['status'] ) ) : $r['status'];
		$quote  = isset( $_POST['quote'] ) ? sanitize_text_field( wp_unslash( $_POST['quote'] ) ) : '';
		$quote  = '' === $quote ? '' : number_format( (float) str_replace( ',', '.', $quote ), 2, '.', '' );
		$notes  = isset( $_POST['notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['notes'] ) ) : '';

		if ( ! isset( self::statuses()[ $status ] ) ) {
			$status = $r['status'];
		}
		update_post_meta( $r['id'], '_sena_req_status', $status );
		update_post_meta( $r['id'], '_sena_req_quote', $quote );
		update_post_meta( $r['id'], '_sena_req_notes', $notes );

		Sena_Studio::redirect( 'requests', __( 'Request updated.', 'sena-studio' ), 'success', array( 'request' => $r['id'] ) );
	}

	public static function handle_delete(): void {
		$r = self::request_from_post( 'sena_studio_request_delete' );
		wp_trash_post( $r['id'] );
		Sena_Studio::redirect( 'requests', __( 'Request moved to trash.', 'sena-studio' ) );
	}

	/**
	 * Create a WooCommerce order for the quote (full or deposit) and email the pay link.
	 */
	public static function handle_quote(): void {
		$r = self::request_from_post( 'sena_studio_request_quote' );
		if ( ! Sena_Studio::has_woocommerce() ) {
			Sena_Studio::redirect( 'requests', __( 'WooCommerce is needed to send a quote.', 'sena-studio' ), 'error', array( 'request' => $r['id'] ) );
		}

		$quote = isset( $_POST['quote'] ) ? (float) str_replace( ',', '.', sanitize_text_field( wp_unslash( $_POST['quote'] ) ) ) : 0.0;
		$mode  = isset( $_POST['mode'] ) && 'full' === $_POST['mode'] ? 'full' : 'deposit';
		if ( $quote <= 0 ) {
			Sena_Studio::redirect( 'requests', __( 'Enter a quote amount first.', 'sena-studio' ), 'error', array( 'request' => $r['id'] ) );
		}
		update_post_meta( $r['id'], '_sena_req_quote', number_format( $quote, 2, '.', '' ) );

		$percent = (int) Sena_Studio::setting( 'deposit_percent' );
		$amount  = 'full' === $mode ? $quote : round( $quote * max( 1, min( 100, $percent ) ) / 100, 2 );
		$label   = 'full' === $mode ? __( 'Custom order', 'sena-studio' ) : __( 'Custom order deposit', 'sena-studio' );

		$product = self::deposit_product();
		if ( ! $product ) {
			Sena_Studio::redirect( 'requests', __( 'Could not create the deposit product.', 'sena-studio' ), 'error', array( 'request' => $r['id'] ) );
		}

		$order = wc_create_order( array( 'created_via' => 'sena-studio' ) );
		if ( is_wp_error( $order ) ) {
			Sena_Studio::redirect( 'requests', $order->get_error_message(), 'error', array( 'request' => $r['id'] ) );
		}

		$item_id = $order->add_product( $product, 1, array( 'subtotal' => $amount, 'total' => $amount ) );
		$item    = $order->get_item( $item_id );
		if ( $item ) {
			$item->set_name( $label . ' – ' . $r['name'] );
			$item->add_meta_data( __( 'Request', 'sena-studio' ), '#' . $r['id'] . ' ' . wp_trim_words( $r['details'], 12 ), true );
			if ( 'deposit' === $mode ) {
				$item->add_meta_data( __( 'Full quote', 'sena-studio' ), Sena_Studio::money( $quote ), true );
			}
			$item->save();
		}

		$names = explode( ' ', $r['name'], 2 );
		$order->set_billing_first_name( $names[0] );
		$order->set_billing_last_name( $names[1] ?? '' );
		$order->set_billing_email( $r['email'] );
		$customer = get_user_by( 'email', $r['email'] );
		if ( $customer ) {
			$order->set_customer_id( $customer->ID );
		}
		$order->set_customer_note( wp_trim_words( $r['details'], 40 ) );
		$order->calculate_totals();
		$order->update_status( 'pending', __( 'Quote created from Sena Studio request #', 'sena-studio' ) . $r['id'] );
		$order->update_meta_data( '_sena_request_id', $r['id'] );
		$order->save();

		update_post_meta( $r['id'], '_sena_req_order_id', $order->get_id() );
		update_post_meta( $r['id'], '_sena_req_status', 'quoted' );

		self::email_pay_link( $order, $r, $quote, $amount, $mode );

		Sena_Studio::redirect(
			'requests',
			sprintf( /* translators: 1: amount 2: email */ __( 'Quote sent: %1$s pay link emailed to %2$s.', 'sena-studio' ), Sena_Studio::money( $amount ), $r['email'] ),
			'success',
			array( 'request' => $r['id'] )
		);
	}

	/**
	 * Hidden, virtual product used as the line item for quotes/deposits.
	 */
	private static function deposit_product(): ?WC_Product {
		$id = wc_get_product_id_by_sku( 'sena-custom-deposit' );
		if ( $id ) {
			$p = wc_get_product( $id );
			return $p instanceof WC_Product ? $p : null;
		}
		$p = new WC_Product_Simple();
		$p->set_name( __( 'Custom order deposit', 'sena-studio' ) );
		$p->set_sku( 'sena-custom-deposit' );
		$p->set_status( 'private' );
		$p->set_catalog_visibility( 'hidden' );
		$p->set_virtual( true );
		$p->set_regular_price( '0' );
		$p->set_sold_individually( true );
		$p->set_reviews_allowed( false );
		$p->save();
		return $p;
	}

	private static function email_pay_link( WC_Order $order, array $r, float $quote, float $amount, string $mode ): void {
		$pay_url = $order->get_checkout_payment_url();
		$shop    = get_bloginfo( 'name' );
		$subject = sprintf( /* translators: %s: shop name */ __( 'Your custom order quote from %s', 'sena-studio' ), $shop );

		$first = explode( ' ', trim( $r['name'] ) )[0];
		$body  = sprintf( /* translators: %s: first name */ __( 'Hi %s,', 'sena-studio' ), $first ) . "\n\n";
		$body .= __( 'Thank you for your custom order request. Here is your quote:', 'sena-studio' ) . "\n\n";
		$body .= sprintf( '%s: %s', __( 'Total', 'sena-studio' ), Sena_Studio::money( $quote ) ) . "\n";
		if ( 'deposit' === $mode ) {
			$body .= sprintf( '%s: %s', __( 'Deposit to start', 'sena-studio' ), Sena_Studio::money( $amount ) ) . "\n";
			$body .= __( 'The remaining balance is due when the piece is finished.', 'sena-studio' ) . "\n";
		}
		$body .= "\n" . __( 'Pay securely here:', 'sena-studio' ) . "\n" . $pay_url . "\n\n";
		$body .= __( 'Just reply to this email if you have questions or want to tweak anything.', 'sena-studio' ) . "\n\n" . $shop;

		$from = Sena_Studio::setting( 'notification_email' );
		$headers = is_email( $from ) ? array( 'Reply-To: ' . $from ) : array();
		wp_mail( $r['email'], $subject, $body, $headers );
	}
}
