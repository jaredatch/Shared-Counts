/* global ajaxurl, shared_counts */

'use strict';

jQuery( document ).ready( function( $ ){

	// Toggle share count URL group visibility.
	$( document ).on( 'click', '#shared-counts-metabox .count-group-toggle', function( event ) {

		event.preventDefault();

		$( this ).closest( '.count-group' ).find( '.count-details' ).slideToggle();
	});

	// Refresh share counts button. Or add a new URL. Or delete or URL.
	$( document ).on( 'click', '#shared-counts-metabox .shared-counts-refresh', function( event ){

		event.preventDefault();

		var addButton = false,
			delButton = false,
			groupName = false,
			groupURL  = false;

		if ( $( this ).hasClass( 'add' ) ) {

			// Check if we are adding a new URL before the refresh.
			addButton = true;

			// As for URL and nickname for the new group.
			groupURL  = window.prompt( shared_counts.url_prompt + '\n\n' + shared_counts.url_prompt_eg  + '\n\n' );
			groupName = window.prompt( shared_counts.name_prompt + '\n\n' + shared_counts.name_prompt_eg  + '\n\n' );

			// If the user didn't provide all the valid details, stop.
			if ( ! groupURL || ! groupName || '' === groupURL || '' === groupName ) {
				return;
			}

		} else if ( $( this ).hasClass( 'delete' ) ) {

			// Check if we are removing a URL before the refresh.
			delButton = true;

			if ( ! window.confirm( shared_counts.confirm_delete ) ) {
				return;
			}
		}

		var $this    = $( this ),
			$metabox = $( '#shared-counts-metabox' ),
			data     = {
				post_id: $this.data( 'postid' ),
				nonce:   $this.data( 'nonce' ),
				action:  'shared_counts_refresh'
			};

		if ( addButton ) {
			data.group_url  = groupURL;
			data.group_name = groupName;
			$this.text( shared_counts.adding );
		} else if ( delButton ) {
			data.group_delete = $this.data( 'group' );
		} else {
			$this.text( shared_counts.loading );
		}

		$metabox.find( 'button' ).prop( 'disabled', true );

		// AJAX post to fetch updated counts.
		$.post( ajaxurl, data, function( res ) {

			if ( res.success ) {
				// Remove out-dated share counts, empty notice, or messages.
				$metabox.find( '.count-group, .counts-empty, p.msg' ).remove();

				// Add updated share counts.
				$metabox.find( '.inside' ).prepend( res.data.counts );

				// Add any messages.
				$metabox.find( '.inside' ).prepend( '<p class="msg '+res.data.msgtype+'">'+res.data.msg+'</p>' );

				// Change last updated date.
				$metabox.find( '.counts-updated span' ).text( res.data.date );
			} else {
				// Remove previous messages.
				$metabox.find( 'p.msg' ).remove();

				// Add any messages.
				$metabox.find( '.inside' ).prepend( '<p class="msg '+res.data.msgtype+'">'+res.data.msg+'</p>' );
			}

			// Enable buttons and change text.
			if ( addButton ) {
				$this.text( shared_counts.add_url );
			} else if ( ! delButton ){
				$this.text( shared_counts.refresh );
			}
			$metabox.find( 'button' ).prop( 'disabled', false );

		}).fail( function( xhr ) {
			console.log( xhr.responseText );
		});
	});
});
