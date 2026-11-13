<?php
/**
 * "Today" tab: a small portal with live numbers, a monthly goal, charts,
 * best sellers, an automatic next-steps list and seasonal ideas.
 *
 * @package SenaStudio
 */

defined( 'ABSPATH' ) || exit;

final class Sena_Studio_Dashboard {

	/** Bump when the cached insights shape changes. */
	private const CACHE_VERSION = 3;
	private const CACHE_TTL     = 10 * MINUTE_IN_SECONDS;
	private const DISMISS_OPTION = 'sena_studio_dismissed_steps';
	public const MILESTONE_OPTION = 'sena_studio_milestones';
	public const META_REVIEW_ASKED = '_sena_review_asked';

	/** Order statuses that count as a sale. */
	private const SALE_STATUSES = array( 'processing', 'packed', 'completed', 'on-hold' );

	public static function init(): void {
		add_action( 'admin_post_sena_studio_save_goal', array( __CLASS__, 'handle_save_goal' ) );
		add_action( 'admin_post_sena_studio_dismiss_step', array( __CLASS__, 'handle_dismiss_step' ) );
		add_action( 'admin_post_sena_studio_review_asked', array( __CLASS__, 'handle_review_asked' ) );
		add_action( 'admin_post_sena_studio_draft_post', array( __CLASS__, 'handle_draft_post' ) );
		add_action( 'admin_post_sena_studio_milestone', array( __CLASS__, 'handle_milestone' ) );

		add_action( 'woocommerce_order_status_changed', array( __CLASS__, 'flush_cache' ) );
		add_action( 'woocommerce_new_order', array( __CLASS__, 'flush_cache' ) );
		add_action( 'woocommerce_order_refunded', array( __CLASS__, 'flush_cache' ) );
	}

	public static function render_tab(): void {
		Sena_Studio::template( 'today', array( 'data' => self::data() ) );
	}

	/* ------------------------------------------------------------------ */
	/* Cache                                                                */
	/* ------------------------------------------------------------------ */

	private static function cache_key(): string {
		return 'sena_studio_insights_' . wp_date( 'Ymd' );
	}

	public static function flush_cache(): void {
		delete_transient( self::cache_key() );
	}

	/* ------------------------------------------------------------------ */
	/* Data                                                                 */
	/* ------------------------------------------------------------------ */

	/**
	 * Collect everything the Today template needs. Every key exists even
	 * without WooCommerce or without a single order.
	 */
	public static function data(): array {
		$has_wc = Sena_Studio::has_woocommerce();
		$user   = wp_get_current_user();
		$tz     = wp_timezone();
		$now    = new DateTimeImmutable( 'now', $tz );
		$first  = trim( (string) $user->first_name );
		if ( '' === $first ) {
			$first = (string) strtok( trim( (string) $user->display_name ), ' ' );
		}

		$data = array(
			'has_wc'        => $has_wc,
			'first_name'    => $first,
			'greeting'      => self::greeting( (int) $now->format( 'G' ) ),
			'date_label'    => wp_date( 'l, ' . get_option( 'date_format' ), $now->getTimestamp() ),
			'pulse'         => '',
			'to_pack'       => array(),
			'to_pack_count' => 0,
			'requests_new'  => 0,
			'posts_pending' => 0,
			'low_stock'     => array(),
			'next_event'    => null,
			'goal'          => self::empty_goal( $now ),
			'stats'         => array(),
			'weeks'         => array(),
			'channels'      => array(),
			'has_sales'     => false,
			'best'          => array(),
			'unseen'        => array(),
			'steps'         => array(),
			'milestones'    => array(),
			'season'        => self::season_ideas( (int) $now->format( 'n' ) ),
			'visitors'      => self::visitors(),
		);

		// Studio CPTs (no WooCommerce needed).
		$data['requests_new']  = Sena_Studio_Requests::count( 'new' );
		$data['requests']      = Sena_Studio_Requests::query( 'new', 3 );
		$data['posts_pending'] = Sena_Studio_Posts::count( 'draft' );
		$data['next_event']    = Sena_Studio_Events::next_event();

		$insights = array();
		$products = array();

		if ( $has_wc ) {
			$data['to_pack']       = wc_get_orders(
				array(
					'status'  => array( 'processing' ),
					'limit'   => 50,
					'orderby' => 'date',
					'order'   => 'ASC',
				)
			);
			$data['to_pack_count'] = count( $data['to_pack'] );
			$data['low_stock']     = Sena_Studio_Products::low_stock( 8 );

			$insights = self::insights( $now );
			$products = self::product_facts( $insights['sold_ids'] ?? array() );

			$data['goal']      = self::goal( $insights, $now );
			$data['stats']     = self::stats( $insights, $products );
			$data['weeks']     = $insights['weeks'];
			$data['channels']  = $insights['channels'];
			$data['has_sales'] = ! empty( $insights['last_sale'] );
			$data['best']      = self::hydrate_best( $insights['best'] );
			$data['unseen']    = $products['unseen'];
		}

		$data['pulse'] = self::pulse( $data, $insights, $now );
		$data['steps']      = self::next_steps( $data, $insights, $products, $now );
		$data['milestones'] = self::milestones( $data, $insights, $products, $now );

		return $data;
	}

	private static function greeting( int $hour ): string {
		if ( $hour < 5 ) {
			return __( 'Still up', 'sena-studio' );
		}
		if ( $hour < 12 ) {
			return __( 'Good morning', 'sena-studio' );
		}
		if ( $hour < 18 ) {
			return __( 'Good afternoon', 'sena-studio' );
		}
		return __( 'Good evening', 'sena-studio' );
	}

	/**
	 * One warm sentence built from what is waiting and the day of the week.
	 */
	private static function pulse( array $d, array $insights, DateTimeImmutable $now ): string {
		$parts = array();
		if ( $d['to_pack_count'] > 0 ) {
			/* translators: %d: number of orders */
			$parts[] = sprintf( _n( '%d order to pack', '%d orders to pack', $d['to_pack_count'], 'sena-studio' ), $d['to_pack_count'] );
		}
		if ( $d['requests_new'] > 0 ) {
			/* translators: %d: number of requests */
			$parts[] = sprintf( _n( '%d new request', '%d new requests', $d['requests_new'], 'sena-studio' ), $d['requests_new'] );
		}
		if ( $d['posts_pending'] > 0 ) {
			/* translators: %d: number of posts */
			$parts[] = sprintf( _n( '%d post to approve', '%d posts to approve', $d['posts_pending'], 'sena-studio' ), $d['posts_pending'] );
		}

		if ( $parts ) {
			$last  = array_pop( $parts );
			$list  = $parts ? implode( ', ', $parts ) . ' ' . __( 'and', 'sena-studio' ) . ' ' . $last : $last;
			$first = ucfirst( $list ) . '.';
		} else {
			$first = __( 'Nothing is waiting on you.', 'sena-studio' );
		}

		$week      = (float) ( $insights['week_total'] ?? 0 );
		$last_week = (float) ( $insights['last_week_total'] ?? 0 );
		$dow       = (int) $now->format( 'N' );

		if ( $week > 0 && $week > $last_week ) {
			$mood = __( 'Already ahead of last week.', 'sena-studio' );
		} elseif ( $d['to_pack_count'] >= 3 ) {
			$mood = __( 'Busy, in the best way.', 'sena-studio' );
		} elseif ( ! $parts ) {
			$moods = array(
				1 => __( 'A fresh week and a clear bench. Make something new.', 'sena-studio' ),
				2 => __( 'A quiet day is a good day to photograph pieces.', 'sena-studio' ),
				3 => __( 'Halfway through the week. Maybe a process video?', 'sena-studio' ),
				4 => __( 'Clear bench. Time for the piece you keep sketching.', 'sena-studio' ),
				5 => __( 'Nearly the weekend. Prep the market table.', 'sena-studio' ),
				6 => __( 'Market day? Smile, it sells.', 'sena-studio' ),
				7 => __( 'A slow Sunday. Rest the hands.', 'sena-studio' ),
			);
			$mood  = $moods[ $dow ];
		} else {
			$moods = array(
				1 => __( 'Fresh week, fresh leather.', 'sena-studio' ),
				2 => __( 'Good stitching weather.', 'sena-studio' ),
				3 => __( 'Nice week so far.', 'sena-studio' ),
				4 => __( 'Keep the needle moving.', 'sena-studio' ),
				5 => __( 'Nearly the weekend.', 'sena-studio' ),
				6 => __( 'Saturday hustle.', 'sena-studio' ),
				7 => __( 'Sunday, gently.', 'sena-studio' ),
			);
			$mood  = $moods[ $dow ];
		}

		return $first . ' ' . $mood;
	}

