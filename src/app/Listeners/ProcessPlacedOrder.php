<?php

namespace App\Listeners;

use App\Events\OrderPlaced;
use App\Jobs\SendOrderConfirmationEmailJob;
use App\Jobs\UpdateOrderStockJob;

class ProcessPlacedOrder
{
    public function handle(OrderPlaced $event): void
    {
        SendOrderConfirmationEmailJob::dispatch($event->order->id);
        UpdateOrderStockJob::dispatch($event->order->id);
    }
}
