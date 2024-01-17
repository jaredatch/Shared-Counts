/* global shared_counts, grecaptcha */

'use strict';

jQuery( document ).ready(function($){

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

});
