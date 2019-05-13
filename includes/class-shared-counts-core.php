<?php
/**
 * Core class.
 *
 * Contains core functionality.
 *
 * @package    SharedCounts
 * @author     Bill Erickson & Jared Atchison
 * @since      1.0.0
 * @license    GPL-2.0+
 * @copyright  Copyright (c) 2019
 */
class Shared_Counts_Core {

	/**
	 * Flag to allow fetching Twitter share counts.
	 *
	 * The API used to fetch Twitter share counts returns the same count number
	 * for both HTTP and HTTPS queries. When the preserve non-HTTPS plugin
	 * setting is enabled, this flag lets us disable the API call for non-HTTP
	 * query checks, thus saving a request.
	 *
	 * @since 1.0.0
	 *
	 * @var bool
	 */
	public $twitter = true;

	/**
	 * Holds list of posts that need share count refreshed.
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	public $update_queue = [];

	/**
	 * Primary class constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		add_action( 'wp_ajax_shared_counts_email', [ $this, 'email_ajax' ] );
		add_action( 'wp_ajax_nopriv_shared_counts_email', [ $this, 'email_ajax' ] );
		add_action( 'shutdown', [ $this, 'shutdown_update_share_counts' ] );
	}

	/**
	 * Process and send email share AJAX requests.
	 *
	 * @since 1.0.0
	 */
	public function email_ajax() {

		$data = $_POST; // phpcs:ignore

		// Check spam honeypot.
		if ( ! empty( $data['validation'] ) ) {
			wp_send_json_error( __( 'Honeypot triggered.', 'shared-counts' ) );
		}

		// Check required fields.
		if ( empty( $data['recipient'] ) || empty( $data['name'] ) || empty( $data['email'] ) ) {
			wp_send_json_error( __( 'Required field missing.', 'shared-counts' ) );
		}

		// Check email addresses.
		if ( ! is_email( $data['recipient'] ) || ! is_email( $data['email'] ) ) {
			wp_send_json_error( __( 'Invalid email.', 'shared-counts' ) );
		}

		$options = shared_counts()->admin->options();

		// Confirm email sharing is enabled.
		if ( ! in_array( 'email', $options['included_services'], true ) ) {
			wp_send_json_error( __( 'Email not enabled.', 'shared-counts' ) );
		}

		// Check if reCAPTCHA is enabled.
		$recaptcha = ! empty( $options['recaptcha'] ) && ! empty( $options['recaptcha_site_key'] ) && ! empty( $options['recaptcha_secret_key'] );

		// reCAPTCHA is enabled, so verify it.
		if ( $recaptcha ) {

			if ( empty( $data['recaptcha'] ) ) {
				wp_send_json_error( __( 'reCAPTCHA is required.', 'shared-counts' ) );
			}

			$api_results = wp_remote_get( 'https://www.google.com/recaptcha/api/siteverify?secret=' . $options['recaptcha_secret_key'] . '&response=' . $data['recaptcha'] );
			$results     = json_decode( wp_remote_retrieve_body( $api_results ) );
			if ( empty( $results->success ) ) {
				wp_send_json_error( __( 'Incorrect reCAPTCHA, please try again.', 'shared-counts' ) );
			}
		}

		$post_id    = absint( $data['postid'] );
		$recipient  = sanitize_text_field( $data['recipient'] );
		$from_email = sanitize_text_field( $data['email'] );
		$from_name  = sanitize_text_field( $data['name'] );
		$site_name  = html_entity_decode( wp_strip_all_tags( get_bloginfo( 'name' ) ), ENT_QUOTES );
		$site_root  = strtolower( $_SERVER['SERVER_NAME'] ); //phpcs:ignore
		if ( substr( $site_root, 0, 4 ) === 'www.' ) {
			$site_root = substr( $site_root, 4 );
		}

		$headers = [
			sprintf( 'From: %s <noreply@%s>', $site_name, $site_root ),
			sprintf( 'Reply-To: %s <%s>', $from_name, $from_email ),
		];
		/* translators: %1$s - Name of the person who shared the article. */
		$subject = sprintf( esc_html__( 'Your friend %1$s has shared an article with you.', 'shared-counts' ), $from_name );
		$body    = html_entity_decode( get_the_title( $post_id ), ENT_QUOTES ) . "\r\n";
		$body   .= get_permalink( $post_id ) . "\r\n";

		wp_mail(
			$recipient,
			apply_filters( 'shared_counts_email_subject', $subject, $post_id, $recipient, $from_name, $from_email ),
			apply_filters( 'shared_counts_email_body',    $body,    $post_id, $recipient, $from_name, $from_email ),
			apply_filters( 'shared_counts_email_headers', $headers, $post_id, $recipient, $from_name, $from_email )
		);

		// Don't track email shares if plugin is configured to omit counts.
		if ( ! empty( $options['count_source'] ) && 'none' !== $options['count_source'] ) {
			$count  = absint( get_post_meta( $post_id, 'shared_counts_email', true ) );
			$update = update_post_meta( $post_id, 'shared_counts_email', ++$count );
		}

		wp_send_json_success();
	}

