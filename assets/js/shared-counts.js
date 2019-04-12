/* global shared_counts, grecaptcha */

'use strict';

jQuery( document ).ready(function($){

	var modalOpen = false,
		shared_counts_id,
		shared_counts_nonce;

	/**
	 * Close email sharing modal.
	 *
	 * @since 1.0.0
	 */
	function modalClose() {

		modalOpen = false;

		$( '#shared-counts-modal-recipient, #shared-counts-modal-name, #shared-counts-modal-email' ).val( '' );
		$( '#shared-counts-modal-wrap' ).fadeOut();
		$( '#shared-counts-modal-sent' ).hide();
	}

	// Google Analytics Social tracking.
	$( document ).on( 'click', '.shared-counts-button', function() {

		if ( shared_counts.social_tracking ) {

			var $this   = $( this ),
				network = $this.data( 'social-network' ),
				action  = $this.data( 'social-action' ),
				target  = $this.data( 'social-target' );

			if ( network && action && target ) {
				if ( 'function' === typeof ga ) {
					// Default GA.
					ga( 'send', 'social', network, action, target );
				} else if ( 'function' === typeof __gaTracker ) {
					// MonsterInsights.
					__gaTracker( 'send', 'social', network, action, target );
				}
			}
		}
	});

	// Share button click.
	$( document ).on( 'click', '.shared-counts-button[target="_blank"]:not(.no-js)', function( event ) {

		event.preventDefault();

		var window_size = '',
			url         = this.href,
			domain      = url.split( '/' )[2];

		switch ( domain ) {
			case 'www.facebook.com':
				window_size = 'width=585,height=368';
				break;
			case 'twitter.com':
				window_size = 'width=585,height=261';
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

		modalOpen = true;

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

	// Close email modal by overlay or close button click.
	$( document ).on( 'click', '#shared-counts-modal-close, #shared-counts-modal-wrap', function( event ) {

		// If the modal wrap was clicked, verify it was the actual element, and
		// not something inside it.
		if ( 'shared-counts-modal-wrap' === $( this ).attr( 'id' ) && ! $( event.target ).is( '#shared-counts-modal-wrap' ) ) {
			return;
		}

		event.preventDefault();

		modalClose();
	});

	// Close email modal if Esc key is pressed while it is open.
	$( document ).keyup( function( event ) {
		if ( modalOpen && event.keyCode === 27 ) {
			modalClose();
		}
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
			alert( shared_counts.email_fields_required );
			return;
		}

		// Disable submit to prevent duplicates.
		$( this ).prop( 'disabled', true );

		// AJAX post.
		$.post( shared_counts.ajaxurl, data, function( res ) {

			if ( res.success ){
				console.log( shared_counts.email_sent );

				modalClose();
			}

			// Enable submit button.
			$this.prop( 'disabled', false );

		}).fail( function( xhr ) {
			console.log( xhr.responseText );
		});
	});
});
