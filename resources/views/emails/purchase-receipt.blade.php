<h1>Дякуємо за покупку на 3Dify</h1>
<p>Замовлення {{ $order->number }} оплачено. Файли доступні в особистому кабінеті.</p>
@foreach($order->items as $item)
    <p>{{ $item->product->localized('title') }} - {{ number_format((float) $item->price, 2) }} {{ $item->currency }}</p>
@endforeach
