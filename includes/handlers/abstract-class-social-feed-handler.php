<?php
/**
 * Abstract base for every network handler.
 *
 * A concrete handler only has to implement fetch() and return an array of
 * post arrays (or a WP_Error). Shared HTTP helpers live here so individual
 * handlers stay small.
 *
 * @package SocialFeed
 */

defined( 'ABSPATH' ) || exit;

abstract class Social_Feed_Abstract_Handler {

	/**
	 * Machine-readable network key (e.g. "instagram").
	 *
	 * @return string
	 */
	abstract public function get_network();

	/**
	 * Fetch the latest posts from the remote API.
	 *
	 * @param int $count Number of posts requested.
	 *
	 * @return array|WP_Error Array of post arrays, or WP_Error on failure.
	 */
	abstract public function fetch( $count );

	/**
	 * Read a stored credential / option for this network.
	 *
	 * Tokens are looked up first as a constant (recommended: define them in
	 * wp-config.php so they never hit the database), then as an option.
	 *
	 * @param string $name    Logical credential name, e.g. "access_token".
	 * @param mixed  $default Fallback value.
	 *
	 * @return mixed
	 */
	protected function get_credential( $name, $default = '' ) {
		$constant = strtoupper( 'SOCIAL_FEED_' . $this->get_network() . '_' . $name );

		if ( defined( $constant ) && '' !== constant( $constant ) ) {
			return constant( $constant );
		}

		$option = get_option( 'social_feed_' . $this->get_network() . '_' . $name, $default );

		return '' !== $option ? $option : $default;
	}

	/**
	 * Perform a GET request and decode the JSON body.
	 *
	 * @param string $url  Endpoint URL.
	 * @param array  $args Optional wp_remote_get() args.
	 *
	 * @return array|WP_Error Decoded array, or WP_Error.
	 */
	protected function request( $url, array $args = array() ) {
		$defaults = array(
			'timeout' => 10,
			'headers' => array( 'Accept' => 'application/json' ),
		);

		$response = wp_remote_get( esc_url_raw( $url ), wp_parse_args( $args, $defaults ) );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );

		if ( $code < 200 || $code >= 300 ) {
			return new WP_Error(
				'social_feed_http_error',
				sprintf(
					/* translators: 1: network, 2: HTTP status code. */
					__( '%1$s API returned HTTP %2$d.', 'social-feed' ),
					$this->get_network(),
					$code
				)
			);
		}

		$data = json_decode( $body, true );

		if ( JSON_ERROR_NONE !== json_last_error() ) {
			return new WP_Error( 'social_feed_json_error', __( 'Could not decode the API response.', 'social-feed' ) );
		}

		return is_array( $data ) ? $data : array();
	}
}
