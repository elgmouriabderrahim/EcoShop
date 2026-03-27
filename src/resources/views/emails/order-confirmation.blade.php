<h1>Thank you for your EcoShop order, {{ $order->user->full_name }}.</h1>
<p>Your order #{{ $order->id }} has been received and is being processed.</p>
<p>Total: ${{ number_format($order->total_amount, 2) }}</p>
<p>We appreciate your support for ecological products.</p>
