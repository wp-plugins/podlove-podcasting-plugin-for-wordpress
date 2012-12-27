var PODLOVE = PODLOVE || {};

/**
 * Handles all logic in Create/Edit Episode screen.
 */
(function($){
	 PODLOVE.Episode = function (container) {

	 	var o = {};
	 	var ajax_requests = [];

	 	// private
	 	function enable_all_media_files_by_default() {
	 		if (o.slug_field.val().length === 0) {
	 			o.slug_field.on('blur', function() {
	 				if (o.slug_field.val().length > 0) {
	 					// by default, tick all
	 					$container.find('input[type="checkbox"][name*="episode_assets"]')
	 						.attr("checked", true)
	 						.change();
	 				}
	 			});
	 		}

			var typewatch = (function() {
				var timer = 0;
				return function(callback, ms) {
					clearTimeout (timer);
					timer = setTimeout(callback, ms);
				}
			})();

	 		o.slug_field
	 			.on('blur', function() {
	 				maybe_update_media_files();
	 			})
	 			.on('keyup', function() {
					typewatch(
						function() { maybe_update_media_files() },
						500
					);
				});

	 	}

	 	function maybe_update_media_files() {
	 		var current_slug = o.slug_field.val(),
	 		    prev_slug = o.slug_field.data('prev-slug');

	 		if (current_slug !== prev_slug) {
	 			// abort all current requests if any are running
	 			$.each(
	 				ajax_requests,
	 				function(index, request){ request.abort(); });
	 			// then trigger new requests
	 			$(".update_media_file").click();
	 		}

	 		o.slug_field.data('prev-slug', current_slug);
	 	};

	 	function generate_live_preview() {
	 		o.update_preview();
	 		$('input[name*="slug"], input[name*="episode_assets"]', container).on('change', o.update_preview);
	 	};

	 	function create_file(args) {
	 		var data = {
	 			action: 'podlove-create-file',
	 			episode_id: args.episode_id,
	 			episode_asset_id: args.episode_asset_id,
	 			slug: $("#_podlove_meta_slug").val()
	 		};

	 		$.ajax({
	 			url: ajaxurl,
	 			data: data,
	 			dataType: 'json',
	 			success: function(result) {
	 				args.checkbox.data({
	 					id: result.file_id,
	 					size: result.file_size
	 				});
	 				o.update_preview();
	 			}
	 		});
	 	};

 		o.update_preview = function() {
 			$(".media_file_row", o.container).each(function() {
 				$container = $(this).closest('.inside');
 				$checkbox  = $(this).find("input");
 				var output = '';

 				if ($($checkbox).is(":checked")) {
 					var file_id = $checkbox.data('id');

 					if (!file_id) {
 						// create file
 						create_file({
 							episode_id: $checkbox.data('episode-id'),
 							episode_asset_id: $checkbox.data('episode-asset-id'),
 							checkbox: $checkbox
 						});
 					} else {
	 					var url                 = $checkbox.data('template');
	 					var media_file_base_uri = $container.find('input[name="show-media-file-base-uri"]').val();
	 					var episode_slug        = $container.find('input[name*="slug"]').val();
	 					var format_extension    = $checkbox.data('extension');
	 					var size                = $checkbox.data('size');
	 					var suffix              = $checkbox.data('suffix');

	 					url = url.replace( '%media_file_base_url%', media_file_base_uri );
	 					url = url.replace( '%episode_slug%', episode_slug );
	 					url = url.replace( '%suffix%', suffix );
	 					url = url.replace( '%format_extension%', format_extension );

	 					var readable_size = human_readable_size( size );
	 					var filename      = url.replace(media_file_base_uri, "");
	 					var $row          = $checkbox.closest(".media_file_row");

	 					if (readable_size === "???") {
	 						size_html = '<span style="color:red">File not found!</span>';
	 						$row.find(".status").html('<span style="color: red">!!!</span>');
	 					} else {
	 						size_html = '<span style="color:#0a0b0b" title="' + readable_size + '">' + size + ' Bytes</span>';	
	 						$row.find(".status").html('<span style="color: green">✓</span>');
	 					}
	 					$row.find(".size").html(size_html);
	 					$row.find(".url").html('<span title="' + url + '">' + filename + '</span>');
	 					$row.find(".update").html('<a href="#" class="update_media_file">update</a>');
 					}

 				} else {
 					$checkbox.data('id', null);
 					$checkbox.closest(".media_file_row").find(".size, .url, .update, .status").html('');
 				}

 			});
 		}

 		o.slug_field = container.find("[name*=slug]");
 		enable_all_media_files_by_default();
 		generate_live_preview();

 		$("#_podlove_meta_subtitle, #_podlove_meta_summary").autogrow();
 		$("#_podlove_meta_subtitle").count_characters( { limit: 255,  title: 'recommended maximum length: 255' } );
 		$("#_podlove_meta_summary").count_characters(  { limit: 4000, title: 'recommended maximum length: 4000' } );

 		$(document).on("click", ".subtitle_warning .close", function() {
 			$(this).closest(".subtitle_warning").remove();
 		});

 		$("#_podlove_meta_subtitle").keydown(function(e) {
 			// forbid return key
 			if (e.keyCode == 13) {
 				e.preventDefault();

 				if (!$(".subtitle_warning").length) {
	 				$(this).after('<span class="subtitle_warning">The subtitle has to be a single line. <span class="close">(hide)</span></span>');
 				}

 				return false;
 			}
 		});

 		$(".media_file_row").each(function() {
 			$(".enable", this).html($(".asset input", this));
 		});

 		$(".row__podlove_meta_episode_assets > span > label").after(" <a href='#' id='update_all_media_files'>update all media files</a>")

 		$(document).on("click", "#update_all_media_files", function(e) {
 			e.preventDefault();
 			$(".update_media_file").click();
 			return false;
 		});

 		$(document).on("click", ".update_media_file", function(e) {
 			e.preventDefault();

 			var container = $(this).closest(".media_file_row");
 			var file = container.find("input").data();

 			var data = {
 				action: 'podlove-update-file',
 				file_id: file.id,
 				slug: $("#_podlove_meta_slug").val()
 			};

 			container.find('.update').html("updating ...");
 			container.find(".size, .url, .status").html('');

 			var request = $.ajax({
 				url: ajaxurl,
 				data: data,
 				dataType: 'json',
 				success: function(result) {
 					container.find("input").data('size', result.file_size);
 					o.update_preview();
 					ajax_requests.pop();
 				}
 			});
 			ajax_requests.push(request);

 			return false;
 		});

	 	return o;

	}
}(jQuery));

