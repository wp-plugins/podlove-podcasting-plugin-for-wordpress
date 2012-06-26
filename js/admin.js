function human_readable_size(size) {
	if (!size || size < 1) {
		return "File Size Missing :(";
	}

	var kilobytes = size / 1024;

	if (kilobytes < 500) {
		return kilobytes.toFixed(2) + " kB";
	}

	var megabytes = kilobytes / 1024
	return megabytes.toFixed(2) + " MB";
}

jQuery(function($) {
	
	var	update_media_file_path = function() {
		$("tr.row_media_locations td .media_file_path").each(function() {
			$container = $(this).closest('.inside');
			$checkbox  = $(this).parent().find("input");

			if ($($checkbox).is(":checked")) {
				var url                 = $checkbox.data('template');

				var media_file_base_uri = $container.find('input[name="show-media-file-base-uri"]').val();
				var episode_slug        = $container.find('input[name*="slug"]').val();
				var feed_suffix         = $checkbox.data('suffix');
				var format_extension    = $checkbox.data('extension');
				var size                = $checkbox.data('size');

				url = url.replace( '%show_base_uri%', media_file_base_uri );
				url = url.replace( '%episode_slug%', episode_slug );
				url = url.replace( '%suffix%', feed_suffix );
				url = url.replace( '%format_extension%', format_extension );

				output = '(' + url + ' [' + human_readable_size( size ) + '])';
			} else {
				output = "";
			}
			$(this).html(output);
		});
	}
	
	$("tr.row_media_locations td label").after('<span class="media_file_path"></span>');
	update_media_file_path();
	$('input[name*="slug"], input[name*="media_locations"]').on('change', update_media_file_path);
	
});