	/**
	 * Retreive share counts for site or post.
	 *
	 * @since 1.0.0
	 *
	 * @param int|string $id    Post or Site ID.
	 * @param bool       $array Return JSON.
	 * @param bool       $force Force refresh.
	 *
	 * @return object $share_count
	 */
	public function counts( $id = false, $array = false, $force = false ) {

		if ( 'site' === $id || 0 === strpos( $id, 'http' ) ) {
			// Primary site URL or Offsite/non post URL.
			$post_date    = true;
			$post_id      = false;
			$post_url     = 'site' === $id ? apply_filters( 'shared_counts_site_url', home_url() ) : esc_url( $id );
			$hash         = md5( $post_url );
			$share_option = get_option( 'shared_counts_urls', [] );
			$share_count  = ! empty( $share_option[ $hash ]['count'] ) ? $share_option[ $hash ]['count'] : false;
			$last_updated = ! empty( $share_option[ $hash ]['datetime'] ) ? $share_option[ $hash ]['datetime'] : false;

		} else {
			// Post type URL.
			$post_id      = $id ? $id : get_the_ID();
			$post_date    = get_the_date( 'U', $post_id );
			$post_url     = get_permalink( $post_id );
			$share_count  = get_post_meta( $post_id, 'shared_counts', true );
			$last_updated = get_post_meta( $post_id, 'shared_counts_datetime', true );
		}

		// Rebuild and update meta if necessary.
		if ( ! $share_count || ! $last_updated || $this->needs_updating( $last_updated, $post_date, $post_id ) || $force ) {

			$id = isset( $post_id ) ? $post_id : $id;

			$this->update_queue[ $id ] = $post_url;

			// If this update was forced then we process immediately. Otherwise
			// add the the queue which processes on shutdown (for now).
			if ( $force ) {
				$this->update_share_counts();
				$share_count = $this->counts( $id );
			}
		}

		if ( $share_count && true === $array ) {
			$share_count = json_decode( $share_count, true );
		}

		return $share_count;
	}

	/**
	 * Retreive a single share count for a site or post.
	 *
	 * @since 1.0.0
	 *
	 * @param int|string $id    Post or Site ID.
	 * @param string     $type  Count type.
	 * @param boolean    $echo  Echo or Return.
	 * @param int        $round How many significant digits on count.
	 *
	 * @return int
	 */
	public function count( $id = false, $type = 'facebook', $echo = false, $round = 2 ) {

		$counts = $this->counts( $id, true );
		$total  = $this->total_count( $counts );

		if ( false === $counts ) {
			$share_count = '0';
		} else {
			switch ( $type ) {
				case 'facebook':
					$share_count = isset( $counts['Facebook']['total_count'] ) ? $counts['Facebook']['total_count'] : '0';
					break;
				case 'facebook_likes':
					$share_count = isset( $counts['like_count'] ) ? $counts['like_count'] : '0';
					break;
				case 'facebook_shares':
					$share_count = isset( $counts['share_count'] ) ? $counts['share_count'] : '0';
					break;
				case 'facebook_comments':
					$share_count = isset( $counts['comment_count'] ) ? $counts['comment_count'] : '0';
					break;
				case 'twitter':
					$share_count = isset( $counts['Twitter'] ) ? $counts['Twitter'] : '0';
					break;
				case 'pinterest':
					$share_count = isset( $counts['Pinterest'] ) ? $counts['Pinterest'] : '0';
					break;
				case 'yummly':
					$share_count = isset( $counts['Yummly'] ) ? $counts['Yummly'] : '0';
					break;
				case 'included_total':
					$share_count = '0';
					$options     = shared_counts()->admin->options();
					// Service total only applies to services we are displaying.
					if ( ! empty( $options['included_services'] ) ) {
						foreach ( $options['included_services'] as $service ) {
							if ( 'included_total' !== $service ) {
								$share_count = $share_count + $this->count( $id, $service, false, false );
							}
						}
					}
					break;
				case 'print':
					$share_count = 0;
					break;
				case 'email':
					$share_count = absint( get_post_meta( $id, 'shared_counts_email', true ) );
					break;
				case 'total':
					$share_count = $total;
					break;
				default:
					$share_count = apply_filters( 'shared_counts_single', '0', $counts, $id, $type );
					break;
			}
		}

		if ( empty( $share_count ) ) {
			$share_count = '0';
		}

		if ( $round && $share_count >= 1000 ) {
			$share_count = $this->round_count( $share_count, $round );
		}

		if ( $echo ) {
			echo $share_count; // phpcs:ignore
		} else {
			return $share_count;
		}
	}

