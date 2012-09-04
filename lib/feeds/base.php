<?php
namespace Podlove\Feeds;
use Podlove\Model;

function mute_feed_title() {
	add_filter( 'bloginfo_rss', function ( $value, $key ) {
		return apply_filters( 'podlove_feed_title_name', ( $key == 'name' ) ? '' : $value );
	}, 10, 2 );
}

function override_feed_title( $feed ) {
	add_filter( 'wp_title_rss', function ( $title ) use ( $feed ) {
		return apply_filters( 'podlove_feed_title', htmlspecialchars( Model\Podcast::get_instance()->full_title() ) );
	} );
}

function override_feed_language( $feed ) {
	add_filter( 'pre_option_rss_language', function ( $language ) use ( $feed ) {
		$podcast = Model\Podcast::get_instance();
		return apply_filters( 'podlove_feed_language', ( $podcast->language ) ? $podcast->language : $language );
	} );
}

// todo: new line for each tag
// todo: hide tags without content
function override_feed_head( $hook, $podcast, $feed, $format ) {

	add_filter( 'podlove_feed_itunes_author', 'convert_chars' );
	add_filter( 'podlove_feed_itunes_owner', 'convert_chars' );
	add_filter( 'podlove_feed_itunes_subtitle', 'convert_chars' );
	add_filter( 'podlove_feed_itunes_keywords', 'convert_chars' );
	add_filter( 'podlove_feed_itunes_summary', 'convert_chars' );
	
	remove_action( $hook, 'the_generator' );
	add_action( $hook, function () use ( $hook ) {
		switch ( $hook ) {
			case 'rss2_head':
				$gen = '<generator>Podlove v' . \Podlove\get_plugin_header( 'Version' ) . '</generator>';
				break;
			case 'atom_head':
				$gen = '<generator uri="' . \Podlove\get_plugin_header( 'PluginURI' ) . '" version="' . \Podlove\get_plugin_header( 'Version' ) . '">' . \Podlove\get_plugin_header( 'Name' ) . '</generator>';
				break;
		}
		echo $gen;
	} );
	
	add_action( $hook, function () use ( $podcast, $feed, $format ) {
		$author = sprintf( '<itunes:author>%s</itunes:author>', $podcast->author_name );
		echo apply_filters( 'podlove_feed_itunes_author', $author );

		$summary = sprintf( '<itunes:summary>%s</itunes:summary>', $podcast->summary );
		echo apply_filters( 'podlove_feed_itunes_summary', $summary );

		$categories = \Podlove\Itunes\categories( false );	
		$category_html = '';
		for ( $i = 1; $i <= 3; $i++ ) { 
			$category_id = $podcast->{'category_' . $i};

			if ( ! $category_id )
				continue;

			list( $cat, $subcat ) = explode( '-', $category_id );

			if ( $subcat == '00' ) {
				$category_html .= sprintf(
					'<itunes:category text="%s"></itunes:category>',
					htmlspecialchars( $categories[ $category_id ] )
				);
			} else {
				$category_html .= sprintf(
					'<itunes:category text="%s"><itunes:category text="%s"></itunes:category></itunes:category>',
					htmlspecialchars( $categories[ $cat . '-00' ] ),
					htmlspecialchars( $categories[ $category_id ] )
				);
			}
		}
		echo apply_filters( 'podlove_feed_itunes_categories', $category_html );

		$owner = sprintf( '
			<itunes:owner>
				<itunes:name>%s</itunes:name>
				<itunes:email>%s</itunes:email>
			</itunes:owner>',
			$podcast->owner_name,
			$podcast->owner_email
		);
		echo apply_filters( 'podlove_feed_itunes_owner', $owner );
		
		if ( $podcast->cover_image ) {
			$coverimage = sprintf( '<itunes:image href="%s" />', $podcast->cover_image );
		} else {
			$coverimage = '';
		}
		echo apply_filters( 'podlove_feed_itunes_image', $coverimage );

		$subtitle = sprintf( '<itunes:subtitle>%s</itunes:subtitle>', $podcast->subtitle );
		echo apply_filters( 'podlove_feed_itunes_subtitle', $subtitle );

		$keywords = sprintf( '<itunes:keywords>%s</itunes:keywords>', $podcast->keywords );
		echo apply_filters( 'podlove_feed_itunes_keywords', $keywords );

		$block = sprintf( '<itunes:block>%s</itunes:block>', ( $feed->enable ) ? 'no' : 'yes' );
		echo apply_filters( 'podlove_feed_itunes_block', $block );

        $explicit = sprintf( '<itunes:explicit>%s</itunes:explicit>', ( $podcast->explicit == 2) ? 'clean' : ( ( $podcast->explicit ) ? 'yes' : 'no' ) );
		echo apply_filters( 'podlove_feed_itunes_explicit', $explicit );
	} );
}

function override_feed_entry( $hook, $podcast, $feed, $format ) {
	add_action( $hook, function () use ( $podcast, $feed, $format ) {
		global $post;

		$episode  = \Podlove\Model\Episode::find_one_by_post_id( $post->ID );
		$location = $feed->media_location();
		$file     = \Podlove\Model\MediaFile::find_by_episode_id_and_media_location_id( $episode->id, $location->id );

		if ( ! $file )
			return;

		$enclosure_duration  = $episode->duration;
		$enclosure_file_size = $file->size;
		$file_slug           = $episode->slug;
		$cover_art_url       = $episode->cover_art;

		// fall back to podcast cover image
		if ( ! $cover_art_url ) {
			$cover_art_url = $podcast->cover_image;
		}

		$enclosure_url = $episode->enclosure_url( $feed->media_location() );
		
		echo apply_filters( 'podlove_feed_enclosure', '', $enclosure_url, $enclosure_file_size, $format->mime_type );

		$duration = sprintf( '<itunes:duration>%s</itunes:duration>', $enclosure_duration );
		echo apply_filters( 'podlove_feed_itunes_duration', $duration );

		$author = sprintf( '<itunes:author>%s</itunes:author>', $podcast->author_name );
		echo apply_filters( 'podlove_feed_itunes_author', $author );

		$summary = sprintf( '<itunes:summary>%s</itunes:summary>', htmlspecialchars( strip_tags( $post->post_excerpt ) ) );
		echo apply_filters( 'podlove_feed_itunes_summary', $summary );

		if ( $cover_art_url ) {
			$cover_art = sprintf( '<itunes:image href="%s" />', $cover_art_url );
		} else {
			$cover_art = '';
		}
		echo apply_filters( 'podlove_feed_itunes_image', $cover_art );
	} );
}
