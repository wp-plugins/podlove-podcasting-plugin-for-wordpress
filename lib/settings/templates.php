<?php 
namespace Podlove\Settings;
use \Podlove\Model;

class Templates {

	static $pagehook;
	
	public function __construct( $handle ) {
		
		self::$pagehook = add_submenu_page(
			/* $parent_slug*/ $handle,
			/* $page_title */ __( 'Templates', 'podlove' ),
			/* $menu_title */ __( 'Templates', 'podlove' ),
			/* $capability */ 'administrator',
			/* $menu_slug  */ 'podlove_templates_settings_handle',
			/* $function   */ array( $this, 'page' )
		);
		add_action( 'admin_init', array( $this, 'scripts_and_styles' ) );	

		register_setting( Templates::$pagehook, 'podlove_template_assignment' );
	}

	public function scripts_and_styles() {

		if ( ! isset( $_REQUEST['page'] ) )
			return;

		if ( $_REQUEST['page'] != 'podlove_templates_settings_handle' )
			return;

		wp_register_script( 'podlove-ace-js', \Podlove\PLUGIN_URL . '/js/admin/ace/ace.js' );

		wp_register_script( 'podlove-template-js', \Podlove\PLUGIN_URL . '/js/admin/template.js', array( 'jquery', 'podlove-ace-js') );
		wp_enqueue_script( 'podlove-template-js' );
	}

	public function page() {
		?>
		<div class="wrap">
			<?php screen_icon( 'podlove-podcast' ); ?>
			<h2><?php echo __( 'Templates', 'podlove' ); ?></h2>
			<?php
			$this->view_template();
			?>
		</div>	
		<?php
	}

	private function view_template() {

		echo sprintf(
			__( 'Episode Templates are an easy way to keep the same structure in all your episodes. Create one and use the displayed %sShortcode%s as the episode content.', 'podlove' ),
			'<a href="http://docs.podlove.org/ref/shortcodes.html" target="_blank">',
			'</a>'
		);

		?>
		<div id="template-editor">
			<div class="navigation">
				<ul>
					<?php foreach ( Model\Template::all() as $template ): ?>
						<li>
							<a href="#" data-id="<?php echo $template->id ?>">
								<span class="filename"><?php echo $template->title; ?></span>&nbsp;
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
				<div class="add">
					<a href="#">+ add new template</a>
				</div>
			</div>
			<div class="editor">
				<div class="toolbar">
					<div class="actions">
						<a href="#" class="delete">delete</a>
						<a href="#" class="save button button-primary">Save</a>
					</div>
					<div class="title">
						<input type="text">
					</div>
					<div class="clear"></div>
				</div>
				<div class="editor-wrapper">
					<div class="main" id="ace-editor"></div>
					<div id="fullscreen" class="fullscreen-on fullscreen-button"></div>
				</div>
			</div>
			<div class="clear"></div>
		</div>

		<h3><?php echo __( 'Insert templates to content automatically', 'podlove' ) ?></h3>
		<form method="post" action="options.php">
			<?php settings_fields( Templates::$pagehook );
			$template_assignment = Model\TemplateAssignment::get_instance();

			$form_attributes = array(
				'context'    => 'podlove_template_assignment',
				'form'       => false
			);

			\Podlove\Form\build_for( $template_assignment, $form_attributes, function ( $form ) {
				$wrapper = new \Podlove\Form\Input\TableWrapper( $form );
				
				$templates = array( 0 => __( 'Don\'t insert automatically', 'podlove' ) );
				foreach ( Model\Template::all() as $template ) {
					$templates[ $template->id ] = $template->title;
				}

				$wrapper->select( 'top', array(
					'label'   => __( 'Insert at top', 'podlove' ),
					'options' => $templates,
					'please_choose' => false
				) );

				$wrapper->select( 'bottom', array(
					'label'   => __( 'Insert at bottom', 'podlove' ),
					'options' => $templates,
					'please_choose' => false
				) );

			});
		?>
		</form>
		<?php
	}

}