	/**
	 * Calculate total shares across all services.
	 *
	 * @since 1.0.0
	 *
	 * @param array $share_count All the counts.
	 *
	 * @return int $total_shares
	 */
	public function total_count( $share_count ) {

		if ( empty( $share_count ) || ! is_array( $share_count ) ) {
			return 0;
		}

		$total = 0;

		foreach ( $share_count as $service => $count ) {
			if ( is_int( $count ) ) {
				$total += (int) $count;
			} elseif ( is_array( $count ) && isset( $count['total_count'] ) ) {
				$total += (int) $count['total_count'];
			}
		}

		return apply_filters( 'shared_counts_total', $total, $share_count );
	}

	/**
	 * Round to Significant Figures.
	 *
	 * @since 1.0.0
	 *
	 * @param int $num Actual number.
	 * @param int $n   Significant digits to round to.
	 *
	 * @return $num rounded number.
	 */
	public function round_count( $num = 0, $n = 0 ) {

		if ( empty( $num ) ) {
			return 0;
		}

		$num       = (int) $num;
		$d         = ceil( log( $num < 0 ? -$num : $num, 10 ) );
		$power     = $n - $d;
		$magnitude = pow( 10, $power );
		$shifted   = round( $num * $magnitude );
		$output    = $shifted / $magnitude;

		if ( $output >= 1000000 ) {
			$output = $output / 1000000 . 'm';
		} elseif ( $output >= 1000 ) {
			$output = $output / 1000 . 'k';
		}

		return $output;
	}

	/**
	 * Check if share count needs updating.
	 *
	 * @since 1.0.0
	 *
	 * @param int       $last_updated Unix timestamp.
	 * @param int       $post_date    Unix timestamp.
	 * @param int|false $post_id      Post ID.
	 *
	 * @return bool
	 */
	public function needs_updating( $last_updated = false, $post_date, $post_id ) {

		if ( ! $last_updated ) {
			return true;
		}

		$update_increments = [
			[
				'post_date' => strtotime( '-1 day' ),
				'increment' => strtotime( '-30 minutes' ),
			],
			[
				'post_date' => strtotime( '-5 days' ),
				'increment' => strtotime( '-6 hours' ),
			],
			[
				'post_date' => 0,
				'increment' => strtotime( '-5 days' ),
			],
		];
		$update_increments = apply_filters( 'shared_counts_update_increments', $update_increments, $post_id );

		$increment = false;
		foreach ( $update_increments as $i ) {
			if ( $post_date > $i['post_date'] ) {
				$increment = $i['increment'];
				break;
			}
		}

		return $last_updated < $increment;
	}

