jQuery(document).ready(function ($) {
	let isConfirmed = $('.trusted_product_basket-box')?.data('confirmed') === false ? false : true;
	function sendAjaxRequest (param, elem) {
		let $this = $(elem);
		let orderId = $this.closest('.trusted_product_basket-box')?.data('order-id')
		$this.addClass('pending')
		$.ajax({
			url: ajax_object.url,
			type: "POST",
			data: {
				action: param,
				order_id: orderId
			},
			success: function (res) {
				$this.removeClass('pending');
			},
			error: function (error) {
				$this.removeClass('pending');
				alert('an error occured! please try again.');
			},
			complete: function () {
				$this.closest('.check-user-willing')?.prepend(`
				<div class='trusted-proccess-finished'>
					<svg width="60px" height="60px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
						<path d="M16.0303 10.0303C16.3232 9.73744 16.3232 9.26256 16.0303 8.96967C15.7374 8.67678 15.2626 8.67678 14.9697 8.96967L10.5 13.4393L9.03033 11.9697C8.73744 11.6768 8.26256 11.6768 7.96967 11.9697C7.67678 12.2626 7.67678 12.7374 7.96967 13.0303L9.96967 15.0303C10.2626 15.3232 10.7374 15.3232 11.0303 15.0303L16.0303 10.0303Z" fill="#61CE70" />
						<path fill-rule="evenodd" clip-rule="evenodd" d="M12 1.25C6.06294 1.25 1.25 6.06294 1.25 12C1.25 17.9371 6.06294 22.75 12 22.75C17.9371 22.75 22.75 17.9371 22.75 12C22.75 6.06294 17.9371 1.25 12 1.25ZM2.75 12C2.75 6.89137 6.89137 2.75 12 2.75C17.1086 2.75 21.25 6.89137 21.25 12C21.25 17.1086 17.1086 21.25 12 21.25C6.89137 21.25 2.75 17.1086 2.75 12Z" fill="#1C274C"/>
					</svg>
				</div>
				`);
				if($this.hasClass('request-send-products')) {
					$this.prepend(`
					<div class='trusted-proccess-finished'>
						<svg width="60px" height="60px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
							<path d="M16.0303 10.0303C16.3232 9.73744 16.3232 9.26256 16.0303 8.96967C15.7374 8.67678 15.2626 8.67678 14.9697 8.96967L10.5 13.4393L9.03033 11.9697C8.73744 11.6768 8.26256 11.6768 7.96967 11.9697C7.67678 12.2626 7.67678 12.7374 7.96967 13.0303L9.96967 15.0303C10.2626 15.3232 10.7374 15.3232 11.0303 15.0303L16.0303 10.0303Z" fill="#61CE70" />
							<path fill-rule="evenodd" clip-rule="evenodd" d="M12 1.25C6.06294 1.25 1.25 6.06294 1.25 12C1.25 17.9371 6.06294 22.75 12 22.75C17.9371 22.75 22.75 17.9371 22.75 12C22.75 6.06294 17.9371 1.25 12 1.25ZM2.75 12C2.75 6.89137 6.89137 2.75 12 2.75C17.1086 2.75 21.25 6.89137 21.25 12C21.25 17.1086 17.1086 21.25 12 21.25C6.89137 21.25 2.75 17.1086 2.75 12Z" fill="#1C274C"/>
						</svg>
					</div>
					`);
				}
				isConfirmed = true;
			}
		})
	}

	$('.check-user-willing > div > button').on('click', function () {
		if(isConfirmed === false) {
			let action = $(this).is(':first-child') ? 'user_wants_keeping' : 'user_wants_sending'
			sendAjaxRequest(action, this);
		} else {
			alert('درخواست شما ثبت شد. لطفا مجددا تلاش نفرمایید')
		}
	});

	$('.request-send-products.woocommerce-Button').on('click', function () {
		sendAjaxRequest('send_user_trusted_products', this);
	});
});