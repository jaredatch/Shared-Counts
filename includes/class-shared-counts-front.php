<?php
/**
 * Front-end class.
 *
 * Contains functionality for the site front-end.
 *
 * @package    SharedCounts
 * @author     Bill Erickson & Jared Atchison
 * @since      1.0.0
 * @license    GPL-2.0+
 * @copyright  Copyright (c) 2019
 */
class Shared_Counts_Front {

	/**
	 * Holds if a share link as been detected.
	 *
	 * @since 1.0.0
	 *
	 * @var boolean
	 */
	public $share_link = false;

	/**
	 * Theme location placements.
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	public $locations;

	/**
	 * Primary class constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		// Load assets.
		add_action( 'template_redirect', [ $this, 'theme_location' ], 99 );
		add_action( 'wp_enqueue_scripts', [ $this, 'header_assets' ], 9 );
		add_action( 'wp_footer', [ $this, 'load_assets' ], 1 );
		add_action( 'wp_footer', [ $this, 'email_modal' ], 50 );
		add_shortcode( 'shared_counts', [ $this, 'shortcode' ] );
		add_action( 'admin_bar_menu', [ $this, 'admin_bar' ], 999 );
	}

	/**
	 * Add share buttons to theme locations.
	 *
	 * @since 1.0.0
	 */
	public function theme_location() {

		// Genesis Hooks.
		if ( 'genesis' === get_template() ) {

			$locations = [
				'before' => [
					'hook'     => 'genesis_entry_header',
					'filter'   => false,
					'priority' => 13,
					'style'    => false,
				],
				'after'  => [
					'hook'     => 'genesis_entry_footer',
					'filter'   => false,
					'priority' => 8,
					'style'    => false,
				],
			];

		// Theme Hook Alliance.
		} elseif ( current_theme_supports( 'tha_hooks', [ 'entry' ] ) ) {

			$locations = [
				'before' => [
					'hook'     => 'tha_entry_top',
					'filter'   => false,
					'priority' => 13,
					'style'    => false,
				],
				'after'  => [
					'hook'     => 'tha_entry_bottom',
					'filter'   => false,
					'priority' => 8,
					'style'    => false,
				],
			];

		// Fallback to 'the_content'.
		} else {

			$locations = [
				'before' => [
					'hook'     => false,
					'filter'   => 'the_content',
					'priority' => 8,
					'style'    => false,
				],
				'after'  => [
					'hook'     => false,
					'filter'   => 'the_content',
					'priority' => 12,
					'style'    => false,
				],
			];
		}

		// Filter theme locations.
		$locations = apply_filters( 'shared_counts_theme_locations', $locations );

		// Make locations available everywhere.
		$this->locations = $locations;

		// Display share buttons before content.
		if ( $locations['before']['hook'] ) {
			add_action( $locations['before']['hook'], [ $this, 'display_before_content' ], $locations['before']['priority'] );
		} elseif ( $locations['before']['filter'] && ! is_feed() ) {
			add_filter( $locations['before']['filter'], [ $this, 'display_before_content_filter' ], $locations['before']['priority'] );
		}

		// Display share buttons after content.
		if ( $locations['after']['hook'] ) {
			add_action( $locations['after']['hook'], [ $this, 'display_after_content' ], $locations['after']['priority'] );
		} elseif ( $locations['after']['filter'] && ! is_feed() ) {
			add_filter( $locations['after']['filter'], [ $this, 'display_after_content_filter' ], $locations['after']['priority'] );
		}
	}

	/**
	 * Enqueue the assets earlier if possible.
	 *
	 * @since 1.0.0
	 */
	public function header_assets() {

		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		// Register assets.
		wp_register_style(
			'shared-counts',
			SHARED_COUNTS_URL . 'assets/css/shared-counts' . $suffix . '.css',
			[],
			SHARED_COUNTS_VERSION
		);

		wp_register_script(
			'shared-counts',
			SHARED_COUNTS_URL . 'assets/js/shared-counts' . $suffix . '.js',
			[ 'jquery' ],
			SHARED_COUNTS_VERSION,
			true
		);

		$options = shared_counts()->admin->options();

		if ( ! empty( $options['theme_location'] )
			&& ! empty( $options['post_type'] )
			&& is_singular( $options['post_type'] )
			&& ! get_post_meta( get_the_ID(), 'shared_counts_exclude', true )
		) {

			$this->share_link = true;
			$this->load_assets();
		}
	}

	/**
	 * Determines if assets need to be loaded.
	 *
	 * @since 1.0.0
	 */
	public function load_assets() {

		// Only continue if a share link was previously used in the page.
		if ( ! $this->share_link ) {
			return;
		}

		$options   = shared_counts()->admin->options();
		$recaptcha = ! empty( $options['recaptcha'] ) && ! empty( $options['recaptcha_site_key'] ) && ! empty( $options['recaptcha_secret_key'] );

		// Load CSS.
		if ( apply_filters( 'shared_counts_load_css', true ) ) {
			wp_enqueue_style( 'shared-counts' );
		}

		// Load JS.
		if ( apply_filters( 'shared_counts_load_js', true ) ) {

			wp_enqueue_script( 'shared-counts' );

			if ( $recaptcha ) {
				wp_enqueue_script(
					'recaptcha',
					'https://www.google.com/recaptcha/api.js',
					[],
					'2.0',
					true
				);
			}
		}

		// Localize JS strings.
		$args = [
			'email_fields_required' => esc_html__( 'Please complete out all 3 fields to email this article.', 'shared-counts' ),
			'email_sent'            => esc_html__( 'Article successfully shared.', 'shared-counts' ),
			'ajaxurl'               => esc_url_raw( admin_url( 'admin-ajax.php' ) ),
			'social_tracking'       => apply_filters( 'shared_counts_social_tracking', true ),
		];

		// Localize recaptcha site key if enabled.
		if ( $recaptcha ) {
			$args['recaptchaSitekey'] = sanitize_text_field( $options['recaptcha_site_key'] );
		}

		wp_localize_script( 'shared-counts', 'shared_counts', $args );
	}

