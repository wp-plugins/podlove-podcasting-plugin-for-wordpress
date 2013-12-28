<?php
namespace Podlove\Settings;
use \Podlove\Model;

class Dashboard {

	static $pagehook;

	public function __construct() {

		// use \Podlove\Podcast_Post_Type::SETTINGS_PAGE_HANDLE to replace
		// default first item name
		Dashboard::$pagehook = add_submenu_page(
			/* $parent_slug*/ \Podlove\Podcast_Post_Type::SETTINGS_PAGE_HANDLE,
			/* $page_title */ __( 'Dashboard', 'podlove' ),
			/* $menu_title */ __( 'Dashboard', 'podlove' ),
			/* $capability */ 'administrator',
			/* $menu_slug  */ \Podlove\Podcast_Post_Type::SETTINGS_PAGE_HANDLE,
			/* $function   */ array( $this, 'settings_page' )
		);

		add_action( Dashboard::$pagehook, function () {

			wp_enqueue_script( 'postbox' );
			add_screen_option( 'layout_columns', array(
				'max' => 2, 'default' => 2
			) );

			wp_register_script(
				'cornify-js',
				\Podlove\PLUGIN_URL . '/js/admin/cornify.js'
			);
			wp_enqueue_script( 'cornify-js' );
		} );
	}

	public static function about_meta() {
		?>
		<ul>
			<li>
				<a href="<?php echo admin_url( 'admin.php?page=podlove_Support_settings_handle' ) ?>">Report Bugs</a>
			</li>
			<li>
				<a target="_blank" href="https://trello.com/board/podlove-publisher/508293f65573fa3f62004e0a">See what we're working on</a>
			</li>
			<li>
				<script type="text/javascript">
				/* <![CDATA[ */
				    (function() {
				        var s = document.createElement('script'), t = document.getElementsByTagName('script')[0];
				        s.type = 'text/javascript';
				        s.async = true;
				        s.src = 'http://api.flattr.com/js/0.6/load.js?mode=auto';
				        t.parentNode.insertBefore(s, t);
				    })();
				/* ]]> */</script>
				<a class="FlattrButton" style="display:none;" rev="flattr;button:compact;" href="http://wordpress.org/extend/plugins/podlove-podcasting-plugin-for-wordpress/"></a>
				<a href="http://www.cornify.com" onclick="cornify_add();return false;" style="text-decoration: none; color: #A7A7A7; float: right; font-size: 20px; line-height: 20px;"><i class="podlove-icon-heart"></i></a>
				<noscript><a href="http://flattr.com/thing/728463/Podlove-Podcasting-Plugin-for-WordPress" target="_blank">
				<img src="http://api.flattr.com/button/flattr-badge-large.png" alt="Flattr this" title="Flattr this" border="0" /></a></noscript>
			</li>
		</ul>
		<?php
	}

	public static function settings_page() {
		add_meta_box( Dashboard::$pagehook . '_about', __( 'About', 'podlove' ), '\Podlove\Settings\Dashboard::about_meta', Dashboard::$pagehook, 'side' );		
		add_meta_box( Dashboard::$pagehook . '_statistics', __( 'At a glance', 'podlove' ), '\Podlove\Settings\Dashboard::statistics', Dashboard::$pagehook, 'normal' );
		add_meta_box( Dashboard::$pagehook . '_validation', __( 'Validate Podcast Files', 'podlove' ), '\Podlove\Settings\Dashboard::validate_podcast_files', Dashboard::$pagehook, 'normal' );

		do_action( 'podlove_dashboard_meta_boxes' );

		?>
		<div class="wrap">
			<?php screen_icon( 'podlove-podcast' ); ?>
			<h2><?php echo __( 'Podlove Dashboard', 'podlove' ); ?></h2>

			<div id="poststuff" class="metabox-holder has-right-sidebar">
				
				<!-- sidebar -->
				<div id="side-info-column" class="inner-sidebar">
					<?php do_action( 'podlove_settings_before_sidebar_boxes' ); ?>
					<?php do_meta_boxes( Dashboard::$pagehook, 'side', NULL ); ?>
					<?php do_action( 'podlove_settings_after_sidebar_boxes' ); ?>
				</div>

				<!-- main -->
				<div id="post-body" class="has-sidebar">
					<div id="post-body-content" class="has-sidebar-content">
						<?php do_action( 'podlove_settings_before_main_boxes' ); ?>
						<?php do_meta_boxes( Dashboard::$pagehook, 'normal', NULL ); ?>
						<?php do_meta_boxes( Dashboard::$pagehook, 'additional', NULL ); ?>
						<?php do_action( 'podlove_settings_after_main_boxes' ); ?>						
					</div>
				</div>

				<br class="clear"/>

			</div>

			<!-- Stuff for opening / closing metaboxes -->
			<script type="text/javascript">
			jQuery( document ).ready( function( $ ){
				// close postboxes that should be closed
				$( '.if-js-closed' ).removeClass( 'if-js-closed' ).addClass( 'closed' );
				// postboxes setup
				postboxes.add_postbox_toggles( '<?php echo \Podlove\Podcast_Post_Type::SETTINGS_PAGE_HANDLE; ?>' );
			} );
			</script>

			<form style='display: none' method='get' action=''>
				<?php
				wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false );
				wp_nonce_field( 'meta-box-order', 'meta-box-order-nonce', false );
				?>
			</form>

