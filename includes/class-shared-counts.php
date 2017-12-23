<?php
/**
 * Main class.
 *
 * @package    SharedCounts
 * @author     Bill Erickson & Jared Atchison
 * @since      1.7.0
 * @license    GPL-2.0+
 * @copyright  Copyright (c) 2015
 */
final class EA_Share_Count {

	/**
	 * Instance of the class.
	 *
	 * @since 1.0.0
	 * @var object
	 */
	private static $instance;

	/**
	 * Plugin version.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private $version = '2.0.0';

	/**
	 * Core instance
	 *
	 * @since 1.3.0
	 * @var object
	 */
	public $core;

	/**
	 * Admin instance
	 *
	 * @since 1.3.0
	 * @var object
	 */
	public $admin;

	/**
	 * Front-end instance
	 *
	 * @since 1.3.0
	 * @var object
	 */
	public $front;

	/**
	 * Share Count Instance.
	 *
	 * @since 1.0.0
	 * @return EA_Share_Count
	 */
	public static function instance() {

		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof EA_Share_Count ) ) {

			self::$instance = new EA_Share_Count();
			self::$instance->load_textdomain();
			self::$instance->install();
			self::$instance->includes();

			add_action( 'init', array( self::$instance, 'init' ) );
		}
		return self::$instance;
	}

	/**
	 * Loads the plugin language files.
	 *
	 * @since 2.0.0
	 */
	public function load_textdomain() {

			load_plugin_textdomain( 'share-count-plugin', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

	/**
	 * Install procedure.
	 *
	 * @since 1.0.0
	 */
	public function install() {

		// When activated, run install.
		register_activation_hook( EA_SHARE_COUNT_FILE, function() {

			do_action( 'ea_share_count_install' );

			// Set current version, to be referenced in future updates.
			update_option( 'ea_share_count_version', EA_SHARE_COUNT_VERSION );
		} );
	}

	/**
	 * Load includes.
	 *
	 * @since 1.3.0
	 */
	public function includes() {

		require_once EA_SHARE_COUNT_DIR . 'includes/class-shared-counts-core.php';
		require_once EA_SHARE_COUNT_DIR . 'includes/class-shared-counts-admin.php';
		require_once EA_SHARE_COUNT_DIR . 'includes/class-shared-counts-front.php';
	}

	/**
	 * Bootstap.
	 *
	 * @since 1.3.0
	 */
	public function init() {

		$this->core  = new EA_Share_Count_Core();
		$this->admin = new EA_Share_Count_Admin();
		$this->front = new EA_Share_Count_Front();
	}

	/**
	 * Helper to access link method directly, for backwards compatibility.
	 *
	 * @since 1.3.0
	 * @param array $types
	 * @param int $id
	 * @param bool $echo
	 * @param string $style
	 * @param int $round
	 * @param mixed $show_empty
	 * @return string
	 */
	public function link( $types = 'facebook', $id = false, $echo = true, $style = 'generic', $round = 2, $show_empty = '' ) {

		return $this->front->link( $types, $id, $echo, $style, $round, $show_empty );
	}

	/**
	 * Helper to access count method directly, for backwards compatibility.
	 *
	 * @since 1.3.0
	 * @param int $id
	 * @param string $type
	 * @param bool $echo
	 * @param int $round
	 * @return string
	 */
	public function count( $id = false, $type = 'facebook', $echo = false, $round = 2 ) {

		return $this->core->count( $id, $type, $echo, $round );
	}
}
