<?php
namespace Podlove;

use \Podlove\Model\Feed;
use \Podlove\Cache\TemplateCache;

/**
 * Adds feed discover links to WordPress head.
 */
class FeedDiscoverability {

	public static function init() {
		
		// priority 2 so they are placed below the WordPress default discovery links
		add_action( 'wp_head', array(__CLASS__, 'add_feed_discoverability'), 2 );

		// hide WordPress default link discovery
		if ( \Podlove\get_setting( 'website', 'hide_wp_feed_discovery' ) === 'on' ) {
			remove_action( 'wp_head', 'feed_links',       2 );
			remove_action( 'wp_head', 'feed_links_extra', 3 );
		}
		
	}

	public static function add_feed_discoverability() {

		if ( is_admin() )
			return;

		if ( ! function_exists( '\Podlove\Feeds\prepare_for_feed' ) )
			require_once \PODLOVE\PLUGIN_DIR . 'lib/feeds/base.php';

		echo TemplateCache::get_instance()->cache_for('', function() {
			$html = '';
			foreach (Feed::all('ORDER BY position ASC') as $feed) {
				if ($feed->discoverable)
					$html .= '<link rel="alternate" type="' . $feed->get_content_type() . '" title="' . \Podlove\Feeds\prepare_for_feed($feed->title_for_discovery()) . '" href="' . $feed->get_subscribe_url() . "\" />\n";			
			}
			return $html;
		});
	}

}