	/**
	 * Email modal pop-up.
	 *
	 * This popup is output (and hidden) in the site footer if the Email
	 * service is configured in the plugin settings.
	 *
	 * @since 1.0.0
	 */
	public function email_modal() {

		// Only continue if a share link is on the page.
		if ( ! $this->share_link ) {
			return;
		}

		// Check to see the email button is configured or being overriden. The
		// filter can be used to enable the modal in use cases where the share
		// button is manually being called.
		$options = shared_counts()->admin->options();

		if ( ! in_array( 'email', $options['included_services'], true ) && ! apply_filters( 'shared_counts_email_modal', false ) ) {
			return;
		}

		// Check for reCAPTCHA settings.
		$recaptcha = ! empty( $options['recaptcha'] ) && ! empty( $options['recaptcha_site_key'] ) && ! empty( $options['recaptcha_secret_key'] );

		// Labels, filterable of course.
		$labels = apply_filters(
			'shared_counts_email_labels',
			[
				'title'      => esc_html__( 'Share this Article', 'shared-counts' ),
				'subtitle'   => esc_html__( 'Like this article? Email it to a friend!', 'shared-counts' ),
				'title_icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="48" height="45" viewBox="0 0 48 45"><path fill-rule="evenodd" d="M31.7849302,27.028093 L27.9750698,27.028093 L27.9750698,19.6751628 C27.9750698,18.7821395 27.9940465,17.9650233 28.0331163,17.2249302 C27.7986977,17.5095814 27.5062326,17.8065116 27.1579535,18.1179535 L25.5806512,19.4195349 L23.6327442,17.024 L28.4026047,13.1393488 L31.7849302,13.1393488 L31.7849302,27.028093 Z M22.6392558,21.4422326 L19.104,21.4422326 L19.104,24.8714419 L16.5488372,24.8714419 L16.5488372,21.4422326 L13.015814,21.4422326 L13.015814,18.896 L16.5488372,18.896 L16.5488372,15.4098605 L19.104,15.4098605 L19.104,18.896 L22.6392558,18.896 L22.6392558,21.4422326 Z M43.5996279,2 L4.40037209,2 C1.9735814,2 0,3.97469767 0,6.40037209 L0,32.8003721 C0,35.2260465 1.9735814,37.1996279 4.40037209,37.1996279 L22.3791628,37.1996279 L33.2796279,45.92 C33.5843721,46.1633488 33.9505116,46.2883721 34.3211163,46.2883721 C34.5689302,46.2883721 34.8178605,46.2325581 35.0511628,46.119814 C35.636093,45.8385116 36,45.2591628 36,44.6106047 L36,37.1996279 L43.5996279,37.1996279 C46.0253023,37.1996279 48,35.2260465 48,32.8003721 L48,6.40037209 C48,3.97469767 46.0253023,2 43.5996279,2 Z" transform="translate(0 -2)"/></svg>',
				'recipient'  => esc_html__( 'Friend\'s Email Address', 'shared-counts' ),
				'name'       => esc_html__( 'Your Name', 'shared-counts' ),
				'email'      => esc_html__( 'Your Email Address', 'shared-counts' ),
				'validation' => esc_html__( 'Comments', 'shared-counts' ),
				'close'      => '<span class="close-icon"><svg xmlns="http://www.w3.org/2000/svg" width="8" height="8" viewBox="0 0 8 8">
				<path fill="#FFF" fill-rule="evenodd" d="M338,11.0149385 L340.805644,8.20929447 C341.000906,8.01403233 341.317489,8.01403233 341.512751,8.20929447 L341.790706,8.48724919 C341.985968,8.68251134 341.985968,8.99909383 341.790706,9.19435597 L338.985062,12 L341.790706,14.805644 C341.985968,15.0009062 341.985968,15.3174887 341.790706,15.5127508 L341.512751,15.7907055 C341.317489,15.9859677 341.000906,15.9859677 340.805644,15.7907055 L338,12.9850615 L335.194356,15.7907055 C334.999094,15.9859677 334.682511,15.9859677 334.487249,15.7907055 L334.209294,15.5127508 C334.014032,15.3174887 334.014032,15.0009062 334.209294,14.805644 L337.014938,12 L334.209294,9.19435597 C334.014032,8.99909383 334.014032,8.68251134 334.209294,8.48724919 L334.487249,8.20929447 C334.682511,8.01403233 334.999094,8.01403233 335.194356,8.20929447 L338,11.0149385 Z" transform="translate(-334 -8)"/>
				</svg></span>',
				'submit'     => esc_html__( 'Send Email', 'shared-counts' ),
			]
		);
		?>
		<div id="shared-counts-modal-wrap" style="display:none;">
			<div class="shared-counts-modal">
				<a href="#" id="shared-counts-modal-close"><?php echo $labels['close']; // phpcs:ignore ?></a>
				<div class="shared-counts-modal-header">
					<?php
					if ( ! empty( $labels['title_icon'] ) ) {
						echo '<span class="shared-counts-modal-icon">' . $labels['title_icon'] . '</span>'; // phpcs:ignore
					}
					if ( ! empty( $labels['title'] ) ) {
						echo '<span class="shared-counts-modal-title">' . esc_html( $labels['title'] ) . '</span>';
					}
					if ( ! empty( $labels['subtitle'] ) ) {
						echo '<span class="shared-counts-modal-subtitle">' . esc_html( $labels['subtitle'] ) . '</span>';
					}
					?>
				</div>
				<div class="shared-counts-modal-content">
					<p>
						<label for="shared-counts-modal-recipient"><?php echo esc_html( $labels['recipient'] ); ?></label>
						<input type="email" id="shared-counts-modal-recipient" placeholder="<?php echo esc_html( $labels['recipient'] ); ?>">
					</p>
					<p>
						<label for="shared-counts-modal-name"><?php echo esc_html( $labels['name'] ); ?></label>
						<input type="text" id="shared-counts-modal-name" placeholder="<?php echo esc_html( $labels['name'] ); ?>">
					</p>
					<p>
						<label for="shared-counts-modal-email"><?php echo esc_html( $labels['email'] ); ?></label>
						<input type="email" id="shared-counts-modal-email" placeholder="<?php echo esc_html( $labels['email'] ); ?>">
					</p>
					<?php
					if ( $recaptcha ) {
						echo '<div id="shared-counts-modal-recaptcha"></div>';
					}
					?>
					<p class="shared-counts-modal-validation">
						<label for="shared-counts-modal-validation"><?php echo esc_html( $labels['validation'] ); ?></label>
						<input type="text" id="shared-counts-modal-validation" autocomplete="off">
					</p>
					<p class="shared-counts-modal-submit">
						<button id="shared-counts-modal-submit"><?php echo $labels['submit']; // phpcs:ignore ?></button>
					</p>
					<div id="shared-counts-modal-sent"><?php esc_html_e( 'Email sent!', 'shared-counts' ); ?></div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Display Share Counts based on plugin settings.
	 *
	 * @since 1.0.0
	 *
	 * @param string       $location Theme location.
	 * @param bool         $echo     Echo or return.
	 * @param string       $style    Desired style.
	 * @param int          $post_id  Post ID.
	 * @param string|array $services Specific services to display.
	 *
	 * @return null|string
	 */
	public function display( $location = '', $echo = true, $style = false, $post_id = false, $services = '' ) {

		$options = shared_counts()->admin->options();

		if ( empty( $services ) ) {
			$services = $options['included_services'];
		} elseif ( ! is_array( $services ) ) {
			$services = explode( ',', $services );
		}

		if ( ! $style && ! empty( $options['style'] ) ) {
			$style = esc_attr( $options['style'] );
		}

		$included_services = array_filter( apply_filters( 'shared_counts_display_services', $services, $location ) );
		$services_output   = '';

		foreach ( $included_services as $service ) {
			$services_output .= $this->link( trim( $service ), $post_id, false, $style );
		}

		$classes     = apply_filters( 'shared_counts_wrap_classes', [ 'shared-counts-wrap', $location, 'style-' . $style ] );
		$classes     = array_map( 'sanitize_html_class', array_filter( $classes ) );
		$links       = apply_filters( 'shared_counts_display', $services_output, $location );
		$wrap_format = apply_filters( 'shared_counts_display_wrap_format', '<div class="%2$s">%1$s</div>', $location );
		$output      = apply_filters( 'shared_counts_display_output', sprintf( $wrap_format, $links, join( ' ', $classes ) ), $location );

		if ( $echo ) {
			echo $output; // phpcs:ignore
		} else {
			return $output;
		}
	}

	/**
	 * Display Before Content.
	 *
	 * @since 1.0.0
	 */
	public function display_before_content() {

		$options = shared_counts()->admin->options();

		if (
			( 'before_content' === $options['theme_location'] || 'before_after_content' === $options['theme_location'] )
			&& ! empty( $options['post_type'] )
			&& is_singular( $options['post_type'] )
			&& ! get_post_meta( get_the_ID(), 'shared_counts_exclude', true )
		) {

			// Detect if we are using a hook or filter.
			if ( ! empty( $this->locations['before']['hook'] ) ) {
				$this->display( 'before_content', true, $this->locations['before']['style'] );
			} elseif ( ! empty( $this->locations['before']['filter'] ) ) {
				return $this->display( 'before_content', false, $this->locations['before']['style'] );
			}
		}
	}

	/**
	 * Display Before Content Filter.
	 *
	 * @since 1.0.0
	 *
	 * @param string $content Post content.
	 *
	 * @return string $content
	 */
	public function display_before_content_filter( $content ) {

		return $this->display_before_content() . $content;
	}

	/**
	 * Display After Content.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function display_after_content() {

		$options = shared_counts()->admin->options();

		if (
			( 'after_content' === $options['theme_location'] || 'before_after_content' === $options['theme_location'] )
			&& ! empty( $options['post_type'] )
			&& is_singular( $options['post_type'] )
			&& ! get_post_meta( get_the_ID(), 'shared_counts_exclude', true )
		) {

			// Detect if we are using a hook or filter.
			if ( ! empty( $this->locations['after']['hook'] ) ) {
				$this->display( 'after_content', true, $this->locations['after']['style'] );
			} elseif ( ! empty( $this->locations['after']['filter'] ) ) {
				return $this->display( 'after_content', false, $this->locations['after']['style'] );
			}
		}
	}

	/**
	 * Display After Content Filter.
	 *
	 * @since 1.0.0
	 *
	 * @param string $content Post content.
	 *
	 * @return string $content
	 */
	public function display_after_content_filter( $content ) {

		return $content . $this->display_after_content();
	}

	/**
	 * Generate sharing links.
	 *
	 * @since 1.0.0
	 *
	 * @param string     $types      Button type.
	 * @param int/string $id         Post or Site ID.
	 * @param boolean    $echo       Echo or return.
	 * @param string     $style      Button style.
	 * @param int        $round      How many significant digits on count.
	 * @param bool       $show_empty Show empty counts.
	 */
	public function link( $types = 'facebook', $id = false, $echo = true, $style = 'generic', $round = 2, $show_empty = '' ) {

		if ( ! $id ) {
			$id = get_the_ID();
		}

		$this->share_link = true;

		$types   = (array) $types;
		$output  = '';
		$options = shared_counts()->admin->options();
		$attr    = [ 'postid' => $id ];

		if ( empty( $show_empty ) ) {
			$show_empty = '1' === $options['hide_empty'] ? 'false' : 'true';
		}

		foreach ( $types as $type ) {

			// Discontinued.
			if ( in_array( $type, [ 'stumbleupon', 'google' ], true ) ) {
				continue;
			}

			$link          = [];
			$link['type']  = $type;
			$link['class'] = '';
			$link['img']   = apply_filters( 'shared_counts_default_image', '', $id, $link );

			if ( 'site' === $id ) {
				$link['url']   = esc_url( home_url() );
				$link['title'] = wp_strip_all_tags( get_bloginfo( 'name' ) );
			} elseif ( 0 === strpos( $id, 'http' ) ) {
				$link['url']   = esc_url( $id );
				$link['title'] = '';
			} else {
				$link['url']   = esc_url( get_permalink( $id ) );
				$link['title'] = wp_strip_all_tags( get_the_title( $id ) );
				if ( has_post_thumbnail( $id ) ) {
					$link['img'] = wp_get_attachment_image_url( get_post_thumbnail_id( $id ), 'full' );
				}
				$link['img'] = apply_filters( 'shared_counts_single_image', $link['img'], $id, $link );
			}
			$link['url']   = apply_filters( 'shared_counts_link_url', $link['url'] );
			$link['count'] = shared_counts()->core->count( $id, $type, false, $round );

			switch ( $type ) {
				case 'facebook':
					$link['link']           = 'https://www.facebook.com/sharer/sharer.php?u=' . $link['url'] . '&display=popup&ref=plugin&src=share_button';
					$link['label']          = esc_html__( 'Facebook', 'shared-counts' );
					$link['icon']           = '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="18.8125" height="32" viewBox="0 0 602 1024"><path d="M548 6.857v150.857h-89.714q-49.143 0-66.286 20.571t-17.143 61.714v108h167.429l-22.286 169.143h-145.143v433.714h-174.857v-433.714h-145.714v-169.143h145.714v-124.571q0-106.286 59.429-164.857t158.286-58.571q84 0 130.286 6.857z"></path></svg>';
					$link['target']         = '_blank';
					$link['rel']            = 'nofollow noopener noreferrer';
					$link['attr_title']     = esc_html__( 'Share on Facebook', 'shared-counts' );
					$link['social_network'] = 'Facebook';
					$link['social_action']  = 'Share';
					break;
				case 'facebook_likes':
					$link['link']           = 'https://www.facebook.com/plugins/like.php?href=' . $link['url'];
					$link['label']          = esc_html__( 'Like', 'shared-counts' );
					$link['icon']           = '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="18.8125" height="32" viewBox="0 0 602 1024"><path d="M548 6.857v150.857h-89.714q-49.143 0-66.286 20.571t-17.143 61.714v108h167.429l-22.286 169.143h-145.143v433.714h-174.857v-433.714h-145.714v-169.143h145.714v-124.571q0-106.286 59.429-164.857t158.286-58.571q84 0 130.286 6.857z"></path></svg>';
					$link['target']         = '_blank';
					$link['rel']            = 'nofollow noopener noreferrer';
					$link['attr_title']     = esc_html__( 'Like on Facebook', 'shared-counts' );
					$link['social_network'] = 'Facebook';
					$link['social_action']  = 'Like';
					break;
				case 'facebook_shares':
					$link['link']           = 'https://www.facebook.com/sharer/sharer.php?u=' . $link['url'] . '&display=popup&ref=plugin&src=share_button';
					$link['label']          = esc_html__( 'Share', 'shared-counts' );
					$link['icon']           = '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="18.8125" height="32" viewBox="0 0 602 1024"><path d="M548 6.857v150.857h-89.714q-49.143 0-66.286 20.571t-17.143 61.714v108h167.429l-22.286 169.143h-145.143v433.714h-174.857v-433.714h-145.714v-169.143h145.714v-124.571q0-106.286 59.429-164.857t158.286-58.571q84 0 130.286 6.857z"></path></svg>';
					$link['target']         = '_blank';
					$link['rel']            = 'nofollow noopener noreferrer';
					$link['attr_title']     = esc_html__( 'Share on Facebook', 'shared-counts' );
					$link['social_network'] = 'Facebook';
					$link['social_action']  = 'Share';
					break;
				case 'twitter':
					$link['link']           = 'https://twitter.com/share?url=' . $link['url'] . '&text=' . htmlspecialchars( rawurlencode( html_entity_decode( $link['title'], ENT_COMPAT, 'UTF-8' ) ), ENT_COMPAT, 'UTF-8' );
					$link['label']          = esc_html__( 'Tweet', 'shared-counts' );
					$link['icon']           = '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="29.71875" height="32" viewBox="0 0 951 1024"><path d="M925.714 233.143q-38.286 56-92.571 95.429 0.571 8 0.571 24 0 74.286-21.714 148.286t-66 142-105.429 120.286-147.429 83.429-184.571 31.143q-154.857 0-283.429-82.857 20 2.286 44.571 2.286 128.571 0 229.143-78.857-60-1.143-107.429-36.857t-65.143-91.143q18.857 2.857 34.857 2.857 24.571 0 48.571-6.286-64-13.143-106-63.714t-42-117.429v-2.286q38.857 21.714 83.429 23.429-37.714-25.143-60-65.714t-22.286-88q0-50.286 25.143-93.143 69.143 85.143 168.286 136.286t212.286 56.857q-4.571-21.714-4.571-42.286 0-76.571 54-130.571t130.571-54q80 0 134.857 58.286 62.286-12 117.143-44.571-21.143 65.714-81.143 101.714 53.143-5.714 106.286-28.571z"></path></svg>';
					$link['target']         = '_blank';
					$link['rel']            = 'nofollow noopener noreferrer';
					$link['attr_title']     = esc_html__( 'Share on Twitter', 'shared-counts' );
					$link['social_network'] = 'Twitter';
					$link['social_action']  = 'Tweet';
					break;
				case 'pinterest':
					$link['link']           = 'https://pinterest.com/pin/create/button/?url=' . $link['url'] . '&media=' . $link['img'] . '&description=' . $link['title'];
					$link['label']          = esc_html__( 'Pin', 'shared-counts' );
					$link['icon']           = '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="22.84375" height="32" viewBox="0 0 731 1024"><path d="M0 341.143q0-61.714 21.429-116.286t59.143-95.143 86.857-70.286 105.714-44.571 115.429-14.857q90.286 0 168 38t126.286 110.571 48.571 164q0 54.857-10.857 107.429t-34.286 101.143-57.143 85.429-82.857 58.857-108 22q-38.857 0-77.143-18.286t-54.857-50.286q-5.714 22.286-16 64.286t-13.429 54.286-11.714 40.571-14.857 40.571-18.286 35.714-26.286 44.286-35.429 49.429l-8 2.857-5.143-5.714q-8.571-89.714-8.571-107.429 0-52.571 12.286-118t38-164.286 29.714-116q-18.286-37.143-18.286-96.571 0-47.429 29.714-89.143t75.429-41.714q34.857 0 54.286 23.143t19.429 58.571q0 37.714-25.143 109.143t-25.143 106.857q0 36 25.714 59.714t62.286 23.714q31.429 0 58.286-14.286t44.857-38.857 32-54.286 21.714-63.143 11.429-63.429 3.714-56.857q0-98.857-62.571-154t-163.143-55.143q-114.286 0-190.857 74t-76.571 187.714q0 25.143 7.143 48.571t15.429 37.143 15.429 26 7.143 17.429q0 16-8.571 41.714t-21.143 25.714q-1.143 0-9.714-1.714-29.143-8.571-51.714-32t-34.857-54-18.571-61.714-6.286-60.857z"></path></svg>';
					$link['target']         = '_blank';
					$link['rel']            = 'nofollow noopener noreferrer';
					$link['attr_title']     = esc_html__( 'Share on Pinterest', 'shared-counts' );
					$link['social_network'] = 'Pinterest';
					$link['social_action']  = 'Pin';
					break;
				case 'yummly':
					$link['link']           = 'https://www.yummly.com/urb/verify?url=' . $link['url'] . '&title=' . rawurlencode( $link['title'] ) . '&yumtype=button';
					$link['label']          = esc_html__( 'Yummly', 'shared-counts' );
					$link['icon']           = '<svg xmlns="http://www.w3.org/2000/svg" height="32" width="32" viewBox="0 0 32 32"><path d="M27.127 21.682c-.015-.137-.132-.213-.216-.236-.21-.06-.43-.01-1.06-.29-.51-.23-2.875-1.37-6.13-1.746l2.357-13.426c.105-.602.1-1.087-.09-1.394-.277-.45-.886-.573-1.586-.514-.545.05-.98.25-1.07.312a.325.325 0 0 0-.145.288c.023.253.22.45.057 1.425-.032.21-.802 4.505-1.453 8.14-1.724 1.038-4.018 1.527-4.488.905-.228-.31-.177-.89.04-1.757.05-.193 1.06-4.03 1.347-5.135.54-2.105.13-4.035-2.05-4.23-1.88-.17-3.676.935-4.216 1.51-.39.415-.26.916.09 1.52.275.473.642.78.735.836.115.07.263.07.32.01.63-.71 1.775-1.243 2.173-.915.35.29.216.83.08 1.35 0 0-1.227 4.606-1.723 6.526-.366 1.417-.007 2.705 1.027 3.32.77.473 1.914.435 2.816.32 1.96-.24 3.11-1.066 3.296-1.208l-.363 2.02s-2.214.2-4.027 1.286c-2.383 1.428-3.345 4.673-1.82 6.347 1.526 1.674 4.193 1.04 5.277.308 1.045-.7 2.407-2.18 3.023-5.535 3.596.184 4.53 2.046 6.063 2.113 1.1.048 1.876-1.014 1.737-2.142zm-12.23 3.214c-.51.297-1.03.337-1.35-.03-.337-.388-.435-2.5 2.914-3.13.005 0-.523 2.56-1.56 3.16z"></path></svg>';
					$link['target']         = '_blank';
					$link['rel']            = 'nofollow noopener noreferrer';
					$link['attr_title']     = esc_html__( 'Share on Yummly', 'shared-counts' );
					$link['social_network'] = 'Yummly';
					$link['social_action']  = 'Saved';
					break;
				case 'linkedin':
					$link['link']           = 'https://www.linkedin.com/shareArticle?mini=true&url=' . $link['url'];
					$link['label']          = esc_html__( 'LinkedIn', 'shared-counts' );
					$link['icon']           = '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="27.4375" height="32" viewBox="0 0 878 1024"><path d="M199.429 357.143v566.286h-188.571v-566.286h188.571zM211.429 182.286q0.571 41.714-28.857 69.714t-77.429 28h-1.143q-46.857 0-75.429-28t-28.571-69.714q0-42.286 29.429-70t76.857-27.714 76 27.714 29.143 70zM877.714 598.857v324.571h-188v-302.857q0-60-23.143-94t-72.286-34q-36 0-60.286 19.714t-36.286 48.857q-6.286 17.143-6.286 46.286v316h-188q1.143-228 1.143-369.714t-0.571-169.143l-0.571-27.429h188v82.286h-1.143q11.429-18.286 23.429-32t32.286-29.714 49.714-24.857 65.429-8.857q97.714 0 157.143 64.857t59.429 190z"></path></svg>';
					$link['target']         = '_blank';
					$link['rel']            = 'nofollow noopener noreferrer';
					$link['attr_title']     = esc_html__( 'Share on LinkedIn', 'shared-counts' );
					$link['social_network'] = 'LinkedIn';
					$link['social_action']  = 'Share';
					break;
				case 'included_total':
					$link['link']  = '';
					$link['label'] = _n( 'Share', 'Shares', $link['count'], 'shared-counts' );
					$link['icon']  = '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="27.4375" height="32" viewBox="0 0 878 1024"><path d="M694.857 585.143q76 0 129.429 53.429t53.429 129.429-53.429 129.429-129.429 53.429-129.429-53.429-53.429-129.429q0-6.857 1.143-19.429l-205.714-102.857q-52.571 49.143-124.571 49.143-76 0-129.429-53.429t-53.429-129.429 53.429-129.429 129.429-53.429q72 0 124.571 49.143l205.714-102.857q-1.143-12.571-1.143-19.429 0-76 53.429-129.429t129.429-53.429 129.429 53.429 53.429 129.429-53.429 129.429-129.429 53.429q-72 0-124.571-49.143l-205.714 102.857q1.143 12.571 1.143 19.429t-1.143 19.429l205.714 102.857q52.571-49.143 124.571-49.143z"></path></svg>';
					break;
				case 'print':
					$link['link']           = 'javascript:window.print()';
					$link['label']          = esc_html__( 'Print', 'shared-counts' );
					$link['icon']           = '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="29.71875" height="32" viewBox="0 0 951 1024"><path d="M219.429 877.714h512v-146.286h-512v146.286zM219.429 512h512v-219.429h-91.429q-22.857 0-38.857-16t-16-38.857v-91.429h-365.714v365.714zM877.714 548.571q0-14.857-10.857-25.714t-25.714-10.857-25.714 10.857-10.857 25.714 10.857 25.714 25.714 10.857 25.714-10.857 10.857-25.714zM950.857 548.571v237.714q0 7.429-5.429 12.857t-12.857 5.429h-128v91.429q0 22.857-16 38.857t-38.857 16h-548.571q-22.857 0-38.857-16t-16-38.857v-91.429h-128q-7.429 0-12.857-5.429t-5.429-12.857v-237.714q0-45.143 32.286-77.429t77.429-32.286h36.571v-310.857q0-22.857 16-38.857t38.857-16h384q22.857 0 50.286 11.429t43.429 27.429l86.857 86.857q16 16 27.429 43.429t11.429 50.286v146.286h36.571q45.143 0 77.429 32.286t32.286 77.429z"></path></svg>';
					$link['attr_title']     = esc_html__( 'Print this Page', 'shared-counts' );
					$link['social_network'] = 'Print';
					$link['social_action']  = 'Printed';
					break;
				case 'email':
					$link['link']           = '#shared-counts-email';
					$link['label']          = esc_html__( 'Email', 'shared-counts' );
					$link['icon']           = '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 1024 1024"><path d="M1024 405.714v453.714q0 37.714-26.857 64.571t-64.571 26.857h-841.143q-37.714 0-64.571-26.857t-26.857-64.571v-453.714q25.143 28 57.714 49.714 206.857 140.571 284 197.143 32.571 24 52.857 37.429t54 27.429 62.857 14h1.143q29.143 0 62.857-14t54-27.429 52.857-37.429q97.143-70.286 284.571-197.143 32.571-22.286 57.143-49.714zM1024 237.714q0 45.143-28 86.286t-69.714 70.286q-214.857 149.143-267.429 185.714-5.714 4-24.286 17.429t-30.857 21.714-29.714 18.571-32.857 15.429-28.571 5.143h-1.143q-13.143 0-28.571-5.143t-32.857-15.429-29.714-18.571-30.857-21.714-24.286-17.429q-52-36.571-149.714-104.286t-117.143-81.429q-35.429-24-66.857-66t-31.429-78q0-44.571 23.714-74.286t67.714-29.714h841.143q37.143 0 64.286 26.857t27.143 64.571z"></path></svg>';
					$link['attr_title']     = 'Share via Email';
					$link['class']         .= ' no-scroll';
					$link['social_network'] = 'Email';
					$link['social_action']  = 'Emailed';
					break;
			}

			$data       = '';
			$link       = apply_filters( 'shared_counts_link', $link, $id, $style );
			$link_class = ! empty( $link['class'] ) ? implode( ' ', array_map( 'sanitize_html_class' , explode( ' ', $link['class'] ) ) ) : '';
			$target     = ! empty( $link['target'] ) ? ' target="' . esc_attr( $link['target'] ) . '" ' : '';
			$rel        = ! empty( $link['rel'] ) ? ' rel="' . esc_attr( $link['rel'] ) . '" ' : '';
			$attr_title = ! empty( $link['attr_title'] ) ? ' title="' . esc_attr( $link['attr_title'] ) . '" ' : '';
			$show_count = true;
			$elements   = [];

			// Add classes.
			$css_classes = [
				'shared-counts-button',
				sanitize_html_class( $link['type'] ),
			];
			$css_classes = array_merge( $css_classes, explode( ' ', $link['class'] ) );
			if ( empty( $link['count'] ) || ( '1' === $options['total_only'] && 'included_total' !== $type ) ) {
				$css_classes[] = 'shared-counts-no-count';
			}
			$css_classes = array_map( 'sanitize_html_class', $css_classes );
			$css_classes = implode( ' ', array_filter( $css_classes ) );

			// Prevent Pinterest JS from hijacking our button.
			if ( 'pinterest' === $type ) {
				$attr['pin-do'] = 'none';
			}

			// Social interaction data attributes - used for GA social tracking.
			if ( apply_filters( 'shared_counts_social_tracking', true ) ) {
				if ( ! empty( $link['social_network'] ) ) {
					$attr['social-network'] = $link['social_network'];
				}
				if ( ! empty( $link['social_action'] ) ) {
					$attr['social-action'] = $link['social_action'];
				}
				if ( ! empty( $link['url'] ) ) {
					$attr['social-target'] = $link['url'];
				}
			}

			// Add data attribues.
			$attr = apply_filters( 'shared_counts_link_data', $attr, $link, $id );
			if ( ! empty( $attr ) ) {
				foreach ( $attr as $key => $val ) {
					$data .= ' data-' . sanitize_html_class( $key ) . '="' . esc_attr( $val ) . '"';
				}
			}

			// Determine if we should show the count.
			if ( 'false' === $show_empty && 0 == $link['count'] ) { //phpcs:ignore
				$show_count = false;
			}
			if ( '1' === $options['total_only'] && 'included_total' !== $type ) {
				$show_count = false;
			}

			// Hide Total Counts button if empty and "Hide Empty Counts" setting
			// is enabled.
			if ( 'included_total' === $type && 0 === absint( $link['count'] ) && 'false' === $show_empty ) {
				continue;
			}

			// Build button output.
			if ( 'included_total' === $type ) {
				$elements['wrap_open']  = sprintf(
					'<span class="%s"%s>',
					$css_classes,
					$data
				);
				$elements['wrap_close'] = '</span>';

			} else {
				$elements['wrap_open']  = sprintf(
					'<a href="%s"%s%s%s class="%s"%s>',
					esc_attr( $link['link'] ),
					$attr_title,
					$target,
					$rel,
					$css_classes,
					$data
				);
				$elements['wrap_close'] = '</a>';
			}

			$elements['icon']       = '<span class="shared-counts-icon">' . $link['icon'] . '</span>';
			$elements['label']      = '<span class="shared-counts-label">' . $link['label'] . '</span>';
			$elements['icon_label'] = '<span class="shared-counts-icon-label">' . $elements['icon'] . $elements['label'] . '</span>';
			$elements['count']      = $show_count ? '<span class="shared-counts-count">' . $link['count'] . '</span>' : '';
			$elements               = apply_filters( 'shared_counts_output_elements', $elements , $link, $id );
			$output                .= $elements['wrap_open'] . $elements['icon_label'] . $elements['count'] . $elements['wrap_close'];
		}

		if ( true === $echo ) {
			echo $output; // phpcs:ignore
		} else {
			return $output;
		}
	}

	/**
	 * Display share counts via shortcode.
	 *
	 * @since 1.0.0
	 *
	 * @param array $atts Shortcode atts.
	 *
	 * @return string
	 */
	public function shortcode( $atts = [] ) {

		$atts = shortcode_atts(
			[
				'location' => 'shortcode',
				'style'    => false,
				'services' => '',
			],
			$atts,
			'shared_counts'
		);

		// Don't show or include the share badges in the feed, since they won't
		// display well.
		if ( ! is_feed() ) {
			return $this->display( esc_attr( $atts['location'] ), false, esc_attr( $atts['style'] ), get_the_ID(), $atts['services'] );
		}
	}

	/**
	 * Admin bar stats.
	 *
	 * @since 1.3.0
	 *
	 * @param object $wp_admin_bar WordPress admin bar object.
	 */
	public function admin_bar( $wp_admin_bar ) {

		if ( ! is_singular() ) {
			return;
		}

		$settings = apply_filters(
			'shared_counts_admin_bar',
			[
				'capability' => 'manage_options',
				'round'      => 2,
				'show'       => true,
				'details'    => true,
				'refresh'    => true,
			]
		);

		if ( ! $settings['show'] || ! current_user_can( $settings['capability'] ) ) {
			return;
		}

		if ( $settings['refresh'] && isset( $_GET['shared_counts_refresh'] ) ) { //phpcs:ignore
			shared_counts()->core->counts( get_the_ID(), true, true );
		}

		$options = shared_counts()->admin->options();

		if (
			empty( $options['post_type'] ) ||
			! is_singular( $options['post_type'] ) ||
			get_post_meta( get_the_ID(), 'shared_counts_exclude', true )
		) {
			return;
		}

		$icon    = '<svg xmlns="http://www.w3.org/2000/svg" width="16" viewBox="0 0 20 19" style="display:inline-block;vertical-align:middle;margin:0 6px 0 0;"><path fill="#a0a5aa" fill-rule="evenodd" d="M13.2438425,10.4284937 L11.6564007,10.4284937 L11.6564007,7.36477277 C11.6564007,6.99267974 11.6643076,6.65221463 11.6805867,6.34384253 C11.5829123,6.46244718 11.4610518,6.58616811 11.3159356,6.71593556 L10.6587263,7.25826114 L9.84709835,6.2601216 L11.8345402,4.64151695 L13.2438425,4.64151695 L13.2438425,10.4284937 Z M9.43314486,8.10105184 L7.9601216,8.10105184 L7.9601216,9.52988904 L6.89547044,9.52988904 L6.89547044,8.10105184 L5.42337742,8.10105184 L5.42337742,7.0401216 L6.89547044,7.0401216 L6.89547044,5.58756346 L7.9601216,5.58756346 L7.9601216,7.0401216 L9.43314486,7.0401216 L9.43314486,8.10105184 Z M18.1666332,0.000121602787 L1.83360997,0.000121602787 C0.822447184,0.000121602787 0.000121602787,0.8229123 0.000121602787,1.83360997 L0.000121602787,12.83361 C0.000121602787,13.8443076 0.822447184,14.6666332 1.83360997,14.6666332 L9.32477277,14.6666332 L13.8666332,18.3001216 C13.99361,18.401517 14.1461681,18.45361 14.3005867,18.45361 C14.4038425,18.45361 14.5075635,18.4303542 14.6047728,18.3833774 C14.8484937,18.2661681 15.0001216,18.0247728 15.0001216,17.7545402 L15.0001216,14.6666332 L18.1666332,14.6666332 C19.1773309,14.6666332 20.0001216,13.8443076 20.0001216,12.83361 L20.0001216,1.83360997 C20.0001216,0.8229123 19.1773309,0.000121602787 18.1666332,0.000121602787 Z"/></svg>';
		$total   = get_post_meta( get_the_ID(), 'shared_counts_total', true );
		$total   = ! empty( $total ) ? absint( $total ) : 0;
		$updated = get_post_meta( get_the_ID(), 'shared_counts_datetime', true );

		if ( $total >= 1000 ) {
			$total = shared_counts()->core->round_count( $total, $settings['round'] );
		}

		if ( ! empty( $updated ) ) {
			$updated = ' <span style="opacity:0.4;">(' . human_time_diff( $updated, time() ) . ')</span>';
		}

		$menu = [
			[
				'id'    => 'shared_counts',
				'title' => $icon . $total . $updated,
				'href'  => $settings['refresh'] ? esc_url( add_query_arg( 'shared_counts_refresh', '1' ) ) : false,
			],
		];

		if ( $settings['details'] ) {

			$counts  = json_decode( get_post_meta( get_the_ID(), 'shared_counts', true ), true );
			$details = [
				[
					'id'     => 'shared_counts_facebook_total',
					'parent' => 'shared_counts',
					'title'  => esc_html__( 'Facebook Total:', 'shared-counts' ) . ' ' . ( ! empty( $counts['Facebook']['total_count'] ) ? number_format( absint( $counts['Facebook']['total_count'] ) ) : '0' ),
				],
				[
					'id'     => 'shared_counts_facebook_likes',
					'parent' => 'shared_counts',
					'title'  => esc_html__( 'Facebook Likes:', 'shared-counts' ) . ' ' . ( ! empty( $counts['Facebook']['like_count'] ) ? number_format( absint( $counts['Facebook']['like_count'] ) ) : '0' ),
				],
				[
					'id'     => 'shared_counts_facebook_shares',
					'parent' => 'shared_counts',
					'title'  => esc_html__( 'Facebook Shares:', 'shared-counts' ) . ' ' . ( ! empty( $counts['Facebook']['share_count'] ) ? number_format( absint( $counts['Facebook']['share_count'] ) ) : '0' ),
				],
				[
					'id'     => 'shared_counts_facebook_comments',
					'parent' => 'shared_counts',
					'title'  => esc_html__( 'Facebook Comments:', 'shared-counts' ) . ' ' . ( ! empty( $counts['Facebook']['comment_count'] ) ? number_format( absint( $counts['Facebook']['comment_count'] ) ) : '0' ),
				],
				[
					'id'     => 'shared_counts_twitter',
					'parent' => 'shared_counts',
					'title'  => esc_html__( 'Twitter:', 'shared-counts' ) . ' ' . ( ! empty( $counts['Twitter'] ) ? number_format( absint( $counts['Twitter'] ) ) : '0' ),
				],
				[
					'id'     => 'shared_counts_pinterest',
					'parent' => 'shared_counts',
					'title'  => esc_html__( 'Pinterest:', 'shared-counts' ) . ' ' . ( ! empty( $counts['Pinterest'] ) ? number_format( absint( $counts['Pinterest'] ) ) : '0' ),
				],
				[
					'id'     => 'shared_counts_yummly',
					'parent' => 'shared_counts',
					'title'  => esc_html__( 'Yummly:', 'shared-counts' ) . ' ' . ( ! empty( $counts['Yummly'] ) ? number_format( absint( $counts['Yummly'] ) ) : '0' ),
				],
			];

			if ( in_array( 'email', $options['included_services'], true ) ) {
				$details[] = [
					'id'     => 'shared_counts_email',
					'parent' => 'shared_counts',
					'title'  => esc_html__( 'Email:', 'shared-counts' ) . ' ' . number_format( absint( get_post_meta( get_the_ID(), 'shared_counts_email', true ) ) ),
				];
			}

			$menu = array_merge( $menu, $details );
		}

		$menu = apply_filters( 'shared_counts_admin_bar_items', $menu, $settings, $options );

		foreach ( $menu as $args ) {
			$wp_admin_bar->add_node( $args );
		}
	}
}
