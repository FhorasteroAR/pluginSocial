<?php
/**
 * LinkedIn handler.
 *
 * Uses the LinkedIn Marketing / Posts API. Define credentials in wp-config.php:
 *
 *     define( 'SOCIAL_FEED_LINKEDIN_ACCESS_TOKEN', '...' );
 *     define( 'SOCIAL_FEED_LINKEDIN_ORG_URN', 'urn:li:organization:12345' );
 *
 * Falls back to sample data when no token is present so the grid can be
 * previewed without live API access.
 *
 * @package SocialFeed
 */

defined( 'ABSPATH' ) || exit;

class Social_Feed_LinkedIn_Handler extends Social_Feed_Abstract_Handler {

	/**
	 * REST endpoint for organization posts.
	 */
	const ENDPOINT = 'https://api.linkedin.com/rest/posts';

	/**
	 * {@inheritDoc}
	 */
	public function get_network() {
		return 'linkedin';
	}

	/**
	 * {@inheritDoc}
	 */
	public function fetch( $count ) {
		$token = $this->get_credential( 'access_token' );
		$org   = $this->get_credential( 'org_urn' );

		if ( '' === $token || '' === $org ) {
			return $this->sample_posts( $count );
		}

		$url = add_query_arg(
			array(
				'author' => rawurlencode( $org ),
				'q'      => 'author',
				'count'  => absint( $count ),
			),
			self::ENDPOINT
		);

		$data = $this->request(
			$url,
			array(
				'headers' => array(
					'Authorization'             => 'Bearer ' . $token,
					'X-Restli-Protocol-Version' => '2.0.0',
					'LinkedIn-Version'          => '202401',
					'Accept'                    => 'application/json',
				),
			)
		);

		if ( is_wp_error( $data ) ) {
			return $data;
		}

		$items = isset( $data['elements'] ) && is_array( $data['elements'] ) ? $data['elements'] : array();
		$posts = array();

		foreach ( $items as $item ) {
			$id = $item['id'] ?? '';

			$posts[] = array(
				'id'        => $id,
				'image'     => $this->extract_image( $item ),
				'text'      => $item['commentary'] ?? '',
				'permalink' => $id ? 'https://www.linkedin.com/feed/update/' . rawurlencode( $id ) : '',
				'author'    => $org,
				'date'      => isset( $item['createdAt'] ) ? gmdate( 'c', (int) ( $item['createdAt'] / 1000 ) ) : '',
			);
		}

		return $posts;
	}

	/**
	 * Best-effort extraction of a thumbnail from a LinkedIn post payload.
	 *
	 * @param array $item Post element.
	 *
	 * @return string
	 */
	private function extract_image( array $item ) {
		if ( ! empty( $item['content']['media']['thumbnail'] ) ) {
			return (string) $item['content']['media']['thumbnail'];
		}

		return '';
	}

	/**
	 * Placeholder posts shown when no credentials are configured.
	 *
	 * @param int $count Number of items.
	 *
	 * @return array
	 */
	private function sample_posts( $count ) {
		$samples = array();

		for ( $i = 1; $i <= $count; $i++ ) {
			$samples[] = array(
				'id'        => 'li-sample-' . $i,
				'image'     => 'https://picsum.photos/seed/li' . $i . '/600/600',
				'text'      => sprintf( __( 'Sample LinkedIn update #%d. Configure an access token to show real posts.', 'social-feed' ), $i ),
				'permalink' => 'https://linkedin.com',
				'author'    => 'Demo Company',
				'date'      => gmdate( 'c' ),
			);
		}

		return $samples;
	}
}
