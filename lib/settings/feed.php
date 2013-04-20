<?php
namespace Podlove\Settings;

class Feed {

	static $pagehook;
	
	public function __construct( $handle ) {
		
		self::$pagehook = add_submenu_page(
			/* $parent_slug*/ $handle,
			/* $page_title */ 'Podcast Feeds',
			/* $menu_title */ 'Podcast Feeds',
			/* $capability */ 'administrator',
			/* $menu_slug  */ 'podlove_feeds_settings_handle',
			/* $function   */ array( $this, 'page' )
		);
		add_action( 'admin_init', array( $this, 'process_form' ) );
	}

	public static function get_action_link( $feed, $title, $action = 'edit', $type = 'link' ) {
		return sprintf(
			'<a href="?page=%s&action=%s&feed=%s"%s>' . $title . '</a>',
			$_REQUEST['page'],
			$action,
			$feed->id,
			$type == 'button' ? ' class="button"' : ''
		);
	}
	
	/**
	 * Process form: save/update a format
	 */
	private function save() {
		if ( ! isset( $_REQUEST['feed'] ) )
			return;
			
		$feed = \Podlove\Model\Feed::find_by_id( $_REQUEST['feed'] );
		$feed->update_attributes( $_POST['podlove_feed'] );
		
		$this->redirect( 'index', $feed->id );
	}
	
	/**
	 * Process form: create a format
	 */
	private function create() {
		global $wpdb;
		
		$feed = new \Podlove\Model\Feed;
		$feed->update_attributes( $_POST['podlove_feed'] );

		$this->redirect( 'index' );
	}
	
	/**
	 * Process form: delete a format
	 */
	private function delete() {
		if ( ! isset( $_REQUEST['feed'] ) )
			return;

		\Podlove\Model\Feed::find_by_id( $_REQUEST['feed'] )->delete();
		
		$this->redirect( 'index' );
	}
	
	/**
	 * Helper method: redirect to a certain page.
	 */
	private function redirect( $action, $feed_id = NULL ) {
		$page   = 'admin.php?page=' . $_REQUEST['page'];
		$show   = ( $feed_id ) ? '&feed=' . $feed_id : '';
		$action = '&action=' . $action;
		
		wp_redirect( admin_url( $page . $show . $action ) );
		exit;
	}

	public function process_form() {

		if ( ! isset( $_REQUEST['feed'] ) )
			return;

		$action = ( isset( $_REQUEST['action'] ) ) ? $_REQUEST['action'] : NULL;

		set_transient( 'podlove_needs_to_flush_rewrite_rules', true );
		
		if ( $action === 'save' ) {
			$this->save();
		} elseif ( $action === 'create' ) {
			$this->create();
		} elseif ( $action === 'delete' ) {
			$this->delete();
		}
	}
	
	public function page() {

		$action = isset( $_REQUEST['action'] ) ? $_REQUEST['action'] : NULL;

		if ( $action == 'confirm_delete' && isset( $_REQUEST['feed'] ) ) {
			?>
			<div class="updated">
				<p>
					<strong>
						<?php echo __( 'Are you sure you want do delete this feed?', 'podlove' ) ?>
					</strong>
				</p>
				<p>
					<?php echo __( 'Clients subscribing to this feed will no longer receive updates. If you are moving your feed, you must inform your subscribers.', 'podlove' ) ?>
				</p>
				<p>
					<?php echo self::get_action_link( \Podlove\Model\Feed::find_by_id( (int) $_REQUEST['feed'] ), __( 'Delete permanently', 'podlove' ), 'delete', 'button' ) ?>
				</p>
			</div>
			<?php
		}
		?>
		<div class="wrap">
			<?php screen_icon( 'podlove-podcast' ); ?>
			<h2><?php echo __( 'Feeds', 'podlove' ); ?> <a href="?page=<?php echo $_REQUEST['page']; ?>&amp;action=new" class="add-new-h2"><?php echo __( 'Add New', 'podlove' ); ?></a></h2>
			<?php
			
			switch ( $action ) {
				case 'new':   $this->new_template();  break;
				case 'edit':  $this->edit_template(); break;
				case 'index': $this->view_template(); break;
				default:      $this->view_template(); break;
			}
			?>
		</div>	
		<?php
	}
	
	private function new_template() {
		$feed = new \Podlove\Model\Feed;
		?>
		<h3><?php echo __( 'Add New Feed', 'podlove' ); ?></h3>
		<?php
		$this->form_template( $feed, 'create', __( 'Add New Feed', 'podlove' ) );
	}
	
