<?php
/**
 * Renderer.
 *
 * Turns a fetch result into HTML. Uses output buffering (ob_start) around a
 * template file so the markup stays readable and all escaping happens at the
 * point of output.
 *
 * @package SocialFeed
 */

defined( 'ABSPATH' ) || exit;

class Social_Feed_Renderer {

	/**
	 * Render the feed for the given attributes.
	 *
	 * @param array $atts {
	 *     Display attributes (already sanitised by the caller).
	 *
	 *     @type string $network Network key.
	 *     @type int    $count   Number of posts.
	 *     @type int    $columns Grid columns.
	 *     @type string $title   Optional heading.
	 * }
	 *
	 * @return string Safe HTML.
	 */
	public static function render( array $atts ) {
		$network = isset( $atts['network'] ) ? sanitize_key( $atts['network'] ) : 'instagram';
		$count   = isset( $atts['count'] ) ? absint( $atts['count'] ) : 9;
		$columns = isset( $atts['columns'] ) ? max( 1, min( 6, absint( $atts['columns'] ) ) ) : 3;
		$title   = isset( $atts['title'] ) ? sanitize_text_field( $atts['title'] ) : '';

		$result = Social_Feed_API_Handler::get_posts( $network, $count );

		// Make these available to the template scope.
		$posts = $result['posts'];
		$error = $result['error'];

		ob_start();
		include SOCIAL_FEED_PATH . 'templates/feed.php';

		return (string) ob_get_clean();
	}
}
