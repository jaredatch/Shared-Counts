<?php
/**
 * AMP class.
 *
 * Contains functionality for AMP compatibility
 *
 * @package    SharedCounts
 * @author     Bill Erickson & Jared Atchison
 * @since      1.3.0
 * @license    GPL-2.0+
 * @copyright  Copyright (c) 2017
 */
class Shared_Counts_AMP {

	/**
	 * Primary class constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		add_filter( 'shared_counts_load_js', array( $this, 'disable_js' ) );
		add_filter( 'shared_counts_additional_attr', array( $this, 'print_action' ), 10, 4 );
		add_filter( 'shared_counts_email_modal_output', array( $this, 'email_modal' ), 10, 2 );
		add_action( 'wp_enqueue_scripts', array( $this, 'email_modal_scripts' ) );
		add_filter( 'script_loader_tag', array( $this, 'amp_script_tag' ), 10, 3 );
	}

	/**
	 * Is AMP? conditional
	 *
	 * @since 1.3.0
	 *
	 * @return bool
	 */
	public function is_amp() {
		return function_exists('is_amp_endpoint') ? is_amp_endpoint() : false;
	}

	/**
	 * Disable JS
	 *
	 * @since 1.3.0
	 *
	 * @return bool
	 */
	public function disable_js( $load_js ) {
		if( $this->is_amp() )
			$load_js = false;
		return $load_js;
	}

	/**
	 * Print Action
	 *
	 * @since 1.3.0
	 *
	 * @param array $attr
	 * @param array $link
	 * @param int $id
	 * @param string $style
	 * @return array
	 */
	function print_action( $attr, $link, $id, $style ) {
		if( ! $this->is_amp() )
			return $attr;

		if( 'print' === $link['type'] ) {
			$attr[] = 'on="tap:AMP.print()"';
		}

		if( 'email' === $link['type'] ) {
			$attr[] = 'on="tap:shared-counts-modal-wrap"';
		}
		return $attr;
	}

	/**
	 * Email modal
	 *
	 * @since 1.3.0
	 *
	 * @param string $output
	 * @return string
	 */
	function email_modal( $output, $modal_settings ) {
		if( ! $this->is_amp() )
			return $output;

		$output = '<amp-lightbox id="shared-counts-modal-wrap" layout="nodisplay">';
			$output .= '<div class="shared-counts-modal">';
				$output .= '<a href="#" id="shared-counts-modal-close" on="tap:shared-counts-modal-wrap.close">' . $modal_settings['labels']['close'] . '</a>'; // WPCS: XSS ok.
				$output .= '<div class="shared-counts-modal-header">';
					if ( ! empty( $modal_settings['labels']['title_icon'] ) ) {
						$output .= '<span class="shared-counts-modal-icon">' . $modal_settings['labels']['title_icon'] . '</span>'; // WPCS: XSS ok.
					}
					if ( ! empty( $modal_settings['labels']['title'] ) ) {
						$output .= '<span class="shared-counts-modal-title">' . esc_html( $modal_settings['labels']['title'] ) . '</span>';
					}
					if ( ! empty( $modal_settings['labels']['subtitle'] ) ) {
						$output .= '<span class="shared-counts-modal-subtitle">' . esc_html( $modal_settings['labels']['subtitle'] ) . '</span>';
					}
				$output .= '</div>';
				$output .= '<div class="shared-counts-modal-content">';
					$output .= '<form class="shared-counts-modal-form" method="post" action-xhr="' . esc_url_raw( admin_url( 'admin-ajax.php' ) ) . '?action=shared_counts_email" target="_top">';
						$output .= '<p>';
							$output .= '<label for="shared-counts-modal-recipient">' . esc_html( $modal_settings['labels']['recipient'] ) . '</label>';
							$output .= '<input type="email" id="shared-counts-modal-recipient" placeholder="' . esc_html( $modal_settings['labels']['recipient'] ) . '">';
						$output .= '</p>';
						$output .= '<p>';
							$output .= '<label for="shared-counts-modal-name">' . esc_html( $modal_settings['labels']['name'] ) . '</label>';
							$output .= '<input type="text" id="shared-counts-modal-name" placeholder="' . esc_html( $modal_settings['labels']['name'] ) . '">';
						$output .= '</p>';
						$output .= '<p>';
							$output .= '<label for="shared-counts-modal-email">' . esc_html( $modal_settings['labels']['email'] ) . '</label>';
							$output .= '<input type="email" id="shared-counts-modal-email" placeholder="' . esc_html( $modal_settings['labels']['email'] ) . '">';
						$output .= '</p>';
						$output .= '<p class="shared-counts-modal-validation">';
							$output .= '<label for="shared-counts-modal-validation">' . esc_html( $modal_settings['labels']['validation'] ) . '</label>';
							$output .= '<input type="text" id="shared-counts-modal-validation" autocomplete="off">';
						$output .= '</p>';

						if ( $modal_settings['has_recaptcha'] ) {
							$output .= '<amp-recaptcha-input layout="nodisplay" name="shared_counts_recaptcha" data-sitekey="' . $modal_settings['recaptcha_site_key'] . '" data-action="shared_counts_recaptcha"></amp-recaptcha-input>';
						}
						$output .= '<p class="shared-counts-modal-submit">';
							$output .= '<button id="shared-counts-modal-submit">' . $modal_settings['labels']['submit'] . '</button>'; // WPCS: XSS ok
						$output .= '</p>';

						$output .= '<div submit-success><template type="amp-mustache"><p>' . __( 'Email sent!', 'shared-counts' ) . '</p></template></div>';
						$output .= '<div submit-error><template type="amp-mustache"><p>' . __( 'There was an issue with your submission.', 'shared-counts' ) . '</p></template></div>';

					$output .= '</form>';
				$output .= '</div>';
			$output .= '</div>';
		$output .= '</amp-lightbox>';

		return $output;
	}

