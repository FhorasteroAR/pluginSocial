<?php
/**
 * Instagram handler.
 *
 * Uses the Instagram Basic Display API. Define the token in wp-config.php:
 *
 *     define( 'SOCIAL_FEED_INSTAGRAM_ACCESS_TOKEN', 'IGQVJ...' );
 *
 * If no token is configured the handler returns sample data so the layout can
 * be previewed during development.
 *
 * @package SocialFeed
 */

defined( 'ABSPATH' ) || exit;

class Social_Feed_Instagram_Handler extends Social_Feed_Abstract_Handler {

	/**
	 * Graph API endpoint for the authenticated user's media.
	 */
	const ENDPOINT = 'https://graph.instagram.com/me/media';

	/**
	 * {@inheritDoc}
	 */
	public function get_network() {
		return 'instagram';
	}

	/**
	 * {@inheritDoc}
	 */
	public function fetch( $count ) {
		$token = $this->get_credential( 'access_token' );

		if ( '' === $token ) {
			return $this->sample_posts( $count );
		}

		$url = add_query_arg(
			array(
				'fields'       => 'id,caption,media_type,media_url,thumbnail_url,permalink,timestamp,username',
				'limit'        => absint( $count ),
				'access_token' => $token,
			),
			self::ENDPOINT
		);

		$data = $this->request( $url );

		if ( is_wp_error( $data ) ) {
			return $data;
		}

		$items = isset( $data['data'] ) && is_array( $data['data'] ) ? $data['data'] : array();
		$posts = array();

		foreach ( $items as $item ) {
			// Videos expose a thumbnail; images use media_url.
			$image = ! empty( $item['thumbnail_url'] ) ? $item['thumbnail_url'] : ( $item['media_url'] ?? '' );

			$posts[] = array(
				'id'        => $item['id'] ?? '',
				'image'     => $image,
				'text'      => $item['caption'] ?? '',
				'permalink' => $item['permalink'] ?? '',
				'author'    => isset( $item['username'] ) ? '@' . $item['username'] : '',
				'date'      => $item['timestamp'] ?? '',
			);
		}

		return $posts;
	}

	/**
	 * Placeholder posts shown when no token is configured.
	 *
	 * @param int $count Number of items.
	 *
	 * @return array
	 */
	private function sample_posts( $count ) {
		$samples = array();

		for ( $i = 1; $i <= $count; $i++ ) {
			$samples[] = array(
				'id'        => 'ig-sample-' . $i,
				'image'     => 'https://picsum.photos/seed/ig' . $i . '/600/600',
				'text'      => sprintf( __( 'Sample Instagram caption #%d. Configure an access token to show real posts.', 'social-feed' ), $i ),
				'permalink' => 'https://instagram.com',
				'author'    => '@demo',
				'date'      => gmdate( 'c' ),
			);
		}

		return $samples;
	}
}
