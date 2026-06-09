<?php
/**
 * Uninstall routine.
 *
 * Runs when the plugin is deleted from the WordPress admin. Removes options
 * and cached transients so no orphaned data is left behind.
 *
 * @package SocialFeed
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'social_feed_version' );

global $wpdb;

// Remove all of the plugin's transients (value + timeout rows).
$wpdb->query(
	"DELETE FROM {$wpdb->options}
	 WHERE option_name LIKE '\_transient\_social\_feed\_%'
	    OR option_name LIKE '\_transient\_timeout\_social\_feed\_%'"
);
