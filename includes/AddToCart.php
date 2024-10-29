<?php
namespace GPLSCore\GPLS_PLUGIN_ATCFW;

use GPLSCore\GPLS_PLUGIN_ATCFW\Settings;
use GPLSCore\GPLS_PLUGIN_ATCFW\Single;

/**
 * Add To Cart Class.
 */

class AddToCart {

	/**
	 * Core Object
	 *
	 * @var object
	 */
	public static $core;

	/**
	 * Plugin Info Object.
	 *
	 * @var object
	 */
	public static $plugin_info;

	/**
	 * Available Product Types.
	 *
	 * @var array
	 */
	public static $available_product_types = array( 'simple', 'variable', 'variation', 'grouped' );

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
	 * Actions and Filters Hooks.
	 *
	 * @return void
	 */
	public function hooks() {
		add_action( 'wp_ajax_' . self::$plugin_info['name'] . '-ajax_add_to_cart_action', array( $this, 'ajax_add_to_cart' ) );
		add_action( 'wp_ajax_nopriv_' . self::$plugin_info['name'] . '-ajax_add_to_cart_action', array( $this, 'ajax_add_to_cart' ) );
	}

	/**
	 * Ajax Add to Cart Variable Product.
	 *
	 * @return void
	 */
	public function ajax_add_to_cart() {
		if ( empty( $_POST[ self::$plugin_info['general_prefix'] . '-ajax-add-to-cart' ] ) ) {
			wc_add_notice( esc_html__( 'missing product ID', 'ajax-single-add-to-cart-for-woocommerce' ), 'error' );
			wp_send_json(
				array(
					'result'  => false,
					'notices' => wc_print_notices( true ),
				)
			);
		}

		$product_id     = absint( wp_unslash( $_POST[ self::$plugin_info['general_prefix'] . '-ajax-add-to-cart' ] ) );
		$adding_to_cart = wc_get_product( $product_id );

		if ( ! $adding_to_cart ) {
			wc_add_notice( esc_html__( 'Invalid Product ID', 'ajax-single-add-to-cart-for-woocommerce' ), 'error' );
			wp_send_json(
				array(
					'result'  => false,
					'notices' => wc_print_notices( true ),
				)
			);
		}

		$add_to_cart_handler = $adding_to_cart->get_type();
		if ( in_array( $add_to_cart_handler, self::$available_product_types ) ) {
			$result = false;
			if ( 'simple' === $add_to_cart_handler ) {
				$result = $this->add_to_cart_handler_simple( $product_id );
			} elseif ( 'variable' === $add_to_cart_handler || 'variation' === $add_to_cart_handler ) {
				$result = $this->add_to_cart_handler_variable( $product_id );
			} elseif ( 'grouped' === $add_to_cart_handler ) {
				// Allow custom add to cart filter for grouped products which have variable products to our other plugins.
				if ( has_filter( 'woocommerce-custom-' . self::$plugin_info['general_prefix'] . '-add-to-cart-grouped' ) ) {
					$result = apply_filters( 'woocommerce-custom-' . self::$plugin_info['general_prefix'] . '-add-to-cart-grouped', $product_id );
				} else {
					$result = $this->add_to_cart_handler_grouped( $product_id );
				}
			}
			$response = self::prepare_ajax_add_to_cart_response( $result, $adding_to_cart );
			wp_send_json( $response );
		} else {
			wc_add_notice( esc_html__( 'Invalid Product Type', 'ajax-single-add-to-cart-for-woocommerce' ), 'error' );
			wp_send_json(
				array(
					'result'  => false,
					'notices' => wc_print_notices( true ),
				)
			);
		}
	}

	/**
	 * Prepare Ajax Add to Cart Respones
	 *
	 * @param boolean $result   Add To Cart Success-Failure.
	 * @param object  $product  Product Object.
	 * @return array
	 */
	private static function prepare_ajax_add_to_cart_response( $result, $product ) {
		$response            = array();
		$response['result']  = $result;
		$response['notices'] = wc_print_notices( true );

		if ( $result ) {
			// Add to cart.
			ob_start();
			woocommerce_mini_cart();
			$mini_cart             = ob_get_clean();
			$response['fragments'] = apply_filters(
				'woocommerce_add_to_cart_fragments',
				array(
					'div.widget_shopping_cart_content' => '<div class="widget_shopping_cart_content">' . $mini_cart . '</div>',
				)
			);

			$response['cart_hash'] = WC()->cart->get_cart_hash();

			// Filter hook for any custom redirect after ajax add to cart in single product page.
			$redirect_link = apply_filters( self::$plugin_info['general_prefix'] . '-redirect-after-ajax-add-to-cart', false, $product );
			if ( $redirect_link ) {
				$response['redirect_link'] = $redirect_link;
			}
		}

		return $response;
	}

