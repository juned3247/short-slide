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

		var file = jQuery('#ss_myfile')[0].files[0];

		var formData = new FormData();
		formData.append('file', file);
		jQuery.ajax({
			url: 'http://wordpress-assignment.tk/wp-json/short-slide/v1/image_upload',
			method: 'POST',
			headers: wpHeaders,
			processData: false,
			contentType: false,
			data: formData
		}).success(function (response) {
			console.log(response);
			jQuery('#ss_myfile').val(null);
			loadPhotos();
		}).error(function (response) {
			console.log('error');
			console.error(response);
		});
	});

	jQuery('button[imageid]').click(function (e) {
		e.preventDefault();
		var imageId = jQuery(this).attr('imageid');
		deleteImage(imageId);
	});
});

function deleteImage(imageId) {
	var formData = new FormData();
	formData.append('image_id', imageId);
		
	jQuery.ajax({
		url: 'http://wordpress-assignment.tk/wp-json/short-slide/v1/image_delete',
		method: 'POST',
		headers: wpHeaders,
		processData: false,
		contentType: false,
		data: formData
	}).success(function (response) {
		console.log(response);
		if(response.success) {
			jQuery('img[imageid=' + imageId + ']').remove();
		}
	}).error(function (response) {
		console.log('error');
		console.error(response);
	});
}

function loadPhotos() {
	jQuery('#photos').empty();
	jQuery.ajax({
		url: 'http://wordpress-assignment.tk/wp-json/short-slide/v1/get_images',
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
			createDeleteContext();
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
				url: 'http://wordpress-assignment.tk/wp-json/short-slide/v1/sort_list',
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

function createDeleteContext() {
	jQuery('#photos img').contextmenu(function (e) {
		e.preventDefault();
		var conf = confirm('Do you want to delete this image?');
		if(conf) {
			var imageId = jQuery(this).attr('imageid');
			deleteImage(imageId);
		}
	});
}
