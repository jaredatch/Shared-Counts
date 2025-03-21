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
			WP_CLI::add_command( 'shared-counts update', [ $this, 'update_single' ] );
		}

	}

	/**
	 * Display popular posts
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

		// Only display if we're collecting share counts.
		if ( ! empty( $options['count_source'] ) && 'none' == $options['count_source'] ) {
			WP_CLI::error( "Counts are not turned on in plugin settings." );
		}

		// Alert user that post must be published to track share counts.
		if ( 'publish' !== $post->post_status ) {
			WP_CLI::error( "Post must be published to view share counts." );
		}

		$counts = get_post_meta( $post->ID, 'shared_counts', true );

		if ( ! empty( $counts ) ) {

			// Decode the primary counts. This is the total of all possible
			// share count URLs.
			$counts = json_decode( $counts, true );

			// Output the primary counts numbers.
			echo $this->wpcli_counts_group( 'total', $counts, $post->ID ); // phpcs:ignore

			// Show https and http groups at the top if we have them.
			if ( ! empty( $groups['http'] ) && ! empty( $groups['https'] ) ) {
				echo $this->wpcli_counts_group( 'https', [], $post->ID ); // phpcs:ignore
				echo $this->wpcli_counts_group( 'http', [], $post->ID ); // phpcs:ignore
			}

			// Output other counts.
			if ( ! empty( $groups ) ) {
				foreach ( $groups as $slug => $group ) {
					// Skip https and https groups since we output them manually
					// above already.
					if ( ! in_array( $slug, [ 'http', 'https' ], true ) ) {
						echo $this->wpcli_counts_group( $slug, [], $post->ID ); // phpcs:ignore
					}
				}
			}

			// Display the date and time the share counts were last updated.
			$date = get_post_meta( $post->ID, 'shared_counts_datetime', true );
			$date = $date + ( get_option( 'gmt_offset' ) * 3600 );
			WP_CLI::line( "Last updated: " . esc_html( date( 'M j, Y g:ia', $date ) ) );

		} else {
			// Current post has not fetched share counts yet.
			WP_CLI::line( "No share counts downloaded for this post." );
		}

	}

	/**
	 * Build the wp-cli list item counts.
	 *
	 * @since 1.0.0
	 *
	 * @param string $group   Group type.
	 * @param array  $counts  Current counts.
	 * @param int    $post_id Post ID.
	 *
	 * @return string
	 */
	public function wpcli_counts_group( $group = 'total', $counts = [], $post_id ) {

		$options = shared_counts()->admin->options();
		$url     = false;
		$disable = false;

		if ( 'total' === $group ) {
			$name = 'Total';
			$total = get_post_meta( $post_id, 'shared_counts_total', true );
		} else {
			$groups = get_post_meta( $post_id, 'shared_counts_groups', true );
			if ( ! empty( $groups[ $group ]['name'] ) ) {
				$name    = esc_html( $groups[ $group ]['name'] );
				$counts  = json_decode( $groups[ $group ]['counts'], true );
				$total   = $groups[ $group ]['total'];
				$url     = ! empty( $groups[ $group ]['url'] ) ? $groups[ $group ]['url'] : false;
				$disable = ! empty( $groups[ $group ]['disable'] ) ? true : false;
			}
		}

		if ( empty( $counts ) || ! is_array( $counts ) ) {
			return;
		}

		WP_CLI::line( get_the_title( $post_id ) );

		// Group name
		WP_CLI::line( $name . ": " . number_format( absint( $total ) ) );

		// Count details
		WP_CLI::line( "Facebook Total: " . ( ! empty( $counts['Facebook']['total_count'] ) ? number_format( absint( $counts['Facebook']['total_count'] ) ) : '0' ) );
		WP_CLI::line( "Facebook Likes: " . ( ! empty( $counts['Facebook']['like_count'] ) ? number_format( absint( $counts['Facebook']['like_count'] ) ) : '0' ) );
		WP_CLI::line( "Facebook Shares: " . ( ! empty( $counts['Facebook']['share_count'] ) ? number_format( absint( $counts['Facebook']['share_count'] ) ) : '0' ) );
		WP_CLI::line( "Facebook Comments: " . ( ! empty( $counts['Facebook']['comment_count'] ) ? number_format( absint( $counts['Facebook']['comment_count'] ) ) : '0' ) );
		WP_CLI::line( "Twitter: " . ( ! empty( $counts['Twitter'] ) ? number_format( absint( $counts['Twitter'] ) ) : "0" ) );
		WP_CLI::line( "Pinterest: " . ( ! empty( $counts['Pinterest'] ) ? number_format( absint( $counts['Pinterest'] ) ) : "0" ) );
		WP_CLI::line( "Yummly: " . ( ! empty( $counts['Yummly'] ) ? number_format( absint( $counts['Yummly'] ) ) : "0" ) );

		// Show Email shares if enabled.
		if ( in_array( 'email', $options['included_services'], true ) ) {
			WP_CLI::line( "Email: "  . absint( get_post_meta( $post_id, 'shared_counts_email', true ) ) );
		}

	}

	/**
	 * Update counts for a single post
	 *
	 * Example: wp shared-counts update 123
	 *
	 * @since 1.0.0
	 */
	public function update_single( $args, $assoc_args ) {

		if( isset( $args[0] ) ){
			$post_id = absint( $args[0] );
		} else {
			WP_CLI::error( 'Error. You must supply a post ID.' );
		}

		$post = get_post( $post_id );
		if ( ! $post ) {
			WP_CLI::error( "Post {$post_id} doesn't exist." );
		}

		shared_counts()->core->counts( $post_id, true, true );
		WP_CLI::line( "Counts Updated" );
		// Show latest counts
		WP_CLI::runcommand( "shared-counts display " . $post_id );

	}

}
