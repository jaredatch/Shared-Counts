<?php
/**
 * Main class.
 *
 * @package    SharedCounts
 * @author     Bill Erickson & Jared Atchison
 * @since      1.0.0
 * @license    GPL-2.0+
 * @copyright  Copyright (c) 2019
 */
final class Shared_Counts {

	/**
	 * Instance of the class.
	 *
	 * @since 1.0.0
	 *
	 * @var object
	 */
	private static $instance;

	/**
	 * Core instance.
	 *
	 * @since 1.0.0
	 *
	 * @var object
	 */
	public $core;

	/**
	 * Admin instance.
	 *
	 * @since 1.0.0
	 *
	 * @var object
	 */
	public $admin;

	/**
	 * Front-end instance.
	 *
	 * @since 1.0.0
	 *
	 * @var object
	 */
	public $front;

	/**
	 * Shared Counts Instance.
	 *
	 * @since 1.0.0
	 *
	 * @return Shared_Counts
	 */
	public static function instance() {

		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof Shared_Counts ) ) {

			self::$instance = new Shared_Counts();
			self::$instance->load_textdomain();
			self::$instance->install();
			self::$instance->includes();

			add_action( 'init', [ self::$instance, 'init' ] );
		}
		return self::$instance;
	}

	/**
	 * Loads the plugin language files.
	 *
	 * @since 1.0.0
	 */
	public function load_textdomain() {

		load_plugin_textdomain( 'shared-counts', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

	/**
	 * Install procedure.
	 *
	 * @since 1.0.0
	 */
	public function install() {

		// When activated, run install.
		register_activation_hook(
			SHARED_COUNTS_FILE,
			function() {

				do_action( 'shared_counts_install' );

				// Set current version, to be referenced in future updates.
				update_option( 'shared_counts_version', SHARED_COUNTS_VERSION );
			}
		);
	}

	/**
	 * Load includes.
	 *
	 * @since 1.0.0
	 */
	public function includes() {

		require_once SHARED_COUNTS_DIR . 'includes/class-shared-counts-core.php';
		require_once SHARED_COUNTS_DIR . 'includes/class-shared-counts-admin.php';
		require_once SHARED_COUNTS_DIR . 'includes/class-shared-counts-front.php';
	}

	/**
	 * Bootstap.
	 *
	 * @since 1.0.0
	 */
	public function init() {

		$this->core  = new Shared_Counts_Core();
		$this->admin = new Shared_Counts_Admin();
		$this->front = new Shared_Counts_Front();
	}

	/**
	 * Helper to access link method directly, for backwards compatibility.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $types      Button types.
	 * @param int    $id         Post or Site ID.
	 * @param bool   $echo       Echo or return.
	 * @param string $style      Button style.
	 * @param int    $round      How many significant digits on count.
	 * @param mixed  $show_empty Show counts when empty.
	 *
	 * @return string
	 */
	public function link( $types = 'facebook', $id = false, $echo = true, $style = 'generic', $round = 2, $show_empty = '' ) {

		return $this->front->link( $types, $id, $echo, $style, $round, $show_empty );
	}

	/**
	 * Helper to access count method directly, for backwards compatibility.
	 *
	 * @since 1.0.0
	 *
	 * @param int    $id    Post or Site ID.
	 * @param string $type  Button type.
	 * @param bool   $echo  Echo or return.
	 * @param int    $round How many significant digits on count.
	 *
	 * @return string
	 */
	public function count( $id = false, $type = 'facebook', $echo = false, $round = 2 ) {

		return $this->core->count( $id, $type, $echo, $round );
	}
}
