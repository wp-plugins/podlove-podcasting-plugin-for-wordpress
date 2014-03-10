<?php
namespace Podlove\Model;

class Feed extends Base {

	const ITEMS_WP_LIMIT = 0;
	const ITEMS_NO_LIMIT = -1;
	const ITEMS_GLOBAL_LIMIT = -2;

	// Define Icons for validation
	const FEED_VALIDATION_OK = '<i class="clickable podlove-icon-ok"></i>';
	const FEED_VALIDATION_INACTIVE = '<i class="podlove-icon-minus"></i>';
	const FEED_VALIDATION_ERROR = '<i class="clickable podlove-icon-remove"></i>';

	public function save() {
		global $wpdb;
		
		set_transient( 'podlove_needs_to_flush_rewrite_rules', true );
		$this->slug = \Podlove\slugify( $this->slug );

		if ( ! $this->position ) {
			$pos = $wpdb->get_var( sprintf( 'SELECT MAX(position)+1 FROM %s', self::table_name() ) );
			$this->position = $pos ? $pos : 1;
		}
		
		parent::save();
	}

	/**
	 * Build public url where the feed can be subscribed at.
	 *
	 * @return string
	 */
	public function get_subscribe_url() {

		$podcast = \Podlove\Model\Podcast::get_instance();

		if ( '' != get_option( 'permalink_structure' ) ) {
			$url = sprintf(
				'%s/feed/%s/',
				get_bloginfo( 'url' ),
				\Podlove\slugify( $this->slug )
			);
		} else {
			$url = get_feed_link( $this->slug );
		}


		return apply_filters( 'podlove_subscribe_url', $url );
	}

	/**
	 * Build html link to subscribe.
	 * 
	 * @return string
	 */
	public function get_subscribe_link() {
		$url = $this->get_subscribe_url();
		return sprintf( '<a href="%s">%s</a>', $url, $url );
	}

	/**
	 * Build the title of the feed
	 *
	 */
	public function get_title() {

		$podcast = Podcast::get_instance();

		if( $this->append_name_to_podcast_title )
			return $podcast->title . ' (' . $this->name . ')';

		return $podcast->title;

	}

	/**
	 * Get title for browser feed discovery.
	 *
	 * This title is used by clients to show the user the subscribe option he
	 * has. Therefore, the most obvious thing to do is to display the show
	 * title and the file extension in paranthesis.
	 *
	 * Fallback to internal feed name.
	 * 
	 * @return string
	 */
	public function title_for_discovery() {

		$podcast = Podcast::get_instance();

		if ( ! $episode_asset = $this->episode_asset() )
			return $this->name;

		if ( ! $file_type = $episode_asset->file_type() )
			return $this->name;

		$file_extension = $file_type->extension;

		$title_template = is_feed() ? '%s (%s)' : __( 'Podcast Feed: %s (%s)', 'podcast' );

		$title = sprintf( $title_template, $podcast->title, $this->name );
		$title = apply_filters( 'podlove_feed_title_for_discovery', $title, $this->title, $file_extension, $this->id );

		return $title;
	}

	/**
	 * Find the related episode asset model.
	 * 
	 * @return \Podlove\Model\EpisodeAsset|NULL
	 */
	public function episode_asset() {
		return ( $this->episode_asset_id ) ? EpisodeAsset::find_by_id( $this->episode_asset_id ) : NULL;
	}

	/**
	 * Find all post_ids associated with this feed.
	 * 
	 * @return array
	 */
	function post_ids() {
		global $wpdb;

		$allowed_status = array("publish");
		$allowed_status = apply_filters("podlove_feed_post_ids_allowed_status", $allowed_status);

		$sql = "
			SELECT
				p.ID
			FROM
				" . $wpdb->posts . " p
				INNER JOIN " . Episode::table_name() .  " e ON e.post_id = p.ID
				INNER JOIN " . MediaFile::table_name() .  " mf ON mf.`episode_id` = e.id
				INNER JOIN " . EpisodeAsset::table_name() .  " a ON a.id = mf.`episode_asset_id`
			WHERE
				a.id = %d
				AND
				p.post_status IN (" . implode(',', array_map(function($s) { return "'$s'"; }, $allowed_status)) . ")
			ORDER BY
				p.post_date DESC
		";

		return $wpdb->get_col(
			$wpdb->prepare(
				$sql,
				$this->episode_asset()->id
			)
		);
	}

