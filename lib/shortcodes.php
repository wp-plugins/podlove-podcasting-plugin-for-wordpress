<?php
namespace Podlove;
use \Podlove\Model;

function handle_direct_download() {
	
	if ( ! isset( $_GET['download_media_file'] ) )
		return;

	// tell WP Super Cache to not cache download links
	if ( ! defined( 'DONOTCACHEPAGE' ) )
		define( 'DONOTCACHEPAGE', true );

	$media_file_id = (int) $_GET['download_media_file'];
	$media_file    = Model\MediaFile::find_by_id( $media_file_id );

	if ( ! $media_file ) {
		status_header( 404 );
		exit;
	}

	$episode_asset = $media_file->episode_asset();

	if ( ! $episode_asset || ! $episode_asset->downloadable ) {
		status_header( 404 );
		exit;
	}

	header( "Expires: 0" );
	header( 'Cache-Control: must-revalidate' );
    header( 'Pragma: public' );
	header( "Content-Type: application/force-download" );
	header( "Content-Description: File Transfer" );
	header( "Content-Disposition: attachment; filename=" . $media_file->get_download_file_name() );
	header( "Content-Transfer-Encoding: binary" );

	if ( $media_file->size > 0 )
		header( 'Content-Length: ' . $media_file->size );
	
	ob_clean();
	flush();
	readfile( $media_file->get_file_url() );
	exit;
}
add_action( 'init', '\Podlove\handle_direct_download' );

/**
 * Provides a shortcode to display all available download links.
 *
 * Usage:
 *	[podlove-episode-downloads]
 * 
 * @param  array $options
 * @return string
 */
function episode_downloads_shortcode( $options ) {
	global $post;

	if ( is_feed() )
		return '';

	$episode = Model\Episode::find_or_create_by_post_id( $post->ID );
	$media_files = $episode->media_files();
	$downloads = array();

	foreach ( $media_files as $media_file ) {

		$episode_asset = $media_file->episode_asset();

		if ( ! $episode_asset->downloadable )
			continue;

		$file_type = $episode_asset->file_type();
		
		$download_link_url  = get_bloginfo( 'url' ) . '?download_media_file=' . $media_file->id;
		$download_link_name = str_replace( " ", "&nbsp;", $episode_asset->title );

		$downloads[] = array(
			'url'  => $download_link_url,
			'name' => $download_link_name,
			'size' => \Podlove\format_bytes( $media_file->size, 0 ),
			'file' => $media_file
		);
	}

	$html  = '<ul class="episode_download_list">';
	foreach ( $downloads as $download ) {
		$html .= '  <li>';
		$html .= sprintf(
			'<a href="%s">%s%s</a>',
			apply_filters( 'podlove_download_link_url', $download['url'], $download['file'] ),
			apply_filters( 'podlove_download_link_name', $download['name'], $download['file'] ),
			'<span class="size">' . $download['size'] . '</span>'
		);
		$html .= '  </li>';
	}
	$html .= '</ul>';

	return apply_filters( 'podlove_downloads_before', '' )
	     . $html
	     . apply_filters( 'podlove_downloads_after', '' );
}
add_shortcode( 'podlove-episode-downloads', '\Podlove\episode_downloads_shortcode' );

/**
 * Provides shortcode to display web player.
 *
 * Right now there is only audio support.
 *
 * Usage:
 * 	[podlove-web-player]
 * 	
 * @param  array $options
 * @return string
 */
