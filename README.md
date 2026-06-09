# Social Feed

A lightweight, secure and modular WordPress plugin that displays the latest
social media posts (Instagram, LinkedIn, and any network you add) through a
**shortcode** and a **Gutenberg block**.

## Features

- Shortcode: `[show_social_media]`
- Server-rendered Gutenberg block (single source of truth with the shortcode)
- Responsive CSS grid (image + text + link to the original post)
- 1-hour caching via the WordPress **Transients API**
- All inputs sanitised, all output escaped
- Nonce-protected AJAX endpoint to force-refresh the cache
- Modular handler architecture — add a network by dropping in one class

## File structure

```
social-feed.php                         Main file: constants, includes, hooks
uninstall.php                           Clean removal (options + transients)
includes/
  class-social-feed-plugin.php          Orchestrator + lifecycle + assets + AJAX
  class-social-feed-api-handler.php     Caching facade + handler factory
  class-social-feed-renderer.php        ob_start() view rendering
  class-social-feed-shortcode.php       [show_social_media]
  class-social-feed-block.php           Gutenberg block registration
  handlers/
    abstract-class-social-feed-handler.php    Base class + HTTP helpers
    class-social-feed-instagram-handler.php   Instagram Basic Display API
    class-social-feed-linkedin-handler.php    LinkedIn Posts API
templates/
  feed.php                              Frontend grid markup (all escaped)
assets/
  css/social-feed.css                   Minimal responsive grid styles
  js/block.js                           Block editor UI (no build step)
```

## Usage

### Shortcode

```
[show_social_media network="instagram" count="6" columns="3" title="Follow us"]
```

| Attribute | Default     | Description                          |
|-----------|-------------|--------------------------------------|
| `network` | `instagram` | `instagram`, `linkedin`, ...         |
| `count`   | `9`         | Number of posts (1–30)               |
| `columns` | `3`         | Grid columns (1–6)                   |
| `title`   | *(empty)*   | Optional heading above the grid      |

### Block

Add the **Social Feed** block in the editor and configure network, title,
post count and columns in the sidebar. A live preview is rendered server-side.

## Configuration (API credentials)

Define tokens in `wp-config.php` (recommended — they never touch the DB):

```php
// Instagram Basic Display API
define( 'SOCIAL_FEED_INSTAGRAM_ACCESS_TOKEN', 'IGQVJ...' );

// LinkedIn Posts API
define( 'SOCIAL_FEED_LINKEDIN_ACCESS_TOKEN', '...' );
define( 'SOCIAL_FEED_LINKEDIN_ORG_URN', 'urn:li:organization:12345' );
```

Without credentials the plugin renders **sample posts** so you can preview the
layout during development.

## Adding a new network

1. Create `includes/handlers/class-social-feed-{network}-handler.php` extending
   `Social_Feed_Abstract_Handler` and implement `get_network()` and `fetch()`.
2. `require_once` it in `social-feed.php`.
3. Register it via the `social_feed_handlers` filter (or add it to the map in
   `Social_Feed_API_Handler::handlers()`).
