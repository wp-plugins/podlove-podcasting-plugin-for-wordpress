<?php
namespace Podlove\Settings\Expert\Tab;
use \Podlove\Settings\Settings;
use \Podlove\Settings\Expert\Tab;

class Metadata extends Tab {
	public function init() {

		add_settings_section(
			/* $id 		 */ 'podlove_settings_episode',
			/* $title 	 */ __( '', 'podlove' ),	
			/* $callback */ function () { echo '<h3>' . __( 'Episode Metadata Settings', 'podlove' ) . '</h3>'; },
			/* $page	 */ Settings::$pagehook	
		);

		add_settings_field(
			/* $id       */ 'podlove_setting_episode_record_date',
			/* $title    */ sprintf(
				'<label for="enable_episode_record_date">%s</label>',
				__( 'Enable recording date field.', 'podlove' )
			),
			/* $callback */ function () {
				?>
				<label>
					<input name="podlove_metadata[enable_episode_record_date]" id="enable_episode_record_date" type="radio" value="1" <?php checked( \Podlove\get_setting( 'metadata', 'enable_episode_record_date' ), 1 ) ?> /> <?php echo __( 'enable', 'podlove' ) ?>
				</label>
				<label>
					<input name="podlove_metadata[enable_episode_record_date]" id="enable_episode_record_date" type="radio" value="0" <?php checked( \Podlove\get_setting( 'metadata', 'enable_episode_record_date' ), 0 ) ?> /> <?php echo __( 'disable', 'podlove' ) ?>
				</label>
				<?php
			},
			/* $page     */ Settings::$pagehook,  
			/* $section  */ 'podlove_settings_episode'
		);

		add_settings_field(
			/* $id       */ 'podlove_setting_episode_publication_date',
			/* $title    */ sprintf(
				'<label for="enable_episode_publication_date">%s</label>',
				__( 'Enable publication date field.', 'podlove' )
			),
			/* $callback */ function () {
				?>
				<label>
					<input name="podlove_metadata[enable_episode_publication_date]" id="enable_episode_publication_date" type="radio" value="1" <?php checked( \Podlove\get_setting( 'metadata', 'enable_episode_publication_date' ), 1 ) ?> /> <?php echo __( 'enable', 'podlove' ) ?>
				</label>
				<label>
					<input name="podlove_metadata[enable_episode_publication_date]" id="enable_episode_publication_date" type="radio" value="0" <?php checked( \Podlove\get_setting( 'metadata', 'enable_episode_publication_date' ), 0 ) ?> /> <?php echo __( 'disable', 'podlove' ) ?>
				</label>
				<?php
			},
			/* $page     */ Settings::$pagehook,  
			/* $section  */ 'podlove_settings_episode'
		);

		add_settings_field(
			/* $id       */ 'podlove_setting_episode_license',
			/* $title    */ sprintf(
				'<label for="enable_episode_license">%s</label>',
				__( 'Enable license field.', 'podlove' )
			),
			/* $callback */ function () {
				?>
				<label>
					<input name="podlove_metadata[enable_episode_license]" id="enable_episode_license" type="radio" value="1" <?php checked( \Podlove\get_setting( 'metadata', 'enable_episode_license' ), 1 ) ?> /> <?php echo __( 'enable', 'podlove' ) ?>
				</label>
				<label>
					<input name="podlove_metadata[enable_episode_license]" id="enable_episode_license" type="radio" value="0" <?php checked( \Podlove\get_setting( 'metadata', 'enable_episode_license' ), 0 ) ?> /> <?php echo __( 'disable', 'podlove' ) ?>
				</label>
				<?php
			},
			/* $page     */ Settings::$pagehook,  
			/* $section  */ 'podlove_settings_episode'
		);

		register_setting( Settings::$pagehook, 'podlove_metadata' );
	}
}