	public function get_content_type() {
		return 'application/rss+xml';
	}

	public function get_self_link() {

		$href = $this->get_subscribe_url();

		$current_page = ( get_query_var('paged') ) ? get_query_var('paged') : 1;
		if ( $current_page > 1 ) {
			$href .= "?paged=" . $current_page;
		}

		return self::get_link_tag( array(
			'prefix' => 'atom',
			'rel'    => 'self',
			'type'   => $this->get_content_type(),
			'title'  => \Podlove\Feeds\prepare_for_feed( $this->title_for_discovery() ),
			'href'   => $href
		) );
	}

	public function get_alternate_links() {

		$html = '';
		foreach ( self::find_all_by_discoverable(1) as $feed ) {
			if ( $feed->id !== $this->id ) {
				$html .= "\n\t" . self::get_link_tag( array(
					'prefix' => 'atom',
					'rel'    => 'alternate',
					'type'   => $feed->get_content_type(),
					'title'  => \Podlove\Feeds\prepare_for_feed( $feed->title_for_discovery() ),
					'href'   => $feed->get_subscribe_url()
				) );
			}
		}

		return apply_filters( 'podlove_feed_alternate_links', $html );
	}

	public static function get_link_tag( $args = array() ) {
		
		$defaults = array(
			'prefix' => NULL,
			'rel'    => 'alternate',
			'type'   => 'application/atom+xml',
			'title'  => '',
			'href'   => ''
		);
		$args = wp_parse_args( $args, $defaults );

		$tag_name = $args['prefix'] ? $args['prefix'] . ':link' : 'link';

		if (isset($_GET['redirect'])) {
			$op = parse_url($args['href'], PHP_URL_QUERY) ? '&amp;' : '?';
			$args['href'] .= $op . "redirect=" . $_GET['redirect'];
		}

		return sprintf(
			'<%s%s%s%s href="%s" />',
			$tag_name,
			$args['rel']   ? ' rel="'   . $args['rel']   . '"' : '',
			$args['type']  ? ' type="'  . $args['type']  . '"' : '',
			$args['title'] ? ' title="' . $args['title'] . '"' : '',
			$args['href']
		);
	}

	/**
	 * Get the SQL LIMIT segment for this feed.
	 *
	 * Depending on settings it can be LIMIT <num> or empty.
	 * 
	 * @return string
	 */
	public function get_post_limit_sql($posts_per_page = false)
	{
		if ($posts_per_page === false)
			$posts_per_page = (int) $this->limit_items;

		if ($posts_per_page === self::ITEMS_WP_LIMIT)
			$posts_per_page = (int) get_option('posts_per_rss');

		if ($posts_per_page > 0)
			return $posts_per_page;

		// no limit
		if ($posts_per_page === self::ITEMS_NO_LIMIT)
			return '';

		if ($posts_per_page === self::ITEMS_GLOBAL_LIMIT) {
			$podcast = Podcast::get_instance();
			if ((int) $podcast->limit_items !== self::ITEMS_GLOBAL_LIMIT) {
				return $this->get_post_limit_sql($podcast->limit_items);
			}
		}

		// default to no limit; however, this should never happen
		return '';
	}

	/**
	 * Fetch feed source
	 */

	public function getSource() {
		$curl = new \Podlove\Http\Curl();
		$curl->request( $this->get_subscribe_url() . ( strpos( $this->get_subscribe_url(), "?" ) ? "&redirect=no" : "?redirect=no" ), array(
			'headers' => array( 'Content-type'  => 'application/json' ),
			'timeout' => 10,
			'compress' => true,
			'decompress' => false,
			'sslcertificates' => '',
			'_redirection' => ''
		) );

		$response = $curl->get_response();

		if( is_wp_error( $response ) )
			return FALSE; // Return FALSE if Error occured

		return $response;
	}

	/**
	 * Feed Validation via w3c validator API (http://validator.w3.org/feed/)
	 */

