jQuery( document ).ready( function( $ ) {
	$( '.taxonomy-image-control a' ).live( 'click', function () {
		taxonomyImagesPlugin.tt_id = parseInt( $( this ).parent().find( 'input.tt_id' ).val() );
		taxonomyImagesPlugin.term_name = $( this ).parent().find( 'input.term_name' ).val();
		taxonomyImagesPlugin.image_id = parseInt( $( this ).parent().find( 'input.image_id' ).val() );
	} );

	$( '.taxonomy-image-control .remove' ).live( 'click', function () {
		$.ajax( {
			url: ajaxurl,
			type: "POST",
			dataType: 'json',
			data: {
				'action'   : 'sti_remove_association',
				'wp_nonce' : taxonomyImagesPlugin.nonce,
				'tt_id'    : taxonomyImagesPlugin.tt_id
				},
			cache: false,
			success: function ( response ) {
				if ( 'good' === response.status ) {
					$( '#remove-' + taxonomyImagesPlugin.tt_id ).addClass( 'hide' );
					$( '#taxonomy_image_plugin_' + taxonomyImagesPlugin.tt_id ).attr( 'src', taxonomyImagesPlugin.img_src );
				}
				else if ( 'bad' === response.status ) {
					alert( response.why );
				}
			}
		} );
		return false;
	} );
} );