		</div>
		<?php
	}

	/**
	 * Look for errors in podcast settings.
	 * 
	 * @return array list of error messages
	 */
	public static function get_podcast_setting_warnings() {
		
		$warnings = array();
		$podcast = Model\Podcast::get_instance();

		$required_attributes = array(
			'title'               => __( 'Your podcast needs a title.', 'podlove' ),
			'media_file_base_uri' => __( 'Your podcast needs a base URL for file storage.', 'podlove' ),
		);
		$required_attributes = apply_filters( 'podlove_podcast_required_attributes', $required_attributes );

		foreach ( $required_attributes as $attribute => $error_text ) {
			if ( ! $podcast->$attribute )
				$warnings[] = $error_text;
		}

		return $warnings;
	}

	public static function duration_to_seconds( $timestring ) {
		$time 		= strtotime($timestring);
		$seconds    = date( "s", $time);
		$minutes    = date( "i", $time);
		$hours	    = date( "H", $time);

		return $seconds + $minutes * 60 + $hours * 3600;
	}

	public static function statistics() {

		$episodes     = Model\Episode::all();
		$media_files  = Model\MediaFile::all();

		$episode_edit_url = site_url() . '/wp-admin/edit.php?post_type=podcast';

		// For Episodes, status and length will be calculated

		$episodes_published 			  = 0;
		$episodes_draft					  = 0;
		$episodes_future				  = 0;
		$episodes_private				  = 0;

		$episodes_total_length	 		  = 0;

		$episodes_days_until_next_release = 0;

		$counted_episodes 		 		  = 0;
		$counted_episodes_length 		  = 0;

		// For Media Files the total and average file size will be calculated

		$mediafile_total_size 		 	  = 0;
		$mediafile_counted 	  			  = 0;

		/*
         *	Episode Statistics
		 */

		foreach ( $episodes as $episode_key => $episode ) {

			$post = get_post( $episode->post_id );
			// Setting the first episode to 0 -> neglected (as there is no reference episode)
			$next_episode = ( $episode_key > 0 ? $episodes[$episode_key - 1] : $episodes[$episode_key] );
			$next_post = get_post( $next_episode->post_id );

			// Average Episode length
			$episodes_total_length = $episodes_total_length + self::duration_to_seconds( $episode->duration );
			if ( self::duration_to_seconds( $episode->duration ) > 0 ) // Only count episodes with a larger episode duration than 0 
				$counted_episodes_length++;

			// Count Episode status (neglect trash and inherit)
			switch ( $post->post_status ) {
				case 'publish' :
					$episodes_published++;
					$counted_episodes++;
				break;
				case 'draft' :
					$episodes_draft++;
					$counted_episodes++;
				break;
				case 'future' :
					$episodes_future++;
					$counted_episodes++;
				break;
				case 'private' :
					$episodes_private++;
					$counted_episodes++;
				break;
			}

			// Calculate time between release of current and next episode
			$timestamp_current_episode = new \DateTime( $post->post_date );
			$timestamp_next_episode = new \DateTime( $next_post->post_date );
			$time_stamp_difference = $timestamp_current_episode->diff($timestamp_next_episode);

			if ( $time_stamp_difference->days > 0 ) // Filter first episode, as no reference episode is available here!
				$episodes_days_until_next_release = $episodes_days_until_next_release + $time_stamp_difference->days;			
		}

		// Calculating average episode in seconds
		$episodes_average_episode_length = ( $counted_episodes_length > 0 ? round( $episodes_total_length / $counted_episodes_length ) : 0 );
		// Calculate average tim until next release in days
		$episodes_days_until_next_release = ( $counted_episodes > 0 ? round( $episodes_days_until_next_release / $counted_episodes ) : 0 );

		/*
         *	Media Files
		 */
		foreach ( $media_files as $media_file_key => $media_file) {
			if ( $media_file->size <= 0 ) // Neglect empty files
				continue;

			$mediafile_total_size = $mediafile_total_size + $media_file->size;
			$mediafile_counted++;
		}

		$formated_mediafile_average_size = ( $mediafile_counted > 0 ? $mediafile_total_size / $mediafile_counted / 1000000 : 0 ); // [Megabyte]
		$formated_mediafile_total_size = $mediafile_total_size / 1000000000; // [Gigabyte]

		?>
			<div class="podlove-dashboard-statistics-wrapper">
				<h4>Episodes</h4>
				<table cellspacing="0" cellpadding="0" class="podlove-dashboard-statistics">
					<tr>
						<td class="podlove-dashboard-number-column">
							<a href="<?php echo $episode_edit_url; ?>&amp;post_status=publish"><?php echo $episodes_published; ?></a>
						</td>
						<td>
							<span style="color: #2c6e36;"><?php echo __( 'Published', 'podlove' ); ?></span>
						</td>
					</tr>
					<tr>
						<td class="podlove-dashboard-number-column">
							<a href="<?php echo $episode_edit_url; ?>&amp;post_status=private"><?php echo $episodes_private; ?></a>
						</td>
						<td>
							<span style="color: #b43f56;"><?php echo __( 'Private', 'podlove' ); ?></span>
						</td>
					</tr>
					<tr>
						<td class="podlove-dashboard-number-column">
							<a href="<?php echo $episode_edit_url; ?>&amp;post_status=future"><?php echo $episodes_future; ?></a>
						</td>
						<td>
							<span style="color: #a8a8a8;"><?php echo __( 'To be published', 'podlove' ); ?></span>
						</td>
					</tr>
					<tr>
						<td class="podlove-dashboard-number-column">
							<a href="<?php echo $episode_edit_url; ?>&amp;post_status=draft"><?php echo $episodes_draft; ?></a>
						</td>
						<td>
							<span style="color: #c0844c;"><?php echo __( 'Drafts', 'podlove' ); ?></span>
						</td>
					</tr>
					<tr>
						<td class="podlove-dashboard-number-column podlove-dashboard-total-number">
							<a href="<?php echo $episode_edit_url; ?>"><?php echo $counted_episodes; ?></a>
						</td>
						<td class="podlove-dashboard-total-number">
							<?php echo __( 'Total', 'podlove' ); ?>
						</td>
					</tr>
				</table>
			</div>
			<div class="podlove-dashboard-statistics-wrapper">
				<h4>Statistics</h4>
				<table cellspacing="0" cellpadding="0" class="podlove-dashboard-statistics">
					<tr>
						<td class="podlove-dashboard-number-column">
							<?php echo gmdate("H:i:s", $episodes_average_episode_length ); ?>
						</td>
						<td>
							<?php echo __( 'is the average length of an episode', 'podlove' ); ?>.
						</td>
					</tr>
					<tr>
						<td class="podlove-dashboard-number-column">
							<?php 
								// Cut after the first decimal place [Days]
								echo substr( $episodes_total_length / 3600 / 24, 0, strpos( $episodes_total_length / 3600 / 24, "." ) + 2 );
							?>
						</td>
						<td>
							<?php echo __( 'Days, is the total playback time of all episodes', 'podlove' ); ?>.
						</td>
					</tr>
					<tr>
						<td class="podlove-dashboard-number-column">
							<?php 
								// Cut after the first decimal place [Megabyte]
								echo substr( $formated_mediafile_average_size , 0, strpos($formated_mediafile_average_size, "." ) + 2 ); 
							?>
						</td>
						<td>
							<?php echo __( 'Megabyte is the average media file size', 'podlove' ); ?>.
						</td>
					</tr>
					<tr>
						<td class="podlove-dashboard-number-column">
							<?php
								// Cut after the first decimal place [Gigabyte]
								echo substr( $formated_mediafile_total_size , 0, strpos($formated_mediafile_total_size, "." ) + 2 ); 
							?>
						</td>
						<td>
							<?php echo __( 'Gigabyte is the total media file size', 'podlove' ); ?>.
						</td>
					</tr>
					<tr>
						<td class="podlove-dashboard-number-column">
							<?php echo $episodes_days_until_next_release; ?>
						</td>
						<td>
							<?php echo __( 'Days, is the average interval until a new episode is released', 'podlove' ); ?>.
						</td>
					</tr>
					<?php do_action('podlove_dashboard_statistics'); ?>
				</table>
			</div>
			<p>
				<?php echo sprintf( __('You are using %s', 'podlove'), '<strong>Podlove Publisher ' . \Podlove\get_plugin_header( 'Version' ) . '</strong>'); ?>.
			</p>
		<?php
	}

	public static function validate_podcast_files() {
		
		$podcast = Model\Podcast::get_instance();
		?>
		<div id="validation">
			<?php
			$episodes = Model\Episode::all( 'ORDER BY slug DESC' );
			$assets   = Model\EpisodeAsset::all();

			$header = array( __( 'Episode', 'podlove' ) );
			foreach ( $assets as $asset ) {
				$header[] = $asset->title;
			}
			$header[] = __( 'Status', 'podlove' );

			define( 'ASSET_STATUS_OK', '<i class="clickable podlove-icon-ok"></i>' );
			define( 'ASSET_STATUS_INACTIVE', '<i class="podlove-icon-minus"></i>' );
			define( 'ASSET_STATUS_ERROR', '<i class="clickable podlove-icon-remove"></i>' );
			?>

			<h4><?php echo $podcast->title ?></h4>

			<input id="revalidate_assets" type="button" class="button button-primary" value="<?php echo __( 'Revalidate Assets', 'podlove' ); ?>">

			<table id="asset_status_dashboard">
				<thead>
					<tr>
						<?php foreach ( $header as $column_head ): ?>
							<th><?php echo $column_head ?></th>
						<?php endforeach; ?>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $episodes as $episode ): ?>
						<?php 
						$post_id = $episode->post_id;
						$post = get_post( $post_id );

						if ( ! $episode || ! $episode->is_valid() )
							continue;
						?>
						<tr>
							<td>
								<a href="<?php echo get_edit_post_link( $episode->post_id ) ?>"><?php echo $episode->slug ?></a>
							</td>
							<?php $media_files = $episode->media_files(); ?>
							<?php foreach ( $assets as $asset ): ?>
								<?php 
								$files = array_filter( $media_files, function ( $file ) use ( $asset ) {
									return $file->episode_asset_id == $asset->id;
								} );
								$file = array_pop( $files );
								?>
								<td style="text-align: center; font-weight: bold; font-size: 20px" data-media-file-id="<?php echo $file ? $file->id : '' ?>">
									<?php
									if ( ! $file ) {
										echo ASSET_STATUS_INACTIVE;
									} elseif ( $file->size > 0 ) {
										echo ASSET_STATUS_OK;
									} else {
										echo ASSET_STATUS_ERROR;
									}
									?>
								</td>
							<?php endforeach; ?>
							<td>
								<?php echo $post->post_status ?>
							</td>
							<!-- <td>buttons</td> -->
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>

		<style type="text/css">
		#validation h4 {
			font-size: 20px;
		}

		#validation .episode {
			margin: 0 0 15px 0;
		}

		#validation .slug {
			font-size: 18px;
			margin: 0 0 5px 0;
		}

		#validation .warning {
			color: maroon;
		}

		#validation .error {
			color: red;
		}
		</style>
		<?php
	}

}
