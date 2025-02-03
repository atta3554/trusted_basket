<div class="trusted_product_basket-box" data-order-id="<?php echo $order_id ?>" data-confirmed="<?php echo $is_confirmed ?>">
	<p class="woocommerce-notice woocommerce-notice--success woocommerce-thankyou-order-received">متشکریم, سفارش شما دریافت شد</p>
	<div class="check-user-willing">
	<?php if($is_confirmed === 'true') : ?><div class="trusted-proccess-finished"></div> <?php endif; ?>
		<p>آیا مایلید محصول بصورت امانی نگهداری شود؟</p>
		<div>
			<button>بله</button>
			<button>خیر</button>
		</div>
	</div>
</div>