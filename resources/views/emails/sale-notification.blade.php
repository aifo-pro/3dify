<h1>У вас новий продаж</h1>
<p>Замовлення {{ $order->number }} оплачено покупцем {{ $order->user->name }}.</p>
@foreach($order->items as $item)
    <p>{{ $item->product->localized('title') }} - {{ number_format((float) $item->price, 2) }} {{ $item->currency }}</p>
@endforeach
