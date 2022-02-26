/**
 * Plugin Name:   Create Posters & thumb
 * Description:   This plugin generates the posters and thumb for the video uploaded as a custom post
 * Author:        Tektonic Solutions
 * Author URI:    http://tektonic.solutions/
 * Developer:     Tektonic Solutions
 * Developer URI: http://tektonic.solutions/
 * Text Domain:   tektonic-create-poster-thumb
 * Domain Path:   /languages/
 * Network:       false
 * Slug:          tektonic-create-poster-thumb
 * Version:       1.0.0
 *
 * Copyright (C) 2019  Tektonic Solutions (http://tektonic.solutions/)
 */

var movie_poster = document.querySelector('#movie_poster');
var mid          = document.getElementById('movie');
var seek         = document.getElementById('seek_time');

var createMoviePoster = {
	addWatermark: function( e, arg ) {
		e.preventDefault();
		
		let watermarkFormData = jQuery(arg).serializeArray();
		let formMethod        = jQuery(arg).attr('method');

		jQuery.ajax({
			cache: false,
			url: tsWatermark.ajax_url,
			method: formMethod,
			data: watermarkFormData,
			success: function( res ) {
				jQuery('#movie_poster_preview').html( res );
			},
			error: function( err ) {}
		});
	},
	saveNewPoster: function() {
		let fpath     = jQuery('#movie_poster_preview img').attr('data-fpath');
		let movieSlug = jQuery('#movie_poster_preview img').attr('data-movie-slug');

		jQuery.ajax({
			cache: false,
			url: tsWatermark.ajax_url,
			method: 'POST',
			data: {action: 'save_poster', file_path: fpath, movie_slug: movieSlug},
			success: function( res ) {
				let resp = JSON.parse( res );

				if( resp.redirect_url !== '' ) {
					window.location = resp.redirect_url;
				}
			},
			error: function( err ) {}
		});
	},
	discardNewPoster: function() {
		let fpath = jQuery('#movie_poster_preview img').attr('data-fpath');

		jQuery.ajax({
			cache: false,
			url: tsWatermark.ajax_url,
			method: 'POST',
			data: {action: 'discard_poster', file_path: fpath},
			success: function( res ) {
				if( res == 1 ) {
					jQuery('#movie_poster_preview img').remove();
				}
			},
			error: function( err ) {}
		});
	},
	setDraggableWatermarkValue: function() {
		let draggableWmDiv    = jQuery('#draggable_watermark');
		let wmTextValue       = jQuery('#watermark_text').val();
		let wmColorValue      = jQuery('#watermark_color').val();
		let wmFontSizeValue   = jQuery('#watermark_font_size').val();
		let wmFontFamilyValue = jQuery('#watermark_font_family').val();
		let wmCenterTextValue = jQuery('#watermark_center_horizontally:checked').length;

		wmFontSizeValue   = parseInt( wmFontSizeValue );
		wmCenterTextValue = parseInt( wmCenterTextValue );

		let cssProps = {
			'color': wmColorValue,
			'font-size': wmFontSizeValue + 'pt',
			'font-family': wmFontFamilyValue
		};

		if( wmCenterTextValue === 1 ) {
			cssProps['width']      = '640px';
			cssProps['text-align'] = 'center';
			cssProps['left']       = '0px';

			jQuery( '#draggable_watermark' ).trigger( 'dragstop' );
		} else {
			cssProps['width']      = '1px';
			cssProps['text-align'] = 'left';
		}

		draggableWmDiv.children('span').html( wmTextValue );
		draggableWmDiv.css( cssProps );
	},
	setFontFace: function( arg ) {
		let fontFace = jQuery(arg).val();
		let PUrl     = tsWatermark.purl;

		jQuery('#watermark_div style').remove();

		jQuery('#watermark_div').prepend("<style type=\"text/css\">" + 
            "@font-face {\n" +
                "\tfont-family: \"WatermarkFont\";\n" + 
                "\tsrc: url('" + PUrl + "/fonts/" + fontFace + ".ttf');\n" + 
            "}\n" + 
            "#draggable_watermark {\n" + 
                "\tfont-family: WatermarkFont !important;\n" + 
            "}\n" + 
        "</style>");

        createMoviePoster.setDraggableWatermarkValue();
	},
	getVideoSeekTime: function () {
	    seek.value = mid.currentTime;
	}
};

jQuery(document).ready(function() {
	jQuery( '#draggable_watermark' ).draggable({
		containment: '#movie_poster'
	});

	jQuery( '#draggable_watermark' ).on( 'dragstop', function( event, ui ) {
		let position = jQuery(this).position();
		let posX     = parseInt(position.left);
		let posY     = parseInt(position.top) + 25; // This is a hack

		let wmCenterTextValue = jQuery('#watermark_center_horizontally:checked').length;

		if( wmCenterTextValue === 1 ) {
			let spanWidth = jQuery('#draggable_watermark span').width();

			posX = (640-spanWidth)/2;
		}

		jQuery('#watermark_coord').attr('value', posX + '-' + posY);
	});

	jQuery( '#add_font_toggler' ).on('click', function( event ) {
		event.preventDefault();

		jQuery( '#upload_font_form' ).slideToggle( 'slow' );
	});

	createMoviePoster.setDraggableWatermarkValue();
});