	/**
	 * Add To Cart Handler for Simple Product.
	 *
	 * @param int $product_id Simple Product ID.
	 *
	 * @return boolean
	 */
	private function add_to_cart_handler_simple( $product_id ) {
		$quantity          = empty( $_REQUEST['quantity'] ) ? 1 : wc_stock_amount( wp_unslash( $_REQUEST['quantity'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$passed_validation = apply_filters( 'woocommerce_add_to_cart_validation', true, $product_id, $quantity );
		if ( $passed_validation && false !== WC()->cart->add_to_cart( $product_id, $quantity ) ) {
			wc_add_to_cart_message( array( $product_id => $quantity ), true );
			return true;
		}
		return false;
	}

	/**
	 * Add To Cart Handler for Variable Product.
	 *
	 * @param int $product_id Variable Product ID.
	 *
	 * @return boolean
	 */
	private function add_to_cart_handler_variable( $product_id ) {
		$variation_id = empty( $_REQUEST['variation_id'] ) ? '' : absint( wp_unslash( $_REQUEST['variation_id'] ) );  // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$quantity     = empty( $_REQUEST['quantity'] ) ? 1 : wc_stock_amount( wp_unslash( $_REQUEST['quantity'] ) );  // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$variations   = array();

		$product = wc_get_product( $product_id );

		foreach ( $_REQUEST as $key => $value ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( 'attribute_' !== substr( $key, 0, 10 ) ) {
				continue;
			}

			$variations[ sanitize_title( wp_unslash( $key ) ) ] = wp_unslash( $value );
		}

		$passed_validation = apply_filters( 'woocommerce_add_to_cart_validation', true, $product_id, $quantity, $variation_id, $variations );

		if ( ! $passed_validation ) {
			return false;
		}

		// Prevent parent variable product from being added to cart.
		if ( empty( $variation_id ) && $product && $product->is_type( 'variable' ) ) {
			/* translators: 1: product link, 2: product name */
			wc_add_notice( sprintf( esc_html__( 'Please choose product options by visiting <a href="%1$s" title="%2$s">%2$s</a>.', 'woocommerce' ), esc_url( get_permalink( $product_id ) ), esc_html( $product->get_name() ) ), 'error' );

			return false;
		}

		if ( false !== WC()->cart->add_to_cart( $product_id, $quantity, $variation_id, $variations ) ) {
			wc_add_to_cart_message( array( $product_id => $quantity ), true );
			return true;
		}

		return false;
	}

	/**
	 * Add To Cart Handler for Grouped Product.
	 *
	 * @param int $product_id Grouped Product ID.
	 *
	 * @return boolean
	 */
	public function add_to_cart_handler_grouped( $product_id = null ) {
		$was_added_to_cart = false;
		$added_to_cart     = array();
		$items             = isset( $_REQUEST['quantity'] ) && is_array( $_REQUEST['quantity'] ) ? wp_unslash( $_REQUEST['quantity'] ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		if ( ! empty( $items ) ) {
			$quantity_set = false;

			foreach ( $items as $item => $quantity ) {
				$quantity = wc_stock_amount( $quantity );
				if ( $quantity <= 0 ) {
					continue;
				}
				$quantity_set = true;

				// Add to cart validation.
				$passed_validation = apply_filters( 'woocommerce_add_to_cart_validation', true, $item, $quantity );

				// Suppress total recalculation until finished.
				remove_action( 'woocommerce_add_to_cart', array( WC()->cart, 'calculate_totals' ), 20, 0 );

				if ( $passed_validation && false !== WC()->cart->add_to_cart( $item, $quantity ) ) {
					$was_added_to_cart      = true;
					$added_to_cart[ $item ] = $quantity;
				}

				add_action( 'woocommerce_add_to_cart', array( WC()->cart, 'calculate_totals' ), 20, 0 );
			}

			if ( ! $was_added_to_cart && ! $quantity_set ) {
				wc_add_notice( esc_html__( 'Please choose the quantity of items you wish to add to your cart&hellip;', 'woocommerce' ), 'error' );
			} elseif ( $was_added_to_cart ) {
				wc_add_to_cart_message( $added_to_cart );
				WC()->cart->calculate_totals();
				return true;
			}
		} elseif ( $product_id ) {
			/* Link on product archives */
			wc_add_notice( esc_html__( 'Please choose a product to add to your cart&hellip;', 'woocommerce' ), 'error' );
		}
		return false;
	}
}
