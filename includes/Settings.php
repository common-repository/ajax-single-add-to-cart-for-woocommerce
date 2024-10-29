<?php
namespace GPLSCore\GPLS_PLUGIN_ATCFW;

/**
 * Redirects To Checkout Class.
 */
class Settings {

	/**
	 * Core Object
	 *
	 * @var object
	 */
	public $core;

	/**
	 * Plugin Info
	 *
	 * @var object
	 */
	public $plugin_info;

	/**
	 * Settings Name.
	 *
	 * @var string
	 */
	public static $settings_name;

	/**
	 * Default Settings.
	 *
	 * @var array
	 */
	public static $default_settings = array(
		'ajax_add_to_cart' => array(
			'enable_by_product_type_simple'   => 'no',
			'enable_by_product_type_variable' => 'no',
			'enable_by_product_type_grouped'  => 'no',
		),
	);

	/**
	 * Settings Array.
	 *
	 * @var array
	 */
	public static $settings;

	/**
	 * Settings Tab Fields
	 *
	 * @var Array
	 */
	protected $fields = array();

	/**
	 * Constructor.
	 *
	 * @param object $core Core Object.
	 * @param object $plugin_info Plugin Info Object.
	 */
	public function __construct( $core, $plugin_info ) {
		$this->core               = $core;
		$this->plugin_info        = $plugin_info;
		self::$settings_name      = $this->plugin_info['name'] . '-main-settings-name';
		self::$settings           = self::get_main_settings();
		$this->hooks();
	}

	/**
	 * Filters and Actions Hooks.
	 *
	 * @return void
	 */
	public function hooks() {
		add_action( 'woocommerce_settings_products', array( $this, 'settings_tab_action' ), PHP_INT_MAX );
		add_action( 'woocommerce_update_options_products', array( $this, 'save_settings' ), PHP_INT_MAX );
		add_action( 'plugin_action_links_' . $this->plugin_info['basename'], array( $this, 'settings_link' ), 5, 1 );
	}