	/* ------------------------------------------------------------------ */
	/* Insights (cached)                                                    */
	/* ------------------------------------------------------------------ */

	private static function week_start( DateTimeImmutable $now ): DateTimeImmutable {
		return $now->modify( '-' . ( (int) $now->format( 'N' ) - 1 ) . ' days' )->setTime( 0, 0 );
	}

	/**
	 * Heavy order maths for the last 90 days, cached for 10 minutes.
	 */
	private static function insights( DateTimeImmutable $now ): array {
		$cached = get_transient( self::cache_key() );
		if ( is_array( $cached ) && ( $cached['v'] ?? 0 ) === self::CACHE_VERSION ) {
			return $cached;
		}

		$week_start   = self::week_start( $now );
		$series_start = $week_start->modify( '-11 weeks' );
		$month_start  = $now->modify( 'first day of this month' )->setTime( 0, 0 );
		$since90      = $now->modify( '-90 days' )->setTime( 0, 0 );

		// All paid orders, once. A solo studio has hundreds, not millions; cached 10 minutes.
		$orders = wc_get_orders(
			array(
				'status'  => self::SALE_STATUSES,
				'limit'   => -1,
				'orderby' => 'date',
				'order'   => 'DESC',
				'return'  => 'objects',
			)
		);

		$weeks = array();
		for ( $i = 0; $i < 12; $i++ ) {
			$start     = $series_start->modify( '+' . $i . ' weeks' );
			$weeks[ $i ] = array(
				'start'   => $start->getTimestamp(),
				'label'   => wp_date( 'j M', $start->getTimestamp() ),
				'total'   => 0.0,
				'count'   => 0,
				'current' => 11 === $i,
			);
		}

		$month_total = 0.0;
		$month_count = 0;
		$channels    = array( 'website' => 0.0, 'custom' => 0.0, 'etsy' => 0.0 );
		$items       = array();
		$emails      = array();
		$sold_ids    = array();
		$week_ts     = $series_start->getTimestamp();
		$month_ts    = $month_start->getTimestamp();
		$since90_ts  = $since90->getTimestamp();
		$months      = array();
		$total_orders = 0;
		$web_order   = false;
		$last_sale   = 0;

		foreach ( $orders as $order ) {
			if ( ! $order instanceof WC_Order ) {
				continue;
			}
			$created = $order->get_date_created();
			$ts      = $created ? $created->getTimestamp() : 0;
			$net     = (float) $order->get_total() - (float) $order->get_total_refunded();

			$total_orders++;
			$last_sale = max( $last_sale, $ts );
			if ( 'admin' !== (string) $order->get_created_via() ) {
				$web_order = true;
			}
			$mkey            = $created ? $created->date_i18n( 'Y-m' ) : '0';
			$months[ $mkey ] = ( $months[ $mkey ] ?? 0.0 ) + $net;
			$email = strtolower( trim( (string) $order->get_billing_email() ) );
			if ( $email ) {
				$emails[ $email ] = ( $emails[ $email ] ?? 0 ) + 1;
			}

			if ( $ts >= $week_ts ) {
				$idx = (int) floor( ( $ts - $week_ts ) / WEEK_IN_SECONDS );
				if ( isset( $weeks[ $idx ] ) ) {
					$weeks[ $idx ]['total'] += $net;
					$weeks[ $idx ]['count']++;
				}
			}
			if ( $ts >= $month_ts ) {
				$month_total += $net;
				$month_count++;
			}
			if ( $ts >= $since90_ts ) {
				$channels[ self::channel( $order ) ] += $net;
				foreach ( $order->get_items() as $item ) {
					if ( ! $item instanceof WC_Order_Item_Product ) {
						continue;
					}
					$pid = (int) $item->get_product_id();
					if ( ! $pid ) {
						continue;
					}
					$sold_ids[ $pid ] = true;
					if ( ! isset( $items[ $pid ] ) ) {
						$items[ $pid ] = array( 'id' => $pid, 'units' => 0, 'revenue' => 0.0 );
					}
					$items[ $pid ]['units']   += (int) $item->get_quantity();
					$items[ $pid ]['revenue'] += (float) $item->get_total();
				}
			}
		}

		usort( $items, static fn( $a, $b ) => ( $b['units'] <=> $a['units'] ) ?: ( $b['revenue'] <=> $a['revenue'] ) );
		$best = array_slice( array_values( $items ), 0, 5 );

		$insights = array(
			'v'               => self::CACHE_VERSION,
			'weeks'           => array_values( $weeks ),
			'week_total'      => $weeks[11]['total'],
			'week_count'      => $weeks[11]['count'],
			'last_week_total' => $weeks[10]['total'],
			'month_total'     => $month_total,
			'month_count'     => $month_count,
			'aov'             => $month_count ? $month_total / $month_count : 0.0,
			'repeat'          => self::repeat_share( $emails ),
			'channels'        => $channels,
			'best'            => $best,
			'sold_ids'        => array_keys( $sold_ids ),
			'last_sale'       => $last_sale,
			'total_orders'    => $total_orders,
			'web_order'       => $web_order,
			'best_month'      => $months ? (float) max( $months ) : 0.0,
		);

		set_transient( self::cache_key(), $insights, self::CACHE_TTL );
		return $insights;
	}

	private static function channel( WC_Order $order ): string {
		if ( '' !== (string) $order->get_meta( '_etsy_order_id' ) ) {
			return 'etsy';
		}
		$via = (string) $order->get_created_via();
		if ( in_array( $via, array( 'sena-studio', 'sena-studio-quote' ), true ) || '' !== (string) $order->get_meta( '_sena_request_id' ) ) {
			return 'custom';
		}
		return 'website';
	}

	public static function channel_labels(): array {
		return array(
			'website' => __( 'Website', 'sena-studio' ),
			'custom'  => __( 'Custom orders', 'sena-studio' ),
			'etsy'    => __( 'Etsy', 'sena-studio' ),
		);
	}

