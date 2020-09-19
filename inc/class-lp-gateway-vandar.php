<?php
defined( 'ABSPATH' ) || exit;
if ( ! class_exists( 'LP_Gateway_Vandar' ) ) {
	class LP_Gateway_Vandar extends LP_Gateway_Abstract {
		private $form_data = array();
		private $wsdl = 'https://vandar.io/api/ipg/send';
		private $startPay = 'https://vandar.io/ipg/';
		private $verifyUrl = 'https://vandar.io/api/ipg/verify';
		private $merchantID = null;
		protected $settings = null;
		protected $order = null;
		protected $posted = null;
		protected $authority = null;

		public function __construct() {

			$this->id = 'vandar';

			$this->method_title       =  __( 'وندار', 'vandar-learnpress' );;
			$this->method_description = __( 'با وندار پرداخت کنید.', 'vandar-learnpress' );
			$this->icon               = '';
			$this->title       = LP()->settings->get( "{$this->id}.title", $this->method_title );
			$this->description = LP()->settings->get( "{$this->id}.description", $this->method_description );
			$settings = LP()->settings;
			
			if ( $settings->get( "{$this->id}.enable" ) ) {
				$this->settings                     = array();
				$this->settings['merchant_id']        = $settings->get( "{$this->id}.merchant_id" );
				
			}
			
			$this->merchantID = $this->settings['merchant_id'];
			if ( did_action( 'learn_press/vandar-add-on/loaded' ) ) {
				return;
			}
			add_filter( 'learn-press/payment-gateway/' . $this->id . '/available', array(
				$this,
				'vandar_available'
			), 10, 2 );
			do_action( 'learn_press/vandar-add-on/loaded' );
			parent::__construct();
			if ( did_action( 'init' ) ) {
				
				$this->register_web_hook();
			} else {
				
				add_action( 'init', array( $this, 'register_web_hook' ) );
			}
			add_action( 'learn_press_web_hooks_processed', array( $this, 'web_hook_process_vandar' ) );
			add_action("learn-press/before-checkout-order-review", array( $this, 'error_message' ));
		}
		public function register_web_hook() {
			
			learn_press_register_web_hook( 'vandar', 'learn_press_vandar' );
			
		}
		
		public function get_settings() {
			
			return apply_filters( 'learn-press/gateway-payment/vandar/settings',
				array(
					array(
						'title'   => __( 'فعال سازی', 'vandar-learnpress' ),
						'id'      => '[enable]',
						'default' => 'no',
						'type'    => 'yes-no'
					),
					array(
						'type'       => 'text',
						'title'      => __( 'نام درگاه', 'vandar-learnpress' ),
						'default'    => __( 'وندار', 'vandar-learnpress' ),
						'id'         => '[title]',
						'class'      => 'regular-text',
						'visibility' => array(
							'state'       => 'show',
							'conditional' => array(
								array(
									'field'   => '[enable]',
									'compare' => '=',
									'value'   => 'yes'
								)
							)
						)
					),
					array(
						'type'       => 'textarea',
						'title'      => __( 'توضیحات درگاه', 'vandar-learnpress' ),
						'default'    => __( 'پرداخت امن توسط درگاه وندار', 'vandar-learnpress' ),
						'id'         => '[description]',
						'editor'     => array(
							'textarea_rows' => 5
						),
						'css'        => 'height: 100px;',
						'visibility' => array(
							'state'       => 'show',
							'conditional' => array(
								array(
									'field'   => '[enable]',
									'compare' => '=',
									'value'   => 'yes'
								)
							)
						)
					),
					array(
						'title'      => __( 'پین درگاه', 'vandar-learnpress' ),
						'id'         => '[merchant_id]',
						'type'       => 'text',
						'visibility' => array(
							'state'       => 'show',
							'conditional' => array(
								array(
									'field'   => '[enable]',
									'compare' => '=',
									'value'   => 'yes'
								)
							)
						)
					)
				)
			);
		}

		public function get_payment_form() {
		
			ob_start();
			$template = learn_press_locate_template( 'form.php', learn_press_template_path() . '/addons/vandar-payment/', LP_ADDON_VANDAR_PAYMENT_TEMPLATE );
			
			include $template;
			
			return ob_get_clean();
		}

		public function error_message() {
		
			if(!isset($_SESSION))
				@session_start();
			if(isset($_SESSION['vandar_error']) && intval($_SESSION['vandar_error']) === 1) {
				
				$_SESSION['vandar_error'] = 0;
				$template = learn_press_locate_template( 'payment-error.php', learn_press_template_path() . '/addons/vandar-payment/', LP_ADDON_VANDAR_PAYMENT_TEMPLATE );
				include $template;
			}
		}
		public function get_icon() {
		
			if ( empty( $this->icon ) ) {

				$this->icon = LP_ADDON_VANDAR_PAYMENT_URL . 'assets/images/vandar.png';
			}
			return parent::get_icon();
		}
		public function vandar_available() {
			
			if ( LP()->settings->get( "{$this->id}.enable" ) != 'yes' ) {
				return false;
			}
			return true;
		}
		public function get_form_data() {
		
		
			if ( $this->order ) {
				$user            = learn_press_get_current_user();
				$currency_code = learn_press_get_currency()  ;
				if ($currency_code == 'IRR') {
					$amount = $this->order->order_total / 10 ;
				} else {
					$amount = $this->order->order_total ;
				}
				$this->form_data = array(
					'amount'      => $amount,
					'currency'    => strtolower( learn_press_get_currency() ),
					'token'       => $this->token,
					'description' => sprintf( __("ایمیل خریدار %s","vandar-learnpress"), $user->get_data( 'email' ) ),
					'customer'    => array(
						'name'          => $user->get_data( 'display_name' ),
						'billing_email' => $user->get_data( 'email' ),
					),
					'errors'      => isset( $this->posted['form_errors'] ) ? $this->posted['form_errors'] : ''
				);
			}
			return $this->form_data;
		}
		public function validate_fields() {
			
			$posted        = learn_press_get_request( 'learn-press-vandar' );
			$email   = !empty( $posted['email'] ) ? $posted['email'] : "";
			$mobile  = !empty( $posted['mobile'] ) ? $posted['mobile'] : "";
			$error_message = array();
			if ( !empty( $email ) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
				$error_message[] = __( 'ایمیل نامعتبر است.', 'vandar-learnpress' );
			}
			if ( !empty( $mobile ) && !preg_match("/^(09)(\d{9})$/", $mobile)) {
				
				$error_message[] = __( 'تلفن همراه نامعتبر است.', 'vandar-learnpress' );
			}
			
			if ( $error = sizeof( $error_message ) ) {
				
			throw new Exception( sprintf( '<div>%s</div>', join( '</div><div>', $error_message ) ), 8000 );
			}

			$this->posted = $posted;

			return $error ? false : true;
		}
		public function process_payment( $order ) {
			
			$this->order = learn_press_get_order( $order );
			$token = $this->get_vandar_authority();
			$gateway_url = $this->startPay.$token;
			
			// header("Location: $gateway_url");
			$json = array(
				'result'   => $gateway_url ? 'success' : 'fail',
				'redirect'   => $gateway_url ? $gateway_url : ''
			);

			return $json;
		}

		public function get_vandar_authority() {
			
			if ( $this->get_form_data() ) {
				$checkout = LP()->checkout();
				$data = [
					'amount' => $this->form_data['amount'],
					'api_key' => $this->merchantID,
					'callback_url' => get_site_url() . '/?' . learn_press_get_web_hook( 'vandar' ) . '=1&order_id='.$this->order->get_id(),
					'factorNumber'=> $this->order->get_id(),
					'description' => $this->form_data['description'],
					
				];
			

				$result = $this->rest_payment_request($this->wsdl,$data);

				if ($result['status'] == 1) {
					return $result['token'];
				}
			
			}
			return false;
		}
		public function web_hook_process_vandar() {
			$learn_press_vandar = sanitize_text_field($_REQUEST['learn_press_vandar']);
			$order_id = sanitize_text_field($_REQUEST['order_id']);
			if(isset($learn_press_vandar) && intval($learn_press_vandar) === 1) {
				$order = LP_Order::instance( $order_id );
				$currency_code = learn_press_get_currency();
				if ($currency_code == 'IRR') {
					$amount = $order->order_total / 10 ;
				} else {
					$amount = $order->order_total ;
				}
				$ntoken = sanitize_text_field($_GET['token']) ;
				$data = array(
						// 'amount' => $amount,
						'token' => $ntoken,
						'api_key' => $this->merchantID,
					);
				$result = $this->rest_payment_verification($this->verifyUrl,$data);
				
					if ($result['status'] == 1) {
						// $refid = sanitize_text_field($request["RefID"]);
						// $transId = sanitize_text_field($result['transId']);
						$_REQUEST["RefID"] = $result['transId'];

						// sanitize_text_field($request["RefID"]) = sanitize_text_field($result['transId']);

					$this->authority = intval($result);
					$this->payment_status_completed($order , $_REQUEST);
					wp_redirect(esc_url( $this->get_return_url( $order ) ));
					exit();	
				}
				if(!isset($_SESSION)){
					session_start();
					$_SESSION['vandar_error'] = 1;
					
					wp_redirect(esc_url( learn_press_get_page_link( 'checkout' ) ));
					exit();
				}
				
				
			}
		}


		public function rest_payment_request($url, $params)
		{
			$result = wp_remote_post($url, array(
				'method' => 'POST',
				'headers'  => array(
					'Content-type: application/x-www-form-urlencoded',
					'Accept: application/json'
				),
				'timeout' => 30,
				'body' => $params
			));

			if (is_wp_error($result)) {
				return $result->get_error_message();
			} else {
				return json_decode(wp_remote_retrieve_body($result), true);
			}
		}
		
		public function rest_payment_verification($url,$params) {
			$result = wp_remote_post($url, array(
				'method' => 'POST',
				'headers'  => array(
					'Content-type: application/x-www-form-urlencoded',
					'Accept: application/json'
				),
				'timeout' => 30,
				'body' => $params
			));

			if (is_wp_error($result)) {
				return $result->get_error_message();
			} else {
				return json_decode(wp_remote_retrieve_body($result), true);
			}
		}

		protected function payment_status_completed( $order, $request ) {
			
			if ( $order->has_status( 'completed' ) ) {
				exit;
			}

			$RefID = sanitize_text_field($request['RefID']);
			$Authority = sanitize_text_field($request['Authority']);

			$this->payment_complete( $order, ( !empty( $RefID ) ? $RefID : '' ), __( 'Payment has been successfully completed', 'vandar-learnpress' ) );
			
			update_post_meta( $order->get_id(), '_vandar_RefID', $RefID );
			update_post_meta( $order->get_id(), '_vandar_authority', $Authority );
		}
		protected function payment_status_pending( $order, $request ) {
			
			$this->payment_status_completed( $order, $request );
		}
		public function payment_complete( $order, $trans_id = '', $note = '' ) {
			
			$order->payment_complete( $trans_id );
		}
	}
}