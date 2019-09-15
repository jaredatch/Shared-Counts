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
	

}
