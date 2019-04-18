/* global Choices, shared_counts */

'use strict';

jQuery( document ).ready(function($){

	// Conditional logic.
	$( '#shared-counts-settings-form' ).conditions( [
		// Sharecount.
		{
			conditions: {
				element:	'#shared-counts-setting-count_source',
				type:		'value',
				operator:	'=',
				condition:  'sharedcount'
			},
			actions: {
				if: {
					element:	'#shared-counts-setting-row-sharedcount_key, #shared-counts-setting-row-twitter_counts, #shared-counts-setting-row-yummly_counts',
					action:		'show'
				},
				else : {
					element:	'#shared-counts-setting-row-sharedcount_key, #shared-counts-setting-row-twitter_counts, #shared-counts-setting-row-yummly_counts',
					action:		'hide'
				}
			},
			effect: 'appear'
		},
		// Native counts.
		{
			conditions: {
				element:	'#shared-counts-setting-count_source',
				type:		'value',
				operator:	'=',
				condition:  'native'
			},
			actions: {
				if: {
					element:	'#shared-counts-setting-row-service, #shared-counts-setting-row-fb_access_token',
					action:		'show'
				},
				else: {
					element:	'#shared-counts-setting-row-service, #shared-counts-setting-row-fb_access_token',
					action:		'hide'
				}
			},
			effect: 'appear'
		},
		// Both SharedCounts and Native counts.
		{
			conditions: {
				element:	'#shared-counts-setting-count_source',
				type:		'value',
				operator:	'array',
				condition:  [ 'native', 'sharedcount' ]
			},
			actions: {
				if: {
					element:	'#shared-counts-setting-row-total_only, #shared-counts-setting-row-hide_empty, #shared-counts-setting-row-preserve_http',
					action:		'show'
				},
				else: {
					element:	'#shared-counts-setting-row-total_only, #shared-counts-setting-row-hide_empty, #shared-counts-setting-row-preserve_http',
					action:		'hide'
				}
			},
			effect: 'appear'
		},
		// Google reCAPTCHA.
		{
			conditions: {
				element:	'#shared-counts-setting-included_services',
				type:		'value',
				operator:	'array',
				condition:  [ 'email' ]
			},
			actions: {
				if: {
					element:	'#shared-counts-setting-row-recaptcha, #shared-counts-setting-row-recaptcha_site_key, #shared-counts-setting-row-recaptcha_secret_key',
					action:		'show'
				},
				else: {
					element:	'#shared-counts-setting-row-recaptcha, #shared-counts-setting-row-recaptcha_site_key, #shared-counts-setting-row-recaptcha_secret_key',
					action:		'hide'
				}
			},
			effect: 'appear'
		}
	] );

	// Service selctor.
	new Choices( $( '.shared-counts-services' )[0], {
		searchEnabled:    true,
		removeItemButton: true,
		placeholderValue: shared_counts.choices_placeholder,
		shouldSort:       false
	} );
});