	/**
	 * Settings Link.
	 *
	 * @param array $links Plugin Row Links.
	 * @return array
	 */
	public function settings_link( $links ) {
		$links[] = '<a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=products&section' ) ) . '">' . esc_html__( 'Settings' ) . '</a>';
		return $links;
	}

	/**
	 * Create the Tab Fields
	 *
	 * @return void
	 */
	public function create_settings_fields() {
		$main_settings = self::get_main_settings();
		// General Tab.
		$this->fields[ $this->plugin_info['name'] ]['general'] = array(
			array(
				'title' => esc_html__( 'Single Product Ajax Add to cart', 'ajax-single-add-to-cart-for-woocommerce' ),
				'type'  => 'title',
				'id'    => $this->plugin_info['name'] . '-add-to-cart-settings-title',
			),
			array(
				'title'         => esc_html__( 'Enable Single Ajax Add To Cart', 'ajax-single-add-to-cart-for-woocommerce' ),
				'desc'          => esc_html__( 'Simple products', 'ajax-single-add-to-cart-for-woocommerce' ),
				'desc_tip'      => esc_html__( 'Enable / Disable Ajax Add to caert in single simple product page', 'ajax-single-add-to-cart-for-woocommerce' ),
				'id'            => self::$settings_name . '[ajax_add_to_cart][enable_by_product_type_simple]',
				'type'          => 'checkbox',
				'checkboxgroup' => 'start',
				'class'         => 'input-checkbox',
				'value'         => $main_settings['ajax_add_to_cart']['enable_by_product_type_simple'],
				'name_keys'     => array( 'ajax_add_to_cart', 'enable_by_product_type_simple' ),
			),
			array(
				'desc_tip'      => esc_html__( 'Enable / Disable Ajax Add to cart in single variable product page', 'ajax-single-add-to-cart-for-woocommerce' ),
				'desc'          => esc_html__( 'Variable products', 'ajax-single-add-to-cart-for-woocommerce' ),
				'id'            => self::$settings_name . '[ajax_add_to_cart][enable_by_product_type_variable]',
				'type'          => 'checkbox',
				'checkboxgroup' => '',
				'class'         => 'input-checkbox',
				'value'         => $main_settings['ajax_add_to_cart']['enable_by_product_type_variable'],
				'name_keys'     => array( 'ajax_add_to_cart', 'enable_by_product_type_variable' ),
			),
			array(
				'desc_tip'      => esc_html__( 'Enable / Disable Ajax Add to cart in single grouped product page', 'ajax-single-add-to-cart-for-woocommerce' ),
				'desc'          => esc_html__( 'Grouped products', 'ajax-single-add-to-cart-for-woocommerce' ),
				'id'            => self::$settings_name . '[ajax_add_to_cart][enable_by_product_type_grouped]',
				'type'          => 'checkbox',
				'checkboxgroup' => 'end',
				'class'         => 'input-checkbox',
				'value'         => $main_settings['ajax_add_to_cart']['enable_by_product_type_grouped'],
				'name_keys'     => array( 'ajax_add_to_cart', 'enable_by_product_type_grouped' ),
			),
			array(
				'name' => '',
				'type' => 'sectionend',
			),
		);
	}

	/**
	 * Show the Settings Tab Fields.
	 *
	 * @return void
	 */
	public function settings_tab_action() {
		if ( empty( $_GET['section'] ) ) {
			$this->create_settings_fields();
			woocommerce_admin_fields( $this->fields[ $this->plugin_info['name'] ]['general'] );
		}
	}

	/**
	 * Get Settings.
	 *
	 * @return array
	 */
	public static function get_main_settings() {
		$settings = \WC_Admin_Settings::get_option( self::$settings_name, self::$default_settings );
		if ( $settings ) {
			return array_merge( self::$default_settings, $settings );
		} else {
			return self::$default_settings;
		}
	}

	/**
	 * Save Tab Settings.
	 *
	 * @return void
	 */
	public function save_settings() {
		$action = '';
		$this->create_settings_fields();
		if ( empty( $_GET['action'] ) ) {
			$action = 'general';
		}

		if ( ! empty( $_GET['action'] ) && in_array( sanitize_text_field( wp_unslash( $_GET['action'] ) ), array_keys( $this->fields[ $this->plugin_info['name'] ] ) ) ) {
			$action = sanitize_text_field( wp_unslash( $_GET['action'] ) );
		}

		// Save Settings.
		if ( ! empty( $_POST[ $this->plugin_info['name'] . '-main-settings-name' ] ) && is_array( $_POST[ $this->plugin_info['name'] . '-main-settings-name' ] ) ) {
			$settings = self::$default_settings;
			foreach ( $this->fields[ $this->plugin_info['name'] ]['general'] as $setting ) {
				if ( ! empty( $setting['name_keys'] ) && ! empty( $_POST[ self::$settings_name ][ $setting['name_keys'][0] ][ $setting['name_keys'][1] ] ) ) {
					$raw_value = wp_unslash( $_POST[ self::$settings_name ][ $setting['name_keys'][0] ][ $setting['name_keys'][1] ] );
					switch ( $setting['type'] ) {
						case 'checkbox':
							$settings[ $setting['name_keys'][0] ][ $setting['name_keys'][1] ] = '1' === $raw_value || 'yes' === $raw_value ? 'yes' : 'no';
							break;
						case 'textarea':
							$settings[ $setting['name_keys'][0] ][ $setting['name_keys'][1] ] = wp_kses_post( trim( $raw_value ) );
							break;
						case 'select':
							$allowed_values = empty( $setting['options'] ) ? array() : array_map( 'strval', array_keys( $setting['options'] ) );
							if ( empty( $setting['default'] ) && empty( $allowed_values ) ) {
								$settings[ $setting['name_keys'][0] ][ $setting['name_keys'][1] ] = null;
								break;
							}
							$default = ( empty( $setting['default'] ) ? $allowed_values[0] : $setting['default'] );
							$settings[ $setting['name_keys'][0] ][ $setting['name_keys'][1] ] = in_array( $raw_value, $allowed_values, true ) ? $raw_value : $default;
							break;

						default:
							$settings[ $setting['name_keys'][0] ][ $setting['name_keys'][1] ] = wc_clean( $raw_value );
							break;
					}
				}
			}
			self::update_main_settings( $settings );
		}
	}

	/**
	 * Update main settings.
	 *
	 * @param array $settings Settings Array.
	 * @return void
	 */
	public static function update_main_settings( $settings ) {
		update_option( self::$settings_name, $settings, true );
	}

	/**
	 * is Ajax add to cart is enabled for provided product type.
	 *
	 * @param string $product_type Product Type.
	 * @return boolean
	 */
	public static function is_global_ajax_add_to_cart_enabled( $product_type ) {
		if ( ! empty( self::$settings['ajax_add_to_cart'][ 'enable_by_product_type_' . $product_type ] ) && ( 'yes' === self::$settings['ajax_add_to_cart'][ 'enable_by_product_type_' . $product_type ] ) ) {
			return true;
		}
		return false;
	}
}
