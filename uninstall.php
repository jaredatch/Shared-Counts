<?php
/**
 * Uninstall Shared Counts.
 *
 * @package    SharedCounts
 * @author     Bill Erickson & Jared Atchison
 * @since      1.0.0
 * @license    GPL-2.0+
 * @copyright  Copyright (c) 2017
 */

// Exit if accessed directly.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

// Remove all plugin options.
$wpdb->query( "DELETE FROM `{$wpdb->options}` WHERE `option_name` LIKE 'shared_counts%'" ); // phpcs:ignore

// Remove all plugin post_meta keys.
$wpdb->query( "DELETE FROM `{$wpdb->postmeta}` WHERE `meta_key` LIKE 'shared_counts%'" ); // phpcs:ignore