	/**
	 * Email modal scripts
	 *
	 * @since 1.3.0
	 *
	 */
	function email_modal_scripts() {
		if( ! ( $this->is_amp() && shared_counts()->front->has_email_modal() ) )
			return;

	  	wp_enqueue_script( 'amp-lightbox', 'https://cdn.ampproject.org/v0/amp-lightbox-0.1.js', array(), '0.1', false );
		wp_enqueue_script( 'amp-form', 'https://cdn.ampproject.org/v0/amp-form-0.1.js', array(), '0.1', false );
		wp_enqueue_script( 'amp-mustache', 'https://cdn.ampproject.org/v0/amp-mustache-0.2.js', array(), '0.2', false );

		$options   = shared_counts()->admin->options();
		$recaptcha = ! empty( $options['recaptcha'] ) && ! empty( $options['recaptcha_site_key'] ) && ! empty( $options['recaptcha_secret_key'] );
		if( $recaptcha )
			wp_enqueue_script( 'amp-recaptcha-input', 'https://cdn.ampproject.org/v0/amp-recaptcha-input-0.1.js', array(), '0.1', false );
	}

	/**
	 * AMP script tag
	 *
	 * @since 1.3.0
	 *
	 * @param string $tag
	 * @param string $handle
	 * @param string $src
	 * @return string
	 */
	function amp_script_tag( $tag, $handle, $src ) {
		if( 'amp-lightbox' === $handle )
			$tag = '<script async custom-element="amp-lightbox" src="' . esc_url_raw( $src ) . '"></script>';
		if( 'amp-form' === $handle )
			$tag = '<script async custom-element="amp-form" src="' . esc_url_raw( $src ) . '"></script>';
		if( 'amp-mustache' === $handle )
			$tag = '<script async custom-template="amp-mustache" src="' . esc_url_raw( $src ) . '"></script>';
		if( 'amp-recaptcha-input' === $handle )
			$tag = '<script async custom-element="amp-recaptcha-input" src="' . esc_url_raw( $src ) . '"></script>';
		return $tag;
	}
}