	/**
	 * Query the Social Service APIs.
	 *
	 * @since 1.0.0
	 *
	 * @param string $url URL to lookup.
	 * @param string $id  Post or Site ID.
	 *
	 * @return object $share_count
	 */
	public function query_api( $url = false, $id = '' ) {

		if ( empty( $url ) ) {
			return;
		}

		$count_source = shared_counts()->admin->settings_value( 'count_source' );

		// Default share counts, filterable.
		$share_count = [
			'Facebook'  => [
				'share_count'   => 0,
				'like_count'    => 0,
				'comment_count' => 0,
				'total_count'   => 0,
			],
			'Twitter'   => 0,
			'Pinterest' => 0,
			'Yummly'    => 0,
			'LinkedIn'  => 0,
		];
		$share_count = apply_filters( 'shared_counts_default_counts', $share_count, $url, $id );

		if ( 'sharedcount' === $count_source ) {
			$share_count = $this->query_sharedcount_api( $url, $share_count );
		} elseif ( 'native' === $count_source ) {
			$share_count = $this->query_native_api( $url, $share_count );
		}

		$global_args = apply_filters(
			'shared_counts_api_params',
			[
				'url' => $url,
			]
		);

		// Modify API query results, or query additional APIs.
		$share_count = apply_filters( 'shared_counts_query_api', $share_count, $global_args, $url, $id );

		// Sanitize.
		array_walk_recursive( $share_count, 'absint' );

		// Final counts.
		return wp_json_encode( $share_count );
	}

	/**
	 * Retrieve counts from SharedCounts.com.
	 *
	 * @since 1.0.0
	 *
	 * @param string $url         URL to lookup.
	 * @param array  $share_count Current counts.
	 *
	 * @return array
	 */
	public function query_sharedcount_api( $url, $share_count ) {

		$api_key = shared_counts()->admin->settings_value( 'sharedcount_key' );

		if ( empty( $api_key ) ) {
			return $share_count;
		}

		// Fetch counts from SharedCount API.
		$global_args = apply_filters(
			'shared_counts_api_params',
			[
				'url' => $url,
			]
		);

		$api_query = add_query_arg(
			[
				'url'    => $global_args['url'],
				'apikey' => trim( $api_key ),
			],
			'https://api.sharedcount.com/v1.0/'
		);

		$api_response = wp_remote_get(
			$api_query,
			[
				'sslverify'  => false,
				'user-agent' => 'Shared Counts Plugin',
			]
		);

		if ( ! is_wp_error( $api_response ) && 200 === wp_remote_retrieve_response_code( $api_response ) ) {

			$results = json_decode( wp_remote_retrieve_body( $api_response ), true );

			// Update counts.
			$share_count['Facebook']['like_count']    = isset( $results['Facebook']['like_count'] ) ? $results['Facebook']['like_count'] : $share_count['Facebook']['like_count'];
			$share_count['Facebook']['comment_count'] = isset( $results['Facebook']['comment_count'] ) ? $results['Facebook']['comment_count'] : $share_count['Facebook']['comment_count'];
			$share_count['Facebook']['share_count']   = isset( $results['Facebook']['share_count'] ) ? $results['Facebook']['share_count'] : $share_count['Facebook']['share_count'];
			$share_count['Facebook']['total_count']   = isset( $results['Facebook']['total_count'] ) ? $results['Facebook']['total_count'] : $share_count['Facebook']['total_count'];
			$share_count['Pinterest']                 = isset( $results['Pinterest'] ) ? $results['Pinterest'] : $share_count['Pinterest'];
			$share_count['LinkedIn']                  = isset( $results['LinkedIn'] ) ? $results['LinkedIn'] : $share_count['LinkedIn'];
		}

		// Check if we also need to fetch Twitter counts.
		$twitter = shared_counts()->admin->settings_value( 'twitter_counts' );

		// Fetch Twitter counts if needed.
		if ( '1' === $twitter ) {
			$twitter_count          = $this->query_third_party_twitter_api( $global_args['url'] );
			$share_count['Twitter'] = false !== $twitter_count ? $twitter_count : $share_count['Twitter'];
		}

		// Check if we also need to fetch Yummly counts.
		$yummly = shared_counts()->admin->settings_value( 'yummly_counts' );

		// Fetch Yummly counts if needed.
		if ( '1' === $yummly ) {
			$yummly_count          = $this->query_yummly_api( $global_args['url'] );
			$share_count['Yummly'] = false !== $yummly_count ? $yummly_count : $share_count['Yummly'];
		}

		return $share_count;
	}

