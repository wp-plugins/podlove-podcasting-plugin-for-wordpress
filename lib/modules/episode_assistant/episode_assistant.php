<?php
namespace Podlove\Modules\EpisodeAssistant;

class Episode_Assistant extends \Podlove\Modules\Base {

	protected $module_name = 'Episode Assistant';
	protected $module_description = <<<EOT
Adds more conventions to episodes and uses them to automate the episode creation process.
<ul style="list-style-type: disc; margin-left: 50px">
  <li>introduces episode numbers</li>
  <li>guesses next episode number for new episodes</li>
  <li>configurable episode title format</li>
</ul>
EOT;

	public function load() {

		$this->register_option( 'title_template', 'string', array(
			'label'       => \Podlove\t( 'Title Template' ),
			'description' => \Podlove\t( 'Placeholders: %show_slug%, %episode_number%, %episode_title%' ),
			'default'     => '%show_slug%%episode_number% %episode_title%',
			'html'        => array( 'class' => 'regular-text' )
		) );

		$this->register_option( 'leading_zeros', 'select', array(
			'label'       => \Podlove\t( 'Number Digits' ),
			'description' => \Podlove\t( 'Add leading zeroes to episode number. Example: 003 instead of 3.' ),
			'default'     => 3,
			'options'     => array( 'no' => 'no', 1 => 1, 2 => 2, 3 => 3, 4 => 4, 5 => 5 )
		) );

		if ( is_admin() ) {
			add_action( 'admin_enqueue_scripts', array( $this, 'register_assets' ) );
			add_action( 'admin_footer', array( $this, 'modal_box_html' ) );
		}
	}

	public function register_assets() {
		wp_register_script(
			'podlove_module_episode_assistant',
			$this->get_module_url() . '/js/episode_assistant.js',
			array( 'jquery', 'jquery-ui-core', 'jquery-ui-button', 'jquery-ui-dialog' ),
			'1.1' 
		);

		// see http://www.arashkarimzadeh.com/jquery/7-editable-jquery-plugin.html
		wp_register_script(
			'jquery-editable',
			$this->get_module_url() . '/js/jquery.editable-1.3.3.min.js',
			array( 'jquery' ),
			'1.3.3'
		);

		wp_enqueue_script( 'jquery-editable' );
		wp_enqueue_script( 'podlove_module_episode_assistant' );

		// TODO: not sure if we should bundle our own theme
		wp_register_style( 'jquery-style', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.1/themes/smoothness/jquery-ui.css', true);
		wp_enqueue_style( 'jquery-style' );
	}

	public function guess_next_episode_number_for_show( $show_id ) {
		$show = \Podlove\Model\Show::find_by_id( $show_id );

		if ( ! $show )
			return;

		$releases = array_filter( $show->releases(), function ( $r ) {
			return strlen( $r->slug ) > 0;
		} );

		if ( ! count( $releases ) ) {
			$number = 1;
		} else {
			// support shows beginning with episode 0
			// first show includes a "1"? then the first number was 1, not 0
			$add = strpos( $releases[0]->slug, '1' ) ? 1 : 0;
			$number = (string) ( count( $releases ) + $add );			
		}

		$leading_zeros = $this->get_module_option( 'leading_zeros', 3 );
		if ( $leading_zeros !== 'no' ) {
			while ( strlen( $number ) < $leading_zeros ) {
				$number = "0$number";
			}
		}

		return $number;
	}

	public function modal_box_html() {
		$shows = \Podlove\Model\Show::all();

		if ( ! $shows )
			return;

		$shows_data = array();
		foreach ( $shows as $s ) {
			$media_locations = $s->media_locations();

			if ( ! $media_locations )
				continue;

			$media_location  = $media_locations[0];

			$shows_data[ $s->id ] = array(
				'slug'        => $s->slug,
				'name'        => $s->name,
				'next_number' => $this->guess_next_episode_number_for_show( $s->id ),
				'base_url'    => $s->media_file_base_uri,
				'media_location' => array(
					'template' => $media_location->url_template,
					'suffix'   => $media_location->suffix
				)
			);
		}

		?>
		<div id="new-episode-modal" class="hidden wrap" title="Create New Episode">
			<div class="hidden" id="new-episode-shows-data"><?php echo json_encode( $shows_data ) ?></div>
			<p>
				<div id="titlediv">
					<p>
						<strong>Show</strong>
						<select name="new_episode_show" id="new_episode_show">
							<?php foreach ( $shows_data as $show_id => $show ): ?>
								<option value="<?php echo $show_id ?>"><?php echo $show['name'] ?></option>
							<?php endforeach ?>
						</select>
					</p>
					<p>
						<strong>Episode Number</strong>
						<input type="text" name="episode_number" value="" class="really-huge-text episode_number" autocomplete="off">
					</p>
					<p>
						<strong>Episode Title</strong>
						<input type="text" name="episode_title" value="" class="really-huge-text episode_title" autocomplete="off">
					</p>
					<p class="media_file_info result">
						<strong>Media Files</strong>
						<span class="url">Loading ...</span>
					</p>
					<p class="post_info result">
						<strong>Post Title</strong>
						<span class="post_title" data-template="<?php echo $this->get_module_option( 'title_template', '%show_slug%%number% %episode_title%' ) ?>">Loading ...</span>
					</p>
				</div>
			</p>
		</div>

		<style type="text/css">
		#new-episode-modal .media_file_info, #new-episode-modal .post_info {
			color: #666;
		}

		#new-episode-modal p.result strong {
			display: inline-block;
			width: 115px;
		}

		#episode_file_slug {
			cursor: pointer;
			font-style: italic;
			color: black;
		}

		#episode_file_slug input {
			width: 70px;
			-webkit-border-radius: 3px;
			border-radius: 3px;
			border-width: 1px;
			border-style: solid;
			border-color: #DFDFDF;
		}

		input.really-huge-text {
			padding: 3px 8px;
			font-size: 1.7em;
			line-height: 100%;
			width: 100%;
		}
		</style>
		<?php
	}

}