<?php

if( ! defined('ABSPATH') ) exit(); // Exit if accessed directly

class My_Account_Trusted_Products {

	private static $instance;
	private $process_failed;

	public static function instance() {
		if( ! self::$instance) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function __construct() {

		$this->process_failed = false;

		add_filter('woocommerce_account_menu_items', array($this, 'add_keep_trusted_menu_item'), 20, 1);
		add_action('init', array($this, 'add_keep_trusted_endpoint'));
		add_action('woocommerce_account_my-trusted-products_endpoint', array($this, 'display_user_keep_trusted_orders'));
		add_action('wp_ajax_send_user_trusted_products', array($this, 'send_user_trusted_products'));
	}

	public function add_keep_trusted_menu_item($items) {
		
		$logout = $items['customer-logout'];
		unset($items['customer-logout']);

		$items['my-trusted-products'] = 'کالاهایه امانیه من';
		$items['customer-logout'] = $logout;
		return $items;
	}

	public static function add_keep_trusted_endpoint() {
		add_rewrite_endpoint('my-trusted-products', EP_ROOT | EP_PAGES);
	}

	public function display_user_keep_trusted_orders() {

		$user_id = get_current_user_id();
		
		if(!$user_id) {
			echo 'لطفا برایه دیدن کالاهایه امانی خود ابتدا وارد شوید.';
			return;
		}

		$orders = wc_get_orders(array(
			'status'=> 'wc-keep-trusted',
			'customer_id'=> $user_id
		));

		if(!empty($orders)) {
			include_once TRUSTED_PRODUCT_BASKET_TEMPLATES_PATH . '/user-trusted-products.php';
		} else {
			echo '<h2>در حال حاظر شما کالایه امانی ندارید.</h2>';
		}

	}

	public function send_user_trusted_products() {
		
		$customer_id = get_current_user_id();
		$customer_nickname = get_user_meta($customer_id, 'nickname', true);
		$customer_phone_number = get_user_meta($customer_id, 'billing_phone', true);
		$total_items_count = 0;
		$total_items_price = 0;

		$user_trusted_orders = wc_get_orders(array(
			'status'=> 'keep-trusted',
			'customer_id'=> $customer_id
		));

		foreach($user_trusted_orders as $order) {
			
			if(!$order->update_status('wc-trusted-finished')) $this->process_failed = true;
				
			if(!$this->process_failed) {
				$total_items_count += $order->get_item_count();
				$total_items_price += $order->get_total();
			}
		}

		if(!$this->process_failed) {
			update_user_meta($customer_id, 'first_keep_trusted_date', '');
			Trusted_Product_Basket_Main::send_sms('admin-send-trusted', '09032512253', $customer_nickname);
			Trusted_Product_Basket_Main::send_sms('customer-send-trusted', $customer_phone_number, $total_items_count, $total_items_price);
			wp_send_json_success(array('message'=> 'success'));
		} else wp_send_json_error(array('message'=> 'error'));

	}
}