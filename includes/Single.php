<?php
namespace GPLSCore\GPLS_PLUGIN_ATCFW;

use GPLSCore\GPLS_PLUGIN_ATCFW\Settings;

/**
 * Single Product Class.
 */
class Single {

	/**
	 * Core Object
	 *
	 * @var object
	 */
	public static $core;

	/**
	 * Plugin Info
	 *
	 * @var object
	 */
	public static $plugin_info;

	/**
	 * Constructor.
	 *
	 * @param object $core Core Object.
	 * @param object $plugin_info Plugin Info Object.
	 */
	public function __construct( $core, $plugin_info ) {
		self::$core        = $core;
		self::$plugin_info = $plugin_info;
		$this->hooks();
	}

	/**
	 * Filters and Actions Hooks.
	 *
	 * @return void
	 */
	public function hooks() {
		add_action( 'wp_enqueue_scripts', array( $this, 'front_assets' ), PHP_INT_MAX );
	}

	/**
	 * Front Assets.
	 *
	 * @return void
	 */
	public function front_assets() {
		if ( is_product() ) {

			if ( ! wp_script_is( 'wc-add-to-cart' ) ) {
				wp_enqueue_script( 'wc-add-to-cart' );
			}

			wp_enqueue_script( self::$plugin_info['name'] . '-single-js-actions', self::$plugin_info['url'] . 'assets/dist/js/front/actions.min.js', array( 'jquery' ), self::$plugin_info['version'], true );
			wp_localize_script(
				self::$plugin_info['name'] . '-single-js-actions',
				str_replace( '-', '_', self::$plugin_info['name'] . '-localize-vars' ),
				array(
					'prefix'                  => self::$plugin_info['name'],
					'ajax_url'                => admin_url( 'admin-ajax.php' ),
					'nonce'                   => wp_create_nonce( self::$plugin_info['name'] . '-ajax-add-to-cart-nonce' ),
					'woo_single_context'      => $this->get_current_woocommerce_single_context(),
					'single_ajax_add_to_cart' => $this->ajax_add_to_cart_status(),
				)
			);
		}
	}

	/**
	 * Localize Single Product JS params.
	 *
	 * @return array
	 */
	public function ajax_add_to_cart_status() {
		if ( is_product() ) {
			global $wp_query;
			$product_id  = $wp_query->get_queried_object_id();
			$product_obj = wc_get_product( $product_id );
			if ( ! is_null( $product_obj ) && is_object( $product_obj ) ) {
				return self::is_ajax_add_to_cart_enabled( $product_obj );
			}
		}
		return false;
	}

	/**
	 * Add current context to the localize array.
	 *
	 * @return array
	 */
	public function get_current_woocommerce_single_context() {
		global $wp_query;

		$context = '';
		if ( is_product() ) {
			$context = 'single';
			if ( $wp_query && is_object( $wp_query ) && ! is_wp_error( $wp_query ) ) {
				$product_id      = $wp_query->get_queried_object_id();
				$queried_product = wc_get_product( $product_id );
				if ( is_object( $queried_product ) && ! is_wp_error( $queried_product ) ) {
					$context = 'single-' . $queried_product->get_type();
				}
			}
		}
		return $context;
	}

	/**
	 * Hide Buy Now Button.
	 *
	 * @param object $product Product Object.
	 * @return boolean
	 */
	public static function is_ajax_add_to_cart_enabled( $product ) {
		$product_type = $product->get_type();
		if ( Settings::is_global_ajax_add_to_cart_enabled( $product_type ) ) {
			return true;
		} else {
			return false;
		}
	}

}
