<?php
/**
 * Bootstrap: menu, assets, tab routing, settings and shared helpers.
 *
 * @package SenaStudio
 */

defined( 'ABSPATH' ) || exit;

final class Sena_Studio {

	public const OPTION = 'sena_studio_settings';
	public const PAGE   = 'sena-studio';

	private static ?Sena_Studio $instance = null;

	/** True while rendering the front-end portal or handling an action sent from it. */
	private static ?bool $portal = null;

	public const QUERY_VAR = 'sena_studio';

	/** @var array<string,string> tab slug => label */
	private array $tabs = array();

	public static function instance(): Sena_Studio {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		require_once SENA_STUDIO_DIR . 'includes/class-dashboard.php';
		require_once SENA_STUDIO_DIR . 'includes/class-orders.php';
		require_once SENA_STUDIO_DIR . 'includes/class-products.php';
		require_once SENA_STUDIO_DIR . 'includes/class-requests.php';
		require_once SENA_STUDIO_DIR . 'includes/class-posts.php';
		require_once SENA_STUDIO_DIR . 'includes/class-events.php';
		require_once SENA_STUDIO_DIR . 'includes/class-rest.php';

		add_action( 'init', array( $this, 'load_textdomain' ) );
		add_action( 'init', array( $this, 'set_tabs' ) );
		add_action( 'init', array( __CLASS__, 'add_rewrite' ) );
		add_filter( 'query_vars', array( $this, 'query_vars' ) );
		add_action( 'template_redirect', array( $this, 'maybe_render_portal' ), 1 );
		add_action( 'admin_init', array( $this, 'maybe_upgrade' ) );
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'register_front_assets' ) );
		add_action( 'admin_notices', array( $this, 'woocommerce_notice' ) );
		add_action( 'admin_post_sena_studio_save_settings', array( $this, 'handle_save_settings' ) );
		add_filter( 'admin_body_class', array( $this, 'body_class' ) );
		add_action( 'admin_bar_menu', array( $this, 'admin_bar' ), 100 );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_bar_style' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'admin_bar_style' ) );
		add_filter( 'login_redirect', array( $this, 'login_redirect' ), 20, 3 );

		// Modules that do not depend on WooCommerce.
		Sena_Studio_Requests::init();
		Sena_Studio_Posts::init();
		Sena_Studio_Events::init();
		Sena_Studio_Rest::init();
		Sena_Studio_Dashboard::init();

		if ( self::has_woocommerce() ) {
			Sena_Studio_Orders::init();
			Sena_Studio_Products::init();
		}
	}

	/* ------------------------------------------------------------------ */
	/* Helpers                                                              */
	/* ------------------------------------------------------------------ */

	public static function has_woocommerce(): bool {
		return class_exists( 'WooCommerce' ) && function_exists( 'wc_get_orders' );
	}

	/**
	 * Capability used for every Studio screen and action.
	 */
	public static function cap(): string {
		return self::has_woocommerce() ? 'manage_woocommerce' : 'manage_options';
	}

	public static function default_settings(): array {
		return array(
			'thank_you_note'     => __( "Thank you so much for supporting my little studio. Every piece is cut, stitched and finished by hand. I hope it brings you joy for years to come.", 'sena-studio' ),
			'deposit_percent'    => 50,
			'notification_email' => get_option( 'admin_email' ),
			'pirate_ship_link'   => 1,
			'monthly_goal'       => 500,
		);
	}

	public static function settings(): array {
		$saved = get_option( self::OPTION, array() );
		return wp_parse_args( is_array( $saved ) ? $saved : array(), self::default_settings() );
	}

	public static function setting( string $key ) {
		$settings = self::settings();
		$value    = $settings[ $key ] ?? null;
		if ( ( null === $value || '' === $value ) && isset( self::default_settings()[ $key ] ) ) {
			return self::default_settings()[ $key ];
		}
		return $value;
	}

	/**
	 * Merge new defaults and flush rewrite rules when the plugin was updated in place.
	 */
	public function maybe_upgrade(): void {
		if ( get_option( 'sena_studio_version' ) === SENA_STUDIO_VERSION ) {
			return;
		}
		$saved = get_option( self::OPTION, array() );
		update_option( self::OPTION, wp_parse_args( is_array( $saved ) ? $saved : array(), self::default_settings() ) );
		update_option( 'sena_studio_version', SENA_STUDIO_VERSION );
		self::add_rewrite();
		flush_rewrite_rules();
	}

	/* ------------------------------------------------------------------ */
	/* Portal (front-end /studio/)                                          */
	/* ------------------------------------------------------------------ */

	public static function add_rewrite(): void {
		add_rewrite_rule( '^studio(?:/([a-z-]+))?/?$', 'index.php?' . self::QUERY_VAR . '=1&sena_tab=$matches[1]', 'top' );
	}

	public function query_vars( array $vars ): array {
		$vars[] = self::QUERY_VAR;
		$vars[] = 'sena_tab';
		return $vars;
	}

	/**
	 * Whether we are in the front-end portal: rendering it, or handling an
	 * admin-post action that was sent from it (`_sena_return` or the referer).
	 */
	public static function is_portal(): bool {
		if ( null !== self::$portal ) {
			return self::$portal;
		}
		$base = untrailingslashit( home_url( '/studio' ) );
		$ret  = isset( $_REQUEST['_sena_return'] ) ? esc_url_raw( wp_unslash( $_REQUEST['_sena_return'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( '' !== $ret && str_starts_with( $ret, $base ) ) {
			self::$portal = true;
			return true;
		}
		if ( ! is_admin() && (int) get_query_var( self::QUERY_VAR ) === 1 ) {
			self::$portal = true;
			return true;
		}
		$referer = (string) wp_get_referer();
		if ( defined( 'DOING_AJAX' ) === false && is_admin() && '' !== $referer && str_starts_with( $referer, $base ) ) {
			self::$portal = true;
			return true;
		}
		return false;
	}

	/**
	 * Front-end portal URL for a tab.
	 */
	public static function portal_url( string $tab = 'today', array $args = array() ): string {
		$url = home_url( '/studio/' . ( 'today' === $tab ? '' : trailingslashit( $tab ) ) );
		if ( ! get_option( 'permalink_structure' ) ) {
			$url  = home_url( '/' );
			$args = array_merge( array( self::QUERY_VAR => 1, 'tab' => $tab ), $args );
		}
		return $args ? add_query_arg( $args, $url ) : $url;
	}

	/**
	 * The URL to come back to after an action started in the portal.
	 */
	public static function return_url(): string {
		return self::portal_url( self::current_tab() );
	}

	/**
	 * Hidden field for forms so actions sent from the portal return to it.
	 */
	public static function return_field(): void {
		if ( self::is_portal() ) {
			echo '<input type="hidden" name="_sena_return" value="' . esc_attr( self::return_url() ) . '">';
		}
	}

	/**
	 * Where GET forms (search) should submit.
	 */
	public static function form_action( string $tab ): string {
		return self::is_portal() ? self::portal_url( $tab ) : admin_url( 'admin.php' );
	}

	/**
	 * Render the portal (or its login page) and exit.
	 */
	public function maybe_render_portal(): void {
		if ( (int) get_query_var( self::QUERY_VAR ) !== 1 ) {
			return;
		}
		self::$portal = true;
		nocache_headers();
		show_admin_bar( false );
		remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
		remove_action( 'wp_print_styles', 'print_emoji_styles' );
		remove_action( 'wp_head', 'wp_generator' );
		remove_action( 'wp_head', 'rsd_link' );
		remove_action( 'wp_head', 'wlwmanifest_link' );
		remove_action( 'wp_head', 'rest_output_link_wp_head' );
		remove_action( 'wp_head', 'wp_shortlink_wp_head' );
		remove_action( 'wp_head', 'feed_links', 2 );
		remove_action( 'wp_head', 'feed_links_extra', 3 );
		add_filter( 'pre_option_blog_public', '__return_zero' ); // noindex for this response only.
		status_header( 200 );
		header( 'Content-Type: text/html; charset=' . get_option( 'blog_charset' ) );

		$tab = self::current_tab();

		if ( ! is_user_logged_in() || ! current_user_can( self::cap() ) ) {
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_portal_assets' ), 20 );
			add_action( 'wp_enqueue_scripts', array( $this, 'dequeue_theme_assets' ), 999 );
			self::template(
				'portal-login',
				array(
					'redirect'  => self::portal_url( $tab ),
					'logged_in' => is_user_logged_in(),
				)
			);
			exit;
		}

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_portal_assets' ), 20 );
		add_action( 'wp_enqueue_scripts', array( $this, 'dequeue_theme_assets' ), 999 );
		add_filter( 'show_admin_bar', '__return_false' );

		self::template(
			'portal',
			array(
				'tab'    => $tab,
				'tabs'   => $this->tabs,
				'notice' => self::pop_notice(),
				'has_wc' => self::has_woocommerce(),
			)
		);
		exit;
	}

	/**
	 * Styles and scripts for the portal document.
	 */
	public function enqueue_portal_assets(): void {
		if ( is_user_logged_in() && current_user_can( self::cap() ) ) {
			wp_enqueue_media();
		}
		wp_enqueue_style( 'dashicons' );
		wp_enqueue_style( 'sena-studio-figtree', 'https://fonts.googleapis.com/css2?family=Figtree:wght@400;500;600;700;800&display=swap', array(), null ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
		wp_enqueue_style( 'sena-studio', SENA_STUDIO_URL . 'assets/studio.css', array( 'dashicons' ), SENA_STUDIO_VERSION );

		$hand_path = get_template_directory() . '/fonts/SenaHandwriting-Regular.ttf';
		if ( file_exists( $hand_path ) ) {
			$hand_url = get_template_directory_uri() . '/fonts/SenaHandwriting-Regular.ttf';
			wp_add_inline_style( 'sena-studio', "@font-face{font-family:'Sena Handwriting';src:url(" . esc_url( $hand_url ) . ") format('truetype');font-display:swap}.sena-portal{--sena-hand:'Sena Handwriting',cursive}" );
		}

		wp_enqueue_script( 'sena-studio', SENA_STUDIO_URL . 'assets/studio.js', array(), SENA_STUDIO_VERSION, true );
		$this->localize();
	}

	/**
	 * Keep the theme's own CSS/JS out of the portal; WordPress core (media, dashicons) stays.
	 */
	public function dequeue_theme_assets(): void {
		$theme_dirs = array_unique( array( get_template_directory_uri(), get_stylesheet_directory_uri() ) );
		foreach ( array( wp_styles(), wp_scripts() ) as $deps ) {
			foreach ( (array) $deps->queue as $handle ) {
				$src = isset( $deps->registered[ $handle ] ) ? (string) $deps->registered[ $handle ]->src : '';
				foreach ( $theme_dirs as $dir ) {
					if ( $src && str_starts_with( $src, $dir ) ) {
						$deps->dequeue( $handle );
					}
				}
			}
		}
	}

	private function localize(): void {
		wp_localize_script(
			'sena-studio',
			'SenaStudio',
			array(
				'restUrl'      => esc_url_raw( rest_url( 'sena-studio/v1/' ) ),
				'restNonce'    => wp_create_nonce( 'wp_rest' ),
				'portalReturn' => self::is_portal() ? self::return_url() : '',
				'i18n'         => array(
					'choosePhotos' => __( 'Choose photos', 'sena-studio' ),
					'usePhotos'    => __( 'Use these photos', 'sena-studio' ),
					'copied'       => __( 'Copied', 'sena-studio' ),
					'copy'         => __( 'Copy caption', 'sena-studio' ),
					'confirm'      => __( 'Are you sure?', 'sena-studio' ),
				),
			)
		);
	}

	/**
	 * Build a URL to a Studio tab.
	 */
	public static function url( string $tab = 'today', array $args = array() ): string {
		if ( self::is_portal() ) {
			return self::portal_url( $tab, $args );
		}
		$args = array_merge( array( 'page' => self::PAGE, 'tab' => $tab ), $args );
		return add_query_arg( $args, admin_url( 'admin.php' ) );
	}

	/**
	 * Build a nonce-protected admin-post action URL.
	 */
	public static function action_url( string $action, array $args = array() ): string {
		$args = array_merge( array( 'action' => $action ), $args );
		if ( self::is_portal() ) {
			$args['_sena_return'] = self::return_url();
		}
		return wp_nonce_url( add_query_arg( $args, admin_url( 'admin-post.php' ) ), $action );
	}

	/**
	 * Verify capability + nonce for an admin-post handler. Dies on failure.
	 */
	public static function verify_action( string $action ): void {
		if ( ! current_user_can( self::cap() ) ) {
			wp_die( esc_html__( 'You are not allowed to do that.', 'sena-studio' ), 403 );
		}
		check_admin_referer( $action );
	}

	/**
	 * Store a one-time notice and redirect back to a tab.
	 */
	public static function redirect( string $tab, string $message = '', string $type = 'success', array $args = array() ): void {
		if ( '' !== $message ) {
			set_transient( 'sena_studio_notice_' . get_current_user_id(), array( 'message' => $message, 'type' => $type ), 60 );
		}
		wp_safe_redirect( self::url( $tab, $args ) );
		exit;
	}

	public static function pop_notice(): ?array {
		$key    = 'sena_studio_notice_' . get_current_user_id();
		$notice = get_transient( $key );
		if ( $notice ) {
			delete_transient( $key );
			return $notice;
		}
		return null;
	}

	public static function current_tab(): string {
		$tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( '' === $tab && ! is_admin() ) {
			$tab = sanitize_key( (string) get_query_var( 'sena_tab' ) );
		}
		if ( '' === $tab && isset( $_REQUEST['_sena_return'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$path = (string) wp_parse_url( esc_url_raw( wp_unslash( $_REQUEST['_sena_return'] ) ), PHP_URL_PATH ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( preg_match( '#/studio/([a-z-]+)/?#', $path, $m ) ) {
				$tab = sanitize_key( $m[1] );
			}
		}
		if ( '' === $tab ) {
			$tab = 'today';
		}
		$tabs = self::instance()->tabs;
		return isset( $tabs[ $tab ] ) ? $tab : 'today';
	}

	public static function tabs(): array {
		return self::instance()->tabs;
	}

	/**
	 * Render a template from /templates with variables in scope.
	 */
	public static function template( string $name, array $vars = array() ): void {
		$file = SENA_STUDIO_DIR . 'templates/' . $name . '.php';
		if ( ! file_exists( $file ) ) {
			return;
		}
		extract( $vars, EXTR_SKIP ); // phpcs:ignore WordPress.PHP.DontExtract
		include $file;
	}

	public static function money( $amount ): string {
		if ( function_exists( 'wc_price' ) ) {
			return html_entity_decode( wp_strip_all_tags( wc_price( (float) $amount ) ), ENT_QUOTES, 'UTF-8' );
		}
		return number_format_i18n( (float) $amount, 2 );
	}

	public static function is_studio_screen(): bool {
		return is_admin() && isset( $_GET['page'] ) && self::PAGE === sanitize_key( wp_unslash( $_GET['page'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}

	/* ------------------------------------------------------------------ */
	/* Setup                                                                */
	/* ------------------------------------------------------------------ */

	public function load_textdomain(): void {
		load_plugin_textdomain( 'sena-studio', false, dirname( plugin_basename( SENA_STUDIO_FILE ) ) . '/languages' );
	}

	public function set_tabs(): void {
		$this->tabs = array(
			'today'    => __( 'Today', 'sena-studio' ),
			'orders'   => __( 'Orders', 'sena-studio' ),
			'products' => __( 'Pieces', 'sena-studio' ),
			'requests' => __( 'Requests', 'sena-studio' ),
			'posts'    => __( 'Posts', 'sena-studio' ),
			'events'   => __( 'Events', 'sena-studio' ),
			'settings' => __( 'Settings', 'sena-studio' ),
		);
	}

	public static function tab_icon( string $tab ): string {
		$icons = array(
			'today'    => 'dashicons-sun',
			'orders'   => 'dashicons-archive',
			'products' => 'dashicons-art',
			'requests' => 'dashicons-email-alt',
			'posts'    => 'dashicons-share',
			'events'   => 'dashicons-calendar-alt',
			'settings' => 'dashicons-admin-generic',
		);
		return $icons[ $tab ] ?? 'dashicons-marker';
	}

	public function register_menu(): void {
		add_menu_page(
			__( 'Studio', 'sena-studio' ),
			__( 'Studio', 'sena-studio' ),
			self::cap(),
			self::PAGE,
			array( $this, 'render_page' ),
			'dashicons-art',
			1
		);
	}

	/**
	 * "Studio" shortcut in the admin bar, front and back.
	 */
	public function admin_bar( WP_Admin_Bar $bar ): void {
		if ( ! current_user_can( self::cap() ) ) {
			return;
		}
		$bar->add_node(
			array(
				'id'    => 'sena-studio',
				'title' => '<span class="ab-icon dashicons-art" aria-hidden="true"></span><span class="ab-label">' . esc_html__( 'Studio', 'sena-studio' ) . '</span>',
				'href'  => self::portal_url( 'today' ),
				'meta'  => array( 'title' => __( 'Open Sena Studio', 'sena-studio' ) ),
			)
		);
	}

	public function admin_bar_style(): void {
		if ( is_admin_bar_showing() && current_user_can( self::cap() ) ) {
			wp_add_inline_style( 'admin-bar', '#wpadminbar #wp-admin-bar-sena-studio .ab-icon:before{content:"\\f309";top:2px}' );
		}
	}

	/**
	 * Send shop managers to Studio after login unless they asked for a specific page.
	 *
	 * @param string           $redirect_to
	 * @param string           $requested
	 * @param WP_User|WP_Error $user
	 */
	public function login_redirect( $redirect_to, $requested, $user ) {
		if ( ! $user instanceof WP_User || ! user_can( $user, 'manage_woocommerce' ) ) {
			return $redirect_to;
		}
		/**
		 * Disable the Studio login redirect by returning false.
		 *
		 * @param bool    $enabled
		 * @param WP_User $user
		 */
		if ( ! apply_filters( 'sena_studio_login_redirect', true, $user ) ) {
			return $redirect_to;
		}
		$requested = (string) $requested;
		$generic   = array( '', admin_url(), admin_url( '/' ), admin_url( 'index.php' ), home_url(), home_url( '/' ) );
		if ( in_array( untrailingslashit( $requested ), array_map( 'untrailingslashit', $generic ), true ) ) {
			return self::portal_url( 'today' );
		}
		return $redirect_to;
	}

	public function body_class( string $classes ): string {
		if ( self::is_studio_screen() ) {
			$classes .= ' sena-studio-screen';
		}
		return $classes;
	}

	public function enqueue_assets( string $hook ): void {
		if ( 'toplevel_page_' . self::PAGE !== $hook ) {
			return;
		}
		wp_enqueue_media();
		wp_enqueue_style( 'dashicons' );
		wp_enqueue_style( 'sena-studio', SENA_STUDIO_URL . 'assets/studio.css', array( 'dashicons' ), SENA_STUDIO_VERSION );
		wp_enqueue_script( 'sena-studio', SENA_STUDIO_URL . 'assets/studio.js', array(), SENA_STUDIO_VERSION, true );
		$this->localize();
	}

	public function register_front_assets(): void {
		wp_register_style( 'sena-studio-front', SENA_STUDIO_URL . 'assets/front.css', array(), SENA_STUDIO_VERSION );
	}

	/**
	 * Enqueue the small front-end stylesheet (called from shortcodes).
	 */
	public static function front_styles(): void {
		if ( wp_style_is( 'sena-studio-front', 'registered' ) ) {
			wp_enqueue_style( 'sena-studio-front' );
		}
	}

	public function woocommerce_notice(): void {
		if ( self::has_woocommerce() || ! current_user_can( 'activate_plugins' ) ) {
			return;
		}
		$screen = get_current_screen();
		if ( $screen && ( 'plugins' === $screen->id || 'toplevel_page_' . self::PAGE === $screen->id ) ) {
			echo '<div class="notice notice-warning"><p>' . esc_html__( 'Sena Studio needs WooCommerce for orders, pieces and quotes. Requests, posts and events still work without it.', 'sena-studio' ) . '</p></div>';
		}
	}

	/* ------------------------------------------------------------------ */
	/* Page                                                                 */
	/* ------------------------------------------------------------------ */

	public function render_page(): void {
		if ( ! current_user_can( self::cap() ) ) {
			wp_die( esc_html__( 'You are not allowed to view this page.', 'sena-studio' ), 403 );
		}
		$tab    = self::current_tab();
		$notice = self::pop_notice();
		$has_wc = self::has_woocommerce();
		self::template(
			'layout',
			array(
				'tab'    => $tab,
				'tabs'   => $this->tabs,
				'notice' => $notice,
				'has_wc' => $has_wc,
			)
		);
	}

	/**
	 * Called from the layout template to render the active tab body.
	 */
	public static function render_tab( string $tab ): void {
		$has_wc = self::has_woocommerce();
		switch ( $tab ) {
			case 'orders':
				$has_wc ? Sena_Studio_Orders::render_tab() : self::template( 'no-woocommerce' );
				break;
			case 'products':
				$has_wc ? Sena_Studio_Products::render_tab() : self::template( 'no-woocommerce' );
				break;
			case 'requests':
				Sena_Studio_Requests::render_tab();
				break;
			case 'posts':
				Sena_Studio_Posts::render_tab();
				break;
			case 'events':
				Sena_Studio_Events::render_tab();
				break;
			case 'settings':
				self::template( 'settings', array( 'settings' => self::settings() ) );
				break;
			default:
				Sena_Studio_Dashboard::render_tab();
		}
	}

	/* ------------------------------------------------------------------ */
	/* Settings                                                             */
	/* ------------------------------------------------------------------ */

	public function handle_save_settings(): void {
		self::verify_action( 'sena_studio_save_settings' );

		$percent = isset( $_POST['deposit_percent'] ) ? absint( wp_unslash( $_POST['deposit_percent'] ) ) : 50;
		$percent = max( 1, min( 100, $percent ) );

		$email = isset( $_POST['notification_email'] ) ? sanitize_email( wp_unslash( $_POST['notification_email'] ) ) : '';
		if ( ! is_email( $email ) ) {
			$email = get_option( 'admin_email' );
		}

		$settings = array(
			'thank_you_note'     => isset( $_POST['thank_you_note'] ) ? sanitize_textarea_field( wp_unslash( $_POST['thank_you_note'] ) ) : '',
			'deposit_percent'    => $percent,
			'notification_email' => $email,
			'pirate_ship_link'   => empty( $_POST['pirate_ship_link'] ) ? 0 : 1,
			'monthly_goal'       => Sena_Studio_Dashboard::sanitize_goal( isset( $_POST['monthly_goal'] ) ? wp_unslash( $_POST['monthly_goal'] ) : 500 ), // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		);
		update_option( self::OPTION, $settings );
		self::redirect( 'settings', __( 'Settings saved.', 'sena-studio' ) );
	}
}