function webplayer_shortcode( $options ) {
	global $post;

	$episode = Model\Episode::find_or_create_by_post_id( $post->ID );
	$podcast = Model\Podcast::get_instance();
	$asset_assignment = Model\AssetAssignment::get_instance();

	$formats_data = get_option( 'podlove_webplayer_formats' );

	if ( ! count( $formats_data ) )
		return;

	$available_formats = array();
	$audio_formats = array( 'mp3', 'mp4', 'ogg' );

	foreach ( $audio_formats as $audio_format ) {
		$episode_asset = Model\EpisodeAsset::find_by_id( $formats_data['audio'][ $audio_format ] );

		if ( ! $episode_asset )
			continue;

		$media_file = Model\MediaFile::find_by_episode_id_and_episode_asset_id( $episode->id, $episode_asset->id );

		if ( $media_file )
			$available_formats[] = sprintf( '%s="%s"', $audio_format, $media_file->get_file_url() );
	}

	$chapters = '';
	if ( $asset_assignment->chapters === 'manual' && $episode->chapters ) {
		$chapters = 'chapters="_podlove_chapters"';
	} elseif ( $asset_assignment->chapters > 0 ) {
		$chapter_asset = Model\EpisodeAsset::find_by_id( $asset_assignment->chapters );
		$media_file = Model\MediaFile::find_by_episode_id_and_episode_asset_id( $episode->id, $chapter_asset->id );
		if ( $media_file ) {
			$chapters = 'chapters="' . $media_file->get_file_url() . '"';
		}
	}

	$attributes = array(
		'permalink'  => get_permalink(),
		'title'      => get_the_title(),
		'subtitle'   => $episode->subtitle,
		'summary'    => $episode->summary,
		'poster'     => $episode->get_cover_art()
	);
	$attr_string = '';
	foreach ( $attributes as $key => $value ) {
		$attr_string .= "$key=\"$value\" ";
	}

	return do_shortcode( '[podloveaudio ' . implode( ' ', $available_formats ) . ' ' . $chapters . ' ' . $attr_string . ']' );
}
add_shortcode( 'podlove-web-player', '\Podlove\webplayer_shortcode' );

/**
 * @deprecated since 1.2.18-alpha
 */
$podlove_public_episode_attributes = array( 'subtitle', 'summary', 'slug', 'duration', 'chapters' );
foreach ( $podlove_public_episode_attributes as $attr ) {
	add_shortcode( 'podlove-episode-' . $attr, function() use ( $attr ) {
		global $post;
		return nl2br( Model\Episode::find_or_create_by_post_id( $post->ID )->$attr );
	} );
}

function episode_data_shortcode( $attributes ) {
	global $post;

	$defaults = array( 'field' => '', 'format' => 'HH:MM:SS' );
	$attributes = shortcode_atts( $defaults, $attributes );

	$episode = Model\Episode::find_or_create_by_post_id( $post->ID );
	if ( ! $episode )
		return;

	$allowed_fields = array( 'subtitle', 'summary', 'slug', 'chapters' );

	if ( in_array( $attributes['field'], $allowed_fields ) ) {
		return nl2br( $episode->$attributes['field'] );
	} elseif ( $attributes['field'] == 'image' ) {
		return $episode->get_cover_art();
	} elseif ( $attributes['field'] == 'duration' ) {
		return $episode->get_duration( $attributes['format'] );
	} else {
		return sprintf( __( 'Podlove Error: Unknown episode field "%s"', 'podcast' ), $attributes['field'] );
	}
}
add_shortcode( 'podlove-episode', '\Podlove\episode_data_shortcode' );

function podcast_data_shortcode( $attributes ) {

	$defaults = array( 'field' => '' );
	$attributes = shortcode_atts( $defaults, $attributes );

	$podcast = Model\Podcast::get_instance();

	if ( $podcast->has_property( $attributes['field'] ) ) {
		return $podcast->$attributes['field'];
	} else {
		return sprintf( __( 'Podlove Error: Unknown podcast field "%s"', 'podcast' ), $attributes['field'] );
	}
}
add_shortcode( 'podlove-podcast', '\Podlove\podcast_data_shortcode' );
add_shortcode( 'podlove-show', '\Podlove\podcast_data_shortcode' );

/**
 * Provides shortcode to display episode template.
 *
 * Usage:
 * 	
 * 	[podlove-template title="Template Title"]
 *
 * 	Parameters:
 * 		title: (required) Title of template to render.
 * 		autop: (optional) Wraps blocks of text in p tags. 'yes' or 'no'. Default: 'yes'
 * 	
 * @param  array $attributes
 * @return string
 */
function template_shortcode( $attributes ) {

	$defaults = array(
		'title' => '',
		'autop' => 'yes'
	);

	$attributes = shortcode_atts( $defaults, $attributes );

	if ( ! $template = Model\Template::find_one_by_title( $attributes['title'] ) )
		return sprintf( __( 'Podlove Error: Whoops, there is no template called "%s"', 'podlove' ), $attributes['title'] );

	$html = $template->content;

	if ( in_array( $attributes['autop'], array('yes', 1, 'true') ) )
		$html = wpautop( $html );

	$html = do_shortcode( $html );

	return $html;
}
add_shortcode( 'podlove-template', '\Podlove\template_shortcode' );
