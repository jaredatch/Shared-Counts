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
		add_filter( 'shared_counts_email_modal_output', array( $this, 'email_modal' ) );
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
		if( $this->is_amp() && 'print' === $link['type'] ) {
			$attr[] = 'on="tap:AMP.print()"';
		}
		return $attr;
	}

	/**
	 * Email modal
	 *
	 * @since 1.3.0
	 *
	 * @param string $markup
	 * @return string
	 */
	function email_modal( $markup ) {
		return $markup;
	}
}
