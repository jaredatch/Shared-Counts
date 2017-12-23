/* global ajaxurl, shared_counts, grecaptcha */

'use strict';

jQuery( document ).ready(function($){

	var shared_counts_id,
		shared_counts_nonce;

	// Share button click.
	$( document ).on( 'click', '.shared-counts-button[target="_blank"]:not(.no-js)', function( event ) {

		event.preventDefault();

		var window_size = '',
			url         = this.href,
			domain      = url.split("/")[2];

		switch ( domain ) {
			case 'www.facebook.com':
				window_size = 'width=585,height=368';
				break;
			case 'twitter.com':
				window_size = 'width=585,height=261';
				break;
			case 'plus.google.com':
				window_size = 'width=517,height=511';
				break;
			case 'pinterest.com':
				window_size = 'width=750,height=550';
				break;
			default:
				window_size = 'width=585,height=515';
		}
		window.open( url, '', 'menubar=no,toolbar=no,resizable=yes,scrollbars=yes,' + window_size );

		$( this ).trigger( 'shared-counts-click' );
	});

	// Email share button, opens email modal.
	$( document ).on( 'click', 'a[href*="#shared-counts-email"]', function( event ) {

		event.preventDefault();

		// Show modal and focus on first field.
		$( '#shared-counts-modal-wrap' ).fadeIn();
		$( '#shared-counts-modal-recipient' ).focus();

		// Set data needed to send.
		shared_counts_id    = $( this ).data( 'postid' );
		shared_counts_nonce = $( this ).data( 'nonce' );

		// Maybe load reCAPTCHA.
		if ( shared_counts.recaptchaSitekey ) {
			grecaptcha.render( 'shared-counts-modal-recaptcha', {
				sitekey:  shared_counts.recaptchaSitekey
			} );
		}
	});

	// Close email modal.
	$( document ).on( 'click', '#shared-counts-modal-close', function( event ) {

		event.preventDefault();

		// Close modal and hide text indicating email was sent for future emails.
		$( '#shared-counts-modal-wrap' ).fadeOut();
		$( '#shared-counts-modal-sent' ).hide();
	});

	// Submit email share via email modal.
	$( document ).on( 'click', '#shared-counts-modal-submit', function( event ) {

		event.preventDefault();

		var empty       = false,
			$this       = $( this ),
			$recipient  = $( '#shared-counts-modal-recipient' ),
			$name       = $( '#shared-counts-modal-name' ),
			$email      = $( '#shared-counts-modal-email' ),
			$validation = $( '#shared-counts-modal-validation' ),
			$recaptcha  = $( '#g-recaptcha-response' ),
			data        = {
				action:    'shared_counts_email',
				postid:     shared_counts_id,
				recipient:  $recipient.val(),
				name:       $name.val(),
				email:      $email.val(),
				validation: $validation.val(),
				recaptcha:  $recaptcha.val(),
				nonce:      shared_counts_nonce
			};

		// Check if any of the required fields are empty.
		$( $recipient, $name, $email ).each(function() {
			if ( ! $( this ).val() || $( this ).val() === '' ) {
				empty = true;
			}
		});

		// If an empty field was found, alert user and stop.
		if ( empty ) {
			alert( 'Please complete out all 3 fields to email this article.' );
			return;
		}

		// Disable submit to prevent duplicates.
		$( this ).prop( 'disabled', true );

		// AJAX post.
		$.post( shared_counts.url, data, function( res ) {

			if ( res.success ){
				console.log( 'Article successfully shared.' );

				// Clear values for future shares.
				$( $recipient, $name, $email ).val( '' );

				alert( res.data );

				$( '#shared-counts-modal-wrap').fadeOut();

			} else {

				alert( res.data );
			}

			// Enable submit button.
			$this.prop( 'disabled', false );

		}).fail( function( xhr ) {
			console.log( xhr.responseText );
		});
	});
});
