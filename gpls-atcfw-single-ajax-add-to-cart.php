<?php

namespace GPLSCore\GPLS_PLUGIN_ATCFW;

/**
 * Plugin Name:  Ajax Single Add to cart for WooCommerce
 * Description:  The plugin allows AJAX add to cart without refresh in single product page for all product types.
 * Author:       GrandPlugins
 * Author URI:   https://profiles.wordpress.org/grandplugins/
 * Plugin URI:   https://grandplugins.com/product/ajax-single-add-to-cart-for-woocommerce-pro/
 * Domain Path:  /languages
 * Requires PHP: 5.6
 * Text Domain:  ajax-single-add-to-cart-for-woocommerce
 * Std Name:     gpls-atcfw-single-ajax-add-to-cart
 * Version:      1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use GPLSCore\GPLS_PLUGIN_ATCFW\Core;


if ( ! class_exists( __NAMESPACE__ . '\GPLS_ATCFW_WOO_Ajax_Add_To_Cart_For_WooCommerce' ) ) :


	/**
	 * Exporter Main Class.
	 */
	class GPLS_ATCFW_WOO_Ajax_Add_To_Cart_For_WooCommerce {

		/**
		 * Single Instance
		 *
		 * @var object
		 */
		private static $instance;

		/**
		 * Plugin Info
		 *
		 * @var array
		 */
		private static $plugin_info;

		/**
		 * Debug Mode Status
		 *
		 * @var bool
		 */
		protected $debug = false;

		/**
		 * Core Object
		 *
		 * @var object
		 */
		private static $core;

		/**
		 * Settings Class Object.
		 *
		 * @var object
		 */
		public $settings;

		/**
		 * Single Product Class Object
		 *
		 * @var object
		 */
		public $single;

		/**
		 * AddToCart Class Object
		 *
		 * @var object
		 */
		public $add_to_cart;

		/**
		 * Singular init Function.
		 *
		 * @return Object
		 */
		public static function init() {
			if ( ! isset( self::$instance ) ) {
				self::$instance = new self();
			}
			return self::$instance;
		}

		/**
		 * Core Actions Hook.
		 *
		 * @return void
		 */
		public static function core_actions( $action_type ) {
			require_once trailingslashit( plugin_dir_path( __FILE__ ) ) . 'core/bootstrap.php';
			self::$core = new Core( self::$plugin_info );
			if ( 'activated' === $action_type ) {
				self::$core->plugin_activated();
			} elseif ( 'deactivated' === $action_type ) {
				self::$core->plugin_deactivated();
			} elseif ( 'uninstall' === $action_type ) {
				self::$core->plugin_uninstalled();
			}
		}

		/**
		 * Plugin Activated Hook.
		 *
		 * @return void
		 */
		public static function plugin_activated() {
			self::setup_plugin_info();
			if ( is_plugin_active( self::$plugin_info['text_domain'] . '-pro/' . self::$plugin_info['name'] . '-pro.php' ) ) {
				deactivate_plugins( plugin_basename( self::$plugin_info['text_domain'] . '-pro/' . self::$plugin_info['name'] . '-pro.php' ) );
			}
			self::core_actions( 'activated' );
		}

		/**
		 * Plugin Deactivated Hook.
		 *
		 * @return void
		 */
		public static function plugin_deactivated() {
			self::setup_plugin_info();
			self::core_actions( 'deactivated' );
		}

		/**
		 * Plugin Installed hook.
		 *
		 * @return void
		 */
		public static function plugin_uninstalled() {
			self::setup_plugin_info();
			self::core_actions( 'uninstall' );
		}

		/**
		 * Constructor
		 */
		public function __construct() {
			self::setup_plugin_info();
			$this->load_languages();
			$this->setup();
			$this->includes();

			self::$core        = new Core( self::$plugin_info );
			$this->settings    = new Settings( self::$core, self::$plugin_info );
			$this->single      = new Single( self::$core, self::$plugin_info );
			$this->add_to_cart = new AddToCart( self::$core, self::$plugin_info );
		}

		/**
		 * Includes Files
		 *
		 * @return void
		 */
		public function includes() {
			require_once trailingslashit( plugin_dir_path( __FILE__ ) ) . 'core/bootstrap.php';
		}

		/**
		 * Load languages Folder.
		 *
		 * @return void
		 */
		public function load_languages() {
			load_plugin_textdomain( self::$plugin_info['text_domain'], false, self::$plugin_info['path'] . 'languages/' );
		}

		/**
		 * Setup Function - Initialize Vars
		 *
		 * @return void
		 */
		public function setup() {
			$this->options_page_slug = self::$plugin_info['name'];
			$this->options_page_url  = admin_url( 'tools.php' ) . '?page=' . self::$plugin_info['name'];
		}

		/**
		 * Set Plugin Info
		 *
		 * @return array
		 */
		public static function setup_plugin_info() {
			$plugin_data = get_file_data(
				__FILE__,
				array(
					'Version'     => 'Version',
					'Name'        => 'Plugin Name',
					'URI'         => 'Plugin URI',
					'SName'       => 'Std Name',
					'text_domain' => 'Text Domain',
				),
				false
			);

			self::$plugin_info = array(
				'id'             => 689,
				'basename'       => plugin_basename( __FILE__ ),
				'version'        => $plugin_data['Version'],
				'name'           => $plugin_data['SName'],
				'text_domain'    => $plugin_data['text_domain'],
				'file'           => __FILE__,
				'plugin_url'     => $plugin_data['URI'],
				'public_name'    => $plugin_data['Name'],
				'path'           => trailingslashit( plugin_dir_path( __FILE__ ) ),
				'url'            => trailingslashit( plugin_dir_url( __FILE__ ) ),
				'options_page'   => $plugin_data['SName'],
				'localize_var'   => str_replace( '-', '_', $plugin_data['SName'] ) . '_localize_data',
				'type'           => 'free',
				'general_prefix' => 'gpls-plugins-general-prefix',
				'classes_prefix' => 'gpls-atcfw',
			);
		}

		/**
		 * Define Constants
		 *
		 * @param string $key
		 * @param string $value
		 * @return void
		 */
		public function define( $key, $value ) {
			if ( ! defined( $key ) ) {
				define( $key, $value );
			}
		}

	}

	add_action( 'plugins_loaded', array( __NAMESPACE__ . '\GPLS_ATCFW_WOO_Ajax_Add_To_Cart_For_WooCommerce', 'init' ), 10 );
	register_activation_hook( __FILE__, array( __NAMESPACE__ . '\GPLS_ATCFW_WOO_Ajax_Add_To_Cart_For_WooCommerce', 'plugin_activated' ) );
	register_deactivation_hook( __FILE__, array( __NAMESPACE__ . '\GPLS_ATCFW_WOO_Ajax_Add_To_Cart_For_WooCommerce', 'plugin_deactivated' ) );
	register_uninstall_hook( __FILE__, array( __NAMESPACE__ . '\GPLS_ATCFW_WOO_Ajax_Add_To_Cart_For_WooCommerce', 'plugin_uninstalled' ) );
endif;
