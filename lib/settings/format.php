<?php
namespace Podlove\Settings;

class Format {
	
	public function __construct( $handle ) {
		
		add_submenu_page(
			/* $parent_slug*/ $handle,
			/* $page_title */ 'Formats',
			/* $menu_title */ 'Formats',
			/* $capability */ 'administrator',
			/* $menu_slug  */ 'podlove_formats_settings_handle',
			/* $function   */ array( $this, 'page' )
		);
		add_action( 'admin_init', array( $this, 'process_form' ) );
	}
	
	/**
	 * Process form: save/update a format
	 */
	private function save() {
		if ( ! isset( $_REQUEST['format'] ) )
			return;
			
		$format = \Podlove\Model\MediaFormat::find_by_id( $_REQUEST['format'] );
		
		if ( ! isset( $_POST['podlove_format'] ) || ! is_array( $_POST['podlove_format'] ) )
			return;
			
		foreach ( $_POST['podlove_format'] as $key => $value )
			$format->{$key} = $value;

		$format->save();
		
		$this->redirect( 'edit', $format->id );
	}
	
	/**
	 * Process form: create a format
	 */
	private function create() {
		global $wpdb;
		
		$format = new \Podlove\Model\MediaFormat;
		
		if ( ! isset( $_POST['podlove_format'] ) || ! is_array( $_POST['podlove_format'] ) )
			return;
			
		foreach ( $_POST['podlove_format'] as $key => $value ) {
			$format->{$key} = $value;
		}
		$format->save();

		$this->redirect( 'edit', $wpdb->insert_id );
	}
	
	/**
	 * Process form: delete a format
	 */
	private function delete() {
		if ( ! isset( $_REQUEST['format'] ) )
			return;

		\Podlove\Model\MediaFormat::find_by_id( $_REQUEST['format'] )->delete();
		
		$this->redirect( 'index' );
	}
	
	/**
	 * Helper method: redirect to a certain page.
	 */
	private function redirect( $action, $format_id = NULL ) {
		$page   = 'admin.php?page=' . $_REQUEST['page'];
		$show   = ( $format_id ) ? '&format=' . $format_id : '';
		$action = '&action=' . $action;
		
		wp_redirect( admin_url( $page . $show . $action ) );
		exit;
	}
	
	public function process_form() {
		$action = ( isset( $_REQUEST['action'] ) ) ? $_REQUEST['action'] : NULL;
		
		if ( $action === 'save' ) {
			$this->save();
		} elseif ( $action === 'create' ) {
			$this->create();
		} elseif ( $action === 'delete' ) {
			$this->delete();
		}
	}
	
	public function page() {
		?>
		<div class="wrap">
			<div id="icon-options-general" class="icon32"></div>
			<h2>Podlove Formats <a href="?page=<?php echo $_REQUEST['page']; ?>&amp;action=new" class="add-new-h2"><?php echo __( 'Add New', 'podlove' ); ?></a></h2>
			<?php
			$action = ( isset( $_REQUEST['action'] ) ) ? $_REQUEST['action'] : NULL;
			switch ( $action ) {
				case 'new':
					$this->new_template();
					break;
				case 'edit':
					$this->edit_template();
					break;
				case 'index':
				default:
					$this->view_template();
					break;
			}
			?>
		</div>	
		<?php
	}
	
	private function new_template() {
		$format = new \Podlove\Model\MediaFormat;
		?>
		<h3><?php echo __( 'Add New Format', 'podlove' ); ?></h3>
		<?php
		$this->form_template( $format, 'create', __( 'Add New Format', 'podlove' ) );
	}
	
	private function view_template() {
		$table = new \Podlove\Format_List_Table();
		$table->prepare_items();
		$table->display();
	}
	
	private function form_template( $format, $action, $button_text = NULL ) {

		\Podlove\Form\build_for( $format, array( 'context' => 'podlove_format', 'hidden' => array( 'format' => $format->id, 'action' => $action ) ), function ( $form ) {
			$wrapper = new \Podlove\Form\Input\TableWrapper( $form );

	 		$wrapper->string( 'name', array(
	 			'label'       => __( 'Name', 'podlove' ),
	 			'description' => '' ) );

	 		$wrapper->select( 'type', array(
	 			'label'       => __( 'Document Type', 'podlove' ),
		 		'options'     => array(
		 			'audio' => __( 'Audio', 'podlove' ),
		 			'video' => __( 'Video', 'podlove' ),
		 			'ebook' => __( 'eBook', 'podlove' ),
		 			'image' => __( 'Image', 'podlove' )
		 		)
	 		) );

	 		$wrapper->string( 'mime_type', array(
	 			'label'       => __( 'Format Mime Type', 'podlove' ),
	 			'description' => __( 'Example: audio/mp4', 'podlove' ) ) );

	 		$wrapper->string( 'extension', array(
	 			'label'       => __( 'Format Extension', 'podlove' ),
	 			'description' => __( 'Example: m4a', 'podlove' ) ) );
		} );
	}
	
	private function edit_template() {
		$format = \Podlove\Model\MediaFormat::find_by_id( $_REQUEST['format'] );
		?>
		<h3>Edit Format: <?php echo $format->name ?></h3>
		
		<?php $this->form_template( $format, 'save' ); ?>
		<?php
	}
	
}