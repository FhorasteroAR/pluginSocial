<?php
/**
 * WP-Cron background refresh.
 *
 * Schedules a recurring event that warms the feed cache so updates do not rely
 * on visitor page loads. The schedule mirrors the admin settings: it is
 * (re)applied whenever settings are saved and on activation.
 *
 * @package SocialFeed
 */

defined( 'ABSPATH' ) || exit;

class Social_Feed_Cron {

	/**
	 * Cron hook name.
	 */
	const HOOK = 'social_feed_refresh_event';

	/**
	 * Custom interval name (12 hours) added on top of the WP defaults.
	 */
	const TWICE_DAILY = 'twicedaily';

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_filter( 'cron_schedules', array( $this, 'add_schedules' ) );
		add_action( self::HOOK, array( $this, 'run' ) );
	}

	/**
	 * Selectable intervals (value => label). Keys map to cron_schedules names.
	 *
	 * @return array<string, string>
	 */
	public static function interval_choices() {
		return array(
			'hourly'           => __( 'Every hour', 'social-feed' ),
			'social_feed_6h'   => __( 'Every 6 hours', 'social-feed' ),
			'twicedaily'       => __( 'Twice a day', 'social-feed' ),
			'daily'            => __( 'Once a day', 'social-feed' ),
		);
	}

	/**
	 * Add any custom schedules not provided by core.
	 *
	 * @param array $schedules Existing schedules.
	 *
	 * @return array
	 */
	public function add_schedules( $schedules ) {
		if ( ! isset( $schedules['social_feed_6h'] ) ) {
			$schedules['social_feed_6h'] = array(
				'interval' => 6 * HOUR_IN_SECONDS,
				'display'  => __( 'Every 6 hours (Social Feed)', 'social-feed' ),
			);
		}

		return $schedules;
	}

	/**
	 * Apply (or clear) the schedule based on settings. Idempotent.
	 *
	 * @param bool|int $enabled  Whether background updates are on.
	 * @param string   $interval Schedule name.
	 *
	 * @return void
	 */
	public static function apply_schedule( $enabled, $interval ) {
		$interval = array_key_exists( $interval, self::interval_choices() ) ? $interval : 'hourly';
		$current  = wp_get_schedule( self::HOOK );

		if ( empty( $enabled ) ) {
			if ( false !== $current ) {
				wp_clear_scheduled_hook( self::HOOK );
			}
			return;
		}

		// Already scheduled at the right cadence — nothing to do.
		if ( $current === $interval ) {
			return;
		}

		// Reschedule: clear any existing event then create the new one.
		wp_clear_scheduled_hook( self::HOOK );
		wp_schedule_event( time() + MINUTE_IN_SECONDS, $interval, self::HOOK );
	}

	/**
	 * Schedule on activation according to saved/default settings.
	 *
	 * @return void
	 */
	public static function activate() {
		self::apply_schedule(
			Social_Feed_Settings::get( 'auto_update', 1 ),
			Social_Feed_Settings::get( 'cron_interval', 'hourly' )
		);
	}

	/**
	 * Always clear the event on deactivation.
	 *
	 * @return void
	 */
	public static function deactivate() {
		wp_clear_scheduled_hook( self::HOOK );
	}

	/**
	 * Cron callback: refresh the cache for the configured network(s).
	 *
	 * @return void
	 */
	public function run() {
		$network = Social_Feed_Settings::get( 'network', 'instagram' );
		$count   = (int) Social_Feed_Settings::get( 'count', 9 );

		/**
		 * Filter the networks refreshed by the background task. Defaults to the
		 * single configured network; return more to warm several caches.
		 *
		 * @param string[] $networks Network keys.
		 */
		$networks = apply_filters( 'social_feed_cron_networks', array( $network ) );

		foreach ( (array) $networks as $net ) {
			// Force a fresh fetch by dropping the transient, then repopulate it.
			Social_Feed_API_Handler::flush_cache( $net, $count );
			Social_Feed_API_Handler::get_posts( $net, $count );
		}
	}
}
