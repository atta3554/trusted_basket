<?php

if( ! defined('ABSPATH') ) exit(); // Exit if accessed directly

class Shipment_Rate {

	private static $instance;

	public static function instance() {
		if( ! self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function __construct() {
		add_filter('woocommerce_package_rates', array($this, 'custom_free_shipping_condition'), 10, 2);
		add_action('woocommerce_cart_totals_before_shipping', array($this, 'custom_shipping_messages'));
	}

	private static function has_shipment_date() {
		$current_user = wp_get_current_user();
		$user_id = $current_user->ID;
		$shipment_date = get_user_meta($user_id, 'first_keep_trusted_date', true);
		$has_shipment_date = ($shipment_date) ? true : false;
		return $has_shipment_date;
	}

	public function custom_free_shipping_condition($rates, $package) {

		if(self::has_shipment_date()) {
			foreach($rates as $rate_id=>$rate) {
				$rates[$rate_id]->cost = 0;

				if( ! empty($rates[$rate_id]->taxes) ) {
					$rates[$rate_id]->taxes = array_map(function() {
						return 0;
					}, $rates[$rate_id]->taxes);
				}
			}
		}

		return $rates;
	}

	public function custom_shipping_message() {
		if(self::has_shipment_date()) {
			echo '<h3>آفرین! نرخ حمل و نقل برایه شما رایگان است</h3>';
		}
	}
}