	/**
	 * Retrieve counts from third party for Twitter counts.
	 *
	 * @since 1.3.0
	 *
	 * @param string $url URL to lookup.
	 *
	 * @return int|false
	 */
	public function query_third_party_twitter_api( $url ) {

		if ( ! $this->twitter ) {
			return 0;
		}

		$args = apply_filters(
			'third_party_twitter_api',
			add_query_arg(
				[
					'url' => $url,
				],
				'https://counts.twitcount.com/counts.php'
			)
		);

		$api_response = wp_remote_get(
			$args,
			[
				'sslverify'  => false,
				'user-agent' => 'Shared Counts Plugin',
			]
		);

		if ( ! is_wp_error( $api_response ) && 200 === wp_remote_retrieve_response_code( $api_response ) ) {

			$body = json_decode( wp_remote_retrieve_body( $api_response ) );

			if ( isset( $body->count ) ) {
				return $body->count;
			}
		}

		return false;
	}

	/**
	 * Retrieve counts from Yummly.
	 *
	 * @since 1.1.0
	 *
	 * @param string $url URL to lookup.
	 *
	 * @return int|false
	 */
	public function query_yummly_api( $url ) {

		$args = add_query_arg(
			[
				'url' => $url,
			],
			'https://www.yummly.com/services/yum-count'
		);

		$api_response = wp_remote_get(
			$args,
			[
				'sslverify'  => false,
				'user-agent' => 'Shared Counts Plugin',
			]
		);

		if ( ! is_wp_error( $api_response ) && 200 === wp_remote_retrieve_response_code( $api_response ) ) {

			$body = json_decode( wp_remote_retrieve_body( $api_response ) );

			if ( isset( $body->count ) ) {
				return $body->count;
			}
		}

		return false;
	}

	/**
	 * Retrieve counts from SharedCounts.com.
	 *
	 * @since 1.0.0
	 *
	 * @param string $url         URL to lookup.
	 * @param array  $share_count Current counts.
	 *
	 * @return int|false
	 */
	public function query_native_api( $url, $share_count ) {

		$services = shared_counts()->admin->settings_value( 'query_services' );

		if ( empty( $services ) ) {
			return $share_count;
		}

		$global_args = apply_filters(
			'shared_counts_api_params',
			[
				'url' => $url,
			]
		);

		// Provide a filter so certain service queries can be bypassed. Helpful
		// if you want to run your own request against other APIs.
		$services = apply_filters( 'shared_counts_query_requests', $services, $global_args );

		if ( ! empty( $services ) ) {

			foreach ( $services as $service ) {

				switch ( $service ) {

					case 'facebook':
						$args = [
							'id' => rawurlencode( $global_args['url'] ),
						];

						$token = shared_counts()->admin->settings_value( 'fb_access_token' );
						if ( $token ) {
							$args['access_token'] = rawurlencode( $token );
						}

						$api_query = add_query_arg( $args, 'https://graph.facebook.com/' );

						$api_response = wp_remote_get(
							$api_query,
							[
								'sslverify'  => false,
								'user-agent' => 'Shared Counts Plugin',
							]
						);

						if ( ! is_wp_error( $api_response ) && 200 === wp_remote_retrieve_response_code( $api_response ) ) {

							$body = json_decode( wp_remote_retrieve_body( $api_response ) );

							// Not sure why Facebook returns the data in different formats sometimes.
							if ( isset( $body->shares ) ) {
								$share_count['Facebook']['share_count'] = $body->shares;
							} elseif ( isset( $body->share->share_count ) ) {
								$share_count['Facebook']['share_count'] = $body->share->share_count;
							}
							if ( isset( $body->comments ) ) {
								$share_count['Facebook']['comment_count'] = $body->comments;
							} elseif ( isset( $body->share->comment_count ) ) {
								$share_count['Facebook']['comment_count'] = $body->share->comment_count;
							}

							$share_count['Facebook']['like_count']  = $share_count['Facebook']['share_count'];
							$share_count['Facebook']['total_count'] = $share_count['Facebook']['share_count'] + $share_count['Facebook']['comment_count'];
						}
						break;

					case 'pinterest':
						$args = [
							'callback' => 'receiveCount',
							'url'      => $global_args['url'],
						];

						$api_query = add_query_arg( $args, 'https://api.pinterest.com/v1/urls/count.json' );

						$api_response = wp_remote_get(
							$api_query,
							[
								'sslverify'  => false,
								'user-agent' => 'Shared Counts Plugin',
							]
						);

						if ( ! is_wp_error( $api_response ) && 200 === wp_remote_retrieve_response_code( $api_response ) ) {

							$raw_json = preg_replace( '/^receiveCount\((.*)\)$/', "\\1", wp_remote_retrieve_body( $api_response ) );
							$body     = json_decode( $raw_json );

							if ( isset( $body->count ) ) {
								$share_count['Pinterest'] = $body->count;
							}
						}
						break;

					case 'yummly':
						$yummly_count          = $this->query_yummly_api( $global_args['url'] );
						$share_count['Yummly'] = false !== $yummly_count ? $yummly_count : $share_count['Yummly'];
						break;

					case 'twitter':
						$twitter_count          = $this->query_third_party_twitter_api( $global_args['url'] );
						$share_count['Twitter'] = false !== $twitter_count ? $twitter_count : $share_count['Twitter'];
						break;
				}
			}
		}

		return $share_count;
	}

