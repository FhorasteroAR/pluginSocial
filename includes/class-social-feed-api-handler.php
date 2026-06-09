<?php
/**
 * API handler facade.
 *
 * Public entry point for fetching posts. Responsibilities:
 *   - Resolve the correct network handler (factory).
 *   - Wrap every external request in the Transients API (1 hour cache).
 *   - Normalise the returned data into a predictable shape.
 *
 * Each concrete network lives in its own handler class extending
 * Social_Feed_Abstract_Handler, which keeps this file network-agnostic.
 *
 * @package SocialFeed
 */

defined( 'ABSPATH' ) || exit;

class Social_Feed_API_Handler {

	/**
	 * Cache lifetime in seconds (1 hour).
	 */
	const CACHE_TTL = HOUR_IN_SECONDS;

	/**
	 * Prefix for every transient created by the plugin.
	 */
	const CACHE_PREFIX = 'social_feed_';

	/**
	 * Map of supported networks => handler class names.
	 *
	 * Add a network by creating a handler and registering it here.
	 *
	 * @return array<string, string>
	 */
	public static function handlers() {
		return apply_filters(
			'social_feed_handlers',
			array(
				'instagram' => 'Social_Feed_Instagram_Handler',
				'linkedin'  => 'Social_Feed_LinkedIn_Handler',
			)
		);
	}

	/**
	 * Fetch posts for a network, served from cache when available.
	 *
	 * @param string $network Network key (e.g. "instagram").
	 * @param int    $count   Number of posts to return.
	 *
	 * @return array{posts: array, error: string} Normalised result.
	 */
	public static function get_posts( $network, $count = 9 ) {
		$network = sanitize_key( $network );
		$count   = max( 1, min( 30, absint( $count ) ) );

		$handlers = self::handlers();

		if ( ! isset( $handlers[ $network ] ) || ! class_exists( $handlers[ $network ] ) ) {
			return array(
				'posts' => array(),
				'error' => sprintf(
					/* translators: %s: network key. */
					__( 'Unsupported social network: %s', 'social-feed' ),
					$network
				),
			);
		}

		$cache_key = self::cache_key( $network, $count );
		$cached    = get_transient( $cache_key );

		if ( false !== $cached && is_array( $cached ) ) {
			return $cached;
		}

		/** @var Social_Feed_Abstract_Handler $handler */
		$handler = new $handlers[ $network ]();
		$posts   = $handler->fetch( $count );

		if ( is_wp_error( $posts ) ) {
			// Cache the failure briefly (5 min) so a broken API does not hammer the remote service.
			$result = array(
				'posts' => array(),
				'error' => $posts->get_error_message(),
			);
			set_transient( $cache_key, $result, 5 * MINUTE_IN_SECONDS );

			return $result;
		}

		$result = array(
			'posts' => self::normalise( $posts ),
			'error' => '',
		);

		set_transient( $cache_key, $result, self::CACHE_TTL );

		return $result;
	}

	/**
	 * Force-clear the cache for one network/count combination.
	 *
	 * @param string $network Network key.
	 * @param int    $count   Post count.
	 *
	 * @return void
	 */
	public static function flush_cache( $network, $count = 9 ) {
		delete_transient( self::cache_key( sanitize_key( $network ), absint( $count ) ) );
	}

	/**
	 * Remove every transient created by the plugin. Used on deactivation.
	 *
	 * @return void
	 */
	public static function flush_all_caches() {
		global $wpdb;

		// Direct query is the only reliable way to bulk-delete transients by prefix.
		$like = $wpdb->esc_like( '_transient_' . self::CACHE_PREFIX ) . '%';
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
				$like,
				$wpdb->esc_like( '_transient_timeout_' . self::CACHE_PREFIX ) . '%'
			)
		);
	}

	/**
	 * Build a deterministic transient key.
	 *
	 * @param string $network Network key.
	 * @param int    $count   Post count.
	 *
	 * @return string
	 */
	private static function cache_key( $network, $count ) {
		return self::CACHE_PREFIX . $network . '_' . $count;
	}

	/**
	 * Guarantee every post item has the keys the renderer expects, with
	 * safe string defaults. Raw values are NOT escaped here — escaping is the
	 * renderer's job, at output time.
	 *
	 * @param array $posts Raw posts from a handler.
	 *
	 * @return array
	 */
	private static function normalise( array $posts ) {
		$clean = array();

		foreach ( $posts as $post ) {
			$post = (array) $post;

			$clean[] = array(
				'id'        => isset( $post['id'] ) ? (string) $post['id'] : '',
				'image'     => isset( $post['image'] ) ? (string) $post['image'] : '',
				'text'      => isset( $post['text'] ) ? (string) $post['text'] : '',
				'permalink' => isset( $post['permalink'] ) ? (string) $post['permalink'] : '',
				'author'    => isset( $post['author'] ) ? (string) $post['author'] : '',
				'date'      => isset( $post['date'] ) ? (string) $post['date'] : '',
			);
		}

		return $clean;
	}
}
