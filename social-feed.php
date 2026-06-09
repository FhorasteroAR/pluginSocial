<?php
/**
 * Plugin Name:       Social Feed
 * Plugin URI:        https://example.com/social-feed
 * Description:       Lightweight, secure and modular plugin to display the latest social media posts (Instagram, LinkedIn, ...) via a shortcode and a Gutenberg block.
 * Version:           1.0.0
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            Your Name
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       social-feed
 *
 * @package SocialFeed
 */

defined( 'ABSPATH' ) || exit;

/*
|--------------------------------------------------------------------------
| Plugin constants
|--------------------------------------------------------------------------
*/
define( 'SOCIAL_FEED_VERSION', '1.0.0' );
define( 'SOCIAL_FEED_FILE', __FILE__ );
define( 'SOCIAL_FEED_PATH', plugin_dir_path( __FILE__ ) );
define( 'SOCIAL_FEED_URL', plugin_dir_url( __FILE__ ) );

/*
|--------------------------------------------------------------------------
| Includes
|--------------------------------------------------------------------------
*/
require_once SOCIAL_FEED_PATH . 'includes/class-social-feed-api-handler.php';
require_once SOCIAL_FEED_PATH . 'includes/handlers/abstract-class-social-feed-handler.php';
require_once SOCIAL_FEED_PATH . 'includes/handlers/class-social-feed-instagram-handler.php';
require_once SOCIAL_FEED_PATH . 'includes/handlers/class-social-feed-linkedin-handler.php';
require_once SOCIAL_FEED_PATH . 'includes/class-social-feed-renderer.php';
require_once SOCIAL_FEED_PATH . 'includes/class-social-feed-shortcode.php';
require_once SOCIAL_FEED_PATH . 'includes/class-social-feed-block.php';
require_once SOCIAL_FEED_PATH . 'includes/class-social-feed-settings.php';
require_once SOCIAL_FEED_PATH . 'includes/class-social-feed-cron.php';
require_once SOCIAL_FEED_PATH . 'includes/class-social-feed-plugin.php';

/*
|--------------------------------------------------------------------------
| Activation / Deactivation hooks
|--------------------------------------------------------------------------
*/
register_activation_hook( __FILE__, array( 'Social_Feed_Plugin', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Social_Feed_Plugin', 'deactivate' ) );

/*
|--------------------------------------------------------------------------
| Bootstrap
|--------------------------------------------------------------------------
*/
add_action(
	'plugins_loaded',
	static function () {
		Social_Feed_Plugin::instance()->init();
	}
);
