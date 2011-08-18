jQuery(function(){
	jQuery( '.migrate_taxonomy_image' ).click( function( e ) {
		_self = jQuery( this );
		parent = _self.closest( 'tr' );
		e.preventDefault();

		if( !parent.hasClass('ajaxing') ) {
			jQuery.ajax({
				url: ajaxurl,
				dataType: 'json',
				data : { action: 'migrateFromTaxonomyImage' },
				beforeSend:function(){
					parent.addClass( 'ajaxing' );
				},
				success:function( result ){
					parent.removeClass( 'ajaxing' );
					_self.next().html( result.why ).addClass( result.status );
				}
			}
			);
		}
	});
})
