<?php
/**
 * WP-CLI Class
 *
 * Contains functionality for custom WP-CLI Commands.
 *
 * @package    SharedCounts
 * @author     Richard Buff & Bill Erickson & Jared Atchison
 * @since      1.0.0
 * @license    GPL-2.0+
 * @copyright  Copyright (c) 2019
 */
class Shared_Counts_WPCLI {

	/**
	 * Primary class constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		// If we're in a WP-CLI context, load the custom WP-CLI commands.
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			WP_CLI::add_command( 'shared-counts popular', [ $this, 'display_popular' ] );
			WP_CLI::add_command( 'shared-counts display', [ $this, 'display_single' ] );
		}

	}

  /**
   * Display popular posts on command line
	 *
	 * Example: wp shared-counts popular --count=100
   *
   * @since 1.0.0
   */
  public function display_popular( $args, $assoc_args ) {

    $number_of_posts = isset( $assoc_args['count'] ) ? absint( $assoc_args['count'] ) : 3;
    $output = [];
    $loop  = new WP_Query(
      [
        'posts_per_page' => $number_of_posts,
        'orderby'        => 'meta_value_num',
        'order'          => 'DESC',
        'meta_key'       => 'shared_counts_total', //phpcs:ignore
      ]
    );

    if ( $loop->have_posts() ) {
      while ( $loop->have_posts() ) {
        $loop->the_post();
        $shares = get_post_meta( get_the_ID(), 'shared_counts_total', true );
        $output[] = [ 'Post Title' => get_the_title(), 'Permalink' => get_permalink(), 'Total Shares' => $shares ];
      }
      WP_CLI\Utils\format_items( 'table', $output, [ 'Post Title', 'Permalink', 'Total Shares' ] );
    } else {
      WP_CLI::warning( 'No popular posts found!' );
    }
    wp_reset_postdata();

  }


	/**
   * Display share counts for a single post
	 *
	 * Example: wp shared-counts display 123
   *
   * @since 1.0.0
   */
  public function display_single( $args, $assoc_args ) {

		$fields = [];
		$output = [];

		if( isset( $args[0] ) ){
			$post_id = absint( $args[0] );
		} else {
			WP_CLI::error( 'Error. You must supply a post ID.' );
		}

		$post = get_post( $post_id );
		if ( ! $post ) {
			WP_CLI::error( "Post {$post_id} doesn't exist." );
		}

		$options = shared_counts()->admin->options();
		$services = $options['included_services'];

		foreach ( $services as $service ) {
			$fields[] = $service;
			$output[$service] = shared_counts()->core->count( $post_id, $service, false, 2 );
		}

		// Add "Post Title" to front of $fields array
		array_unshift( $fields, "Post Title" );
		// Add "Post Title" and the actual post title to the front of the $output array
		$output = [ "Post Title" => get_the_title( $post_id ) ] + $output;

		// The output to WP_CLI\Utils\format_items needs to be an array of arrays
		$output = [$output];

		WP_CLI\Utils\format_items( 'table', $output, $fields );

	}

}