	/**
	 * Share of customers with 2+ paid orders, all time via the WooCommerce
	 * analytics lookup table when it exists; otherwise from the last 90 days.
	 *
	 * @return array{share:float,customers:int}
	 */
	private static function repeat_share( array $emails ): array {
		global $wpdb;
		$table = $wpdb->prefix . 'wc_order_stats';
		$counts = array();
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) === $table ) { // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$statuses = array_map( static fn( $s ) => 'wc-' . $s, self::SALE_STATUSES );
			$in       = implode( ',', array_fill( 0, count( $statuses ), '%s' ) );
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$counts = $wpdb->get_col( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE customer_id IS NOT NULL AND customer_id > 0 AND parent_id = 0 AND status IN ({$in}) GROUP BY customer_id", $statuses ) );
			$counts = array_map( 'intval', (array) $counts );
		}
		if ( ! $counts ) {
			$counts = array_values( $emails );
		}
		$total  = count( $counts );
		$repeat = count( array_filter( $counts, static fn( $c ) => $c >= 2 ) );
		return array(
			'share'     => $total ? $repeat / $total : 0.0,
			'customers' => $total,
			'repeat'    => $repeat,
		);
	}

	/* ------------------------------------------------------------------ */
	/* Products                                                             */
	/* ------------------------------------------------------------------ */

	/**
	 * One pass over published products: available count, missing prices,
	 * too few photos and pieces nobody bought in 90 days.
	 */
	private static function product_facts( array $sold_ids ): array {
		$facts = array(
			'total'      => 0,
			'available'  => 0,
			'no_price'   => array(),
			'few_photos' => array(),
			'unseen'     => array(),
		);
		$products = wc_get_products(
			array(
				'limit'   => -1,
				'status'  => 'publish',
				'orderby' => 'date',
				'order'   => 'ASC',
				'return'  => 'objects',
			)
		);
		$sold = array_fill_keys( array_map( 'intval', $sold_ids ), true );
		foreach ( $products as $product ) {
			if ( ! $product instanceof WC_Product ) {
				continue;
			}
			$facts['total']++;
			$status = Sena_Studio_Products::status( $product );
			$id     = $product->get_id();
			$sellable = in_array( $status, array( 'available', 'made_to_order' ), true ) && $product->is_in_stock();
			if ( $sellable ) {
				$facts['available']++;
			}
			if ( '' === (string) $product->get_price() && 'showcase' !== $status ) {
				$facts['no_price'][] = $product;
			}
			$photos = ( $product->get_image_id() ? 1 : 0 ) + count( $product->get_gallery_image_ids() );
			if ( $photos < 2 ) {
				$facts['few_photos'][] = $product;
			}
			if ( $sellable && ! isset( $sold[ $id ] ) && count( $facts['unseen'] ) < 5 ) {
				$facts['unseen'][] = array(
					'id'    => $id,
					'name'  => $product->get_name(),
					'price' => $product->get_price(),
					'thumb' => $product->get_image_id() ? wp_get_attachment_image_url( $product->get_image_id(), 'thumbnail' ) : '',
					'url'   => Sena_Studio::url( 'products', array( 's' => $product->get_name() ) ),
				);
			}
		}
		return $facts;
	}

	private static function hydrate_best( array $best ): array {
		$out = array();
		foreach ( $best as $row ) {
			$product = wc_get_product( (int) $row['id'] );
			$out[]   = array(
				'id'      => (int) $row['id'],
				'name'    => $product ? $product->get_name() : get_the_title( (int) $row['id'] ),
				'units'   => (int) $row['units'],
				'revenue' => (float) $row['revenue'],
				'thumb'   => $product && $product->get_image_id() ? wp_get_attachment_image_url( $product->get_image_id(), 'thumbnail' ) : '',
				'url'     => $product ? Sena_Studio::url( 'products', array( 's' => $product->get_name() ) ) : Sena_Studio::url( 'products' ),
			);
		}
		return $out;
	}

	/* ------------------------------------------------------------------ */
	/* Goal & stats                                                         */
	/* ------------------------------------------------------------------ */

	public static function goal_amount(): float {
		return max( 0.0, (float) Sena_Studio::setting( 'monthly_goal' ) );
	}

	private static function empty_goal( DateTimeImmutable $now ): array {
		$days_in_month = (int) $now->format( 't' );
		$day           = (int) $now->format( 'j' );
		$amount        = self::goal_amount();
		return array(
			'amount'        => $amount,
			'sales'         => 0.0,
			'percent'       => 0,
			'remaining'     => $amount,
			'days_left'     => $days_in_month - $day + 1,
			'per_day'       => $amount / max( 1, $days_in_month - $day + 1 ),
			'behind'        => false,
			'month_label'   => wp_date( 'F', $now->getTimestamp() ),
			'day'           => $day,
			'days_in_month' => $days_in_month,
		);
	}

	private static function goal( array $insights, DateTimeImmutable $now ): array {
		$g              = self::empty_goal( $now );
		$g['sales']     = (float) $insights['month_total'];
		$g['remaining'] = max( 0.0, $g['amount'] - $g['sales'] );
		$g['percent']   = $g['amount'] > 0 ? (int) min( 100, round( $g['sales'] / $g['amount'] * 100 ) ) : 0;
		$g['per_day']   = $g['remaining'] / max( 1, $g['days_left'] );
		$expected       = $g['amount'] * $g['day'] / max( 1, $g['days_in_month'] );
		$g['behind']    = $g['amount'] > 0 && $g['day'] > 10 && $g['sales'] < $expected * 0.9;
		return $g;
	}

	private static function stats( array $insights, array $products ): array {
		$week  = (float) $insights['week_total'];
		$last  = (float) $insights['last_week_total'];
		$trend = 'flat';
		if ( $week > $last ) {
			$trend = 'up';
		} elseif ( $week < $last ) {
			$trend = 'down';
		}
		return array(
			'week'      => $week,
			'last_week' => $last,
			'trend'     => $trend,
			'orders'    => (int) $insights['month_count'],
			'aov'       => (float) $insights['aov'],
			'repeat'    => (float) $insights['repeat']['share'],
			'customers' => (int) $insights['repeat']['customers'],
			'available' => (int) $products['available'],
		);
	}

	/* ------------------------------------------------------------------ */
	/* Visitors via MonsterInsights (optional)                              */
	/* ------------------------------------------------------------------ */

	/**
	 * @return array{available:bool,value:int,url:string,installed:bool}
	 */
	private static function visitors(): array {
		$out = array(
			'available' => false,
			'value'     => 0,
			'url'       => admin_url( 'admin.php?page=monsterinsights_settings' ),
			'installed' => function_exists( 'MonsterInsights' ),
		);
		if ( ! $out['installed'] ) {
			$out['url'] = admin_url( 'plugin-install.php?s=monsterinsights&tab=search&type=term' );
		}

		/**
		 * Short-circuit the visitors tile with your own analytics source.
		 * Return an int (sessions in the last 7 days) or null to keep going.
		 */
		$custom = apply_filters( 'sena_studio_visitors_7d', null );
		if ( is_int( $custom ) ) {
			$out['available'] = true;
			$out['value']     = $custom;
			return $out;
		}

		if ( ! $out['installed'] ) {
			return $out;
		}

		$cached = get_transient( 'sena_studio_visitors' );
		if ( is_array( $cached ) && isset( $cached['value'] ) ) {
			$out['available'] = true;
			$out['value']     = (int) $cached['value'];
			return $out;
		}

		$value = null;
		try {
			$mi = MonsterInsights();
			if ( is_object( $mi ) && isset( $mi->reporting ) && is_object( $mi->reporting ) && method_exists( $mi->reporting, 'get_report' ) ) {
				$report = $mi->reporting->get_report( 'overview' );
				if ( is_object( $report ) && method_exists( $report, 'get_data' ) ) {
					$result = $report->get_data(
						array(
							'start' => wp_date( 'Y-m-d', strtotime( '-7 days' ) ),
							'end'   => wp_date( 'Y-m-d', strtotime( '-1 day' ) ),
						)
					);
					$value  = self::find_sessions( $result );
				}
			}
		} catch ( \Throwable $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement
			$value = null;
		}

		if ( null !== $value ) {
			set_transient( 'sena_studio_visitors', array( 'value' => $value ), HOUR_IN_SECONDS );
			$out['available'] = true;
			$out['value']     = $value;
		}
		return $out;
	}

	/**
	 * Pull a sessions / users number out of a MonsterInsights report array.
	 */
	private static function find_sessions( $result ): ?int {
		if ( ! is_array( $result ) ) {
			return null;
		}
		if ( isset( $result['success'] ) && false === $result['success'] ) {
			return null;
		}
		$data = $result['data'] ?? $result;
		foreach ( array( 'sessions', 'users' ) as $key ) {
			if ( isset( $data['infobox'][ $key ]['value'] ) && is_numeric( $data['infobox'][ $key ]['value'] ) ) {
				return (int) $data['infobox'][ $key ]['value'];
			}
			if ( isset( $data['infobox'][ $key ] ) && is_numeric( $data['infobox'][ $key ] ) ) {
				return (int) $data['infobox'][ $key ];
			}
		}
		if ( ! empty( $data['overviewgraph']['sessions'] ) && is_array( $data['overviewgraph']['sessions'] ) ) {
			return (int) array_sum( array_map( 'intval', $data['overviewgraph']['sessions'] ) );
		}
		return null;
	}

	/* ------------------------------------------------------------------ */
	/* Next steps                                                           */
	/* ------------------------------------------------------------------ */

	private static function dismissed(): array {
		$list = get_user_option( self::DISMISS_OPTION );
		$list = is_array( $list ) ? $list : array();
		$now  = time();
		return array_filter( $list, static fn( $until ) => (int) $until > $now );
	}

	/**
	 * Build the checklist. Each item: id, title, why, button, url, priority.
	 *
	 * @return array[]
	 */
	private static function next_steps( array $d, array $insights, array $products, DateTimeImmutable $now ): array {
		$steps = array();

		if ( $d['to_pack_count'] > 0 ) {
			$names = array();
			foreach ( array_slice( $d['to_pack'], 0, 3 ) as $order ) {
				$names[] = '#' . $order->get_order_number() . ' ' . $order->get_formatted_billing_full_name();
			}
			$steps[] = array(
				'id'       => 'pack',
				/* translators: %d: number of orders */
				'title'    => sprintf( _n( '%d order waiting to pack', '%d orders waiting to pack', $d['to_pack_count'], 'sena-studio' ), $d['to_pack_count'] ),
				'why'      => implode( ', ', $names ) . ( $d['to_pack_count'] > 3 ? '…' : '' ) . ' ' . __( 'Fast shipping is the review you do not have to ask for.', 'sena-studio' ),
				'button'   => __( 'Pack orders', 'sena-studio' ),
				'url'      => Sena_Studio::url( 'orders', array( 'filter' => 'pack' ) ),
				'priority' => 10,
			);
		}

		if ( $d['requests_new'] > 0 ) {
			$steps[] = array(
				'id'       => 'requests',
				/* translators: %d: number of requests */
				'title'    => sprintf( _n( '%d custom request to answer', '%d custom requests to answer', $d['requests_new'], 'sena-studio' ), $d['requests_new'] ),
				'why'      => __( 'A quick reply with a quote turns a maybe into a deposit.', 'sena-studio' ),
				'button'   => __( 'Open requests', 'sena-studio' ),
				'url'      => Sena_Studio::url( 'requests', array( 'status' => 'new' ) ),
				'priority' => 20,
			);
		}

		if ( $d['has_wc'] && $d['goal']['behind'] ) {
			$steps[] = array(
				'id'       => 'goal',
				'title'    => __( 'Monthly goal is behind pace', 'sena-studio' ),
				/* translators: 1: amount per day, 2: days left */
				'why'      => sprintf( __( 'About %1$s a day for the next %2$d days gets you there. A small sale or a story post can do it.', 'sena-studio' ), Sena_Studio::money( $d['goal']['per_day'] ), $d['goal']['days_left'] ),
				'button'   => __( 'New post', 'sena-studio' ),
				'url'      => Sena_Studio::url( 'posts', array( 'add' => 1 ) ),
				'priority' => 30,
			);
		}

		if ( $d['has_wc'] && $insights ) {
			$last_sale = (int) ( $insights['last_sale'] ?? 0 );
			if ( $last_sale > 0 && $last_sale < $now->getTimestamp() - 14 * DAY_IN_SECONDS ) {
				$steps[] = array(
					'id'       => 'no-sale',
					'title'    => __( 'No sale in 14 days', 'sena-studio' ),
					'why'      => __( 'Send a newsletter or post a process video. People buy when they remember you exist.', 'sena-studio' ),
					'button'   => __( 'New post', 'sena-studio' ),
					'url'      => Sena_Studio::url( 'posts', array( 'add' => 1 ) ),
					'priority' => 35,
				);
			}
		}

		if ( $d['posts_pending'] > 0 ) {
			$steps[] = array(
				'id'       => 'posts',
				/* translators: %d: number of posts */
				'title'    => sprintf( _n( '%d post awaiting approval', '%d posts awaiting approval', $d['posts_pending'], 'sena-studio' ), $d['posts_pending'] ),
				'why'      => __( 'Approving takes a minute and keeps the feed alive while you make.', 'sena-studio' ),
				'button'   => __( 'Review posts', 'sena-studio' ),
				'url'      => Sena_Studio::url( 'posts', array( 'status' => 'draft' ) ),
				'priority' => 40,
			);
		}

		if ( ! empty( $products['no_price'] ) ) {
			$n = count( $products['no_price'] );
			$steps[] = array(
				'id'       => 'no-price',
				/* translators: %d: number of products */
				'title'    => sprintf( _n( '%d piece has no price', '%d pieces have no price', $n, 'sena-studio' ), $n ),
				'why'      => $products['no_price'][0]->get_name() . ( $n > 1 ? '…' : '' ) . ' ' . __( 'Nobody can buy what has no price.', 'sena-studio' ),
				'button'   => __( 'Set a price', 'sena-studio' ),
				'url'      => Sena_Studio::url( 'products', array( 's' => $products['no_price'][0]->get_name() ) ),
				'priority' => 50,
			);
		}

		if ( ! empty( $d['low_stock'] ) ) {
			$n = count( $d['low_stock'] );
			$steps[] = array(
				'id'       => 'low-stock',
				/* translators: %d: number of products */
				'title'    => sprintf( _n( '%d piece is low on stock', '%d pieces are low on stock', $n, 'sena-studio' ), $n ),
				'why'      => __( 'Make another batch or switch it to made to order.', 'sena-studio' ),
				'button'   => __( 'See low stock', 'sena-studio' ),
				'url'      => Sena_Studio::url( 'products', array( 'filter' => 'low' ) ),
				'priority' => 55,
			);
		}

		if ( $d['has_wc'] ) {
			foreach ( self::review_candidates( $now ) as $order ) {
				$steps[] = array(
					'id'       => 'review-' . $order->get_id(),
					/* translators: 1: order number, 2: customer name */
					'title'    => sprintf( __( 'Ask #%1$s (%2$s) for a review', 'sena-studio' ), $order->get_order_number(), $order->get_formatted_billing_full_name() ),
					'why'      => __( 'Delivered over 10 days ago. A friendly note now is when reviews happen.', 'sena-studio' ),
					'button'   => __( 'Mark asked', 'sena-studio' ),
					'url'      => Sena_Studio::action_url( 'sena_studio_review_asked', array( 'order_id' => $order->get_id() ) ),
					'priority' => 60,
				);
			}
		}

		if ( ! empty( $products['few_photos'] ) ) {
			$n = count( $products['few_photos'] );
			$steps[] = array(
				'id'       => 'few-photos',
				/* translators: %d: number of products */
				'title'    => sprintf( _n( '%d piece has fewer than 2 photos', '%d pieces have fewer than 2 photos', $n, 'sena-studio' ), $n ),
				'why'      => $products['few_photos'][0]->get_name() . ( $n > 1 ? '…' : '' ) . ' ' . __( 'A second angle and a hand for scale sell more than words.', 'sena-studio' ),
				'button'   => __( 'Add photos', 'sena-studio' ),
				'url'      => get_edit_post_link( $products['few_photos'][0]->get_id(), 'raw' ) ?: Sena_Studio::url( 'products' ),
				'priority' => 70,
			);
		}

		if ( ! self::posted_recently( $now ) ) {
			$steps[] = array(
				'id'       => 'no-post',
				'title'    => __( 'Nothing posted in the last 7 days', 'sena-studio' ),
				'why'      => __( 'A work-in-progress shot is enough. Consistency beats polish.', 'sena-studio' ),
				'button'   => __( 'New post', 'sena-studio' ),
				'url'      => Sena_Studio::url( 'posts', array( 'add' => 1 ) ),
				'priority' => 80,
			);
		}

		if ( empty( $d['next_event'] ) ) {
			$steps[] = array(
				'id'       => 'no-event',
				'title'    => __( 'No upcoming event', 'sena-studio' ),
				'why'      => __( 'Markets are where people touch the leather. Book the next one.', 'sena-studio' ),
				'button'   => __( 'Add event', 'sena-studio' ),
				'url'      => Sena_Studio::url( 'events', array( 'add' => 1 ) ),
				'priority' => 90,
			);
		}

		/**
		 * Add, remove or reorder next steps. Each item needs id, title, why,
		 * button, url and priority (lower comes first).
		 *
		 * @param array[] $steps
		 * @param array   $d        Dashboard data.
		 * @param array   $insights Cached insights (may be empty).
		 */
		$steps = apply_filters( 'sena_studio_next_steps', $steps, $d, $insights );

		$dismissed = self::dismissed();
		$steps     = array_filter( $steps, static fn( $s ) => is_array( $s ) && ! empty( $s['id'] ) && ! isset( $dismissed[ $s['id'] ] ) );
		usort( $steps, static fn( $a, $b ) => ( (int) ( $a['priority'] ?? 100 ) ) <=> ( (int) ( $b['priority'] ?? 100 ) ) );
		return array_values( $steps );
	}

	/**
	 * Completed orders delivered more than 10 days ago (but within 60) with no review ask yet.
	 *
	 * @return WC_Order[]
	 */
	private static function review_candidates( DateTimeImmutable $now ): array {
		$orders = wc_get_orders(
			array(
				'status'         => array( 'completed' ),
				'limit'          => 20,
				'orderby'        => 'date',
				'order'          => 'DESC',
				'date_completed' => ( $now->getTimestamp() - 60 * DAY_IN_SECONDS ) . '...' . ( $now->getTimestamp() - 10 * DAY_IN_SECONDS ),
				'return'         => 'objects',
			)
		);
		$out = array();
		foreach ( $orders as $order ) {
			if ( ! $order instanceof WC_Order || '' !== (string) $order->get_meta( self::META_REVIEW_ASKED ) ) {
				continue;
			}
			$out[] = $order;
			if ( count( $out ) >= 3 ) {
				break;
			}
		}
		return $out;
	}

	private static function posted_recently( DateTimeImmutable $now ): bool {
		$since = $now->getTimestamp() - 7 * DAY_IN_SECONDS;
		foreach ( Sena_Studio_Posts::query( 'published', 20 ) as $post ) {
			$at = (string) get_post_meta( $post->ID, '_sena_published_at', true );
			$ts = $at ? strtotime( $at ) : (int) get_post_modified_time( 'U', true, $post );
			if ( $ts && $ts >= $since ) {
				return true;
			}
		}
		return false;
	}

	/* ------------------------------------------------------------------ */
	/* Milestones                                                           */
	/* ------------------------------------------------------------------ */

	private static function manual_milestones(): array {
		$list = get_option( self::MILESTONE_OPTION, array() );
		return is_array( $list ) ? $list : array();
	}

	/**
	 * Weeks (out of the last 4) with at least one published post.
	 */
	private static function posting_streak( DateTimeImmutable $now ): int {
		$week_start = self::week_start( $now )->getTimestamp();
		$hit        = array();
		foreach ( Sena_Studio_Posts::query( 'published', 60 ) as $post ) {
			$at = (string) get_post_meta( $post->ID, '_sena_published_at', true );
			$ts = $at ? (int) strtotime( $at ) : (int) get_post_modified_time( 'U', true, $post );
			if ( ! $ts ) {
				continue;
			}
			$k = (int) floor( ( $week_start + WEEK_IN_SECONDS - $ts ) / WEEK_IN_SECONDS ); // 0 = this week.
			if ( $k >= 0 && $k < 4 ) {
				$hit[ $k ] = true;
			}
		}
		return count( $hit );
	}

	private static function has_five_star_review(): bool {
		if ( ! Sena_Studio::has_woocommerce() ) {
			return false;
		}
		$found = get_comments(
			array(
				'type'       => 'review',
				'status'     => 'approve',
				'post_type'  => 'product',
				'number'     => 1,
				'fields'     => 'ids',
				'meta_key'   => 'rating', // phpcs:ignore WordPress.DB.SlowDBQuery
				'meta_value' => 5, // phpcs:ignore WordPress.DB.SlowDBQuery
			)
		);
		return ! empty( $found );
	}

	/**
	 * The ladder of tiny steps. Each: id, title, why, button, url, done, progress, manual.
	 *
	 * @return array{items:array[],done:int,total:int,current:?string,cheer:string}
	 */
	private static function milestones( array $d, array $insights, array $products, DateTimeImmutable $now ): array {
		$has_wc       = $d['has_wc'];
		$total_orders = (int) ( $insights['total_orders'] ?? 0 );
		$best_month   = (float) ( $insights['best_month'] ?? 0 );
		$repeat       = (int) ( $insights['repeat']['repeat'] ?? 0 );
		$total_prod   = (int) ( $products['total'] ?? 0 );
		$answered     = 0;
		foreach ( array_keys( Sena_Studio_Requests::statuses() ) as $st ) {
			if ( 'new' !== $st ) {
				$answered += Sena_Studio_Requests::count( $st );
			}
		}
		$streak   = self::posting_streak( $now );
		$pieces   = Sena_Studio::url( 'products' );
		$post_url = Sena_Studio::url( 'posts', array( 'add' => 1 ) );
		/* translators: 1: current value, 2: target */
		$of = __( '%1$s of %2$s', 'sena-studio' );

		$items = array(
			array(
				'id'     => 'prices',
				'title'  => __( 'Every piece has a price', 'sena-studio' ),
				'why'    => __( 'A piece without a price is a photo, not a product.', 'sena-studio' ),
				'button' => $total_prod ? __( 'Check pieces', 'sena-studio' ) : __( 'Add a piece', 'sena-studio' ),
				'url'    => $total_prod ? ( ! empty( $products['no_price'] ) ? Sena_Studio::url( 'products', array( 's' => $products['no_price'][0]->get_name() ) ) : $pieces ) : Sena_Studio::url( 'products', array( 'add' => 1 ) ),
				'done'   => $has_wc && $total_prod > 0 && empty( $products['no_price'] ),
			),
			array(
				'id'     => 'photos',
				'title'  => __( 'Every piece has at least 2 photos', 'sena-studio' ),
				'why'    => __( 'The second photo answers the question the first one raised.', 'sena-studio' ),
				'button' => __( 'Add photos', 'sena-studio' ),
				'url'    => ! empty( $products['few_photos'] ) ? ( get_edit_post_link( $products['few_photos'][0]->get_id(), 'raw' ) ?: $pieces ) : $pieces,
				'done'   => $has_wc && $total_prod > 0 && empty( $products['few_photos'] ),
				'progress' => $total_prod ? sprintf( $of, $total_prod - count( $products['few_photos'] ?? array() ), $total_prod ) : '',
			),
			array(
				'id'     => 'event',
				'title'  => __( 'A festival date is on the site', 'sena-studio' ),
				'why'    => __( 'People plan their weekends around makers they like.', 'sena-studio' ),
				'button' => __( 'Add event', 'sena-studio' ),
				'url'    => Sena_Studio::url( 'events', array( 'add' => 1 ) ),
				'done'   => ! empty( $d['next_event'] ),
			),
			array(
				'id'     => 'first-order',
				'title'  => __( 'First order on the website', 'sena-studio' ),
				'why'    => __( 'The first one proves the shop works. Everything after is repetition.', 'sena-studio' ),
				'button' => __( 'New post', 'sena-studio' ),
				'url'    => $post_url,
				'done'   => ! empty( $insights['web_order'] ),
			),
			array(
				'id'     => 'first-request',
				'title'  => __( 'First custom request answered', 'sena-studio' ),
				'why'    => __( 'Custom work pays best and builds the stories you post about.', 'sena-studio' ),
				'button' => __( 'Open requests', 'sena-studio' ),
				'url'    => Sena_Studio::url( 'requests' ),
				'done'   => $answered > 0,
			),
			array(
				'id'       => 'orders-5',
				'title'    => __( '5 orders', 'sena-studio' ),
				'why'      => __( 'Five strangers trusted you with their money. That is a business.', 'sena-studio' ),
				'button'   => __( 'See orders', 'sena-studio' ),
				'url'      => Sena_Studio::url( 'orders', array( 'filter' => 'all' ) ),
				'done'     => $total_orders >= 5,
				'progress' => sprintf( $of, min( 5, $total_orders ), 5 ),
			),
			array(
				'id'     => 'review',
				'title'  => __( 'First 5-star review', 'sena-studio' ),
				'why'    => __( 'One honest review sells more than ten captions.', 'sena-studio' ),
				'button' => __( 'Ask a happy customer', 'sena-studio' ),
				'url'    => Sena_Studio::url( 'orders', array( 'filter' => 'shipped' ) ),
				'done'   => self::has_five_star_review(),
			),
			array(
				'id'       => 'streak',
				'title'    => __( 'A post every week, 4 weeks in a row', 'sena-studio' ),
				'why'      => __( 'Showing up weekly is what the algorithm, and people, reward.', 'sena-studio' ),
				'button'   => __( 'New post', 'sena-studio' ),
				'url'      => $post_url,
				'done'     => $streak >= 4,
				'progress' => sprintf( $of, $streak, 4 ),
			),
			array(
				'id'       => 'month-500',
				'title'    => __( 'First $500 month', 'sena-studio' ),
				'why'      => __( 'Materials paid for, and a little left over for you.', 'sena-studio' ),
				'button'   => __( 'See the goal', 'sena-studio' ),
				'url'      => Sena_Studio::url( 'today' ) . '#sena-goal-title',
				'done'     => $best_month >= 500,
				'progress' => $best_month > 0 && $best_month < 500 ? sprintf( __( 'best month %s', 'sena-studio' ), Sena_Studio::money( $best_month ) ) : '',
			),
			array(
				'id'       => 'repeat-10',
				'title'    => __( '10 repeat customers', 'sena-studio' ),
				'why'      => __( 'Repeat buyers mean the quality speaks for itself.', 'sena-studio' ),
				'button'   => __( 'Say thanks', 'sena-studio' ),
				'url'      => Sena_Studio::url( 'orders', array( 'filter' => 'shipped' ) ),
				'done'     => $repeat >= 10,
				'progress' => sprintf( $of, min( 10, $repeat ), 10 ),
			),
			array(
				'id'       => 'month-1000',
				'title'    => __( 'First $1,000 month', 'sena-studio' ),
				'why'      => __( 'This is where a hobby quietly becomes a studio.', 'sena-studio' ),
				'button'   => __( 'See the goal', 'sena-studio' ),
				'url'      => Sena_Studio::url( 'today' ) . '#sena-goal-title',
				'done'     => $best_month >= 1000,
				'progress' => $best_month >= 500 && $best_month < 1000 ? sprintf( __( 'best month %s', 'sena-studio' ), Sena_Studio::money( $best_month ) ) : '',
			),
			array(
				'id'       => 'orders-100',
				'title'    => __( '100 orders', 'sena-studio' ),
				'why'      => __( 'A hundred parcels. Frame the first packing slip.', 'sena-studio' ),
				'button'   => __( 'See orders', 'sena-studio' ),
				'url'      => Sena_Studio::url( 'orders', array( 'filter' => 'all' ) ),
				'done'     => $total_orders >= 100,
				'progress' => sprintf( $of, min( 100, $total_orders ), 100 ),
			),
		);

		/**
		 * Add, remove or reorder milestones. Each needs id, title, why, button, url, done; progress is optional.
		 */
		$items  = apply_filters( 'sena_studio_milestones', $items, $d, $insights );
		$manual = self::manual_milestones();

		$done    = 0;
		$current = null;
		foreach ( $items as &$item ) {
			if ( ! is_array( $item ) || empty( $item['id'] ) ) {
				continue;
			}
			$item['manual'] = isset( $manual[ $item['id'] ] );
			$item['done']   = ! empty( $item['done'] ) || $item['manual'];
			$item['current'] = false;
			if ( $item['done'] ) {
				$done++;
			} elseif ( null === $current ) {
				$current         = $item['id'];
				$item['current'] = true;
			}
		}
		unset( $item );
		$items = array_values( array_filter( $items, static fn( $i ) => is_array( $i ) && ! empty( $i['id'] ) ) );

		$cheers = array(
			__( 'Tiny step. You have got this.', 'sena-studio' ),
			__( 'One at a time. That is how every studio was built.', 'sena-studio' ),
			__( 'This one is closer than it looks.', 'sena-studio' ),
			__( 'Small, concrete, doable. Today, maybe.', 'sena-studio' ),
		);
		$cheer = $done >= count( $items ) && $items ? __( 'Every rung climbed. Time to write a new ladder.', 'sena-studio' ) : $cheers[ $done % count( $cheers ) ];

		return array(
			'items'   => $items,
			'done'    => $done,
			'total'   => count( $items ),
			'current' => $current,
			'cheer'   => $cheer,
		);
	}

	public static function handle_milestone(): void {
		Sena_Studio::verify_action( 'sena_studio_milestone' );
		$id   = isset( $_REQUEST['milestone'] ) ? sanitize_key( wp_unslash( $_REQUEST['milestone'] ) ) : '';
		$undo = ! empty( $_REQUEST['undo'] );
		if ( '' === $id ) {
			Sena_Studio::redirect( 'today' );
		}
		$list = self::manual_milestones();
		if ( $undo ) {
			unset( $list[ $id ] );
			$msg = __( 'Milestone unticked.', 'sena-studio' );
		} else {
			$list[ $id ] = time();
			$msg         = __( 'Milestone ticked. Onwards.', 'sena-studio' );
		}
		update_option( self::MILESTONE_OPTION, $list, false );
		Sena_Studio::redirect( 'today', $msg );
	}

	/* ------------------------------------------------------------------ */
	/* Season                                                               */
	/* ------------------------------------------------------------------ */

	/**
	 * Three short idea cards for the time of year. Static, filterable.
	 */
	public static function season_ideas( int $month ): array {
		$post_url  = Sena_Studio::url( 'posts', array( 'add' => 1 ) );
		$piece_url = Sena_Studio::url( 'products', array( 'add' => 1 ) );

		if ( in_array( $month, array( 12, 1 ), true ) ) {
			$seasonal = array(
				'title' => __( "Valentine's lead time", 'sena-studio' ),
				'text'  => __( 'Orders for 14 February land in late January. List a couple of small, giftable pieces now: card holders, a journal with a red thread.', 'sena-studio' ),
				'cta'   => __( 'Add a piece', 'sena-studio' ),
				'url'   => $piece_url,
			);
		} elseif ( in_array( $month, array( 3, 4 ), true ) ) {
			$seasonal = array(
				'title' => __( "Mother's Day", 'sena-studio' ),
				'text'  => __( 'Something soft and useful: a keyring, a glasses case, a notebook with her initials. Post it with a "ships by" date.', 'sena-studio' ),
				'cta'   => __( 'Add a piece', 'sena-studio' ),
				'url'   => $piece_url,
			);
		} elseif ( in_array( $month, array( 5, 6 ), true ) ) {
			$seasonal = array(
				'title' => __( 'Wedding season', 'sena-studio' ),
				'text'  => __( 'Guest books, vow booklets, groomsmen wallets. Offer a small bundle and mention custom orders on the form page.', 'sena-studio' ),
				'cta'   => __( 'New post', 'sena-studio' ),
				'url'   => $post_url,
			);
		} elseif ( in_array( $month, array( 7, 8 ), true ) ) {
			$seasonal = array(
				'title' => __( 'Back-to-school journals', 'sena-studio' ),
				'text'  => __( 'Refillable notebooks and pen loops sell to students and teachers alike. A "first page" photo tells the story.', 'sena-studio' ),
				'cta'   => __( 'Add a piece', 'sena-studio' ),
				'url'   => $piece_url,
			);
		} else {
			$seasonal = array(
				'title' => __( 'Holiday markets', 'sena-studio' ),
				'text'  => __( 'Book the stalls, make small stocking-stuffer pieces in batches, and print a card with your shop link for every bag.', 'sena-studio' ),
				'cta'   => __( 'Add an event', 'sena-studio' ),
				'url'   => Sena_Studio::url( 'events', array( 'add' => 1 ) ),
			);
		}

		$ideas = array(
			$seasonal,
			array(
				'title' => __( 'Offer personalisation', 'sena-studio' ),
				'text'  => __( 'Stamped initials or a short line inside the cover turn a nice object into a gift. Mention it on your best seller.', 'sena-studio' ),
				'cta'   => __( 'Open requests form', 'sena-studio' ),
				'url'   => Sena_Studio::url( 'requests' ),
			),
			array(
				'title' => __( 'Film a process video', 'sena-studio' ),
				'text'  => __( 'Thirty seconds of saddle stitching or edge burnishing, phone propped on a mug. No talking needed.', 'sena-studio' ),
				'cta'   => __( 'New post', 'sena-studio' ),
				'url'   => $post_url,
			),
		);

		/**
		 * Change the seasonal idea cards.
		 *
		 * @param array[] $ideas Each: title, text, cta, url.
		 * @param int     $month 1-12.
		 */
		return apply_filters( 'sena_studio_season_ideas', $ideas, $month );
	}

	/* ------------------------------------------------------------------ */
	/* Charts (inline SVG, no JS)                                           */
	/* ------------------------------------------------------------------ */

	public static function money_short( float $amount ): string {
		$symbol = function_exists( 'get_woocommerce_currency_symbol' ) ? html_entity_decode( get_woocommerce_currency_symbol(), ENT_QUOTES, 'UTF-8' ) : '';
		if ( $amount >= 1000 ) {
			return $symbol . rtrim( rtrim( number_format_i18n( $amount / 1000, 1 ), '0' ), '.,' ) . 'k';
		}
		return $symbol . number_format_i18n( $amount, 0 );
	}

	private static function nice_max( float $max ): float {
		if ( $max <= 0 ) {
			return 100.0;
		}
		$mag  = pow( 10, floor( log10( $max ) ) );
		$norm = $max / $mag;
		if ( $norm <= 1 ) {
			$n = 1;
		} elseif ( $norm <= 2 ) {
			$n = 2;
		} elseif ( $norm <= 2.5 ) {
			$n = 2.5;
		} elseif ( $norm <= 5 ) {
			$n = 5;
		} else {
			$n = 10;
		}
		return (float) ( $n * $mag );
	}

	/**
	 * Weekly bar chart as SVG plus a visually hidden table.
	 *
	 * @param array[] $weeks Each: label, total, count, current.
	 */
	public static function bar_chart( array $weeks, string $id, string $caption ): string {
		$w     = 360;
		$h     = 190;
		$pl    = 44;
		$pr    = 6;
		$pt    = 10;
		$pb    = 26;
		$n     = max( 1, count( $weeks ) );
		$max   = self::nice_max( (float) max( array_merge( array( 0 ), array_column( $weeks, 'total' ) ) ) );
		$iw    = $w - $pl - $pr;
		$ih    = $h - $pt - $pb;
		$slot  = $iw / $n;
		$bw    = $slot * 0.62;
		$title_id = $id . '-title';

		$svg  = '<svg class="sena-chart__svg" viewBox="0 0 ' . $w . ' ' . $h . '" role="img" aria-labelledby="' . esc_attr( $title_id ) . '" preserveAspectRatio="xMidYMid meet">';
		$svg .= '<title id="' . esc_attr( $title_id ) . '">' . esc_html( $caption ) . '</title>';

		foreach ( array( 0, 0.5, 1 ) as $f ) {
			$y    = $pt + $ih - $ih * $f;
			$svg .= '<line x1="' . $pl . '" x2="' . ( $w - $pr ) . '" y1="' . round( $y, 1 ) . '" y2="' . round( $y, 1 ) . '" class="sena-chart__grid"/>';
			$svg .= '<text x="' . ( $pl - 6 ) . '" y="' . round( $y + 3.5, 1 ) . '" text-anchor="end" class="sena-chart__axis">' . esc_html( self::money_short( $max * $f ) ) . '</text>';
		}

		foreach ( array_values( $weeks ) as $i => $wk ) {
			$val = (float) $wk['total'];
			$bh  = $max > 0 ? $ih * $val / $max : 0;
			if ( $val > 0 && $bh < 2 ) {
				$bh = 2;
			}
			$x  = $pl + $i * $slot + ( $slot - $bw ) / 2;
			$y  = $pt + $ih - $bh;
			/* translators: 1: week start date, 2: amount, 3: number of orders */
			$tip = sprintf( __( 'Week of %1$s: %2$s, %3$s', 'sena-studio' ), $wk['label'], Sena_Studio::money( $val ), sprintf( _n( '%d order', '%d orders', (int) $wk['count'], 'sena-studio' ), (int) $wk['count'] ) );
			$svg .= '<g class="sena-chart__bar' . ( ! empty( $wk['current'] ) ? ' is-current' : '' ) . '"><title>' . esc_html( $tip ) . '</title>';
			$svg .= '<rect x="' . round( $x, 1 ) . '" y="' . round( $y, 1 ) . '" width="' . round( $bw, 1 ) . '" height="' . round( $bh, 1 ) . '" rx="3"/>';
			$svg .= '<rect x="' . round( $pl + $i * $slot, 1 ) . '" y="' . $pt . '" width="' . round( $slot, 1 ) . '" height="' . $ih . '" fill="transparent"/>';
			$svg .= '</g>';
			if ( $n <= 7 || 1 === $i % 2 || $i === $n - 1 ) {
				$svg .= '<text x="' . round( $x + $bw / 2, 1 ) . '" y="' . ( $h - 8 ) . '" text-anchor="middle" class="sena-chart__label">' . esc_html( $wk['label'] ) . '</text>';
			}
		}
		$svg .= '</svg>';

		$table  = '<table class="screen-reader-text"><caption>' . esc_html( $caption ) . '</caption><thead><tr><th scope="col">' . esc_html__( 'Week starting', 'sena-studio' ) . '</th><th scope="col">' . esc_html__( 'Net sales', 'sena-studio' ) . '</th><th scope="col">' . esc_html__( 'Orders', 'sena-studio' ) . '</th></tr></thead><tbody>';
		foreach ( $weeks as $wk ) {
			$table .= '<tr><td>' . esc_html( $wk['label'] ) . '</td><td>' . esc_html( Sena_Studio::money( (float) $wk['total'] ) ) . '</td><td>' . esc_html( number_format_i18n( (int) $wk['count'] ) ) . '</td></tr>';
		}
		$table .= '</tbody></table>';

		return $svg . $table;
	}

	/**
	 * Single stacked horizontal bar for sales by channel, with a legend.
	 *
	 * @param array<string,float> $channels channel => net sales
	 */
	public static function channel_chart( array $channels, string $caption ): string {
		$labels = self::channel_labels();
		$active = array_filter( $channels, static fn( $v ) => (float) $v > 0 );
		if ( count( $active ) < 2 ) {
			if ( 1 === count( $active ) ) {
				$key = (string) array_key_first( $active );
				/* translators: 1: channel name, 2: amount */
				return '<p class="sena-muted">' . esc_html( sprintf( __( 'All sales in the last 90 days came through %1$s (%2$s).', 'sena-studio' ), $labels[ $key ] ?? $key, Sena_Studio::money( (float) $active[ $key ] ) ) ) . '</p>';
			}
			return '<p class="sena-muted">' . esc_html__( 'Once sales come in, you will see here where they come from.', 'sena-studio' ) . '</p>';
		}
		$total = array_sum( $active );
		$x     = 0.0;
		$svg   = '<svg class="sena-chart__stack" viewBox="0 0 360 16" role="img" aria-label="' . esc_attr( $caption ) . '" preserveAspectRatio="none">';
		$legend = '<ul class="sena-legend">';
		foreach ( $active as $key => $value ) {
			$wd   = 360 * (float) $value / $total;
			$pct  = round( (float) $value / $total * 100 );
			$name = $labels[ $key ] ?? $key;
			$svg .= '<rect class="sena-chart__seg sena-chart__seg--' . esc_attr( $key ) . '" x="' . round( $x, 1 ) . '" y="0" width="' . round( $wd, 1 ) . '" height="16"><title>' . esc_html( $name . ': ' . Sena_Studio::money( (float) $value ) . ' (' . $pct . '%)' ) . '</title></rect>';
			$legend .= '<li><span class="sena-legend__dot sena-legend__dot--' . esc_attr( $key ) . '" aria-hidden="true"></span>' . esc_html( $name ) . ' <strong>' . esc_html( $pct . '%' ) . '</strong> <span class="sena-muted">' . esc_html( Sena_Studio::money( (float) $value ) ) . '</span></li>';
			$x   += $wd;
		}
		$svg    .= '</svg>';
		$legend .= '</ul>';
		return $svg . $legend;
	}

	/* ------------------------------------------------------------------ */
	/* Actions                                                              */
	/* ------------------------------------------------------------------ */

	public static function sanitize_goal( $raw ): float {
		$raw = str_replace( array( ',', ' ' ), array( '.', '' ), sanitize_text_field( (string) $raw ) );
		$raw = preg_replace( '/[^0-9.]/', '', $raw );
		return round( max( 0.0, (float) $raw ), 2 );
	}

	public static function handle_save_goal(): void {
		Sena_Studio::verify_action( 'sena_studio_save_goal' );
		$settings                 = Sena_Studio::settings();
		$settings['monthly_goal'] = self::sanitize_goal( isset( $_POST['monthly_goal'] ) ? wp_unslash( $_POST['monthly_goal'] ) : 0 ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		update_option( Sena_Studio::OPTION, $settings );
		Sena_Studio::redirect( 'today', __( 'Goal updated. Go get it.', 'sena-studio' ) );
	}

	public static function handle_dismiss_step(): void {
		Sena_Studio::verify_action( 'sena_studio_dismiss_step' );
		$id = isset( $_REQUEST['step'] ) ? sanitize_key( wp_unslash( $_REQUEST['step'] ) ) : '';
		if ( '' !== $id ) {
			$list        = self::dismissed();
			$list[ $id ] = time() + 7 * DAY_IN_SECONDS;
			update_user_option( get_current_user_id(), self::DISMISS_OPTION, $list );
		}
		Sena_Studio::redirect( 'today', __( 'Hidden for a week.', 'sena-studio' ) );
	}

	public static function handle_review_asked(): void {
		Sena_Studio::verify_action( 'sena_studio_review_asked' );
		$id    = isset( $_REQUEST['order_id'] ) ? absint( $_REQUEST['order_id'] ) : 0;
		$order = $id && Sena_Studio::has_woocommerce() ? wc_get_order( $id ) : false;
		if ( ! $order instanceof WC_Order ) {
			Sena_Studio::redirect( 'today', __( 'Order not found.', 'sena-studio' ), 'error' );
		}
		$order->update_meta_data( self::META_REVIEW_ASKED, gmdate( 'c' ) );
		$order->save();
		$order->add_order_note( __( 'Review request sent (marked from Sena Studio).', 'sena-studio' ) );
		Sena_Studio::redirect( 'today', sprintf( /* translators: %s: order number */ __( 'Marked #%s as asked. Fingers crossed.', 'sena-studio' ), $order->get_order_number() ) );
	}

	/**
	 * One click: Instagram draft from a product's first description paragraph plus hashtags.
	 */
	public static function handle_draft_post(): void {
		Sena_Studio::verify_action( 'sena_studio_draft_post' );
		$id      = isset( $_REQUEST['product_id'] ) ? absint( $_REQUEST['product_id'] ) : 0;
		$product = $id && Sena_Studio::has_woocommerce() ? wc_get_product( $id ) : false;
		if ( ! $product instanceof WC_Product ) {
			Sena_Studio::redirect( 'today', __( 'Piece not found.', 'sena-studio' ), 'error' );
		}

		$caption = self::first_paragraph( $product->get_description() );
		if ( '' === $caption ) {
			$caption = self::first_paragraph( $product->get_short_description() );
		}
		if ( '' === $caption ) {
			/* translators: %s: product name */
			$caption = sprintf( __( 'New in the shop: %s. Cut, stitched and finished by hand in the studio.', 'sena-studio' ), $product->get_name() );
		}

		$post_id = Sena_Studio_Posts::create(
			array(
				'platform'   => 'instagram',
				'caption'    => $caption,
				'hashtags'   => implode( ' ', self::hashtags_for( $product ) ),
				'product_id' => $product->get_id(),
				'image_id'   => (int) $product->get_image_id(),
				'status'     => 'draft',
			)
		);
		if ( is_wp_error( $post_id ) ) {
			Sena_Studio::redirect( 'today', $post_id->get_error_message(), 'error' );
		}
		Sena_Studio::redirect( 'posts', sprintf( /* translators: %s: product name */ __( 'Draft post for "%s" is in the queue. Give it a read.', 'sena-studio' ), $product->get_name() ), 'success', array( 'status' => 'draft' ) );
	}

	private static function first_paragraph( string $html ): string {
		$text = wp_strip_all_tags( str_replace( array( '</p>', '<br>', '<br/>', '<br />' ), "\n\n", $html ) );
		$text = html_entity_decode( $text, ENT_QUOTES, 'UTF-8' );
		$parts = preg_split( '/\n\s*\n/', trim( $text ) ) ?: array();
		foreach ( $parts as $p ) {
			$p = trim( preg_replace( '/\s+/', ' ', $p ) );
			if ( '' !== $p ) {
				return $p;
			}
		}
		return '';
	}

	/**
	 * Five hashtags: product categories and tags first, then studio staples.
	 *
	 * @return string[]
	 */
	private static function hashtags_for( WC_Product $product ): array {
		$tags = array();
		foreach ( array( 'product_cat', 'product_tag' ) as $tax ) {
			$terms = get_the_terms( $product->get_id(), $tax );
			if ( is_array( $terms ) ) {
				foreach ( $terms as $term ) {
					$slug = preg_replace( '/[^a-z0-9]/', '', strtolower( remove_accents( $term->name ) ) );
					if ( $slug && 'uncategorized' !== $slug ) {
						$tags[] = '#' . $slug;
					}
				}
			}
		}
		$name = strtolower( remove_accents( $product->get_name() ) );
		foreach ( array( 'wallet', 'journal', 'notebook', 'card', 'bag', 'belt', 'keyring', 'sketchbook', 'tote', 'clutch', 'passport', 'planner' ) as $word ) {
			if ( str_contains( $name, $word ) ) {
				$tags[] = '#leather' . $word;
			}
		}
		$tags = array_merge( $tags, array( '#handmade', '#leathercraft', '#slowmade', '#paperlove', '#smallbusiness', '#giftideas' ) );
		$tags = array_values( array_unique( $tags ) );
		/**
		 * Change the hashtags used for one-click draft posts.
		 */
		$tags = apply_filters( 'sena_studio_draft_hashtags', $tags, $product );
		return array_slice( $tags, 0, 5 );
	}
}
