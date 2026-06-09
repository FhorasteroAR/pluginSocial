<?php
/**
 * Gutenberg block component.
 *
 * Registers a dynamic (server-rendered) block: the editor stores only the
 * attributes, and PHP renders the same markup as the shortcode at display
 * time. This keeps a single source of truth for the output.
 *
 * @package SocialFeed
 */

defined( 'ABSPATH' ) || exit;

class Social_Feed_Block {

	/**
	 * Block name (namespace/name).
	 */
	const NAME = 'social-feed/feed';

	/**
	 * Register the block type and its editor assets.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'init', array( $this, 'register_block' ) );
	}

	/**
	 * Register editor script + dynamic block.
	 *
	 * @return void
	 */
	public function register_block() {
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}

		wp_register_script(
			'social-feed-block',
			SOCIAL_FEED_URL . 'assets/js/block.js',
			array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-server-side-render', 'wp-i18n' ),
			SOCIAL_FEED_VERSION,
			true
		);

		register_block_type(
			self::NAME,
			array(
				'api_version'     => 2,
				'editor_script'   => 'social-feed-block',
				'editor_style'    => 'social-feed',
				'style'           => 'social-feed',
				'render_callback' => array( $this, 'render' ),
				'attributes'      => array(
					'network' => array(
						'type'    => 'string',
						'default' => 'instagram',
					),
					'count'   => array(
						'type'    => 'number',
						'default' => 9,
					),
					'columns' => array(
						'type'    => 'number',
						'default' => 3,
					),
					'title'   => array(
						'type'    => 'string',
						'default' => '',
					),
				),
			)
		);
	}

	/**
	 * Server-side render callback for the block.
	 *
	 * @param array $attributes Block attributes (validated against the schema above).
	 *
	 * @return string
	 */
	public function render( $attributes ) {
		$atts = array(
			'network' => sanitize_key( $attributes['network'] ?? 'instagram' ),
			'count'   => absint( $attributes['count'] ?? 9 ),
			'columns' => absint( $attributes['columns'] ?? 3 ),
			'title'   => sanitize_text_field( $attributes['title'] ?? '' ),
		);

		return Social_Feed_Renderer::render( $atts );
	}
}
