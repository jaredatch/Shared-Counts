<?php
/**
 * AMP class.
 *
 * Contains functionality for AMP compatibility
 *
 * @package    SharedCounts
 * @author     Bill Erickson & Jared Atchison
 * @since      1.4.0
 * @license    GPL-2.0+
 * @copyright  Copyright (c) 2017
 */
class Shared_Counts_AMP {

	/**
	 * Primary class constructor.
	 *
	 * @since 1.4.0
	 */
	public function __construct() {
		add_filter( 'shared_counts_load_js', array( $this, 'disable_js' ) );
		add_filter( 'shared_counts_additional_attr', array( $this, 'print_action' ), 10, 4 );
		add_filter( 'shared_counts_link', array( $this, 'print_link' ), 10, 3 );
		add_filter( 'shared_counts_link', array( $this, 'email_action' ), 10, 3 );
	}

	/**
	 * Is AMP? conditional
	 *
	 * @since 1.4.0
	 *
	 * @return bool
	 */
	public function is_amp() {
		return function_exists('is_amp_endpoint') ? is_amp_endpoint() : false;
	}

	/**
	 * Disable JS
	 *
	 * @since 1.4.0
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
	 * @since 1.4.0
	 *
	 * @param array $attr
	 * @param array $link
	 * @param int $id
	 * @param string $style
	 * @return array
	 */
	function print_action( $attr, $link, $id, $style ) {
		if( $this->is_amp() && 'print' === $link['type'] ) {
			$attr[] = 'on="tap:AMP.print()"';
		}
		return $attr;
	}

	/**
	 * Remove print javascript.
	 *
	 * @since 1.4.0
	 *
	 * @param array $link
	 * @param int $id
	 * @return array
	 */
	function print_link( $link, $id ) {
		if( $this->is_amp() && 'print' === $link['type'] ) {
			$link['link'] = '';
		}
		return $link;
	}

	/**
	 * Email action
	 *
	 * @since 1.4.0
	 *
	 * @param array $link
	 * @param int $id
	 * @param string $style
	 * @return array
	 */
	function email_action( $link, $id, $style ) {
		if( $this->is_amp() && 'email' === $link['type'] ) {
			$subject = esc_html__( 'Your friend has shared an article with you.', 'shared-counts' );
			$subject = apply_filters( 'shared_counts_amp_email_subject', $subject, $id );

			$body    = html_entity_decode( get_the_title( $id ), ENT_QUOTES ) . "\r\n";
			$body   .= get_permalink( $id ) . "\r\n";
			$body    = apply_filters( 'shared_counts_amp_email_body', $body, $id );

			$link['link'] = 'mailto:?subject=' . rawurlencode( $subject ) . '&body=' . rawurlencode( $body );
		}
		return $link;
	}
}
