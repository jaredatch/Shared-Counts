<?php
/**
 * CLI class
 *
 * Integration with wp cli
 *
 * @package    SharedCounts
 * @author     Bill Erickson & Jared Atchison
 * @since      1.4.0
 * @license    GPL-2.0+
 * @copyright  Copyright (c) 2019
 */
class Shared_Counts_CLI {

	/**
	 * Primary class constructor.
	 *
	 * @since 1.4.0
	 */
	public function __construct() {

		WP_CLI::add_command( 'shared-counts', array( $this, 'shared_counts' ) );
		WP_CLI::add_command( 'shared-counts update', array( $this, 'update' ) );
		WP_CLI::add_command( 'shared-counts bulk-update', array( $this, 'bulk_update' ) );

	}

	/**
	 * Shared Counts
	 *
	 */
	public function shared_counts( $args, $assoc_args ) {
		WP_CLI::success( 'test' );
	}

	/**
	 * Update share counts for a specific post
	 *
	 * <post_id>
	 * : The ID of the post you'd like to update
	 */
	public function update( $args, $assoc_args ) {
		$id = intval( $args[0] );
		if( empty( $id ) ) {
			WP_CLI::error( 'Please provide a post ID' );
		}

		$count_source  = shared_counts()->admin->settings_value( 'count_source' );
		if( 'none' === $count_source ) {
			WP_CLI::error( 'No share count source specified in Settings > Shared Counts' );
		}

		$counts = shared_counts()->core->counts( $id, true, true );
		foreach( $counts as $service => $count ) {
			$total = is_array( $count ) ? intval( $count['total_count'] ) : intval( $count );
			WP_CLI::log( $service . ': ' . $total );
		}

		WP_CLI::success( 'Share counts updated.' );


	}

	/**
	 * Bulk update share counts
	 *
	 * --count=<count>
	 * : How many posts to update. Default is 100
	 *
	 * --only_empty=<only_empty>
	 * : (bool) only update posts with no share count data. Default is false
	 */
	public function bulk_update( $args, $assoc_args ) {
		WP_CLI::success( 'Bulk update goes here' );
	}

}
