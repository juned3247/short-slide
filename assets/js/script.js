var ssmode = true;
jQuery( document ).ready( function() {

	wpHeaders = {
		'X-WP-Nonce': apiVars.nonce
	}

	loadPhotos();

	jQuery('#ss_submit').click(function () {

		if ( 'undefined' === typeof( jQuery('#ss_myfile')[0].files[0] ) ) {
			alert( 'Select a file!' );
			return;
		}

		var files = jQuery('#ss_myfile')[0].files;

		var formData = new FormData();
		for(let i = 0; i < files.length; i++) {
			formData.append('files[]', files[i]);
		}
		jQuery.ajax({
			url: '/wp-json/short-slide/v1/image_upload',
			method: 'POST',
			headers: wpHeaders,
			processData: false,
			contentType: false,
			data: formData,
			xhr: function () {
				var myXhr = jQuery.ajaxSettings.xhr();
                if(myXhr.upload){
                    myXhr.upload.addEventListener('progress',progress_upload, false);
                }
                return myXhr;
			}
		}).success(function (response) {
			console.log(response);
			jQuery('#ss_myfile').val(null);
			loadPhotos();
			jQuery('#upload_progress').css('display', 'none');
			jQuery('#upload_progress_bar').css('width', '1%');
		}).error(function (response) {
			console.log('error');
			console.error(response);
			jQuery('#upload_progress').css('display', 'none');
			jQuery('#upload_progress_bar').css('width', '1%');
		});
	});

	jQuery('button[imageid]').click(function (e) {
		e.preventDefault();
		var imageId = jQuery(this).attr('imageid');
		deleteImage(imageId);
	});

	jQuery('#delete_photos_button').click(function (e) {
		e.preventDefault();
		toggleMode(ssmode);
	});

	jQuery('#delete_selected_button').click(function (e) {
		e.preventDefault();
		var selected_images = new Array();
		jQuery('#photos').children().each(function (a) {
			if (jQuery(this).hasClass('ui-selected')) {
				selected_images.push(jQuery(this).attr('imageid'));
			}
		});
		deleteImages(selected_images);
	});
});

function toggleMode() {
	ssmode = !ssmode;
	if(ssmode) {
		// switch to sorting mode
		console.log('switch to sorting mode');
		jQuery('#delete_selected_button, #multiple_select_note').hide();
		jQuery('#photos').selectable(null);
		jQuery('#photos').children().each(function () {
			jQuery(this).removeClass('ui-selectee');
			jQuery(this).removeClass('ui-selected');
		});
		jQuery('#photos').sortable('enable');
	} else {
		// switch to selection mode
		console.log('switch to selection mode');
		jQuery('#delete_selected_button, #multiple_select_note').show();
		jQuery('#photos').sortable('disable');
		jQuery('#photos').children().each(function () {
			jQuery(this).addClass('ui-selectee');
		});
		jQuery('#photos').selectable();
	}
}

function deleteImages(imageIds) {
	var formData = new FormData();
	for(let i = 0; i < imageIds.length; i++) {
		formData.append('image_ids[]', imageIds[i]);
	}
	jQuery.ajax({
		url: '/wp-json/short-slide/v1/image_delete',
		method: 'POST',
		headers: wpHeaders,
		processData: false,
		contentType: false,
		data: formData
	}).success(function (response) {
		console.log(response);
		if(response.success) {
			for(let i = 0; i < imageIds.length; i++) {
				jQuery('img[imageid=' + imageIds[i] + ']').remove();
			}
			toggleMode();
		}
	}).error(function (response) {
		console.log('error');
		console.error(response);
	});
}

function loadPhotos() {
	jQuery('#photos').empty();
	jQuery.ajax({
		url: '/wp-json/short-slide/v1/get_images',
		method: 'GET',
		headers: wpHeaders,
		processData: false,
		contentType: false
	}).success(function (response) {
		if(response.success) {
			for(let i = 0; i < response.photos.length; i++) {
				var photo = response.photos[i];
				var photoele = jQuery('<img imageid="' + photo.image_id + '">');
				photoele.attr('src', photo.image_url);
				photoele.appendTo('#photos');
			}
			createPhotosSortable();
		} else {
			console.log(error);
			console.log(response);
		}
	}).error(function (response) {
		console.log(error);
		console.log(response);
	})
}

function createPhotosSortable() {
	jQuery('#photos').sortable({
		update: function (event, ui) {
			var updatedList = jQuery('#photos').children();
			var sorted = new Array(updatedList.length);
			for(let i = 0; i < updatedList.length; i++) {
				sorted[i] = updatedList[i].attributes.imageid.value;
			}
			console.log(sorted);
			var formData = new FormData();
			formData.append('new_list', JSON.stringify(sorted));
			jQuery.ajax({
				url: '/wp-json/short-slide/v1/sort_list',
				method: 'POST',
				headers: wpHeaders,
				processData: false,
				contentType: false,
				data: formData
			}).success(function (response) {
				console.log(response);
			}).error(function (response) {
				console.log('errors');
				console.log(response);
			});
		}
	});
	jQuery('#photos').disableSelection();
}

function progress_upload (e) {
	if (e.lengthComputable) {
		var percentage = e.loaded * 100 / e.total;
		jQuery('#upload_progress').css('display', 'block');
		jQuery('#upload_progress_bar').css('width', percentage + '%');
	}
}
