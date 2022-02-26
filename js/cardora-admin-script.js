jQuery( function() {
	/**
	 * Options for jQuery sortable
	 *
	 * @type  Object
	 */
	let options = {
		handle: ".drag-movies",
		cursor: 'move',
		axis: 'y',
		items: ' tr',
		update: function( ev, ui ) {
			let dataToSort = jQuery(this).sortable( 'toArray' );

			jQuery.ajax({
				url: tsWatermark.ajax_url,
				method: 'POST',
				data: {action: 'sort_movies', data: dataToSort},
				success: function( res ) {
				},
				error: function( err ) {
					console.log( 'Error: ' + err );
				}
			});
		},
	};

	/**
	 * Apply jQuery sortable
	 */
	jQuery( '#the-list' ).sortable( options );

	jQuery( document ).on('click', '.btt-movie', function() {
		let mid = parseInt( jQuery(this).attr('data-mid') );
		let so  = parseInt( jQuery(this).attr('data-so') );
		let parentTr = jQuery(this).parents('tr');
		let parentTrBgColor = parentTr.css('background-color');

		jQuery.ajax({
			url: tsWatermark.ajax_url,
			method: 'POST',
			data: { action: 'btt_movie', mid: mid, so: so },
			beforeSend: function() {
				parentTr.css('background-color', '#ffb0a2');
			},
			success: function( res ) {
				parentTr.clone().prependTo('#the-list');
				parentTr.remove();
			},
			error: function( err ) {
				console.log( 'Error: ' + err );
			},
			complete: function() {
				setTimeout(
					function() { jQuery('tr#'+mid).css('background-color', parentTrBgColor); },
					3000
				);
			}
		});
	});

	jQuery( document ).on('click', '#reset_movies_sort_order', function() {
		if( confirm('Are you sure about resetting sort order for all the movies now?') === true ) {
			jQuery.ajax({
				url: tsWatermark.ajax_url,
				method: 'POST',
				data: {action: 'reset_movies_sort_order'},
				success: function( res ) {
					window.location = tsWatermark.site_url + tsWatermark.current_page;
				},
				error: function( err ) {
					console.log( err );
				}
			});
		}
	});
});
