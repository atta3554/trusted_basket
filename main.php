<?php

/*
* Plugin Name: trusted woocommerce basket
* Plugin URI: https://github.com/atta3554
* Description: maintain products for users along it's desirable period
* Version: 1.0.0
* author: ata ashrafi
* author URI: mailto:ata.ashrafi3554@gmail.com
* Text Domain: trusted-product-basket
* Domain Path: /languages
*/

// Exit if accessed directly
if( ! defined('ABSPATH') ) {
	exit();
}

// Include main plugin class
require_once plugin_dir_path(__FILE__) . 'includes/class-trusted-product-basket.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-myaccount-fields.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-shipment-rate.php';

// Initialize main plugin class
function trusted_product_basket_init() {
	return Trusted_Product_Basket_Main::instance();
}

function myaccount_trusted_products_init() {
	return My_Account_Trusted_Products::instance();
}

function shipment_rate() {
	return Shipment_Rate::instance();
}

trusted_product_basket_init();
myaccount_trusted_products_init();
shipment_rate();

register_activation_hook(__FILE__, function () {
	My_Account_Trusted_Products::add_keep_trusted_endpoint();
	flush_rewrite_rules();
});

register_deactivation_hook(__FILE__, function () {
	flush_rewrite_rules();
});