	private function view_template() {
		$table = new \Podlove\Feed_List_Table();
		$table->prepare_items();
		$table->display();
	}
	
	private function form_template( $feed, $action, $button_text = NULL ) {

		$form_args = array(
			'context' => 'podlove_feed',
			'hidden'  => array(
				'feed' => $feed->id,
				'action' => $action
			)
		);

		\Podlove\Form\build_for( $feed, $form_args, function ( $form ) {
			$wrapper = new \Podlove\Form\Input\TableWrapper( $form );

			$feed = $form->object;

			$episode_assets = \Podlove\Model\EpisodeAsset::all();
			$assets = array();
			foreach ( $episode_assets as $asset ) {
				$assets[ $asset->id ] = $asset->title;
			}

			$wrapper->subheader( __( 'Basic Settings', 'podlove' ) );

			$wrapper->select( 'episode_asset_id', array(
				'label'       => __( 'Episode Media File', 'podlove' ),
				'options'     => $assets,
				'html'        => array( 'class' => 'required' )
			) );

			$wrapper->string( 'name', array(
				'label'       => __( 'Feed Title', 'podlove' ),
				'description' => __( 'Some podcast clients may display this title to describe the feed content.', 'podlove' ),
				'html' => array( 'class' => 'regular-text required' )
			) );

			$wrapper->string( 'slug', array(
				'label'       => __( 'Slug', 'podlove' ),
				'description' => ( $feed ) ? sprintf( __( 'Feed identifier. URL Preview: %s', 'podlove' ), '<span id="feed_subscribe_url_preview">' . $feed->get_subscribe_url() .  '</span>' ) : '',
				'html'        => array( 'class' => 'regular-text required' )
			) );

			$wrapper->checkbox( 'discoverable', array(
				'label'       => __( 'Discoverable?', 'podlove' ),
				'description' => __( 'Embed a meta tag into the head of your site so browsers and feed readers will find the link to the feed.', 'podlove' ),
				'default'     => true
			) );

			$wrapper->subheader( __( 'Directory Settings', 'podlove' ) );
			
			$wrapper->checkbox( 'enable', array(
				'label'       => __( 'Allow Submission to Directories', 'podlove' ),
				'description' => __( 'Allow this feed to appear in podcast directories.', 'podlove' ),
				'default'     => true
			) );
			
			$wrapper->string( 'itunes_feed_id', array(
				'label'       => __( 'iTunes Feed ID', 'podlove' ),
				'description' => __( 'Is used to generate a link to the iTunes directory.', 'podlove' ),
				'html'        => array( 'class' => 'regular-text' )
			) );

			$wrapper->subheader( __( 'Advanced Settings', 'podlove' ) );

			$wrapper->select( 'redirect_http_status', array(
				'label'       => __( 'Redirect Method', 'podlove' ),
				'description' => __( '', 'podlove' ),
				'options' => array(
					'0'   => 'Don\'t redirect', 
					'307' => 'Temporary Redirect (HTTP Status 307)',
					'301' => 'Permanent Redirect (HTTP Status 301)'
				),
				'default' => 0,
				'please_choose' => false
			) );
			
			$wrapper->string( 'redirect_url', array(
				'label'       => __( 'Redirect Url', 'podlove' ),
				'description' => __( 'e.g. Feedburner URL', 'podlove' ),
				'html' => array( 'class' => 'regular-text' )
			) );

			$limit_options = array(
				'-1' => __( "No limit. Include all items.", 'podlove' ),
				'0'  => __( 'Use WordPress Default', 'podlove' ) . ' (' . get_option( 'posts_per_rss' ) . ')'
			);
			for( $i = 1; $i*5 <= 100; $i++ ) {
				$limit_options[ $i*5 ] = $i*5;
			}

			$wrapper->select( 'limit_items', array(
				'label'       => __( 'Limit Items', 'podlove' ),
				'description' => __( 'If you have a lot of episodes, you might want to restrict the feed size.', 'podlove' ),
				'options' => $limit_options,
				'please_choose' => false,
				'default' => '-1'
			) );
			
			$wrapper->checkbox( 'embed_content_encoded', array(
				'label'       => __( 'Include HTML Content', 'podlove' ),
				'description' => __( 'Warning: Potentially creates huge feeds.', 'podlove' ),
				'default'     => false
			) );
		} );
	}
	
	private function edit_template() {
		$feed = \Podlove\Model\Feed::find_by_id( $_REQUEST['feed'] );
		echo '<h3>' . sprintf( __( 'Edit Feed: %s', 'podlove' ), $feed->name ) . '</h3>';
		$this->form_template( $feed, 'save' );
	}
	
}