/* global ajaxurl */

'use strict';

jQuery( document ).ready(function($){

	// Refresh share counts button.
	$( document ).on( 'click', '#shared-counts-refresh', function( event ){

		event.preventDefault();

		var $this = $( this ),
			data  = {
				post_id: $this.data( 'postid' ),
				nonce:   $this.data( 'nonce' ),
				action:  'shared_counts_refresh'
			};

		// Disable refresh button and change text.
		$this.text( 'Loading share counts...' ).prop( 'disabled',true );

		// AJAX post to fetch updated counts.
		$.post( ajaxurl, data, function( res ) {

			if ( res.success ) {
				$( '#shared-counts-msg, #shared-counts-list, #shared-counts-date, #shared-counts-empty' ).remove();
				$( '#shared-counts-metabox .inside' ).prepend( res.data.date ).prepend( res.data.list ).prepend( '<p id="shared-counts-msg" class="'+res.data.class+'">'+res.data.msg+'</p>' );
			} else {
				$( '#shared-counts-msg' ).remove();
				$( '#shared-counts-metabox .inside' ).prepend( '<p id="shared-counts-msg" class="'+res.data.class+'">'+res.data.msg+'</p>' );
			}

			// Enable refresh button and change text.
			$this.text( 'Refresh Share Counts' ).prop( 'disabled',false );

		}).fail( function( xhr ) {
			console.log( xhr.responseText );
		});
	});
});
