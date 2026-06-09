<?php
/**
 * Main plugin orchestrator.
 *
 * Wires together the components (shortcode, block, assets) and owns the
 * activation / deactivation lifecycle. Kept intentionally thin: it delegates
 * the real work to the dedicated component classes.
 *
 * @package SocialFeed
 */

defined( 'ABSPATH' ) || exit;

final class Social_Feed_Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var Social_Feed_Plugin|null
	 */
	private static $instance = null;

	/**
	 * Whether init() has already run.
	 *
	 * @var bool
	 */
	private $booted = false;

	/**
	 * Retrieve the shared instance.
	 *
	 * @return Social_Feed_Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Private constructor — use instance().
	 */
	private function __construct() {}

	/**
	 * Register hooks. Safe to call once.
	 *
	 * @return void
	 */
	public function init() {
		if ( $this->booted ) {
			return;
		}
		$this->booted = true;

		// Frontend assets (registered always so the block editor can enqueue them too).
		add_action( 'init', array( $this, 'register_assets' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );

		// Components.
		( new Social_Feed_Shortcode() )->register();
		( new Social_Feed_Block() )->register();

		// Authenticated AJAX endpoint to force-refresh the cache from the editor.
		add_action( 'wp_ajax_social_feed_refresh', array( $this, 'ajax_refresh_cache' ) );

		load_plugin_textdomain( 'social-feed', false, dirname( plugin_basename( SOCIAL_FEED_FILE ) ) . '/languages' );
	}

	/**
	 * Register (but do not necessarily enqueue) the front-end style.
	 *
	 * @return void
	 */
	public function register_assets() {
		wp_register_style(
			'social-feed',
			SOCIAL_FEED_URL . 'assets/css/social-feed.css',
			array(),
			SOCIAL_FEED_VERSION
		);
	}

	/**
	 * Enqueue the stylesheet on the front end.
	 *
	 * @return void
	 */
	public function enqueue_frontend_assets() {
		wp_enqueue_style( 'social-feed' );
	}

	/**
	 * AJAX: clear the cached transient for a given network so the next render
	 * fetches fresh data. Protected by a nonce and a capability check.
	 *
	 * @return void
	 */
	public function ajax_refresh_cache() {
		check_ajax_referer( 'social_feed_refresh', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'social-feed' ) ), 403 );
		}

		$network = isset( $_POST['network'] ) ? sanitize_key( wp_unslash( $_POST['network'] ) ) : '';
		$count   = isset( $_POST['count'] ) ? absint( wp_unslash( $_POST['count'] ) ) : 9;

		if ( '' === $network ) {
			wp_send_json_error( array( 'message' => __( 'Missing network.', 'social-feed' ) ), 400 );
		}

		Social_Feed_API_Handler::flush_cache( $network, $count );

		wp_send_json_success( array( 'message' => __( 'Cache cleared.', 'social-feed' ) ) );
	}

	/**
	 * Activation callback. Nothing destructive — just a version flag.
	 *
	 * @return void
	 */
	public static function activate() {
		add_option( 'social_feed_version', SOCIAL_FEED_VERSION );
	}

	/**
	 * Deactivation callback. Remove our transients so we leave a clean state.
	 *
	 * @return void
	 */
	public static function deactivate() {
		Social_Feed_API_Handler::flush_all_caches();
	}
}
