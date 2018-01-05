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
			'submit'     => '<i class="shared-counts-icon-envelope"></i> ' . esc_html__( 'Send Email', 'shared-counts' ),
			'close'      => '<i class="shared-counts-icon-close close-icon"></i>',
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
					$link['icon']       = 'shared-counts-icon-facebook';
					$link['target']     = '_blank';
					$link['attr_title'] = 'Share on Facebook';
					break;
				case 'facebook_likes':
					$link['link']       = 'https://www.facebook.com/plugins/like.php?href=' . $link['url'];
					$link['label']      = 'Like';
					$link['icon']       = 'shared-counts-icon-facebook';
					$link['target']     = '_blank';
					$link['attr_title'] = 'Like on Facebook';
					break;
				case 'facebook_shares':
					$link['link']       = 'https://www.facebook.com/sharer/sharer.php?u=' . $link['url'] . '&display=popup&ref=plugin&src=share_button';
					$link['label']      = 'Share';
					$link['icon']       = 'shared-counts-icon-facebook';
					$link['target']     = '_blank';
					$link['attr_title'] = 'Share on Facebook';
					break;
				case 'twitter':
					$link['link']       = 'https://twitter.com/share?url=' . $link['url'] . '&text=' . rawurlencode( $link['title'] );
					$link['label']      = 'Tweet';
					$link['icon']       = 'shared-counts-icon-twitter';
					$link['target']     = '_blank';
					$link['attr_title'] = 'Share on Twitter';
					break;
				case 'pinterest':
					$link['link']       = 'https://pinterest.com/pin/create/button/?url=' . $link['url'] . '&media=' . $link['img'] . '&description=' . $link['title'];
					$link['label']      = 'Pin';
					$link['icon']       = 'shared-counts-icon-pinterest-p';
					$link['target']     = '_blank';
					$link['attr_title'] = 'Share on Pinterest';
					break;
				case 'linkedin':
					$link['link']       = 'https://www.linkedin.com/shareArticle?mini=true&url=' . $link['url'];
					$link['label']      = 'LinkedIn';
					$link['icon']       = 'shared-counts-icon-linkedin';
					$link['target']     = '_blank';
					$link['attr_title'] = 'Share on LinkedIn';
					break;
				case 'google':
					$link['link']       = 'https://plus.google.com/share?url=' . $link['url'];
					$link['label']      = 'Google+';
					$link['icon']       = 'shared-counts-icon-google-plus';
					$link['target']     = '_blank';
					$link['attr_title'] = 'Share on Google+';
					break;
				case 'stumbleupon':
					$link['link']       = 'https://www.stumbleupon.com/submit?url=' . $link['url'] . '&title=' . $link['title'];
					$link['label']      = 'StumbleUpon';
					$link['icon']       = 'shared-counts-icon-stumbleupon';
					$link['target']     = '_blank';
					$link['attr_title'] = 'Share on StumbleUpon';
					break;
				case 'included_total':
					$link['link']   = '';
					$link['label']  = 'Total';
					$link['icon']   = 'shared-counts-icon-share';
					$link['target'] = '';
					break;
				case 'print':
					$link['link']       = 'javascript:window.print()';
					$link['label']      = 'Print';
					$link['icon']       = 'shared-counts-icon-print';
					$link['attr_title'] = 'Print this Page';
					break;
				case 'email':
					$link['link']       = '#shared-counts-email';
					$link['label']      = 'Email';
					$link['icon']       = 'shared-counts-icon-envelope';
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
			$output .= '<i class="shared-counts-icon ' . $link['icon'] . '"></i>';
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

		return;
	}
}