	/**
	 * Update Share Counts.
	 *
	 * @since 1.0.0
	 */
	public function update_share_counts() {

		$count_source  = shared_counts()->admin->settings_value( 'count_source' );
		$preserve_http = shared_counts()->admin->settings_value( 'preserve_http' );

		if ( 'none' === $count_source ) {
			return;
		}

		$queue = apply_filters( 'shared_counts_update_queue', $this->update_queue );

		if ( ! empty( $queue ) ) {

			foreach ( $queue as $id => $post_url ) {

				$share_count = $this->query_api( $post_url, $id );

				if ( $share_count && ( 'site' === $id || 0 === strpos( $id, 'http' ) ) ) {

					$share_option                      = get_option( 'shared_counts_urls', [] );
					$hash                              = md5( $post_url );
					$share_option[ $hash ]['count']    = $share_count;
					$share_option[ $hash ]['datetime'] = time();
					$share_option[ $hash ]['url']      = $post_url;

					$total = $this->total_count( $share_count );

					if ( $total ) {
						$share_option[ $hash ]['total'] = $share_count;
					}

					update_option( 'shared_counts_urls', $share_option );

				} elseif ( $share_count ) {

					$groups = get_post_meta( $id, 'shared_counts_groups', true );
					$counts = [];

					if ( ! is_array( $groups ) ) {
						$groups = [];
					}

					// Maybe preserve old http share counts.
					if ( ! empty( $preserve_http ) && apply_filters( 'shared_counts_preserve_http', true, $id ) ) {

						// The current share counts are for the primary SSL URL.
						$groups['https']['name']   = esc_html__( 'HTTPS', 'shared-counts' );
						$groups['https']['counts'] = $share_count;
						$counts['https']           = json_decode( $share_count, true );
						$groups['https']['total']  = $this->total_count( $counts['https'] );

						// Now fetch the old HTTP counts.
						$this->twitter            = false;
						$groups['http']['name']   = esc_html__( 'HTTP', 'shared-counts' );
						$groups['http']['counts'] = $this->query_api( str_replace( 'https://', 'http://', $post_url ), $id );
						$counts['http']           = json_decode( $groups['http']['counts'], true );
						$groups['http']['total']  = $this->total_count( $counts['http'] );
						$this->twitter            = true;
					}

					if ( ! empty( $groups ) ) {
						foreach ( $groups as $slug => $group ) {
							// This skips the http/https stored counts since
							// they don't have a URL stored.
							if ( empty( $group['url'] ) ) {
								continue;
							}
							// Each URL group can future updates disabled. This
							// means don't look for share count updates after
							// the initial counts have been fetched. This
							// setting is useful when tracking old URLs which
							// have a 301 redirect to the current URL.
							if ( ! empty( $group['disable'] ) ) {
								continue;
							}
							$groups[ $slug ]['counts'] = $this->query_api( $group['url'], $id );
							$counts[ $slug ]           = json_decode( $groups[ $slug ]['counts'], true );
							$groups[ $slug ]['total']  = $this->total_count( $counts[ $slug ] );
						}

						// Update the groups count meta.
						update_post_meta( $id, 'shared_counts_groups', $groups );
					}

					// Check if we need to recalculate the total.
					if ( ! empty( $counts ) ) {
						$share_count = wp_json_encode( $this->calculate_totals( $counts ) );
					}

					// Update primary counts meta.
					update_post_meta( $id, 'shared_counts', $share_count );
					update_post_meta( $id, 'shared_counts_datetime', time() );

					$total = $this->total_count( json_decode( $share_count, true ) );

					if ( $total ) {
						update_post_meta( $id, 'shared_counts_total', $total );
					}
				}

				// After processing remove from queue.
				unset( $this->update_queue[ $id ] );
			}
		}
	}

