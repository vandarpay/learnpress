<?php
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'LP_Addon_Vandar_Payment' ) ) {
	
	class LP_Addon_Vandar_Payment extends LP_Addon {
		
		public $version = LP_ADDON_VANDAR_PAYMENT_VER;
		public $require_version = LP_ADDON_VANDAR_PAYMENT_REQUIRE_VER;
		public function __construct() {
			parent::__construct();
		}
		protected function _define_constants() {
			
			define( 'LP_ADDON_VANDAR_PAYMENT_PATH', dirname( LP_ADDON_VANDAR_PAYMENT_FILE ) );
			define( 'LP_ADDON_VANDAR_PAYMENT_INC', LP_ADDON_VANDAR_PAYMENT_PATH . '/inc/' );
			define( 'LP_ADDON_VANDAR_PAYMENT_URL', plugin_dir_url( LP_ADDON_VANDAR_PAYMENT_FILE ) );
			define( 'LP_ADDON_VANDAR_PAYMENT_TEMPLATE', LP_ADDON_VANDAR_PAYMENT_PATH . '/templates/' );
			
		}
		protected function _includes() {
			
			include_once LP_ADDON_VANDAR_PAYMENT_INC . 'class-lp-gateway-vandar.php';
		}
		protected function _init_hooks() {
			
			// add payment gateway class
			add_filter( 'learn_press_payment_method', array( $this, 'add_payment' ) );
			add_filter( 'learn-press/payment-methods', array( $this, 'add_payment' ) );
		}
		protected function _enqueue_assets() {
			
			return;
			if (LP()->settings->get( 'learn_press_vandar.enable' ) == 'yes' ) {
				$user = learn_press_get_current_user();

				learn_press_assets()->enqueue_script( 'learn-press-vandar-payment', $this->get_plugin_url( 'assets/js/script.js' ), array() );
				learn_press_assets()->enqueue_style( 'learn-press-vandar', $this->get_plugin_url( 'assets/css/style.css' ), array() );

				$data = array(
					'plugin_url'  => plugins_url( '', LP_ADDON_VANDAR_PAYMENT_FILE )
				);
				wp_localize_script( 'learn-press-vandar', 'learn_press_vandar_info', $data );
			}
		}
		public function add_payment( $methods ) {
			
			$methods['vandar'] = 'LP_Gateway_Vandar';
		
			return $methods;
		}
		public function plugin_links() {
			
			$links[] = '<a href="' . admin_url( 'admin.php?page=learn-press-settings&tab=payments&section=vandar' ) . '">' . __( 'Settings', 'vandar-learnpress' ) . '</a>';

			return $links;
		}
	}
}