	public function getValidation()
	{
		$curl = new \Podlove\Http\Curl();
		$curl->request( "http://validator.w3.org/feed/check.cgi?output=soap12&url=" . $this->get_subscribe_url(), array(
			'headers' => array( 'Content-type'  => 'application/soap+xml' ),
			'timeout' => 20,
			'compress' => true,
			'decompress' => false,
			'sslcertificates' => '',
			'_redirection' => ''
		) );
		$response = $curl->get_response();

		if( is_wp_error( $response ) )
			return FALSE; // Return FALSE if Error occured

		if( strpos( $response['body'], 'faultcode' ) )
			return FALSE; // Returning FALSE if feed is not recheable

		$xml = simplexml_load_string( $response['body'] ); 

		$namespaces = $xml->getNamespaces( true );
		$soap = $xml->children( $namespaces['env'] ); // Strip SOAP environment

		return $soap->Body->children( $namespaces['m'] )->children( $namespaces['m'] ); // Return errors and warnings
	}

	public function getValidationErrorsandWarnings()
	{
		$warning_and_error_list = $this->getValidation();

		if( !$warning_and_error_list ) 
			return FALSE;

		$warning_list = array();
		$error_list = array();

		// Getting Warnings
		foreach ( $warning_and_error_list->warnings->warninglist->children()  as $warning_key => $warning  ) {
			$warning_list[] = get_object_vars( $warning ); // Converting object to array here to have a consistent data structure
		}

		foreach ( $warning_and_error_list->errors->errorlist->children()  as $error_key => $error  ) {
			$error_list[] = get_object_vars( $error ); // Converting object to array here to have a consistent data structure
		}

		return array(
						'validity'				=> $warning_and_error_list->validity->__toString(),
						'number_of_errors' 		=> $warning_and_error_list->errors->errorcount->__toString(),
						'number_of_warnings'	=> $warning_and_error_list->warnings->warningcount->__toString(),
						'errors'				=> $error_list,
						'warnings'				=> $warning_list
					);
	}

	public function getValidationIcon()
	{

		$errors_and_warnings = $this->getValidationErrorsandWarnings();
		$feed_subscribe_url = $this->get_subscribe_url();

		\Podlove\Log::get()->addInfo( 'Validate feed <a href="' . $feed_subscribe_url . '">' . $this->name . '</a>.' );

		if( !$errors_and_warnings ) {
			\Podlove\Log::get()->addInfo( 'Feed <a href="' . $feed_subscribe_url . '">' . $this->name . '</a> is not accessible for validation.' );
			return self::FEED_VALIDATION_INACTIVE;
		}

		// Log Warnings and errors
		$this->logValidation( $errors_and_warnings );

		return ( $errors_and_warnings['validity'] == 'true' ? self::FEED_VALIDATION_OK : self::FEED_VALIDATION_ERROR );
	}

	public function logValidation( $errors_and_warnings )
	{
		$feed_subscribe_url = $this->get_subscribe_url();

		foreach ( $errors_and_warnings['warnings'] as $warning_key => $warning ) {
			\Podlove\Log::get()->addInfo( 'Warning: ' . $warning['text'] . ', line ' . $warning['line'] . ' in Feed <a href="' . $feed_subscribe_url . '">' . $this->name . '</a>.'   );	
		}

		foreach ( $errors_and_warnings['errors'] as $error_key => $error ) {
			\Podlove\Log::get()->addError( 'Error: ' . $error['text'] . ', line ' . $error['line'] . ' in Feed <a href="' . $feed_subscribe_url . '">' . $this->name . '</a>.'   );	
		}
	}

}

Feed::property( 'id', 'INT NOT NULL AUTO_INCREMENT PRIMARY KEY' );
Feed::property( 'episode_asset_id', 'INT' );
Feed::property( 'itunes_feed_id', 'INT' );
Feed::property( 'name', 'VARCHAR(255)' );
Feed::property( 'title', 'VARCHAR(255)' );
Feed::property( 'slug', 'VARCHAR(255)' );
Feed::property( 'position', 'FLOAT' );
Feed::property( 'redirect_url', 'VARCHAR(255)' );
Feed::property( 'redirect_http_status', 'INT' );
Feed::property( 'enable', 'INT' );
Feed::property( 'discoverable', 'INT' );
Feed::property( 'limit_items', 'INT' );
Feed::property( 'embed_content_encoded', 'INT' );
Feed::property( 'append_name_to_podcast_title', 'TINYINT(1)' );
Feed::property( 'protected', 'TINYINT(1)' );
Feed::property( 'protection_type', 'TINYINT(1)' ); // Protection type: 0: local, 1: WordPress User
Feed::property( 'protection_user', 'VARCHAR(60)' );
Feed::property( 'protection_password', 'VARCHAR(64)' );