	/**
	 * Update share counts on shutdown, after intial page rendering is complete.
	 *
	 * @since 1.3.0
	 */
	public function shutdown_update_share_counts() {

		// If fastcgi_finish_request is available, run it which will close to
		// browsers connection but allow the processing to continue in the
		// background.
		if ( function_exists( 'fastcgi_finish_request' ) ) {
			fastcgi_finish_request();
		}

		$this->update_share_counts();
	}

	/**
	 * Prime the pump.
	 *
	 * Ensure we have share count data for at least 100 posts.
	 * Useful when querying based on share count data.
	 *
	 * @link https://gist.github.com/billerickson/0f316f75430f3fd3a87c
	 * @since 1.1.0
	 *
	 * @param int  $count    How many posts should have sharing data.
	 * @param int  $interval How many should be updated at once.
	 * @param bool $messages Whether to display messages during the update.
	 */
	public function prime_the_pump( $count = 100, $interval = 20, $messages = false ) {

		$options = shared_counts()->admin->options();

		$current = new WP_Query(
			[
				'fields'         => 'ids',
				'post_type'      => $options['post_type'],
				'posts_per_page' => $count,
				'meta_query'     => [ // phpcs:ignore
					[
						'key'     => 'shared_counts',
						'compare' => 'EXISTS',
					],
				],
			]
		);
		$current = count( $current->posts );

		if ( $messages && function_exists( 'ea_pp' ) ) {
			ea_pp( 'Currently ' . $current . ' posts with share counts' );
		}

		if ( $current < $count ) {

			$update = new WP_Query(
				[
					'fields'         => 'ids',
					'post_type'      => $options['post_type'],
					'posts_per_page' => ( $count - $current ),
					'meta_query'     => [ // phpcs:ignore
						[
							'key'     => 'shared_counts',
							'value'   => 1,
							'compare' => 'NOT EXISTS',
						],
					],
				]
			);

			if ( $update->have_posts() ) {

				foreach ( $update->posts as $i => $post_id ) {
					if ( $interval > $i ) {
						$this->count( $post_id );
						do_action( 'shared_counts_primed', $post_id );
					}
				}

				if ( $messages && function_exists( 'ea_pp' ) ) {
					$total_updated = $interval > count( $update->posts ) ? count( $update->posts ) : $interval;
					ea_pp( 'Updated ' . $total_updated . ' posts with share counts' );
				}
			}
		}
	}

	/**
	 * Returns count groups combined.
	 *
	 * @since 1.0.0
	 * @author Justin Sternberg
	 *
	 * @param array $counts Counts to combine.
	 *
	 * @return array
	 */
	public function calculate_totals( $counts ) {

		return $this->combine_totals( $counts[ key( $counts ) ], $counts );
	}

	/**
	 * Combine and calculate all the different count groups.
	 *
	 * @since 1.0.0
	 * @author Justin Sternberg
	 *
	 * @param array $totals Total counts.
	 * @param array $counts Counts to combine.
	 *
	 * @return array
	 */
	public function combine_totals( $totals, $counts ) {

		foreach ( $totals as $key => $value ) {
			if ( ! is_array( $value ) ) {
				$value = 0;
				foreach ( $counts as $parent_key => $array_counts ) {
					if ( isset( $array_counts[ $key ] ) ) {
						$value += $array_counts[ $key ];
					}
				}

				$totals[ $key ] = $value;
			} else {
				$_counts = [];
				foreach ( $counts as $parent_key => $array_counts ) {
					if ( isset( $array_counts[ $key ] ) ) {
						$_counts[] = $array_counts[ $key ];
					}
				}
				$totals[ $key ] = $this->combine_totals( $value, $_counts );
			}
		}

		return $totals;
	}
}
