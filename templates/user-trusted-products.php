<table>
	<thead>
		<tr>
			<th>سفارش</th>
			<th>تاریخ</th>
			<th>مجموع</th>
			<th>عملیات</th>
		</tr>
	</thead>
	<tbody>
	<?php foreach($orders as $order) : ?>
	<?php $item_count = $order->get_item_count() - $order->get_item_count_refunded() ?>
		<tr>
			<td><a href="<?= esc_url($order->get_view_order_url()) ?>"><?= esc_html($order->get_order_number()) ?></a></td>
			<td datetime="<?= esc_attr($order->get_date_created()->date('c')) ?>"><time><?= esc_html( wc_format_datetime( $order->get_date_created() ) ) ?></time></td>
			<td><?= wp_kses_post( sprintf( _n( '%1$s for %2$s item', '%1$s for %2$s items', $item_count, 'woocommerce' ), $order->get_formatted_order_total(), $item_count ) ) ?></td>
			<td><a href="<?= esc_url($order->get_view_order_url()) ?>">مشاهده</a></td>
		</tr>
	<?php endforeach; ?>
	</tbody>
</table>

<button class="request-send-products woocommerce-Button button">درخواسته ارساله کالاها</button>