<?php
/**
 * Shortcode component: [show_social_media]
 *
 * Example:
 *   [show_social_media network="instagram" count="6" columns="3" title="Follow us"]
 *
 * @package SocialFeed
 */

defined( 'ABSPATH' ) || exit;

class Social_Feed_Shortcode {

	/**
	 * Shortcode tag.
	 */
	const TAG = 'show_social_media';

	/**
	 * Register the shortcode.
	 *
	 * @return void
	 */
	public function register() {
		add_shortcode( self::TAG, array( $this, 'render' ) );
	}

	/**
	 * Shortcode callback.
	 *
	 * @param array|string $atts Raw shortcode attributes.
	 *
	 * @return string
	 */
	public function render( $atts ) {
		// Defaults come from the settings page, so an attribute-less shortcode
		// reflects the site-wide configuration.
		$atts = shortcode_atts(
			array(
				'network' => Social_Feed_Settings::get( 'network', 'instagram' ),
				'count'   => Social_Feed_Settings::get( 'count', 9 ),
				'columns' => Social_Feed_Settings::get( 'columns', 3 ),
				'title'   => Social_Feed_Settings::get( 'title', '' ),
			),
			$atts,
			self::TAG
		);

		// Sanitise each input before handing off to the renderer.
		$atts['network'] = sanitize_key( $atts['network'] );
		$atts['count']   = absint( $atts['count'] );
		$atts['columns'] = absint( $atts['columns'] );
		$atts['title']   = sanitize_text_field( $atts['title'] );

		// Ensure the stylesheet is present even if the shortcode runs late.
		if ( ! wp_style_is( 'social-feed', 'enqueued' ) ) {
			wp_enqueue_style( 'social-feed' );
		}

		return Social_Feed_Renderer::render( $atts );
	}
}
