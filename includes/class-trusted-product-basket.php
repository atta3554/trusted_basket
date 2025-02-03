<?php

// Exit if accessed directly
if( ! defined('ABSPATH') ) exit();

class Trusted_Product_Basket_Main {
	
	private static $instance;
	private $version;
	private $orders_proccessing_failed;

	public static function instance() {
		if( ! isset(self::$instance) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function __construct() {
		
		$this->version = '1.0.0';
		$this->orders_proccessing_failed = false;

		define('TRUSTED_PRODUCT_BASKET_URL', plugin_dir_url(__DIR__));
		define('TRUSTED_PRODUCT_BASKET_ASSETS_URL', TRUSTED_PRODUCT_BASKET_URL . 'assets/');
		define('TRUSTED_PRODUCT_BASKET_PATH', plugin_dir_path(__DIR__));
		define('TRUSTED_PRODUCT_BASKET_INC_PATH', TRUSTED_PRODUCT_BASKET_PATH . 'includes');
		define('TRUSTED_PRODUCT_BASKET_ASSETS_PATH', TRUSTED_PRODUCT_BASKET_PATH . 'assets');
		define('TRUSTED_PRODUCT_BASKET_TEMPLATES_PATH', TRUSTED_PRODUCT_BASKET_PATH . 'templates');

		$this->init_hooks();
	}

	public function init_hooks() {
		add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
		add_filter( 'woocommerce_thankyou_order_received_text', array($this, 'user_want_store'), 10, 2);
		add_filter('wc_order_statuses', array($this, 'add_custom_order_status'));
		add_action('init', array($this, 'register_custom_order_status'));
		add_filter('woocommerce_payment_complete_order_status', array($this, 'set_payment_complete_order_status'), 10, 2);
		add_action('wp_ajax_user_wants_keeping', array($this, 'user_wants_keeping'));
		add_action('wp_ajax_user_wants_sending', array($this, 'user_wants_sending'));
		add_filter('cron_schedules', array($this, 'add_trusted_monthly_schedule'));
		add_action('init', array($this, 'schedule_monthly_shipping'));
		add_action('proccess_monthly_shipping', array($this, 'proccess_keep_trusted_orders'));
		add_action('proccess_monthly_shipping', array($this, 'proccess_pending_determine_orders'));
	}

	public function enqueue_assets() {

		$script_path = TRUSTED_PRODUCT_BASKET_ASSETS_PATH . '/scripts/index.js';
		$style_path = TRUSTED_PRODUCT_BASKET_ASSETS_PATH . '/styles/style.css';

		$localized_data = array(
			'url'=> admin_url('admin-ajax.php'),
			'nonce'=> wp_create_nonce('incomplete_order_nonce')
		);

		wp_enqueue_style('trusted-product-style', TRUSTED_PRODUCT_BASKET_ASSETS_URL . 'styles/style.css', NULL, filemtime($style_path));
		
		wp_enqueue_script('trusted-product-script', TRUSTED_PRODUCT_BASKET_ASSETS_URL . 'scripts/index.js', NULL, filemtime($script_path), true);
		wp_localize_script('trusted-product-script', 'ajax_object', $localized_data);

	}

	public function user_want_store($message, $order) {

		$order_id = $order->get_id();

		$is_confirmed = $order->get_status() === 'pending-determine' ? 'false' : 'true';

		$template = include_once TRUSTED_PRODUCT_BASKET_TEMPLATES_PATH . '/user-willing-form.php';

		return $template;
	}

	public function add_custom_order_status($order_statuses) {
		$order_statuses['wc-pending-determine'] = 'در انتظار تعیین وضعیت ارسال';
		$order_statuses['wc-keep-trusted'] = 'در سبد کالایه امانی';
		$order_statuses['wc-trusted-finished'] = 'ارسال از سبد امانی';
		return $order_statuses;
	}

	public function register_custom_order_status() {
		register_post_status('wc-pending-determine', array(
			'label'=> 'در انتظار تعیین وضعیت ارسال',
			'public'=> true,
			'exclude_from_search'=> false,
			'show_in_admin_all_list'=>true,
			'show_in_admin_status_list'=> true,
			'label_count'=> _n_noop('در انتظار تعیین وضعیت ارسال (%s)', 'در انتظار تعیین وضعیت ارسال (%s)')
		));
		register_post_status('wc-keep-trusted', array(
			'label'=> 'در سبد کالایه امانی',
			'public'=> true,
			'exclude_from_search'=> false,
			'show_in_admin_all_list'=> true,
			'show_in_admin_status_list'=>true,
			'label_count'=> _n_noop('در سبد کالایه امانی (%s)', 'در سبد کالایه امانی (%s)')
		));
		register_post_status('wc-trusted-finished', array(
			'label'=> 'ارسال از سبد امانی',
			'public'=> true,
			'show_in_admin_all_list'=> true,
			'show_in_admin_status_list'=> true,
			'exclude_from_search'=> false,
			'label_count'=> _n_noop('ارسال از سبد امانی (%s)', 'ارسال از سبد امانی (%s)')
		));
	}

	public function set_payment_complete_order_status($status, $order_id) {
		
		$order = wc_get_order($order_id);

		if($order) {
			update_post_meta($order_id, 'shipment_deadline', strtotime('+1 minute', time()));
			return 'wc-pending-determine';
		}

		return $status;
	}

	public function user_wants_keeping() {
		
		if(isset($_POST['order_id'])) {
			$order_id = sanitize_text_field($_POST['order_id']);

			$order_id = intval($order_id);
			$order = wc_get_order($order_id);
		
			if($order) {

				if( ! $order->get_status() === 'pending-determine' ) {
					wp_send_json_error(array(
						'message'=> 'malicious request'
					));
				}

				if($order->update_status('wc-keep-trusted')) {

					update_post_meta($order_id, 'shipment_deadline', '');

					$template = 'customer-keep-trusted';
					$order_owner_phone_number = $order->get_billing_phone();
					$order_total_price = $order->get_total();

					$customer_status = self::send_sms('customer-keep-trusted', $order_owner_phone_number, $order_id, $order_total_price );
					$admin_status = self::send_sms('admin-keep-trusted', '{ADMIN-PHONE-NUMBER}', $order_id);

					$order_owner = $order->get_user_id();
					$shipment_date = get_user_meta($order_owner, 'first_keep_trusted_date', true);

					if(!$shipment_date) {
						$shipment_date = strtotime('+5 minutes', time());
						update_user_meta($order_owner, 'first_keep_trusted_date', $shipment_date);
					}

					wp_send_json_success(array(
						'customer status'=> $customer_status,
						'admin status'=> $admin_status,
						'order ID'=> $order->ID,
						'user date'=> $shipment_date
					), 200);
				}

				wp_send_json_error(array(
					'message'=> 'an error occured'
				));

			}

			wp_send_json_error(array(
				'message'=> 'invalid order id'
			));
		} 

		wp_send_json_error(array(
			'message'=> 'invalid request'
		));
	}

	public function user_wants_sending() {
		
		if(isset($_POST['order_id'])) {
			$order_id = sanitize_text_field($_POST['order_id']);

			$order_id = intval($order_id);
			$order = wc_get_order($order_id);

			if($order) {

				if( ! $order->get_status() === 'pending-determine' ) {
					wp_send_json_error(array(
						'message'=> 'malicious request'
					));
				}

				if($order->update_status('wc-processing')) {
					update_post_meta($order_id, 'shipment_deadline', '');
					wp_send_json_success(array(
						'order status'=> 'wc-processing',
					), 200);
				} 

				wp_send_json_error(array(
					'message'=> 'an error occured'
				));

			}
			
			wp_send_json_error(array(
				'message'=> 'invalid order id'
			));
		}

		wp_send_json_error(array(
			'message'=> 'invalid request'
		));
	}

	public function add_trusted_monthly_schedule($new_schedules) {
		$new_schedules['trusted_basket_schudule'] = array(
			'interval'=> 70,
			'display'=> 'هر 70 ثانیه'
		);
		return $new_schedules;
	}

	public function schedule_monthly_shipping() {
		if(!wp_next_scheduled('proccess_monthly_shipping')) {
			wp_schedule_event(strtotime('+10 seconds', time()), 'trusted_basket_schudule', 'proccess_monthly_shipping');
		}
	}

	public function proccess_keep_trusted_orders() {
		
		$trusted_users = get_users(array(
			'meta_query'=>array(
				'relation'=> 'AND',
				array(
					'key'=> 'first_keep_trusted_date',
					'compare'=> 'EXISTS' 
				),
				array(
					'key'=> 'first_keep_trusted_date',
					'value'=> 0,
					'compare'=> '>',
					'type'=> 'NUMERIC'
				),
				array(
					'key'=> 'first_keep_trusted_date',
					'value'=> time(),
					'compare'=> '<=',
					'type'=> 'NUMERIC'
				)
			)
		));

		if(count($trusted_users) == 0) return;

		foreach($trusted_users as $user) {
			$user_id = $user->ID;
			
			$orders = wc_get_orders(array(
				'customer'=> $user_id,
				'status'=> 'wc-keep-trusted'
			));

			if( ! $orders OR count($orders) == 0) {
				update_user_meta(6, 'first_keep_trusted_date', '');
				return;
			} 

			$customer_nickname = get_user_meta($user_id, 'nickname', true);
			$customer_phone_number = get_user_meta($user_id, 'billing_phone', true);
			$total_items_count = 0;
			$total_items_price = 0;

			foreach($orders as $order) {
				
				if(!$order->update_status('wc-trusted-finished')) $this->orders_proccessing_failed = true;

				if(!$this->orders_proccessing_failed) {
					$total_items_count += $order->get_item_count();
					$total_items_price += $order->get_total();
				}

			}

			if(!$this->orders_proccessing_failed) {
				update_user_meta($user_id, 'first_keep_trusted_date', '');
				self::send_sms('admin-send-trusted', '{ADMIN-PHONE-NUMBER}', $customer_nickname);
				self::send_sms('customer-send-trusted', $customer_phone_number, $total_items_count, $total_items_price);
			}
		}
	}

	public function proccess_pending_determine_orders () {
		
		$orders = wc_get_orders(array(
			'status'=> 'wc-pending-determine',
			'meta_key'=> 'shipment_deadline',
			'meta_value'=> time(),
			'meta_compare'=> '<='
		));

		if(!empty($orders)) {
			foreach($orders as $order) {
				update_post_meta($order->ID, 'shipment_deadline', '');
				$order->update_status('wc-processing');
			}
		}
	}

	public static function send_sms($template = NULL, $receptor = NULL, $token1 = NULL, $token2 = NULL, $token3 = NULL) {
		
		$url = 'https://api.kavenegar.com/v1/{API-Key}/verify/lookup.json';

		$ch = curl_init();

		$data= array(
			'receptor'=> $receptor,
			'template'=> $template,
			'token'=> $token1,
			'token2'=> $token2,
			'token3'=> $token3
		);

		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

		$response = json_decode(curl_exec($ch));

		curl_close($ch);

		return $response->return->status;
	}
 
}