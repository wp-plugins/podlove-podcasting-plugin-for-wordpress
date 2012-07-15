<?php

namespace Podlove\Feeds;

function mute_feed_title() {
	add_filter( 'bloginfo_rss', function ( $value, $key ) {
		return apply_filters( 'podlove_feed_title_name', ( $key == 'name' ) ? '' : $value );
	}, 10, 2 );
}

function override_feed_title( $feed ) {
	add_filter( 'wp_title_rss', function ( $title ) use ( $feed ) {
		return apply_filters( 'podlove_feed_title', htmlspecialchars( $feed->title ) );
	} );
}

function override_feed_language( $feed ) {
	add_filter( 'pre_option_rss_language', function ( $language ) use ( $feed ) {
		return apply_filters( 'podlove_feed_language', ( $feed->language ) ? $feed->language : $language );
	} );
}

// todo: new line for each tag
// todo: hide tags without content
function override_feed_head( $hook, $show, $feed, $format ) {

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
	
	add_action( $hook, function () use ( $show, $feed, $format ) {
		$author = sprintf( '<itunes:author>%s</itunes:author>', $show->author_name );
		echo apply_filters( 'podlove_feed_itunes_author', $author );

		$summary = sprintf( '<itunes:summary>%s</itunes:summary>', $show->summary );
		echo apply_filters( 'podlove_feed_itunes_summary', $summary );

		$categories = \Podlove\Itunes\categories( false );	
		$category_html = '';
		for ( $i = 1; $i <= 3; $i++ ) { 
			$category_id = $show->{'category_' . $i};

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
			$show->owner_name,
			$show->owner_email
		);
		echo apply_filters( 'podlove_feed_itunes_owner', $owner );
		
		if ( $show->cover_image ) {
			$coverimage = sprintf( '<itunes:image href="%s" />', $show->cover_image );
		} else {
			$coverimage = '';
		}
		echo apply_filters( 'podlove_feed_itunes_image', $coverimage );

		$subtitle = sprintf( '<itunes:subtitle>%s</itunes:subtitle>', $show->subtitle );
		echo apply_filters( 'podlove_feed_itunes_subtitle', $subtitle );

		$keywords = sprintf( '<itunes:keywords>%s</itunes:keywords>', $show->keywords );
		echo apply_filters( 'podlove_feed_itunes_keywords', $keywords );

		$block = sprintf( '<itunes:block>%s</itunes:block>', ( $feed->enable ) ? 'no' : 'yes' );
		echo apply_filters( 'podlove_feed_itunes_block', $block );

        $explicit = sprintf( '<itunes:explicit>%s</itunes:explicit>', ( $show->explicit == 2) ? 'clean' : ( ( $show->explicit ) ? 'yes' : 'no' ) );
		echo apply_filters( 'podlove_feed_itunes_explicit', $explicit );
	} );
}

function override_feed_entry( $hook, $show, $feed, $format ) {
	add_action( $hook, function () use ( $show, $feed, $format ) {
		global $post;

		$episode  = \Podlove\Model\Episode::find_or_create_by_post_id( $post->ID );
		$release  = \Podlove\Model\Release::find_or_create_by_episode_id_and_show_id( $episode->id, $show->id );
		$location = \Podlove\Model\MediaLocation::find_by_show_id_and_media_format_id( $show->id, $format->id );
		$file     = \Podlove\Model\MediaFile::find_by_release_id_and_media_location_id( $release->id, $location->id );

		$enclosure_duration  = $release->duration;
		$enclosure_file_size = $file->size;
		$file_slug           = $release->slug;
		$cover_art_url       = $release->cover_art;

		// fall back to show cover image
		if ( ! $cover_art_url ) {
			$cover_art_url = $show->cover_image;
		}

		$enclosure_url = $release->enclosure_url( $show, $feed->media_location(), $format );
		
		echo apply_filters( 'podlove_feed_enclosure', '', $enclosure_url, $enclosure_file_size, $format->mime_type );

		$duration = sprintf( '<itunes:duration>%s</itunes:duration>', $enclosure_duration );
		echo apply_filters( 'podlove_feed_itunes_duration', $duration );

		$author = sprintf( '<itunes:author>%s</itunes:author>', $show->author_name );
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
