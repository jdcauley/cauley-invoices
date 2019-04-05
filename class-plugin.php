<?php

namespace Cauley\Invoices;

require_once( 'inc/class-mv-dbi.php');
require_once( 'inc/class-api-services.php');
require_once( 'inc/invoices/class-invoices.php' );

if ( ! defined( 'ABSPATH' ) ) {
	die;
}

class Plugin {

	const VERSION = '0.0.4';

	const DB_VERSION = '0.0.4';

	const PLUGIN_DOMAIN = 'cauley_invoices';

	const PLUGIN_FILE_PATH = __FILE__;

	const PLUGIN_ACTIVATION_FILE = 'cauley-invoices.php';

	public $api_route = 'cauley-invoices';

	public $api_version = 'v1';

	public static $models = null;

	private static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self;
			self::$instance->init();
			Invoices::get_instance();
		}
		return self::$instance;
	}

	public static function get_activation_path() {
		return dirname( __FILE__ ) . '/' . self::PLUGIN_ACTIVATION_FILE;
	}

	public function plugin_activation() {
		// This runs after all plugins are loaded so it can run after update
		if ( get_option( 'cauley_invoices_version' ) === self::VERSION ) {
			return;
		}

		do_action( self::PLUGIN_DOMAIN . '_plugin_activated' );
		update_option( 'cauley_invoices_version', self::VERSION );
		flush_rewrite_rules();
	}

	public function plugin_deactivation() {
		do_action( self::PLUGIN_DOMAIN . '_plugin_deactivated' );
		flush_rewrite_rules();
	}

	public function generate_tables() {
		\Mediavine\MV_DBI::upgrade_database_check( self::PLUGIN_DOMAIN, self::DB_VERSION );
	}

	public function init() {

		register_activation_hook( self::get_activation_path(), array( $this, 'plugin_activation' ) );
		register_deactivation_hook( self::get_activation_path(), array( $this, 'plugin_deactivation' ) );

		add_action( self::PLUGIN_DOMAIN . '_plugin_activated', array( $this, 'generate_tables' ), 20 );

		self::$models = \Mediavine\MV_DBI::get_models(
			array(
				'cauley_invoices',
			)
		);
	}

}

$Plugin = Plugin::get_instance();
