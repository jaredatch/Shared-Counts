<?php
/**
 * Plugin Name: Shared Counts
 * Plugin URI:  https://wordpress.org/plugins/shared-counts/
 * Description: A lean plugin for quickly retrieving, caching, and displaying various social sharing counts and buttons.
 * Author:      Bill Erickson & Jared Atchison
 * Version:     2.0.0
 *
 * EA Share Count is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.
 *
 * EA Share Count is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with EA Share Count. If not, see <http://www.gnu.org/licenses/>.
 *
 * @package    EA_ShareCount
 * @author     Bill Erickson & Jared Atchison
 * @since      1.0.0
 * @license    GPL-2.0+
 * @copyright  Copyright (c) 2015
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Basic plugin constants.
 */
// Version.
define( 'EA_SHARE_COUNT_VERSION', '1.0.0' );

// Directory path.
define( 'EA_SHARE_COUNT_DIR', plugin_dir_path( __FILE__ ) );

// Directory URL.
define( 'EA_SHARE_COUNT_URL', plugin_dir_url( __FILE__ ) );

// Base name.
define( 'EA_SHARE_COUNT_BASE', plugin_basename( __FILE__ ) );

// Plugin root file.
define( 'EA_SHARE_COUNT_FILE', __FILE__ );

/**
 * We require a modern supported version of PHP (5.6+).
 */
if ( version_compare( PHP_VERSION, '5.6', '<' ) ) {

	/**
	 * Deactivate plugin.
	 *
	 * @since 2.0.0
	 */
	add_action( 'admin_init', function() {

		deactivate_plugins( plugin_basename( __FILE__ ) );
	} );

	/**
	 * Display notice after deactivation.
	 *
	 * @since 2.0.0
	 */
	add_action( 'admin_notices', function() {

		echo '<div class="notice notice-warning"><p>' . __( 'Share Count Plugin requires PHP 5.6+. Contact your web host to update.', 'share-count-plugin' ) . '</p></div>';

		if ( isset( $_GET['activate'] ) ) {
			unset( $_GET['activate'] );
		}
	} );

} else {

	/**
	 * Load the primary.
	 */
	require_once EA_SHARE_COUNT_DIR . 'includes/class-shared-counts.php';

	/**
	 * The function provides access to the sharing methods.
	 *
	 * Use this function like you would a global variable, except without needing
	 * to declare the global.
	 *
	 * @since 1.0.0
	 * @return object
	 */
	function ea_share() {

		return EA_Share_Count::instance();
	}
	ea_share();
}
