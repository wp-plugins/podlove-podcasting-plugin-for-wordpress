<?php
namespace Podlove;

/**
 * Shorthand translation function.
 * 
 * @param string $text
 * @return string
 */
function t( $text ) {
	return __( $text, 'podlove' );
}

function format_bytes( $size, $decimals = 2 ) {
    $units = array( ' B', ' KB', ' MB', ' GB', ' TB' );
    for ( $i = 0; $size >= 1024 && $i < 4; $i++ ) $size /= 1024;
    return round( $size, $decimals ) . $units[$i];
}

function get_setting( $name ) {
	
	$defaults = array(
		'merge_episodes' => 'off' // can't be "on"
	);

	$options = get_option( 'podlove' );
	$options = wp_parse_args( $options, $defaults );

	return $options[ $name ];
}

namespace Podlove\Form;

/**
 * Convenience wrapper function for Form Builder.
 * 
 * @see \Podlove\Form\Builder::input
 */
function input( $context, $object, $field_key, $field_value ) {
	$builder = new Builder;
	$builder->input( $context, $object, $field_key, $field_value );
}

/**
 * Build whole form
 * @param  object   $object   object that shall be modified via the form
 * @param  array    $args     list of options, all optional
 * 		- action        form action url
 * 		- method        get, post
 * 		- hidden        dictionary with hidden values
 * 		- submit_button set to false to hide the submit button
 * @param  function $callback inner form
 * @return void
 * 
 * @todo  refactor into a wrapper so the <table> is optional
 * @todo  hidden fields should be added via input builders
 */
function build_for( $object, $args, $callback ) {

	// determine form action url
	if ( isset( $args[ 'action' ] ) ) {
		$url = $args[ 'action' ];
	} else {
		$url = is_admin() ? 'admin.php' : '';
		if ( isset( $_REQUEST[ 'page' ] ) ) {
			$url .= '?page=' . $_REQUEST[ 'page' ];
		}
	}

	// determine method
	$method = isset( $args[ 'method' ] ) ? $args[ 'method' ] : 'post';

	// determine context
	$context = isset( $args[ 'context' ] ) ? $args[ 'context' ] : ''; 
	?>
	<form action="<?php echo $url; ?>" method="<?php echo $method; ?>">

		<?php if ( isset( $args[ 'hidden' ] ) && $args[ 'hidden' ] ): ?>
			<?php foreach ( $args[ 'hidden' ] as $name => $value ): ?>
				<input type="hidden" name="<?php echo $name; ?>" value="<?php echo $value; ?>" />		
			<?php endforeach ?>
		<?php endif ?>

		<table class="form-table">
			<?php call_user_func( $callback, new \Podlove\Form\Input\Builder( $object, $context ) ); ?>
		</table>
		<?php if ( ! isset( $args[ 'submit_button' ] ) || $args[ 'submit_button' ] === true ): ?>
			<?php submit_button(); ?>
		<?php endif ?>
	</form>
	<?php
}

namespace Podlove\Itunes;

/**
 * iTunes category generator.
 * 
 * Gratefully borrowed from powerpress.
 * 
 * @param bool $prefix_subcategories
 * @return array
 */
function categories( $prefix_subcategories = true ) {
	$temp = array();
	$temp[ '01-00' ] = 'Arts';
		$temp[ '01-01' ] = 'Design';
		$temp[ '01-02' ] = 'Fashion & Beauty';
		$temp[ '01-03' ] = 'Food';
		$temp[ '01-04' ] = 'Literature';
		$temp[ '01-05' ] = 'Performing Arts';
		$temp[ '01-06' ] = 'Visual Arts';

	$temp[ '02-00' ] = 'Business';
		$temp[ '02-01' ] = 'Business News';
		$temp[ '02-02' ] = 'Careers';
		$temp[ '02-03' ] = 'Investing';
		$temp[ '02-04' ] = 'Management & Marketing';
		$temp[ '02-05' ] = 'Shopping';

	$temp[ '03-00' ] = 'Comedy';

	$temp[ '04-00' ] = 'Education';
		$temp[ '04-01' ] = 'Education Technology';
		$temp[ '04-02' ] = 'Higher Education';
		$temp[ '04-03' ] = 'K-12';
		$temp[ '04-04' ] = 'Language Courses';
		$temp[ '04-05' ] = 'Training';
		 
	$temp[ '05-00' ] = 'Games & Hobbies';
		$temp[ '05-01' ] = 'Automotive';
		$temp[ '05-02' ] = 'Aviation';
		$temp[ '05-03' ] = 'Hobbies';
		$temp[ '05-04' ] = 'Other Games';
		$temp[ '05-05' ] = 'Video Games';

	$temp[ '06-00' ] = 'Government & Organizations';
		$temp[ '06-01' ] = 'Local';
		$temp[ '06-02' ] = 'National';
		$temp[ '06-03' ] = 'Non-Profit';
		$temp[ '06-04' ] = 'Regional';

	$temp[ '07-00' ] = 'Health';
		$temp[ '07-01' ] = 'Alternative Health';
		$temp[ '07-02' ] = 'Fitness & Nutrition';
		$temp[ '07-03' ] = 'Self-Help';
		$temp[ '07-04' ] = 'Sexuality';

	$temp[ '08-00' ] = 'Kids & Family';
 
	$temp[ '09-00' ] = 'Music';
 
	$temp[ '10-00' ] = 'News & Politics';
 
	$temp[ '11-00' ] = 'Religion & Spirituality';
		$temp[ '11-01' ] = 'Buddhism';
		$temp[ '11-02' ] = 'Christianity';
		$temp[ '11-03' ] = 'Hinduism';
		$temp[ '11-04' ] = 'Islam';
		$temp[ '11-05' ] = 'Judaism';
		$temp[ '11-06' ] = 'Other';
		$temp[ '11-07' ] = 'Spirituality';
	 
	$temp[ '12-00' ] = 'Science & Medicine';
		$temp[ '12-01' ] = 'Medicine';
		$temp[ '12-02' ] = 'Natural Sciences';
		$temp[ '12-03' ] = 'Social Sciences';
	 
	$temp[ '13-00' ] = 'Society & Culture';
		$temp[ '13-01' ] = 'History';
		$temp[ '13-02' ] = 'Personal Journals';
		$temp[ '13-03' ] = 'Philosophy';
		$temp[ '13-04' ] = 'Places & Travel';

	$temp[ '14-00' ] = 'Sports & Recreation';
		$temp[ '14-01' ] = 'Amateur';
		$temp[ '14-02' ] = 'College & High School';
		$temp[ '14-03' ] = 'Outdoor';
		$temp[ '14-04' ] = 'Professional';
		 
	$temp[ '15-00' ] = 'Technology';
		$temp[ '15-01' ] = 'Gadgets';
		$temp[ '15-02' ] = 'Tech News';
		$temp[ '15-03' ] = 'Podcasting';
		$temp[ '15-04' ] = 'Software How-To';

	$temp[ '16-00' ] = 'TV & Film';

	if ( $prefix_subcategories ) {
		while ( list( $key, $val ) = each( $temp ) ) {
			$parts  = explode( '-', $key );
			$cat    = $parts[ 0 ];
			$subcat = $parts[ 1 ];
		 
			if( $subcat != '00' )
				$temp[ $key ] = $temp[ $cat . '-00' ] . ' > ' . $val;
		}
		reset( $temp );
	}
 
	return $temp;
}
