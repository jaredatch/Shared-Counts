document.addEventListener('DOMContentLoaded', function() {

	// Google Analytics Social tracking.
	document.addEventListener('click', function(event) {
		var target = event.target;

		if (target.classList.contains('shared-counts-button')) {

			if (shared_counts.social_tracking) {

				var network = target.getAttribute('data-social-network'),
					action = target.getAttribute('data-social-action'),
					targetValue = target.getAttribute('data-social-target');

				if (network && action && targetValue) {
					if (typeof ga === 'function') {
						// Default GA.
						ga('send', 'social', network, action, targetValue);
					} else if (typeof __gaTracker === 'function') {
						// MonsterInsights.
						__gaTracker('send', 'social', network, action, targetValue);
					}
				}
			}
		}
	});

	// Share button click.
	document.addEventListener('click', function(event) {
		var target = event.target;

		if (target.classList.contains('shared-counts-button') && target.getAttribute('target') === '_blank' && !target.classList.contains('no-js')) {

			event.preventDefault();

			var window_size = '',
				url = target.href,
				domain = url.split('/')[2];

			switch (domain) {
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
			window.open(url, '', 'menubar=no,toolbar=no,resizable=yes,scrollbars=yes,' + window_size);

			target.dispatchEvent(new Event('shared-counts-click'));
		}
	});

});