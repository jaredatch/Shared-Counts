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
 * @copyright  Copyright (c) 2017
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
		add_action( 'template_redirect', array( $this, 'theme_location' ), 99 );
		add_action( 'wp_enqueue_scripts', array( $this, 'header_assets' ), 9 );
		add_action( 'wp_footer', array( $this, 'load_assets' ), 1 );
		add_action( 'wp_footer', array( $this, 'email_modal' ), 50 );
		add_shortcode( 'shared_counts', array( $this, 'shortcode' ) );
	}

	/**
	 * Add share buttons to theme locations.
	 *
	 * @since 1.0.0
	 */
	public function theme_location() {

		// Genesis Hooks.
		if ( 'genesis' === get_template_directory() ) {

			$locations = array(
				'before' => array(
					'hook'     => 'genesis_entry_header',
					'filter'   => false,
					'priority' => 13,
					'style'    => false,
				),
				'after'  => array(
					'hook'     => 'genesis_entry_footer',
					'filter'   => false,
					'priority' => 8,
					'style'    => false,
				),
			);

		// Theme Hook Alliance.
		} elseif ( current_theme_supports( 'tha_hooks', array( 'entry' ) ) ) {

			$locations = array(
				'before' => array(
					'hook'     => 'tha_entry_top',
					'filter'   => false,
					'priority' => 13,
					'style'    => false,
				),
				'after'  => array(
					'hook'     => 'tha_entry_bottom',
					'filter'   => false,
					'priority' => 8,
					'style'    => false,
				),
			);

		// Fallback to 'the_content'.
		} else {

			$locations = array(
				'before' => array(
					'hook'     => false,
					'filter'   => 'the_content',
					'priority' => 8,
					'style'    => false,
				),
				'after'  => array(
					'hook'     => false,
					'filter'   => 'the_content',
					'priority' => 12,
					'style'    => false,
				),
			);
		}

		// Filter theme locations.
		$locations = apply_filters( 'shared_counts_theme_locations', $locations );

		// Make locations available everywhere.
		$this->locations = $locations;

		// Display share buttons before content.
		if ( $locations['before']['hook'] ) {
			add_action( $locations['before']['hook'], array( $this, 'display_before_content' ), $locations['before']['priority'] );
		} elseif ( $locations['before']['filter'] && ! is_feed() ) {
			add_filter( $locations['before']['filter'], array( $this, 'display_before_content_filter' ), $locations['before']['priority'] );
		}

		// Display share buttons after content.
		if ( $locations['after']['hook'] ) {
			add_action( $locations['after']['hook'], array( $this, 'display_after_content' ), $locations['after']['priority'] );
		} elseif ( $locations['after']['filter'] && ! is_feed() ) {
			add_filter( $locations['after']['filter'], array( $this, 'display_after_content_filter' ), $locations['after']['priority'] );
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
			array(),
			SHARED_COUNTS_VERSION
		);

		wp_register_script(
			'shared-counts',
			SHARED_COUNTS_URL . 'assets/js/shared-counts' . $suffix . '.js',
			array( 'jquery' ),
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
					array(),
					null,
					true
				);
			}
		}

		// Localize JS strings.
		$args = array(
			'email_fields_required' => esc_html__( 'Please complete out all 3 fields to email this article.', 'shared-counts' ),
			'email_sent'            => esc_html__( 'Article successfully shared.', 'shared-counts' ),
			'ajaxurl'               => esc_url_raw( admin_url( 'admin-ajax.php' ) ),
		);
		wp_localize_script( 'shared-counts', 'shared_counts', $args );

		// Localize recaptcha site key if enabled.
		if ( $recaptcha ) {
			$args['recaptchaSitekey'] = sanitize_text_field( $options['recaptcha_site_key'] );
		}
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
		$labels = apply_filters( 'shared_counts_email_labels', array(
			'title'      => esc_html__( 'Share this Article', 'shared-counts' ),
			'recipient'  => esc_html__( 'Friend\'s Email Address', 'shared-counts' ),
			'name'       => esc_html__( 'Your Name', 'shared-counts' ),
			'email'      => esc_html__( 'Your Email Address', 'shared-counts' ),
			'validation' => esc_html__( 'Comments', 'shared-counts' ),
			'submit'     => '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 1024 1024"><path d="M1024 405.714v453.714q0 37.714-26.857 64.571t-64.571 26.857h-841.143q-37.714 0-64.571-26.857t-26.857-64.571v-453.714q25.143 28 57.714 49.714 206.857 140.571 284 197.143 32.571 24 52.857 37.429t54 27.429 62.857 14h1.143q29.143 0 62.857-14t54-27.429 52.857-37.429q97.143-70.286 284.571-197.143 32.571-22.286 57.143-49.714zM1024 237.714q0 45.143-28 86.286t-69.714 70.286q-214.857 149.143-267.429 185.714-5.714 4-24.286 17.429t-30.857 21.714-29.714 18.571-32.857 15.429-28.571 5.143h-1.143q-13.143 0-28.571-5.143t-32.857-15.429-29.714-18.571-30.857-21.714-24.286-17.429q-52-36.571-149.714-104.286t-117.143-81.429q-35.429-24-66.857-66t-31.429-78q0-44.571 23.714-74.286t67.714-29.714h841.143q37.143 0 64.286 26.857t27.143 64.571z"></path></svg> ' . esc_html__( 'Send Email', 'shared-counts' ),
			'close'      => '<svg class="close-icon" version="1.1" xmlns="http://www.w3.org/2000/svg" width="25.15625" height="32" viewBox="0 0 805 1024"><path d="M741.714 755.429q0 22.857-16 38.857l-77.714 77.714q-16 16-38.857 16t-38.857-16l-168-168-168 168q-16 16-38.857 16t-38.857-16l-77.714-77.714q-16-16-16-38.857t16-38.857l168-168-168-168q-16-16-16-38.857t16-38.857l77.714-77.714q16-16 38.857-16t38.857 16l168 168 168-168q16-16 38.857-16t38.857 16l77.714 77.714q16 16 16 38.857t-16 38.857l-168 168 168 168q16 16 16 38.857z"></path></svg>',
		) );
		?>
		<div id="shared-counts-modal-wrap" style="display:none;">
			<div class="shared-counts-modal">
				<span class="shared-counts-modal-title"><?php echo esc_html( $labels['title'] ); ?></span>
				<p>
					<label for="shared-counts-modal-recipient"><?php echo esc_html( $labels['recipient'] ); ?></label>
					<input type="email" id="shared-counts-modal-recipient">
				</p>
				<p>
					<label for="shared-counts-modal-name"><?php echo esc_html( $labels['name'] ); ?></label>
					<input type="text" id="shared-counts-modal-name">
				</p>
				<p>
					<label for="shared-counts-modal-email"><?php echo esc_html( $labels['email'] ); ?></label>
					<input type="email" id="shared-counts-modal-email">
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
					<button id="shared-counts-modal-submit"><?php echo $labels['submit']; // WPCS: XSS ok. ?></button>
				</p>
				<a href="#" id="shared-counts-modal-close"><?php echo $labels['close']; // WPCS: XSS ok. ?></a>
				<div id="shared-counts-modal-sent"><?php esc_html_e( 'Email sent!', 'shared-counts' ); ?></div>
			</div>
		</div>
		<?php
	}

	/**
	 * Display Share Counts based on plugin settings.
	 *
	 * @since 1.0.0
	 *
	 * @param string $location
	 * @param bool $echo
	 * @param string $style
	 *
	 * @return null|string, depending on $echo
	 */
	public function display( $location = '', $echo = true, $style = false ) {

		$options  = shared_counts()->admin->options();
		$services = '';

		if ( ! $style && isset( $options['style'] ) ) {
			$style = esc_attr( $options['style'] );
		} elseif ( ! $style ) {
			$style = 'generic';
		}

		foreach ( $options['included_services'] as $service ) {
			$services .= $this->link( $service, false, false, $style );
		}

		$links       = apply_filters( 'shared_counts_display', $services, $location );
		$wrap_format = apply_filters( 'shared_counts_display_wrap_format', '<div class="shared-counts-wrap %2$s">%1$s</div>', $location );
		$output      = apply_filters( 'shared_counts_display_output', sprintf( $wrap_format, $links, sanitize_html_class( $location ) ), $location );

		if ( $echo ) {
			echo $output; // WPCS: XSS ok.
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
	 * @param string $content
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
	 * @param string $content
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
	 * @param string $types button type.
	 * @param int/string $id pass 'site' for full site stats.
	 * @param boolean $echo
	 * @param string $style
	 * @param int $round how many significant digits on count.
	 * @param bool $show_empty
	 */
	public function link( $types = 'facebook', $id = false, $echo = true, $style = 'generic', $round = 2, $show_empty = '' ) {

		if ( ! $id ) {
			$id = get_the_ID();
		}

		$this->share_link = true;

		$types   = (array) $types;
		$output  = '';
		$options = shared_counts()->admin->options();
		$attr    = array( 'postid' => $id );
		$data    = '';

		if ( empty( $show_empty ) ) {
			$show_empty = '1' === $options['hide_empty'] ? 'false' : 'true';
		}

		foreach ( $types as $type ) {

			$link          = array();
			$link['type']  = $type;
			$link['class'] = esc_attr( 'style-' . $style );

			if ( 'site' === $id ) {
				$link['url']   = esc_url( home_url() );
				$link['title'] = wp_strip_all_tags( get_bloginfo( 'name' ) );
				$link['img']   = apply_filters( 'shared_counts_default_image', '', $id );
			} elseif ( 0 === strpos( $id, 'http' ) ) {
				$link['url']   = esc_url( $id );
				$link['title'] = '';
				$link['img']   = apply_filters( 'shared_counts_default_image', '', $id );
			} else {
				$link['url']   = esc_url( get_permalink( $id ) );
				$link['title'] = wp_strip_all_tags( get_the_title( $id ) );
				$link['img']   = apply_filters( 'shared_counts_single_image', wp_get_attachment_image_url( get_post_thumbnail_id(), 'full' ), $id );
			}
			$link['url']   = apply_filters( 'shared_counts_link_url', $link['url'] );
			$link['count'] = shared_counts()->core->count( $id, $type, false, $round );

			switch ( $type ) {
				case 'facebook':
					$link['link']       = 'https://www.facebook.com/sharer/sharer.php?u=' . $link['url'] . '&display=popup&ref=plugin&src=share_button';
					$link['label']      = 'Facebook';
					$link['icon']       = '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="18.8125" height="32" viewBox="0 0 602 1024"><path d="M548 6.857v150.857h-89.714q-49.143 0-66.286 20.571t-17.143 61.714v108h167.429l-22.286 169.143h-145.143v433.714h-174.857v-433.714h-145.714v-169.143h145.714v-124.571q0-106.286 59.429-164.857t158.286-58.571q84 0 130.286 6.857z"></path></svg>';
					$link['target']     = '_blank';
					$link['attr_title'] = 'Share on Facebook';
					break;
				case 'facebook_likes':
					$link['link']       = 'https://www.facebook.com/plugins/like.php?href=' . $link['url'];
					$link['label']      = 'Like';
					$link['icon']       = '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="18.8125" height="32" viewBox="0 0 602 1024"><path d="M548 6.857v150.857h-89.714q-49.143 0-66.286 20.571t-17.143 61.714v108h167.429l-22.286 169.143h-145.143v433.714h-174.857v-433.714h-145.714v-169.143h145.714v-124.571q0-106.286 59.429-164.857t158.286-58.571q84 0 130.286 6.857z"></path></svg>';
					$link['target']     = '_blank';
					$link['attr_title'] = 'Like on Facebook';
					break;
				case 'facebook_shares':
					$link['link']       = 'https://www.facebook.com/sharer/sharer.php?u=' . $link['url'] . '&display=popup&ref=plugin&src=share_button';
					$link['label']      = 'Share';
					$link['icon']       = '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="18.8125" height="32" viewBox="0 0 602 1024"><path d="M548 6.857v150.857h-89.714q-49.143 0-66.286 20.571t-17.143 61.714v108h167.429l-22.286 169.143h-145.143v433.714h-174.857v-433.714h-145.714v-169.143h145.714v-124.571q0-106.286 59.429-164.857t158.286-58.571q84 0 130.286 6.857z"></path></svg>';
					$link['target']     = '_blank';
					$link['attr_title'] = 'Share on Facebook';
					break;
				case 'twitter':
					$link['link']       = 'https://twitter.com/share?url=' . $link['url'] . '&text=' . rawurlencode( $link['title'] );
					$link['label']      = 'Tweet';
					$link['icon']       = '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="29.71875" height="32" viewBox="0 0 951 1024"><path d="M925.714 233.143q-38.286 56-92.571 95.429 0.571 8 0.571 24 0 74.286-21.714 148.286t-66 142-105.429 120.286-147.429 83.429-184.571 31.143q-154.857 0-283.429-82.857 20 2.286 44.571 2.286 128.571 0 229.143-78.857-60-1.143-107.429-36.857t-65.143-91.143q18.857 2.857 34.857 2.857 24.571 0 48.571-6.286-64-13.143-106-63.714t-42-117.429v-2.286q38.857 21.714 83.429 23.429-37.714-25.143-60-65.714t-22.286-88q0-50.286 25.143-93.143 69.143 85.143 168.286 136.286t212.286 56.857q-4.571-21.714-4.571-42.286 0-76.571 54-130.571t130.571-54q80 0 134.857 58.286 62.286-12 117.143-44.571-21.143 65.714-81.143 101.714 53.143-5.714 106.286-28.571z"></path></svg>';
					$link['target']     = '_blank';
					$link['attr_title'] = 'Share on Twitter';
					break;
				case 'pinterest':
					$link['link']       = 'https://pinterest.com/pin/create/button/?url=' . $link['url'] . '&media=' . $link['img'] . '&description=' . $link['title'];
					$link['label']      = 'Pin';
					$link['icon']       = '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="22.84375" height="32" viewBox="0 0 731 1024"><path d="M0 341.143q0-61.714 21.429-116.286t59.143-95.143 86.857-70.286 105.714-44.571 115.429-14.857q90.286 0 168 38t126.286 110.571 48.571 164q0 54.857-10.857 107.429t-34.286 101.143-57.143 85.429-82.857 58.857-108 22q-38.857 0-77.143-18.286t-54.857-50.286q-5.714 22.286-16 64.286t-13.429 54.286-11.714 40.571-14.857 40.571-18.286 35.714-26.286 44.286-35.429 49.429l-8 2.857-5.143-5.714q-8.571-89.714-8.571-107.429 0-52.571 12.286-118t38-164.286 29.714-116q-18.286-37.143-18.286-96.571 0-47.429 29.714-89.143t75.429-41.714q34.857 0 54.286 23.143t19.429 58.571q0 37.714-25.143 109.143t-25.143 106.857q0 36 25.714 59.714t62.286 23.714q31.429 0 58.286-14.286t44.857-38.857 32-54.286 21.714-63.143 11.429-63.429 3.714-56.857q0-98.857-62.571-154t-163.143-55.143q-114.286 0-190.857 74t-76.571 187.714q0 25.143 7.143 48.571t15.429 37.143 15.429 26 7.143 17.429q0 16-8.571 41.714t-21.143 25.714q-1.143 0-9.714-1.714-29.143-8.571-51.714-32t-34.857-54-18.571-61.714-6.286-60.857z"></path></svg>';
					$link['target']     = '_blank';
					$link['attr_title'] = 'Share on Pinterest';
					break;
				case 'linkedin':
					$link['link']       = 'https://www.linkedin.com/shareArticle?mini=true&url=' . $link['url'];
					$link['label']      = 'LinkedIn';
					$link['icon']       = '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="27.4375" height="32" viewBox="0 0 878 1024"><path d="M199.429 357.143v566.286h-188.571v-566.286h188.571zM211.429 182.286q0.571 41.714-28.857 69.714t-77.429 28h-1.143q-46.857 0-75.429-28t-28.571-69.714q0-42.286 29.429-70t76.857-27.714 76 27.714 29.143 70zM877.714 598.857v324.571h-188v-302.857q0-60-23.143-94t-72.286-34q-36 0-60.286 19.714t-36.286 48.857q-6.286 17.143-6.286 46.286v316h-188q1.143-228 1.143-369.714t-0.571-169.143l-0.571-27.429h188v82.286h-1.143q11.429-18.286 23.429-32t32.286-29.714 49.714-24.857 65.429-8.857q97.714 0 157.143 64.857t59.429 190z"></path></svg>';
					$link['target']     = '_blank';
					$link['attr_title'] = 'Share on LinkedIn';
					break;
				case 'google':
					$link['link']       = 'https://plus.google.com/share?url=' . $link['url'];
					$link['label']      = 'Google+';
					$link['icon']       = '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="32" height="24.880789674" viewBox="0 0 1317 1024"><path d="M821.143 521.714q0 118.857-49.714 211.714t-141.714 145.143-210.857 52.286q-85.143 0-162.857-33.143t-133.714-89.143-89.143-133.714-33.143-162.857 33.143-162.857 89.143-133.714 133.714-89.143 162.857-33.143q163.429 0 280.571 109.714l-113.714 109.143q-66.857-64.571-166.857-64.571-70.286 0-130 35.429t-94.571 96.286-34.857 132.857 34.857 132.857 94.571 96.286 130 35.429q47.429 0 87.143-13.143t65.429-32.857 44.857-44.857 28-47.429 12.286-42.286h-237.714v-144h395.429q6.857 36 6.857 69.714zM1316.571 452v120h-119.429v119.429h-120v-119.429h-119.429v-120h119.429v-119.429h120v119.429h119.429z"></path></svg>';
					$link['target']     = '_blank';
					$link['attr_title'] = 'Share on Google+';
					break;
				case 'stumbleupon':
					$link['link']       = 'https://www.stumbleupon.com/submit?url=' . $link['url'] . '&title=' . $link['title'];
					$link['label']      = 'StumbleUpon';
					$link['icon']       = '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="32" height="29.870556062" viewBox="0 0 1097 1024"><path d="M606.857 406.857v-67.429q0-24-17.143-41.143t-41.143-17.143-41.143 17.143-17.143 41.143v349.714q0 100-72 170.857t-173.143 70.857q-101.714 0-173.429-71.714t-71.714-173.429v-152h187.429v149.714q0 24.571 17.143 41.429t41.143 16.857 41.143-16.857 17.143-41.429v-354.286q0-97.714 72.286-166.857t172.286-69.143q100.571 0 172.571 69.714t72 168v77.714l-111.429 33.143zM909.714 533.714h187.429v152q0 101.714-71.714 173.429t-173.429 71.714q-101.143 0-173.143-71.143t-72-171.714v-153.143l74.857 34.857 111.429-33.143v154.286q0 24 17.143 40.857t41.143 16.857 41.143-16.857 17.143-40.857v-157.143z"></path></svg>';
					$link['target']     = '_blank';
					$link['attr_title'] = 'Share on StumbleUpon';
					break;
				case 'included_total':
					$link['link']   = '';
					$link['label']  = 'Total';
					$link['icon']   = '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="27.4375" height="32" viewBox="0 0 878 1024"><path d="M694.857 585.143q76 0 129.429 53.429t53.429 129.429-53.429 129.429-129.429 53.429-129.429-53.429-53.429-129.429q0-6.857 1.143-19.429l-205.714-102.857q-52.571 49.143-124.571 49.143-76 0-129.429-53.429t-53.429-129.429 53.429-129.429 129.429-53.429q72 0 124.571 49.143l205.714-102.857q-1.143-12.571-1.143-19.429 0-76 53.429-129.429t129.429-53.429 129.429 53.429 53.429 129.429-53.429 129.429-129.429 53.429q-72 0-124.571-49.143l-205.714 102.857q1.143 12.571 1.143 19.429t-1.143 19.429l205.714 102.857q52.571-49.143 124.571-49.143z"></path></svg>';
					$link['target'] = '';
					break;
				case 'print':
					$link['link']       = 'javascript:window.print()';
					$link['label']      = 'Print';
					$link['icon']       = '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="29.71875" height="32" viewBox="0 0 951 1024"><path d="M219.429 877.714h512v-146.286h-512v146.286zM219.429 512h512v-219.429h-91.429q-22.857 0-38.857-16t-16-38.857v-91.429h-365.714v365.714zM877.714 548.571q0-14.857-10.857-25.714t-25.714-10.857-25.714 10.857-10.857 25.714 10.857 25.714 25.714 10.857 25.714-10.857 10.857-25.714zM950.857 548.571v237.714q0 7.429-5.429 12.857t-12.857 5.429h-128v91.429q0 22.857-16 38.857t-38.857 16h-548.571q-22.857 0-38.857-16t-16-38.857v-91.429h-128q-7.429 0-12.857-5.429t-5.429-12.857v-237.714q0-45.143 32.286-77.429t77.429-32.286h36.571v-310.857q0-22.857 16-38.857t38.857-16h384q22.857 0 50.286 11.429t43.429 27.429l86.857 86.857q16 16 27.429 43.429t11.429 50.286v146.286h36.571q45.143 0 77.429 32.286t32.286 77.429z"></path></svg>';
					$link['attr_title'] = 'Print this Page';
					break;
				case 'email':
					$link['link']       = '#shared-counts-email';
					$link['label']      = 'Email';
					$link['icon']       = '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 1024 1024"><path d="M1024 405.714v453.714q0 37.714-26.857 64.571t-64.571 26.857h-841.143q-37.714 0-64.571-26.857t-26.857-64.571v-453.714q25.143 28 57.714 49.714 206.857 140.571 284 197.143 32.571 24 52.857 37.429t54 27.429 62.857 14h1.143q29.143 0 62.857-14t54-27.429 52.857-37.429q97.143-70.286 284.571-197.143 32.571-22.286 57.143-49.714zM1024 237.714q0 45.143-28 86.286t-69.714 70.286q-214.857 149.143-267.429 185.714-5.714 4-24.286 17.429t-30.857 21.714-29.714 18.571-32.857 15.429-28.571 5.143h-1.143q-13.143 0-28.571-5.143t-32.857-15.429-29.714-18.571-30.857-21.714-24.286-17.429q-52-36.571-149.714-104.286t-117.143-81.429q-35.429-24-66.857-66t-31.429-78q0-44.571 23.714-74.286t67.714-29.714h841.143q37.143 0 64.286 26.857t27.143 64.571z"></path></svg>';
					$link['target']     = '';
					$link['attr_title'] = 'Share via Email';
					$link['class']      = 'no-scroll';
					break;
			}

			$link       = apply_filters( 'shared_counts_link', $link, $id );
			$target     = ! empty( $link['target'] ) ? ' target="' . esc_attr( $link['target'] ) . '" ' : '';
			$attr_title = ! empty( $link['attr_title'] ) ? ' title="' . esc_attr( $link['attr_title'] ) . '" ' : '';

			// Add classes.
			if ( empty( $link['count'] ) || ( '1' === $options['total_only'] && 'included_total' !== $type ) ) {
				$link['class'] .= ' shared-counts-no-count';
			}

			// Add data attribues.
			if ( ! empty( apply_filters( 'shared_counts_link_data', $attr, $link, $id ) ) ) {
				foreach ( $attr as $key => $val ) {
					$data .= ' data-' . sanitize_html_class( $key ) . '="' . esc_attr( $val ) . '"';
				}
			}

			// Build button output.
			if ( 'included_total' === $type ) {
				$output .= '<span class="shared-counts-button ' . $link['class'] . ' ' . sanitize_html_class( $link['type'] ) . '"' . $data . '>';
			} else {
				$output .= '<a href="' . $link['link'] . '"' . $attr_title . $target . ' class="shared-counts-button ' . $link['class'] . ' ' . sanitize_html_class( $link['type'] ) . '"' . $data . '>';
			}
			$output .= '<span class="shared-counts-icon-label">';
			$output .= '<span class="shared-counts-icon">' . $link[ 'icon' ] . '</span>';
			//$output .= '<i class="shared-counts-icon ' . $link['icon'] . '"></i>';
			$output .= '<span class="shared-counts-label">' . $link['label'] . '</span>';
			$output .= '</span>';

			if ( 'included_total' === $type && ( ( 'true' !== $show_empty ) || ( 'true' === $show_empty && $link['count'] > 0 ) ) ) {
				$output .= '<span class="shared-counts-count">' . $link['count'] . '</span>';
			} elseif ( '1' !== $options['total_only'] && ( ( 'true' !== $show_empty ) || ( 'true' === $show_empty && $link['count'] > 0 ) ) ) {
				$output .= '<span class="shared-counts-count">' . $link['count'] . '</span>';
			}

			$output .= 'included_total' === $type ? '</span>' : '</a>';
		}

		if ( true === $echo ) {
			echo $output; // WPCS: XSS ok.
		} else {
			return $output;
		}
	}

	/**
	 * Display share counts via shortcode.
	 *
	 * @since 1.0.0
	 *
	 * @param array $atts
	 *
	 * @return string
	 */
	public function shortcode( $atts = array() ) {

		$atts = shortcode_atts(
			array(
				'location' => 'shortcode',
				'style'    => false,
			),
			$atts,
			'shared_counts'
		);

		// Don't show or include the share badges in the feed, since they won't
		// display well.
		if ( ! is_feed() ) {
			return $this->display( esc_attr( $atts['location'] ), false, esc_attr( $atts['style'] ) );
		}
	}
}
